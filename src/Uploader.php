<?php
declare(strict_types=1);

namespace Upload;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Log\Log;
use Cake\Utility\Text;
use Exception;
use Laminas\Diactoros\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;
use Upload\Exception\UploadException;

/**
 * Class Uploader
 *
 * @package Upload
 */
class Uploader
{
    use InstanceConfigTrait;

    public const UPLOAD_ERR_OK = UPLOAD_ERR_OK; // 0
    public const UPLOAD_ERR_INI_SIZE = UPLOAD_ERR_INI_SIZE; // 1
    public const UPLOAD_ERR_FORM_SIZE = UPLOAD_ERR_FORM_SIZE; // 2
    public const UPLOAD_ERR_PARTIAL = UPLOAD_ERR_PARTIAL; // 3
    public const UPLOAD_ERR_NO_FILE = UPLOAD_ERR_NO_FILE; // 4
    public const UPLOAD_ERR_NO_TMP_DIR = UPLOAD_ERR_NO_TMP_DIR; // 6
    public const UPLOAD_ERR_CANT_WRITE = UPLOAD_ERR_CANT_WRITE; // 7
    public const UPLOAD_ERR_EXTENSION = UPLOAD_ERR_EXTENSION; // 8
    public const UPLOAD_ERR_MIN_FILE_SIZE = 100;
    public const UPLOAD_ERR_MAX_FILE_SIZE = 101;
    public const UPLOAD_ERR_MIME_TYPE = 102;
    public const UPLOAD_ERR_FILE_EXT = 103;
    public const UPLOAD_ERR_FILE_EXISTS = 104;
    public const UPLOAD_ERR_STORE_UPLOAD = 105;

    protected array $_defaultConfig = [
        'uploadDir' => null,
        'minFileSize' => 1,
        'maxFileSize' => 2097152, // 2MB
        'mimeTypes' => '*',
        'fileExtensions' => '*',
        'multiple' => false,
        'slug' => '_',
        'slugFilename' => false,
        'hashFilename' => false,
        'uniqueFilename' => false,
        'overwrite' => false,
        'saveAs' => null, // filename override
        //'pattern' => false, // @todo Implement me
    ];

    /**
     * @var array
     */
    protected array $_data;

    /**
     * @var array|null
     */
    protected ?array $_result;

    /**
     * Constructor
     *
     * @param array|string $config Uploader config
     * @param array $data Upload data
     * @throws \Exception
     */
    public function __construct(array|string $config = [], array $data = [])
    {
        // Load config
        if (is_string($config)) {
            if (!Configure::check('Upload.' . $config)) {
                throw new Exception(__d('upload', 'Invalid Upload Configuration: {0}', $config));
            }
            $config = (array)Configure::read('Upload.' . $config);
        }

        // Fallback upload dir
        if (!isset($config['uploadDir'])) {
            //@todo Make fallback upload dir configurable (constant/config/setting)
            $config['uploadDir'] = TMP . 'uploads' . DS;
        }

        // Validate upload dir
        //if (!is_dir($config['uploadDir']) || !is_writable($config['uploadDir'])) {
        //    throw new \Exception(__d('upload', 'Upload directory not writeable: {0}', $config['uploadDir']));
        //}

        $this->setConfig($config);
        $this->setUploadData($data);
    }

    /**
     * Upload data setter
     * Only for testing purposes. Pass upload data to the upload() method instead.
     *
     * @param array $data Upload data
     * @return $this
     */
    public function setUploadData(array $data = [])
    {
        $this->_data = $data;

        return $this;
    }

    /**
     * Upload data setter
     *
     * @param array $data Upload data
     * @return $this
     * @deprecated use setUploadData() instead
     */
    public function setData(array $data = [])
    {
        return $this->setUploadData($data);
    }

    /**
     * Upload dir setter
     *
     * @param string $dir Absolute path to upload directory
     * @return $this
     * @throws \Exception
     */
    public function setUploadDir(string $dir)
    {
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new Exception(__d('upload', 'Upload directory not writable'));
        }

        $this->setConfig('uploadDir', $dir);

        return $this;
    }

    /**
     * Minimum upload file size in bytes
     *
     * @param int $sizeInBytes File size in bytes
     * @return $this
     */
    public function setMinFileSize(int $sizeInBytes)
    {
        $this->setConfig('minFileSize', (int)$sizeInBytes);

        return $this;
    }

    /**
     * Maximum upload file size in bytes
     *
     * @param int $sizeInBytes File size in bytes
     * @return $this
     */
    public function setMaxFileSize(int $sizeInBytes)
    {
        $this->setConfig('maxFileSize', (int)$sizeInBytes);

        return $this;
    }

    /**
     * Allowed upload file mime type(s)
     *
     * @param array|string $val Allowed mime type(s)
     * @return $this
     */
    public function setMimeTypes(string|array $val)
    {
        $this->setConfig('mimeTypes', $val);

        return $this;
    }

    /**
     * Override the target filename
     *
     * @param string $val Filename with file extension
     * @return $this
     */
    public function setSaveAs(string $val)
    {
        $this->setConfig('saveAs', $val);

        return $this;
    }

    /**
     * Allowed upload file extension(s)
     *
     * @param array|string $ext Allowed file extensions
     * @return $this
     */
    public function setFileExtensions(string|array $ext)
    {
        $this->setConfig('fileExtensions', $ext);

        return $this;
    }

    /**
     * Enable/Disable filename hashing
     *
     * @param bool $enable Enable flag
     * @return $this
     */
    public function enableHashFilename(bool $enable)
    {
        $this->setConfig('hashFilename', (bool)$enable);

        return $this;
    }

    /**
     * Enable/Disable unique filename
     *
     * @param bool $enable Enable flag
     * @return $this
     */
    public function enableUniqueFilename(bool $enable)
    {
        $this->setConfig('uniqueFilename', (bool)$enable);

        return $this;
    }

    /**
     * Perform upload
     *
     * @param \Laminas\Diactoros\UploadedFile|\Psr\Http\Message\UploadedFileInterface|array $uploadData Upload data
     * @param array $options Upload options
     * @return array
     * @throws \Exception
     */
    public function upload(mixed $uploadData = null, array $options = []): array
    {
        $this->_result = null;
        $uploadData = $uploadData ?: $this->_data;
        $options = array_merge(['exceptions' => false], $options);

        if (!$uploadData || empty($uploadData)) {
            throw new UploadException(self::UPLOAD_ERR_NO_FILE);
        }

        if ($this->_config['multiple']) {
            return $this->_result = $this->_uploadMultiple($uploadData, $options['exceptions']);
        }
        $this->_result = $this->_upload($uploadData, $options['exceptions']);

        return $this->_result;
    }

    /**
     * @return array|null
     */
    public function getResult(): ?array
    {
        return $this->_result;
    }

    /**
     * @param array $data Upload data
     * @param bool $throwExceptions If TRUE throws exception instead of returning an upload_err. Defaults to FALSE
     * @return array
     * @throws \Exception
     */
    protected function _uploadMultiple(array $data, bool $throwExceptions = false): array
    {
        $result = [];
        foreach ($data as $_data) {
            $result[] = $this->_upload($_data, $throwExceptions);
        }

        return $result;
    }

    /**
     * @param \Laminas\Diactoros\UploadedFile|\Psr\Http\Message\UploadedFileInterface|array $upload Upload data
     * @param bool $throwExceptions If TRUE throws exception instead of returning an upload_err. Defaults to FALSE
     * @return array
     * @throws \Exception
     */
    protected function _upload($upload, bool $throwExceptions = false): array
    {
        try {
            if (is_array($upload)) {
                if ($upload['error'] > 0) {
                    throw new UploadException($upload['error']);
                }

                $upload = new UploadedFile(
                    $upload['tmp_name'] ?? null,
                    $upload['size'] ?? 0,
                    $upload['error'] ?? 0,
                    $upload['name'] ?? null,
                    $upload['type'] ?? null
                );
            }
            if (!($upload instanceof UploadedFileInterface)) {
                throw new Exception('Invalid upload data');
            }

            $this->_validateUpload($upload);
            $result = $this->_processUpload($upload);
        } catch (Exception $ex) {
            if ($throwExceptions === true) {
                throw $ex;
            }

            $data['upload_err'] = $ex->getMessage();

            return $data;
        }

        return $result;
    }

    /**
     * @param \Psr\Http\Message\UploadedFileInterface $upload Upload data
     * @return bool
     */
    protected function _validateUpload(UploadedFileInterface $upload): bool
    {
        $config = $this->_config;

        // validate upload data
        if (!$upload || !$upload->getStream()) {
            throw new UploadException(UPLOAD_ERR_NO_FILE);
        }

        // check upload error
        if ($upload->getError() > 0) {
            throw new UploadException($upload->getError());
        }

        // check upload dir
        //@TODO D.R.Y.
        if (!is_dir($config['uploadDir']) || !is_writeable($config['uploadDir'])) {
            //$upload['error'] = UPLOAD_ERR_CANT_WRITE;
            Log::critical('Uploader: Upload directory is not writable (' . $config['uploadDir'] . ')', ['upload']);
            throw new UploadException(UPLOAD_ERR_CANT_WRITE);
        }

        // validate size limits and mime type
        if ($upload->getSize() < $config['minFileSize']) {
            throw new UploadException(self::UPLOAD_ERR_MIN_FILE_SIZE);
        }
        if ($upload->getSize() > $config['maxFileSize']) {
            throw new UploadException(self::UPLOAD_ERR_MAX_FILE_SIZE);
        }
        if (!self::validateMimeType($upload->getClientMediaType(), $config['mimeTypes'])) {
            throw new UploadException(self::UPLOAD_ERR_MIME_TYPE);
        }

        // split basename
        [$filename, $ext, $dotExt] = self::splitBasename(trim($upload->getClientFilename()));

        // validate extension
        if (!self::validateFileExtension($ext, $config['fileExtensions'])) {
            throw new UploadException(self::UPLOAD_ERR_FILE_EXT);
        }

        return true;
    }

    /**
     * Upload Handler
     *
     * @param \Psr\Http\Message\UploadedFileInterface $upload Upload data
     * @return array
     */
    protected function _processUpload(UploadedFileInterface $upload): array
    {
        $config = $this->_config;

        //@TODO Fire event 'Upload.beforeUpload'

        // filename
        $uploadName = $upload->getClientFilename();
        [$filename, $ext, $dotExt] = self::splitBasename($uploadName);
        $ext = strtolower($ext);
        $dotExt = strtolower($dotExt);

        // slug filename
        if ($config['slugFilename']) {
            $filename = Text::slug($filename, $config['slug']);
        }

        // hash filename
        if ($config['hashFilename']) {
            $filename = sha1($filename);
        }

        // unique filename
        if ($config['uniqueFilename']) {
            $filename = uniqid($filename . $config['slug'], false);
        }

        // filename override
        if ($config['saveAs']) {
            [$filename, $ext, $dotExt] = self::splitBasename($config['saveAs']);
        }


        $basename = $filename . $dotExt;
        $path = $config['uploadDir'];

        // build target file path
        $target = $path . $basename;
        Log::debug("Uploading file to $path", 'upload');

        if (file_exists($target) && $config['overwrite'] !== true) {
            $i = 0;
            $_filename = $filename;
            do {
                $filename = $_filename . '__' . ++$i;
                $basename = $filename . $dotExt;
                $target = $path . $basename;
            } while (file_exists($target) === true);
        }
        Log::debug("Saving uploaded file to $target", 'upload');

        //move uploaded file to target location
        try {
            $upload->moveTo($target);
        } catch (Exception $ex) {
            Log::critical($ex->getMessage(), ['upload', 'uploader']);
            throw new UploadException(self::UPLOAD_ERR_STORE_UPLOAD);
        }

        $uploadedFile = [
            'name' => $upload->getClientFilename(), // file.txt
            'type' => $upload->getClientMediaType(), // text/plain
            'size' => $upload->getSize(), // 1234 (bytes)
            'path' => $target, // /path/to/uploaded/file
            'basename' => $basename, // file.txt
            'filename' => $filename, // file
            'ext' => $ext, // txt
            'dotExt' => $dotExt, // .txt
            'ts' => time(),
        ];

        //@TODO Fire event 'Upload.afterUpload'
        return $uploadedFile;
    }

    /**
     * Split basename
     *
     * @param string $basename File basename (filename with extension)
     * @return array Returns in format array($filename, $ext, $dotExt)
     */
    public static function splitBasename(string $basename): array
    {
        $ext = $dotExt = null;
        $filename = $basename;

        if (strrpos($basename, '.') !== false) {
            $parts = explode('.', $basename);
            $ext = array_pop($parts);
            $dotExt = '.' . $ext;
            $filename = join('.', $parts);
        }

        return [$filename, $ext, $dotExt];
    }

    /**
     * Validate mime type
     *
     * For the $allowed key use wildcard '*' for all mime types
     * or a list of mime types like ['image/png', 'image/jpeg']
     *
     * @param string $mime The mime type to check
     * @param array|string $allowed List of allowed mime types
     * @return bool
     */
    public static function validateMimeType(string $mime, array|string $allowed = []): bool
    {
        if (is_string($allowed)) {
            if ($allowed == '*') {
                return true;
            }

            $allowed = array_map('strtolower', explode(',', $allowed));
        }

        $mime = explode('/', $mime);

        foreach ($allowed as $type) {
            $type = explode('/', $type);
            if ($mime[0] != $type[0]) {
                continue;
            }

            if ($type[1] == '*' || $mime[1] == $type[1]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate file extension
     *
     * @param string $ext The file extension to check
     * @param array|string $allowed List of allowed extensions. Use '*' for all extensions
     * @return bool
     */
    public static function validateFileExtension(string $ext, array|string $allowed = []): bool
    {
        if (is_string($allowed)) {
            if ($allowed == '*') {
                return true;
            }

            $allowed = array_map('strtolower', explode(',', $allowed));
        }

        return in_array(strtolower($ext), $allowed);
    }
}

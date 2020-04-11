<?php
declare(strict_types=1);

namespace Upload;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Log\Log;
use Cake\Utility\Text;
use Upload\Exception\UploadException;

/**
 * Class Uploader
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

    protected $_defaultConfig = [
        'uploadDir' => null,
        'minFileSize' => 1,
        'maxFileSize' => 2097152, // 2MB
        'mimeTypes' => '*',
        'fileExtensions' => '*',
        'multiple' => false,
        'slug' => "_",
        'hashFilename' => false,
        'uniqueFilename' => true,
        'overwrite' => false,
        'saveAs' => null, // filename override
        //'pattern' => false, // @todo Implement me
    ];

    protected $_data;

    protected $_result;

    /**
     * Constructor
     *
     * @param array|string $config Uploader config
     * @param array $data Upload data
     * @throws \Exception
     */
    public function __construct($config = [], array $data = [])
    {
        // Load config
        if (is_string($config) && !Configure::check('Upload.' . $config)) {
            throw new \Exception(__d('upload', 'Invalid Upload Configuration: {0}', $config));
        } elseif (is_string($config)) {
            $config = (array)Configure::read('Upload.' . $config);
        }

        // Fallback upload dir
        if (!isset($config['uploadDir'])) {
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
     *
     * @deprecated use setUploadData() instead
     */
    public function setData($data = [])
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
    public function setUploadDir($dir)
    {
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \Exception(__d('upload', 'Upload directory not writable'));
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
    public function setMinFileSize($sizeInBytes)
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
    public function setMaxFileSize($sizeInBytes)
    {
        $this->setConfig('maxFileSize', (int)$sizeInBytes);

        return $this;
    }

    /**
     * Allowed upload file mime type(s)
     *
     * @param string|array $val Allowed mime type(s)
     * @return $this
     */
    public function setMimeTypes($val)
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
    public function setSaveAs($val)
    {
        $this->setConfig('saveAs', $val);

        return $this;
    }

    /**
     * Allowed upload file extension(s)
     *
     * @param string|array $ext Allowed file extensions
     * @return $this
     */
    public function setFileExtensions($ext)
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
    public function enableHashFilename($enable)
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
    public function enableUniqueFilename($enable)
    {
        $this->setConfig('uniqueFilename', (bool)$enable);

        return $this;
    }

    /**
     * Perform upload
     *
     * @param array $uploadData Upload data
     * @param array $options Upload options
     * @return array
     * @throws \Exception
     */
    public function upload($uploadData = null, $options = []): array
    {
        $this->_result = null;
        $uploadData = $uploadData ?: $this->_data;
        $options = array_merge(['exceptions' => false], $options);

        if ($this->_config['multiple']) {
            $this->_result = $this->_uploadMultiple($uploadData, $options['exceptions']);
        } else {
            $this->_result = $this->_upload($uploadData, $options['exceptions']);
        }

        return $this->_result;
    }

    /**
     * @return null|array
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
    protected function _uploadMultiple($data, $throwExceptions = false): array
    {
        $result = [];
        foreach ($data as $_data) {
            $result[] = $this->_upload($_data, $throwExceptions);
        }

        return $result;
    }

    /**
     * @param array $data Upload data
     * @param bool $throwExceptions If TRUE throws exception instead of returning an upload_err. Defaults to FALSE
     * @return array
     * @throws \Exception
     */
    protected function _upload($data, $throwExceptions = false): array
    {
        try {
            $this->_validateUpload($data);
            $result = $this->_processUpload($data);
        } catch (\Exception $ex) {
            if ($throwExceptions === true) {
                throw $ex;
            }

            $data['upload_err'] = $ex->getMessage();

            return $data;
        }

        return $result;
    }

    /**
     * @param array $upload Upload data
     * @return bool
     */
    protected function _validateUpload($upload): bool
    {
        $config = $this->_config;

        // validate upload data
        if (!$upload || !is_array($upload)) {
            throw new UploadException(UPLOAD_ERR_NO_FILE);
        }

        // check upload error
        if ($upload['error'] > 0) {
            throw new UploadException($upload['error']);
        }

        // check upload dir
        //@TODO D.R.Y.
        if (!is_dir($config['uploadDir']) || !is_writeable($config['uploadDir'])) {
            //$upload['error'] = UPLOAD_ERR_CANT_WRITE;
            //debug('Uploader: Upload directory is not writable (' . $config['uploadDir'] . ')');
            throw new UploadException(UPLOAD_ERR_CANT_WRITE);
        }

        // validate size limits and mime type
        if ($upload['size'] < $config['minFileSize']) {
            throw new UploadException(self::UPLOAD_ERR_MIN_FILE_SIZE);
        } elseif ($upload['size'] > $config['maxFileSize']) {
            throw new UploadException(self::UPLOAD_ERR_MAX_FILE_SIZE);
        } elseif (!self::validateMimeType($upload['type'], $config['mimeTypes'])) {
            throw new UploadException(self::UPLOAD_ERR_MIME_TYPE);
        }

        // split basename
        [$filename, $ext, $dotExt] = self::splitBasename(trim($upload['name']));

        // validate extension
        if (!self::validateFileExtension($ext, $config['fileExtensions'])) {
            throw new UploadException(self::UPLOAD_ERR_FILE_EXT);
        }

        return true;
    }

    /**
     * Upload Handler
     *
     * @param array $upload Upload data
     * @throws \Upload\Exception\UploadException
     * @return array
     */
    protected function _processUpload($upload): array
    {
        $config = $this->_config;

        //debug($upload);
        //debug($config);

        //@TODO Fire event 'Upload.beforeUpload'

        // filename
        $uploadName = strtolower(trim($upload['name']));
        [$filename, $ext, $dotExt] = self::splitBasename($uploadName);
        $filename = Text::slug($filename, $config['slug']);
        $ext = strtolower($ext);
        $dotExt = strtolower($dotExt);

        // filename override
        if ($config['saveAs']) {
            [$filename, $ext, $dotExt] = self::splitBasename($config['saveAs']);
        }

        // hash filename
        if ($config['hashFilename'] && !$config['saveAs']) {
            $filename = sha1($filename);
        }

        // unique filename
        if ($config['uniqueFilename']) {
            $filename = uniqid($filename . $config['slug'], false);
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

        //debug("Is Uploaded: " . is_uploaded_file($upload['tmp_name']));
        //debug("Move uploaded file from " . $upload['tmp_name'] . " to " . $target);

        $isUploaded = is_uploaded_file($upload['tmp_name']);

        //move uploaded file to upload dir
        //@TODO Use a StorageEngine

        if ($isUploaded) {
            if (!move_uploaded_file($upload['tmp_name'], $target)) {
                throw new UploadException(self::UPLOAD_ERR_STORE_UPLOAD);
            }
        } else {
            if (!copy($upload['tmp_name'], $target)) {
                throw new UploadException(self::UPLOAD_ERR_STORE_UPLOAD);
            }
        }

        //@TODO Return UploadFile object instance
        $uploadedFile = [
            'name' => $upload['name'], // file.txt
            'type' => $upload['type'], // text/plain
            'size' => $upload['size'], // 1234 (bytes)
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
     * @param string $basename File basename (filename with extension)
     * @return array Returns in format array($filename, $ext, $dotExt)
     */
    public static function splitBasename($basename): array
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
    public static function validateMimeType($mime, $allowed = []): bool
    {
        if (is_string($allowed)) {
            if ($allowed == "*") {
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

            if ($type[1] == "*" || $mime[1] == $type[1]) {
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
    public static function validateFileExtension($ext, $allowed = []): bool
    {
        if (is_string($allowed)) {
            if ($allowed == "*") {
                return true;
            }

            $allowed = array_map('strtolower', explode(',', $allowed));
        }

        return in_array(strtolower($ext), $allowed);
    }
}

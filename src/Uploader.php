<?php
namespace Upload;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Upload\Exception\UploadException;

/**
 * Class Uploader
 * @package Upload
 *
 * @todo Refactor with InstanceConfigTrait
 */
class Uploader
{
    const UPLOAD_ERR_MIN_FILE_SIZE = 100;
    const UPLOAD_ERR_MAX_FILE_SIZE = 101;
    const UPLOAD_ERR_MIME_TYPE = 102;
    const UPLOAD_ERR_FILE_EXT = 103;
    const UPLOAD_ERR_FILE_EXISTS = 104;
    const UPLOAD_ERR_STORE_UPLOAD = 105;

    protected $_config = [
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
     * @param array $config
     * @param null  $data
     * @throws \Exception
     */
    public function __construct($config = [], $data = null)
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
        if (!is_dir($config['uploadDir']) || !is_writable($config['uploadDir'])) {
            throw new \Exception(__d('upload', 'Upload directory not writeable: {0}', $config['uploadDir']));
        }

        $this->config($config);
        $this->setData($data);
    }

    /**
     * Configuration Getter / Setter
     *
     * @param      $key
     * @param null $val
     * @return $this|mixed
     * @throws \InvalidArgumentException
     */
    public function config($key = null, $val = null)
    {
        if ($key === null) {
            return $this->_config;
        } elseif (is_array($key)) {
            foreach ($key as $_k => $_v) {
                $this->config($_k, $_v);
            }

            return $this;
        } elseif (is_string($key) && $val !== null) {
            $this->_config[$key] = $val;

            return $this;
        } elseif (is_string($key) && array_key_exists($key, $this->_config)) {
            return $this->_config[$key];
        } else {
            throw new \InvalidArgumentException('Uploader: Invalid config key ' . $key);
        }
    }

    /**
     * Upload data setter
     *
     * @param $data
     * @return $this
     *
     * @deprecated Pass data directly to upload method
     */
    public function setData($data = [])
    {
        $this->_data = $data;

        return $this;
    }

    /**
     * Upload dir setter
     *
     * @param $dir Absolute path to upload directory
     * @return $this
     * @throws \Exception
     */
    public function setUploadDir($dir)
    {
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \Exception(__d('upload', 'Upload directory not writable'));
        }

        return $this->config('uploadDir', $dir);
    }

    /**
     * Minimum upload file size in bytes
     *
     * @param int $sizeInBytes File size in bytes
     * @return $this
     */
    public function setMinFileSize($sizeInBytes)
    {
        return $this->config('minFileSize', (int)$sizeInBytes);
    }

    /**
     * Maximum upload file size in bytes
     *
     * @param int $sizeInBytes File size in bytes
     * @return $this
     */
    public function setMaxFileSize($sizeInBytes)
    {
        return $this->config('maxFileSize', (int)$sizeInBytes);
    }

    /**
     * Allowed upload file mime type(s)
     *
     * @param string|array $type
     * @return $this
     */
    public function setMimeTypes($type)
    {
        $this->config('mimeTypes', $type);
    }

    /**
     * Allowed upload file extension(s)
     *
     * @param string|array $ext
     * @return $this
     */
    public function setFileExtensions($ext)
    {
        return $this->config('fileExtensions', $ext);
    }

    /**
     * Enable/Disable filename hashing
     *
     * @param $enable
     * @return $this
     */
    public function enableHashFilename($enable)
    {
        return $this->config('hashFilename', (bool)$enable);
    }

    /**
     * Enable/Disable unique filename
     *
     * @param $enable
     * @return $this
     */
    public function enableUniqueFilename($enable)
    {
        return $this->config('uniqueFilename', (bool)$enable);
    }

    /**
     * Perform upload
     *
     * @param array $uploadData
     * @param array $options
     *
     * @return array
     */
    public function upload($uploadData = null, $options = [])
    {
        $this->_result = null;
        $uploadData = $uploadData ?: $this->_data;
        $options = array_merge(['exceptions' => false], $options);

        if ($this->_config['multiple']) {
            $this->_result = [];
            foreach ($uploadData as $upload) {
                $this->_result[] = $this->_upload($upload, $options['exceptions']);
            }
        } else {
            $this->_result = $this->_upload($uploadData, $options['exceptions']);
        }

        return $this->_result;
    }

    public function getResult()
    {
        return $this->_result;
    }

    protected function _upload($upload, $throwExceptions = false)
    {
        try {
            $this->_validateUpload($upload);
            $result = $this->_processUpload($upload);
        } catch (\Exception $ex) {
            if ($throwExceptions === true) {
                throw $ex;
            }

            $upload['upload_err'] = $ex->getMessage();

            return $upload;
        }

        return $result;
    }

    protected function _validateUpload($upload)
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
        list($filename, $ext, $dotExt) = self::splitBasename(trim($upload['name']));

        // validate extension
        if (!self::validateFileExtension($ext, $config['fileExtensions'])) {
            throw new UploadException(self::UPLOAD_ERR_FILE_EXT);
        }

        return true;
    }

    /**
     * Upload Handler
     *
     * @param array $upload
     * @throws UploadException
     * @return array
     */
    protected function _processUpload($upload)
    {
        $config = $this->_config;

        //debug($upload);
        //debug($config);

        //@TODO Fire event 'Upload.beforeUpload'

        // filename
        $uploadName = strtolower(trim($upload['name']));
        list($filename, $ext, $dotExt) = self::splitBasename($uploadName);
        $filename = Inflector::slug($filename, $config['slug']);
        $ext = strtolower($ext);
        $dotExt = strtolower($dotExt);

        // filename override
        if ($config['saveAs']) {
            list($filename, $ext, $dotExt) = self::splitBasename($config['saveAs']);
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
        if (file_exists($target) && $config['overwrite'] !== true) {
            $i = 0;
            $_filename = $filename;
            do {
                $filename = $_filename . '__' . ++$i;
                $basename = $filename . $dotExt;
                $target = $path . $basename;
            } while (file_exists($target) === true);
        }

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
            'name' => $upload['name'],  // file.txt
            'type' => $upload['type'],  // text/plain
            'size' => $upload['size'],  // 1234 bytes
            'path' => $target,          // /path/to/uploaded/file
            'basename' => $basename,    // file.txt
            'filename' => $filename,    // file
            'ext' => $ext,              // txt
            'dotExt' => $dotExt,        // .txt
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
    public static function splitBasename($basename)
    {
        if (strrpos($basename, '.') !== false) {
            $parts = explode('.', $basename);
            $ext = array_pop($parts);
            $dotExt = '.'.$ext;
            $filename = join('.', $parts);
        } else {
            $ext = $dotExt = null;
            $filename = $basename;
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
     * @return boolean
     */
    public static function validateMimeType($mime, $allowed = [])
    {
        if (is_string($allowed)) {
            if ($allowed == "*") {
                return true;
            } else {
                $allowed = array_map('strtolower', explode(',', $allowed));
            }
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
     * @param string $ext
     * @param array|string $allowed List of allowed extensions. Use '*' for all extensions
     * @return boolean
     */
    public static function validateFileExtension($ext, $allowed = [])
    {
        if (is_string($allowed)) {
            if ($allowed == "*") {
                return true;
            } else {
                $allowed = array_map('strtolower', explode(',', $allowed));
            }
        }

        return in_array(strtolower($ext), $allowed);
    }
}

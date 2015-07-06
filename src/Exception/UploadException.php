<?php
namespace Upload\Exception;

use \Cake\Core\Exception\Exception;
use Upload\Uploader;

class UploadException extends Exception
{
    public function __construct($errCode, $code = 500)
    {
        parent::__construct(self::mapErrorMessage($errCode), $code);
    }

    /**
     * Map an upload error code to the corresponding error message
     *
     * @param int $errCode Upload error code
     * @return string Upload error message
     */
    public static function mapErrorMessage($errCode)
    {
        $errors = array(
            UPLOAD_ERR_OK => __d('upload', "Upload successful"),
            UPLOAD_ERR_INI_SIZE => __d('upload', "Maximum ini file size exceeded (%s)", ini_get('upload_max_filesize')),
            UPLOAD_ERR_FORM_SIZE => __d('upload', "Maximum form file size exceeded"),
            UPLOAD_ERR_PARTIAL => __d('upload', "File only partially uploaded"),
            UPLOAD_ERR_NO_FILE => __d('upload', "No file uploaded"),
            UPLOAD_ERR_NO_TMP_DIR => __d('upload', "Upload directory missing"), //PHP 5.0.3+
            UPLOAD_ERR_CANT_WRITE => __d('upload', "Cant write to upload directory"), //PHP 5.1.0+
            UPLOAD_ERR_EXTENSION => __d('upload', "Upload extension error"), //PHP 5.2.0+
            Uploader::UPLOAD_ERR_FILE_EXISTS => __d('upload', "File already exists"),
            Uploader::UPLOAD_ERR_FILE_EXT => __d('upload', "Invalid file extension"),
            Uploader::UPLOAD_ERR_MIME_TYPE => __d('upload', "Invalid mime type"),
            Uploader::UPLOAD_ERR_MIN_FILE_SIZE => __d('upload', "Minimum file size error"),
            Uploader::UPLOAD_ERR_MAX_FILE_SIZE => __d('upload', "Maximum file size exceeded"),
            Uploader::UPLOAD_ERR_STORE_UPLOAD => __d('upload', "Failed to store uploaded file"),
        );

        if (isset($errors[$errCode])) {
            return $errors[$errCode];
        }

        return __d('upload', "Unknown upload error");
    }
}
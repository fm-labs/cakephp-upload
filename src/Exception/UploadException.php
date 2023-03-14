<?php
declare(strict_types=1);

namespace Upload\Exception;

use Cake\Core\Exception\CakeException;
use Upload\Uploader;

class UploadException extends CakeException
{
    /**
     * UploadException constructor.
     *
     * @param int $errCode Upload error code
     */
    public function __construct(int $errCode)
    {
        parent::__construct(self::mapErrorMessage($errCode), $errCode);
    }

    /**
     * Map an upload error code to the corresponding error message
     *
     * @param int $errCode Upload error code
     * @return string Upload error message
     */
    public static function mapErrorMessage(int $errCode): string
    {
        $errors = [
            Uploader::UPLOAD_ERR_OK => __d('upload', 'Upload successful'),
            Uploader::UPLOAD_ERR_INI_SIZE => __d(
                'upload',
                'Maximum ini file size exceeded (%s)',
                ini_get('upload_max_filesize')
            ),
            Uploader::UPLOAD_ERR_FORM_SIZE => __d('upload', 'Maximum form file size exceeded'),
            Uploader::UPLOAD_ERR_PARTIAL => __d('upload', 'File only partially uploaded'),
            Uploader::UPLOAD_ERR_NO_FILE => __d('upload', 'No file uploaded'),
            Uploader::UPLOAD_ERR_NO_TMP_DIR => __d('upload', 'Upload directory missing'),
            Uploader::UPLOAD_ERR_CANT_WRITE => __d('upload', 'Cant write to upload directory'),
            Uploader::UPLOAD_ERR_EXTENSION => __d('upload', 'Upload extension error'),
            Uploader::UPLOAD_ERR_FILE_EXISTS => __d('upload', 'File already exists'),
            Uploader::UPLOAD_ERR_FILE_EXT => __d('upload', 'Invalid file extension'),
            Uploader::UPLOAD_ERR_MIME_TYPE => __d('upload', 'Invalid mime type'),
            Uploader::UPLOAD_ERR_MIN_FILE_SIZE => __d('upload', 'Minimum file size error'),
            Uploader::UPLOAD_ERR_MAX_FILE_SIZE => __d('upload', 'Maximum file size exceeded'),
            Uploader::UPLOAD_ERR_STORE_UPLOAD => __d('upload', 'Failed to store uploaded file'),
        ];

        if (isset($errors[$errCode])) {
            return $errors[$errCode];
        }

        return __d('upload', 'Unknown upload error');
    }
}

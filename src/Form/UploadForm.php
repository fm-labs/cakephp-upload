<?php
declare(strict_types=1);

namespace Upload\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use InvalidArgumentException;
use Upload\Uploader;

class UploadForm extends Form
{
    /**
     * @var \Upload\Uploader The Uploader instance
     */
    protected Uploader $uploader;

    /**
     * @var array Form data
     */
    protected array $data = [];

    /**
     * @var array
     */
    protected array $_result;

    /**
     * @param Uploader|array|string $uploaderConfig
     * @throws \Exception
     */
    public function __construct(array|string|Uploader $uploaderConfig = [])
    {
        parent::__construct();

        if ($uploaderConfig instanceof Uploader) {
            $this->uploader = $uploaderConfig;
        } else {
            $this->uploader = new Uploader($uploaderConfig);
        }

        if (!$this->uploader instanceof Uploader) {
            throw new InvalidArgumentException('Invalid uploader config');
        }
    }

    /**
     * Build upload form schema
     *
     * @param \Cake\Form\Schema $schema
     * @return \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema): Schema
    {
        $schema->addField('upload_file', ['type' => 'string']);

        return $schema;
    }

    /**
     * @return \Upload\Uploader
     * @throws \Exception
     */
    public function getUploader(): Uploader
    {
        return $this->uploader;
    }

    /**
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    protected function _execute(array $data): bool
    {
        $this->_result = $this->getUploader()
            ->upload($data['upload_file']);

        return $this->_result ? true : false;
    }

    /**
     * Get the result of the upload.
     *
     * @return array
     */
    public function getUploadedFiles(): array
    {
        return $this->_result;
    }
}

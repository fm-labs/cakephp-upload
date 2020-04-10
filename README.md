# cakephp-upload

Simple file upload plugin for CakePHP4

## Installation

 $ composer require fm-labs/cakephp-upload

## Usage

```php
// Example Controller

class UploadController extends Controller
{
    public function upload()
    {
        $uploader = new \Upload\Uploader([]);
        // alternatively the uploader config can be loaded from a config file
        // (copy example config from PLUGIN/config/upload.sample.php to app/config/upload.php)
        //\Cake\Core\Configure::load('upload');
        //$upload = new \Upload\Uploader('default');

        $uploader->upload($this->getRequest()->getData('uploadfile'));
        $result = $uploader->getResult();
    }

}
```

```
// Example upload form using CakePHP FormHelper
<?= $this->Form->create(null); ?>
<?= $this->Form->input('uploadfile', ['type' => 'file']; ?>
<?= $this->Form->submit('Upload'); ?>
<?= $this->Form->end(); ?>

// Example upload form using plain html
<form method="POST" action="/url/to/upload-controller/">
<input name="uploadfile" type="file" />
<input type="submit" value="Upload" />
</form>
```
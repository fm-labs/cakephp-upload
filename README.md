# cakephp-upload

Simple file upload plugin for CakePHP4

## Installation

 $ composer require fm-labs/cakephp-upload

## Usage

```php
// Example Controller

class UploadController extends \Cake\Controller\Controller
{
    public function upload()
    {
        // create Uploader instance
        $uploader = new \Upload\Uploader([]);
        // alternatively the uploader config can be loaded from a config file
        // (copy example config from PLUGIN/config/upload.sample.php to app/config/upload.php)
        //\Cake\Core\Configure::load('upload');
        //$uploader = new \Upload\Uploader('default');
    
        // process the uploaded data
        $uploader->upload($this->getRequest()->getData('uploadfile'));
        
        // the result is a list of uploaded files
        // [[
        //  'name' => '/path/to/file',
        //  'size' => (int) 1234,
        //  'mime_type' => 'text/plain'
        //  'error' => 0
        // ], [...]]
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


## Run tests

 $ composer run test
 
## Changelog
 
[1.3]
* Migrations for CakePHP 4.0
 
[1.2]
* Migrations for CakePHP 3.8
 
[1.1]
* Added uploader option 'saveAs'
* Refactored uploader with InstanceConfigTrait
 
[1.0]
* Requires CakePHP 3.3
 
## License
 
 See LICENSE file
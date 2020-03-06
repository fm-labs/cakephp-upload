<?php
namespace Upload\Test\TestCase;

use Cake\Filesystem\Folder;
use Upload\Uploader;

class UploaderTest extends UploadPluginTestCase
{
    /**
     * @var string Path to dummy file directory
     */
    public $filesDir;

    /**
     * @var string Path to test upload dir
     */
    public $uploadDir;

    /**
     * @var Folder Folder instance of the test upload directory
     */
    public $UploadFolder;

    public $upload1;
    public $upload2;
    public $uploadEmpty;
    public $uploadNoExt;
    public $uploadImage;

    public function setUp()
    {
        $this->filesDir = dirname(__DIR__) . DS . 'files' . DS;
        $this->uploadDir = TMP . 'tests' . DS . 'upload' . DS;

        // setup test upload dir
        $this->UploadFolder = new Folder($this->uploadDir, true, 0777);
        if (!is_dir($this->uploadDir)) {
            $this->fail('Failed to create test upload dir in ' . $this->uploadDir);
        }

        // setup dummy upload files
        $this->upload1 = array(
            'name' => 'Upload File 1.txt',
            'type' => 'text/plain',
            'tmp_name' => $this->filesDir . 'upload1.txt',
            'error' => (int)0,
            'size' => @filesize($this->filesDir . 'upload1.txt')
        );

        $this->upload2 = array(
            'name' => 'Upload File 2.txt',
            'type' => 'text/plain',
            'tmp_name' => $this->filesDir . 'upload2.txt',
            'error' => (int)0,
            'size' => @filesize($this->filesDir . 'upload2.txt')
        );

        $this->uploadNoExt = array(
            'name' => 'Upload_Without_Ext',
            'type' => 'text/plain',
            'tmp_name' => $this->filesDir . 'upload_noext',
            'error' => (int)0,
            'size' => @filesize($this->filesDir . 'upload_noext')
        );

        $this->uploadImage = array(
            'name' => 'Upload.jpg',
            'type' => 'image/jpg',
            'tmp_name' => $this->filesDir . 'upload.jpg',
            'error' => (int)0,
            'size' => @filesize($this->filesDir . 'upload.jpg')
        );

        $this->uploadEmpty = array(
            'name' => 'Upload_empty.txt',
            'type' => 'text/plain',
            'tmp_name' => $this->filesDir . 'upload_empty.txt',
            'error' => (int)0,
            'size' => @filesize($this->filesDir . 'upload_empty.txt')
        );
    }

    /**
     * Get Test Uploader instance
     *
     * @param array $data Upload data
     * @param array $config Uploader config
     * @return Uploader
     */
    public function uploader($data = array(), $config = [])
    {
        $config['uploadDir'] = $this->uploadDir;

        return new Uploader($config, $data);
    }

    /**
     * Check if all dummy files exist
     * and the test upload dir is writable
     */
    public function testTestSetup()
    {
        // check dummy files
        foreach ([$this->upload1, $this->upload2, $this->uploadNoExt, $this->uploadImage] as $upload) {
            $this->assertTrue(file_exists($upload['tmp_name']));
        }

        // check upload dir
        $this->assertEquals($this->uploadDir, $this->UploadFolder->pwd());
    }

    public function testStaticValidateMimeType()
    {
        $this->markTestIncomplete('Implement me: ' . __FUNCTION__);
    }

    public function testStaticValidateFileExtension()
    {
        $this->markTestIncomplete('Implement me: ' . __FUNCTION__);
    }

    public function testStaticSplitBasename()
    {
        $this->markTestIncomplete('Implement me: ' . __FUNCTION__);
    }

    public function testDefaultConfig()
    {
        $expected = [
            'uploadDir' => $this->uploadDir,
            'minFileSize' => 1,
            'maxFileSize' => 2 * 1024 * 1024,
            'multiple' => false,
            'mimeTypes' => '*',
            'fileExtensions' => '*',
            'slug' => '_',
            'hashFilename' => false,
            'uniqueFilename' => true,
            'overwrite' => false,
            'saveAs' => null,
            //'pattern' => '',
        ];
        $this->assertEquals($expected, $this->uploader()->config());
    }

    public function testSetMinFileSize()
    {
        $minFileSize = 1000;

        $Uploader = $this->uploader();
        $Uploader->setMinFileSize($minFileSize);

        $this->assertEquals($minFileSize, $Uploader->getConfig('minFileSize'));
    }

    public function testSetMaxFileSize()
    {
        $maxSize = 1000;

        $Uploader = $this->uploader();
        $Uploader->setMaxFileSize($maxSize);

        $this->assertEquals($maxSize, $Uploader->getConfig('maxFileSize'));
    }

    public function testSetMimeTypes()
    {
        $mimeTypes = ['image/*', 'text/html'];

        $Uploader = $this->uploader();
        $Uploader->setMimeTypes($mimeTypes);

        $this->assertEquals($mimeTypes, $Uploader->getConfig('mimeTypes'));
    }

    public function testSetFileExtensions()
    {
        $extensions = ['jpg', 'png'];

        $Uploader = $this->uploader();
        $Uploader->setFileExtensions($extensions);

        $this->assertEquals($extensions, $Uploader->getConfig('fileExtensions'));
    }

    public function testUploadWithFormSizeExceededUploadError()
    {
        $upload = [
            'error' => UPLOAD_ERR_FORM_SIZE,
        ];

        $Uploader = $this->uploader($upload);
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('Maximum form file size exceeded', $result['upload_err']);
    }

    public function testUploadWithNoFileUploadError()
    {
        $upload = [
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $Uploader = $this->uploader($upload);
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('No file uploaded', $result['upload_err']);
    }

    public function testUploadWithPartialUploadError()
    {
        $upload = [
            'error' => UPLOAD_ERR_PARTIAL,
        ];

        $Uploader = $this->uploader($upload);
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('File only partially uploaded', $result['upload_err']);
    }

    public function testUploadWithMinFileSizeError()
    {
        // pre-condition
        $this->assertTrue($this->uploadEmpty['size'] === 0);

        $Uploader = $this->uploader($this->uploadEmpty);
        $Uploader->setMinFileSize(1);
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('Minimum file size error', $result['upload_err']);
    }

    public function testUploadWithMaxFileSizeError()
    {
        $Uploader = $this->uploader($this->upload1);
        $Uploader->setMaxFileSize(1);
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('Maximum file size exceeded', $result['upload_err']);
    }

    public function testUploadWithMimeTypeError()
    {
        $Uploader = $this->uploader($this->upload1);
        $Uploader->setmimeTypes(array('image/*'));
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('Invalid mime type', $result['upload_err']);
    }

    public function testUploadWithFileExtensionError()
    {
        $Uploader = $this->uploader($this->upload1);
        $Uploader->setfileExtensions(array('jpg', 'png'));
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('Invalid file extension', $result['upload_err']);
    }

    public function testZeroConfigUpload()
    {
        $Uploader = $this->uploader($this->upload1);
        $result = $Uploader->upload();

        $this->assertTrue(file_exists($result['path']));
        $this->assertEquals('txt', $result['ext']);
        $this->assertEquals('.txt', $result['dotExt']);
        $this->assertEquals(1, preg_match('/upload_file_1_([0-9a-z]+).txt$/', $result['basename']));
        $this->assertEquals(1, preg_match('/upload_file_1_([0-9a-z]+)$/', $result['filename']));
    }

    public function testUploadOverwrite()
    {
        $Uploader = $this->uploader(
            $this->upload1,
            [
                'overwrite' => true,
                'uniqueFilename' => false,
                'hashFilename' => false,
            ]
        );
        $result = $Uploader->upload();
        $this->assertInternalType('array', $result);

        $result = $Uploader->upload();
        $this->assertInternalType('array', $result);

        //@TODO Compare file names
    }

    public function testUploadMultiple()
    {
        $Uploader = $this->uploader(
            [
                $this->upload1,
                $this->upload2
            ],
            [
                'multiple' => true,
                'overwrite' => false,
                'uniqueFilename' => false,
                'hashFilename' => false,
            ]
        );

        $result = $Uploader->upload();

        $this->assertInternalType('array', $result);
        $this->assertTrue(isset($result[0]));
        $this->assertTrue(isset($result[1]));

        //@TODO check results
    }

    public function testUploadMultipleError()
    {
        $Uploader = $this->uploader(
            [
                $this->upload1,
                $this->upload2
            ],
            [
                'multiple' => true,
                'overwrite' => false,
                'uniqueFilename' => false,
                'hashFilename' => false,
                'minFileSize' => 1 * 1024 * 1024
            ]
        );

        $result = $Uploader->upload();

        $this->assertInternalType('array', $result);
        $this->assertTrue(isset($result[0]));
        $this->assertTrue(isset($result[1]));

        //@TODO check results
    }

    public function testUploadWithPredefinedFilename()
    {
        $Uploader = $this->uploader($this->upload1, [
            'multiple' => false,
            'overwrite' => false,
            'uniqueFilename' => false,
            'hashFilename' => false,
            'saveAs' => 'test.file'
        ]);
        $result = $Uploader->upload();

        $this->assertTrue(file_exists($result['path']));
        $this->assertEquals('file', $result['ext']);
        $this->assertEquals('.file', $result['dotExt']);
        $this->assertEquals('test', $result['filename']);
        $this->assertEquals('test.file', $result['basename']);
    }

    public function tearDown()
    {
        // clean up test upload dir
        $this->UploadFolder->delete();
    }
}

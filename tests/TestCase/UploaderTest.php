<?php
declare(strict_types=1);

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

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->filesDir = dirname(__DIR__) . DS . 'files' . DS;
        $this->uploadDir = TMP . 'tests' . DS . 'upload' . DS;

        // setup test upload dir
        $this->UploadFolder = new Folder($this->uploadDir, true, 0777);
        if (!is_dir($this->uploadDir)) {
            $this->fail('Failed to create test upload dir in ' . $this->uploadDir);
        }

        // setup dummy upload files
        $this->upload1 = [
            'name' => 'Upload File 1.txt',
            'type' => 'text/plain',
            'tmp_name' => $this->filesDir . 'upload1.txt',
            'error' => (int)0,
            'size' => filesize($this->filesDir . 'upload1.txt'),
        ];

        $this->upload2 = [
            'name' => 'Upload File 2.txt',
            'type' => 'text/plain',
            'tmp_name' => $this->filesDir . 'upload2.txt',
            'error' => (int)0,
            'size' => filesize($this->filesDir . 'upload2.txt'),
        ];

        $this->uploadNoExt = [
            'name' => 'Upload_Without_Ext',
            'type' => 'text/plain',
            'tmp_name' => $this->filesDir . 'upload_noext',
            'error' => (int)0,
            'size' => filesize($this->filesDir . 'upload_noext'),
        ];

        $this->uploadImage = [
            'name' => 'Upload.jpg',
            'type' => 'image/jpg',
            'tmp_name' => $this->filesDir . 'upload.jpg',
            'error' => (int)0,
            'size' => filesize($this->filesDir . 'upload.jpg'),
        ];

        $this->uploadEmpty = [
            'name' => 'Upload_empty.txt',
            'type' => 'text/plain',
            'tmp_name' => $this->filesDir . 'upload_empty.txt',
            'error' => (int)0,
            'size' => filesize($this->filesDir . 'upload_empty.txt'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        // clean up test upload dir
        $this->UploadFolder->delete();
    }

    /**
     * Get Test Uploader instance
     *
     * @param array $data Upload data
     * @param array $config Uploader config
     * @return Uploader
     * @throws \Exception
     */
    public function uploader($data = [], $config = [])
    {
        $config['uploadDir'] = $this->uploadDir;

        return new Uploader($config, $data);
    }

    /**
     * Check if all dummy files exist
     * and the test upload dir is writable
     *
     * @return void
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

    /**
     * @return void
     */
    public function testStaticValidateMimeType()
    {
        $this->assertTrue(Uploader::validateMimeType('text/plain', 'text/plain'));
        $this->assertTrue(Uploader::validateMimeType('text/plain', 'text/*'));
        $this->assertTrue(Uploader::validateMimeType('text/plain', '*'));
        $this->assertTrue(Uploader::validateMimeType('text/plain', ['text/plain']));
        $this->assertTrue(Uploader::validateMimeType('text/plain', ['text/*']));

        $this->assertFalse(Uploader::validateMimeType('text/plain', 'image/png'));
        $this->assertFalse(Uploader::validateMimeType('text/plain', 'image/*'));
    }

    /**
     * @return void
     */
    public function testStaticValidateFileExtension()
    {
        $this->assertTrue(Uploader::validateFileExtension('txt', 'txt'));
        $this->assertTrue(Uploader::validateFileExtension('txt', ['txt']));
        $this->assertTrue(Uploader::validateFileExtension('txt', '*'));
    }

    /**
     * @return void
     */
    public function testStaticSplitBasename()
    {
        $basename = 'filename.ext';
        [$filename, $ext, $dotExt] = Uploader::splitBasename($basename);
        $this->assertEquals('filename', $filename);
        $this->assertEquals('ext', $ext);
        $this->assertEquals('.ext', $dotExt);

        $basename = 'filename';
        [$filename, $ext, $dotExt] = Uploader::splitBasename($basename);
        $this->assertEquals('filename', $filename);
        $this->assertEquals('', $ext);
        $this->assertEquals('', $dotExt);

        $basename = '.filename';
        [$filename, $ext, $dotExt] = Uploader::splitBasename($basename);
        $this->assertEquals('', $filename);
        $this->assertEquals('filename', $ext);
        $this->assertEquals('.filename', $dotExt);
    }

    /**
     * @return void
     */
    public function testConstructInvalidConfig()
    {
        $this->expectException(\Exception::class);
        new Uploader('invalid-config');
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testConstructFallbackUploadDir()
    {
        $uploader = new Uploader([]);
        $this->assertEquals(TMP . 'uploads' . DS, $uploader->getConfig('uploadDir'));
    }

    /**
     * @return void
     * @throws \Exception
     */
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
        $this->assertEquals($expected, $this->uploader()->getConfig());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetUploadDir()
    {
        $Uploader = $this->uploader();
        $Uploader->setUploadDir(TMP);
        $this->assertEquals(TMP, $Uploader->getConfig('uploadDir'));

        $this->expectException(\Exception::class);
        $Uploader->setUploadDir('/non-existent-path');
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetMinFileSize()
    {
        $minFileSize = 1000;

        $Uploader = $this->uploader();
        $Uploader->setMinFileSize($minFileSize);

        $this->assertEquals($minFileSize, $Uploader->getConfig('minFileSize'));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetMaxFileSize()
    {
        $maxSize = 1000;

        $Uploader = $this->uploader();
        $Uploader->setMaxFileSize($maxSize);

        $this->assertEquals($maxSize, $Uploader->getConfig('maxFileSize'));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetMimeTypes()
    {
        $mimeTypes = ['image/*', 'text/html'];

        $Uploader = $this->uploader();
        $Uploader->setMimeTypes($mimeTypes);

        $this->assertEquals($mimeTypes, $Uploader->getConfig('mimeTypes'));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetFileExtensions()
    {
        $extensions = ['jpg', 'png'];

        $Uploader = $this->uploader();
        $Uploader->setFileExtensions($extensions);

        $this->assertEquals($extensions, $Uploader->getConfig('fileExtensions'));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetSaveAs()
    {
        $Uploader = $this->uploader();
        $Uploader->setSaveAs('my-file-name.ext');

        $this->assertEquals('my-file-name.ext', $Uploader->getConfig('saveAs'));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testEnableUniqueFilename()
    {
        $Uploader = $this->uploader();
        $Uploader->enableUniqueFilename(true);

        $this->assertEquals(true, $Uploader->getConfig('uniqueFilename'));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testEnableHashFilename()
    {
        $Uploader = $this->uploader();
        $Uploader->enableHashFilename(true);

        $this->assertEquals(true, $Uploader->getConfig('hashFilename'));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testUploadWithFormSizeExceededUploadError()
    {
        $upload = [
            'name' => 'Upload File 1.txt',
            'type' => 'text/plain',
            'tmp_name' => $this->filesDir . 'upload1.txt',
            'error' => UPLOAD_ERR_FORM_SIZE,
            'size' => filesize($this->filesDir . 'upload1.txt'),
        ];

        $Uploader = $this->uploader($upload);
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('Maximum form file size exceeded', $result['upload_err']);
    }

    /**
     * @return void
     * @throws \Exception
     */
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

    /**
     * @return void
     * @throws \Exception
     */
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

    /**
     * @return void
     * @throws \Exception
     */
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

    /**
     * @return void
     * @throws \Exception
     */
    public function testUploadWithMaxFileSizeError()
    {
        $Uploader = $this->uploader($this->upload1);
        $Uploader->setMaxFileSize(1);
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('Maximum file size exceeded', $result['upload_err']);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testUploadWithMimeTypeError()
    {
        $Uploader = $this->uploader($this->upload1);
        $Uploader->setMimeTypes(['image/*']);
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('Invalid mime type', $result['upload_err']);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testUploadWithFileExtensionError()
    {
        $Uploader = $this->uploader($this->upload1);
        $Uploader->setFileExtensions(['jpg', 'png']);
        $result = $Uploader->upload();

        $this->assertTrue(isset($result['upload_err']));
        $this->assertEquals('Invalid file extension', $result['upload_err']);
    }

    /**
     * @return void
     * @throws \Exception
     */
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

    /**
     * @return void
     * @throws \Exception
     */
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
        $this->assertIsArray($result);

        $result = $Uploader->upload();
        $this->assertIsArray($result);

        //@TODO Compare file names
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testUploadMultiple()
    {
        $Uploader = $this->uploader(
            [
                $this->upload1,
                $this->upload2,
            ],
            [
                'multiple' => true,
                'overwrite' => false,
                'uniqueFilename' => false,
                'hashFilename' => false,
            ]
        );

        $result = $Uploader->upload();

        $this->assertIsArray($result);
        $this->assertTrue(isset($result[0]));
        $this->assertTrue(isset($result[1]));

        //@TODO check results
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testUploadMultipleError()
    {
        $Uploader = $this->uploader(
            [
                $this->upload1,
                $this->upload2,
            ],
            [
                'multiple' => true,
                'overwrite' => false,
                'uniqueFilename' => false,
                'hashFilename' => false,
                'minFileSize' => 1 * 1024 * 1024,
            ]
        );

        $result = $Uploader->upload();

        $this->assertIsArray($result);
        $this->assertTrue(isset($result[0]));
        $this->assertTrue(isset($result[1]));

        //@TODO check results
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testUploadWithPredefinedFilename()
    {
        $Uploader = $this->uploader($this->upload1, [
            'multiple' => false,
            'overwrite' => false,
            'uniqueFilename' => false,
            'hashFilename' => false,
            'saveAs' => 'test.file',
        ]);
        $result = $Uploader->upload();

        $this->assertTrue(file_exists($result['path']));
        $this->assertEquals('file', $result['ext']);
        $this->assertEquals('.file', $result['dotExt']);
        $this->assertEquals('test', $result['filename']);
        $this->assertEquals('test.file', $result['basename']);
    }
}

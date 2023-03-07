<?php
return [
    'Settings' => [
        'Upload' => [
            'groups' => [
                'Uploader.Default' => [
                    'label' => __d('upload', 'Default uploader settings'),
                ],
            ],

            'schema' => [
                'Upload.enabled' => [
                    'group' => 'Uploader.Default',
                    'type' => 'boolean',
                    'label' => __d('upload', 'Enable uploads'),
                    'help' => __d('upload', 'Enables file upload system-wide (Only activate, if your application needs it)'),
                    'default' => false,
                ],
                'Upload.minFileSize' => [
                    'group' => 'Uploader.Default',
                    'type' => 'string',
                    'help' => __d('upload', 'Minimum file size in bytes'),
                    'default' => 1,
                ],
                'Upload.maxFileSize' => [
                    'group' => 'Uploader.Default',
                    'type' => 'string',
                    'help' => __d('upload', 'Maximum file size in bytes'),
                    'default' => 2 * 1024 * 1024, // 2MB
                ],
                'Upload.mimeTypes' => [
                    'group' => 'Uploader.Default',
                    'type' => 'string',
                    'help' => __d('upload', 'Maximum file size in bytes'),
                    'default' => 'image/*',
                ],
                'Upload.fileSlug' => [
                    'group' => 'Uploader.Default',
                    'type' => 'string',
                    'help' => __d('upload', 'Replacement character for file names'),
                    'default' => '_',
                ],
                'Upload.fileExtensions' => [
                    'group' => 'Uploader.Default',
                    'type' => 'string',
                    'help' => __d('upload', 'Maximum file size in bytes'),
                    'default' => 'jpg,jpeg,png,gif',
                ],
                'Upload.hashFilename' => [
                    'group' => 'Uploader.Default',
                    'type' => 'boolean',
                    'label' => __d('upload', 'Hash filename'),
                    'help' => __d('upload', 'Sets the filename to the hashed value of the original filename'),
                    'default' => false,
                ],
                'Upload.uniqueFilename' => [
                    'group' => 'Uploader.Default',
                    'type' => 'boolean',
                    'label' => __d('upload', 'Unique filename'),
                    'help' => __d('upload', 'Ensures each file is stored with a unique filename (to prevent accidental overwriting of files)'),
                    'default' => false,
                ],
                'Upload.overwrite' => [
                    'group' => 'Uploader.Default',
                    'type' => 'boolean',
                    'label' => __d('upload', 'Allow overwrite'),
                    'help' => __d('upload', 'Allow overwritting of existent files'),
                    'default' => false,
                ],
            ],
        ],
    ],
];

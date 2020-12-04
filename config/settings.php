<?php
return [
    'Settings' => [
        'Upload' => [
            'groups' => [
                'Uploader.Default' => [
                    'label' => __('Default uploader settings'),
                ],
            ],

            'schema' => [
                'Upload.enabled' => [
                    'group' => 'Uploader.Default',
                    'type' => 'boolean',
                    'label' => __('Enable uploads'),
                    'help' => __('Enables file upload system-wide (Only activate, if your application needs it)'),
                    'default' => false,
                ],
                'Upload.minFileSize' => [
                    'group' => 'Uploader.Default',
                    'type' => 'string',
                    'help' => __('Minimum file size in bytes'),
                    'default' => 1,
                ],
                'Upload.maxFileSize' => [
                    'group' => 'Uploader.Default',
                    'type' => 'string',
                    'help' => __('Maximum file size in bytes'),
                    'default' => 2 * 1024 * 1024, // 2MB
                ],
                'Upload.mimeTypes' => [
                    'group' => 'Uploader.Default',
                    'type' => 'string',
                    'help' => __('Maximum file size in bytes'),
                    'default' => 'image/*',
                ],
                'Upload.fileSlug' => [
                    'group' => 'Uploader.Default',
                    'type' => 'string',
                    'help' => __('Replacement character for file names'),
                    'default' => '_',
                ],
                'Upload.fileExtensions' => [
                    'group' => 'Uploader.Default',
                    'type' => 'string',
                    'help' => __('Maximum file size in bytes'),
                    'default' => 'jpg,jpeg,png,gif',
                ],
                'Upload.hashFilename' => [
                    'group' => 'Uploader.Default',
                    'type' => 'boolean',
                    'label' => __('Hash filename'),
                    'help' => __('Sets the filename to the hashed value of the original filename'),
                    'default' => false,
                ],
                'Upload.uniqueFilename' => [
                    'group' => 'Uploader.Default',
                    'type' => 'boolean',
                    'label' => __('Unique filename'),
                    'help' => __('Ensures each file is stored with a unique filename (to prevent accidental overwriting of files)'),
                    'default' => false,
                ],
                'Upload.overwrite' => [
                    'group' => 'Uploader.Default',
                    'type' => 'boolean',
                    'label' => __('Allow overwrite'),
                    'help' => __('Allow overwritting of existent files'),
                    'default' => false,
                ],
            ],
        ],
    ],
];

<?php
/**
 *
 */
return [
    'Upload' => [
        'default' => [
            // Absolute Path To Upload Directory
            'uploadDir' => TMP . 'uploads' . DS,
            // Minimum File Size In Bytes
            'minFileSize' => 1,
            // Maximum File Size In Bytes
            'maxFileSize' => 2 * 1024 * 1024, // 2MB
            // List Of Allowed MIME-Types
            'mimeTypes' => '*',
            // List Of Allowed File Extensions
            'fileExtensions' => '*',
            // Enable Multiple Files Upload
            'multiple' => false,
            // Filename Replacement Character For Forbidden Values
            'slug' => '_',
            // Enable Filename Hashing
            'hashFilename' => false,
            // Ensure Unique Filename
            'uniqueFilename' => true,
            // Overwrite Existing Files With Same Filename
            'overwrite' => false,
            // Custom Target Filename
            'saveAs' => null,
            // Enable / Disable Events
            #'events' => true, // @todo Implement 'events' config key
            // Filename Regex Pattern.
            #'pattern' => false, // @todo Implement 'pattern' config key
        ],
    ],
];

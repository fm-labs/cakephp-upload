<?php
use Cake\Core\Configure;
/**
 * Automatically load app's upload configuration
 *
 * Copy upload.default.php to your app's config folder,
 * rename to upload.php and adjust contents
 */
//@TODO Fallback to default upload configuration
Configure::load('upload');

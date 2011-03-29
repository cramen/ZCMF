<?php
// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR.'application');

// Define path to site
defined('SITE_PATH')
    || define('SITE_PATH', realpath(dirname(__FILE__)));
    
// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    APPLICATION_PATH . DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'library',
    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';

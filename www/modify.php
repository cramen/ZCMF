<?php
include 'defines.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
	APPLICATION_ENV,
	APPLICATION_PATH . '/configs/application.ini'
);

include_once 'jQuery.php';

$application->bootstrap();

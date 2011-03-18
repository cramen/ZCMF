<?
include '../defines.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
$application->bootstrap();

if (Z_Auth::getInstance()->getUser()->getRole()!='root') die ('Доступ запрещен');

phpinfo();
?>


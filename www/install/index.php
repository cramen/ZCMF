<?php
include '../defines.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
        APPLICATION_PATH . '/configs/application.ini'
);

include_once 'jQuery.php';

$application->bootstrap();

$view = new Zend_View();
$view->addScriptPath('./view/');

$step = isset($_GET['step'])?(string)$_GET['step']:'0';
$steps = array(
    'db','config','ok'
);

$file = 'inc/'.$steps[$step].'.php';
$class = 'install_'.$steps[$step];


if (file_exists($file)) require_once $file;
if (class_exists($class)) $stepClass = new $class($view);

if ($stepClass->run())
{
    $step++;
    header('Location: /install/?'.http_build_query(array('step'=>$step)));
}

echo $view->render('view.html');


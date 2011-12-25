<?php
include 'defines.php';


$microtime = microtime(true);

// Create application, bootstrap, and run

$application = new Zend_Application(
	APPLICATION_ENV,
	APPLICATION_PATH . '/configs/application.ini'
);

include_once 'jQuery.php';

$application->bootstrap();
$application->run();

//$pf = $application->getBootstrap()->getResource('db')->getProfiler();
//$pfs = $pf->getQueryProfiles();
//if ($pfs)
//foreach ($pfs as $pfel)
//{
//	echo $pfel->getElapsedSecs()."\t".$pfel->getQuery().'<br />------------------<br />';
//}
//echo $pf->getTotalElapsedSecs();

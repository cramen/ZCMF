<?php

include '../defines.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV, 
    APPLICATION_PATH . '/configs/application.ini'
);
$application->bootstrap();

$role = Z_Auth::getInstance()->getUser()->getRole();
$acl = Z_Acl::getInstance();
try
{
	$allow = $acl->isAllowed($role,'z_statpage');
}
catch (Exception $e)
{
	$allow = false;
}

if (!$allow) die('Доступ запрещен');

$model = new Z_Model_Statpage();
$items = $model->fetchAll(array(),'title asc');

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru"> 
	<head> 
		<meta http-equiv="content-type" content="text/html; charset=windows-1251"> 
		<style type="text/css"> 
body{margin:5px;padding:0;}
a,a:link,a:visited {color:#000000;text-decoration:none;font-family:arial;font-size:12px;}
a:hover{text-decoration:underline;}
		</style> 
		<script type="text/javascript"> 
function m_set_url(url) {
	window.parent.document.getElementById('href').value = url;
	window.parent.mcTabs.displayTab('general_tab','general_panel');
	return false;
}
		</script> 
	</head> 
	<body> 
		<ul>
<?php foreach ($items as $el):?>
			<li><a href="#" onclick="return m_set_url('/page/<?=$el->sid?>')"><?=$el->title?></a>
<?php endforeach;?>
		</ul> 
	</body> 
</html>
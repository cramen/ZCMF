<?
include '../defines.php';

$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
//$application->bootstrap();


//получение параметров
$uri = $_SERVER['REQUEST_URI'];
$parsedUri = parse_url($uri);
$query = $parsedUri['query'];
parse_str($query,$params);
if (!isset($params['file'])) exit();
$fileSite = $params['file'];
unset($params['file']);
ksort($params);

$filePathName = SITE_PATH.$fileSite;
if (!file_exists($filePathName)) exit;

$filePreviewObject = new Z_File_Image_Thumbnail($filePathName);

$filePreviewPathName = $filePreviewObject->createThumbnail($params);

$extArray = explode('.',$filePreviewPathName);
$ext = $extArray[count($extArray)-1];


if (file_exists($filePreviewPathName)) {
    header('Content-Type: image/'.$ext);
    header('Content-Length: ' . filesize($filePreviewPathName));
    ob_clean();
    flush();
//    echo $filePreviewPathName;
    readfile($filePreviewPathName);
    exit;
}

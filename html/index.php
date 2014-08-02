<?php

define('ROOT', dirname(__FILE__));
$libPath = ROOT . DIRECTORY_SEPARATOR . 'lib';
$storagePath = ROOT . DIRECTORY_SEPARATOR . 'storage';
$uploadPath = ROOT . DIRECTORY_SEPARATOR . 'upload';
$getID3 = $libPath . DIRECTORY_SEPARATOR . 'getid3';
set_include_path(get_include_path() . PATH_SEPARATOR . $libPath . PATH_SEPARATOR . $getID3);

function _my_autoload($className)
{
    $filename = str_replace(array('_', '\\'), DIRECTORY_SEPARATOR, $className) . '.php';
    if(($res = include $filename) !== false)
    {
        return true;
    }
    return false;
}
spl_autoload_register('_my_autoload');

//$storage = new LocalStorage($storagePath);
$storage = new S3Storage();

$uploadHandler = new UploadHandler(array(
    'upload_dir' => $uploadPath,
    'storage' => $storage
));
$app = new MusicApp($storage, $uploadHandler);
$app->handleRequest();
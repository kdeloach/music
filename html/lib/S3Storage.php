<?php

require_once '/var/www/music.kevinx.net/local_settings.php';

class S3Storage implements IStorage
{
    static $awsAccessKey = AWS_ACCESS_KEY;
    static $awsSecretKey = AWS_SECRET_KEY;
    static $awsBucketName = AWS_BUCKET_NAME;
    
    function getFile($hash, $headers = array())
    {
        $hash = str_replace('.mp3', '', $hash);
        $s3 = self::getS3Instance();
        $res = $s3->getObject(self::$awsBucketName, $hash, false, $headers);
        if($res === false)
        {
            return false;
        }
        return $res->body;
    }
    
    function getInfo($hash)
    {
        $hash = str_replace('.mp3', '', $hash);
        $s3 = self::getS3Instance();
        $res = $s3->getObjectInfo(self::$awsBucketName, $hash, true);
        return $res;
    }
    
    function saveFile($filePath)
    {
        $parts = Song::getFileParts($filePath);
        $hash = $parts['hash'];
        if($parts === false || $hash === false)
        {
            return false;
        }
        $s3 = self::getS3Instance();
        return $s3->putObject(S3::inputFile($filePath), self::$awsBucketName, $hash);
    }
    
    function saveInfo($hash, $info = array())
    {
        $s3 = self::getS3Instance();
        return $s3->copyObject(self::$awsBucketName, $hash, self::$awsBucketName, $hash, S3::ACL_PRIVATE, $info);
    }
    
    function deleteFile($hash)
    {
        $s3 = self::getS3Instance();
        return $s3->deleteObject(self::$awsBucketName, $hash);
    }
    
    static function getS3Instance()
    {
        $s3 = new S3(self::$awsAccessKey, self::$awsSecretKey);
        return $s3;
    }
}

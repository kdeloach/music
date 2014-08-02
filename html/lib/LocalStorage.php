<?php

class LocalStorage implements IStorage
{
    var $storageDir;
    
    function __construct($storageDir)
    {
        $this->storageDir = $storageDir;
        if(!file_exists($storageDir))
        {
            mkdir($storageDir);
        }
    }
    
    function saveFile($filePath)
    {
        $parts = Song::getFileParts($filePath);
        $hash = $parts['hash'];
        if($parts === false || $hash === false)
        {
            return false;
        }
        $newFilePath = $this->storageDir . DIRECTORY_SEPARATOR . $hash;
        if(file_exists($newFilePath))
        {
            unlink($newFilePath);
        }
        if(!copy($filePath, $newFilePath))
        {
            return false;
        }
        return true;
    }
    
    function getFile($hash, $headers)
    {
        $hash = str_replace('.mp3', '', $hash);
        $filePath = $this->storageDir . DIRECTORY_SEPARATOR . $hash;
        if(!file_exists($filePath))
        {
            return false;
        }
        return file_get_contents($filePath);
    }
    
    function getInfo($hash)
    {
        return filesize($this->getFile($hash));
    }
}

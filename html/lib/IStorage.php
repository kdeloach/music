<?php

interface IStorage
{
    // @filepath string Path to uploaded file.
    // @return bool TRUE on success FALSE on failure.
    function saveFile($filePath);
    
    // Retrieve song contents by file hash.
    // @return string Contents of song file.
    function getFile($hash, $headers);
    
    // Retreive file info (size) to enable seeking
    function getInfo($hash);
}

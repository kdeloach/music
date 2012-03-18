<?php

class LocalImport
{
    function __construct()
    
        // TODO: move to tests
        assert($this->endsWith('test.mp3', 'mp3') === true);
        assert($this->endsWith('test.mp3', '.mp') === false);
    }
    
    // Return list of music filenames in folder; Does not scan subdirectories
    function findMusic($dir)
    {
        if($dh = opendir($dir))
        {
            $result = array();
            while(($file = readdir($dh)) !== false)
            {
                if($file == '.' || $file == '..')
                {
                    continue;
                }
                if(is_dir($dir . DIRECTORY_SEPARATOR . $file))
                {
                    continue;
                }
                if($this->endsWith($file, '.mp3'))
                {
                    $result[] = $dir . DIRECTORY_SEPARATOR . $file;
                }
            }
            closedir($dh);
            return $result;
        }
        return array();
    }

    // Returns list of subdirectory folder paths (inclusive)
    function listFolders($dir)
    {
        if($dh = opendir($dir))
        {
            $result = array();
            while(($file = readdir($dh)) !== false)
            {
                if($file == '.' || $file == '..')
                {
                    continue;
                }
                if(is_dir($dir . DIRECTORY_SEPARATOR . $file))
                {
                    $result[] = $dir . DIRECTORY_SEPARATOR . $file;
                    $result = array_merge($result, $this->listFolders($dir . DIRECTORY_SEPARATOR .$file));
                }
            }
            closedir($dh);
            return $result;
        }
        return array();
    }
    
    // Returns true if all artist names match on list of songs
    function artistsMatch($songs)
    {
        $val = null;
        foreach($songs as $song)
        {
            $val = $val == null ? $song->artist : $val;
            if($song->artist != $val)
            {
                return false;
            }
        }
        return true;
    }
    
    function endsWith($haystack, $needle)
    {
        if(strlen($needle) > strlen($haystack))
        {
            return false;
        }
        if(($pos = strpos($haystack, $needle)) !== false)
        {
            return $pos + strlen($needle) == strlen($haystack);
        }
        return false;
    }
}

<?php

class Song
{
    var $id = 0;
    var $folder = '';
    var $artist = '';
    var $album = '';
    var $track = '';
    var $title = '';
    var $dateAdded;
    var $dateUpdated;
    var $hash = '';
    
    function save()
    {
        $db = DatabaseProvider::getInstance();
        $sql = <<<sql
            insert into music (folder, album, artist, track, title, dateAdded, dateUpdated, hash) 
            values (:folder, :album, :artist, :track, :title, UTC_TIMESTAMP(), null, :hash)
            on duplicate key update folder=:folder, album=:album, artist=:artist, track=:track, title=:title, dateUpdated=UTC_TIMESTAMP()
sql;
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':folder', $this->folder);
        $stmt->bindParam(':album', $this->album);
        $stmt->bindParam(':artist', $this->artist);
        $stmt->bindParam(':track', $this->track);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':hash', $this->hash);
        $stmt->execute();
    }
    
    //
    // Reads file ID3 tags and returns the id3 tag info.
    //
    // @filename string Path to song file
    // @return array(artist, album, track, title) or false on error
    //
    static function getFileParts($filename)
    {
        if(strpos($filename, '?') !== false)
        {
            return false;
        }
        $getID3 = new getid3();
        $detail = $getID3->analyze($filename);
        if($detail != null && $detail !== false && isset($detail['error']))
        {
            return false;
        }
        $tags = $detail['tags'];
        foreach($tags as $version => $info);
        $tags = $tags[$version];
        $artist = isset($tags['artist']) ? $tags['artist'][0] : 'Unknown';
        $album = isset($tags['album']) ? $tags['album'][0] : 'Unknown';
        $track = isset($tags['track_number']) ? $tags['track_number'][0] : 'Unknown';
        $track = isset($tags['track']) ? $tags['track'][0] : $track;
        $title = isset($tags['title']) ? $tags['title'][0] : 'Unknown';
        $parts = array(
            'folder' => $artist, 
            'artist' => $artist, 
            'album'  => $album, 
            'track'  => $track, 
            'title'  => $title,
            'hash'   => md5($artist . $album . $track . $title)
        );
        // don't want to alter the hash in case i want to rebuild the DB from my local music without uploading
        // i know string length is a really dumb way of finding out which field to use for the actual artist, oh well
        // the alternative would be to have some sort web lookup to see the official artist of the album
        $parts['artist'] = isset($tags['composer']) && strlen($tags['composer'][0]) < $artist ? $tags['composer'][0] : $artist;
        $parts['artist'] = isset($tags['band']) && strlen($tags['band'][0]) < $parts['artist'] ? $tags['band'][0] : $parts['artist'];
        // some albums just group as Soundtrack since grouping by artist would be incomprehensible
        if(isset($tags['genre'])) 
        {
            $genre = strtolower(trim($tags['genre'][0]));
            $groups = array('soundtrack', 'game music');
            if(in_array($genre, $groups))
            {
                $parts['artist'] = 'Soundtrack';
            }
        }
        return $parts;
    }
    
    // Source: http://stackoverflow.com/questions/3380114/strip-bad-windows-filename-characters
    static function stripInvalidCharacters($filename)
    {
        $bad = array_merge(array_map('chr', range(0,31)), array("<", ">", ":", '"', "/", "\\", "|", "?", "*"));
        $result = str_replace($bad, '_', $filename);
        return $result;
    }
}

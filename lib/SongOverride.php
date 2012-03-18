<?php

class SongOverride
{
    var $id;
    var $folder;
    var $artist;
    var $album;
    var $track;
    var $title;
    var $dateAdded;
    var $hash;
    
    function save()
    {
        $db = DatabaseProvider::getInstance();
        $sql = <<<sql
            insert into music_override (folder, album, artist, track, title, dateAdded, dateUpdated, hash) 
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
}

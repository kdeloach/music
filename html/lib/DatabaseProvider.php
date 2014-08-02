<?php

require_once '/var/www/music.kevinx.net/local_settings.php';

class DatabaseProvider
{
    static function getInstance()
    {
        $pdo = new PDO('mysql:dbname=kevinxn_music;host=localhost', DB_USER, DB_PASSWORD);
        return $pdo;
    }
}

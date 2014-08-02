<?php

class DatabaseProvider
{
    static function getInstance()
    {
        $pdo = new PDO('mysql:dbname=kevinxn_music;host=localhost', 'root', 'root');
        return $pdo;
    }
}

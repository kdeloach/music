<?php

class MusicApp
{
    var $storage;
    var $uploadHandler;

    function __construct($storage, $uploadHandler)
    {
        $this->storage = $storage;
        $this->uploadHandler = $uploadHandler;
    }

    function handleRequest()
    {
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $args = array();
        if(($pos = strpos($page, '/')) !== false)
        {
            $args = explode('/', $page);
            $page = array_shift($args);
        }
        switch($page)
        {
            case 'up':
                $this->handleUpload($args);
                break;
            case 'search':
                $this->handleSearch($args);
                break;
            case 'download':
                $this->handleDownload($args);
                break;
            case 'art':
                $this->handleArt($args);
                break;
            case 'update':
                $this->handleUpdate($args);
                break;
            case 'inventory':
                $this->handleInventory($args);
                break;
            case 'import':
                $this->handleImport($args);
                break;
            case 'info':
                $this->handleInfo($args);
                break;
            case 'test':
                $this->handleTest($args);
                break;
            default:
                $this->handleDefault();
                break;
        }
    }
    
    function handleDefault()
    {
        $finder = new SongFinder();
        $artists = $finder->getArtistsAlbums();
        include 'templates/main_template.php';
    }
    
    function handleUpload($args)
    {
        switch ($_SERVER['REQUEST_METHOD'])
        {
            case 'POST':
                $this->uploadHandler->post();
                break;
            default:
                header('HTTP/1.0 405 Method Not Allowed');
        }
    }
    
    function handleSearch($args)
    {
        if(!isset($_GET['q']))
        {
            return;
        }
        $q = $_GET['q'];
        $finder = new SongFinder();
        $songs = array();
        if(!empty($q))
        {
            if($q == 'all' || $q == 'everything')
            {
                $songs = $finder->all();
            }
            else
            {
                $songs = $finder->search($q);
            }
        }
        header('Pragma: no-cache');
        header('Cache-Control: private, no-cache');
        header('X-Content-Type-Options: nosniff');
        header('Content-type: application/json');
        echo json_encode($songs);
    }
    
    function handleDownload($args)
    {
        if(count($args) != 1)
        {
            return;
        }
        list($hash) = $args;
        $info = $this->storage->getInfo($hash);
        
        $fullLength = $info['size'];
        $start = 0;
        $end = $fullLength - 1;
        $S3Headers = array();
        
        $header = array();
        $headers[] = 'Accept-Ranges: bytes';
        $headers[] = 'Content-type: audio/mpeg';
        $headers[] = 'Cache-Control: must-revalidate, post-check=0, pre-check=0';
        $headers[] = 'Pragma: public';
        
        if(isset($_SERVER['HTTP_RANGE']))
        {
            if(!preg_match('/^bytes=\d*-\d*(,\d*-\d*)*$/', $_SERVER['HTTP_RANGE'], $matches))
            {
                $headers[] = 'HTTP/1.1 416 Requested Range Not Satisfiable';
                $headers[] ='Content-Range: bytes */0';
                $this->_outputHeaders($headers);
                exit;
            }
            list(,$range) = explode('=', $_SERVER['HTTP_RANGE']);
            list($start, $end) = explode('-', $range);
            
            $start = empty($start) ? 0 : $start;
            $end = empty($end) ? $fullLength - 1 : $end;
            $end = min($end, $fullLength - 1);
            
            if ($start > $end)
            {
                $headers[] = 'HTTP/1.1 416 Requested Range Not Satisfiable';
                $headers[] = 'Content-Range: bytes */0';
                $this->_outputHeaders($headers);
                exit;
            }
            $length = $end - $start + 1;
            $S3Headers['Range'] = "bytes=$start-$end";
            $headers[] = 'HTTP/1.1 206 Partial Content';
            $headers[] = "Content-Length: $length";
            $headers[] = "Content-Range: bytes $start-$end/$length";
        }
        else
        {
            $headers[] = "Content-Length: $fullLength";
            $headers[] = "Content-Range: bytes $start-$end/$fullLength";
            //$headers[] = 'Content-Disposition: attachment; filename="' . $hash. '"';
        }
        $output = $this->storage->getFile($hash, $S3Headers);
        if($output === false)
        {
            die('Error loading file');
        }
        $this->_outputHeaders($headers);
        echo $output;
    }
    
    function _outputHeaders($headers)
    {
        foreach($headers as $header)
        {
            header($header);
        }
    }
    
    function handleArt($args)
    {
        if(count($args) != 1)
        {
            return;
        }
        list($hash) = $args;
        $finder = new SongFinder();
        $song = $finder->getByHash($hash);
        if($song === false)
        {
            return;
        }
        $url = 'https://ajax.googleapis.com/ajax/services/search/images?' . http_build_query(array(
            'v'   => '1.0',
            'key' => 'AIzaSyALp6qvMuW2uGx1qg_kvmE-UtFPyZvoVqA',
            'q'   => $song->title . ' ' . $song->album . ' ' . $song->artist
        ));
        $json = file_get_contents($url);
        header('Content-type: application/json');
        echo $json;
    }
    
    function handleUpdate($args)
    {
        $q = $_POST['q'];
        $folder = $_POST['folder'];
        $artist = $_POST['artist'];
        $album = $_POST['album'];
        $track = $_POST['track'];
        $title = $_POST['title'];
        
        $finder = new SongFinder();
        $songs = $finder->search($q);
        foreach($songs as $song)
        {
            $override = new SongOverride();
            $override->folder = empty($folder) ? $song->folder : $folder;
            $override->artist = empty($artist) ? $song->artist : $artist;
            $override->album = empty($album) ? $song->album : $album;
            $override->track = empty($track) ? $song->track : $track;
            $override->title = empty($title) ? $song->title : $title;
            $override->hash = $song->hash;
            $override->save();
        }
    }
    
    function handleInventory($args)
    {
        $finder = new SongFinder();
        $artists = $finder->getArtistsAlbums();
        echo json_encode($artists);
    }
    
    function handleImport($args)
    {
        set_time_limit(0);
        error_reporting(E_ALL);
        if(file_exists('import.log'))
        {
            unlink('import.log');
        }
        $srcDirs = array(
            'C:/Users/Kevin/Music'
        );
        $import = new LocalImport();
        $dirs = array();
        foreach($srcDirs as $srcDir)
        {
            $dirs = array_merge($dirs, $import->listFolders($srcDir));
        }
        foreach($dirs as $dir)
        {
            $filePaths = $import->findMusic($dir);
            $songs = array();
            foreach($filePaths as $filePath)
            {
                $info = Song::getFileParts($filePath);
                if($info !== false)
                {
                    echo "$filePath\n"; 
                    if($this->storage->saveFile($filePath))
                    {
                        $song = new Song();
                        $song->folder = $info['artist'];
                        $song->artist = $info['artist'];
                        $song->album = $info['album'];
                        $song->track = $info['track'];
                        $song->title = $info['title'];
                        $song->hash = $info['hash'];
                        $songs[] = $song;                        
                    }
                    else
                    {
                        echo "storage failure\n";
                    }
                }
                else
                {
                    echo "(skipping) couldn't get ID3 tags\n";
                }
                file_put_contents('import.log', ob_get_contents(), FILE_APPEND);
                ob_flush();
            }
            foreach($songs as $song)
            {
                $song->save();
            }
        }
        exit;
    }
    
    function handleInfo($args)
    {
        $s3 = S3Storage::getS3Instance();
        $result = $s3->getBucket(S3Storage::$awsBucketName);
        $total = 0;
        foreach($result as $row)
        {
            $total += $row['size'];
        }
        $total = $total / 1024 / 1024;
        echo "Total size: $total MB\n";
    }
    
    function handleTest()
    {
    }
}

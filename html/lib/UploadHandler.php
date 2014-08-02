<?php
/*
 * jQuery File Upload Plugin PHP Example 5.2.4
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://creativecommons.org/licenses/MIT/
 */

error_reporting(E_ALL ^ E_WARNING);

class UploadHandler
{
    private $options;
    
    function __construct($options=null)
    {
        $this->options = array(
            'script_url' => $_SERVER['PHP_SELF'],
            'upload_dir' => dirname(__FILE__).'/upload/',
            'upload_url' => dirname($_SERVER['PHP_SELF']).'/upload/',
            'param_name' => 'files',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            'min_file_size' => 1,
            'accept_file_types' => '/.+\.mp3$/i',
            'max_number_of_files' => null,
            'discard_aborted_uploads' => true,
            'storage' => null
        );
        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
    }
    
    private function has_error($uploaded_file, $file, $error)
    {
        if ($error) {
            return $error;
        }
        if (!preg_match($this->options['accept_file_types'], $file->name)) {
            return 'acceptFileTypes';
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = filesize($uploaded_file);
        } else {
            $file_size = $_SERVER['CONTENT_LENGTH'];
        }
        if ($this->options['max_file_size'] && ($file_size > $this->options['max_file_size'] || $file->size > $this->options['max_file_size'])) {
            return 'maxFileSize';
        }
        if ($this->options['min_file_size'] && $file_size < $this->options['min_file_size']) {
            return 'minFileSize';
        }
        return $error;
    }
    
    private function handle_file_upload($uploaded_file, $name, $size, $type, $error)
    {
        $file = new stdClass();
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $file->name = trim(basename(stripslashes($name)), ".\x00..\x20");
        $file->size = intval($size);
        $file->type = $type;
        $error = $this->has_error($uploaded_file, $file, $error);
        if (!$error && $file->name) {
            $file_path = $this->options['upload_dir'] . DIRECTORY_SEPARATOR . $file->name;
            $append_file = is_file($file_path) && $file->size > filesize($file_path);
            clearstatcache();
            if ($uploaded_file && is_uploaded_file($uploaded_file))
            {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents($file_path, fopen($uploaded_file, 'r'), FILE_APPEND);
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } 
            else 
            {
                // Non-multipart uploads (PUT method support)
                file_put_contents($file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0);
            }
            $file_size = filesize($file_path);
            if ($file_size === $file->size)
            {
                if(isset($this->options['storage']))
                {
                    $info = Song::getFileParts($file_path);
                    if($this->options['storage']->saveFile($file_path))
                    {
                        $song = new Song();
                        $song->folder = $info['folder'];
                        $song->artist = $info['artist'];
                        $song->album = $info['album'];
                        $song->track = $info['track'];
                        $song->title = $info['title'];
                        $song->hash = $info['hash'];
                        $song->save();
                        $file->url = '#' . $song->hash;
                        if(file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                    else
                    {
                        unlink($file_path);
                        $file->error = 'storage failure';
                    }
                }
            }
            else if ($this->options['discard_aborted_uploads'])
            {
                unlink($file_path);
                die($file_path);
                $file->error = 'abort, ' . $file_path . ', ' . $file_size . ', ' . $file->size;
            }
            $file->size = $file_size;
        } else {
            $file->error = $error;
        }
        return $file;
    }

    public function post()
    {
        header('Pragma: no-cache');
        header('Cache-Control: private, no-cache');
        header('Content-Disposition: inline; filename="files.json"');
        header('X-Content-Type-Options: nosniff');
        $upload = isset($_FILES[$this->options['param_name']]) ?
            $_FILES[$this->options['param_name']] : array(
                'tmp_name' => null,
                'name' => null,
                'size' => null,
                'type' => null,
                'error' => null
            );
        $info = array();
        if (is_array($upload['tmp_name'])) {
            foreach ($upload['tmp_name'] as $index => $value) {
                $info[] = $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    isset($_SERVER['HTTP_X_FILE_NAME']) ?
                        $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'][$index],
                    isset($_SERVER['HTTP_X_FILE_SIZE']) ?
                        $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'][$index],
                    isset($_SERVER['HTTP_X_FILE_TYPE']) ?
                        $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'][$index],
                    $upload['error'][$index]
                );
            }
        } else {
            $info[] = $this->handle_file_upload(
                $upload['tmp_name'],
                isset($_SERVER['HTTP_X_FILE_NAME']) ?
                    $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'],
                isset($_SERVER['HTTP_X_FILE_SIZE']) ?
                    $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'],
                isset($_SERVER['HTTP_X_FILE_TYPE']) ?
                    $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'],
                $upload['error']
            );
        }
        header('Vary: Accept');
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        {
            header('Content-type: application/json');
        }
        else
        {
            header('Content-type: text/plain');
        }
        echo json_encode($info);
    }
}

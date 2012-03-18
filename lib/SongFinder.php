<?php

class SongFinder
{
    // This class is now a weird combination of SongeFinder and mysql query where clause tokenizer thing...
    // Need to split these things later...
    var $ops = array('&&', '||', '(', ')', '!');
    
    function getByHash($hash)
    {
        $db = DatabaseProvider::getInstance();
        $sql = "select id, folder, artist, album, track, title, hash, unix_timestamp(dateAdded) dateAdded, unix_timestamp(dateUpdated) dateUpdated from music_combine where hash=? limit 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(array($hash));
        $result = $stmt->fetchAll(PDO::FETCH_CLASS, 'Song');
        if(count($result) > 0)
        {
            return $result[0];
        }
        return false;
    }
    
    function search($q)
    {
        $tokens = $this->_tokenize($q);
        $isValid = $this->_validTokens($tokens);
        if(!$isValid)
        {
            return false;
        }
        list($sql, $params) = $this->createSearchQuery($q);
        $db = DatabaseProvider::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_CLASS, 'Song');
        return $result;
    }
    
    function createSearchQuery($q)
    {        
        $sql = new SqlBuilder();
        $sql->select('id, folder, artist, album, track, title, hash')
            ->select('unix_timestamp(dateAdded) dateAdded')
            ->select('unix_timestamp(dateUpdated) dateUpdated')
            ->from('music_combine')
            ->orderBy('folder, artist, album, length(track), track');
        list($where, $params) = $this->createWhereClause($q);
        $sql->where($where);
        return array($sql->toString(), $params);
    }
    
    // @param string $clause each term in $q will be replaced with this
    function createWhereClause($q, $clause = null)
    {
        if(!isset($clause))
        {
            $clause = '(folder regexp ? or artist regexp ? or album regexp ? or title regexp ? or track regexp ?)';
        }
        $offset = 0;
        $numInClause = 0;
        while(($pos = strpos($clause, '?', $offset)) !== false)
        {
            $numInClause++;
            $offset = $pos + 1;
        }
        $tokens = $this->_tokenize($q);
        $clean = array();
        $params = array();
        foreach($tokens as $token)
        {
            if($this->_isOp($token))
            {
                switch($token)
                {
                    case '&&':
                        $clean[] = 'and';
                        break;
                    case '||':
                        $clean[] = 'or';
                        break;
                    case '!':
                        $clean[] = 'not';
                        break;
                    default:
                        $clean[] = $token;
                }
            }
            else
            {
                $clean[] = $clause;
                for($i = 0; $i < $numInClause; $i++)
                {
                    $params[] = $token;
                }
            }
        }
        $result = implode(' ', $clean);
        return array($result, $params);
    }
    
    // Helper method to return next token position or false
    function _nextToken($str, $offset)
    {
        if($offset > strlen($str))
        {
            return false;
        }
        $first = strlen($str);
        $foundOp = false;
        foreach($this->ops as $op)
        {
            if(($pos = strpos($str, $op, $offset)) !== false && $pos <= $first)
            {
                $first = $pos;
                $foundOp = $op;
            }
        }
        if($foundOp !== false)
        {
            return array($foundOp, $first);
        }
        return false;
    }
    
    function _tokenize($str)
    {
        $offset = 0;
        $tokens = array();
        while($offset < strlen($str))
        {
            $res = $this->_nextToken($str, $offset);
            if($res === false)
            {
                $op = '';
                $pos = strlen($str);
            }
            else
            {
                list($op, $pos) = $res;
            }
            $tokens[] = substr($str, $offset, $pos - $offset);
            $offset = $pos;
            $tokens[] = substr($str, $offset, strlen($op));
            $offset += strlen($op);
            
        }
        $result = array();
        foreach($tokens as $token)
        {
            if($token === false || strlen(trim($token)) == 0)
            {
                continue;
            }
            $result[] = trim($token);
        }
        return $result;
    }
    
    function _validTokens($tokens)
    {
        $openedParens = 0;
        for($i = 0; $i < count($tokens); $i++)
        {
            $token = $tokens[$i];
            $isFirst = $i == 0;
            $isLast = $i == count($tokens) - 1;
            if($this->_isOp($token))
            {
                switch($token)
                {
                    case '&&': case '||':
                        if($isFirst || $isLast)
                        {
                            return false;
                        }
                        if($tokens[$i-1] == '&&' || $tokens[$i-1] == '||')
                        {
                            return false;
                        }
                        if($tokens[$i+1] == '&&' || $tokens[$i+1] == '||')
                        {
                            return false;
                        }
                        break;
                    case '!':
                        if($isLast)
                        {
                            return false;
                        }
                        if($tokens[$i-1] != '&&' && $tokens[$i-1] != '||' && $tokens[$i-1] != '(')
                        {
                            return false;
                        }
                        break;
                    case '(':
                        $openedParens++;
                        if($isLast)
                        {
                            return false;
                        }
                        if(!$isFirst && !$this->_isOp($tokens[$i-1]))
                        {
                            return false;
                        }
                        break;
                    case ')':
                        $openedParens--;
                        if($isFirst)
                        {
                            return false;
                        }
                        if(!$isLast && !$this->_isOp($tokens[$i+1]))
                        {
                            return false;
                        }
                        break;
                    default:
                        break;
                }
                if($openedParens < 0)
                {
                    return false;
                }
            }
        }
        return true;
    }
    
    function _isOp($str)
    {
        return in_array($str, $this->ops);
    }
    
    function all()
    {
        $db = DatabaseProvider::getInstance();
        $sql = "select id, folder, artist, album, track, title, hash, unix_timestamp(dateAdded) dateAdded, unix_timestamp(dateUpdated) dateUpdated from music_combine order by artist, album, length(track), track";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_CLASS, 'Song');
        return $result;
    }
    
    // Generate a flat-heirarchy of folders/artists/albums
    // This structure is to make it very easy to populate a YUI grid
    function getArtistsAlbums()
    {
        $db = DatabaseProvider::getInstance();
        $sql = "select folder, artist, album from music_combine group by folder, artist, album order by folder, artist, album";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_NUM);
        $nodes = array();
        foreach($result as $row)
        {
            list($folder, $artist, $album) = $row;
            if(!isset($nodes[$folder]))
            {
                $nodes[$folder] = array();
                $nodes[$folder]['artists'] = array();
                $nodes[$folder]['albums'] = array();
            }
            $nodes[$folder]['artists'][] = $artist;
            $nodes[$folder]['albums'][] = $album;
        }
        $clean = array('folders' => array());
        foreach($nodes as $folder => $children)
        {
            $f = array(
                'name'    => $folder,
                'artists' => array_values(array_unique($children['artists'])),
                'albums'  => array_values(array_unique($children['albums']))
            );
            $clean['folders'][] = $f;
        }
        return $clean;
    }
}

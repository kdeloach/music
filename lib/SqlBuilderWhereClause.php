<?php

class SqlBuilderWhereClause
{
    var $where = array();
    
    function __construct()
    {
    }
    
    function where($where, $op='or')
    {
        $this->where[] = count($this->where) == 0 ? $where : "$op $where";
        return $this;
    }
    
    function toString()
    {
        $result = '';
        if(count($this->where) > 0)
        {
            if(count($this->where) > 1)
            {
                $result .= '(' . implode(' ', $this->where) . ')';
            }
            else
            {
                $result .= implode(' ', $this->where);
            }
        }
        return $result;
    }
    
    function __toString()
    {
        return $this->toString();
    }
}

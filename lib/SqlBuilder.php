<?php

// Extremely basic utility for generating select statements
class SqlBuilder
{
    var $select = array();
    var $from = array();
    var $where = array();
    var $order = array();
    
    function __construct()
    {
    }
    
    function select($select)
    {
        $this->select[] = $select;
        return $this;
    }
    
    function from($from)
    {
        $this->from[] = $from;
        return $this;
    }
    
    function where($where, $op='or')
    {
        $this->where[] = count($this->where) == 0 ? $where : "$op $where";
        return $this;
    }
    
    function orderBy($order)
    {
        $this->order[] = $order;
        return $this;
    }
    
    function toString()
    {
        $result = '';
        if(count($this->select) > 0)
        {
            $result .= 'select ' . implode(', ', $this->select);
        }
        if(count($this->from) > 0)
        {
            $result .= ' from ' . implode(', ', $this->from);
        }
        if(count($this->where) > 0)
        {
            $result .= ' where ' . implode(' ', $this->where);
        }
        if(count($this->order) > 0)
        {
            $result .= ' order by ' . implode(', ', $this->order);
        }
        return $result;
    }
    
    function __toString()
    {
        return $this->toString();
    }
}

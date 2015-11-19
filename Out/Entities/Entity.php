<?php

namespace Out\Entities;

/**
 * As EntityPopulatr thinks these are Doctrine1.X's DoctrineRecords, and redmine's api 
 * uses only arrays I wrap issue values in an ArrayAccess instance that also implements
 * DoctrineRecord's fromArray() method.
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
abstract class Entity implements \ArrayAccess
{
    const API = null;

    private $values = array();
    
    // <editor-fold defaultstate="collapsed" desc="DoctrineRecord">
    public abstract function fromArray($dataArray);
    
    public abstract function toArray();
    
    public abstract function free();

    /**
     * uses the propper Redmine API object to create an instance of this entity 
     * in redmine.
     * 
     * @return void
     */
    public abstract function save();
    // </editor-fold>
    
}

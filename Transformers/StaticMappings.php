<?php

namespace Transformers;

/**
 * Placeholder for mapping arrays and methods used from other transformes classes.
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class StaticMappings
{
    /**
     * Sheet has [Male|Female] I need [1|0]
     * 
     * @var array
     */
    public static $sex = array (
        'Male'   => 1,
        'Female' => 0,
    );
}

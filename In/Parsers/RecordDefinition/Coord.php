<?php
namespace In\Parsers\RecordDefinition;


/**
 * Description of Coord
 *
 * @author juan.fernandez
 */
class Coord {
    public $x;
    public $y;
    
    /**
     * Represents an increment compatible with $coord to get from current coord to next, 
     * field's coord, normally its Coord->x = 0; Coord->y = 1 which means same 
     * collumn, next line.
     */
    public $incX = 0;
    public $incY = 1;
    
    public function incrementCoord() 
    {
        $this->x += $this->incX;
        $this->y += $this->incY;
    }

    /**
     * Instantiates a Coord Object
     * 
     * @param array $configFieldDefCoord     A two values array like {x: int, y: int} 
     * @param array $configFieldDefIncrement A two values array like {x: int, y: int}
     * often i'll be {x: 0, y: 1} for values in a collumn in the sheet. or {x: 0, y: 0}
     * for header values that stays the same for all records.
     */
    public static function getInstance(array $configFieldDefCoord, array $configFieldDefIncrement) 
    {
        $x = $configFieldDefCoord['x'];
        $y = $configFieldDefCoord['y'];
        
        $incX = $configFieldDefIncrement['x'];
        $incY = $configFieldDefIncrement['y'];
        
        return new Coord($x, $y, $incX, $incY);
    }
    
    protected function __construct($x, $y, $incX, $incY) 
    {
        $this->x = $x;
        $this->y = $y;
        
        $this->incX = $incX;
        $this->incY = $incY;
    }

}

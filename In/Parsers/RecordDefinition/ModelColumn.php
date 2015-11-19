<?php
namespace In\Parsers\RecordDefinition;


/**
 * Description of Coord
 *
 * @author juan.fernandez
 */
class ModelColumn {
    /**
     * Model|EntityName FQCN
     * 
     * @var string
     */
    public $modelName;
    
    /**
     * Model|Entity Collumn
     * 
     * @var string
     */
    public $modelColumn;
    
    /**
     * Model|Entity Collumn glue, in cases where more than one sheet record field
     * are related to the same collum, a concatenation strategy must be given.
     * 
     * If string given, it works like $column .= $glue . $newFieldValue;
     * 
     * @var string|callable
     */
    public $glue = null;

    public function __construct($modelName, $modelColumn, $glue = null) {
        $this->modelName = $modelName;
        $this->modelColumn = $modelColumn;
        $this->glue = $glue;
    }
    
    public function __toString() {
        return sprintf("%s->%s, glue: %s", $this->modelName, $this->modelColumn, $this->glue);
    }
}

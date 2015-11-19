<?php
namespace In\Parsers\RecordDefinition;

/**
 * Description of EntityDefinition
 *
 * @author juan.fernandez
 */
class EntityDefaultDefinition {
    private $columnName = null;
    
    /**
     * 
     * @var string|callable 
     */
    private $default = null;
    
    /**
     * should be true if this defauls is not present un Record definition's fields
     * you can tell that by not finding this entity&column in model: {entity: .., column: ..} 
     * in config
     * 
     * @var boolean
     */
    private $notPresentInFields = null;
    
    function __construct($columnName, $default = null) {
        $this->columnName = $columnName;
        $this->default = $default;
    }

    function getColumnName() {
        return $this->columnName;
    }

    function getDefault() {
        return $this->default;
    }

    /**
     * 
     * @return boolean
     */
    function isNotPresentInFields() {
        return $this->notPresentInFields;
    }

    function setColumnName($columnName) {
        $this->columnName = $columnName;
    }

    function setDefault(callable $default) {
        $this->default = $default;
    }

    function setNotPresentInFields($notPresentInFields) {
        $this->notPresentInFields = $notPresentInFields;
    }
}

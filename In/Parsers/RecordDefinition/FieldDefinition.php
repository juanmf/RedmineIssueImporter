<?php
namespace In\Parsers\RecordDefinition;

/**
 * Description of EntityDefinition
 *
 * @author juan.fernandez
 */
class FieldDefinition {
    /**
     * Represents de Destination persistence FW model name. 
     * @var type 
     */
    private $name;
   
    /**
     * Represents de Destination persistence FW model name. 
     * @var ModelColumn 
     */
    private $model;
    
    /**
     * Represents de Sheet's Record's field location coordinates.
     * 
     * @var Coord 
     */
    private $coord;
    
    /*
     * Represents de default value for this field
     * 
     * @var Callable|string 
     */
    private $default = null;
    
    /**
     * This callable processes the field value.
     * 
     * @var Callable 
     */
    private $transform = null;
    
    /**
     * 
     * @param array $configFieldDef $sheets['<sheetName>']['records']['<recordName>']['fields']
     */
    public static function getInstance($name, array $configFieldDef) {
        $mDef = $configFieldDef['model'];
        $model = new ModelColumn(
                $mDef['entity'], $mDef['column'],
                isset($mDef['glue']) ? $mDef['glue'] : null
            );
        $coord = Coord::getInstance(
                $configFieldDef['coord'], $configFieldDef['increment']
            );
        $default = $configFieldDef['default'];
        $transform = $configFieldDef['transform'];
        return new FieldDefinition($name, $model, $coord, $default, $transform);
    }

    protected function __construct(
            $name, ModelColumn $model, Coord $coord, $default, $transform = null
    ) {
        $this->name = $name;
        $this->model = $model;
        $this->coord = $coord;
        $this->default = $default;
        $this->transform = $transform;
    }
    
    function getName() {
        return $this->name;
    }
    
    /**
     * 
     * @return ModelColumn
     */
    function getModel() {
        return $this->model;
    }

    /**
     * 
     * @return Coord
     */
    function getCoord() {
        return $this->coord;
    }

    /**
     * 
     * @return string|callable
     */
    function getDefault() {
        return $this->default;
    }

    /**
     * 
     * @return callable
     */
    function getTransform() {
        return $this->transform;
    }

}

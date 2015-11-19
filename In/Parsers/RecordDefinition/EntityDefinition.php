<?php
namespace In\Parsers\RecordDefinition;

/**
 * Description of EntityDefinition
 *
 * @author juan.fernandez
 */
class EntityDefinition {
    private $name = null;
    
    /**
     *
     * @var EntityDefaultDefinition[]
     */
    private $defaults = array();
    
    /**
     * FQCN Class name for an Entity to use for hosting some or all of the 
     * sheet's recod's values.
     * 
     * @var type string
     */
    private $schemaEntity = null;

    /**
     * 
     * @param string $name  EntityName   
     * @param array $configEntityDef $sheets['<sheetName>']['records']['<recordName>']['entities']['<EntityName>']
     *                               The structure should be:<pre>
     *    schema_entity: "Name\Space\Entity" optional
     *    defaults:
     *       <fieldName>: "string"|callable
     * </pre>
     *                              
     */
    public static function getInstance($name, array $configEntityDef) 
    {
        $schemaEntity = (isset($configEntityDef['schema_entity']) && ! empty($configEntityDef['schema_entity']))
                      ? $configEntityDef['schema_entity']
                      : null;
        
        $defaults = (isset($configEntityDef['defaults']) && ! empty($configEntityDef['defaults']))
                  ? $configEntityDef['defaults']
                  : array();
        
        return new EntityDefinition($name, $schemaEntity, $defaults);
    }

    protected function __construct($name, $schemaEntity, $defaults) 
    {
        $this->name = $name;
        $this->schemaEntity = $schemaEntity;
        foreach ($defaults as $columnName => $default) {
            $this->defaults[] = new EntityDefaultDefinition($columnName, $default);
        }
    }
    
    function getName() {
        return $this->name;
    }

    function getDefaults() {
        return $this->defaults;
    }

    function getSchemaEntity() {
        return $this->schemaEntity;
    }

    /**
     * 
     * @param type $column
     * @return EntityDefaultDefinition
     */
    public function getDefault($column) {
        return $this->defaults[$column];
    }
}

<?php
namespace In\Parsers\RecordDefinition;


/**
 * Description of RecordDefinition
 *
 * @author juan.fernandez
 */
class RecordDefinition {
    /**
     * This Record Configuration name
     * @var string 
     */
    private $name;
    
    /**
     * This sheet Configuration Persistence Engine, could be either 
     * Redmine or Doctrine2, so far.
     * 
     * @var string 
     */
    private $persistenceEngine;
    
    /**
     * Applies for Redmine ProjectId
     * 
     * Must be the project identifier. 
     * <RedmineDomain>/projects/<projectIdentifier>/issues?...
     * 
     * @var string
     */
    private $projectId;
    
    /**
     * Collection of {@link EntityDefinition}
     * 
     * @var EntityDefinition[] 
     */
    private $entities;
    
    /**
     * Describes de sheet field mapping to an Entity.
     * 
     * @var FieldDefinition[]
     */
    private $fields;
   
    /**
     * contains the same elements as $fields but keys are Field names.
     * 
     * @var FieldDefinition[]
     */
    private $relationalFields;
    
    /**
     * Creates a RecordDefinition object, it needs data from config.yml
     * 
     * @param array $configRecodDef $sheets['<sheetName>']['records']['<recordName>']
     */
    public static function getInstance(array $configRecodDef, $persistenceEngine) {
        $name = $configRecodDef['name'];
        $projectId = $configRecodDef['project_id'];
        $fields = array();
        foreach ($configRecodDef['fields'] as $fname => $field) {
            $fields[] = FieldDefinition::getInstance($fname, $field);
        }
        
        $entities = array();
        foreach ($configRecodDef['entities'] as $ename => $entity) {
            $entities[] = EntityDefinition::getInstance($ename, $entity);
        }
        $recordDef = new RecordDefinition(
                $name, $projectId, $entities, $fields, $persistenceEngine
            );
        return $recordDef;
    }
    
    private function __construct($name, $projectId, array $entities, array $fields, $persistenceEngine) {
        $this->name = $name;
        $this->projectId = $projectId;
        $this->entities = $entities;
        $this->fields = $fields;
        $this->persistenceEngine = $persistenceEngine;
        foreach ($fields as $f) {
            $this->relationalFields[$f->getName()] = $f;
        }
        $this->markEntityColumnsMissingInRecordFields();
    }
    
    function getPersistenceEngine() {
        return $this->persistenceEngine;
    }

        function getName() {
        return $this->name;
    }

    function getProjectId() {
        return $this->projectId;
    }

    function getEntities() {
        return $this->entities;
    }

    function getFields() {
        return $this->fields;
    }
    
    function getField($name) {
        if (!isset($this->relationalFields[$name])) {
            throw new \InvalidArgumentException("Field naem not found: " . $name);
        }
        return $this->relationalFields[$name];
    }

    /**
     * A Record definitoin has two portions of interest here:<pre>
     * recordName:  
     *     entities: {defaults: {columnName: Default, ..}} 
     *     fields: {fieldName: {.., model: {entity: entName, column: colName}, ..}, ..}
     * </pre>
     * 
     * here we seek for enetities->defaults->colunmanes not present in 
     * fields->fieldname->model->[entName][colName] and mark them.
     * @see EntityDefinition
     */
    public function markEntityColumnsMissingInRecordFields() {
        foreach ($this->entities as $entityConfigDef) {
            foreach ($entityConfigDef->getDefaults() as $column => $default) {
                $this->markCulumnNotPresentInFields($entityConfigDef, $column);
            }
        } 
    }
    
    private function markCulumnNotPresentInFields(EntityDefinition $entityConfigDef, $column) {
        foreach ($this->fields as $field) {
            if ($field->getModel()->modelName == $entityConfigDef->getName()
                && $field->getModel()->modelColumn == $column
            ) {
                return;
            }
        }
        $entityConfigDef->getDefault($column)->setNotPresentInFields(true);
    }
}
 
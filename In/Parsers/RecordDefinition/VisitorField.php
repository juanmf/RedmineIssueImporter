<?php
namespace In\Parsers\RecordDefinition;

/**
 * Description of EntityDefinition
 *
 * @author juan.fernandez
 */
class VisitorField {
    /**
     *
     * @var FieldDefinition
     */
    private $fieldDefinition;   
    
    private $currentValue;
    
    /**
     * 
     * @param array $configFieldDef $sheets['<sheetName>']['records']['<recordName>']['fields']
     */
    public static function getInstance(FieldDefinition $fieldDefinition) {
        return new VisitorField($fieldDefinition);
    }

    protected function __construct(FieldDefinition $fieldDefinition) {
        $this->fieldDefinition = $fieldDefinition;
    }
    
    function getCurrentValue() {
        return $this->currentValue;
    }

    function setCurrentValue($currentValue) {
        $this->currentValue = $currentValue;
    }
    
    public function getCoord() {
        return $this->fieldDefinition->getCoord();
    }

    public function getDefault() {
        return $this->fieldDefinition->getDefault();
    }

    public function getModel() {
        return $this->fieldDefinition->getModel();
    }

    public function getName() {
        return $this->fieldDefinition->getName();
    }

    public function getTransform() {
        return $this->fieldDefinition->getTransform();
    }
}

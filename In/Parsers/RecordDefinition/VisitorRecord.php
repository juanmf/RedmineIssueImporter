<?php
namespace In\Parsers\RecordDefinition;

use \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * VisitorRecord holds all the structure of RecordDefinition plus a current 
 * Value, for each field that represents de current record values.
 * 
 * It also has logic that helps transform Record Fields into Entity Column.
 *
 * @author juan.fernandez
 */
class VisitorRecord implements \Iterator {

    private $position = 0;
    
    /**
     *
     * @var RecordDefinition
     */
    private $recordDefinition;
    
    /**
     *
     * @var VisitorField[] 
     */
    private $fields = array();
    
    /**
     * Same VisitorField as in $fields but organized per related entity.
     * 
     * Entities mapped by this SheetRecord.
     * The structure is:<pre>
     *      VisitorField[entityName][EntotyColumn]:
     *      {
     *          entityName: {
     *              EntotyColumn: VisitorField,
     *              ...
     *          },...
     *      } 
     * 
     * </pre> 
     * 
     * @var VisitorField[][] 
     */
    private $entities = array();
            
    public function __construct(RecordDefinition $recordDefinition) {
        $this->recordDefinition = $recordDefinition;
        $this->initFields($recordDefinition);
        $this->initEntityFields();
    }
        
    private function initFields($recordDefinition) {
        foreach ($recordDefinition->getFields() as $field) {
            $this->fields[] = VisitorField::getInstance($field);
        }
    }
    
    /**
     * keeps a multidimensional array of VisitorFields frouped by Entity to which 
     * they relate.
     * 
     * @throws InvalidConfigurationException
     */
    private function initEntityFields() {
        foreach ($this->fields as $field) {
            $entity = $field->getModel();
            if (! (isset($entity->modelColumn) && isset($entity->modelName))) {
                throw new InvalidConfigurationException(
                        "Check out entity mapping config for field: " . $field->getName()
                    );
            }
            if (isset($this->entities[$entity->modelName][$entity->modelColumn])) {
                $this->handleCompositeColumnFields($field);
            } else {
                $this->entities[$entity->modelName][$entity->modelColumn] = $field;
            }
        }
    }
    
    /**
     * Turns a composite collumn into an array of VisitorField. Normally it'd contain
     * a single VisitorField. And adds the given $field to this array.
     * 
     * @param \In\Parsers\RecordDefinition\VisitorField $field
     * @throws InvalidConfigurationException
     */
    private function handleCompositeColumnFields(VisitorField $field) {
        $entity = $field->getModel();
        if (null == $entity->glue) {
            throw new InvalidConfigurationException(
                    "Composite Entity Column needs a glue in model definition"
                );
        }
        if (! is_array($this->entities[$entity->modelName][$entity->modelColumn])) {
            $this->entities[$entity->modelName][$entity->modelColumn] =
                    array($this->entities[$entity->modelName][$entity->modelColumn]);
        }
        
        $this->entities[$entity->modelName][$entity->modelColumn][] = $field;
    }

    /**
     * return the subset of fields related to the given entity name.
     * 
     * @param string $entity The Entity name.
     * 
     * @return VisitorField[] The Fields related to the given entity's columns
     * in current Record.
     */
    public function getEntityFields($entity) {
        if (! isset($this->entities[$entity])) {
            throw new \InvalidArgumentException("Non existent entity given: " . $entity);
        }
       return $this->entities[$entity]; 
    }
    
    public function getPersistenceEngine() {
        return $this->recordDefinition->getPersistenceEngine();
    }
    
    /**
     * 
     * @return RecordDefinition
     */
    function getRecordDefinition() {
        return $this->recordDefinition;
    }

        /**
     * 
     * @return VisitorField
     */
    public function current() {
        return $this->fields[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        $this->position++;
    }

    public function rewind() {
        $this->position = 0;
    }

    /**
     * 
     * @return bool
     */
    public function valid() {
        return isset($this->fields[$this->position]);
    }

    /**
     * This should receive the visitorFields for a paticular Entity. i.e. a 1st
     * level index of the self::$entities attribute of an instance of this class
     * 
     * @param VisitorField[] $entFields
     * 
     * @return array suitable for ActiveRecord::fromArray() data assignment. 
     */
    public static function processFieldsDataForEntityColumns(array $entFields) {
        $entityData = array();
        foreach ($entFields as $visitorField) {
            self::createColumn($visitorField, $entityData);
        }
        return $entityData;
    }

    /**
     * Takes care of Entity Column value. If Column, in $entityData is missing,
     * just adds it, otherwise {@link self::joinValues()}
     * 
     * @param VisitorField|VisitorField[] $visitorField
     * @param array        $entityData
     */
    private static function createColumn(
            $visitorField, array & $entityData
    ) {
        if (is_array($visitorField)) {
            self::cerateCompositeColumn($visitorField, $entityData);
            return;
        } 
        $column = $visitorField->getModel()->modelColumn;
        $entityData[$column] = $visitorField->getCurrentValue();
    }

    /**
     * Takes care of Entity Column value. If Column, in $entityData is missing,
     * just adds it, otherwise {@link self::joinValues()}
     * 
     * @param VisitorField|VisitorField[] $visitorField
     * @param array        $entityData
     * 
     * @throws InvalidConfigurationException
     */
    public static function cerateCompositeColumn(array $visitorFields, array & $entityData) 
    {
        foreach ($visitorFields as $vf) {
            $column = $vf->getModel()->modelColumn;
            if (isset($entityData[$column])) {
                if (empty($vf->getModel()->glue)) {
                    throw new InvalidConfigurationException(
                            "Not the 1st instance of Field for the same column without glue!: "
                          . $vf->getModel()
                        );
                }
                $entityData[$column] = self::joinValues(
                        $vf->getModel()->glue, $entityData[$column], 
                        $vf->getCurrentValue()
                    );
            } else {
                $entityData[$column] = $vf->getCurrentValue();
            }
        }
    }

    /**
     * Retuns a joint value. using glue, which can be either string or 
     * callable_fn($glue, $left, $right)
     * 
     * @param string|callable $glue  The fn or string that's used for join.
     * @param mixed           $left  The left value.
     * @param mixed           $right The right value.
     * 
     * @return mixed. Could be string of callable return value.
     * @throws \InvalidArgumentException if Glue is neither string, nor callable.
     */
    private static function joinValues($glue, $left, $right) {
        if (is_callable($glue)) {
            return call_user_func($glue, $left, $right);
        } else if (is_string($glue)) {
            return $left . $glue . $right;
        } else {
            throw new \InvalidArgumentException("Glue is neither string, nor callable.");
        }
    }

}

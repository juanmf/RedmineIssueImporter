<?php

namespace Transform\EntityPopulator\Helper;

use In\Parsers\RecordDefinition\VisitorRecord;
use In\Parsers\RecordDefinition\VisitorField;

/**
 * Description of PhpConfigsHelper
 *
 * @author juan.fernandez
 */
class RecordSanitizerHelper {

    /**
     * This method is ment to distribute values among entities, in redmine we 
     * are just assigning all fields to one issue.
     * 
     * For each record, this method splits it's values in several arrays, one
     * for each entity defined in this recordDefinition, not in the Doctrine
     * schema, assigning to each its own.
     * 
     * @param VisitorRecord $sheetRecord    The values retrieved by the parser 
     * for this record.
     * @param array         $recordDef The record Definition from config.
     * 
     * @return array The splited array, wich keys are the related entities names.
     * {entName => {EntColunm1 => value, .., EntColunmN => value }}
     */
    public static function valueArrayChunkForEntitiesPerRecord(
        VisitorRecord $sheetRecord, array $recordDef
    ) {
        $split = array();
        foreach ($recordDef['entities'] as $entityName => $definition) {
            foreach ($sheetRecord as $fieldName => $value) {
                // $fieldName holds the field's name as in importSchema config
                if ($entityName !== $fieldDef['model']['entity']) {
                    continue;
                }
                // for each entity maps it's sheet data to Doctrine column names
                $column = $fieldDef['model']['column'];
                $value = self::loadFieldValue($value, $fieldName, $recordDef);
                if (empty($fieldDef['model']['glue'])
                    || empty($split[$entityName][$column])
                ) {
                    // $entityName holds the entity's name as in importSchema config's "entities" key
                    $split[$entityName][$column] = $value;
                } else {
                    // note that here, declaration order is important in importSchema 
                    // TODO: PROVE the previous statement
                    $split[$entityName][$column] = self::concat(
                        $value, $split[$entityName][$column], $fieldName,
                        $recordDef
                    );
                }
            }
        }
        return $split;
    }
    
    public static function lookUpForRecordFieldsDefault(VisitorRecord $currentRecord) {
        foreach ($currentRecord as $field) {
            /* @var $field VisitorField */
            if (empty($field->getCurrentValue())) {
                $field->setCurrentValue(self::getDefault($field->getDefault()));
            }
        }
    }
    
    public static function applyRecordFieldsTransformation(VisitorRecord $currentRecord) {
        foreach ($currentRecord as $field) {
            /* @var $field VisitorField */
            if (! empty($field->getTransform())) {
                $field->setCurrentValue(self::getTransformation(
                    $field->getTransform(), $field->getCurrentValue()
                ));
            }
        }
    }
    
    /**
     * Searches, for each entity defined in importSchema for this record, the
     * associated values in $entVals, trying to replace null by their defaults.
     * If no such index exists in $entVals[$imporEntName] then one is created
     * and populated entirelly by default values. That means that the sheet had
     * no information about this entity, but yet it's defined in this $recordDef.
     * Note that this could lead to a doctrine exception if the provided defaults
     * do not satisfy the entity constraints.
     * 
     * @param array &$entVals  Values from Parser after being Splited. The data 
     * structure must as per { @link self::valueArrayChunkForEntitiesPerRecord() }
     * {entName => {EntColunm1 => value, .., EntColunmN => value }}
     * @param array $recordDef This record definition.
     * 
     * @return void. Since $entVals is passed by reference.
     */
    public static function loadRecordDefinitionDefaultValuesPerEntityArrayChunk(
            array & $entVals, array $recordDef
    ) {
        foreach ($recordDef['entities'] as $importEntName => $definition) {
            $entityDef = $recordDef['entities'][$importEntName];
            if (isset($entVals[$importEntName])) {
                // Fills existing but null schema columns with $recordDef defaults  
                foreach ($entVals[$importEntName] as $doctrineSchemaColName => $val) {
                    if (null === $val
                        && isset($entityDef['defaults'])
                        && isset($entityDef['defaults'][$doctrineSchemaColName])
                    ) {
                        $defVal = self::getDefault(
                            $entityDef['defaults'][$doctrineSchemaColName]
                        );
                        $entVals[$importEntName][$doctrineSchemaColName] = $defVal;
                    }
                }
            } else {
                $entVals[$importEntName] = array();
            }
            // Find missing fields in sheet, having defaults in recordDef, and create them wih its defaults.
            if (! empty($entityDef['defaults'])) {
                $missingFields = array_diff(
                    array_keys($entityDef['defaults']),
                    array_keys($entVals[$importEntName])
                );
                foreach ($missingFields as $column) {
                    $defVal = self::getDefault(
                        $entityDef['defaults'][$column]
                    );
                    $entVals[$importEntName][$column] = $defVal;
                }
            }
        }
    }
    
    /**
     * Finds out the right way to interpret $defaultSpecs and gets a default 
     * value for some field being processed.
     * 
     * @param Callable|string $defaultSpecs Either a literal value from config
     * or a function that should return a value used as default for some field.
     * 
     * @see self::loadEntityDefaultValues()
     * @return string The value to be used as default in the field that is being 
     * populated. 
     */
    public static function getDefault($defaultSpecs)
    {
        if (is_callable($defaultSpecs)) {
            $defaultSpecs = call_user_func($defaultSpecs);
        }
        return $defaultSpecs;
    }
    
    /**
     * Concatenates the values of a new field (found in the record) with previous
     * ones, all of the related to the same column and entity.
     * 
     * @param string $newValue  The value just found in new record field, with
     * need to be concatenated with previous ones.
     * @param string $acumVal   The result of previous related fields concatenations.
     * @param string $fieldName The Field name in importSchema from wich to get
     * the glue information, it can be either a string or a callback function.
     * @param array  $recordDef The record definition
     * 
     * @return string the concatenated value.
     */
    protected static function concat($newValue, $acumVal, $fieldName, array $recordDef) 
    {
        $glue = $fieldDef['model']['glue'];
        if (is_array($glue)) {
            $acumVal = call_user_func($glue, $newValue, $acumVal);
        } else {
            $acumVal .= ( $glue . $newValue);
        }
        return $acumVal;
    }

    /**
     * Finds out the right way to interpret $transformSpecs and gets a transformed 
     * value for some field being processed.
     * 
     * @param Callable $transformSpecs The callable that should be called with the 
     * original value that should be processed.
     * @param string   $origVal        The value as extracted from the curren Sheet 
     * record field being parced.
     * 
     * @return string The transformed value, or the original value if $transformSpecs
     * is not callable.
     */
    protected static function getTransformation($transformSpecs, $origVal)
    {
        $transformed = $origVal;
        if (is_callable($transformSpecs)) {
            $transformed = call_user_func($transformSpecs, $origVal);
        }
        return $transformed;
    }

    /**
     * On an import Sheet Field basis, check for empty values and replace them
     * with either defaults, if defined, or null values, to make DoctrineRecord
     * apply it's defaults or thorw proper exceptions.
     * 
     * @param string $value     The string representation of any column value to be 
     * persisted, as it was retrieved from the sheet.
     * @param string $fieldName Some field name under "fields" key in importSheet.yml
     * for this record.
     * @param array  $recordDef This record definition.
     * 
     * @return string|null The $value as it was retrieved, a default one or null
     */
    private static function loadFieldValue($value, $fieldName, array $recordDef) 
    {
        $fieldDef = $recordDef['fields'][$fieldName];
        if (empty($value)) {
            $value = null;
            if (! empty($fieldDef['default'])) {
                $value = self::getDefault(
                    $fieldDef['default']
                );
            }
        } else {
            if (! empty($fieldDef['transform'])) {
                $value = self::getTransformation(
                    $fieldDef['transform'],
                    $value
                );
            }
        }
        return $value;
    }

}

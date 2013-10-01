<?php

namespace EntityPopulator;

use \Config\Config,    
    Parsers\SheetRecordParserAbstract;

/**
 * Instantiates Entities acording to record definition present in config, populates 
 * them and try to save those entities using a Redmine API. The source of
 * data must be a {@link SheetRecordParserAbstract} object.
 *
 * @author Juan Fernandez <juanmf@gmail.com>
 */
class EntityPopulator
{
    const ERR_DUPLICATE = 1;
    const ERR_FOREIGN = 2;
    
    private static $_maxExecTime = null;
    
    protected static $_conflicts = null;
    
    /**
     * Array of error messages
     * 
     * @var array
     */
    protected static $_errors = array();
    
    /**
     *  Behavior to assume for error handling. Posible values ar:
     *  continue: All errors are loaded in a return array
     *  stop: Throw Exception on first error. But save as much as possible.
     *  rollback: Revet changes, do nothing. and throw Exception.
     *  @var string
     */
    protected static $_onErrorBehavior = null;
    
    /**
     * Define if displays exceptions when an error ocurred.
     * @var bool
     */
    protected static $_onErrorDisplayExceptions = null;
    
    /**
     * // Not used 
     * The current Doctrine_Manager connection object.
     * @var Doctrine_Connection
     */
    public static $conn;

    /**
     * // TODO: WE ARE NOT USING SF1.4 FORMS HERE, COME OUT WITH SOME VALIDATION STRATEGY
     * 
     * Handles Sheet Record data validation by using a sfForm dynamicaly 
     * generated according to RecordDefinition config.
     * 
     * @param array                   $sheetRecord The Current Record being parsed.
     * @param sfImportSheetRecordForm $sheetForm   The Form object used to validate 
     * the input data.
     * 
     * @return array|null With validated values. Null on Error, if this kind of 
     * error didn't throw an error.
     */
    protected static function validateSheetRecord(
        array $sheetRecord, sfImportSheetRecordForm $sheetForm = null
    ) {
        throw new \LogicException('Not implemented yet, who called me?');
        
        //        $values = $this->normalizeValues($sheetRecord);
        //        if (null === $sheetForm) {
        //            return $values;
        //        }
        //        $sheetForm->bind($values);
        //        if (! $sheetForm->isValid()) {
        //            self::handleValidationError($sheetForm, $values);
        //            return null;
        //        }
        //        return $sheetForm->getValues();
    }
    
    /**
     * Put values found in parsed record in a simpler {fieldName => fieldValue} new array 
     * 
     * @param array $sheetRecord The Current Record being parsed.
     * 
     * @return array {fieldName => fieldValue} 
     */
    private static function normalizeValues(array $sheetRecord)
    {
        $values = array();
        foreach ($sheetRecord as $field) {
            $values[$field['name']] = $field['value'];
        }
        return $values;
    }

    /**
     * Persists entities values.
     * 
     * @param Entities\Entity[]         $entities     The array of Entities, encapsulating
     * redmine APi calls, to save.
     * @param SheetRecordParserAbstract $recordParser The record parser being 
     * traversed.
     * 
     * @return void
     */
    protected static function saveEntities(
        $entities, SheetRecordParserAbstract $recordParser
    ) {
        $save = (bool) Config::get('save_records', true);
        foreach ($entities as $entName => $entity) {
            if ($save) {
                try {   
                    $entity->save();
                } catch (RedmineApiException $e) {
                    $eventName = 'redmine.api_error';
                    $parameters = array(
                        'record_parser'  => $recordParser, 
                        'current_entity' => $recordParser->getCurrentEntity(), 
                        'entity'         => reset($entities),
                        'type'           => $e->return,
                        'err_code'       => $e->getCode(),
                    );
                    self::registerConflictingRow($parameters);
                    self::notify($eventName, $parameters);
                }
            }
            /* @var $entity Doctrine_Record */
            $entity->free(true);
            unset($entities[$entName]);
        }
    }
    
    /**
     * Records entities which cause troubles in {@link self::$_conflicts} array.
     * 
     * @param array $parameters Same Array of parameters that is sent to the event
     * handlers with keys:<pre>
     *              'record_parser'
     *              'current_entity'
     *              'entity'
     *              'type'             
     *              'duplicate_index'|'foreign_column'
     * </pre>
     * 
     * @return void
     */
    public static function registerConflictingRow(array $parameters) 
    {
        $entity = $parameters['entity'];

        self::$_conflicts[get_class($entity)][] = array(
            'entity' => $entity->toArray(),
            'type'   => $parameters['type'],
        );
    }
    
    /**
     * Gets all the conflicts that fired an exception.
     * 
     * @see self::registerConflictingRow()
     * @return array With the conflicts. 
     * array('entity' => $entity->toArray(), 'type' => Doctrine_core::ERR_*)
     */
    public static function getConflicts()
    {
        return self::$_conflicts;
    }

    /**
     * // TODO: this is interesting but not implemented yet.
     * Fires an event notification to the system.
     * 
     * @param string $eventName  The event name to wich handlers connect to. 
     * @param array  $parameters The event parameters, to initialize the sfEvent
     * Object used to notify handlers.
     * 
     * @return void
     */
    protected static function notify($eventName, array $parameters) 
    {
        $dispatcher = null; // TODO: CONSIDER symfony EventDispatcher Component here
    }
    
    /**
     * Instantiates the entities involved in this sheet reccord according to the 
     * definition provided in config, and present in <i>$recordDef</i>, and 
     * populates them with the values present in <i>$entVals</i>.
     * 
     * @param array $entVals   The splited array, wich keys are the related 
     * entities' names. {@see self::splitValuesForEntity()}
     * @param array $recordDef The record Definition from config.
     * 
     * @return array With the entities loaded with the values extracted from the
     * sheet record being parsed.
     */
    protected static function loadEntities(array $entVals, array $recordDef) 
    {
        $entities = self::instantiateEntities($recordDef);
        foreach ($entities as $entName => $entity) {
            /* @var $entity sfDoctrineRecord */
            if (isset($entVals[$entName])) {
                // when refClasses are added, they can cause troubles here.
                $entity->fromArray($entVals[$entName]);
            }
        }
        return $entities;
    }

    /**
     * Configures error handling behavior
     * 
     * @param string $onErrorBehavior          Controls how to react to errors. 
     * Optional, defaults to null. {@see self::$_onErrorBehavior}
     * @param bool   $onErrorDisplayExceptions Define if displays exceptions when 
     * an error ocurred.
     * 
     * @return void
     */
    private static function _loadErrorConfig(
        $onErrorBehavior = null, $onErrorDisplayExceptions = null
    ) {
        $onError = Config::get('on_error');
        if (null === self::$_onErrorBehavior) {
            self::$_onErrorBehavior = (null !== $onErrorBehavior) 
                                    ? $onErrorBehavior 
                                    : $onError['behavior'];
        }
        if (null === self::$_onErrorBehavior) {
            self::$_onErrorDisplayExceptions = (null !== $onErrorDisplayExceptions) 
                                             ? $onErrorDisplayExceptions 
                                             : $onError['display_exceptions'];
        }
    }

    /**
     * Alters default script execution time. Toggling it between infinite and 
     * original value.
     * 
     * @param bool $restore If true restores the original values of php.ini vars
     * 
     * @return void
     */
    protected static function handleExecutionTime($restore = false) 
    {
        if (null === self::$_maxExecTime) {
            self::$_maxExecTime = (int) ini_get('max_execution_time');
            // checkout safe_mode
            set_time_limit(0);
        } elseif ($restore) {
            // FIXME: When recursoin takes place, for foreign constraints, time limit 
            // gets restored, when aver a sub process finish, thats wrong.
            set_time_limit(self::$_maxExecTime);
            self::$_maxExecTime = null;
        }
    }
    
    /**
     * For each Record returned by the parser, try to assign it to the proper
     * entity. Also takes care of populating defaults found in importSchema
     * configuration.
     * 
     * @param SheetRecordParserAbstract $recordParser             An instanse of
     * SheetRecordParserAbstract already associated to the input file to be parsed.
     * @param sfImportSheetRecordForm   $sheetForm                The sheetForm 
     * used for validation it should be already initialized by the action.
     * @param array                     $onErrorBehavior          Controls how to 
     * react to errors. Optional, defaults to null.
     * @param bool                      $onErrorDisplayExceptions Define if 
     * display exceptions when an error ocurred. 
     * 
     * @return array With error messages.
     * @see self::$_onErrorBehavior
     */
    public static function populateEntities(
        SheetRecordParserAbstract $recordParser, 
        $onErrorBehavior = null, $onErrorDisplayExceptions = null
    ) {
        self::handleExecutionTime();
        self::_loadErrorConfig($onErrorBehavior, $onErrorDisplayExceptions);
        $recordDef = $recordParser->getSheetRecordDefinition();
        //self::$conn->transaction->setIsolation('READ UNCOMMITTED');
        try {
            foreach ($recordParser as $sheetRecord) {
                // We are not validating input. if we did, sfForms are missing.
//                 $validatedVals = self::validateSheetRecord($sheetRecord);
                // if (null === $validatedVals) {
                //     continue;
                // }
                $validatedVals = self::normalizeValues($sheetRecord);
                $entVals = self::splitValuesForEntity($validatedVals, $recordDef);
                self::loadEntityDefaultValues($entVals, $recordDef);
                try {
                    $entities = self::loadEntities($entVals, $recordDef);
//                    self::relateEntities($entities, $recordDef);
                    self::saveEntities($entities, $recordParser);
                } catch (MethodNotImplementedException $e) {
                    // BOOM! prevent next catch to hide this.
                    throw $e;
                } catch (Exception $e) {
                    self::handlePopulationError($e, $validatedVals, $entities);
                }
            }
        } catch (sfFileException $e) {
            if (true == self::$_onErrorDisplayExceptions) {
                throw $e;
            }
        }
        self::handleExecutionTime(true);
        return self::$_errors;
    }

    /**
     * Takes care of validation errors in input data. Also adds the error message 
     * to the <i>self::$_errors</i> array.
     * 
     * @param sfImportSheetRecordForm $sheetForm The Form object used to validate 
     * the input data.
     * @param array                   $values    The values from the current 
     * record being parsed. As a key value array. {@see self::validateSheetRecord()}
     * 
     * @return void
     */
    protected static function handleValidationError(
        sfImportSheetRecordForm $sheetForm, $values
    ) {
        $message = self::writeError($sheetForm, $values);
        self::$_errors[] = $message;
        self::handleError($message);
    }

    /**
     * Handles error as specified by {@link self::$_onErrorBehavior}
     * 
     * @param Exception $e               The unhandled exception cought by the 
     * caller method. 
     * @param array     $validatedValues The values from current record after being 
     * validated by the sfImportSheetRecordForm object, if used. If not used, these 
     * values are exactly the same as they were input in the sheet. Either case
     * before data is as it was before setting default values and transformations.
     * @param array     $entities        The entities loaded with the values 
     * extracted from the sheet record being parsed. 
     * 
     * @return void
     */
    protected static function handlePopulationError(
        Exception $e, array $validatedValues, array $entities
    ) {
        $message = implode(PHP_EOL . " # ", $validatedValues)
                 . " # Errors: " . $e->getMessage();
        self::$_errors[] = $message;
        self::handleError($message);
    }

    /**
     * Handles error as specified by {@link self::$_onErrorBehavior}
     * 
     * @param type $message The error message from the exception thrown.
     * 
     * @see self::handlePopulationError()
     * @return void
     */
    protected static function handleError($message)
    {
        switch (self::$_onErrorBehavior) {
            case 'continue':
                break;
            case 'stop':
                self::$conn->commit();
                throw new sfFileException($message);
                break;
            case 'rollback':
                self::$conn->rollback();
                throw new sfFileException($message);
                break;
            default:
                throw new Exception(
                    'La opcion: ' . self::$_onErrorBehavior
                    . ' no es una opcion válida para la clave on_error_behavior'
                );
                break;
        }
    }

    /**
     * Makes a human readable error report for each failing field in current 
     * record being parsed. Extracts error info from sfImportSheetRecordForm's 
     * ErrorSchema.
     * 
     * @param sfImportSheetRecordForm $sheetForm The Form object used to validate 
     * the input data.
     * @param array                   $values    The values from the current 
     * record being parsed. As a key value array. {@see self::validateSheetRecord()}
     * 
     * @return string 
     */
    protected static function writeError(
        sfImportSheetRecordForm $sheetForm, array $values
    ) {
        $error = array();
        $message = 'No se ha utilizado validación de datos.';
        if (null !== $sheetForm) {
            $validationErrors = $sheetForm->getErrorSchema()->getErrors();
            foreach ($validationErrors as $eName => $ve) {
                $error[] = sprintf(
                    'El campo %s no pasó la validación. Error: %s', $eName, $ve
                );
            }
            $message = implode(PHP_EOL . " # ", $error);
        }
        $row = implode(PHP_EOL . " # ", $values);
        return $row . " # Errors: " . $message;
    }

    /**
     * For each entity defined in $recordDef['entities']. instantiate a
     * sfDoctrineRecord associated to it.
     * 
     * @param array $recordDef The record Definition from config.
     * 
     * @return array With the  sfDoctrineRecords, one for each import entity.
     * Note that it's not nessesary to have a one to one mapping between
     * $recordDef['entities'] and the entities in de model, since we could find
     * $recordDef['entities']['padre'] and $recordDef['entities']['hijo']. Both
     * with $recordDef['entities']['padre|hijo']['schema_entity'] = Persona
     */
    protected static function instantiateEntities(array $recordDef)
    {
        $importEntityNames = array_keys($recordDef['entities']);
        $entities = array();
        foreach ($importEntityNames as $eName) {
            $entityClass = 'EntityPopulator\\Entities\\';
            $entityClass .= (isset($recordDef['entities'][$eName]['schema_entity'])
                            && (null !== $recordDef['entities'][$eName]['schema_entity'])) 
                         ? $recordDef['entities'][$eName]['schema_entity'] 
                         : $eName;
            $entities[$eName] = new $entityClass();
        }
        return $entities;
    }

    /**
     * This method is ment to distribute values among entities, ith redmine we 
     * are just assigning all fields to one issue.
     * 
     * For each record, this method splits it's values in several arrays, one
     * for each entity defined in this recordDefinition, not in the Doctrine
     * scheama, assigning to each its own.
     * 
     * @param array $values    The values retrieved by the parser for this record.
     * @param array $recordDef The record Definition from config.
     * 
     * @return array The splited array, wich keys are the related entities names.
     * {entName => {EntColunm1 => value, .., EntColunmN => value }}
     */
    protected static function splitValuesForEntity(
        array $values, array $recordDef
    ) {
        $split = array();
        foreach ($recordDef['entities'] as $entityName => $definition) {
            foreach ($values as $fieldName => $value) {
                // $fieldName holds the field's name as in importSchema config
                if ($entityName !== $recordDef['fields'][$fieldName]['model']['entity']) {
                    continue;
                }
                // for each entity maps it's sheet data to Doctrine column names
                $column = $recordDef['fields'][$fieldName]['model']['column'];
                $value = self::loadFieldValue($value, $fieldName, $recordDef);
                if (empty($recordDef['fields'][$fieldName]['model']['glue'])
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
        $glue = $recordDef['fields'][$fieldName]['model']['glue'];
        if (is_array($glue)) {
            $acumVal = call_user_func($glue, $newValue, $acumVal);
        } else {
            $acumVal .= ( $glue . $newValue);
        }
        return $acumVal;
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
     * @param array &$entVals  Values from Parser after being Splited.
     * @param array $recordDef This record definition.
     * 
     * @return void. Since $entVals is passed by reference.
     */
    protected static function loadEntityDefaultValues(array & $entVals, array $recordDef)
    {
        foreach ($recordDef['entities'] as $imporEntName => $definition) {
            if (isset($entVals[$imporEntName])) {
                // Fills existing but null schema columns with $recordDef defaults  
                foreach ($entVals[$imporEntName] as $doctrineSchemaColName => $val) {
                    if (null === $val
                        && isset($recordDef['entities'][$imporEntName]['defaults'])
                        && isset(
                                $recordDef['entities'][$imporEntName]['defaults'][$doctrineSchemaColName]
                        )
                    ) {
                        $defVal = self::getDefault(
                            $recordDef['entities'][$imporEntName]['defaults'][$doctrineSchemaColName]
                        );
                        $entVals[$imporEntName][$doctrineSchemaColName] = $defVal;
                    }
                }
            } else {
                $entVals[$imporEntName] = array();
            }
            // Find missing fields in sheet, having defaults in recordDef, and create them wih its defaults.
            if (! empty($recordDef['entities'][$imporEntName]['defaults'])) {
                $missingFields = array_diff(
                    array_keys($recordDef['entities'][$imporEntName]['defaults']),
                    array_keys($entVals[$imporEntName])
                );
                foreach ($missingFields as $column) {
                    $defVal = self::getDefault(
                        $recordDef['entities'][$imporEntName]['defaults'][$column]
                    );
                    $entVals[$imporEntName][$column] = $defVal;
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
    protected static function getDefault($defaultSpecs)
    {
        if (is_callable($defaultSpecs)) {
            $defaultSpecs = call_user_func($defaultSpecs);
        }
        return $defaultSpecs;
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
        if (is_callable($transformSpecs)) {
            $origVal = call_user_func($transformSpecs, $origVal);
        }
        return $origVal;
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
    protected static function loadFieldValue($value, $fieldName, array $recordDef) 
    {
        if (empty($value)) {
            $value = null;
            if (! empty($recordDef['fields'][$fieldName]['default'])) {
                $value = self::getDefault(
                    $recordDef['fields'][$fieldName]['default']
                );
            }
        } else {
            if (! empty($recordDef['fields'][$fieldName]['transform'])) {
                $value = self::getTransformation(
                    $recordDef['fields'][$fieldName]['transform'],
                    $value
                );
            }
        }
        return $value;
    }

    /**
     * When ever we find a refClass, it may introduce trouble if it has more fields
     * than just the two related foreign keys. For such situations valid default 
     * values should be provided.
     * 
     * @param string           $entName   The entity name as in record definition config.
     * @param string           $relName   The relation name as in record definition config.
     * @param sfDoctrineRecord $refEntity The new entity acting as a refclass
     * @param array            $recordDef This record definition.
     * 
     * @return void
     */
    protected static function loadRefEntityDefaults(
        $entName, $relName, sfDoctrineRecord $refEntity, array $recordDef
    ) {
        if (isset($recordDef['entities'][$entName]['relations'][$relName]['refClassDefaults'])
            && ! empty($recordDef['entities'][$entName]['relations'][$relName]['refClassDefaults'])
        ) {
            $columns = $recordDef['entities'][$entName]['relations'][$relName]['refClassDefaults'];
            foreach ($columns as $colName => $defVal) {
                $refEntity->set($colName, self::getDefault($defVal));
            }
        }
    }

    /**
     * Once all the data in the current Record is assigned to the entities. This
     * method tries to relate those entities by finding the entities's methods
     * responsible for this, and adding each entity to its related ones.
     * 
     * @param array &$entities The array with sfDoctrineRecord instances, one 
     * for each $recordDef['entities'] @see self::instantiateEntities()
     * @param array $recordDef This record definition.
     * 
     * @return void
     */
    protected static function relateEntities(array &$entities, array $recordDef)
    {
        foreach ($recordDef['entities'] as $entName => $entDefinitions) {
            if (null === $entDefinitions['relations']) {
                continue;
            }
            foreach ($entDefinitions['relations'] as $relNAme => $entRelation) {
                $invokeAddMe = 'set' 
                             . ((isset($entRelation['foreignRelationAlias'])
                                && null !== $entRelation['foreignRelationAlias']) 
                             ? $entRelation['foreignRelationAlias'] 
                             : $entName);
                if (isset($entRelation['refClass'])
                        && (null !== $entRelation['refClass'])
                ) {
                    /* instantiate a refClass and add a reference to both related
                     * classes. This is a Many to Many relation. Find out the
                     * method name to use when adding the reference using config
                     */
                    $refClass = $entRelation['refClass'];
                    $refEntity = new $refClass();
                    $refEntity->$invokeAddMe($entities[$entName]);
                    $foreignRelatedAlias = (isset($entRelation['foreignRelatedAlias'])
                                            && null !== $entRelation['foreignRelatedAlias']) 
                                         ? $entRelation['foreignRelatedAlias'] 
                                         : $entRelation['entity'];
                    $invokeAddForeign = 'set' . $foreignRelatedAlias;
                    $refEntity->$invokeAddForeign($entities[$entRelation['entity']]);
                    self::loadRefEntityDefaults(
                        $entName, $relNAme, $refEntity, $recordDef
                    );
                    $entities = array($entName . '-' . $relNAme => $refEntity) + $entities;
                } else {
                    // Add a reference of $entities[$entName] in related entity.
                    $entities[$entRelation['entity']]->$invokeAddMe($entities[$entName]);
                }
            }
        }
    }
}

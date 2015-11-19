<?php

namespace Transform\EntityPopulator;

use Transform\EntityPopulator\Helper\PhpConfigsHelper;    
use Transform\EntityPopulator\Helper\ValidationHelper;    
use Transform\EntityPopulator\Helper\EntityFactoryHelper;    
use Transform\EntityPopulator\Helper\EntityRelationsHelper;    
use Transform\EntityPopulator\Helper\EntityPersistenceHelper;    
use Transform\EntityPopulator\Helper\RecordSanitizerHelper;    
use In\Parsers\SheetRecordParserAbstract;
use In\Parsers\RecordDefinition\VisitorRecord;

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
    
    /**
     * // Not used 
     * The current Doctrine_Manager connection object.
     * @var Doctrine_Connection
     */
    public static $conn;

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
        PhpConfigsHelper::handleExecutionTime();
        ValidationHelper::loadErrorConfig($onErrorBehavior, $onErrorDisplayExceptions);

        try {
            self::traverseSheet($recordParser);
        } catch (sfFileException $e) {
            if (true == ValidationHelper::getOnErrorDisplayExceptions()) {
                throw $e;
            }
        }
        PhpConfigsHelper::handleExecutionTime(true);
        return ValidationHelper::getErrors();
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
     * Iterates through the parser generating Entities for each record and errors
     * report.
     * 
     * @param SheetRecordParserAbstract $recordParser
     * @throws \Transform\EntityPopulator\MethodNotImplementedException
     */
    private static function traverseSheet(SheetRecordParserAbstract $recordParser) {
        $recordDef = $recordParser->getSheetRecordDefinition();
        //self::$conn->transaction->setIsolation('READ UNCOMMITTED');
        
        
        foreach ($recordParser as $sheetRecord) {
            /* @var $sheetRecord VisitorRecord */
            // We are not validating input. if we did, sfForms are missing.
//                 $validatedVals = ValidationHelper::validateSheetRecord($sheetRecord);
            // if (null === $validatedVals) {
            //     continue;
            // }
            self::sanitizeSheetRecord($sheetRecord);
            try {
                $entities = EntityFactoryHelper::getRecordEntitiesInstances($sheetRecord);
//                    EntityRelationsHelper::relateEntities($entities, $recordDef);
                EntityPersistenceHelper::saveEntities($entities, $recordParser);
            } catch (MethodNotImplementedException $e) {
                // BOOM! prevent next catch to hide this.
                throw $e;
            } catch (Exception $e) {
                ValidationHelper::handlePopulationError($e, $validatedVals, $entities);
            }
        }
    }

    private static function sanitizeSheetRecord(VisitorRecord $sheetRecord) {
//        $oneArrayPerEntityOnRecord = RecordSanitizerHelper::valueArrayChunkForEntitiesPerRecord(
//                $sheetRecord, $recordDef
//            );
//        viejo, 
        
        RecordSanitizerHelper::lookUpForRecordFieldsDefault($sheetRecord);
        RecordSanitizerHelper::applyRecordFieldsTransformation($sheetRecord);
        /**
         * TODO: los campos de los fields ya estan sanitizados, pero no mergidos 
         * si hay composite Entity column con glue, 
         * esa parte deberia venir despues. todo esta en VisitorRecord
         */
//        RecordSanitizerHelper::loadRecordDefinitionDefaultValuesPerEntityArrayChunk(
//                $oneArrayPerEntityOnRecord, $recordDef
//            );
//        return $oneArrayPerEntityOnRecord;
    }
}

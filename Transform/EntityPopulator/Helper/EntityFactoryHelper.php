<?php

namespace Transform\EntityPopulator\Helper;

use Config\Config;
use In\Parsers\RecordDefinition\VisitorRecord;

/**
 * Uses Abstract Factory pattern to instantiate the proper factory, according to
 * the output engine. 
 *
 * @author juan.fernandez
 */
class EntityFactoryHelper {
        
    /**
     * Instantiates the entities involved in this sheet reccord according to the 
     * definition provided in config, and present.
     * 
     * @param array $entVals   The splited array, wich keys are the related 
     * entities' names. {@see self::valueArrayChunkForEntitiesPerRecord()}
     * @param array $recordDef The record Definition from config.
     * 
     * @return array With the entities loaded with the values extracted from the
     * sheet record being parsed.
     */
    public static function getRecordEntitiesInstances(VisitorRecord $visitorRecord) 
    {
        $presistenceEngine = $visitorRecord->getPersistenceEngine();
        return Config::getEntityFactory($presistenceEngine)
                ->createEntities($visitorRecord);
    }
}

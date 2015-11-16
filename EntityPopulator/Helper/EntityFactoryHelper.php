<?php

namespace EntityPopulator\Helper;

/**
 * Description of ValidationHelper
 *
 * @author juan.fernandez
 */
class EntityFactoryHelper {
        
    /**
     * Instantiates the entities involved in this sheet reccord according to the 
     * definition provided in config, and present in <i>$recordDef</i>, and 
     * populates them with the values present in <i>$entVals</i>.
     * 
     * @param array $entVals   The splited array, wich keys are the related 
     * entities' names. {@see self::valueArrayChunkForEntitiesPerRecord()}
     * @param array $recordDef The record Definition from config.
     * 
     * @return array With the entities loaded with the values extracted from the
     * sheet record being parsed.
     */
    public static function getRecordEntitiesInstances(array $entVals, array $recordDef) 
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
    private static function instantiateEntities(array $recordDef)
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


}

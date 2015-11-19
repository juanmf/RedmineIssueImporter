<?php

namespace Transform\EntityPopulator\Helper;

/**
 * Description of PhpConfigsHelper
 *
 * @author juan.fernandez
 */
class EntityRelationsHelper {

    /**
     * Once all the data in the current Record is assigned to the entities. This
     * method tries to relate those entities by finding the entities's methods
     * responsible for this, and adding each entity to its related ones.
     * 
     * @param array &$entities The array with sfDoctrineRecord instances, one 
     * for each $recordDef['entities'] @see EntityLoaderHelper::instantiateEntities()
     * @param array $recordDef This record definition.
     * 
     * @return void
     */
    public static function relateEntities(array &$entities, array $recordDef)
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
    
    /**
     * When ever we find a refClass, it may introduce trouble if it has more fields
     * than just the two related foreign keys. For such situations valid default 
     * values should be provided.
     * 
     * A refClass is a middle table in a ManyToMany relationship
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
                $refEntity->set($colName, RecordSanitizerHelper::getDefault($defVal));
            }
        }
    }
}

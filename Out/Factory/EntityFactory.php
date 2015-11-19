<?php

namespace Out\Factory;

use In\Parsers\RecordDefinition\VisitorRecord;
use Transform\EntityPopulator\Entities\Entity;

/**
 * Abstract Factory pattern for Entity instantiation.
 * 
 * @author juan.fernandez
 */
abstract class EntityFactory {

    /**
     * Creates an entity that wraps the concrete entity and implements activeRecord 
     * pattern, so it can handle persistence logic as a proxy for the  underlying 
     * persistence engine. 
     * 
     * @param VisitorRecord $visitroRecord 
     * 
     * @return Entity The AcvtiveRecord wrapper for the concrete entity that is 
     * to be persisted in the underlying persistence engine.
     */
    public function createEntities(VisitorRecord $visitorRecord) {
        $entities = array();
        foreach ($visitorRecord->getRecordDefinition()->getEntities() as $entityDef) {
            // TODO: more than 1 instance of the same entity, mapped by this
            // record, this is not yet supported, nor distinguishable.
            $entFields = $visitorRecord->getEntityFields($entityDef->getName());
            $entities[$entityDef->getName()] = $this->instantiateEntity($entFields);
        }
        return $entities;
    }
    
    /**
     * 
     * @param VisitorField[] $entFields The fields in Record for a given Entity 
     */
    public abstract function instantiateEntity(array $entFields);
}

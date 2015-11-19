<?php

namespace Out\Factory;

use In\Parsers\RecordDefinition\VisitorRecord;
use In\Parsers\RecordDefinition\VisitorField;

/**
 * Description of RedmineEntityFactory
 *
 * @author juan.fernandez
 */
class DoctrineEntityFactory extends EntityFactory {
    /**
     * 
     * @param VisitorField[] $entFields The fields in Record for a given Entity 
     */
    public function instantiateEntity(array $entFields) {
        $fqcn = $entFields[0]->getModel()->modelName;
        // modelName should have FQCN of the Doctrine2 entity.
        $entity = new $fqcn();
        $entity->fromArray(VisitorRecord::processFieldsDataForEntityColumns($entFields));
        // TODO: NOT TESTED
        return $entity;
    }

}

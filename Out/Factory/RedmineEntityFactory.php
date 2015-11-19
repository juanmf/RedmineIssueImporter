<?php

namespace Out\Factory;

use In\Parsers\RecordDefinition\VisitorRecord;
use In\Parsers\RecordDefinition\VisitorField;

/**
 * Description of RedmineEntityFactory
 *
 * @author juan.fernandez
 */
class RedmineEntityFactory extends EntityFactory {

    /**
     * 
     * @param VisitorField[] $entFields The fields in Record for a given Entity 
     */
    public function instantiateEntity(array $entFields) {
        
        $fqcn = 'Out\\Entities\\Redmine\\' . current($entFields)->getModel()->modelName;
        $entity = new $fqcn();
        /* @var $entity \Out\Entities\Redmine\RedmineEntity */
        $entity->fromArray(VisitorRecord::processFieldsDataForEntityColumns($entFields));
        return $entity;
    }
}

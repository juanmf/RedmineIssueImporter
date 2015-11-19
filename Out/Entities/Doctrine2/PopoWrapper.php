<?php
namespace Out\Entities\Doctrine2;

use Out\Entities\Entity;

/**
 * Description of PopoWrapper
 *
 * @author juan.fernandez
 */
class PopoWrapper extends Entity {
    
    private $entityManager;
    
    private $popoEntity;
    
    function __construct($entityManager, $popoEntity) {
        $this->entityManager = $entityManager;
        $this->popoEntity = $popoEntity;
    }

    public function save() {
        
    }

    public function free() {
        
    }

    public function fromArray($dataArray) {
        
    }

    public function toArray() {
        
    }

    public function offsetExists($offset) {
        
    }

    public function offsetGet($offset) {
        
    }

    public function offsetSet($offset, $value) {
        
    }

    public function offsetUnset($offset) {
        
    }
}

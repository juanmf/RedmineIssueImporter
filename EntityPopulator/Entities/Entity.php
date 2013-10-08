<?php

namespace EntityPopulator\Entities;

/**
 * As EntityPopulatr thinks these are Doctrine1.X's DoctrineRecords, and redmine's api 
 * uses only arrays I wrap issue values in an ArrayAccess instance that also implements
 * DoctrineRecord's fromArray() method.
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
abstract class Entity implements \ArrayAccess
{
    const API = null;

    private $values = array();
    
    // <editor-fold defaultstate="collapsed" desc="ArrayAccess">
    public function offsetExists($offset) {
        return isset($this->values[$offset]);
    }

    public function offsetGet($offset) {
        return $this->values[$offset];
    }

    public function offsetSet($offset, $value) {
        $this->values[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset ($this->values[$offset]);
    } 
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="DoctrineRecord">
    public function fromArray($dataArray) {
        $this->values = $dataArray;
    }
    
    public function toArray() {
        return $this->values ;
    }
    
    
    public function free()
    {
        unset ($this->values);
    }

    /**
     * uses the propper Redmine API object to create an instance of this entity 
     * in redmine.
     * 
     * @return void
     */
    public abstract function save();
    // </editor-fold>
    
    /**
     * retrieve from config the id of a dustom field.
     * 
     * @param strign $fieldName The custom fieldName e.g. 'sprint'
     * 
     * @return int
     */
    public function getCustomFieldId($fieldName)
    {
        $customFields = $this->getCustomFieldsConfig();
        return $customFields[$fieldName]['id'];
    }
    
    /**
     * retrieve this entity's custom fields config.
     * 
     * @return array This entity's custom fields config
     */
    public abstract function getCustomFieldsConfig();
    
    /**
     * Watches config for this entity's custom fields, if any key value pair matches 
     * a custom field name, it is extracted from the values and added to a new value with key
     * 'custom_fields'
     * 
     * @param \EntityPopulator\Entities\Entity $entity The Redmine entity being created 
     * treated.
     * 
     * @return void
     */
    public static function adaptCustomFields(Entity $entity)
    {
        $customFields = $entity->getCustomFieldsConfig();
        $fields = $entity->toArray();
        $usedCustomFields = array_intersect_key($fields, $customFields);
        if (0 < count($usedCustomFields)) {
            $apiReadyCustomFields = self::createCustomFields($entity, $usedCustomFields);
            $nativeFields = array_diff_key($fields, $customFields);
            $nativeFields += $apiReadyCustomFields;
            $entity->fromArray($nativeFields);
        }
    }
    
    /**
     * Given that we found custom fields, create the custom_field structure.
     * 
     * @param \EntityPopulator\Entities\Entity $entity 
     * @param type $usedCustomFields
     * 
     * @return type
     */
    private static function createCustomFields(Entity $entity, $usedCustomFields)
    {
        $customFields = array('custom_fields' => array());
        foreach ($usedCustomFields as $fName => $fValue) {
            $fId = $entity->getCustomFieldId($fName);
            $customFields['custom_fields'][] = array('id' => $fId, 'value' => $fValue);
        }
        return $customFields;
    }
    
    /**
     * Check if $error is set in API Response, and throws an exceptino if so.
     * 
     * @param \SimpleXMLElement $apiReturn the API Response
     * 
     * @return void
     * @throws \EntityPopulator\RedmineApiException If $error is present in API response
     */
    protected function checkErrors($apiReturn, $noResponseIsOK = false)
    {
        if (isset($apiReturn->error) || ! ($apiReturn instanceof \SimpleXMLElement)) {
            $ex = new \EntityPopulator\RedmineApiException('The entity could not be persisted');
            if (is_object($apiReturn)) {
                $ex->return = $apiReturn->error ;
            } else if (! $noResponseIsOK) {
                $ex->return = 'No response from server, not even an error code, check config data, project name, etc.';
            } else {
                // in Update can return empty string.
                return;
            }
            throw $ex;
        }
    }
}

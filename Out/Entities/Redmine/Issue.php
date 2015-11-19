<?php

namespace Out\Entities\Redmine;

use \Config\Config;

/**
 * As EntityPopulatr thinks these are Doctrine1.X's DoctrineRecords, and redmine's api 
 * uses only arrays I wrap issue values in an ArrayAccess instance that also implements
 * DoctrineRecord's fromArray() method.
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class Issue extends RedmineEntity
{
    /**
     * The Redmin API this entity encapsulates {@see \Redmine\Client::api()}
     */
    const API = 'issue';
    const CUSTOM_FIELDS_CONFIG_KEY = 'issue';
    
    public static $createdIds = array();
    
    /**
     * Holds the array of customFields ids. {@see Config/config.yml}
     * 
     * @var array
     */
    private static $customFieldsConfig = array();
    
    /**
     * Initializes, only the 1st instance, the static $customFields for this entity.
     * 
     * @return void
     */
    public function __construct()
    {
        if (! empty(self::$customFieldsConfig)) {
            return;
        }
        $config = Config::get('custom_fields');
        self::$customFieldsConfig = $config[self::CUSTOM_FIELDS_CONFIG_KEY];
    }
    
    /**
     * Send the create rest reqest to redmine host after adapting customFields to the 
     * expected format
     * 
     * @return void
     */
    public function save()
    {
        parent::adaptCustomFields($this);
        if (isset($this['id'])) {
            return $this->update();
        }
        $importService = \ImportService::getInstance();
        $api = $importService->getClient()->api(self::API);
        /* @var $api \Redmine\Api\Issue */
        $return = $api->create($this->toArray());
        $this->checkErrors($return);
        $this->addIdToCreatedIds($return, $importService);
    }

    /**
     * stores last created Id in self::$createdIds. these id are used for issue deletion
     * while testing our imports until we configure it right.
     * 
     * @param type $param
     * 
     * @return void
     */
    private function addIdToCreatedIds($return, $importService) 
    {
        $currentProject = $importService->getCurrentProject();
        self::$createdIds[$currentProject]['issues'][] = array('id' => (string) $return->id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCustomFieldsConfig()
    {
        return self::$customFieldsConfig;
    }

    public function update()
    {
        $this['notes'] = 'Actualización Automatica, desde planilla.';
        $importService = \ImportService::getInstance();
        $api = $importService->getClient()->api(self::API);
        /* @var $api \Redmine\Api\Issue */
        $return = $api->update($this['id'], $this->toArray());
        $this->checkErrors($return, true);
    }
}

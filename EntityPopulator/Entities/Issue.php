<?php

namespace EntityPopulator\Entities;

use \Config\Config;

/**
 * As EntityPopulatr thinks these are Doctrine1.X's DoctrineRecords, and redmine's api 
 * uses only arrays I wrap issue values in an ArrayAccess instance that also implements
 * DoctrineRecord's fromArray() method.
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class Issue extends Entity
{
    /**
     * The Redmin API this entity encapsulates {@see \Redmine\Client::api()}
     */
    const API = 'issue';
    const CUSTOM_FIELDS_CONFIG_KEY = 'issue';
    
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
        $api = \ImportService::getInstance()->getClient()->api(self::API);
        /* @var $api \Redmine\Api\Issue */
        parent::adaptCustomFields($this);
        $return = $api->create($this->toArray());
        $this->checkErrors($return);
        
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomFieldsConfig()
    {
        return self::$customFieldsConfig;
    }
}

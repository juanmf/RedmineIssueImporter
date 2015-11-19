<?php

namespace Transform\EntityPopulator\Helper;

use In\Parsers\SheetRecordParserAbstract;
use Config\Config;

/**
 * Description of PhpConfigsHelper
 *
 * @author juan.fernandez
 */
class EntityPersistenceHelper {
    
    protected static $_conflicts = null;
    
    /**
     * Persists entities values.
     * 
     * @param Entities\Entity[]         $entities     The array of Entities, encapsulating
     * redmine APi calls, to save.
     * @param SheetRecordParserAbstract $recordParser The record parser being 
     * traversed.
     * 
     * @return void
     */
    public static function saveEntities(
        $entities, SheetRecordParserAbstract $recordParser
    ) {
        $save = (bool) Config::get('save_records', true);
        foreach ($entities as $entName => $entity) {
            if ($save) {
                try {   
                    $entity->save();
                } catch (RedmineApiException $e) {
                    $eventName = 'redmine.api_error';
                    $parameters = array(
                        'record_parser'  => $recordParser, 
                        'current_entity' => $entity, 
                        'entity'         => reset($entities),
                        'type'           => $e->return,
                        'err_code'       => $e->getCode(),
                    );
                    self::registerConflictingRow($parameters);
                    self::notify($eventName, $parameters);
                }
            }
            /* @var $entity Doctrine_Record */
            $entity->free(true);
            unset($entities[$entName]);
        }
    }
    
    /**
     * Records entities which cause troubles in {@link self::$_conflicts} array.
     * 
     * @param array $parameters Same Array of parameters that is sent to the event
     * handlers with keys:<pre>
     *              'record_parser'
     *              'current_entity'
     *              'entity'
     *              'type'             
     *              'duplicate_index'|'foreign_column'
     * </pre>
     * 
     * @return void
     */
    private static function registerConflictingRow(array $parameters) 
    {
        $entity = $parameters['entity'];

        self::$_conflicts[get_class($entity)][] = array(
            'entity' => $entity->toArray(),
            'type'   => $parameters['type'],
        );
    }
    
    /**
     * Gets all the conflicts that fired an exception.
     * 
     * @see self::registerConflictingRow()
     * @return array With the conflicts. 
     * array('entity' => $entity->toArray(), 'type' => Doctrine_core::ERR_*)
     */
    public static function getConflicts()
    {
        return self::$_conflicts;
    }

    /**
     * // TODO: this is interesting but not implemented yet.
     * Fires an event notification to the system.
     * 
     * @param string $eventName  The event name to wich handlers connect to. 
     * @param array  $parameters The event parameters, to initialize the sfEvent
     * Object used to notify handlers.
     * 
     * @return void
     */
    private static function notify($eventName, $parameters) {
        // TODO: define event to notify.
        $dispatcher = Config::getContainer()->get('dispatcher');
        /* @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $event = new \Symfony\Component\EventDispatcher\Event();
        $dispatcher->dispatch($eventName, $event);
    }
}

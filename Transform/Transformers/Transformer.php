<?php

namespace Transform\Transformers;

/**
 * Static callbacks holder for transformin inputa data in sheets to Redmine Fields 
 * comatible values
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class Transformer
{
    /**
     * Default user defined mapping file name, where self::$foundMappings serializes to.
     */
    const SERIALIZED_MAPPINGS = '/mappings.serialized';
    
    /**
     * User defined mappings.
     * @see self::unSerializeMappings()
     */
    public static $foundMappings = array();
    
    /**
     * Serialize user defined mappings.
     * 
     * @see self::unSerializeMappings()
     */
    public static function serializeMappings()
    {
        $fName = __DIR__ . self::SERIALIZED_MAPPINGS;
        file_put_contents($fName, serialize(self::$foundMappings));
    }
    
    /**
     * Sheet Fields that must match a list of allowed values (e.g. custom fields of type List)
     * are added to an array of mappings at runtime and serialized for future executions.
     * Available optins should be added in {@link Transformers\StaticMappings} and used by
     * transformer methods matching field names which are called as per {@link \Config\config.yml}
     * transformer in field definition:
     * <pre>
     * sheets: 
     *   demandas: #sheetName
     *     records:
     *       demanda: #recordType Name
     *         fields:
     *           beneficiario: #fieldName
     *             #transformer Method some are already implemented, for lists use existing examples to create your own.
     *             transform: [Transformers\Transformer, asunto] 
     *           
     */
    public static function unSerializeMappings($savedMappingsFileName = null)
    {
        $savedMappingsFileName = $savedMappingsFileName ? : __DIR__ . self::SERIALIZED_MAPPINGS;
        if (! file_exists($savedMappingsFileName)) {
            return;
        }
        $serialized = file_get_contents($savedMappingsFileName);
        self::$foundMappings = unserialize($serialized);
    }
    
    /**
     * Tryes to find $fieldValue in corresponding {@link StaticMappings} array of 
     * values (as they are configured in Redmine Lists, same string values).
     * 
     * If it doesn't match, prompts the user to select the right Redmine value for this
     * sheet $fieldValue, and stores this asociation so as to don't ask again.
     * 
     * @param string $fieldValue The value found in the sheet Record.
     * 
     * @return string The Redmine List corresponding value, as determined in {@link StaticMappings}
     */
    public static function localidad($fieldValue)
    {
        self::serializeMappings();
        $fieldValue = trim($fieldValue);
        if ($retVal = self::findMappedValue($fieldValue, __METHOD__, 'localidades')) {
            return $retVal;
        }
        return self::askForHelp($fieldValue, __METHOD__, 'localidades');
    }

    /**
     * Tryes to find $fieldValue in corresponding {@link StaticMappings} array of 
     * values (as they are configured in Redmine Lists, same string values).
     * 
     * If it doesn't match, prompts the user to select the right Redmine value for this
     * sheet $fieldValue, and stores this asociation so as to don't ask again.
     * 
     * @param string $fieldValue The value found in the sheet Record.
     * 
     * @return string The Redmine List corresponding value, as determined in {@link StaticMappings}
     */
    public static function intermediario($fieldValue)
    {
        $fieldValue = trim($fieldValue);
        if ($retVal = self::findMappedValue($fieldValue, __METHOD__, 'intermediario')) {
            return $retVal;
        }
        return self::askForHelp($fieldValue, __METHOD__, 'intermediario');
    }
    
    /**
     * Tryes to find $fieldValue in corresponding {@link StaticMappings} array of 
     * values (as they are configured in Redmine Lists, same string values).
     * 
     * If it doesn't match, prompts the user to select the right Redmine value for this
     * sheet $fieldValue, and stores this asociation so as to don't ask again.
     * 
     * @param string $fieldValue The value found in the sheet Record.
     * 
     * @return string The Redmine List corresponding value, as determined in {@link StaticMappings}
     */
    public static function estado($fieldValue)
    {
        $fieldValue = trim($fieldValue);
        if ($retVal = self::findMappedValueAssoc($fieldValue, __METHOD__, 'estado')) {
            return $retVal;
        }
        return self::askForHelp($fieldValue, __METHOD__, 'estado');
    }
    
    /**
     * Tryes to find $fieldValue in corresponding {@link StaticMappings} array of 
     * values (as they are configured in Redmine Lists, same string values).
     * 
     * If it doesn't match, prompts the user to select the right Redmine value for this
     * sheet $fieldValue, and stores this asociation so as to don't ask again.
     * 
     * @param string $fieldValue The value found in the sheet Record.
     * 
     * @return string The Redmine List corresponding value, as determined in {@link StaticMappings}
     */
    public static function fecha($fieldValue)
    {
        $fieldValue = trim($fieldValue);
        try {
            $date = \DateTime::createFromFormat('m/d/Y', $fieldValue);
            if (is_object($date)) {
                return $date->format('Y-m-d');
            }
        } catch (Exception $exc) {
        }
        if ($retVal = self::findMappedValue($fieldValue, __METHOD__, 'fecha')) {
            return $retVal;
        }
        return self::askForHelpLiteral($fieldValue, __METHOD__, 'fecha');
    } 
    
    /**
     * Subjects can't be longer than 255 characters. Sych values cause issue to be
     * rejected.
     * 
     * @param string $fieldValue The value found in the sheet Record.
     * 
     * @return string truncated subject value.
     */
    public static function asunto($fieldValue)
    {   
        $fieldValue = trim($fieldValue);
        return substr($fieldValue, 0, 100);
    }
    
    /**
     * Generic mapping finder used from field transformer methods.
     * 
     * @param string $fieldValue The value found in the sheet Record.
     * @param string $invoker    The method that invoked me.
     * @param string $mapping    The array variable name found in {@link StaticMappings}
     * 
     * @return boolean|string the mapping if found, false otherwise.
     */
    private static function findMappedValue($fieldValue, $invoker, $mapping)
    {
        $map = isset(StaticMappings::$$mapping) ? StaticMappings::$$mapping : array();
        if (in_array($fieldValue, $map)) {
            return $fieldValue;
        } else if (isset(self::$foundMappings[$invoker][$fieldValue])) {
            return self::$foundMappings[$invoker][$fieldValue];
        }
        return false;
    }

    /**
     * Same as {@link findMappedValue()} but with associative arrays
     * 
     * @param string $fieldValue The value found in the sheet Record.
     * @param string $invoker    The method that invoked me.
     * @param string $mapping    The array variable name found in {@link StaticMappings}
     * 
     * @return boolean|string the mapping if found, false otherwise.
     */
    private static function findMappedValueAssoc($fieldValue, $invoker, $mapping)
    {
        $map = isset(StaticMappings::$$mapping) ? StaticMappings::$$mapping : array();
        if (isset($map[$fieldValue])) {
            return $map[$fieldValue];
        } else if (isset(self::$foundMappings[$invoker][$fieldValue])) {
            return self::$foundMappings[$invoker][$fieldValue];
        }
        return false;
    }

    /**
     * When no mapping is found, ask the user for the right value.
     * 
     * @param string $fieldValue The value found in the sheet Record.
     * @param string $invoker    The method that invoked me.
     * @param string $mapping    The array variable name found in {@link StaticMappings}
     * 
     * @return string the value in {@link StaticMappings::$$mapping} array related to the 
     * input key.
     */
    private static function askForHelp($fieldValue, $invoker, $mapping)
    {
        $map = isset(StaticMappings::$$mapping) ? StaticMappings::$$mapping : array();
        echo sprintf('Please select the key of right value for field [%s] with value: [%s]', $mapping, $fieldValue) . PHP_EOL;        
        print_r($map);
        $key = readline('Selected Key: ');
        self::$foundMappings[$invoker][$fieldValue] = $map[$key];
        return $map[$key];
    }
    
    /**
     * When no mapping is found, ask the user for the right value, but instead of
     * using it as a map key, like {@link askForHelp} returns the string given by the user
     * 
     * @param string $fieldValue The value found in the sheet Record.
     * @param string $invoker    The method that invoked me.
     * @param string $mapping    The array variable name found in {@link StaticMappings}
     * 
     * @return string the string given by the user
     */
    private static function askForHelpLiteral($fieldValue, $invoker, $mapping)
    {
        $map = isset(StaticMappings::$$mapping) ? StaticMappings::$$mapping : array();
        echo sprintf('Please select the key of right value for field [%s] with value: [%s]', $mapping, $fieldValue) . PHP_EOL;        
        print_r($map);
        $value = readline('Selected Key: ');
        self::$foundMappings[$invoker][$fieldValue] = $value;
        return $value;
    }
}

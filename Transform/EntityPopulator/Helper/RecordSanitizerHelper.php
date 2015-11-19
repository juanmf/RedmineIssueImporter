<?php

namespace Transform\EntityPopulator\Helper;

use In\Parsers\RecordDefinition\VisitorRecord;
use In\Parsers\RecordDefinition\VisitorField;

/**
 * Description of PhpConfigsHelper
 *
 * @author juan.fernandez
 */
class RecordSanitizerHelper {
   
    public static function lookUpForRecordFieldsDefault(VisitorRecord $currentRecord) {
        foreach ($currentRecord as $field) {
            /* @var $field VisitorField */
            if (empty($field->getCurrentValue())) {
                $field->setCurrentValue(self::getDefault($field->getDefault()));
            }
        }
    }
    
    public static function applyRecordFieldsTransformation(VisitorRecord $currentRecord) {
        foreach ($currentRecord as $field) {
            /* @var $field VisitorField */
            if (! empty($field->getTransform())) {
                $field->setCurrentValue(self::getTransformation(
                    $field->getTransform(), $field->getCurrentValue()
                ));
            }
        }
    }
    
    /**
     * Finds out the right way to interpret $defaultSpecs and gets a default 
     * value for some field being processed.
     * 
     * @param Callable|string $defaultSpecs Either a literal value from config
     * or a function that should return a value used as default for some field.
     * 
     * @see self::loadEntityDefaultValues()
     * @return string The value to be used as default in the field that is being 
     * populated. 
     */
    public static function getDefault($defaultSpecs)
    {
        if (is_callable($defaultSpecs)) {
            $defaultSpecs = call_user_func($defaultSpecs);
        }
        return $defaultSpecs;
    }

    /**
     * Finds out the right way to interpret $transformSpecs and gets a transformed 
     * value for some field being processed.
     * 
     * @param Callable $transformSpecs The callable that should be called with the 
     * original value that should be processed.
     * @param string   $origVal        The value as extracted from the curren Sheet 
     * record field being parced.
     * 
     * @return string The transformed value, or the original value if $transformSpecs
     * is not callable.
     */
    protected static function getTransformation($transformSpecs, $origVal)
    {
        $transformed = $origVal;
        if (is_callable($transformSpecs)) {
            $transformed = call_user_func($transformSpecs, $origVal);
        }
        return $transformed;
    }
}

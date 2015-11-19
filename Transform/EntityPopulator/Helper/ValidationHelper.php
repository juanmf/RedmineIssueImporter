<?php

namespace Transform\EntityPopulator\Helper;

use Config\Config;

/**
 * Description of ValidationHelper
 *
 * @author juan.fernandez
 */
class ValidationHelper {
        
    /**
     * Array of error messages
     * 
     * @var array
     */
    protected static $_errors = array();
    
    

    /**
     *  Behavior to assume for error handling. Posible values ar:
     *  continue: All errors are loaded in a return array
     *  stop: Throw Exception on first error. But save as much as possible.
     *  rollback: Revet changes, do nothing. and throw Exception.
     *  @var string
     */
    protected static $_onErrorBehavior = null;
    
    /**
     * Define if displays exceptions when an error ocurred.
     * @var bool
     */
    private static $_onErrorDisplayExceptions = null;
    
    /**
     * Handles error as specified by {@link self::$_onErrorBehavior}
     * 
     * @param Exception $e               The unhandled exception cought by the 
     * caller method. 
     * @param array     $validatedValues The values from current record after being 
     * validated by the sfImportSheetRecordForm object, if used. If not used, these 
     * values are exactly the same as they were input in the sheet. Either case
     * before data is as it was before setting default values and transformations.
     * @param array     $entities        The entities loaded with the values 
     * extracted from the sheet record being parsed. 
     * 
     * @return void
     */
    public static function handlePopulationError(
        Exception $e, array $validatedValues, array $entities
    ) {
        $message = implode(PHP_EOL . " # ", $validatedValues)
                 . " # Errors: " . $e->getMessage();
        self::$_errors[] = $message;
        self::handleError($message);
    }
    
    /**
     * Takes care of validation errors in input data. Also adds the error message 
     * to the <i>self::$_errors</i> array.
     * 
     * @param sfImportSheetRecordForm $sheetForm The Form object used to validate 
     * the input data.
     * @param array                   $values    The values from the current 
     * record being parsed. As a key value array. {@see self::validateSheetRecord()}
     * 
     * @return void
     */
    protected static function handleValidationError(
        sfImportSheetRecordForm $sheetForm, $values
    ) {
        $message = self::writeError($sheetForm, $values);
        self::$_errors[] = $message;
        self::handleError($message);
    }

    /**
     * Handles error as specified by {@link self::$_onErrorBehavior}
     * 
     * @param type $message The error message from the exception thrown.
     * 
     * @see self::handlePopulationError()
     * @return void
     */
    private static function handleError($message)
    {
        switch (self::$_onErrorBehavior) {
            case 'continue':
                break;
            case 'stop':
                self::$conn->commit();
                throw new sfFileException($message);
                break;
            case 'rollback':
                self::$conn->rollback();
                throw new sfFileException($message);
                break;
            default:
                throw new Exception(
                    'La opcion: ' . self::$_onErrorBehavior
                    . ' no es una opcion válida para la clave on_error_behavior'
                );
                break;
        }
    }
    
    /**
     * Makes a human readable error report for each failing field in current 
     * record being parsed. Extracts error info from sfImportSheetRecordForm's 
     * ErrorSchema.
     * 
     * @param sfImportSheetRecordForm $sheetForm The Form object used to validate 
     * the input data.
     * @param array                   $values    The values from the current 
     * record being parsed. As a key value array. {@see self::validateSheetRecord()}
     * 
     * @return string 
     */
    private static function writeError(
        sfImportSheetRecordForm $sheetForm, array $values
    ) {
        $error = array();
        $message = 'No se ha utilizado validación de datos.';
        if (null !== $sheetForm) {
            $validationErrors = $sheetForm->getErrorSchema()->getErrors();
            foreach ($validationErrors as $eName => $ve) {
                $error[] = sprintf(
                    'El campo %s no pasó la validación. Error: %s', $eName, $ve
                );
            }
            $message = implode(PHP_EOL . " # ", $error);
        }
        $row = implode(PHP_EOL . " # ", $values);
        return $row . " # Errors: " . $message;
    }
    
    /**
     * Configures error handling behavior
     * 
     * @param string $onErrorBehavior          Controls how to react to errors. 
     * Optional, defaults to null. {@see self::$_onErrorBehavior}
     * @param bool   $onErrorDisplayExceptions Define if displays exceptions when 
     * an error ocurred.
     * 
     * @return void
     */
    public static function loadErrorConfig(
        $onErrorBehavior = null, $onErrorDisplayExceptions = null
    ) {
        $onError = Config::get('on_error');
        if (null === self::$_onErrorBehavior) {
            self::$_onErrorBehavior = (null !== $onErrorBehavior) 
                                    ? $onErrorBehavior 
                                    : $onError['behavior'];
        }
        if (null === self::$_onErrorBehavior) {
            self::$_onErrorDisplayExceptions = (null !== $onErrorDisplayExceptions) 
                                             ? $onErrorDisplayExceptions 
                                             : $onError['display_exceptions'];
        }
    }
    
    public static function getOnErrorDisplayExceptions() {
        return self::$_onErrorDisplayExceptions;
    }
    
    static function getErrors() {
        return self::$_errors;
    }
        
    /**
     * // TODO: WE ARE NOT USING SF1.4 FORMS HERE, COME OUT WITH SOME VALIDATION STRATEGY
     * 
     * Handles Sheet Record data validation by using a sfForm dynamicaly 
     * generated according to RecordDefinition config.
     * 
     * @param array                   $sheetRecord The Current Record being parsed.
     * @param sfImportSheetRecordForm $sheetForm   The Form object used to validate 
     * the input data.
     * 
     * @return array|null With validated values. Null on Error, if this kind of 
     * error didn't throw an error.
     */
    public static function validateSheetRecord(
        array $sheetRecord, sfImportSheetRecordForm $sheetForm = null
    ) {
        throw new \LogicException('Not implemented yet, who called me?');
        
        //        $values = $this->normalizeValues($sheetRecord);
        //        if (null === $sheetForm) {
        //            return $values;
        //        }
        //        $sheetForm->bind($values);
        //        if (! $sheetForm->isValid()) {
        //            self::handleValidationError($sheetForm, $values);
        //            return null;
        //        }
        //        return $sheetForm->getValues();
    }
    
}

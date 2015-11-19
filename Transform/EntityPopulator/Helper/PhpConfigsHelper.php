<?php

namespace Transform\EntityPopulator\Helper;

/**
 * Description of PhpConfigsHelper
 *
 * @author juan.fernandez
 */
class PhpConfigsHelper {

    private static $_maxExecTime = null;

    /**
     * Alters default script execution time. Toggling it between infinite and 
     * original value.
     * 
     * @param bool $restore If true restores the original values of php.ini vars
     * 
     * @return void
     */
    public static function handleExecutionTime($restore = false) 
    {
        if (null === self::$_maxExecTime) {
            self::$_maxExecTime = (int) ini_get('max_execution_time');
            // checkout safe_mode
            set_time_limit(0);
        } elseif ($restore) {
            // FIXME: When recursoin takes place, for foreign constraints, time limit 
            // gets restored, when ever a sub process finish, thats wrong.
            set_time_limit(self::$_maxExecTime);
            self::$_maxExecTime = null;
        }
    }
}

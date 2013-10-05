<?php

namespace Defaults;

/**
 * Place holder for default values callbacks, similar to Transformers, only that
 * default values are invoked if sheet record value comes null/empty and this methods
 * don't receive any value to tarnsform, only returns somthing.
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class Defaults
{
    /**
     * Returns de current date time. when sheet has no value for this field, this 
     * method gets invoked and Isse sart_date is set to now.
     * 
     * @return string Current date
     * @see /Config/config.yml See the default value for field start_date: [Defaults\Defaults, startDate]
     */
    public static function startDate()
    {
        return date('Y-m-d');
    }
}

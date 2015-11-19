<?php

namespace Transform\EntityPopulator;

/**
 * Uses an attribute called $return to hold Redmine API error messages.
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class RedmineApiException extends \Exception 
{
    public $return;
}

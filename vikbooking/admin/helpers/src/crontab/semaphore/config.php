<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Cron runner semaphore interface.
 * 
 * @since 1.7
 */
class VBOCrontabSemaphoreConfig implements VBOCrontabSemaphore
{
    /**
     * @inheritDoc
     */
    public function getLastExecution(VBOCrontabRunner $cron)
    {
        // fetch the last execution time of the provided cron (unix timestamp)
        $lastExecution = VBOFactory::getConfig()->getUint('cron_exec_' . $cron->getID(), 0);
        // create a date time instance starting from the provided UNIX timestamp
        return JFactory::getDate($lastExecution);
    }

    /**
     * @inheritDoc
     */
    public function shouldPass(VBOCrontabRunner $cron)
    {
        // fetch the last execution date time of the cron job
        $lastExecution = $this->getLastExecution($cron);

        // calculate the next execution of the cron job
        $nextExecution = $cron->getNextExecution($lastExecution)->getTimestamp();

        // get the current timestamp
        $now = JFactory::getDate()->getTimestamp();

        // check whether the next execution is still in the future
        if ($nextExecution > $now) {
            // should not execute
            return false;
        }

        // immediately update the execution to prevent duplicate usages
        VBOFactory::getConfig()->set('cron_exec_' . $cron->getID(), $now);
        // should execute right now
        return true;
    }
}

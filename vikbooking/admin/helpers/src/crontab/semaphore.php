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
interface VBOCrontabSemaphore
{
    /**
     * Returns the last execution of the provided cron.
     * 
     * @param   VBOCrontabRunner  $cron  The cron to check.
     * 
     * @return  DateTime  The last execution date time.
     */
    public function getLastExecution(VBOCrontabRunner $cron);

    /**
     * Checks whether the specified cron should be executed.
     * In case the cron should run, the last execution time is
     * immediately updated to prevent a duplicate usage.
     * 
     * @param   VBOCrontabRunner  $cron  The cron to check.
     * 
     * @return  bool  True is the cron should be executed, false otherwise.
     */
    public function shouldPass(VBOCrontabRunner $cron);
}

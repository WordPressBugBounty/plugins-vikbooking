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
 * Cron logger interface.
 * 
 * @since 1.7
 */
interface VBOCrontabLogger
{
    /**
     * Logs a text during the cron job execution.
     * 
     * @param   string  $message  The message to log.
     * @param   string  $status   The action status (eg. info, error, warning and so on).
     * 
     * @return  void
     */
    public function log(string $message, string $status = 'info');
}

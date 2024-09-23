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
 * Cron logger null pointer.
 * 
 * @since 1.7
 */
final class VBOCrontabLoggerNull implements VBOCrontabLogger
{
    /**
     * @inheritDoc
     */
    public function log(string $message, string $status = 'info')
    {
        // do nothing here
    }
}

<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Task schedule implementation for type "prearrival".
 * 
 * @since   1.18.2 (J) - 1.8.2 (WP)
 */
class VBOTaskScheduleTypePrearrival extends VBOTaskScheduleType
{
    /**
     * @inheritDoc
     */
    public function getOrdering()
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getDates()
    {
        return [
            // calculate a single date-time object for the booking check-in date
            new DateTime(date('Y-m-d H:i:s', $this->getBooking()->getStayTimestamps()[0]), new DateTimeZone(date_default_timezone_get())),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getDescription(array $info = [], int $counter = 0)
    {
        switch ($info['task_enum'] ?? '') {
            case 'cleaning':
                // cleaning task title
                return JText::translate('VBO_TM_SCHED_CLEANING_PREARRIVAL');

            case 'maintenance':
                // maintenance task title
                return JText::translate('VBO_TM_SCHED_MAINTENANCE_PREARRIVAL');

            default:
                // default schedule type name
                return JText::translate('VBO_PREARRIVAL');
        }
    }
}

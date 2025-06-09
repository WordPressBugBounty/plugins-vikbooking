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
 * Task schedule implementation for type "weekly".
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
class VBOTaskScheduleTypeWeekly extends VBOTaskScheduleType
{
    /**
     * @inheritDoc
     */
    public function getOrdering()
    {
        return 7;
    }

    /**
     * @inheritDoc
     */
    public function getDates()
    {
        if ($this->getBooking()->getTotalNights() < 8) {
            return [];
        }

        $nights = [];

        // local timezone
        $tz = new DateTimezone(date_default_timezone_get());

        foreach ($this->getBooking()->buildStayPeriodInterval('P7D') as $counter => $dt) {
            if (!$counter) {
                // exclude the check-in date
                continue;
            }

            // push weekly night of stay date-time object
            $nights[] = new DateTime($dt->format('Y-m-d H:i:s'), $tz);
        }

        return $nights;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(array $info = [], int $counter = 0)
    {
        switch ($info['task_enum'] ?? '') {
            case 'cleaning':
                // cleaning task title
                return JText::translate('VBO_TM_SCHED_CLEANING_WEEKLY') . ($counter ? ' #' . ++$counter : '');

            case 'maintenance':
                // maintenance task title
                return JText::translate('VBO_TM_SCHED_MAINTENANCE_WEEKLY') . ($counter ? ' #' . ++$counter : '');

            default:
                // default schedule type name
                return JText::translate('VBO_WEEKLY');
        }
    }
}

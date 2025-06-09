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
 * Task schedule implementation for type "every2".
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
class VBOTaskScheduleTypeEvery2 extends VBOTaskScheduleType
{
    /**
     * @inheritDoc
     */
    public function getOrdering()
    {
        return 2;
    }

    /**
     * @inheritDoc
     */
    public function getDates()
    {
        if ($this->getBooking()->getTotalNights() < 3) {
            return [];
        }

        $nights = [];

        // local timezone
        $tz = new DateTimezone(date_default_timezone_get());

        foreach ($this->getBooking()->buildStayPeriodInterval('P2D') as $counter => $dt) {
            if (!$counter) {
                // exclude the check-in date
                continue;
            }

            // push night of stay date-time object every 2 nights
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
                return JText::translate('VBO_TM_SCHED_CLEANING_DAILY') . ($counter ? ' #' . ++$counter : '');

            case 'maintenance':
                // maintenance task title
                return JText::translate('VBO_TM_SCHED_MAINTENANCE_DAILY') . ($counter ? ' #' . ++$counter : '');

            default:
                // default schedule type name
                return JText::translate('VBO_EVERY_2_DAYS');
        }
    }
}

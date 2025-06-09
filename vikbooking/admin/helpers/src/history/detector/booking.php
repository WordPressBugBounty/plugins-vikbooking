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
 * Item booking changes detector class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOHistoryDetectorBooking extends VBOHistoryDetectoraware
{
    /**
     * Class constructor.
     */
    public function __construct(?string $propertyName = null)
    {
        parent::__construct($propertyName ?: 'id_order');
    }

    /**
     * @inheritDoc
     */
    protected function checkChanges($previousValue, $currentValue)
    {
        // compare the booking IDs as integers
        return (int) $previousValue !== (int) $currentValue;
    }

    /**
     * @inheritDoc
     */
    protected function doDescribe($previousValue, $currentValue)
    {
        if (!$previousValue) {
            // assigned to a booking from empty
            return JText::sprintf('VBO_HISTORY_TRACKER_BOOKING_ADDED', '#' . $currentValue);
        }

        if (!$currentValue) {
            // removed assigned booking
            return JText::sprintf('VBO_HISTORY_TRACKER_BOOKING_REMOVED', '#' . $previousValue);
        }

        // changed from a booking to another
        return JText::sprintf('VBO_HISTORY_TRACKER_BOOKING_CHANGED', '#' . $previousValue, '#' . $currentValue);
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('calendar-check');
    }
}

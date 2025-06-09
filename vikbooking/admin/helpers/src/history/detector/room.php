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
 * Item room changes detector class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOHistoryDetectorRoom extends VBOHistoryDetectoraware
{
    /**
     * Class constructor.
     */
    public function __construct(?string $propertyName = null)
    {
        parent::__construct($propertyName ?: 'id_room');
    }

    /**
     * @inheritDoc
     */
    protected function checkChanges($previousValue, $currentValue)
    {
        // compare the room IDs as integers
        return (int) $previousValue !== (int) $currentValue;
    }

    /**
     * @inheritDoc
     */
    protected function doDescribe($previousValue, $currentValue)
    {
        if ($previousValue) {
            // convert room ID into a readable name
            $previousValue = VikBooking::getRoomInfo((int) $previousValue);
            $previousValue = $previousValue['name'] ?? null;
        }

        if ($currentValue) {
            // convert room ID into a readable name
            $currentValue = VikBooking::getRoomInfo((int) $currentValue);
            $currentValue = $currentValue['name'] ?? null;
        }

        if (!$previousValue) {
            // assigned to a room from empty
            return JText::sprintf('VBO_HISTORY_TRACKER_ROOM_ADDED', $currentValue);
        }

        if (!$currentValue) {
            // removed assigned room
            return JText::sprintf('VBO_HISTORY_TRACKER_ROOM_REMOVED', $previousValue);
        }

        // changed from a room to another
        return JText::sprintf('VBO_HISTORY_TRACKER_ROOM_CHANGED', $previousValue, $currentValue);
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('bed');
    }
}

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
 * Tracks the creation of a specific item.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOHistoryDetectorInsert implements VBOHistoryDetector
{
    /**
     * @inheritDoc
     */
    public function getEvent()
    {
        return 'item.created';
    }

    /**
     * @inheritDoc
     */
    final public function hasChanged(object $prev, object $curr)
    {
        // created when the previous value is an object with no properties
        return get_object_vars($prev) === [];
    }

    /**
     * @inheritDoc
     */
    public function describe()
    {
        return JText::translate('VBO_HISTORY_TRACKER_ITEM_CREATED');
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('plus');
    }
}

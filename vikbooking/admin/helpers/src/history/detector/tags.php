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
 * Item tags changes detector class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
abstract class VBOHistoryDetectorTags extends VBOHistoryDetectorArray
{
    /**
     * Class constructor.
     */
    public function __construct(?string $propertyName = null)
    {
        parent::__construct($propertyName ?: 'tags');
    }

    /**
     * @inheritDoc
     */
    protected function describeAddedItems(array $added)
    {
        $last = array_pop($added);

        if (!$added) {
            // only one item
            return JText::sprintf('VBO_HISTORY_TRACKER_TAG_ADDED', $last);
        }
        
        // 2 or more items
        return JText::sprintf('VBO_HISTORY_TRACKER_TAGS_ADDED', implode(', ', $added), $last);
    }

    /**
     * @inheritDoc
     */
    protected function describeRemovedItems(array $removed)
    {
        $last = array_pop($removed);

        if (!$removed) {
            // only one item
            return JText::sprintf('VBO_HISTORY_TRACKER_TAG_REMOVED', $last);
        }
        
        // 2 or more items
        return JText::sprintf('VBO_HISTORY_TRACKER_TAGS_REMOVED', implode(', ', $removed), $last);
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('tag');
    }
}

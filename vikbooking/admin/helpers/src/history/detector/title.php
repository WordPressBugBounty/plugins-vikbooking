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
 * Item title changes detector class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOHistoryDetectorTitle extends VBOHistoryDetectoraware
{
    /**
     * Class constructor.
     */
    public function __construct(?string $propertyName = null)
    {
        parent::__construct($propertyName ?: 'title');
    }

    /**
     * @inheritDoc
     */
    protected function doDescribe($previousValue, $currentValue)
    {
        return JText::sprintf('VBO_HISTORY_TRACKER_TITLE_CHANGED', $previousValue, $currentValue);
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('quote-right');
    }
}

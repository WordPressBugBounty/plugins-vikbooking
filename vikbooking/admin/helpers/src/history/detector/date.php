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
 * Item date changes detector class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOHistoryDetectorDate extends VBOHistoryDetectoraware
{
    /**
     * The date format.
     * 
     * @var string
     */
    protected $format;

    /**
     * Class constructor.
     */
    public function __construct(string $format, ?string $propertyName = null)
    {
        $this->format = $format;

        parent::__construct($propertyName ?: 'date');
    }

    /**
     * @inheritDoc
     */
    protected function doDescribe($previousValue, $currentValue)
    {
        return JText::sprintf(
            'VBO_HISTORY_TRACKER_DATE_CHANGED',
            JHtml::fetch('date', $previousValue, $this->format),
            JHtml::fetch('date', $currentValue, $this->format)
        );
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('calendar-day');
    }
}

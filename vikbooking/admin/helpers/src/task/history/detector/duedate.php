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
 * Task due date changes detector class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOTaskHistoryDetectorDuedate extends VBOHistoryDetectorDate
{
    /**
     * Class constructor.
     */
    public function __construct(?string $format = null)
    {
        parent::__construct($format ?: 'd F Y H:i', 'dueon');
    }

    /**
     * @inheritDoc
     */
    protected function doDescribe($previousValue, $currentValue)
    {
        return JText::sprintf(
            'VBO_HISTORY_TRACKER_DUEDATE_CHANGED',
            JHtml::fetch('date', $previousValue, $this->format),
            JHtml::fetch('date', $currentValue, $this->format)
        );
    }
}

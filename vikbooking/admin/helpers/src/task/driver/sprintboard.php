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
 * Task driver implementation for sprint board.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
class VBOTaskDriverSprintboard extends VBOTaskDriveraware
{
    use VBOTaskDriverParamsVisibility;
    use VBOTaskDriverParamsDefault;

    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'sprintboard';
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return JText::translate('VBO_TASK_DRIVER_SPRINTBOARD');
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('running');
    }

    /**
     * @inheritDoc
     */
    public function getParams()
    {
        return array_merge(
            $this->useVisibilityParams(),
            $this->useDefaultParams()
        );
    }
}

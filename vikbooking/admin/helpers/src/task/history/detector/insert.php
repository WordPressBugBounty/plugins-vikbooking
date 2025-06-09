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
 * Tracks the creation of a specific task.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOTaskHistoryDetectorInsert extends VBOHistoryDetectorInsert
{
    /**
     * @inheritDoc
     */
    public function getEvent()
    {
        return 'task.created';
    }

    /**
     * @inheritDoc
     */
    public function describe()
    {
        return JText::translate('VBO_HISTORY_TRACKER_TASK_CREATED');
    }
}

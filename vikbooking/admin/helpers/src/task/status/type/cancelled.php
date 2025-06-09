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
 * Task status implementation for type "cancelled".
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
class VBOTaskStatusTypeCancelled extends VBOTaskStatusAware
{
    use VBOTaskStatusHelperChatter;

    /**
     * @inheritDoc
     */
    public function getEnum()
    {
        return 'cancelled';
    }

    /**
     * @inheritDoc
     */
    public function getGroupEnum()
    {
        return 'closed';
    }

    /**
     * @inheritDoc
     */
    public function getColor()
    {
        return 'red';
    }

    /**
     * @inheritDoc
     */
    public function getOrdering()
    {
        return 3;
    }

    /**
     * @inheritDoc
     */
    public function apply(int $taskId)
    {
        // inform the other users that the status of the task changed to "CANCELLED"
        $this->sendMessage(
            $taskId,
            JText::sprintf('VBO_TASK_STATUS_CHANGED_MESSAGE', $this->getName())
        );
    }
}

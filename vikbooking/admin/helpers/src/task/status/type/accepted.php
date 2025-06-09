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
 * Task status implementation for type "accepted".
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
class VBOTaskStatusTypeAccepted extends VBOTaskStatusAware
{
    use VBOTaskStatusHelperAssigner;

    /**
     * @inheritDoc
     */
    public function getEnum()
    {
        return 'accepted';
    }

    /**
     * @inheritDoc
     */
    public function getGroupEnum()
    {
        return 'scheduled';
    }

    /**
     * @inheritDoc
     */
    public function getColor()
    {
        return 'ocean';
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
        $this->assignUser(VBOTaskTaskregistry::getRecordInstance((int) $taskId));
    }

    /**
     * @inheritDoc
     */
    public function display(VBOTaskTaskregistry $task)
    {
        return $this->displayUnassignable($task);
    }
}

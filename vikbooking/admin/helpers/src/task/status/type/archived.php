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
 * Task status implementation for type "archived".
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
class VBOTaskStatusTypeArchived extends VBOTaskStatusAware
{
    use VBOTaskStatusHelperArchiver;
    use VBOTaskStatusHelperWorker;

    /**
     * @inheritDoc
     */
    public function getEnum()
    {
        return 'archived';
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
        return 'brown';
    }

    /**
     * @inheritDoc
     */
    public function getOrdering()
    {
        return 2;
    }

    /**
     * @inheritDoc
     */
    public function apply(int $taskId)
    {
        $task = VBOTaskTaskregistry::getRecordInstance((int) $taskId);

        $this->finishWork($task);
        $this->archive($task);
    }

    /**
     * @inheritDoc
     */
    public function display(VBOTaskTaskregistry $task)
    {
        return $this->displayFinishedWork($task);
    }
}

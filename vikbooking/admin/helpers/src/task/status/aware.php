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
 * Declares all task status methods.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
abstract class VBOTaskStatusAware implements VBOTaskStatusInterface
{
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return JText::translate('VBO_TASK_STATUS_TYPE_' . strtoupper($this->getEnum()));
    }

    /**
     * @inheritDoc
     */
    public function getColor()
    {
        return 'gray';
    }

    /**
     * @inheritDoc
     */
    public function getOrdering()
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function apply(int $taskId)
    {
        // do nothing by default
    }

    /**
     * @inheritDoc
     */
    public function display(VBOTaskTaskregistry $task)
    {
        // do nothing by default
    }
}

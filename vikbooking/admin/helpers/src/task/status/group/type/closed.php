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
 * Task status group implementation for type "closed".
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskStatusGroupTypeClosed implements VBOTaskStatusGroupInterface
{
    /**
     * @inheritDoc
     */
    public function getEnum()
    {
        return 'closed';
    }

    /**
     * @inheritDoc
     */
    public function getOrdering()
    {
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return JText::translate('VBO_TASK_STATUS_GROUP_TYPE_CLOSED');
    }
}

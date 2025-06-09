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
 * Task driver default params trait.
 * 
 * @since 1.18.0 (J) - 1.8.0 (WP)
 */
trait VBOTaskDriverParamsDefault
{
    /**
     * Returns the default params:
     * 
     * - task duration
     * 
     * @return  array
     * 
     * @see VBOTaskDriverinterface::getParams()
     */
    public function useDefaultParams()
    {
        return [
            'taskduration' => [
                'type'    => 'number',
                'label'   => JText::translate('VBO_DEF_TASK_DURATION'),
                'help'    => JText::translate('VBO_DEF_TASK_DURATION_HELP'),
                'min'     => 0,
                'default' => 60,
            ],
        ];
    }
}

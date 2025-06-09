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
 * Task driver visibility params trait.
 * 
 * @since 1.18.0 (J) - 1.8.0 (WP)
 */
trait VBOTaskDriverParamsVisibility
{
    /**
     * Returns the default params:
     * 
     * - private
     * 
     * @return  array
     * 
     * @see VBOTaskDriverinterface::getParams()
     */
    public function useVisibilityParams()
    {
        return [
            'private' => [
                'type'    => 'checkbox',
                'label'   => JText::translate('VBO_TASK_AREA_PRIVATE'),
                'help'    => JText::translate('VBO_TASK_AREA_PRIVATE_HELP'),
                'default' => 0,
            ],
        ];
    }
}

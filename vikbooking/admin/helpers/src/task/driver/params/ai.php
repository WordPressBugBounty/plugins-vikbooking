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
 * Task driver AI params trait.
 * 
 * @since 1.18.0 (J) - 1.8.0 (WP)
 */
trait VBOTaskDriverParamsAi
{
    /**
     * Returns the AI params:
     * 
     * - AI Support
     * 
     * @return  array
     * 
     * @see VBOTaskDriverinterface::getParams()
     */
    public function useAiParams()
    {
        return [
            'ai' => [
                'type'    => 'checkbox',
                'label'   => JText::translate('VBO_AI_SUPPORT'),
                'help'    => JText::translate('VBO_TASK_AI_SUPPORT_HELP'),
                'default' => 1,
            ],
        ];
    }
}

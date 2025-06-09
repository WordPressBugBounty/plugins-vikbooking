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
 * Task driver filtering params trait.
 * 
 * @since 1.18.0 (J) - 1.8.0 (WP)
 */
trait VBOTaskDriverParamsFiltering
{
    /**
     * Returns the filtering params:
     * 
     * - operators
     * - listings
     * 
     * @return  array
     * 
     * @see VBOTaskDriverinterface::getParams()
     */
    public function useFilteringParams()
    {
        $params = [
            'operators' => [
                'type'     => 'elements',
                'label'    => JText::translate('VBMENUOPERATORS'),
                'help'    => JText::translate('VBO_TASK_OPERATORS_DESC'),
                'elements' => VikBooking::getOperatorInstance()->getElements(),
                'multiple' => true,
                'inline'   => false,
                'asset_options' => [
                    'placeholder' => JText::translate('VBANYTHING'),
                    'allowClear'  => true,
                ],
            ],
            'listings' => [
                'type'     => 'listings',
                'label'    => JText::translate('VBO_LISTINGS'),
                'help'    => JText::translate('VBO_TASK_LISTINGS_DESC'),
                'multiple' => true,
                'inline'   => false,
                'asset_options' => [
                    'placeholder' => JText::translate('VBANYTHING'),
                    'allowClear'  => true,
                ],
            ],
        ];

        // in case of no existing operators, suggest the user to create a new one
        if (!$params['operators']['elements']) {
            $uri = VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=newoperator');

            $params['operators']['type'] = 'custom';
            $params['operators']['html'] = '<a href="' . $uri . '" class="btn btn-small">' . JText::translate('VBO_BACKUP_ACTION_CREATE') . '</a>';
        }

        return $params;
    }
}

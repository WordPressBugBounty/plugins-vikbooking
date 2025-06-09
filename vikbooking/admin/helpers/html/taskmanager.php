<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking HTML task manager helper.
 *
 * @since 1.8
 */
abstract class VBOHtmlTaskmanager
{
    /**
     * Renders the HTML badge of a status code.
     * 
     * List of supported options:
     * 
     * @var bool  editable  Whether the badge allows the users to change status.
     * 
     * @param   VBOTaskStatusInterface  $status
     * @param   array                   $options
     * 
     * @return  string
     */
    public static function status(VBOTaskStatusInterface $status, array $options = [])
    {
        // inject default values
        $options = array_merge([
            'editable' => true,
            'class' => '',
        ], $options);

        // render status badge
        return JLayoutHelper::render(
            'taskmanager.components.status',
            [
                'status' => $status,
                'editable' => $options['editable'],
                'class' => $options['class'],
            ],
            null,
            [
                'client' => 'admin',
            ]
        );
    }
}

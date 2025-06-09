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
 * VikBooking task manager controller (site).
 *
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
class VikBookingControllerTaskmanager extends JControllerAdmin
{
    /**
     * AJAX endpoint to update an existing TM task.
     * 
     * @return  void
     */
    public function updateTask()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // the name of the tool to access
        $tool = 'task_manager';

        try {
            // obtain data from the validation of the current operator and tool permissions
            list($operator, $permissions, $tool_uri) = VikBooking::getOperatorInstance()->authOperatorToolData($tool);
        } catch (Exception $e) {
            // abort
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        // gather request values to update
        $data = (array) $app->input->get('data', [], 'array');

        if (empty($data['id'])) {
            // missing task id
            VBOHttpDocument::getInstance($app)->close(400, 'Missing task record ID.');
        }

        // build a list of properties supported for the update
        $supportedProperties = [
            'dueon',
            'status_enum',
            'tags',
        ];

        // filter out unsupported record properties for modification
        $data = array_filter($data, function($value, $property) use ($supportedProperties) {
            return !empty($value) && ($property == 'id' || in_array($property, $supportedProperties));
        }, ARRAY_FILTER_USE_BOTH);

        if (count($data) < 2) {
            // nothing to update except the ID
            VBOHttpDocument::getInstance($app)->close(500, 'Nothing to update.');
        }

        // update the existing record
        if (!VBOTaskModelTask::getInstance()->update($data)) {
            // query failed
            VBOHttpDocument::getInstance($app)->close(500, 'Could not update the database record. Please try again.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => 1,
            'taskId'  => $data['id'],
        ]);
    }

    /**
     * AJAX endpoint to update the N-th checkbox of a task.
     * 
     * @return  void
     */
    public function updateChecklist()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // the name of the tool to access
        $tool = 'task_manager';

        try {
            // obtain data from the validation of the current operator and tool permissions
            list($operator, $permissions, $tool_uri) = VikBooking::getOperatorInstance()->authOperatorToolData($tool);

            $taskId = $app->input->getUint('id', 0);
            $index = $app->input->getUint('index', 0);
            $status = $app->input->getBool('status', null);

            VBOTaskModelTask::getInstance()->updateChecklist($taskId, $index, $status);
        } catch (Exception $e) {
            // abort
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        // send the response to output
        $app->close();
    }
}

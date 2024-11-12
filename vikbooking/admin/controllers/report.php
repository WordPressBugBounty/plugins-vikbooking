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
 * VikBooking report controller.
 *
 * @since   1.17.1 (J) - 1.7.1 (WP)
 */
class VikBookingControllerReport extends JControllerAdmin
{
    /**
     * AJAX endpoint to render the custom settings of a report.
     * 
     * @return  void
     */
    public function renderSettings()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $report = $app->input->getString('report', '');

        $report_obj = VikBooking::getReportInstance($report);

        if (!$report_obj) {
            // invalid report requested
            VBOHttpDocument::getInstance($app)->close(404, sprintf('Could not find the report [%s] to load the settings from.', $report));
        }

        // fetch the report form settings
        $layout_data = [
            'report'   => $report,
            'fields'   => $report_obj->getSettingFields(),
            'settings' => $report_obj->loadSettings(),
            'instance' => $report_obj,
        ];

        $form_html = JLayoutHelper::render('reports.report.settings', $layout_data, null, [
            'component' => 'com_vikbooking',
            'client'    => 'administrator',
        ]);

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'html' => $form_html,
        ]);
    }

    /**
     * AJAX endpoint to save the custom settings of a report.
     * 
     * @return  void
     */
    public function saveSettings()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $report = $app->input->getString('report', '');
        $data   = $app->input->get('data', [], 'array');

        $report_obj = VikBooking::getReportInstance($report);

        if (!$report_obj) {
            // invalid report requested
            VBOHttpDocument::getInstance($app)->close(404, sprintf('Could not find the report [%s] to save the settings for.', $report));
        }

        $report_obj->saveSettings($data, $merge = true);

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => 1,
        ]);
    }

    /**
     * AJAX endpoint to execute a custom scoped action of a report.
     * 
     * @return  void
     */
    public function executeCustomAction()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $report = $app->input->getString('report_file', '');
        $action = $app->input->getString('report_action', '');
        $scope  = $app->input->getString('report_scope', '');
        $data   = $app->input->get('report_data', [], 'array');

        $report_obj = VikBooking::getReportInstance($report);

        if (!$report_obj) {
            // invalid report requested
            VBOHttpDocument::getInstance($app)->close(404, sprintf('Could not find the report [%s] for executing the action.', $report));
        }

        // get all the available scoped actions, hidden and visible
        $actions = $report_obj->getScopedActions($scope, $visible = false);
        if (!in_array($action, array_column($actions, 'id'))) {
            // unsupported action
            VBOHttpDocument::getInstance($app)->close(403, sprintf('Unsupported report action [%s].', $action));
        }

        try {
            $result = $report_obj->executeAction($action, $scope, $data);
        } catch (Exception $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json($result);
    }
}

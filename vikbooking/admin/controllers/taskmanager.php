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
 * VikBooking task manager controller (admin).
 *
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
class VikBookingControllerTaskmanager extends JControllerAdmin
{
    /**
     * AJAX endpoint to render a task manager layout file.
     * 
     * @return  void
     */
    public function renderLayout()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $type = $app->input->getString('type', '');
        $data = (array) $app->input->get('data', [], 'array');

        if (empty($type)) {
            // invalid layout requested
            VBOHttpDocument::getInstance($app)->close(404, sprintf('Could not find the layout [%s] to render.', $type));
        }

        // fetch the requested TM layout
        $layout_data = [
            'data' => $data,
        ];

        try {
            $layout_html = JLayoutHelper::render('taskmanager.' . $type, $layout_data);
        } catch (Exception $e) {
            // raise the error caught
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'html' => $layout_html,
        ]);
    }

    /**
     * AJAX endpoint to create a new TM area.
     * 
     * @return  void
     */
    public function createArea()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $area = (array) $app->input->get('area', [], 'array');
        $area_settings = (array) $app->input->get('area_settings', [], 'array');

        if (empty($area['instanceof'])) {
            // missing task driver type for area
            VBOHttpDocument::getInstance($app)->close(400, 'Missing task driver type for the area.');
        }

        // access the task manager object
        $taskManager = VBOFactory::getTaskManager();

        if (!$taskManager->driverExists($area['instanceof'])) {
            // unknown task driver
            VBOHttpDocument::getInstance($app)->close(400, sprintf('Unknown task driver [%s]', $area['instanceof']));
        }

        // normalize area fields
        if (empty($area['name'])) {
            // set the default task driver name
            $area['name'] = $taskManager->getDriverInstance($area['instanceof'])->getName();
        }

        // set area task driver settings
        $area['settings'] = $area_settings[$area['instanceof']] ?? [];

        // filter out empty values
        $area = array_filter($area);

        // store the record
        $areaId = VBOTaskModelArea::getInstance()->save($area);

        if (!$areaId) {
            // query failed
            VBOHttpDocument::getInstance($app)->close(500, 'Could not store the database record. Please try again.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'areaId' => $areaId,
        ]);
    }

    /**
     * AJAX endpoint to update an existing TM area.
     * 
     * @return  void
     */
    public function updateArea()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $area = (array) $app->input->get('area', [], 'array');
        $area_settings = (array) $app->input->get('area_settings', [], 'array');

        if (empty($area['id'])) {
            // missing area record id
            VBOHttpDocument::getInstance($app)->close(400, 'Missing area record id.');
        }

        if (empty($area['instanceof'])) {
            // missing task driver type for area
            VBOHttpDocument::getInstance($app)->close(400, 'Missing task driver type for the area.');
        }

        // access the task manager object
        $taskManager = VBOFactory::getTaskManager();

        if (!$taskManager->driverExists($area['instanceof'])) {
            // unknown task driver
            VBOHttpDocument::getInstance($app)->close(400, sprintf('Unknown task driver [%s]', $area['instanceof']));
        }

        // normalize area fields
        if (empty($area['name'])) {
            // set the default task driver name
            $area['name'] = $taskManager->getDriverInstance($area['instanceof'])->getName();
        }

        // set area task driver settings
        $area['settings'] = $area_settings[$area['instanceof']] ?? [];

        // filter out empty values
        $area = array_filter($area);

        // update the existing record
        if (!VBOTaskModelArea::getInstance()->update($area)) {
            // query failed
            VBOHttpDocument::getInstance($app)->close(500, 'Could not update the database record. Please try again.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'areaId' => $area['id'],
        ]);
    }

    /**
     * AJAX endpoint to delete an existing TM area.
     * 
     * @return  void
     */
    public function deleteArea()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $area_id = $app->input->getInt('area_id', 0);

        if (empty($area_id)) {
            // missing area record id
            VBOHttpDocument::getInstance($app)->close(400, 'Missing area record id.');
        }

        $record = VBOTaskModelArea::getInstance()->getItem($area_id);

        if (!$record) {
            // area record not found
            VBOHttpDocument::getInstance($app)->close(404, 'Area record not found.');
        }

        if (!VBOTaskModelArea::getInstance()->delete($record->id)) {
            // query error
            VBOHttpDocument::getInstance($app)->close(500, 'Could not delete the area record.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => 1,
        ]);
    }

    /**
     * AJAX endpoint to toggle the display state for an existing TM area.
     * 
     * @return  void
     */
    public function toggleAreaDisplay()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $area = (array) $app->input->get('area', [], 'array');

        if (empty($area['id'])) {
            // missing area record id
            VBOHttpDocument::getInstance($app)->close(400, 'Missing area record id.');
        }

        // get area record
        $record = VBOTaskModelArea::getInstance()->getItem($area['id']);
        if (!$record) {
            // area not found
            VBOHttpDocument::getInstance($app)->close(404, 'Area record not found.');
        }

        // build record data payload
        $data = [
            'id' => $record->id,
            // set or toggle display state
            'display' => isset($area['display']) ? intval((bool) $area['display']) : intval(!((bool) $record->display)),
        ];

        // update the existing record
        if (!VBOTaskModelArea::getInstance()->update($data)) {
            // query failed
            VBOHttpDocument::getInstance($app)->close(500, 'Could not update the database record. Please try again.');
        }

        // update visible areas in session
        if ($data['display']) {
            VBOFactory::getTaskManager()->setVisibleArea($record->id);
        } else {
            VBOFactory::getTaskManager()->unsetVisibleArea($record->id);
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'areaId' => $record->id,
            'status' => $data['display'],
        ]);
    }

    /**
     * AJAX endpoint to update an existing color tag.
     * 
     * @return  void
     */
    public function updateColorTag()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $colortag = (array) $app->input->get('colortag', [], 'array');

        if (empty($colortag['id'])) {
            // missing color tag record id
            VBOHttpDocument::getInstance($app)->close(400, 'Missing color tag record id.');
        }

        // get color tag record
        $record = VBOTaskModelColortag::getInstance()->getItem($colortag['id']);
        if (!$record) {
            // color tag not found
            VBOHttpDocument::getInstance($app)->close(404, 'Color tag record not found.');
        }

        // update the existing record
        if (!VBOTaskModelColortag::getInstance()->update($colortag)) {
            // query failed
            VBOHttpDocument::getInstance($app)->close(500, 'Could not update the database record. Please try again.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => 1,
        ]);
    }

    /**
     * AJAX endpoint to delete an existing color tag.
     * 
     * @return  void
     */
    public function deleteColorTag()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $colortag = (array) $app->input->get('colortag', [], 'array');

        if (empty($colortag['id'])) {
            // missing color tag record id
            VBOHttpDocument::getInstance($app)->close(400, 'Missing color tag record id.');
        }

        // get color tag record
        $record = VBOTaskModelColortag::getInstance()->getItem($colortag['id']);
        if (!$record) {
            // color tag not found
            VBOHttpDocument::getInstance($app)->close(404, 'Color tag record not found.');
        }

        // delete the existing record
        if (!VBOTaskModelColortag::getInstance()->delete($colortag['id'])) {
            // query failed
            VBOHttpDocument::getInstance($app)->close(500, 'Could not delete the database record. Please try again.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => 1,
        ]);
    }

    /**
     * AJAX endpoint to create a new TM task.
     * 
     * @return  void
     */
    public function createTask()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $data = (array) $app->input->get('data', [], 'array');

        if (empty($data['id_area'])) {
            // missing area ID
            VBOHttpDocument::getInstance($app)->close(400, 'Missing task project/area ID.');
        }

        if (empty($data['title'])) {
            // missing task title
            VBOHttpDocument::getInstance($app)->close(400, 'Please provide a title for the task.');
        }

        // store the record
        $taskId = VBOTaskModelTask::getInstance()->save($data);

        if (!$taskId) {
            // query failed
            VBOHttpDocument::getInstance($app)->close(500, 'Could not store the task database record. Please try again.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'taskId' => $taskId,
        ]);
    }

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

        $data = (array) $app->input->get('data', [], 'array');

        if (empty($data['id'])) {
            // missing task id
            VBOHttpDocument::getInstance($app)->close(400, 'Missing task record ID.');
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
     * AJAX endpoint to delete an existing TM task.
     * 
     * @return  void
     */
    public function deleteTask()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $data = (array) $app->input->get('data', [], 'array');

        if (empty($data['id'])) {
            // missing task id
            VBOHttpDocument::getInstance($app)->close(400, 'Missing task record ID.');
        }

        // get task record
        $record = VBOTaskModelTask::getInstance()->getItem($data['id']);
        if (!$record) {
            // task not found
            VBOHttpDocument::getInstance($app)->close(404, 'Task record not found.');
        }

        // delete the existing record
        if (!VBOTaskModelTask::getInstance()->delete($data['id'])) {
            // query failed
            VBOHttpDocument::getInstance($app)->close(500, 'Could not delete the database record. Please try again.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => 1,
        ]);
    }

    /**
     * AJAX endpoint to load the tasks for a given area, listings and dates.
     * 
     * @return  void
     */
    public function loadAreaListingTasks()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $area_id = $app->input->getUInt('area_id', 0);
        $room_ids = (array) $app->input->get('room_ids', [], 'array');
        $from_date = $app->input->getString('from_date', date('Y-m-01'));
        $to_date = $app->input->getString('to_date', date('Y-m-t'));

        if (!$area_id || !$room_ids) {
            // missing mandatory values
            VBOHttpDocument::getInstance($app)->close(400, 'Missing area/project ID or listing IDs.');
        }

        // access the task manager object
        $taskManager = VBOFactory::getTaskManager();

        // get the area record
        $area = VBOTaskModelArea::getInstance()->getItem($area_id);

        if (!$area) {
            // area/project id not found
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid area/project ID.');
        }

        // wrap the area record into a registry
        $areaRegistry = VBOTaskArea::getInstance((array) $area);

        // normalize area record object
        if (empty($area->icon) && !empty($area->instanceof)) {
            $area->icon = $areaRegistry->getIcon();
        }
        if (!empty($area->icon)) {
            $area->icon_class = VikBookingIcons::i($area->icon);
        }

        // pool of listing tasks
        $listingTasks = [];

        // build filters
        $filters = [
            'id_area'  => $area_id,
            'id_rooms' => $room_ids,
            'dates'    => $from_date . ':' . $to_date,
        ];

        // load tasks according to filters, by always forcing/injecting the area IDs and the dates
        foreach (VBOTaskModelTask::getInstance()->filterItems($filters, 0, 0) as $taskRecord) {
            // wrap task record into a registry
            $task = VBOTaskTaskregistry::getInstance((array) $taskRecord);

            // task listing id
            $listing_id = $task->getListingId();

            // task due date key
            $date_key = $task->getDueDate(true, 'Y-m-d');

            if (!isset($listingTasks[$listing_id])) {
                // start container
                $listingTasks[$listing_id] = [];
            }

            if (!isset($listingTasks[$listing_id][$date_key])) {
                // start container
                $listingTasks[$listing_id][$date_key] = [];
            }

            // build task status color enum
            $statusColorEnum = '';
            if ($taskManager->statusTypeExists($task->getStatus())) {
                $statusColorEnum = $taskManager->getStatusTypeInstance($task->getStatus())->getColor();
            }

            // push listing day task information
            $listingTasks[$listing_id][$date_key][] = [
                'id'         => $task->getID(),
                'area_id'    => $task->getAreaID(),
                'bid'        => $task->getBookingId(),
                'title'      => $task->getTitle(),
                'status'     => $task->getStatus(),
                'color'      => $statusColorEnum,
                'dueon'      => $task->getDueDate(true, 'Y-m-d H:i'),
                'scheduling' => $task->getScheduling(),
            ];
        }

        // send response to output
        VBOHttpDocument::getInstance($app)->json([
            'area' => $area,
            'listings' => $listingTasks,
            'listingIds' => $areaRegistry->getListingIds(),
        ]);
    }
}

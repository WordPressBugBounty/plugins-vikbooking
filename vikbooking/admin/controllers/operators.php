<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking operators controller.
 *
 * @since 	1.16.9 (J) - 1.6.9 (WP)
 */
class VikBookingControllerOperators extends JControllerAdmin
{
	/**
	 * Removes the permissions from one operator for a given tool.
	 * AJAX endpoint.
	 */
	public function removePermission()
	{
		if (!JFactory::getUser()->authorise('core.delete', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		if (!JSession::checkToken()) {
			// missing CSRF-proof token
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$input = $app->input;

		$operator_id = $input->getUInt('operator_id', 0);
		$tool_id = $input->getString('tool_id', '');

		if (!$operator_id || !$tool_id) {
			VBOHttpDocument::getInstance($app)->close(400, 'Missing mandatory values to perform the request');
		}

		// access the global operators object
		$oper_obj = VikBooking::getOperatorInstance();

		$record = $oper_obj->getOne($operator_id);

		if (!$record || !$record['perms']) {
			VBOHttpDocument::getInstance($app)->close(404, 'Operator or operator-tool not found');
		}

		foreach ($record['perms'] as $index => $tool_perms) {
			if (!strcasecmp($tool_perms['type'], $tool_id)) {
				// tool permissions found, unset them
				unset($record['perms'][$index]);
				break;
			}
		}

		// reset keys to always keep a numeric array
		$record['perms'] = array_values($record['perms']);

		$dbo->setQuery(
			$dbo->getQuery(true)
				->update($dbo->qn('#__vikbooking_operators'))
				->set($dbo->qn('perms') . ' = ' . $dbo->q(json_encode($record['perms'])))
				->where($dbo->qn('id') . ' = ' . (int) $record['id'])
		);

		$dbo->execute();

		VBOHttpDocument::getInstance($app)->json($record['perms']);
	}

	/**
	 * Saves (adds or updates) the permissions of one operator for a given tool.
	 * AJAX endpoint.
	 */
	public function savePermission()
	{
		if (!JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		if (!JSession::checkToken()) {
			// missing CSRF-proof token
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$input = $app->input;

		$operator_id = $input->getUInt('operator_id', 0);
		$tool_id     = $input->getString('tool_id', '');
		$perms       = $input->get('perms', [], 'array');

		if (!$operator_id || !$tool_id) {
			VBOHttpDocument::getInstance($app)->close(400, 'Missing mandatory values to perform the request');
		}

		// access the global operators object
		$oper_obj = VikBooking::getOperatorInstance();

		$record = $oper_obj->getOne($operator_id);

		if (!$record) {
			VBOHttpDocument::getInstance($app)->close(404, 'Operator not found');
		}

		if (!$record['perms']) {
			$record['perms'] = [];
		}

		// detect if we are updating existing tool permissions
		$updated = false;
		foreach ($record['perms'] as $index => $tool_perms) {
			if (!strcasecmp($tool_perms['type'], $tool_id)) {
				// existing tool permissions found
				$record['perms'][$index]['perms'] = $perms;

				// turn flag on
				$updated = true;

				break;
			}
		}

		if (!$updated) {
			// append new tool permissions
			$record['perms'][] = [
				'type'  => $tool_id,
				'perms' => $perms,
			];
		}

		// update operator record
		$dbo->setQuery(
			$dbo->getQuery(true)
				->update($dbo->qn('#__vikbooking_operators'))
				->set($dbo->qn('perms') . ' = ' . $dbo->q(json_encode($record['perms'])))
				->where($dbo->qn('id') . ' = ' . (int) $record['id'])
		);

		$dbo->execute();

		// output the new operator permissions
		VBOHttpDocument::getInstance($app)->json($record['perms']);
	}
}

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
 * VikBooking states controller.
 *
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VikBookingControllerStates extends JControllerAdmin
{
	/**
	 * AJAX endpoint to load the states of a given country.
	 * 
	 * @return 	void
	 */
	public function load_from_country()
	{
		$dbo = JFactory::getDbo();

		$id_country 	= VikRequest::getInt('id_country', 0, 'request');
		$country_3_code = VikRequest::getString('country_3_code', '', 'request');
		$country_2_code = VikRequest::getString('country_2_code', '', 'request');
		$country_name 	= VikRequest::getString('country_name', '', 'request');

		if (empty($id_country) && empty($country_3_code) && empty($country_2_code) && empty($country_name)) {
			VBOHttpDocument::getInstance()->close(500, 'Missing country identifier');
		}

		if (!empty($id_country)) {
			$q = "SELECT * FROM `#__vikbooking_states` WHERE `id_country`=" . $id_country;
			$dbo->setQuery($q);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				// no records found for this country
				VBOHttpDocument::getInstance()->json([]);
			}
			// output the JSON encoded list of states found
			VBOHttpDocument::getInstance()->json($dbo->loadAssocList());
		}

		// find country ID by name or code
		$field_name = $dbo->qn('country_name');
		$field_value = $country_name;
		if (!empty($country_3_code)) {
			$field_name = $dbo->qn('country_3_code');
			$field_value = $country_3_code;
		}
		if (!empty($country_2_code)) {
			$field_name = $dbo->qn('country_2_code');
			$field_value = $country_2_code;
		}

		$q = "SELECT `id` FROM `#__vikbooking_countries` WHERE {$field_name}=" . $dbo->quote($field_value);
		$dbo->setQuery($q, 0, 1);
		$id_country = $dbo->loadResult();
		if (!$id_country) {
			// country not found
			VBOHttpDocument::getInstance()->close(404, sprintf('Country [%s] not found', $field_value));
		}

		$q = "SELECT * FROM `#__vikbooking_states` WHERE `id_country`=" . $id_country;
		$dbo->setQuery($q);
		$states = $dbo->loadAssocList();
		if (!$states) {
			// no records found for this country
			VBOHttpDocument::getInstance()->json([]);
		}

		// output the JSON encoded list of states found
		VBOHttpDocument::getInstance()->json($states);
	}

	/**
	 * Adds a new state.
	 */
	public function add()
	{
		$app = JFactory::getApplication();
		$app->redirect('index.php?option=com_vikbooking&view=managestate&idcountry=' . $app->input->getInt('idcountry', 0));
	}

	/**
	 * Modifies an existing state.
	 */
	public function edit()
	{
		$app = JFactory::getApplication();

		$ids = $app->input->get('cid', [0], 'uint');

		$app->redirect('index.php?option=com_vikbooking&view=managestate&id=' . $ids[0]);
	}

	/**
	 * Removes one or more existing states.
	 */
	public function remove()
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$ids = $app->input->get('cid', [0], 'uint');

		$q = "DELETE FROM `#__vikbooking_states` WHERE `id` IN (" . implode(', ', $ids) . ")";
		$dbo->setQuery($q);
		$dbo->execute();

		$app->redirect('index.php?option=com_vikbooking&view=states');
	}

	/**
	 * Cancels the management operation.
	 */
	public function cancel()
	{
		$app = JFactory::getApplication();
		$app->redirect('index.php?option=com_vikbooking&view=states');
	}

	/**
	 * Adds a new state/province.
	 */
	public function save()
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$pidcountry    = $app->input->getInt('idcountry', 0);
		$pstate_name   = $app->input->getString('state_name', '');
		$pstate_3_code = $app->input->getString('state_3_code', '');
		$pstate_2_code = $app->input->getString('state_2_code', '');
		$ppublished    = $app->input->getInt('published', 0);

		if (empty($pidcountry) || empty($pstate_name)) {
			$app->enqueueMessage('Missing required values', 'error');
			$app->redirect('index.php?option=com_vikbooking&task=states.add');
			$app->close();
		}

		if (empty($pstate_3_code)) {
			$pstate_3_code = function_exists('mb_substr') ? mb_substr($pstate_name, 0, 3, 'UTF-8') : substr($pstate_name, 0, 3);
		}

		if (empty($pstate_2_code)) {
			$pstate_2_code = function_exists('mb_substr') ? mb_substr($pstate_name, 0, 2, 'UTF-8') : substr($pstate_name, 0, 2);
		}

		$add_state = new stdClass;
		$add_state->id_country 	 = $pidcountry;
		$add_state->state_name 	 = $pstate_name;
		$add_state->state_3_code = $pstate_3_code;
		$add_state->state_2_code = $pstate_2_code;
		$add_state->published 	 = $ppublished;

		$dbo->insertObject('#__vikbooking_states', $add_state, 'id');

		if (!isset($add_state->id)) {
			$app->enqueueMessage('Could not create the new state/province');
		} else {
			$app->enqueueMessage(JText::translate('JLIB_APPLICATION_SAVE_SUCCESS'));
		}

		$app->redirect('index.php?option=com_vikbooking&view=states');
	}

	/**
	 * Updates a state/province.
	 */
	public function update()
	{
		$this->do_update();
	}

	/**
	 * Updates a state/province.
	 */
	public function update_stay()
	{
		$this->do_update($stay = true);
	}

	/**
	 * Updates a state/province.
	 */
	protected function do_update($stay = false)
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$pid 		   = $app->input->getInt('id', 0);
		$pidcountry    = $app->input->getInt('idcountry', 0);
		$pstate_name   = $app->input->getString('state_name', '');
		$pstate_3_code = $app->input->getString('state_3_code', '');
		$pstate_2_code = $app->input->getString('state_2_code', '');
		$ppublished    = $app->input->getInt('published', 0);

		if (empty($pid)) {
			$app->enqueueMessage('Missing record ID to update', 'error');
			$app->redirect('index.php?option=com_vikbooking&view=states');
			$app->close();
		}

		if (empty($pidcountry) || empty($pstate_name)) {
			$app->enqueueMessage('Missing required values', 'error');
			$app->redirect('index.php?option=com_vikbooking&task=states.edit&cid[]=' . $pid);
			$app->close();
		}

		if (empty($pstate_3_code)) {
			$pstate_3_code = function_exists('mb_substr') ? mb_substr($pstate_name, 0, 3, 'UTF-8') : substr($pstate_name, 0, 3);
		}

		if (empty($pstate_2_code)) {
			$pstate_2_code = function_exists('mb_substr') ? mb_substr($pstate_name, 0, 2, 'UTF-8') : substr($pstate_name, 0, 2);
		}

		$set_state = new stdClass;
		$set_state->id 	 		 = $pid;
		$set_state->id_country 	 = $pidcountry;
		$set_state->state_name 	 = $pstate_name;
		$set_state->state_3_code = $pstate_3_code;
		$set_state->state_2_code = $pstate_2_code;
		$set_state->published 	 = $ppublished;

		$dbo->updateObject('#__vikbooking_states', $set_state, 'id');

		$app->enqueueMessage(JText::translate('JLIB_APPLICATION_SAVE_SUCCESS'));

		if ($stay) {
			$app->redirect('index.php?option=com_vikbooking&task=states.edit&cid[]=' . $set_state->id);
		} else {
			$app->redirect('index.php?option=com_vikbooking&view=states');
		}
	}
}

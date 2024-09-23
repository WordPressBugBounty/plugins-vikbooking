<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking menuactions controller.
 *
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VikBookingControllerMenuactions extends JControllerAdmin
{
	/**
	 * AJAX endpoint to update the admin menu actions (quick actions).
	 * 
	 * @return 	void
	 */
	public function update()
	{
		$actions = JFactory::getApplication()->input->get('actions', [], 'array');

		if (!$actions) {
			VBOHttpDocument::getInstance()->close(500, 'Invalid data received');
		}

		// parse new actions
		foreach ($actions as &$menu_action) {
			if (!is_array($menu_action) || !isset($menu_action['scope']) || !isset($menu_action['actions'])) {
				unset($menu_action);
				continue;
			}

			if (!is_array($menu_action['actions'])) {
				$menu_action['actions'] = [];
			}
		}

		// unset last reference
		unset($menu_action);

		if (!$actions) {
			VBOHttpDocument::getInstance()->close(500, 'No actions to store');
		}

		if (!$config_field_nm = $this->getConfigFieldName()) {
			VBOHttpDocument::getInstance()->close(500, 'No valid user');
		}

		$config = VBOFactory::getConfig();
		$current_actions = $config->getArray($config_field_nm, []);

		if (!$current_actions) {
			// store configuration setting
			$config->set($config_field_nm, $actions);

			// terminate with success
			VBOHttpDocument::getInstance()->json($actions);
		}

		// merge missing menu scopes, if any
		foreach ($current_actions as $curr_menu_action) {
			if (!is_array($curr_menu_action) || !isset($curr_menu_action['scope']) || !isset($curr_menu_action['actions'])) {
				continue;
			}

			if (!is_array($curr_menu_action['actions'])) {
				$curr_menu_action['actions'] = [];
			}

			$scope_found = false;
			foreach ($actions as $menu_action) {
				if ($menu_action['scope'] == $curr_menu_action['scope']) {
					// scope found, nothing to set
					$scope_found = true;
					break;
				}
			}

			if (!$scope_found) {
				// append missing menu scope
				$actions[] = $curr_menu_action;
			}
		}

		// update configuration setting
		$config->set($config_field_nm, $actions);

		// output result
		VBOHttpDocument::getInstance()->json($actions);
	}

	/**
	 * AJAX endpoint to retrieve the admin menu actions (quick actions).
	 * 
	 * @return 	void
	 */
	public function retrieve()
	{
		if (!$config_field_nm = $this->getConfigFieldName()) {
			VBOHttpDocument::getInstance()->close(500, 'No valid user');
		}

		$config = VBOFactory::getConfig();
		$current_actions = $config->getArray($config_field_nm, []);

		// current origin
		$origin = JUri::root();

		// parse actions
		foreach ($current_actions as &$menu_action) {
			if (!is_array($menu_action) || !isset($menu_action['scope']) || empty($menu_action['actions']) || !is_array($menu_action['actions'])) {
				unset($menu_action);
				continue;
			}

			// check for images with a different origin
			foreach ($menu_action['actions'] as &$quick_action) {
				if (!is_array($quick_action) || empty($quick_action['img']) || empty($quick_action['origin'])) {
					continue;
				}

				if (preg_match("/^https?:/i", $quick_action['img'])) {
					$quick_action['img'] = str_replace($quick_action['origin'], $origin, $quick_action['img']);
				}
			}

			// unset last reference
			unset($quick_action);
		}

		// unset last reference
		unset($menu_action);

		// output result
		VBOHttpDocument::getInstance()->json($current_actions);
	}

	/**
	 * Returns the apposite configuration field name for the current user.
	 * 
	 * @return 	string 	the configuration field name to fetch.
	 */
	protected function getConfigFieldName()
	{
		$admin_user_name = JFactory::getUser()->name;

		if (!$admin_user_name) {
			return '';
		}

		return "admin_menu_actions_{$admin_user_name}";
	}
}

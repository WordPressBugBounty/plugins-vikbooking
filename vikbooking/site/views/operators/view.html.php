<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

jimport('joomla.application.component.view');

class VikbookingViewOperators extends JViewVikBooking
{
	public function display($tpl = null)
	{
		VikBooking::prepareViewContent();

		$app = JFactory::getApplication();

		// access the global operators object
		$oper_obj = VikBooking::getOperatorInstance();

		// attempt to get the current operator
		$operator = $oper_obj->getOperatorAccount();

		// check request value for a custom tool
		$tool = $app->input->getString('tool', '');

		if ($operator === false) {
			// operator needs to log in (default_login.php)
			$tpl = 'login';
		} elseif ($tool) {
			/**
			 * Change layout from default to "tool" (tool.php).
			 * 
			 * @since 	1.16.9 (J) - 1.6.9 (WP)
			 */
			$this->setLayout('tool');
		} else {
			// operator is logged in (default_dashboard.php)
			$tpl = 'dashboard';
		}

		// turn the permissions into an associative (or empty) array
		if ($operator) {
			$operator['perms'] = !empty($operator['perms']) ? json_decode($operator['perms'], true) : [];
			$operator['perms'] = is_array($operator['perms']) ? $operator['perms'] : [];
		}

		$this->operator = $operator;
		$this->tool = $tool;

		parent::display($tpl);
	}
}

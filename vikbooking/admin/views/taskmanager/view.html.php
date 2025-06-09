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
 * VikBooking Task Manager view.
 *
 * @since 	1.18.0 (J) - 1.8.0 (WP)
 */
class VikBookingViewTaskmanager extends JViewVikBooking
{
	/**
	 * VikBooking view display method.
	 *
	 * @return 	void
	 */
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		$app = JFactory::getApplication();

		if (!JFactory::getUser()->authorise('core.vbo.pms', 'com_vikbooking')) {
			VBOHttpDocument::getInstance($app)->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		// list of allowed modes
		$allowedModes = [
			'board' => [
				'name' => JText::translate('VBO_BOARD'),
				'icon' => VikBookingIcons::i('table'),
			],
			'list' => [
				'name' => JText::translate('VBO_LIST'),
				'icon' => VikBookingIcons::i('th-list'),
			],
			'calendar' => [
				'name' => JText::translate('VBMENUQUICKRES'),
				'icon' => VikBookingIcons::i('calendar'),
			],
			'overv' => [
				'name' => JText::translate('VBMENUTHREE'),
				'icon' => VikBookingIcons::i('calendar-check'),
				'link' => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=overv', false),
			],
		];

		// user mode preferences
		$user = JFactory::getUser();
		$uname = $user->name;
		$preferences = (array) VBOFactory::getConfig()->getArray('tm_modes', []);

		// check for mode switch
		$mode = $app->getUserStateFromRequest("vbo.tm.mode", 'mode', '', 'string');

		if (!$mode || !isset($allowedModes[$mode])) {
			// check if there's a user mode preference
			if ($user_mode = ($preferences[$uname] ?? null)) {
				$mode = $user_mode;
			}
		}

		$mode = $mode && isset($allowedModes[$mode]) ? $mode : key($allowedModes);

		// update preferences
		$preferences[$uname] = $mode;
		VBOFactory::getConfig()->set('tm_modes', $preferences);

		// get the current View filters
		$filters = (array) $app->getUserStateFromRequest("vbo.tm.filters", 'filters', [], 'array');

		// access task manager
		$taskManager = VBOFactory::getTaskManager();

		// get the first visible areas
		$visibleAreas = $taskManager->getVisibleAreas(0, 3);
		$activeAreas  = array_map('intval', array_column($visibleAreas, 'id'));

		// set View properties
		$this->visibleAreas = $visibleAreas;
		$this->activeAreas  = $activeAreas;
		$this->allowedModes = $allowedModes;
		$this->mode = $mode;
		$this->filters = $filters;

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Setting the toolbar.
	 *
	 * @return 	void
	 */
	protected function addToolBar()
	{
		// add menu title and some buttons to the page
		JToolBarHelper::title(JText::translate('VBO_TITLE_TASK_MANAGER'), 'vikbooking');
	}
}

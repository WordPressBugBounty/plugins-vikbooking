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

/**
 * VikBooking Sample Data view.
 * @wponly
 *
 * @since 1.3.9
 */
class VikBookingViewSampledata extends JViewVikBooking
{
	/**
	 * @override
	 * View display method.
	 *
	 * @return 	void
	 */
	public function display($tpl = null)
	{
		$this->addToolbar();
		
		// display parent
		parent::display($tpl);
	}

	/**
	 * Helper method to setup the toolbar.
	 *
	 * @return 	void
	 */
	public function addToolbar()
	{
		JToolbarHelper::title(JText::translate('VBO_SAMPLEDATA_MENU_TITLE'));

		JToolbarHelper::cancel('canceldash');
	}
}

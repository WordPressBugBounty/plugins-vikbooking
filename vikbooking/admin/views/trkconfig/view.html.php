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

// import Joomla view library
jimport('joomla.application.component.view');

class VikBookingViewTrkconfig extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		// require the tracker class
		VikBooking::getTracker(true);
		//

		$trksettings = VikBookingTracker::loadSettings();
		
		$this->trksettings = $trksettings;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINTRACKINGSTITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
			JToolBarHelper::apply( 'savetrkconfigstay', JText::translate('VBSAVE'));
			JToolBarHelper::save( 'savetrkconfig', JText::translate('VBSAVECLOSE'));
			JToolBarHelper::spacer();
		}
		JToolBarHelper::cancel( 'canceltrk', JText::translate('VBBACK'));
		JToolBarHelper::spacer();
	}
}

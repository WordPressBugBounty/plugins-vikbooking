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

class VikBookingViewExportcustomers extends JViewVikBooking {
	
	function display($tpl = null) {
		// Set the toolbar
		$this->addToolBar();
		
		$cid = VikRequest::getVar('cid', array(0));
		
		$dbo = JFactory::getDBO();
		$q = "SELECT * FROM `#__vikbooking_countries` ORDER BY `#__vikbooking_countries`.`country_name` ASC;";
		$dbo->setQuery($q);
		$countries = $dbo->loadAssocList();
		
		$this->cid = $cid;
		$this->countries = $countries;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		JToolBarHelper::title(JText::translate('VBMAINEXPCUSTOMERSTITLE'), 'vikbooking');
		JToolBarHelper::custom('exportcustomerslaunch', 'download', 'download', JText::translate('VBOCSVEXPCUSTOMERSGET'), false);
		JToolBarHelper::spacer();
		JToolBarHelper::cancel( 'cancelcustomer', JText::translate('VBBACK'));
		JToolBarHelper::spacer();
	}

}

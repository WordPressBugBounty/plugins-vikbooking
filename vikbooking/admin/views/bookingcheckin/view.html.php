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

class VikBookingViewBookingcheckin extends JViewVikBooking {
	
	function display($tpl = null) {
		// This view is usually called within a modal box, so it does not require the toolbar or page title

		/**
		 * @wponly - if we fall here because of a redirect after the generation of the check-in doc, and we are not doing Ajax,
		 * we need to print a button in the toolbar to go back to the booking details page.
		 */
		$ptmpl = VikRequest::getString('tmpl', '', 'request');
		if (empty($ptmpl) && !wp_doing_ajax()) {
			JToolBarHelper::cancel( 'editorder', JText::translate('VBBACK'));
			JToolBarHelper::spacer();
		}
		//
		
		$cid = VikRequest::getVar('cid', array(0));
		$id = $cid[0];
		
		if (file_exists(VCM_ADMIN_PATH.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'vcm-channels.css')) {
			$document = JFactory::getDocument();
			$document->addStyleSheet(VCM_ADMIN_URI.'assets/css/vikchannelmanager.css');
			$document->addStyleSheet(VCM_ADMIN_URI.'assets/css/vcm-channels.css');
		}
		$dbo = JFactory::getDBO();
		$mainframe = JFactory::getApplication();
		$ptmpl = VikRequest::getString('tmpl', '', 'request');
		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=".(int)$id." AND `status`='confirmed';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			$mainframe->redirect('index.php');
			exit;
		}
		$row = $dbo->loadAssoc();
		$rooms = VikBooking::loadOrdersRoomsData($row['id']);
		if (!is_array($rooms) || !(count($rooms) > 0)) {
			$mainframe->redirect('index.php');
			exit;
		}
		$customer = array();
		$q = "SELECT `c`.*,`co`.`idorder`,`co`.`signature`,`co`.`pax_data`,`co`.`comments`,`co`.`checkindoc` FROM `#__vikbooking_customers` AS `c` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `c`.`id`=`co`.`idcustomer` WHERE `co`.`idorder`=".$row['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$customer = $dbo->loadAssoc();
			if (!empty($customer['country'])) {
				if (file_exists(VBO_ADMIN_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'countries'.DIRECTORY_SEPARATOR.$customer['country'].'.png')) {
					$customer['country_img'] = '<img src="'.VBO_ADMIN_URI.'resources/countries/'.$customer['country'].'.png'.'" title="'.$customer['country'].'" class="vbo-country-flag vbo-country-flag-left"/>';
				}
			}
		}
		if (!(count($customer) > 0)) {
			VikError::raiseWarning('', JText::translate('VBOCHECKINERRNOCUSTOMER'));
			$mainframe->redirect('index.php?option=com_vikbooking&task=newcustomer&checkin=1&bid='.$row['id'].($ptmpl == 'component' ? '&tmpl=component' : ''));
			exit;
		}
		
		$this->row = $row;
		$this->rooms = $rooms;
		$this->customer = $customer;
		
		// Display the template
		parent::display($tpl);
	}

}

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

class VikbookingViewRevstay extends JViewVikBooking
{
	function display($tpl = null)
	{
		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();
		$vbo_tn = VikBooking::getTranslator();
		
		// data validation
		$sid = VikRequest::getString('sid', '', 'request');
		$ts = VikRequest::getString('ts', '', 'request');
		if (empty($sid) || empty($ts)) {
			showSelectVb(JText::translate('VBINSUFDATA'));
			return;
		}

		// find booking details
		$q = "SELECT `o`.* FROM `#__vikbooking_orders` AS `o` WHERE (`o`.`sid`=" . $dbo->quote($sid) . " OR `o`.`idorderota`=" . $dbo->quote($sid) . ") AND `o`.`ts`=" . $dbo->quote($ts) . " AND `o`.`status`='confirmed';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			showSelectVb(JText::translate('VBORDERNOTFOUND'));
			return;
		}
		$order = $dbo->loadAssoc();

		// make sure a review can be left for this booking
		if (!VikBooking::canBookingBeReviewed($order)) {
			throw new Exception('Cannot leave a review at this time', 403);
		}

		// get customer information
		$customer = VikBooking::getCPinIstance()->getCustomerFromBooking($order['id']);

		// review services
		$grev_services = VikBooking::guestReviewsServices();
		$vbo_tn->translateContents($grev_services, '#__vikbooking_greview_service');
		
		$this->order = $order;
		$this->customer = $customer;
		$this->grev_services = $grev_services;
		$this->vbo_tn = $vbo_tn;
		
		parent::display($tpl);
	}
}

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

class VikBookingViewCsvexportprepare extends JViewVikBooking {
	
	function display($tpl = null) {
		
		$dbo = JFactory::getDbo();

		$all_rooms = array();
		$all_channels = array();
		$all_payments = array();
		$all_categories = array();

		$q = "SELECT `id`,`name` FROM `#__vikbooking_rooms` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$all_rooms = $dbo->loadAssocList();
		}
		$q = "SELECT `channel` FROM `#__vikbooking_orders` WHERE `channel` IS NOT NULL GROUP BY `channel`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$ord_channels = $dbo->loadAssocList();
			foreach ($ord_channels as $o_channel) {
				$channel_parts = explode('_', $o_channel['channel']);
				$channel_name = count($channel_parts) > 1 ? trim($channel_parts[1]) : trim($channel_parts[0]);
				if (in_array($channel_name, $all_channels)) {
					continue;
				}
				$all_channels[] = $channel_name;
			}
		}
		$q = "SELECT `id`,`name` FROM `#__vikbooking_gpayments` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$all_payments = $dbo->loadAssocList();
		}

		$q = "SELECT `id`,`name` FROM `#__vikbooking_categories` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$all_categories = $dbo->loadAssocList();
		}
		
		$this->all_rooms = $all_rooms;
		$this->all_channels = $all_channels;
		$this->all_payments = $all_payments;
		$this->all_categories = $all_categories;
		
		// Display the template
		parent::display($tpl);
	}

}

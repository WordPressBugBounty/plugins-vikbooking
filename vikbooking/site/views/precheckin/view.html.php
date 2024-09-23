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

class VikbookingViewPrecheckin extends JViewVikBooking
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
		$q = "SELECT `o`.*,(SELECT SUM(`or`.`adults`) FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`o`.`id`) AS `tot_adults` FROM `#__vikbooking_orders` AS `o` WHERE (`o`.`sid`=" . $dbo->quote($sid) . " OR `o`.`idorderota`=" . $dbo->quote($sid) . ") AND `o`.`ts`=" . $dbo->quote($ts) . " AND `o`.`status`='confirmed';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			showSelectVb(JText::translate('VBORDERNOTFOUND'));
			return;
		}
		$order = $dbo->loadAssoc();
		
		$orderrooms = array();
		$q = "SELECT `or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`idtar`,`or`.`optionals`,`or`.`childrenage`,`or`.`t_first_name`,`or`.`t_last_name`,`or`.`roomindex`,`or`.`pkg_id`,`or`.`pkg_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`or`.`otarplan`,`r`.`id` AS `r_reference_id`,`r`.`name`,`r`.`img`,`r`.`idcarat`,`r`.`fromadult`,`r`.`toadult` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`=".$order['id']." AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			showSelectVb(JText::translate('VBORDERNOTFOUND'));
			return;
		}
		$orderrooms = $dbo->loadAssocList();
		$vbo_tn->translateContents($orderrooms, '#__vikbooking_rooms', array('id' => 'r_reference_id'));

		$customer = array();
		$q = "SELECT `c`.*,`co`.`idorder`,`co`.`signature`,`co`.`pax_data`,`co`.`comments`,`co`.`checkindoc` FROM `#__vikbooking_customers` AS `c` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `c`.`id`=`co`.`idcustomer` WHERE `co`.`idorder`=".$order['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$customer = $dbo->loadAssoc();
		}
		if (!count($customer)) {
			// one customer must be assigned to this booking for the pre-checkin
			showSelectVb(JText::translate('VBINSUFDATA'));
			return;
		}
		
		$this->order = $order;
		$this->orderrooms = $orderrooms;
		$this->customer = $customer;
		$this->vbo_tn = $vbo_tn;
		
		parent::display($tpl);
	}
}

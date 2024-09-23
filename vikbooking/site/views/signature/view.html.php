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

class VikbookingViewSignature extends JViewVikBooking
{
	public function display($tpl = null)
	{
		//set noindex instruction for robots
		$document = JFactory::getDocument();
		$document->setMetaData('robots', 'noindex,follow');

		$sid = VikRequest::getString('sid', '', 'request');
		$ts = VikRequest::getString('ts', '', 'request');

		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();
		$vbo_tn = VikBooking::getTranslator();

		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `ts`=" . $dbo->quote($ts) . " AND (`sid`=" . $dbo->quote($sid) . " OR `idorderota`=" . $dbo->quote($sid) . ") AND `status`='confirmed';";
		$dbo->setQuery($q);
		$row = $dbo->loadAssoc();
		if (!$row) {
			VikError::raiseWarning('', 'Booking not found');
			$mainframe->redirect('index.php');
			exit;
		}
		
		$tonight = mktime(23, 59, 59, date('n'), date('j'), date('Y'));
		if ($tonight > $row['checkout']) {
			VikError::raiseWarning('', 'Check-out date is in the past');
			$mainframe->redirect('index.php');
			exit;
		}

		$q = "SELECT `c`.*,`co`.`idorder`,`co`.`signature`,`co`.`pax_data`,`co`.`comments` FROM `#__vikbooking_customers` AS `c` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `c`.`id`=`co`.`idcustomer` WHERE `co`.`idorder`=".(int)$row['id'].";";
		$dbo->setQuery($q);
		$customer = $dbo->loadAssoc();
		if (!$customer) {
			VikError::raiseWarning('', 'Customer not found');
			$mainframe->redirect('index.php');
			exit;
		}

		$orderrooms = array();
		$q = "SELECT `or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`idtar`,`or`.`optionals`,`or`.`roomindex`,`or`.`pkg_id`,`or`.`pkg_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`r`.`id` AS `r_reference_id`,`r`.`name`,`r`.`img`,`r`.`idcarat`,`r`.`fromadult`,`r`.`toadult` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`=".(int)$row['id']." AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
		$dbo->setQuery($q);
		$orderrooms = $dbo->loadAssocList();
		if (!$orderrooms) {
			VikError::raiseWarning('', 'No rooms found');
			$mainframe->redirect('index.php');
			exit;
		}

		$vbo_tn->translateContents($orderrooms, '#__vikbooking_rooms', array('id' => 'r_reference_id'));

		// load assets
		if (VikBooking::loadJquery()) {
			JHtml::fetch('jquery.framework', true, true);
		}
		$document->addScript(VBO_SITE_URI.'resources/signature_pad.js');
		$document->addStyleSheet(VBO_ADMIN_URI.'resources/fonts/vboicomoon.css');

		$this->ord = $row;
		$this->orderrooms = $orderrooms;
		$this->customer = $customer;
		$this->vbo_tn = $vbo_tn;

		// theme
		$theme = VikBooking::getTheme();
		if ($theme != 'default') {
			$thdir = VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'signature';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir . DIRECTORY_SEPARATOR);
			}
		}

		parent::display($tpl);
	}
}

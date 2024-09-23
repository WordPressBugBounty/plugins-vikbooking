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

class VikBookingViewRefundtn extends JViewVikBooking {
	
	function display($tpl = null) {
		// This view is usually called within a modal box, so it does not require the toolbar or page title
		
		$cid = VikRequest::getVar('cid', array(0));
		$id = $cid[0];
		
		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();

		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=".(int)$id." AND `status`!='standby';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			$mainframe->redirect('index.php?option=com_vikbooking');
			exit;
		}
		$row = $dbo->loadAssoc();

		// get booking history instance
		$history_obj = VikBooking::getBookingHistoryInstance();
		$history_obj->setBid($row['id']);
		
		// get payment information
		$payment = VikBooking::getPayment($row['idpayment']);
		$tn_driver = is_array($payment) ? $payment['file'] : null;

		// transaction data validation callback
		$tn_data_callback = function($data) use ($tn_driver) {
			return (is_object($data) && isset($data->driver) && basename($data->driver, '.php') == basename($tn_driver, '.php'));
		};
		// get previous transactions
		$prev_tn_data = $history_obj->getEventsWithData(array('P0', 'PN'), $tn_data_callback);

		if (!is_array($prev_tn_data) || !count($prev_tn_data)) {
			// no previous transactions found
			VikError::raiseWarning('', 'No previous transactions found, unable to issue the refund');
			$mainframe->redirect('index.php?option=com_vikbooking&task=editorder&cid[]=' . $row['id']);
			exit;
		}

		// load all previous refund events, if any
		$refunds = $history_obj->getEventsWithData('RF', null, false);
		$refunds = !is_array($refunds) ? array() : $refunds;
		
		$this->row = $row;
		$this->payment = $payment;
		$this->refunds = $refunds;
		
		// Display the template
		parent::display($tpl);
	}

}

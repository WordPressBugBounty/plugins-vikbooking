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

class VikbookingViewVikbooking extends JViewVikBooking {
	function display($tpl = null) {
		VikBooking::prepareViewContent();
		$dbo = JFactory::getDBO();
		$session = JFactory::getSession();
		//booking modification start
		$mod_booking = array();
		$pmodify_sid = VikRequest::getString('modify_sid', '', 'request');
		$pmodify_id = VikRequest::getInt('modify_id', '', 'request');
		if (!empty($pmodify_sid) && !empty($pmodify_id)) {
			$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=".intval($pmodify_id)." AND `sid`=".$dbo->quote($pmodify_sid)." AND `status`='confirmed' AND `split_stay`=0;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$mod_booking = $dbo->loadAssoc();
				//check if modification is allowed
				$days_to_arrival = 0;
				$now_info = getdate();
				$checkin_info = getdate($mod_booking['checkin']);
				if ($now_info[0] < $checkin_info[0]) {
					while ($now_info[0] < $checkin_info[0]) {
						if (!($now_info['mday'] != $checkin_info['mday'] || $now_info['mon'] != $checkin_info['mon'] || $now_info['year'] != $checkin_info['year'])) {
							break;
						}
						$days_to_arrival++;
						$now_info = getdate(mktime(0, 0, 0, $now_info['mon'], ($now_info['mday'] + 1), $now_info['year']));
					}
				}
				$resmodcanc = VikBooking::getReservationModCanc();
				$resmodcanc = $days_to_arrival < 1 ? 0 : $resmodcanc;
				$resmodcancmin = VikBooking::getReservationModCancMin();
				$mod_allowed = ($resmodcanc > 1 && $resmodcanc != 3 && $days_to_arrival >= $resmodcancmin);
				if (!$mod_allowed) {
					VikError::raiseWarning('', JText::translate('VBOERRCANNOTMODBOOK'));
					$mainframe = JFactory::getApplication();
					$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=booking&sid=".$mod_booking['sid']."&ts=".$mod_booking['ts'], false));
					exit;
				}
				//start session for modifying the booking
				$session->set('vboModBooking', $mod_booking);
			}
		} else {
			//check if a booking modification was previously stored in the session (and not unset) to re-start it
			$cur_mod = $session->get('vboModBooking', '');
			if (is_array($cur_mod) && count($cur_mod)) {
				$mod_booking = $cur_mod;
			}
		}
		$this->mod_booking = $mod_booking;
		//theme
		$theme = VikBooking::getTheme();
		if ($theme != 'default') {
			$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'vikbooking';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir.DS);
			}
		}
		//
		parent::display($tpl);
	}
}

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

class VikbookingViewLoginregister extends JViewVikBooking {
	function display($tpl = null) {
		$dbo = JFactory::getDBO();
		$proomid = VikRequest::getVar('roomid', array());
		$pdays = VikRequest::getInt('days', '', 'request');
		$pcheckin = VikRequest::getInt('checkin', '', 'request');
		$pcheckout = VikRequest::getInt('checkout', '', 'request');
		$proomsnum = VikRequest::getInt('roomsnum', '', 'request');
		$padults = VikRequest::getVar('adults', array());
		$pchildren = VikRequest::getVar('children', array());
		$rooms = array();
		$arrpeople = array();
		for($ir = 1; $ir <= $proomsnum; $ir++) {
			$ind = $ir - 1;
			if (!empty($proomid[$ind])) {
				$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `id`='".intval($proomid[$ind])."' AND `avail`='1';";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() > 0) {
					$takeroom = $dbo->loadAssocList();
					$rooms[$ir] = $takeroom[0];
				}
			}
			if (!empty($padults[$ind])) {
				$arrpeople[$ir]['adults'] = intval($padults[$ind]);
			}else {
				$arrpeople[$ir]['adults'] = 0;
			}
			if (!empty($pchildren[$ind])) {
				$arrpeople[$ir]['children'] = intval($pchildren[$ind]);
			}else {
				$arrpeople[$ir]['children'] = 0;
			}
		}
		$prices = array();
		foreach($rooms as $num => $r) {
			$ppriceid = VikRequest::getString('priceid'.$num, '', 'request');
			if (!empty($ppriceid)) {
				$prices[$num] = intval($ppriceid);
			}
		}
		$selopt = array();
		$q = "SELECT * FROM `#__vikbooking_optionals`;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$optionals = $dbo->loadAssocList();
			foreach($rooms as $num => $r) {
				foreach ($optionals as $opt) {
					$tmpvar = VikRequest::getString('optid'.$num.$opt['id'], '', 'request');
					if (!empty ($tmpvar)) {
						$opt['quan'] = $tmpvar;
						$selopt[$num][] = $opt;
					}
				}
			}
		}
		$this->prices = $prices;
		$this->rooms = $rooms;
		$this->days = $pdays;
		$this->checkin = $pcheckin;
		$this->checkout = $pcheckout;
		$this->selopt = $selopt;
		$this->roomsnum = $proomsnum;
		$this->adults = $padults;
		$this->children = $pchildren;
		$this->arrpeople = $arrpeople;
		//theme
		$theme = VikBooking::getTheme();
		if($theme != 'default') {
			$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'loginregister';
			if(is_dir($thdir)) {
				$this->_setPath('template', $thdir.DS);
			}
		}
		//
		parent::display($tpl);
	}
}

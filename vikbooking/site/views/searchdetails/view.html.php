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

class VikbookingViewSearchdetails extends JViewVikBooking
{
	public function display($tpl = null)
	{
		$vbo_tn = VikBooking::getTranslator();
		$proomid = VikRequest::getInt('roomid', '', 'request');
		$pcheckin = VikRequest::getInt('checkin', '', 'request');
		$pcheckout = VikRequest::getInt('checkout', '', 'request');
		$padults = VikRequest::getInt('adults', '', 'request');
		$pchildren = VikRequest::getInt('children', '', 'request');

		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `id`=".$dbo->quote($proomid)." AND `avail`='1';";
		$dbo->setQuery($q);
		$dbo->execute();
		
		if (!$dbo->getNumRows()) {
			$app = JFactory::getApplication();
			$app->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=roomslist", false));
			$app->close();
		}

		$room = $dbo->loadAssocList();
		$vbo_tn->translateContents($room, '#__vikbooking_rooms');

		$secdiff = $pcheckout - $pcheckin;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = VikBooking::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		$q="SELECT * FROM `#__vikbooking_dispcost` WHERE `idroom`='".$room[0]['id']."' AND `days`='".$daysdiff."' ORDER BY `#__vikbooking_dispcost`.`cost` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$tar=$dbo->loadAssocList();
		} else {
			$q="SELECT * FROM `#__vikbooking_dispcost` WHERE `idroom`='".$room[0]['id']."' ORDER BY `#__vikbooking_dispcost`.`cost` ASC;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$tar=$dbo->loadAssocList();
				$tar[0]['cost']=($tar[0]['cost'] / $tar[0]['days']);
			} else {
				$tar[0]['cost']=0;
			}
		}
		//Closed rate plans on these dates
		$roomrpclosed = VikBooking::getRoomRplansClosedInDates(array($room[0]['id']), $pcheckin, $daysdiff);
		if (count($roomrpclosed) > 0 && array_key_exists($room[0]['id'], $roomrpclosed)) {
			foreach ($tar as $kk => $tt) {
				if (array_key_exists('idprice', $tt) && array_key_exists($tt['idprice'], $roomrpclosed[$room[0]['id']])) {
					unset($tar[$kk]);
				}
			}
			$tar = array_values($tar);
		}
		//
		if (!(count($tar) > 0)) {
			$tar = array(array('cost' => 0));
		}
		$tar = array($tar[0]);
		$tar = VikBooking::applySeasonsRoom($tar, $pcheckin, $pcheckout);

		// apply OBP rules
		$tar = VBORoomHelper::getInstance()->applyOBPRules($tar, $room[0], $padults);

		$this->room = $room[0];
		$this->tar = $tar;
		$this->checkin = $pcheckin;
		$this->checkout = $pcheckout;
		$this->adults = $padults;
		$this->children = $pchildren;
		$this->daysdiff = $daysdiff;
		$this->vbo_tn = $vbo_tn;
		//theme
		$theme = VikBooking::getTheme();
		if ($theme != 'default') {
			$thdir = VBO_SITE_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$theme.DIRECTORY_SEPARATOR.'searchdetails';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir.DIRECTORY_SEPARATOR);
			}
		}
		//
		parent::display($tpl);
	}
}

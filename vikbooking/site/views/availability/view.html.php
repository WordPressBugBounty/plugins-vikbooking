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

class VikbookingViewAvailability extends JViewVikBooking
{
	public function display($tpl = null)
	{
		$room_ids = array_filter((array)VikRequest::getVar('room_ids', array(), 'request', 'int'));
		$psortby = VikRequest::getString('sortby', '', 'request');
		$psortby = !in_array($psortby, array('adults', 'name', 'id')) ? 'adults' : $psortby;
		$psorttype = VikRequest::getString('sorttype', '', 'request');
		$psorttype = $psorttype == 'desc' ? 'DESC' : 'ASC';

		$oclause = "`#__vikbooking_rooms`.`toadult` ".$psorttype.", `#__vikbooking_rooms`.`totpeople` ".$psorttype.", `#__vikbooking_rooms`.`name` ".$psorttype;
		if ($psortby == 'name') {
			$oclause = "`#__vikbooking_rooms`.`name` ".$psorttype;
		} elseif ($psortby == 'id') {
			$oclause = "`#__vikbooking_rooms`.`id` ".$psorttype;
		}

		$dbo = JFactory::getDBO();
		$vbo_tn = VikBooking::getTranslator();

		$q = "SELECT * FROM `#__vikbooking_rooms` WHERE ".(count($room_ids) > 0 ? "`id` IN (".implode(',', $room_ids).") AND " : "")."`avail`='1' ORDER BY ".$oclause.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			$app = JFactory::getApplication();
			$app->redirect("index.php");
			$app->close();
		}
		$rooms=$dbo->loadAssocList();
		$vbo_tn->translateContents($rooms, '#__vikbooking_rooms');

		$pmonth = VikRequest::getInt('month', '', 'request');
		if (!empty($pmonth)) {
			$tsstart=$pmonth;
		} else {
			$oggid = getdate();
			$tsstart = mktime(0, 0, 0, $oggid['mon'], 1, $oggid['year']);
		}
		$oggid = getdate($tsstart);
		if ($oggid['mon'] == 12) {
			$nextmon = 1;
			$year = $oggid['year'] + 1;
		} else {
			$nextmon = $oggid['mon'] + 1;
			$year = $oggid['year'];
		}
		$tsend = mktime(0, 0, 0, $nextmon, 1, $year);
		$today = getdate();
		$firstmonth = mktime(0, 0, 0, $today['mon'], 1, $today['year']);
		$wmonthsel = "<select name=\"month\" onchange=\"document.vbmonths.submit();\">\n";
		$wmonthsel .= "<option value=\"".$firstmonth."\"".($firstmonth==$tsstart ? " selected=\"selected\"" : "").">".VikBooking::sayMonth($today['mon'])." ".$today['year']."</option>\n";
		$futuremonths = 12;
		for ($i = 1; $i <= $futuremonths; $i++) {
			$newts = getdate($firstmonth);
			if ($newts['mon'] == 12) {
				$nextmon = 1;
				$year = $newts['year'] + 1;
			} else {
				$nextmon = $newts['mon'] + 1;
				$year = $newts['year'];
			}
			$firstmonth = mktime(0, 0, 0, $nextmon, 1, $year);
			$newts = getdate($firstmonth);
			$wmonthsel .= "<option value=\"".$firstmonth."\"".($firstmonth==$tsstart ? " selected=\"selected\"" : "").">".VikBooking::sayMonth($newts['mon'])." ".$newts['year']."</option>\n";
		}
		$wmonthsel .= "</select>\n";

		$busy = array();
		$q = "SELECT * FROM `#__vikbooking_busy` WHERE ".(count($room_ids) > 0 ? "`idroom` IN (".implode(',', $room_ids).") AND " : "")."(`checkin`>=".$tsstart." OR `checkout`>=".$tsstart.");";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$all_busy = $dbo->loadAssocList();
			foreach ($all_busy as $brecord) {
				$busy[$brecord['idroom']][] = $brecord;
			}
		}

		$this->rooms = $rooms;
		$this->tsstart = $tsstart;
		$this->wmonthsel = $wmonthsel;
		$this->busy = $busy;
		$this->vbo_tn = $vbo_tn;
		//theme
		$theme = VikBooking::getTheme();
		if ($theme != 'default') {
			$thdir = VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'availability';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir . DIRECTORY_SEPARATOR);
			}
		}
		//
		parent::display($tpl);
	}
}

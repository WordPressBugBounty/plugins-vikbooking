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

class VikbookingViewPackageslist extends JViewVikBooking {
	function display($tpl = null) {
		VikBooking::prepareViewContent();
		$dbo = JFactory::getDBO();
		$vbo_tn = VikBooking::getTranslator();
		$psortby = VikRequest::getString('sortby', '', 'request');
		$psortby = !in_array($psortby, array('cost', 'name', 'id', 'dfrom')) ? 'dfrom' : $psortby;
		$psorttype = VikRequest::getString('sorttype', '', 'request');
		$psorttype = $psorttype == 'desc' ? 'DESC' : 'ASC';
		$preslim = VikRequest::getInt('reslim', '', 'request');
		$preslim = empty($preslim) || $preslim < 1 ? 20 : $preslim;
		$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
		
		$nowts = time();
		$packages = array();
		$navig = '';

		$q = "SELECT SQL_CALC_FOUND_ROWS * FROM `#__vikbooking_packages` WHERE `dto`>=".$nowts." ORDER BY `".$psortby."` ".$psorttype;
		$dbo->setQuery($q, $lim0, $preslim);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$packages = $dbo->loadAssocList();
			//pagination
			$dbo->setQuery('SELECT FOUND_ROWS();');
			jimport('joomla.html.pagination');
			$pageNav = new JPagination($dbo->loadResult(), $lim0, $preslim);
			$navig = $pageNav->getPagesLinks();
			//
			$vbo_tn->translateContents($packages, '#__vikbooking_packages');
			if ($vbo_tn->default_lang != $vbo_tn->current_lang && $psortby == 'name') {
				//VBO 1.9 - resort packages by name after the translations have been applied
				$resort_map = array();
				foreach ($packages as $k => $v) {
					$resort_map[$k] = $v['name'];
				}
				asort($resort_map);
				$resorted = array();
				foreach ($resort_map as $k => $v) {
					$resorted[$k] = $packages[$k];
				}
				$packages = $resorted;
				unset($resorted);
			}
			foreach ($packages as $pk => $pv) {
				$q = "SELECT `pr`.`idroom`,`r`.`name`,`r`.`img`,`r`.`fromadult`,`r`.`toadult`,`r`.`fromchild`,`r`.`tochild`,`r`.`totpeople`,`r`.`params` FROM `#__vikbooking_packages_rooms` AS `pr` LEFT JOIN `#__vikbooking_rooms` `r` ON `r`.`id`=`pr`.`idroom` AND `r`.`avail`=1 WHERE `pr`.`idpackage`=".(int)$pv['id'].";";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() > 0) {
					$pkg_rooms = $dbo->loadAssocList();
					$vbo_tn->translateContents($pkg_rooms, '#__vikbooking_rooms', array('id' => 'idroom'));
					$packages[$pk]['rooms'] = $pkg_rooms;
				}
			}
		}
		$this->packages = $packages;
		$this->navig = $navig;
		$this->vbo_tn = $vbo_tn;
		//theme
		$theme = VikBooking::getTheme();
		if ($theme != 'default') {
			$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'packageslist';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir.DS);
			}
		}
		//
		parent::display($tpl);
	}
}

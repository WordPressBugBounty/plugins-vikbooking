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

class VikbookingViewPackagedetails extends JViewVikBooking {
	function display($tpl = null) {
		$dbo = JFactory::getDBO();
		$vbo_tn = VikBooking::getTranslator();
		$pkgid = VikRequest::getInt('pkgid', '', 'request');
		$pitemid = VikRequest::getInt('Itemid', '', 'request');
		$q = "SELECT SQL_CALC_FOUND_ROWS * FROM `#__vikbooking_packages` WHERE `id`='".(int)$pkgid."' AND `dto`>=".time().";";
		$dbo->setQuery($q);
		$dbo->execute();
		if($dbo->getNumRows() == 1) {
			$package = $dbo->loadAssoc();
			$vbo_tn->translateContents($package, '#__vikbooking_packages');
			$q = "SELECT `pr`.`idroom`,`r`.`name`,`r`.`img`,`r`.`units`,`r`.`moreimgs`,`r`.`fromadult`,`r`.`toadult`,`r`.`fromchild`,`r`.`tochild`,`r`.`smalldesc`,`r`.`totpeople`,`r`.`mintotpeople`,`r`.`params`,`r`.`imgcaptions` FROM `#__vikbooking_packages_rooms` AS `pr` LEFT JOIN `#__vikbooking_rooms` `r` ON `r`.`id`=`pr`.`idroom` AND `r`.`avail`=1 WHERE `pr`.`idpackage`=".(int)$package['id'].";";
			$dbo->setQuery($q);
			$dbo->execute();
			if($dbo->getNumRows() > 0) {
				$pkg_rooms = $dbo->loadAssocList();
				$vbo_tn->translateContents($pkg_rooms, '#__vikbooking_rooms', array('id' => 'idroom'));
				$package['rooms'] = $pkg_rooms;
			}
			$this->package = $package;
			$this->vbo_tn = $vbo_tn;
			//theme
			$theme = VikBooking::getTheme();
			if($theme != 'default') {
				$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'packagedetails';
				if(is_dir($thdir)) {
					$this->_setPath('template', $thdir.DS);
				}
			}
			//
			parent::display($tpl);
		}else {
			$mainframe = JFactory::getApplication();
			//no need to set an error as it was probably already raised
			//VikError::raiseWarning('', JText::translate('VBOPKGNOTFOUND'));
			$mainframe->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=packageslist".(!empty($pitemid) ? "&Itemid=".$pitemid : ""), false));
			exit;
		}
	}
}

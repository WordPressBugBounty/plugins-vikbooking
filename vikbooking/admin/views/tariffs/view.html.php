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

class VikBookingViewTariffs extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		if (!JFactory::getUser()->authorise('core.vbo.pricing', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		$cid = VikRequest::getVar('cid', array(0));
		$aid = $cid[0];

		$dbo = JFactory::getDBO();
		$mainframe = JFactory::getApplication();
		if (empty($aid)) {
			$q = "SELECT `id` FROM `#__vikbooking_rooms` ORDER BY `#__vikbooking_rooms`.`name` ASC LIMIT 1";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() == 1) {
				$aid = $dbo->loadResult();
			}
		}
		if (empty($aid)) {
			VikError::raiseWarning('', 'No Rooms.');
			$mainframe->redirect("index.php?option=com_vikbooking&task=rooms");
			exit;
		}
		$q = "SELECT `id`,`name`,`img`,`idcat` FROM `#__vikbooking_rooms` WHERE `id`=".$dbo->quote($aid).";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() != 1) {
			VikError::raiseWarning('', 'No Rooms.');
			$mainframe->redirect("index.php?option=com_vikbooking&task=rooms");
			exit;
		}
		$roomrows = $dbo->loadAssoc();
		$q = "SELECT * FROM `#__vikbooking_prices`;";
		$dbo->setQuery($q);
		$dbo->execute();
		$prices = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : "";
		$pnewtar = VikRequest::getString('newdispcost', '', 'request');
		$pddaysfrom = VikRequest::getInt('ddaysfrom', '', 'request');
		$pddaysto = VikRequest::getInt('ddaysto', '', 'request');
		if (!empty($pnewtar) && !empty($pddaysfrom) && is_array($prices)) {
			if (empty($pddaysto) || $pddaysfrom == $pddaysto) {
				foreach ($prices as $pr) {
					$tmpvarone = VikRequest::getFloat('dprice'.$pr['id'], '', 'request');
					if (!empty($tmpvarone)) {
						$tmpvartwo = VikRequest::getString('dattr'.$pr['id'], '', 'request');
						$multipattr = is_numeric($tmpvartwo) ? true : false;
						$safeq = "SELECT `id` FROM `#__vikbooking_dispcost` WHERE `days`=".$dbo->quote($pddaysfrom)." AND `idroom`='".$roomrows['id']."' AND `idprice`='".$pr['id']."';";
						$dbo->setQuery($safeq);
						$dbo->execute();
						if ($dbo->getNumRows() == 0) {
							$q = "INSERT INTO `#__vikbooking_dispcost` (`idroom`,`days`,`idprice`,`cost`,`attrdata`) VALUES('".$roomrows['id']."',".$dbo->quote($pddaysfrom).",'".$pr['id']."','".($tmpvarone * $pddaysfrom)."',".($multipattr ? "'".($tmpvartwo  * $pddaysfrom)."'" : $dbo->quote($tmpvartwo)).");";
							$dbo->setQuery($q);
							$dbo->execute();
						} elseif ($dbo->getNumRows() == 1) {
							$upd_id = $dbo->loadResult();
							$q = "UPDATE `#__vikbooking_dispcost` SET `cost`='".($tmpvarone * $pddaysfrom)."', `attrdata`=".($multipattr ? "'".($tmpvartwo  * $pddaysfrom)."'" : $dbo->quote($tmpvartwo))." WHERE `id`=".(int)$upd_id." AND `days`=".$dbo->quote($pddaysfrom)." AND `idroom`='".$roomrows['id']."' AND `idprice`='".$pr['id']."' LIMIT 1;";
							$dbo->setQuery($q);
							$dbo->execute();
						}
					}
				}
			} else {
				$pddaysto = intval($pddaysto) > 365 ? 365 : $pddaysto;
				for ($i = intval($pddaysfrom); $i <= intval($pddaysto); $i++) {
					foreach ($prices as $pr) {
						$tmpvarone = VikRequest::getFloat('dprice'.$pr['id'], '', 'request');
						if (!empty($tmpvarone)) {
							$tmpvartwo = VikRequest::getString('dattr'.$pr['id'], '', 'request');
							$multipattr = is_numeric($tmpvartwo) ? true : false;
							$safeq = "SELECT `id` FROM `#__vikbooking_dispcost` WHERE `days`=".$dbo->quote($i)." AND `idroom`='".$roomrows['id']."' AND `idprice`='".$pr['id']."';";
							$dbo->setQuery($safeq);
							$dbo->execute();
							if ($dbo->getNumRows() == 0) {
								$q = "INSERT INTO `#__vikbooking_dispcost` (`idroom`,`days`,`idprice`,`cost`,`attrdata`) VALUES('".$roomrows['id']."',".$dbo->quote($i).",'".$pr['id']."','".($tmpvarone * $i)."',".($multipattr ? "'".($tmpvartwo  * $i)."'" : $dbo->quote($tmpvartwo)).");";
								$dbo->setQuery($q);
								$dbo->execute();
							} elseif ($dbo->getNumRows() == 1) {
								$upd_id = $dbo->loadResult();
								$q = "UPDATE `#__vikbooking_dispcost` SET `cost`='".($tmpvarone * $i)."', `attrdata`=".($multipattr ? "'".($tmpvartwo  * $i)."'" : $dbo->quote($tmpvartwo))." WHERE `id`=".(int)$upd_id." AND `days`=".$dbo->quote($i)." AND `idroom`='".$roomrows['id']."' AND `idprice`='".$pr['id']."' LIMIT 1;";
								$dbo->setQuery($q);
								$dbo->execute();
							}
						}
					}
				}
			}
		}

		$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `idroom`=" . (int)$roomrows['id'] . " ORDER BY `#__vikbooking_dispcost`.`days` ASC, `#__vikbooking_dispcost`.`idprice` ASC;";
		$dbo->setQuery($q);
		$rows = $dbo->loadAssocList();

		$q = "SELECT `id`,`name` FROM `#__vikbooking_rooms` ORDER BY `#__vikbooking_rooms`.`name` ASC;";
		$dbo->setQuery($q);
		$allc = $dbo->loadAssocList();

		$this->roomrows = $roomrows;
		$this->rows = $rows;
		$this->prices = $prices;
		$this->allc = $allc;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINTARIFFETITLE'), 'vikbooking');
		JToolBarHelper::save('cancel', JText::translate('VBMAINTARIFFEBACK'));
		JToolBarHelper::spacer();
		if (JFactory::getUser()->authorise('core.delete', 'com_vikbooking')) {
			JToolBarHelper::deleteList(JText::translate('VBDELCONFIRM'), 'removetariffs', JText::translate('VBMAINTARIFFEDEL'));
			JToolBarHelper::spacer();
			JToolBarHelper::spacer();
		}
	}
}

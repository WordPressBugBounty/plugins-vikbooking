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

class VikBookingViewManageprice extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		$dbo = JFactory::getDBO();

		$cid = VikRequest::getVar('cid', array(0));
		if (!empty($cid[0])) {
			$id = $cid[0];
		}

		$row = [];

		if (!empty($cid[0])) {
			$q = "SELECT * FROM `#__vikbooking_prices` WHERE `id`=" . (int)$id;
			$dbo->setQuery($q, 0, 1);
			$row = $dbo->loadAssoc();
			if (!$row) {
				VikError::raiseWarning('', 'Not found.');
				$mainframe = JFactory::getApplication();
				$mainframe->redirect("index.php?option=com_vikbooking&task=prices");
				exit;
			}
		}

		$q = $dbo->getQuery(true)
			->select([
				$dbo->qn('id'),
				$dbo->qn('name'),
			])
			->from($dbo->qn('#__vikbooking_prices'))
			->where($dbo->qn('derived_id') . ' = 0')
			->where($dbo->qn('derived_data') . ' IS NULL');
		if ($row) {
			$q->where($dbo->qn('id') . ' != ' . $row['id']);
		}
		$dbo->setQuery($q);

		$parent_rates = [];
		foreach ($dbo->loadAssocList() as $parent_rate) {
			$parent_rates[$parent_rate['id']] = $parent_rate['name'];
		}

		$this->row = $row;
		$this->parent_rates = $parent_rates;

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		$cid = VikRequest::getVar('cid', array(0));
		
		if (!empty($cid[0])) {
			//edit
			JToolBarHelper::title(JText::translate('VBMAINPRICETITLEEDIT'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
				JToolBarHelper::apply( 'updatepricestay', JText::translate('VBSAVE'));
				JToolBarHelper::spacer();
				JToolBarHelper::save( 'updateprice', JText::translate('VBSAVECLOSE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel( 'cancelprice', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		} else {
			//new
			JToolBarHelper::title(JText::translate('VBMAINPRICETITLENEW'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
				JToolBarHelper::save( 'createprice', JText::translate('VBSAVE'));
				JToolBarHelper::spacer();
				JToolBarHelper::custom('createprice_new', 'save-new', 'save-new', JText::translate('VBSAVENEW'), false, false);
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel( 'cancelprice', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		}
	}
}

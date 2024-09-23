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

class VikBookingViewTranslations extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		$app = JFactory::getApplication();
		$vbo_tn = VikBooking::getTranslator();

		if ($app->input->getInt('normalize')) {
			// fixer can also be executed manually
			if ($vbo_tn->normalizeTnTableNames()) {
				$app->enqueueMessage('OK');
			} else {
				$app->enqueueMessage('NOK', 'error');
			}
		}
		
		$this->vbo_tn = $vbo_tn;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINTRANSLATIONSTITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.create', 'com_vikbooking') || JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
			JToolBarHelper::apply( 'savetranslationstay', JText::translate('VBSAVE'));
			JToolBarHelper::spacer();
			JToolBarHelper::save( 'savetranslation', JText::translate('VBSAVECLOSE'));
			JToolBarHelper::spacer();
		}
		JToolBarHelper::cancel( 'cancel', JText::translate('VBBACK'));
		JToolBarHelper::spacer();
	}
}

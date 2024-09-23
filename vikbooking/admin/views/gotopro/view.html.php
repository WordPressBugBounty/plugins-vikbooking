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

/**
 * @wponly this View is only for WP
 */

// import Joomla view library
jimport('joomla.application.component.view');

class VikBookingViewGotopro extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		VikBookingLoader::import('update.license');

		$hidead = VikRequest::getInt('hidead', 0, 'request');
		if ($hidead > 0) {
			VikBookingLicense::setVcmAd(false);
		}

		$lic_key = VikBookingLicense::getKey();
		$lic_date = VikBookingLicense::getExpirationDate();
		$is_pro = VikBookingLicense::isPro();

		if ($is_pro > 0) {
			$tpl = 'pro';
		}
		
		$this->lic_key = $lic_key;
		$this->lic_date = $lic_date;
		$this->is_pro = $is_pro;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::translate('VBMAINGOTOPROTITLE'), 'vikbooking');
	}
}

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

class VikBookingViewGetpro extends JViewVikBooking {
	
	function display($tpl = null) {
		// Set the toolbar
		$this->addToolBar();

		VikBookingLoader::import('update.changelog');
		VikBookingLoader::import('update.license');

		$version = VikRequest::getString('version', '', 'request');
		if (!empty($version)) {
			/**
			 * Download Changelog
			 * 
			 * @since 	1.2.12
			 */
			$http = new JHttp;

			$url = 'https://vikwp.com/api/?task=products.changelog';

			$data = array(
				'sku' 		=> 'vbo',
				'version' 	=> $version,
			);

			$response = $http->post($url, $data);

			if ($response->code == 200) {
				VikBookingChangelog::store(json_decode($response->body));
			}
		}

		$changelog = VikBookingChangelog::build();
		$lic_key = VikBookingLicense::getKey();
		$lic_date = VikBookingLicense::getExpirationDate();
		$is_pro = VikBookingLicense::isPro();

		if (!$is_pro) {
			VikError::raiseWarning('', JText::translate('VBONOPROERROR'));
			JFactory::getApplication()->redirect('index.php?option=com_vikbooking&view=gotopro');
			exit;
		}
		
		$this->changelog = $changelog;
		$this->lic_key = $lic_key;
		$this->lic_date = $lic_date;
		$this->is_pro = $is_pro;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		JToolBarHelper::title(JText::translate('VBMAINGETPROTITLE'), 'vikbooking');
	}

}

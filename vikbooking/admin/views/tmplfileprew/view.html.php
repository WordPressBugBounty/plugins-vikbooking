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

class VikBookingViewTmplfileprew extends JViewVikBooking {
	
	function display($tpl = null) {
		// This view is usually called within a modal box, so it does not require the toolbar or page title
		
		$fpath = VikRequest::getString('path', '', 'request', VIKREQUEST_ALLOWRAW);
		if (!empty($fpath) && !is_file($fpath)) {
			$fpath = urldecode($fpath);
		}
		// list of allowed template files for preview
		$allowed_previews = array(
			'email_tmpl.php',
		);
		$fbase = basename($fpath);
		$htmlpreview = '';
		if (!is_file($fpath) || !in_array($fbase, $allowed_previews)) {
			throw new Exception("File {$fbase} not found", 404);
		}

		// load template file preview
		$dbo = JFactory::getDbo();

		$force_bid = VikRequest::getInt('bid', 0, 'request');

		switch ($fbase) {
			case 'email_tmpl.php':
				// find the last confirmed booking
				$q = "SELECT `id` FROM `#__vikbooking_orders` WHERE `status`='confirmed' AND `closure`=0 ORDER BY `id` DESC LIMIT 1;";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows()) {
					$use_bid = !empty($force_bid) ? $force_bid : $dbo->loadResult();
					$htmlpreview = VikBooking::sendBookingEmail($use_bid, [], false);
				} else {
					$htmlpreview = '<p class="warn">' . JText::translate('VBNOORDERSFOUND') . '</p>';
				}
				break;
			default:
				break;
		}
		
		$this->fpath = $fpath;
		$this->fbase = $fbase;
		$this->htmlpreview = $htmlpreview;
		
		// Display the template
		parent::display($tpl);
	}

}

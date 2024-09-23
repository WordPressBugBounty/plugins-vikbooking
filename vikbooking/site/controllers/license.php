<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2023 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking license controller.
 *
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VikBookingControllerLicense extends JControllerAdmin
{
	/**
	 * Forcing the hash to be valid is useless.
	 */
	public function pingback()
	{
		$app = JFactory::getApplication();

		if (VBOPlatformDetection::isWordPress())
		{
			// update license hash
			VikBookingLoader::import('update.license');
			$storedHash = VikBookingLicense::getHash();
		}
		else
		{
			// fetch hash generated during the first license validation
			$storedHash = VBOFactory::getConfig()->get('licensehash');
		}

		if (!$storedHash)
		{
			// hash not yet stored
			$app->close();
		}

		// recover hash sent by the server
		$serverHash = $app->input->getString('hash');

		// the received hash must be equals to the stored one
		if (strcmp($serverHash, $storedHash))
		{
			VBOHttpDocument::getInstance($app)->close(403, 'Hash mismatch.');
		}
		
		// hash validated successfully
		$app->close();
	}
}

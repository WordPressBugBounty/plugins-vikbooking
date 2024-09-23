<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking Web App Push subscriptions handling class.
 * 
 * @since 		1.16.5 (J) - 1.6.5 (WP)
 * 
 * @requires 	Vik Channel Manager >= 1.8.20
 */
final class VBOWebappPush
{
	/**
	 * Returns the configuration settings for the Push subscriptions.
	 * 
	 * @return 	array
	 */
	public static function getConfig()
	{
		static $pushConfig = null;

		if ($pushConfig !== null) {
			return $pushConfig;
		}

		$application_key = '';

		if (class_exists('VCMPushSubscription')) {
			// the Channel Manager is required
			$application_key = VCMPushSubscription::getApplicationKey();
		}

		$pushConfig = [
			'application_key' => $application_key,
			'ajax_url' 		  => VBOFactory::getPlatform()->getUri()->ajax('index.php?option=com_vikchannelmanager&task=push.subscriptions', false),
		];

		return $pushConfig;
	}
}

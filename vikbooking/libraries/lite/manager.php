<?php
/** 
 * @package     VikBooking - Libraries
 * @subpackage  lite
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Manager class used to setup the LITE version of the plugin.
 *
 * @since 1.5.11
 */
abstract class VikBookingLiteManager
{
	/**
	 * Flag used to avoid initializing the setup more than once.
	 * 
	 * @var boolean
	 */
	private static $setup = false;

	/**
	 * Accessor used to start the setup.
	 * 
	 * @param 	mixed  $helper  The implementor instance or a static class.
	 * 
	 * @return 	void
	 */
	final public static function setup($helper = null)
	{
		if (!static::$setup && !static::guessPro())
		{
			if (!$helper)
			{
				// use the default implementor
				VikBookingLoader::import('lite.helper');
				$helper = new VikBookingLiteHelper();
			}

			// set up only once and in case of missing PRO version
			static::$setup = static::doSetup($helper);
		}
	}

	/**
	 * Helper method used to assume whether the PRO version is
	 * installed or not, because it is not enough to check whether
	 * a PRO license is registered. In example, we cannot automatically
	 * re-enable the LITE restrictions after a PRO license expires.
	 * 
	 * @return 	boolean
	 */
	public static function guessPro()
	{
		// immediately check whether we have a valid PRO license
		if (VikBookingLicense::isPro())
		{
			// in case of downgrade to the free version, display system message
			if (!JFile::exists(VBO_ADMIN_PATH . '/payments/paypal.php'))
			{
				$app = JFactory::getApplication();

				if ($app->input->getString('view') != 'getpro')
				{
					// display system message
					$app->enqueueMessage(
						sprintf(
							__('Your Pro version of Vik Booking was downgraded to the Free version after the update. Please visit the <a href="%s">Go to Pro</a> page to restore your Pro settings and complete the update process.', 'vikbooking'),
							'admin.php?page=vikbooking&view=getpro'
						),
						'warning'
					);
				}
			}

			return true;
		}

		// Missing PRO license or expired... First make sure the
		// license key was specified.
		if (!VikBookingLicense::getKey())
		{
			// missing license key, never allow usage of PRO features
			return false;
		}

		// Check whether the PRO license was ever installed, which
		// can be easily done by looking for the PayPal integration.
		return JFile::exists(VBO_ADMIN_PATH . '/payments/paypal.php');
	}

	/**
	 * Setup implementor.
	 * 
	 * @param 	mixed  $helper  The implementor instance or a static class.
	 * 
	 * @return 	boolean
	 */
	protected static function doSetup($helper)
	{
		/**
		 * Filters which capabilities a role has.
		 *
		 * @since 2.0.0
		 *
		 * @param 	bool[]  $capabilities  Array of key/value pairs where keys represent a capability name and boolean values
		 *                                 represent whether the role has that capability.
		 * @param 	string  $cap           Capability name.
		 * @param 	string  $name          Role name.
		 */
		add_filter('role_has_cap', array($helper, 'restrictCapabilities'));

		/**
		 * Dynamically filter a user's capabilities.
		 *
		 * @since 2.0.0
		 * @since 3.7.0 Added the `$user` parameter.
		 *
		 * @param 	bool[]    $allcaps  Array of key/value pairs where keys represent a capability name
		 *                              and boolean values represent whether the user has that capability.
		 * @param 	string[]  $caps     Required primitive capabilities for the requested capability.
		 * @param 	array     $args     Arguments that accompany the requested capability check.
		 * @param 	WP_User   $user     The user object.
		 */
		add_filter('user_has_cap', array($helper, 'restrictCapabilities'));

		/**
		 * Fires before the controller of VikBooking is dispatched.
		 * Useful to require libraries and to check user global permissions.
		 *
		 * @since 1.0
		 */
		add_action('vikbooking_before_dispatch', array($helper, 'disableSetNewRatesTask'));
		add_action('vikbooking_before_dispatch', array($helper, 'displayBanners'));
		add_action('vikbooking_before_dispatch', array($helper, 'hideProFeatures'));
		add_action('vikbooking_before_dispatch', array($helper, 'disableEditBusyRoomFeatures'));
		add_action('vikbooking_before_dispatch', array($helper, 'listenTosFieldSavingTask'));

		/**
		 * Fires after the controller displays the view.
		 *
		 * @param 	JView  $view  The view instance.
		 *
		 * @since 	1.0
		 */
		add_action('vikbooking_after_display_customf', array($helper, 'displayTosFieldManagementForm'));
	}
}

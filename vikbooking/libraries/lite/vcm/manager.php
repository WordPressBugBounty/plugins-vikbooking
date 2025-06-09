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
 * Manager class used to sponsor the missing VikChannelManager features.
 *
 * @since 1.8
 */
abstract class VikChannelManagerLiteManager
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
		if (!static::$setup && !VikBookingLicense::hasVcm())
		{
			if (!$helper)
			{
				// use the default implementor
				VikBookingLoader::import('lite.vcm.helper');
				$helper = new VikChannelManagerLiteHelper();
			}

			// set up only once and in case VCM is missing
			static::$setup = static::doSetup($helper);
		}
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
		 * Fires before the controller of VikBooking/VikChannelManager is dispatched.
		 * Useful to require libraries and to check user global permissions.
		 *
		 * @since 1.0
		 */
		add_action('init', [$helper, 'hijackChannelManager'], 1);
		add_action('vikbooking_before_dispatch', [$helper, 'displayViewBanners']);
		add_action('vikbooking_before_dispatch', [$helper, 'displayWidgetBanners']);
		add_action('vikbooking_before_dispatch', [$helper, 'hijackWidgetMultitask']);
	}
}

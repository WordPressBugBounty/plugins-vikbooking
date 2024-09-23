<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	update
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class used to handle the software license.
 *
 * @since 1.0
 */
class VikBookingLicense
{
	/**
	 * Gets the current License Key.
	 *
	 * @return 	string
	 */
	public static function getKey()
	{
		return get_option('vikbooking_license_key', '');
	}

	/**
	 * Updates the current License Key.
	 *
	 * @param 	string 	$key
	 *
	 * @return 	void
	 */
	public static function setKey($key)
	{
		/**
		 * In case of multi-site, update the option on all the network sites.
		 *
		 * @since 1.4.0
		 */
		JFactory::getApplication()->set('vikbooking_license_key', (string) $key, $network = true);
	}

	/**
	 * Gets the current License Expiration Timestamp.
	 *
	 * @return 	int
	 */
	public static function getExpirationDate()
	{
		return (int)get_option('vikbooking_license_expdate', 0);
	}

	/**
	 * Updates the current License Expiration Timestamp.
	 *
	 * @param 	int 	$time
	 *
	 * @return 	void
	 */
	public static function setExpirationDate($time)
	{
		/**
		 * In case of multi-site, update the option on all the network sites.
		 *
		 * @since 1.4.0
		 */
		JFactory::getApplication()->set('vikbooking_license_expdate', (int) $time, $network = true);
	}

	/**
	 * Checks whether the software version is Pro.
	 *
	 * @return 	boolean
	 */
	public static function isPro()
	{
		return (strlen(self::getKey()) && (!self::isExpired() || self::hasVcm()));
	}

	/**
	 * Checks whether the VCM plugin is installed.
	 *
	 * @return 	boolean
	 */
	public static function hasVcm()
	{
		return is_dir(VCM_SITE_PATH);
	}

	/**
	 * Checks whether the ad for VCM was hid.
	 *
	 * @return 	boolean
	 */
	public static function hideVcmAd()
	{
		$hide = (int) get_option('vikbooking_hide_vcmad', 0);
		return ($hide > 0);
	}

	/**
	 * Updates the value for the ad of VCM.
	 *
	 * @param 	boolean  true for showing the ad (0), false otherwise (1).
	 * 
	 * @return  void
	 */
	public static function setVcmAd($value)
	{
		/**
		 * In case of multi-site, update the option on all the network sites.
		 *
		 * @since 1.4.0
		 */
		JFactory::getApplication()->set('vikbooking_hide_vcmad', (int) !$value, $network = true);
	}

	/**
	 * Checks whether the License Key is expired.
	 *
	 * @return 	boolean
	 */
	public static function isExpired()
	{
		return (strlen(self::getKey()) && self::getExpirationDate() < time());
	}

	/**
	 * Gets the current License Hash.
	 *
	 * @return 	string
	 */
	public static function getHash()
	{
		$hash = get_option('vikbooking_license_hash', '');
		
		if (empty($hash))
		{
			$hash = self::setHash();
		}

		return $hash;
	}

	/**
	 * Sets and returns the License Hash.
	 *
	 * @return 	string
	 */
	public static function setHash()
	{
		$hash = md5(JUri::root() . uniqid());
		update_option('vikbooking_license_hash', $hash);

		return $hash;
	}

	/**
	 * Registers some options upon installation of the plugin.
	 *
	 * @return 	void
	 */
	public static function install()
	{
		update_option('vikbooking_license_key', '');
		update_option('vikbooking_license_expdate', 0);
		update_option('vikbooking_license_hash', '');
		update_option('vikbooking_hide_vcmad', 0);
	}

	/**
	 * Deletes all the options upon uninstallation of the plugin.
	 *
	 * @return 	void
	 */
	public static function uninstall()
	{
		delete_option('vikbooking_license_key');
		delete_option('vikbooking_license_expdate');
		delete_option('vikbooking_license_hash');
		delete_option('vikbooking_hide_vcmad');
	}
}

<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Notification elements registry to store a record for the Notification Center.
 * 
 * @since 	1.16.8 (J) - 1.6.8 (WP)
 */
final class VBONotificationElements extends JObject
{
	/**
	 * Valid notification group enumerations.
	 * 
	 * @var  array
	 */
	private $groupEnums = [
		// notifications from Website
		'website',
		// notifications from OTAs
		'otas',
		// notifications from Channel Manager
		'cm',
		// notifications from Guests
		'guests',
		// notifications from Operators
		'operators',
	];

	/**
	 * Default notification type.
	 * 
	 * @var  string
	 */
	private $defaultType = 'info';

	/**
	 * Determines and returns the group to which the notification belongs.
	 * 
	 * @return 	string
	 */
	public function getGroup()
	{
		// check if the group was already determined
		$group = (string) $this->get('_group', '');

		if (!$group) {
			// access the notification sender property
			$group = strtolower((string) $this->get('sender', $this->groupEnums[0]));

			// validate the group property
			$group = in_array($group, $this->groupEnums) ? $group : $this->groupEnums[0];

			// cache determined group
			$this->set('_group', $group);
		}

		return $group;
	}

	/**
	 * Returns the notification type.
	 * 
	 * @return 	string
	 */
	public function getType()
	{
		// access the notification type
		$type = strtolower((string) $this->get('type', $this->defaultType));

		// ensure the maximum length of 32 chars is respected
		return $this->shortenString($type, 32, $this->defaultType);
	}

	/**
	 * Returns the notification title.
	 * 
	 * @return 	string
	 */
	public function getTitle()
	{
		// access the notification title
		$title = (string) $this->get('title', '');

		// try to guess the title
		if (!$title) {
			// check for virtual credit card balance
			if (strpos($this->getType(), 'vcc_balance') === 0) {
				$title = JText::translate('VBO_VCC_BALANCE');
				if ($title == 'VBO_VCC_BALANCE') {
					$title = ucwords(str_replace('_', ' ', $this->getType()));
				}
			}
		}

		// ensure the maximum length of 64 chars is respected
		return $this->shortenString($title, 64);
	}

	/**
	 * Returns the notification summary.
	 * 
	 * @return 	string
	 */
	public function getSummary()
	{
		// access the notification summary
		$summary = (string) $this->get('summary', '');

		// normalize the summary, if needed
		if ($summary && $this->getChannel() && !strcasecmp($this->getType(), 'lvf')) {
			// listing verification framework (LVF)
			$summary = JText::sprintf('VBO_VERIFY_LISTING_INFO', $summary);
		}

		// ensure the maximum length of 256 chars is respected
		return $this->shortenString($summary, 256);
	}

	/**
	 * Builds and returns the notification call-to-action data.
	 * 
	 * @return 	null|string
	 */
	public function getCallToActionData()
	{
		$cta_data = [];

		// check if a widget identifier was provided
		$widget = (string) $this->get('widget', '');
		if ($widget) {
			$cta_data['widget'] = $widget;
		}

		// check if some widget options were provided
		$widget_options = (array) $this->get('widget_options', []);
		if ($widget_options) {
			$cta_data['widget_options'] = $widget_options;
		}

		// check if a notification URL was provided
		$cta_url = (string) $this->get('cta_url', $this->get('url'));
		if ($cta_url) {
			$cta_data['url'] = $cta_url;
		}

		// check if a custom label was provided
		if ($cta_data && is_string($this->get('label'))) {
			$cta_data['label'] = $this->get('label');
		}

		if (!$cta_data) {
			// attempt to determine the CTA payload to set
			if (strpos($this->getType(), 'vcc_balance') === 0 && ($this->getReservationID() || $this->getOTAReservationID())) {
				// set call-to-action for Virtual Terminal admin-widget for VCC balance
				$cta_data = [
					'label'  => JText::translate('VBO_CC_DOCHARGE'),
					'widget' => 'virtual_terminal',
					'widget_options' => [
						'bid' => $this->getReservationID() ?: $this->getOTAReservationID(),
					],
				];
			} elseif (!strcasecmp($this->getType(), 'guest_message') && ($this->getReservationID() || $this->getOTAReservationID())) {
				// set call-to-action for Guest Messages admin-widget to reply to the guest message
				$cta_data = [
					'label'  => JText::translate('VBO_REPLY'),
					'widget' => 'guest_messages',
					'widget_options' => [
						'bid' => $this->getReservationID() ?: $this->getOTAReservationID(),
					],
				];
			} elseif (!strcasecmp($this->getType(), 'ob') && $this->getReservationID()) {
				// set call-to-action for Bookings Calendar admin-widget in case of overbooking
				$cta_data = [
					'widget' => 'bookings_calendar',
					'widget_options' => [
						'bid'         => $this->getReservationID(),
						'overbooking' => 1,
					],
				];
			}
		}

		return $cta_data ? json_encode($cta_data) : null;
	}

	/**
	 * Returns the VikBooking reservation ID.
	 * 
	 * @return 	null|int
	 */
	public function getReservationID()
	{
		$res_id = $this->get('idorder', null);

		return $res_id ? (int) $res_id : null;
	}

	/**
	 * Returns the OTA reservation ID.
	 * 
	 * @return 	null|string
	 */
	public function getOTAReservationID()
	{
		$ota_res_id = $this->get('idorderota', null);

		return $ota_res_id ? (string) $ota_res_id : null;
	}

	/**
	 * Returns the channel name.
	 * 
	 * @return 	null|string
	 */
	public function getChannel()
	{
		$channel = $this->get('channel', null);

		return $channel ? (string) $channel : null;
	}

	/**
	 * Returns the notification date and time.
	 * 
	 * @return 	string
	 */
	public function getDate()
	{
		try {
			$date = JFactory::getDate($this->get('date') ?: 'now');
		} catch(Exception $e) {
			$date = JFactory::getDate();
		}

		return $date->toSql();
	}

	/**
	 * Returns the notification signature.
	 * 
	 * @return 	string
	 */
	public function getSignature()
	{
		$signature = (string) $this->get('_signature', '');

		if (!$signature) {
			$signature = $this->buildSignature();
		}

		return $signature;
	}

	/**
	 * Sets the notification signature.
	 * 
	 * @return 	self
	 */
	public function setSignature(string $signature = '')
	{
		$this->set('_signature', $signature);

		return $this;
	}

	/**
	 * Builds, sets and returns the notification signature.
	 * 
	 * @return 	string
	 */
	public function buildSignature()
	{
		// build notification signature elements
		$elements = [
			'id'      => $this->get('id', $this->get('notification_id', 0)),
			'idorder' => $this->get('idorder', 0),
			'sender'  => $this->get('sender', ''),
			'type'    => $this->get('type', ''),
			'title'   => $this->get('title', ''),
			'summary' => $this->get('summary', ''),
		];

		// build notification signature string
		$signature = md5(serialize($elements));

		// set signature string
		$this->setSignature($signature);

		return $signature;
	}

	/**
	 * Returns the notification error code.
	 * 
	 * @return 	int
	 */
	public function getErrorCode()
	{
		return (int) $this->get('_errorCode', 500);
	}

	/**
	 * Sets the notification error code.
	 * 
	 * @return 	self
	 */
	public function setErrorCode($code = 500)
	{
		$this->set('_errorCode', $code);

		return $this;
	}

	/**
	 * Ensures a string reflects the given length, or it will be eventually shortened or replaced.
	 * 
	 * @param 	string 	$value 	   the string to check.
	 * @param 	int 	$length    the length to reflect.
	 * @param 	string 	$fallback  optional string to replace as fallback.
	 * 
	 * @return 	string
	 */
	private function shortenString(string $value, int $length, string $fallback = null)
	{
		if (strlen($value) <= $length || $length <= 0) {
			// length is safe
			return $value;
		}

		if ($fallback) {
			// replace string with provided fallback
			return $fallback;
		}

		// shorten the string to the desired length
		if (!function_exists('mb_strlen')) {
			// use a regular sub-string without multi-byte support
			return substr($value, 0, $length);
		}

		// calculate string length
		$size 	 = strlen($value);
		$mb_size = mb_strlen($value);
		$ch_diff = $size - $mb_size;

		if ($ch_diff <= 0) {
			// no multi-byte chars found
			return substr($value, 0, $length);
		}

		// safely construct the string with one multibyte char per time
		$mb_value = '';
		$mb_char_start = 0;
		while (strlen($mb_value) < $length) {
			// get a one-char multi-byte portion
			$mb_portion = mb_substr($value, $mb_char_start, 1, 'UTF-8');

			if (strlen($mb_value . $mb_portion) > $length) {
				// abort to not exceed the length
				return $mb_value;
			}

			// add portion to string value
			$mb_value .= $mb_portion;

			// increase chart start counter
			$mb_char_start++;
		}

		return $mb_value;
	}
}

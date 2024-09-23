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
 * Handle customers within the reservation scope.
 */
class VikBookingCustomersPin
{
	public $all_pins;
	public $is_admin;
	public $fieldflags;
	public $error;
	private $dbo;
	private $new_pin;
	private $new_customer_id;

	public function __construct()
	{
		$this->all_pins = false;
		$this->is_admin = false;
		$this->fieldflags = [];
		$this->error = '';
		$this->dbo = JFactory::getDbo();
		$this->new_pin = '';
		$this->new_customer_id = 0;
	}

	/**
	 * Generates a unique PIN number for the customer.
	 * 
	 * @param 	boolean 	$notpush
	 * 
	 * @return 	int 		8-digit pin
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) pin length changed from 5 to 8 digits.
	 */
	public function generateUniquePin($notpush = false)
	{
		// minimum 5 digits, maximum 8 digits
		$rand_pin = rand(10000, 99999999);
		if ($this->pinExists($rand_pin)) {
			while ($this->pinExists($rand_pin)) {
				$rand_pin += 1;
			}
		}

		if (!$notpush) {
			$this->all_pins[] = $rand_pin;
		}

		return $rand_pin;
	}

	/**
	 * Checks if the pin already exists.
	 * 
	 * @param 	string 	$pin
	 * @param 	string 	$ignorepin
	 * 
	 * @return 	boolean
	 */
	public function pinExists($pin, $ignorepin = '')
	{
		$current_pins = $this->all_pins === false ? $this->getAllPins($ignorepin) : $this->all_pins;
		return in_array($pin, $current_pins);
	}

	/**
	 * Fetches and sets all the pins currently stored in the database.
	 * 
	 * @param 	string 	$ignorepin
	 */
	public function getAllPins($ignorepin = '')
	{
		$current_pins = [];

		$q = "SELECT `pin` FROM `#__vikbooking_customers`".(!empty($ignorepin) ? " WHERE `pin`!=".$this->dbo->quote($ignorepin) : "").";";
		$this->dbo->setQuery($q);
		$pins = $this->dbo->loadAssocList();
		if ($pins) {
			foreach ($pins as $v) {
				$current_pins[] = $v['pin'];
			}
		}

		$this->all_pins = $current_pins;

		return $this->all_pins;
	}

	/**
	 * Attempts to fetch the customer details record by Joomla/WordPress User ID.
	 * 
	 * @param 	array 	$customer_details
	 * 
	 * @return 	array 	can also be used with no return value as reference.
	 */
	private function getDetailsByUjid(&$customer_details)
	{
		$user = JFactory::getUser();

		if (!$user->guest && (int)$user->id > 0) {
			$q = "SELECT * FROM `#__vikbooking_customers` WHERE `ujid`=" . (int)$user->id . " ORDER BY `#__vikbooking_customers`.`id` DESC";
			$this->dbo->setQuery($q, 0, 1);
			$customer = $this->dbo->loadAssoc();
			if ($customer) {
				$customer['cfields'] = empty($customer['cfields']) ? array() : json_decode($customer['cfields'], true);
				$customer_details = $customer;
			}
		}

		return $customer_details;
	}

	/**
	 * Attempts to fetch the customer details record by PIN Cookie.
	 * 
	 * @param 	array 	$customer_details
	 * 
	 * @return 	array 	can also be used with no return value as reference.
	 */
	private function getDetailsByPinCookie(&$customer_details)
	{
		$pin_cookie = $this->getPinCookie();
		$pin_cookie = empty($pin_cookie) ? (int)$this->getNewPin() : $pin_cookie;
		if ($pin_cookie) {
			$q = "SELECT * FROM `#__vikbooking_customers` WHERE `pin`=" . $this->dbo->quote($pin_cookie) . " ORDER BY `#__vikbooking_customers`.`id` DESC";
			$this->dbo->setQuery($q, 0, 1);
			$customer = $this->dbo->loadAssoc();
			if ($customer) {
				$customer['cfields'] = empty($customer['cfields']) ? array() : json_decode($customer['cfields'], true);
				$customer_details = $customer;
			}
		}

		return $customer_details;
	}

	/**
	 * Gets "decoded" PIN from Cookie.
	 * 
	 * @return 	int 	the pin cookie.
	 */
	private function getPinCookie()
	{
		$pin_cookie = 0;
		$cookie = JFactory::getApplication()->input->cookie;
		$cookie_val = $cookie->get('vboPinData', '', 'string');
		if (!empty($cookie_val) && intval($cookie_val) > 0) {
			$cookie_val = intval(strrev( (string)$cookie_val )) / 1987;
			$pin_cookie = (int)$cookie_val > 0 ? $cookie_val : $pin_cookie;
		}
		return $pin_cookie;
	}

	/**
	 * Sets "encoded" PIN to Cookie with a lifetime of 365 days.
	 * 
	 * @param 	string 	$pin
	 */
	private function setPinCookie($pin)
	{
		$pin_cookie = 0;
		if (!empty($pin)) {
			$pin_cookie = (int)$pin * 1987;
			$pin_cookie = strrev( (string)$pin_cookie );
			VikRequest::setCookie('vboPinData', $pin_cookie, (time() + (86400 * 365)), '/', '', false, true);
		}
		
		return $pin_cookie;
	}

	/**
	 * Unsets PIN Cookie
	 */
	private function unsetPinCookie()
	{
		$cookie = JFactory::getApplication()->input->cookie;
		VikRequest::setCookie('vboPinData', $pin_cookie, (time() - (86400 * 365)), '/', '', false, true);
		$cookie_val = $cookie->get('vboPinData', '', 'string');
		
		return $pin_cookie;
	}

	/**
	 * Loads the customer details by Joomla/WordPress User ID or by PIN cookie.
	 * Returns an associative array with the record fetched from the DB.
	 * 
	 * @return 	array 	empty array or customer record associative array.
	 */
	public function loadCustomerDetails()
	{
		$customer_details = [];

		// first attempt is through Joomla User ID
		$this->getDetailsByUjid($customer_details);

		if (!count($customer_details)) {
			// second attempt is through PIN Cookie
			$this->getDetailsByPinCookie($customer_details);
		}

		return $customer_details;
	}

	/**
	 * Checks whether the given customer has got automatic discounts reserved for the current booking.
	 * 
	 * @param 	array 	$customer 	customer record associative array.
	 * @param 	array 	$booking 	the booking information array to validate the coupon.
	 * 
	 * @return 	array 	empty array or proper customer coupon record.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function getCustomerCoupon(array $customer = [], array $booking = [])
	{
		if (empty($customer['id'])) {
			return [];
		}

		$q = "SELECT `ccp`.`idcoupon`, `cp`.* FROM `#__vikbooking_customers_coupons` AS `ccp` 
			LEFT JOIN `#__vikbooking_coupons` AS `cp` ON `ccp`.`idcoupon`=`cp`.`id` 
			WHERE `ccp`.`idcustomer`=" . (int)$customer['id'] . " AND `ccp`.`automatic`=1 ORDER BY `ccp`.`idcoupon` DESC";
		$this->dbo->setQuery($q);
		$customer_coupons = $this->dbo->loadAssocList();
		if (!$customer_coupons) {
			return [];
		}

		if (empty($booking)) {
			// do not perform any validation on the coupon restrictions
			return $customer_coupons[0];
		}

		// process all coupon codes for this customer to find the best one for this booking details
		foreach ($customer_coupons as $coupon) {
			if (!empty($coupon['datevalid'])) {
				$dateparts = explode("-", $coupon['datevalid']);
				$pickinfo = getdate($booking['checkin']);
				$dropinfo = getdate($booking['checkout']);
				$checkpick = mktime(0, 0, 0, $pickinfo['mon'], $pickinfo['mday'], $pickinfo['year']);
				$checkdrop = mktime(0, 0, 0, $dropinfo['mon'], $dropinfo['mday'], $dropinfo['year']);
				if (!($checkpick >= $dateparts[0] && $checkpick <= $dateparts[1] && $checkdrop >= $dateparts[0] && $checkdrop <= $dateparts[1])) {
					// invalid dates
					continue;
				}
			}
			if (!empty($coupon['minlos']) && $coupon['minlos'] > $booking['days']) {
				// invalid min LOS
				continue;
			}
			if (!$coupon['allvehicles'] && !empty($coupon['idrooms'])) {
				// validate rooms booked
				foreach ((array)$booking['rooms'] as $room) {
					if (!preg_match("/;" . $room['id'] . ";/i", $coupon['idrooms'])) {
						// room not allowed
						continue 2;
					}
				}
			}
			// return the first eligible coupon discount
			return $coupon;
		}

		// no eligible discounts found
		return [];
	}

	/**
	 * Attempts to fetch the customer details record by PIN code.
	 */
	public function getCustomerByPin($pin)
	{
		$customer = [];
		$this->setNewPin($pin);

		if (!empty($pin)) {
			$q = "SELECT * FROM `#__vikbooking_customers` WHERE `pin`=".$this->dbo->quote($pin)." ORDER BY `#__vikbooking_customers`.`id` DESC";
			$this->dbo->setQuery($q, 0, 1);
			$customer = $this->dbo->loadAssoc();
			if ($customer) {
				$customer['cfields'] = empty($customer['cfields']) ? array() : json_decode($customer['cfields'], true);
				$this->setPinCookie($pin);
			}
		}

		return $customer;
	}

	/**
	 * Get customer by ID.
	 * 
	 * @param 	int 	$cust_id 	the ID of the customer.
	 * 
	 * @return 	array 	the customer array or an empty array.
	 */
	public function getCustomerByID($cust_id)
	{
		$customer = [];
		if (!empty($cust_id)) {
			$q = "SELECT `c`.*,`nat`.`country_name`,`nat`.`country_2_code` FROM `#__vikbooking_customers` AS `c` LEFT JOIN `#__vikbooking_countries` AS `nat` ON `c`.`country`=`nat`.`country_3_code` WHERE `c`.`id`=".$this->dbo->quote($cust_id);
			$this->dbo->setQuery($q, 0, 1);
			$customer = $this->dbo->loadAssoc();
			$customer = !$customer ? [] : $customer;
		}

		if ($customer) {
			$customer['cfields'] = empty($customer['cfields']) ? array() : json_decode($customer['cfields'], true);
			$customer['chdata'] = !empty($customer['chdata']) ? json_decode($customer['chdata'], true) : array();
			$customer['chdata'] = is_array($customer['chdata']) ? $customer['chdata'] : array();
		}

		return $customer;
	}

	/**
	 * Get customer array by booking ID.
	 * Also returns the pax_data information.
	 * 
	 * @param 	int 	$orderid 	the VBO booking ID.
	 * 
	 * @return 	array 	the customer array or an empty array.
	 * 
	 * @uses 	getCustomerByID()
	 */
	public function getCustomerFromBooking($orderid)
	{
		if (empty($orderid)) {
			return [];
		}
		$q = "SELECT `idcustomer`, `pax_data` FROM `#__vikbooking_customers_orders` WHERE `idorder`=".(int)$orderid.";";
		$this->dbo->setQuery($q);
		$data = $this->dbo->loadAssoc();
		if (!$data) {
			return [];
		}

		$customer = $this->getCustomerByID($data['idcustomer']);
		if ($customer) {
			/**
			 * Merge pax_data into the customer array to know whether
			 * the pre check-in or the registration was performed.
			 *
			 * @since 	1.12
			 */
			$customer['pax_data'] = $data['pax_data'];
		}

		return $customer;
	}

	/**
	 * Checks whether a customer with the same email address already exists
	 * Returns false or the record of the existing customer
	 * 
	 * @param 	string 	$email 		the email address.
	 * @param 	string 	$first_name optional first name to compare existing emails.
	 * @param 	string 	$last_name 	optional last name to compare existing emails.
	 * 
	 * @return 	mixed 	false if customer does not exist, array if customer found.
	 * 
	 * @since 	1.13 	equal email addresses can be shared across multiple customers
	 *  				so long as the name or the last name are different.
	 */
	public function customerExists($email, $first_name = '', $last_name = '')
	{
		if (empty($email)) {
			return false;
		}
		$q = "SELECT * FROM `#__vikbooking_customers` WHERE `email`=".$this->dbo->quote(trim($email))." ORDER BY `#__vikbooking_customers`.`id` DESC;";
		$this->dbo->setQuery($q);
		$customers = $this->dbo->loadAssocList();
		if (!$customers) {
			return false;
		}

		if (empty($first_name) || empty($last_name)) {
			// no info to compare an existing customer, so say it exists with this email address
			return $customers[0];
		}

		// check if name and last name match with the current customers having this email address
		foreach ($customers as $c) {
			if (stripos($c['first_name'], $first_name) !== false && stripos($c['last_name'], $last_name) !== false) {
				// customer with same email, first name and last name found
				return $c;
			}
		}

		/**
		 * We now check if a record with same email, first name and last name is found first, if not we return false.
		 * 
		 * @since 	1.14 (Joomla) - 1.4.0 (WordPress)
		 */
		return false;
	}

	/**
	 * Sets some customer extra information like address, city, zip, company name, vat
	 */
	public function setCustomerExtraInfo($fieldflags)
	{
		if (is_array($fieldflags) && count($fieldflags) > 0) {
			$this->fieldflags = $fieldflags;
		}
	}

	/**
	 * Converts the 2-char country code or country name into the ISO Alpha3 Char Code.
	 * 
	 * @param 	string 	$country 	the 2-char country code or country name to convert.
	 * 
	 * @return 	string 	either the 3-char version or the passed value.
	 * 
	 * @since 	1.13.5
	 */
	public function get3CharCountry($country)
	{
		if (empty($country) || strlen((string)$country) < 2) {
			return $country;
		}

		// trim white spaces
		$country = trim($country);

		// check what field to look for
		$clause = [];
		if (strlen($country) == 2) {
			array_push($clause, "`country_2_code`=" . $this->dbo->quote($country));
		} else {
			array_push($clause, "`country_name` LIKE " . $this->dbo->quote("%{$country}%"));
		}

		// query the db
		$q = "SELECT `country_3_code` FROM `#__vikbooking_countries` WHERE " . implode(' AND ', $clause);
		$this->dbo->setQuery($q, 0, 1);
		$three_country = $this->dbo->loadResult();
		if ($three_country) {
			// 3-char code found
			return $three_country;
		}

		// nothing found, return the passed value
		return $country;
	}

	/**
	 * Saves the customer in DB if it doesn't exist, generates the PIN and sets the cookie
	 */
	public function saveCustomerDetails($first_name, $last_name, $email, $phone_number, $country, $cfields)
	{
		if (empty($first_name) || empty($last_name) || empty($email)) {
			$this->setError('Missing fields for saving new customer');
			return false;
		}

		/**
		 * We convert any 2-char country code into a 3-char country code
		 * 
		 * @since 	1.13.5
		 */
		if (!empty($country) && strlen($country) == 2) {
			$country = $this->get3CharCountry($country);
		}

		/**
		 * In case the phone number is missing the international prefix at the beginning of the string,
		 * mostly happens in case of OTA bookings, we pre-pend the country prefix.
		 * 
		 * @since 	1.12 (patch October 2019)
		 */
		$phone_number = trim($phone_number);
		if (!empty($phone_number) && !empty($country) && substr($phone_number, 0, 1) != '+' && substr($phone_number, 0, 2) != '00') {
			// try to find the country phone prefix
			$q = "SELECT `phone_prefix` FROM `#__vikbooking_countries` WHERE `country_" . (strlen($country) == 2 ? '2' : '3') . "_code`=" . $this->dbo->quote($country) . ";";
			$this->dbo->setQuery($q);
			$phone_prefix = $this->dbo->loadResult();
			if ($phone_prefix) {
				$country_prefix = str_replace(' ', '', $phone_prefix);
				$num_prefix = str_replace('+', '', $country_prefix);
				if (substr($phone_number, 0, strlen($num_prefix)) != $num_prefix) {
					// country prefix is completely missing
					$phone_number = $country_prefix . $phone_number;
				} else {
					// try to prepend the plus symbol because the phone number starts with the country prefix
					$phone_number = '+' . $phone_number;
				}
			}
		}

		// check if the customer exists
		$customer = $this->customerExists($email, $first_name, $last_name);

		// access the current user
		$user = JFactory::getUser();

		if ($customer === false) {
			$new_pin = $this->generateUniquePin();

			// build customer record
			$customer_obj = new stdClass;
			$customer_obj->first_name = $first_name;
			$customer_obj->last_name  = $last_name;
			$customer_obj->email 	  = $email;
			$customer_obj->phone 	  = $phone_number;
			$customer_obj->country 	  = $country;
			$customer_obj->cfields 	  = is_array($cfields) && $cfields ? json_encode($cfields) : null;
			$customer_obj->pin 		  = $new_pin;
			$customer_obj->ujid 	  = $this->is_admin ? 0 : (int)$user->id;
			// add the extra field flags
			foreach ($this->fieldflags as $flagk => $flagv) {
				if (is_array($flagv) || is_object($flagv)) {
					$flagv = json_encode($flagv);
				}
				$customer_obj->{$flagk} = $flagv;
			}

			// trigger the customer before-insert event
			$this->pluginCustomerSync(0, 'insert', (array)$customer_obj, $before = true);

			// insert the new customer record
			$this->dbo->insertObject('#__vikbooking_customers', $customer_obj, 'id');
			$new_customer_id = isset($customer_obj->id) ? $customer_obj->id : null;

			if (!empty($new_customer_id)) {
				$this->setNewPin($new_pin);
				$this->setNewCustomerId($new_customer_id);

				// trigger the customer after-save event
				$this->pluginCustomerSync($new_customer_id, 'insert', (array)$customer_obj, $before = false);
			}
		} elseif (is_array($customer) && $customer) {
			$this->setNewPin($customer['pin']);
			$this->setNewCustomerId($customer['id']);

			// build customer record
			$customer_obj = new stdClass;
			$customer_obj->id 		  = $customer['id'];
			$customer_obj->first_name = $first_name;
			$customer_obj->last_name  = $last_name;
			$customer_obj->email 	  = $email;
			$customer_obj->phone 	  = $phone_number;
			$customer_obj->country 	  = $country;
			if (!$this->is_admin) {
				$customer_obj->cfields = is_array($cfields) && $cfields ? json_encode($cfields) : null;
			}
			$customer_obj->pin 		  = $customer['pin'];
			$customer_obj->ujid 	  = $this->is_admin ? 0 : (int)$user->id;
			// add the extra field flags
			foreach ($this->fieldflags as $flagk => $flagv) {
				if (is_array($flagv) || is_object($flagv)) {
					$flagv = json_encode($flagv);
				}
				$customer_obj->{$flagk} = $flagv;
			}

			// trigger the customer before-update event
			$this->pluginCustomerSync($customer['id'], 'update', (array)$customer_obj, $before = true);

			// update customer record
			$this->dbo->updateObject('#__vikbooking_customers', $customer_obj, 'id');

			// trigger the customer after-save event
			$this->pluginCustomerSync($customer['id'], 'update', (array)$customer_obj, $before = false);
		}

		//unset extra info
		$this->fieldflags = [];

		return !$this->is_admin ? $this->storeCustomerCookie() : true;
	}

	public function storeCustomerCookie()
	{
		$pin = $this->getNewPin();
		$customer_id = $this->getNewCustomerId();
		if (empty($pin) || empty($customer_id)) {
			return false;
		}
		$this->setPinCookie($pin);
		return true;
	}

	/**
	 * Stores a relation between the Customer ID and the Booking ID
	 * This method should be called after the saveCustomerDetails() because
	 * it requires the methods setNewPin and setNewCustomerId to be called before.
	 * Since VBO 1.9 this method also calculates and sets the commissions
	 * amount if the customer is a sales channel.
	 * Requires the records in _ordersrooms to be stored before being called.
	 * 
	 * @param 	int 	orderid 	the ID of the VBO order
	 * 
	 * @return 	boolean
	 */
	public function saveCustomerBooking($orderid)
	{
		$pin = $this->getNewPin();
		$customer_id = $this->getNewCustomerId();
		if (empty($orderid) || empty($pin) || empty($customer_id)) {
			return false;
		}

		$q = "SELECT * FROM `#__vikbooking_ordersrooms` WHERE `idorder`=".(int)$orderid.";";
		$this->dbo->setQuery($q);
		$orders_rooms = $this->dbo->loadAssocList();
		if (!$orders_rooms) {
			return false;
		}

		$q = "DELETE FROM `#__vikbooking_customers_orders` WHERE `idorder`=".$this->dbo->quote($orderid).";";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		$q = "INSERT INTO `#__vikbooking_customers_orders` (`idcustomer`,`idorder`) VALUES(".$this->dbo->quote($customer_id).", ".$this->dbo->quote($orderid).");";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		// when assigning a booking to a customer, check that the traveler first and last name is not empty for the page Dashboard that reads it
		if (empty($orders_rooms[0]['t_first_name']) && empty($orders_rooms[0]['t_last_name'])) {
			$customer_info = $this->getCustomerByID($customer_id);
			$q = "UPDATE `#__vikbooking_ordersrooms` SET `t_first_name`=".$this->dbo->quote($customer_info['first_name']).", `t_last_name`=".$this->dbo->quote($customer_info['last_name'])." WHERE `idorder`=".(int)$orderid." LIMIT 1;";
			$this->dbo->setQuery($q);
			$this->dbo->execute();

			// update the country as well
			if (!empty($customer_info['country'])) {
				$q = "UPDATE `#__vikbooking_orders` SET `country`=".$this->dbo->quote($customer_info['country'])." WHERE `id`=".(int)$orderid.";";
				$this->dbo->setQuery($q);
				$this->dbo->execute();
			}
		}

		// commissions for customers that are sales channels
		$this->updateBookingCommissions($orderid, $customer_id);

		return true;
	}

	/**
	 * Changes the customer assigned to the booking by
	 * re-calculating the amount of commissions (if any).
	 * 
	 * @param 	int 	$orderid 		the booking id.
	 * @param 	int 	$customer_id 	the id of the new customer.
	 * 
	 * @return 	boolean
	 * 
	 * @since 	1.11
	 */
	public function updateCustomerBooking($orderid, $customer_id)
	{
		if (empty($orderid) || empty($customer_id)) {
			return false;
		}
		$new_customer = $this->getCustomerByID($customer_id);
		if (!count($new_customer)) {
			// invalid customer ID given
			return false;
		}
		$old_customer = $this->getCustomerFromBooking($orderid);
		if (count($old_customer) && (int)$old_customer['ischannel'] > 0) {
			// unset first the old commissions and channel name
			$q = "UPDATE `#__vikbooking_orders` SET `channel`=NULL, `cmms`=NULL WHERE `id`=".(int)$orderid.";";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
		}
		if (count($old_customer)) {
			// update reference
			$q = "UPDATE `#__vikbooking_customers_orders` SET `idcustomer`=".(int)$new_customer['id']." WHERE `idorder`=".(int)$orderid.";";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			if (!empty($new_customer['country']) && strlen($new_customer['country']) == 3) {
				// update booking country
				$q = "UPDATE `#__vikbooking_orders` SET `country`=" . $this->dbo->quote($new_customer['country']) . " WHERE `id`=" . (int)$orderid . ";";
				$this->dbo->setQuery($q);
				$this->dbo->execute();
			}
		} else {
			// insert relation
			$q = "INSERT INTO `#__vikbooking_customers_orders` (`idcustomer`,`idorder`) VALUES(".(int)$new_customer['id'].", ".(int)$orderid.");";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			if (!empty($new_customer['country']) && strlen($new_customer['country']) == 3) {
				// update booking country
				$q = "UPDATE `#__vikbooking_orders` SET `country`=" . $this->dbo->quote($new_customer['country']) . " WHERE `id`=" . (int)$orderid . ";";
				$this->dbo->setQuery($q);
				$this->dbo->execute();
			}
		}

		// update commissions and sale channel information
		return $this->updateBookingCommissions($orderid, $new_customer['id']);
	}

	/**
	 * Calculates and sets the commissions amount if the customer
	 * is a "sales channel" with a percentage greater than 0.
	 * 
	 * @param 	int 	$orderid
	 * @param 	int 	$customer_id
	 * 
	 * @since 	1.9
	 */
	public function updateBookingCommissions($orderid, $customer_id = 0)
	{
		if (empty($customer_id)) {
			$customer_id = $this->getNewCustomerId();
		}

		if (empty($orderid) || empty($customer_id)) {
			return false;
		}

		$customer = $this->getCustomerByID($customer_id);
		if ((int)$customer['ischannel'] > 0 && !empty($customer['chdata']) && isset($customer['chdata']['commission']) && $customer['chdata']['commission'] > 0.00) {
			$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=".$this->dbo->quote($orderid).";";
			$this->dbo->setQuery($q);
			$order_info = $this->dbo->loadAssoc();
			if ($order_info) {
				if ((float)$order_info['total'] > 0.00) {
					$cmms_calc_base = $this->calcCommissionsBaseAmount($order_info, $customer);
					$cmms_amount = $cmms_calc_base * $customer['chdata']['commission'] / 100;
					$source_name = 'customer'.$customer['id'].(array_key_exists('chname', $customer['chdata']) ? '_'.$customer['chdata']['chname'] : '');

					$q = "UPDATE `#__vikbooking_orders` SET `channel`=".$this->dbo->quote($source_name).", `cmms`=".$this->dbo->quote($cmms_amount)." WHERE `id`=".$this->dbo->quote($order_info['id']).";";
					$this->dbo->setQuery($q);
					$this->dbo->execute();

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Calculates the base amount on which the commissions should be applied.
	 * Considers the parameters for this customer sales channel for taxes and rooms rates.
	 * 
	 * @param 	array 	$order_info
	 * @param 	array 	$customer
	 */
	private function calcCommissionsBaseAmount($order_info, $customer)
	{
		$cmms_calc_base = $order_info['total'];

		if (!empty($customer['chdata']) && array_key_exists('calccmmon', $customer['chdata']) && (int)$customer['chdata']['calccmmon'] > 0) {
			// commissions based on room rates only
			$q = "SELECT `or`.* FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`='" . (int)$order_info['id'] . "';";
			$this->dbo->setQuery($q);
			$order_rooms = $this->dbo->loadAssocList();
			if ($order_rooms) {
				$map_cost_tax = [];
				foreach ($order_rooms as $or) {
					if (!((float)$or['room_cost'] > 0.00) && !((float)$or['cust_cost'] > 0.00)) {
						// missing information about the room cost - cannot proceed
						continue;
					}
					$map_cost_tax[] = array(
						'amount' => ((float)$or['room_cost'] > 0.00 ? $or['room_cost'] : $or['cust_cost']),
						'taxid' => (!empty($or['cust_cost']) ? intval($or['cust_idiva']) : $this->getTaxIdFromTar($or['idtar']))
					);
				}
				if (count($map_cost_tax) == count($order_rooms)) {
					// all rooms have a custom cost or a room cost set. We can proceed
					if (array_key_exists('applycmmon', $customer['chdata']) && (int)$customer['chdata']['applycmmon'] > 0) {
						// commissions based on amounts tax excluded
						if ($this->pricesTaxIncluded()) {
							// prices are tax included so update the amounts in $map_cost_tax
							foreach ($map_cost_tax as $ctk => $ctv) {
								if (!((float)$ctv['amount'] > 0.00) || empty($ctv['taxid'])) {
									continue;
								}
								list($aliq, $taxcap) = $this->getAliqFromTaxId($ctv['taxid']);
								if ((float)$aliq > 0.00) {
									$op_div = (100 + $aliq) / 100;
									$tmp_op = $ctv['amount'] / $op_div;
									/**
									 * Tax Cap implementation
									 * 
									 * @since 	1.12
									 */
									if ($taxcap > 0 && ($ctv['amount'] - $tmp_op) > $taxcap) {
										$tmp_op = $ctv['amount'] - $taxcap;
									}
									$map_cost_tax[$ctk]['amount'] = $tmp_op;
								}
							}
						}
					}
					// sum all the amounts to get the base amount where commissions will be applied
					$sum = 0;
					foreach ($map_cost_tax as $k => $map) {
						$sum += $map['amount'];
					}
					$cmms_calc_base = $sum;
				}
			}
		}

		return $cmms_calc_base;
	}

	private function pricesTaxIncluded()
	{
		if (!class_exists('VikBooking')) {
			require_once(VBO_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikbooking.php');
		}
		return VikBooking::ivaInclusa();
	}

	/**
	 * Retrieves the aliquot and tax cap of the tax ID passed.
	 * Returns 0 if nothing is found, the Tax Aliq otherwise.
	 * 
	 * @param 	int 	$taxid 	the ID of the tax rate
	 * 
	 * @return 	array 	the aliquote as 0th array value, the tax cap as 1st value.
	 *
	 * @since 	1.12 	this private method used to return just the aliquote.
	 */
	private function getAliqFromTaxId($taxid)
	{
		$aliq 	= 0;
		$taxcap = 0;
		if (intval($taxid) > 0) {
			$q = "SELECT `i`.`aliq`,`i`.`taxcap` FROM `#__vikbooking_iva` AS `i` WHERE `i`.`id`=" . (int)$taxid;
			$this->dbo->setQuery($q, 0, 1);
			$tax_info = $this->dbo->loadAssoc();
			if ($tax_info) {
				$aliq 	= $tax_info['aliq'];
				$taxcap = $tax_info['taxcap'];
			}
		}

		return [$aliq, $taxcap];
	}

	/**
	 * Retrieves the ID of the tax used for the tariff passed.
	 * Returns 0 if nothing is found, the Tax ID otherwise.
	 * 
	 * @param 	int 	$idtar
	 */
	private function getTaxIdFromTar($idtar)
	{
		$taxid = 0;
		if (intval($idtar) > 0) {
			$q = "SELECT `d`.`id`,`d`.`idprice`,`p`.`idiva`,`i`.`aliq` FROM `#__vikbooking_dispcost` AS `d` LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`d`.`idprice` LEFT JOIN `#__vikbooking_iva` `i` ON `i`.`id`=`p`.`idiva` WHERE `d`.`id`=" . (int)$idtar;
			$this->dbo->setQuery($q, 0, 1);
			$tax_info = $this->dbo->loadAssoc();
			if ($tax_info) {
				$taxid = (int)$tax_info['idiva'];
			}
		}

		return $taxid;
	}

	/**
	 * Takes the Customer PIN from the Order ID
	 * 
	 * @param 	int 	$orderid
	 */
	public function getPinCodeByOrderId($orderid)
	{
		$pin = '';
		if (!empty($orderid)) {
			$q = "SELECT `o`.`id`,`oc`.`idcustomer`,`c`.`pin` FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_customers_orders` `oc` ON `oc`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_customers` `c` ON `c`.`id`=`oc`.`idcustomer` WHERE `o`.`id`=".intval($orderid)." AND `oc`.`idcustomer` IS NOT NULL;";
			$this->dbo->setQuery($q);
			$custdata = $this->dbo->loadAssocList();
			if ($custdata) {
				if (!empty($custdata[0]['pin'])) {
					$pin = $custdata[0]['pin'];
				}
			}
		}
		
		return $pin;
	}

	/**
	 * Triggers the events to sync customers accross multiple plugins.
	 * 
	 * @param 	int 	$customer_id
	 * @param 	string 	$mode
	 * @param 	array 	$data 			optional customer data associative array.
	 * @param 	bool 	$before 		whether it's before or after saving the customer.
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP) introduced args $data and $before.
	 */
	public function pluginCustomerSync($customer_id, $mode, $data = [], $before = true)
	{
		if ($data) {
			$customer = $data;
		} else {
			$q = "SELECT * FROM `#__vikbooking_customers` WHERE `id`=" . (int)$customer_id;
			$this->dbo->setQuery($q, 0, 1);
			$customer = $this->dbo->loadAssoc();
			if (!$customer) {
				return false;
			}
		}

		// make sure to import this type of plugins
		JPluginHelper::importPlugin('e4j');

		// get the event name
		if ($mode == 'insert') {
			// trigger plugin -> customer creation
			$ev_name = 'onCustomerInsert';
		} elseif ($mode == 'update') {
			// trigger plugin -> customer update
			$ev_name = 'onCustomerUpdate';
		} elseif ($mode == 'delete') {
			// trigger plugin -> customer delete
			$ev_name = 'onCustomerDelete';
		} else {
			return false;
		}

		// event options
		$options = [
			'alias' 	=> 'com_vikbooking',
			'version' 	=> (defined('VIKBOOKING_SOFTWARE_VERSION') ? VIKBOOKING_SOFTWARE_VERSION : E4J_SOFTWARE_VERSION),
			'admin' 	=> VikBooking::isAdmin(),
			'call' 		=> __FUNCTION__,
		];

		try {
			if ($before) {
				/**
				 * Trigger event for the imminent creation, update or deletion of the customer.
				 */
				VBOFactory::getPlatform()->getDispatcher()->trigger($ev_name, [$customer, $options]);
			} elseif (!$before && ($mode === 'insert' || $mode === 'update')) {
				/**
				 * Trigger event after the creation or the update of the customer.
				 */
				VBOFactory::getPlatform()->getDispatcher()->trigger('onAfterSaveCustomer', [$customer, ($mode === 'insert')]);
			}
		} catch (Throwable $e) {
			// do nothing
		}

		return true;
	}

	/**
	 * Sets the current customer PIN
	 * 
	 * @param 	string 	$pin
	 */
	public function setNewPin($pin = '')
	{
		$this->new_pin = $pin;
	}

	/**
	 * Get the current customer PIN
	 */
	public function getNewPin()
	{
		return $this->new_pin;
	}

	/**
	 * Sets the current customer ID
	 * 
	 * @param 	int 	$cid
	 */
	public function setNewCustomerId($cid = 0)
	{
		$this->new_customer_id = (int)$cid;
	}

	/**
	 * Get the current customer ID
	 */
	public function getNewCustomerId()
	{
		return $this->new_customer_id;
	}

	/**
	 * Explanation of the XML error
	 * 
	 * @param 	object 	$error
	 */
	public function libxml_display_error($error)
	{
		$return = "\n";
		switch ($error->level) {
			case LIBXML_ERR_WARNING :
				$return .= "Warning ".$error->code.": ";
				break;
			case LIBXML_ERR_ERROR :
				$return .= "Error ".$error->code.": ";
				break;
			case LIBXML_ERR_FATAL :
				$return .= "Fatal Error ".$error->code.": ";
				break;
		}
		$return .= trim($error->message);
		if ($error->file) {
			$return .= " in ".$error->file;
		}
		$return .= " on line ".$error->line."\n";
		return $return;
	}

	/**
	 * Get the XML errors occurred
	 */
	public function libxml_display_errors()
	{
		$errorstr = "";
		$errors = libxml_get_errors();
		foreach ($errors as $error) {
			$errorstr .= $this->libxml_display_error($error);
		}
		libxml_clear_errors();
		return $errorstr;
	}

	private function setError($str)
	{
		$this->error .= $str."\n";
	}

	public function getError()
	{
		return nl2br(rtrim($this->error, "\n"));
	}

	/**
	 * Tells whether Vik Booking is up to date to support the customer picture.
	 * May be used by VCM to detect if this feature is available in Vik Booking.
	 * 
	 * @return 	 bool
	 * 
	 * @since 	 1.15.3 (J) - 1.5.5 (WP)
	 * @requires VCM >= 1.8.6
	 */
	public function supportsProfileAvatar()
	{
		return true;
	}

	/**
	 * Tells whether Vik Booking is up to date to support the state/province.
	 * May be used by VCM to detect if this feature is available in Vik Booking.
	 * 
	 * @return 	 bool
	 * 
	 * @since 	 1.16.1 (J) - 1.6.1 (WP)
	 * @requires VCM >= 1.8.12
	 */
	public function supportsStateProvince()
	{
		return true;
	}
}

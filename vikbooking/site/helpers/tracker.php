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
 * This class is used to store statistics about the requests made by the customers to
 * produce Tracking and Conversion. May be used also by VCM for the booking links.
 * 
 * @since 	1.11 (J) - 1.1 (WP)
 * @since 	1.15.0 (J) - 1.5.0 (WP)  implemented several improvements and new features.
 */
class VikBookingTracker
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var VikBookingTracker
	 */
	protected static $instance = null;

	/**
	 * The fingerprint of this session.
	 *
	 * @var string
	 */
	protected static $fingerprint = null;

	/**
	 * The tracking fingerprint of a previous session. If provided,
	 * this tracking execution should override the previous session.
	 * Introduced to keep up with VCM and Google Hotel booking links.
	 *
	 * @var 	string
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected static $trk_fingerprint = null;

	/**
	 * The tracking data object.
	 *
	 * @var object
	 */
	protected static $trackdata;

	/**
	 * Optional associative array of extra information.
	 *
	 * @var array
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	protected static $extra_info = [];

	/**
	 * The tracking info identifier.
	 *
	 * @var int
	 */
	protected static $identifier = 0;

	/**
	 * The referrer string to which the visitor came from.
	 *
	 * @var string
	 */
	protected static $referrer = '';

	/**
	 * The database handler instance.
	 *
	 * @var object
	 */
	protected $dbo;

	/**
	 * List of common properties that should not be displayed as "extra" properties
	 * set by VCM during a tracking. Useful for the back-end "trackings" View of VBO.
	 * 
	 * @var 	array
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static $common_trk_props = array(
		'checkin',
		'checkout',
		'nights',
		'rooms_num',
		'party',
		'rooms',
		'rplans',
		'idcustomer',
		'idorder',
		'rplans',
		'referrer',
		'test',
	);

	/**
	 * Class constructor is protected.
	 *
	 * @see 	getInstance()
	 */
	protected function __construct()
	{
		$this->dbo = JFactory::getDbo();
		$this->getFingerprint();
		static::$trackdata = new stdClass;
		$this->getIdentifier();
		$this->getReferrer();
	}

	/**
	 * Returns the global Tracker object, either
	 * a new instance or the existing instance
	 * if the class was already instantiated.
	 *
	 * @return 	self 	A new instance of the class.
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Starts a new fingerprint if it doesn't exist.
	 * Returns the existing fingerprint otherwise.
	 * Fingerprint is composed of: Session ID + IP + User Agent.
	 * The generated fingerprint is stored in the session
	 * as well as on a class variable for the execution.
	 * A cookie is sent to the visitor to memorize the fingerprint.
	 *
	 * @return 	string 	the md5 fingerprint of the current session.
	 * 
	 * @see 			this public method may also be used by VCM.
	 */
	public function getFingerprint()
	{
		// check if the fingerprint has been instantiated already
		if (!is_null(static::$fingerprint)) {
			// return the current fingerprint
			return static::$fingerprint;
		}

		/**
		 * To check for an existing or previous fingerprint, we give the highest priority
		 * to the reserved request var "vbo_tracking", which may have been passed after
		 * a previous tracking session for a booking link with VCM.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$vbo_tracking = VikRequest::getString('vbo_tracking', '', 'request');
		$prev_trkdata = $this->loadFingerprintData($vbo_tracking);
		if (is_array($prev_trkdata) && count($prev_trkdata)) {
			// generate a new fingerprint for the real visitor
			static::$fingerprint = $this->generateFingerprintHash();
			// set the previous tracking fingerprint of the server
			static::$trk_fingerprint = $prev_trkdata['fingerprint'];
			// renew cookie for fingerprint
			VikRequest::setCookie('vboTFP', static::$fingerprint, (time() + (86400 * 365)), '/');
			return static::$fingerprint;
		}

		// rely on session and cookies
		$session = JFactory::getSession();
		$app  	 = JFactory::getApplication();
		$cookie  = $app->input->cookie;

		// check if the fingerprint was saved in the session
		$sesstfp = $session->get('vboTFP', '');
		if (!empty($sesstfp)) {
			// set var and return the session fingerprint
			static::$fingerprint = $sesstfp;
			// renew cookie for fingerprint
			VikRequest::setCookie('vboTFP', static::$fingerprint, (time() + (86400 * 365)), '/');
			return $sesstfp;
		}

		// check if the fingerprint is available in a cookie
		$cketfp = $cookie->get('vboTFP', '', 'string');
		if (!empty($cketfp)) {
			// set var, session and return the fingerprint cookie
			static::$fingerprint = $cketfp;
			$session->set('vboTFP', $cketfp);
			// renew cookie for fingerprint
			VikRequest::setCookie('vboTFP', static::$fingerprint, (time() + (86400 * 365)), '/');

			return $cketfp;
		}

		// create a new fingerprint for the visitor
		static::$fingerprint = $this->generateFingerprintHash();

		// set the fingerprint session and cookie values
		$session->set('vboTFP', static::$fingerprint);
		VikRequest::setCookie('vboTFP', static::$fingerprint, (time() + (86400 * 365)), '/');

		return static::$fingerprint;
	}

	/**
	 * Generates a fingerprint for the current visitor. The signature
	 * is composed of: PHP Session ID + IP address + User Agent.
	 * 
	 * @return 	string 	the md5 hash of the visitor's fingerprint
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected function generateFingerprintHash()
	{
		// get the current Session ID
		$sid = @session_id();
		if (empty($sid)) {
			if (class_exists('JSession') && method_exists('JSession', 'getFormToken')) {
				$sid = JSession::getFormToken();
			} else {
				$sid = rand();
			}
		}

		// get visitor IP
		$client_ip = $this->getIpAddress();

		// get visitor user agent
		$visitorua = JFactory::getApplication()->input->server->getString('HTTP_USER_AGENT', '');

		// return the md5 hash of the signature
		return md5($sid . $client_ip . $visitorua);
	}

	/**
	 * Generates or updates and returns the
	 * tracking info identifier that will be
	 * cleared after the tracking conversion.
	 * This is useful to group later the various
	 * tracking info records into precise processes.
	 * 
	 * @return 	int 	the tracking info identifier
	 */
	protected function getIdentifier()
	{
		$session = JFactory::getSession();

		$sess_identifier = $session->get('vboTidentifier', '');

		if (!empty($sess_identifier)) {
			// get the identifier from the session
			static::$identifier = (int)$sess_identifier;
		} else {
			// generate a new tracking info identifier
			static::$identifier = time();
			// update the session
			$session->set('vboTidentifier', static::$identifier);
		}

		return static::$identifier;
	}

	/**
	 * Gets and stores in the session the referrer string.
	 * This method is called when the object is instantiated,
	 * but the headers are only available after a redirect from
	 * another site, and they may not be available all the times.
	 * 
	 * @return 	string 	the referrer string, empty string if none
	 */
	protected function getReferrer()
	{
		if (!empty(static::$referrer)) {
			// if previously registered, return it
			return static::$referrer;
		}

		// rely on the session, cookies or headers
		$session = JFactory::getSession();
		$input 	 = JFactory::getApplication()->input;
		$cookie  = $input->cookie;
		$baseuri = JUri::root();

		$sess_referrer 	= $session->get('vboTreferrer', '');
		$ck_referrer 	= $cookie->get('vboTProv', '', 'string');
		$sess_channel 	= $session->get('vcmChannelData', '');

		if (!empty($sess_referrer)) {
			// get the referrer from the session
			static::$referrer = $sess_referrer;
		} elseif (!empty($ck_referrer)) {
			// get the referrer from the cookie
			static::$referrer = $ck_referrer;
		} elseif (is_array($sess_channel) && !empty($sess_channel['name'])) {
			/**
			 * Get the referrer from VCM in case of meta-search booking links.
			 * VCM is invoked before the VBO Tracker, and so we can rely on it.
			 * 
			 * @since 	1.15.0 (J) - 1.5.0 (WP)
			 */
			static::$referrer = $sess_channel['name'];
		} else {
			// try to get the referrer from the HTTP headers
			$provenience = $input->server->getString('HTTP_REFERER', '');

			if (!empty($provenience) && strpos($provenience, $baseuri) !== false) {
				// this could be an internal redirect made by the CMS to set the language (Joomla) or update data
				$provenience = '';
			}

			if (empty($provenience)) {
				// try to get the provenience from the campaign requests
				$rqdata = $input->getArray();
				foreach (self::loadCampaigns() as $rkey => $cval) {
					if (isset($rqdata[$rkey])) {
						if (!empty($cval['value'])) {
							if ($rqdata[$rkey] == $cval['value']) {
								// request key is set and matches the value so we take this campaign as provenience
								$provenience = $cval['name'];
								break;
							}
						} else {
							// request key is set and no value is needed so we take this campaign as provenience
							$provenience = $cval['name'];
							break;
						}
					}

				}
			}

			if (!empty($provenience)) {
				// store the provenience in the browser cookie
				VikRequest::setCookie('vboTProv', $provenience, floor((time() + (86400 * (float)self::loadSettings('trkcookierfrdur')))), '/');
			}

			// register the variable
			static::$referrer = $provenience;
		}

		// update the session
		$session->set('vboTreferrer', static::$referrer);

		return static::$referrer;
	}

	/**
	 * Inserts a new main tracking record onto the db.
	 * 
	 * @return 	string 	the IP address of the visitor
	 */
	protected function getIpAddress()
	{
		$client_ip = '';

		// get server super global
		$srv = JFactory::getApplication()->input->server;

		// vars identifying the remote's IP address
		$ipvars = array(
			'REMOTE_ADDR',
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED'
		);

		// seek for the visitor's IP address from several vars
		do {
			
			// the var to look for in the super global
			$ipvar = array_shift($ipvars);

			// get the visitor IP address from the super global
			$client_ip = $srv->getString($ipvar, '');

		} while (empty($client_ip) && count($ipvars));

		return $client_ip;
	}

	/**
	 * Attempts to find and return the record of the given fingerprint tracking.
	 * If tracking has been disabled for this ID, boolean false is returned.
	 *
	 * @param 	string 	$id 	the md5 hash of the fingerprint to look for
	 *
	 * @return 	mixed 	the array record for the found fingerprint tracking, or
	 * 					an empty array. False if this Tracking ID was unpublished.
	 */
	protected function loadFingerprintData($id)
	{
		if (empty($id)) {
			return array();
		}

		$q = "SELECT * FROM `#__vikbooking_trackings` WHERE `fingerprint`=".$this->dbo->quote($id).";";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$data = $this->dbo->loadAssoc();
			return $data['published'] ? $data : false;
		}

		return array();
	}

	/**
	 * Returns a specific country data information, if anything was found.
	 * 
	 * @param 	string 	$country 	the country to look for.
	 * @param 	int 	$type 		the type of data to get.
	 * 
	 * @return 	string 	either the 2-char, 3-char or full country name, or null.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected function getCountryData($country, $type = 3)
	{
		if (empty($country) || !is_string($country)) {
			return null;
		}
		$country = trim($country);

		if (strlen($country) == 2) {
			$clause = '`country_2_code`=' . $this->dbo->quote($country);
		} elseif (strlen($country) == 3) {
			$clause = '`country_3_code`=' . $this->dbo->quote($country);
		} elseif (strlen($country) == 3) {
			$clause = '`country_name` LIKE ' . $this->dbo->quote("%{$country}%");
		}

		$q = "SELECT * FROM `#__vikbooking_countries` WHERE $clause";
		$this->dbo->setQuery($q, 0, 1);
		$this->dbo->execute();
		if (!$this->dbo->getNumRows()) {
			return null;
		}
		$country_data = $this->dbo->loadAssoc();

		if ($type == 2) {
			return $country_data['country_2_code'];
		}

		if ($type == 3) {
			return $country_data['country_3_code'];
		}

		return $country_data['country_name'];
	}

	/**
	 * Gets the current datetime string for SQL, either in UTC or local timezone.
	 * 
	 * @param 	bool 	$local 	true to use the current timezone, false for UTC datetime.
	 *
	 * @return 	string 			the datetime string in the desired timezone.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected function getCurrentSqlDateTime($local = true)
	{
		try {
			// get server's current datetime string
			$date = JFactory::getDate();
			$date->setTimezone(new DateTimeZone(date_default_timezone_get()));
			$now_date = $date->toSql($local);
		} catch (Exception $e) {
			$now_date = date('Y-m-d H:i:s');
		}

		return $now_date;
	}

	/**
	 * Inserts a new main tracking record onto the db.
	 *
	 * @return 	int 	the ID of the main tracking record created
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)  inserts object record and supports a given country, if set.
	 */
	protected function storeMainTracking()
	{
		// get SQL NOW() equivalent
		$now_date = $this->getCurrentSqlDateTime();

		// build record object
		$tracking_record = new stdClass;
		$tracking_record->dt = $now_date;
		$tracking_record->lastdt = $now_date;
		$tracking_record->fingerprint = static::$fingerprint;
		$tracking_record->ip = $this->getIpAddress();

		if (isset(static::$trackdata->country)) {
			$three_char_country = $this->getCountryData(static::$trackdata->country, 3);
			if (!empty($three_char_country)) {
				$tracking_record->country = $three_char_country;
			}
		}

		$this->dbo->insertObject('#__vikbooking_trackings', $tracking_record, 'id');

		return isset($tracking_record->id) ? (int)$tracking_record->id : 0;
	}

	/**
	 * Merges the current tracking data information to the previous ones.
	 * This is useful in case a step of the booking process does not push
	 * some information that were previously pushed, such as the room IDs,
	 * or the price IDs, but maybe it pushes other details, such as the new Booking ID.
	 * 
	 * @param 	int 	$track_info_id 	the ID of the previous tracking info record
	 *
	 * @return 	object 	the merged tracking data object
	 */
	protected function mergeTrackingData($track_info_id)
	{
		if (!is_object(static::$trackdata) || !count(get_object_vars(static::$trackdata))) {
			return static::$trackdata;
		}

		$q = "SELECT `trkdata` FROM `#__vikbooking_tracking_infos` WHERE `id`=".(int)$track_info_id.";";
		$this->dbo->setQuery($q);
		$prev_trackdata = $this->dbo->loadResult();
		if ($prev_trackdata) {
			$prev_trackdata = json_decode($prev_trackdata);
			if (!is_object($prev_trackdata)) {
				return static::$trackdata;
			}

			// merge new properties onto previous properties, whether they are missing or different
			foreach (static::$trackdata as $prop => $val) {
				if (!property_exists($prev_trackdata, $prop) || $prev_trackdata->$prop != $val) {
					$prev_trackdata->$prop = $val;
				}
			}

			return $prev_trackdata;
		}

		return static::$trackdata;
	}

	/**
	 * Pushes the dates requested onto the tracking data object.
	 * It's preferred to pass the dates as unix timestamps.
	 *
	 * @param 	string 	$checkin 	the checkin date, either a string or an integer
	 * @param 	string 	$checkout 	the checkout date, either a string or an integer
	 * @param 	int 	$night 		optional, the number of nights for the stay
	 *
	 * @return 	self 	for chainability.
	 */
	public function pushDates($checkin, $checkout, $nights = 0)
	{
		if (!is_numeric($checkin)) {
			// get timestamp from date string
			$checkin = VikBooking::getDateTimestamp($checkin, 0, 0);
		}
		if (!is_numeric($checkout)) {
			// get timestamp from date string
			$checkout = VikBooking::getDateTimestamp($checkout, 0, 0);
		}
		
		// prepare unix timestamps for sql format
		$checkin = JDate::getInstance(date('Y-m-d H:i:s', $checkin))->toSql();
		$checkout = JDate::getInstance(date('Y-m-d H:i:s', $checkout))->toSql();

		// register variables
		static::$trackdata->checkin = $checkin;
		static::$trackdata->checkout = $checkout;
		if ($nights > 0) {
			static::$trackdata->nights = $nights;
		}

		return static::$instance;
	}

	/**
	 * Pushes the party requested onto the tracking data object.
	 * Includes the guests per room and number of rooms
	 *
	 * @param 	array 	$guests 	the array of guests per room
	 *
	 * @return 	self 	for chainability.
	 */
	public function pushParty($guests)
	{
		if (is_array($guests) && count($guests)) {
			// register variable
			static::$trackdata->rooms_num 	= count($guests);
			static::$trackdata->party 		= $guests;
		}

		return static::$instance;
	}

	/**
	 * Pushes the rooms selected onto the tracking data object.
	 * Sets an array of key-value pairs (key = ID, value = units).
	 *
	 * @param 	mixed 	$rooms 		integer or array of integers for the room IDs selected
	 * @param 	mixed 	[$rplans] 	integer or array of integers for the price IDs selected
	 * @param 	mixed 	[$rindex] 	integer or array of integers for the room indexes selected (geomap)
	 *
	 * @return 	self 	for chainability.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP) 3rd argument $rindex introduced due to interactive geomap
	 */
	public function pushRooms($rooms, $rplans = array(), $rindex = array())
	{
		if (is_scalar($rooms)) {
			$rooms = array($rooms);
		}

		if (!empty($rplans) && is_scalar($rplans)) {
			$rplans = array($rplans);
		}

		if (!empty($rindex) && is_scalar($rindex)) {
			$rindex = array($rindex);
		}

		if (!property_exists(static::$trackdata, 'rooms')) {
			static::$trackdata->rooms = array();
		}

		// group the rooms by units requested and id
		$rooms_data = array();
		foreach ($rooms as $id) {
			if (!isset($rooms_data[$id])) {
				$rooms_data[$id] = 0;
			}
			$rooms_data[$id]++;
		}

		// register variable
		static::$trackdata->rooms = $rooms_data;

		if (is_array($rplans) && count($rplans) == count($rooms)) {
			// add also the information about the rate plans selected
			if (!property_exists(static::$trackdata, 'rplans')) {
				static::$trackdata->rplans = array();
			}

			// group the rate plans by units requested and id like the rooms
			$rplans_data = array();
			foreach ($rplans as $id) {
				if (!isset($rplans_data[$id])) {
					$rplans_data[$id] = 0;
				}
				$rplans_data[$id]++;
			}

			// register variable
			static::$trackdata->rplans = $rplans_data;
		}

		if (is_array($rindex) && count($rindex) == count($rooms)) {
			// register variable also for the information about the room indexes selected
			static::$trackdata->rindex = $rindex;
		}

		return static::$instance;
	}

	/**
	 * Pushes the esit onto the tracking data object.
	 * Multiple messages allowed.
	 *
	 * @param 	string 	$msg 	the message log for the tracking
	 * @param 	string 	$type 	the type of the message
	 *
	 * @return 	self 	for chainability.
	 */
	public function pushMessage($msg, $type = 'success')
	{
		if (!property_exists(static::$trackdata, 'msg')) {
			static::$trackdata->msg = array();
		}

		// register variable
		array_push(static::$trackdata->msg, array(
			'text' => $msg,
			'type' => $type
		));

		return static::$instance;
	}

	/**
	 * Pushes custom data onto the tracking data object.
	 * Any previously set property will be overridden.
	 *
	 * @param 	string 	$prop 	the name of the property
	 * @param 	mixed 	$val 	the value for the property
	 *
	 * @return 	self 	for chainability.
	 */
	public function pushData($prop, $val)
	{
		// list of reserved properties that won't be added to the $trackdata object
		$reserved_props = array(
			'referrer',
		);

		if (empty($prop)) {
			// do not proceed
			return static::$instance;
		}

		if ((is_string($val) && strlen($val)) || !empty($val)) {
			// check if the property is reserved
			if (in_array($prop, $reserved_props) && isset(static::${$prop})) {
				// replace internal/reserved class property
				static::${$prop} = $val;
			} else {
				// register variable
				static::$trackdata->$prop = $val;
			}
		}

		return static::$instance;
	}

	/**
	 * Closes the current tracking process by preventing multiple calls.
	 * This method should be called before the end of the execution process.
	 *
	 * @return 	int 	whether the track was closed
	 */
	public function closeTrack()
	{
		static $track_closed = null;

		if (!$track_closed) {
			// prevent the track from being closed multiple times
			$track_closed = 1;

			// abort if tracking is disabled or framework is admin
			if (!(int)self::loadSettings('trkenabled') || VikBooking::isAdmin()) {
				return 0;
			}

			// abort if no tracking data
			$arrtrack = (array)static::$trackdata;
			if (empty($arrtrack)) {
				return 0;
			}

			// close the track by storing the information
			$id_tracking = null;

			// get the tracking ID or abort (if tracking for this visitor is disabled)
			$prev_tracking = $this->loadFingerprintData((!is_null(static::$trk_fingerprint) ? static::$trk_fingerprint : static::$fingerprint));
			if ($prev_tracking === false) {
				// abort, tracking for this ID is disabled
				return 0;
			}
			if ($prev_tracking) {
				$id_tracking = $prev_tracking['id'];
			}
			if (!$id_tracking) {
				// store the main tracking record
				$id_tracking = $this->storeMainTracking();
				if (!$id_tracking) {
					return 0;
				}
			}

			// current dates requested
			$cur_checkin  = property_exists(static::$trackdata, 'checkin') ? static::$trackdata->checkin : '';
			$cur_checkout = property_exists(static::$trackdata, 'checkout') ? static::$trackdata->checkout : '';

			$session = JFactory::getSession();

			// previous tracking info identifier
			$prev_identifier 	= $session->get('vboTinfoId', '');
			$prev_identifier_id = 0;
			if (!empty($prev_identifier)) {
				$prev_parts = explode(';', $prev_identifier);
				if (count($prev_parts) > 2 && !empty($prev_parts[0]) && $prev_parts[1] == $cur_checkin && $prev_parts[2] == $cur_checkout) {
					// an equal previous identifier is available, we should not create a new tracking info record as the dates have not changed
					$prev_identifier_id = (int)$prev_parts[0];
				}
			}

			// get SQL NOW() equivalent
			$now_date = $this->getCurrentSqlDateTime();

			// store or update the tracking info record
			if (empty($prev_identifier_id)) {
				// detect user agent type (device)
				$device = isset(static::$trackdata->device) ? static::$trackdata->device : '';
				if (!empty($device)) {
					/**
					 * Validate device type: "mobile", "tablet", "desktop", "unknown" should be converted to either
					 * "smartphone", "tablet", "computer" or empty string for compliance with detectUserAgent().
					 * 
					 * @since 	1.15.0 (J) - 1.5.0 (WP)
					 */
					if (!strcasecmp($device, 'desktop') || !strcasecmp($device, 'unknown')) {
						$device = 'computer';
					} elseif (!strcasecmp($device, 'mobile')) {
						$device = 'smartphone';
					}
				}
				$uatype = !empty($device) ? $device : VikBooking::detectUserAgent(true, false);
				$uatype = !empty($uatype) ? strtoupper(substr($uatype, 0, 1)) : '';

				// insert a new tracking info record
				$trk_info_record = new stdClass;
				$trk_info_record->idtracking = (int)$id_tracking;
				$trk_info_record->identifier = $this->getIdentifier();
				$trk_info_record->trackingdt = $now_date;
				$trk_info_record->device = $uatype;
				$trk_info_record->trkdata = json_encode(static::$trackdata);
				$trk_info_record->checkin = !empty($cur_checkin) ? $cur_checkin : null;
				$trk_info_record->checkout = !empty($cur_checkout) ? $cur_checkout : null;
				$trk_info_record->idorder = isset(static::$trackdata->idorder) ? (int)static::$trackdata->idorder : 0;
				$trk_info_record->referrer = !empty(static::$referrer) ? static::$referrer : null;

				/**
				 * Trigger event for the tracking information being saved.
				 * 
				 * @since 	1.16.2 (J) - 1.6.2 (WP)
				 */
				VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeSaveTrackingInformationVikBooking', [$trk_info_record, (int)$id_tracking, $this]);

				// store record
				$this->dbo->insertObject('#__vikbooking_tracking_infos', $trk_info_record, 'id');

				$trkinfo_id = isset($trk_info_record->id) ? (int)$trk_info_record->id : 0;
				if (!$trkinfo_id) {
					return 0;
				}

				// store in the session the tracking info identifier
				$identifier = array(
					$trkinfo_id,
					$cur_checkin,
					$cur_checkout
				);
				$session->set('vboTinfoId', implode(';', $identifier));
			} else {
				// merge tracking data from previous tracking info with current info
				$new_trackdata = $this->mergeTrackingData($prev_identifier_id);

				// update an existing tracking info record
				$trk_info_record = new stdClass;
				$trk_info_record->id = $prev_identifier_id;
				$trk_info_record->trackingdt = $now_date;
				$trk_info_record->trkdata = json_encode($new_trackdata);
				$trk_info_record->checkin = !empty($cur_checkin) ? $cur_checkin : null;
				$trk_info_record->checkout = !empty($cur_checkout) ? $cur_checkout : null;
				if (isset(static::$trackdata->idorder)) {
					$trk_info_record->idorder = (int)static::$trackdata->idorder;
				}

				/**
				 * Trigger event for the tracking information being saved.
				 * 
				 * @since 	1.16.2 (J) - 1.6.2 (WP)
				 */
				VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeSaveTrackingInformationVikBooking', [$trk_info_record, (int)$id_tracking, $this]);

				// update record
				$this->dbo->updateObject('#__vikbooking_tracking_infos', $trk_info_record, 'id');
			}

			// update main tracking record
			$main_record = new stdClass;
			$main_record->id = (int)$id_tracking;
			$main_record->lastdt = $now_date;
			if (!is_null(static::$trk_fingerprint)) {
				// the real visitor has received a new fingerprint, so we update it on the main tracking record
				$main_record->fingerprint = static::$fingerprint;
				// update the IP address for the actual visitor and replace a possible e4jConnect server IP address
				$main_record->ip = $this->getIpAddress();
			}
			if (isset(static::$trackdata->idcustomer)) {
				$main_record->idcustomer = (int)static::$trackdata->idcustomer;
			}
			$this->dbo->updateObject('#__vikbooking_trackings', $main_record, 'id');

		}

		return $track_closed;
	}

	/**
	 * Resets the class and session variables to allow a new tracking.
	 * This should be called after the conversion is reached and done.
	 *
	 * @return 	void
	 */
	public function resetTrack()
	{
		static::$instance 	 	 = null;
		static::$fingerprint 	 = null;
		static::$trk_fingerprint = null;
		static::$trackdata 	 	 = new stdClass;

		$session = JFactory::getSession();
		$session->set('vboTinfoId', '');
		$session->set('vboTidentifier', '');
		$session->set('vboTreferrer', '');
	}

	/**
	 * Returns the current tracking data object.
	 * 
	 * @return 	object 	the current track data.
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP)
	 */
	public function getTrackData()
	{
		return static::$trackdata;
	}

	/**
	 * Returns a registry or array for the current extra information.
	 * 
	 * @param 	bool 	$registry 	whether to get the registry object or array.
	 * 
	 * @return 	JObject|array
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public function getExtraInfo($registry = true)
	{
		return $registry ? (new JObject(static::$extra_info)) : static::$extra_info;
	}

	/**
	 * Sets a value for an extra information type.
	 * 
	 * @param 	string 	$key 	the information key in the associative array.
	 * @param 	mixed 	$value 	the value to set for the given key.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public function setExtraInfo($key, $value = null)
	{
		static::$extra_info[$key] = $value;

		return $this;
	}

	/**
	 * Runs when visiting the booking details page in case conversion must be triggered.
	 * 
	 * @param 	array 	$booking 	the booking record for conversion.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP)
	 */
	public function triggerBookingConversion(array $booking)
	{
		$fingerprint = $this->getFingerprint();

		$q = $this->dbo->getQuery(true);

		$q->select($this->dbo->qn([
			'i.id',
			'i.trkdata',
			'i.checkin',
			'i.checkout',
			'i.idorder',
			'i.referrer',
			't.geo',
			't.country',
			't.idcustomer',
		]));
		$q->from($this->dbo->qn('#__vikbooking_tracking_infos', 'i'));
		$q->leftJoin($this->dbo->qn('#__vikbooking_trackings', 't') . ' ON ' . $this->dbo->qn('t.id') . ' = ' . $this->dbo->qn('i.idtracking'));
		$q->where($this->dbo->qn('t.fingerprint') . ' = ' . $this->dbo->q($fingerprint));
		$q->where($this->dbo->qn('i.idorder') . ' = ' . (int)$booking['id']);
		$q->order($this->dbo->qn('i.trackingdt') . ' DESC');

		$this->dbo->setQuery($q, 0, 1);

		$last_tracking_record = $this->dbo->loadObject();

		if (!$last_tracking_record) {
			// tracking must be enabled and functioning
			return $this;
		}

		$prev_trackdata = json_decode($last_tracking_record->trkdata);
		if (!is_object($prev_trackdata)) {
			// no previous tracking data available
			return $this;
		}

		/**
		 * Check if tracking was made for the current booking status.
		 * 
		 * @since 	1.16.7 (J) - 1.6.7 (WP)
		 */
		$converted_status_prop = 'converted_' . strtolower($booking['status']);

		if (!isset($prev_trackdata->{$converted_status_prop})) {
			// proceed with triggering the conversion event for this status

			// ensure no more tracking events will be triggered for the current status
			$prev_trackdata->{$converted_status_prop} = 1;

			$upd_record = new stdClass;
			$upd_record->id = $last_tracking_record->id;
			$upd_record->trkdata = json_encode($prev_trackdata);

			$this->dbo->updateObject('#__vikbooking_tracking_infos', $upd_record, 'id');

			// trigger event to allow third party plugins to perform a tracking of the booking conversion only once
			VBOFactory::getPlatform()->getDispatcher()->trigger('onBookingConversionTrackingVikBooking', [$booking, $last_tracking_record]);
		}

		return $this;
	}

	/**
	 * Retrieves information about a given IP address.
	 * This method does NOT require getInstance() to
	 * be called to instantiate the object. It was made
	 * to be used from the admin section of the site.
	 * Returned array keys are maintained for mapping.
	 *
	 * @param 	mixed 	$ips 	the visitors IP address(es) as a string or array
	 *
	 * @return 	mixed 	array on success, false on failure
	 */
	public static function getIpGeoInfo($ips)
	{
		if (is_scalar($ips)) {
			$ips = array($ips);
		}
		
		if (empty($ips)) {
			return false;
		}

		// pool of data to be returned
		$geo_info = array();

		// buffer to cache IPs already parsed to avoid double queries for same IPs
		$buffer = array();

		// request endpoint
		$endpoint = 'https://'.'ip'.'info'.'.'.'io'.'/'.'%s'.'/'.'geo';

		// iterate through the IPs requested
		foreach ($ips as $k => $ip) {
			if (isset($buffer[$ip])) {
				// this IP address was already requested
				$geo_info[$k] = $buffer[$ip];
				continue;
			}

			$geo_info[$k] = null;
			if (empty($ip)) {
				continue;
			}
			// make the curl request to obtain the information
			$try = 0;
			$curl_errno = 0;
			$curl_err = '';
			do {
				$ch = curl_init(sprintf($endpoint, $ip));
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($ch, CURLOPT_TIMEOUT, 20);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$serverres = curl_exec($ch);
				if ($curl_errno = curl_errno($ch)) {
					$curl_err = curl_error($ch);
				} else {
					$curl_errno = 0;
					$curl_err = '';
				}
				curl_close($ch);
				$try++;
			} while ($try < 2 && $curl_errno > 0 && in_array($curl_errno, array(2, 6, 7, 28)));

			if ($curl_errno) {
				$geo_info[$k] = false;
				continue;
			}

			// decode response
			$resp = json_decode($serverres, true);
			if (!is_array($resp) || !isset($resp['city']) || !isset($resp['country'])) {
				// invalid response or missing required data
				$geo_info[$k] = false;
				continue;
			}

			// cache result for this IP
			$buffer[$ip] = $resp;

			// push result
			$geo_info[$k] = $resp;
		}

		return $geo_info;
	}

	/**
	 * Returns information about the differences between two dates.
	 * This method does NOT require getInstance() to
	 * be called to instantiate the object. It was made
	 * to be used from the admin section of the site.
	 * The dates passed should be either Unix timestamps, or
	 * strings in a format compatible with strtotime().
	 * The first date is supposed to be greater than the second.
	 *
	 * @param 	mixed 	$first 		int unix timestamp or string formatted date
	 * @param 	mixed 	$second 	int unix timestamp or string formatted date
	 *
	 * @return 	array 	the information about the differences (max type = hours)
	 */
	public static function datesDiff($first, $second)
	{
		// make sure dates are converted to timestamps
		if (!is_numeric($first)) {
			$first = strtotime($first);
		} else {
			$first = (int)$first;
		}
		if (!is_numeric($second)) {
			$second = strtotime($second);
		} else {
			$second = (int)$second;
		}

		// seconds of difference
		$diff = abs($first - $second);

		if ($diff < 60) {
			// just some seconds of difference
			return array(
				'diff' => $diff,
				'type' => 'seconds'
			);
		}

		if ($diff < 3600) {
			// minutes of difference
			return array(
				'diff' => round(($diff / 60), 0, PHP_ROUND_HALF_UP),
				'type' => 'minutes'
			);
		}

		// hours of difference
		return array(
			'diff' => round(($diff / 3600), 0, PHP_ROUND_HALF_UP),
			'type' => 'hours'
		);
	}

	/**
	 * Loads the tracking settings or one specific setting
	 *
	 * @param 	string 	$key 	an optional key for the setting to load
	 *
	 * @return 	mixed 	tracking settings array or the value for the requested setting key
	 */
	public static function loadSettings($key = '')
	{
		$dbo = JFactory::getDbo();

		// configuration settings keys for tracking
		$validkeys = array(
			'trkenabled',
			'trkcookierfrdur',
			'trkcampaigns',
		);

		if (!empty($key)) {
			$validkeys = array($key);
		}

		// quote keys for query
		$query_keys = array_map(array($dbo, 'quote'), $validkeys);

		// load settings from db
		$q = "SELECT `param`, `setting` FROM `#__vikbooking_config` WHERE `param` IN (".implode(', ', $query_keys).");";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return false;
		}
		$res = $dbo->loadAssocList();
		$settings = array();
		foreach ($res as $s) {
			$settings[$s['param']] = $s['setting'];
		}

		return !empty($key) && count($settings) < 2 ? $settings[$key] : $settings;
	}

	/**
	 * Loads all the current campaigns from the settings
	 *
	 * @return 	array 	all the campaigns decoded data
	 *
	 * @uses 	loadSettings()
	 */
	public static function loadCampaigns()
	{
		$campaigns = array();

		$data = self::loadSettings('trkcampaigns');
		if (!empty($data)) {
			$campaigns = json_decode($data, true);
			$campaigns = !is_array($campaigns) ? array() : $campaigns;
		}

		return $campaigns;
	}

	/**
	 * Counts tracked records in a given time frame.
	 * Firstly developed for the admin widget "visitors counter".
	 * 
	 * @param 	string 	$from_date 	start date in Y-m-d H:i:s format.
	 * @param 	string 	$to_date 	end date in Y-m-d H:i:s format.
	 *
	 * @return 	int 	the total number of tracking records found.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function countTrackedRecords($from_date = null, $to_date = null)
	{
		$dbo = JFactory::getDbo();

		$total_records = 0;

		$filters = array();
		if (!empty($from_date)) {
			array_push($filters, '`t`.`lastdt` >= '.$dbo->quote(JDate::getInstance($from_date)->toSql()));
		}
		if (!empty($to_date)) {
			array_push($filters, '`t`.`lastdt` <= '.$dbo->quote(JDate::getInstance($to_date)->toSql()));
		}
		// exclude records with tracking status disabled
		array_push($filters, '`t`.`published` = 1');

		$q = "SELECT COUNT(*) FROM `#__vikbooking_trackings` AS `t` WHERE " . implode(' AND ', $filters);
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$total_records = $dbo->loadResult();
		}

		return $total_records;
	}

	/**
	 * Gets the global statistics for a given time frame. This includes
	 * the most demanded nights, conversion rates and best referrers.
	 * Firstly developed for the VCM framework of the e4jConnect App.
	 * 
	 * @param 	string 	$from_date 	start date in Y-m-d format.
	 * @param 	string 	$to_date 	end date in Y-m-d format.
	 * @param 	int 	$dt_type 	type of dates identifier (tracking, booking etc..).
	 *
	 * @return 	array 				empty or associative array with statistics.
	 * 
	 * @since 	1.15.3 (J) - 1.5.5 (WP)
	 */
	public static function getStatistics($from_date, $to_date, $dt_type = 1)
	{
		if (empty($from_date) || empty($to_date)) {
			return [];
		}

		$dbo = JFactory::getDbo();

		// dates information
		$from_info 	   = getdate(strtotime($from_date));
		$to_info   	   = getdate(strtotime($to_date));
		$from_start_ts = mktime(0, 0, 0, $from_info['mon'], $from_info['mday'], $from_info['year']);
		$from_end_ts   = mktime(23, 59, 59, $from_info['mon'], $from_info['mday'], $from_info['year']);
		$to_start_ts   = mktime(0, 0, 0, $to_info['mon'], $to_info['mday'], $to_info['year']);
		$to_end_ts 	   = mktime(23, 59, 59, $to_info['mon'], $to_info['mday'], $to_info['year']);

		// query filters
		$filters = [];

		if ($dt_type == 2) {
			// booking dates
			$bookdatesfilt = [];
			// filter from-date inside a range of dates booked (we use 23:59:59 to avoid calculating the check-in time)
			array_push(
				$bookdatesfilt, 
				'(' . 
					'`i`.`checkin` <= ' . $dbo->quote(date('Y-m-d H:i:s', $from_end_ts)) . 
					' AND ' . 
					'`i`.`checkout` >= ' . $dbo->quote(date('Y-m-d H:i:s', $from_start_ts)) . 
				')'
			);
			// filter to-date inside a range of dates booked (we use 00:00:00 to avoid calculating the check-out time)
			array_push(
				$bookdatesfilt, 
				'(' . 
					'`i`.`checkin` <= ' . $dbo->quote(date('Y-m-d H:i:s', $to_end_ts)) . 
					' AND ' . 
					'`i`.`checkout` >= ' . $dbo->quote(date('Y-m-d H:i:s', $to_start_ts)) . 
				')'
			);
			// filter dates including booking dates (bigger than)
			array_push(
				$bookdatesfilt, 
				'(' . 
					'`i`.`checkin` >= ' . $dbo->quote(date('Y-m-d H:i:s', $from_start_ts)) . 
					' AND ' . 
					'`i`.`checkout` <= ' . $dbo->quote(date('Y-m-d H:i:s', $to_end_ts)) . 
				')'
			);
			// push all clauses to filters
			array_push($filters, '(' . implode(' OR ', $bookdatesfilt) . ')');
		} elseif ($dt_type == 3) {
			// checkin date
			array_push($filters, '`i`.`checkin` >= ' . $dbo->quote(date('Y-m-d H:i:s', $from_start_ts)));
			array_push($filters, '`i`.`checkin` <= ' . $dbo->quote(date('Y-m-d H:i:s', $to_end_ts)));
		} elseif ($dt_type == 4) {
			// checkout date
			array_push($filters, '`i`.`checkout` >= ' . $dbo->quote(date('Y-m-d H:i:s', $from_start_ts)));
			array_push($filters, '`i`.`checkout` <= ' . $dbo->quote(date('Y-m-d H:i:s', $to_end_ts)));
		} else {
			// default (1) tracking dates
			array_push($filters, '`t`.`lastdt` >= ' . $dbo->quote(date('Y-m-d H:i:s', $from_start_ts)));
			array_push($filters, '`t`.`lastdt` <= ' . $dbo->quote(date('Y-m-d H:i:s', $to_end_ts)));
		}

		// calculate most demanded nights, conversion rates, best referrers
		$tomorrowdt = JDate::getInstance(date('Y-m-d', strtotime('tomorrow')))->toSql();
		$q = "SELECT `i`.`id`, `i`.`idtracking`, `i`.`identifier`, `i`.`checkin`, `i`.`checkout`, `i`.`idorder`, `i`.`referrer`, `t`.`lastdt`, `t`.`published` 
			FROM `#__vikbooking_tracking_infos` AS `i` 
			LEFT JOIN `#__vikbooking_trackings` AS `t` ON `i`.`idtracking`=`t`.`id` 
			WHERE `t`.`published`=1 AND " . (count($filters) ? implode(' AND ', $filters) : '`i`.`checkin` > ' . $dbo->quote($tomorrowdt)) . " 
			ORDER BY `i`.`checkin` ASC, `i`.`id` DESC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// no data found
			return [];
		}

		// grab all records
		$stats_data = $dbo->loadAssocList();

		// calculate statistics
		$demands_nights = [];
		$demands_count  = [];
		$referrer_count = [];
		$totidentifiers = [];
		$totbookings 	= [];
		$los_pool 		= [];
		foreach ($stats_data as $stat) {
			if (!empty($stat['referrer'])) {
				if (!isset($referrer_count[$stat['referrer']])) {
					$referrer_count[$stat['referrer']] = 0;
				}
				$referrer_count[$stat['referrer']]++;
			}
			if (!isset($totidentifiers[$stat['identifier']])) {
				// total identifiers
				$totidentifiers[$stat['identifier']] = 1;
			}
			if (!empty($stat['idorder']) && !isset($totbookings[$stat['identifier']])) {
				// one conversion per tracking identifier
				$totbookings[$stat['identifier']] = $stat['idorder'];
			}
			// loop through the nights of this tracking info record
			$in_ts 	 = strtotime($stat['checkin']);
			$in_info = getdate($in_ts);
			$out_ts  = strtotime($stat['checkout']);
			$out_dt  = date('Y-m-d', $out_ts);
			$in_dt   = date('Y-m-d', $in_info[0]);
			$now_los = 0;
			while ($in_dt != $out_dt) {
				if (!($in_ts < $out_ts)) {
					// prevent any possible loop in case of records with invalid data
					break;
				}
				$now_los++;
				if (!isset($demands_nights[$in_dt])) {
					$demands_nights[$in_dt] = 0;
				}
				// increase the requests for this night
				$demands_nights[$in_dt]++;
				if (!isset($demands_count[$in_dt])) {
					$demands_count[$in_dt] = array();
				}
				if (!in_array($stat['idtracking'], $demands_count[$in_dt])) {
					// push this visitor (tracking) ID to the counter for this night
					array_push($demands_count[$in_dt], $stat['idtracking']);
				}
				// update next loop
				$in_info = getdate(mktime(0, 0, 0, $in_info['mon'], ($in_info['mday'] + 1), $in_info['year']));
				$in_dt   = date('Y-m-d', $in_info[0]);
			}
			array_push($los_pool, $now_los);
		}

		// sort most demanded nights and best referrers
		arsort($demands_nights);
		arsort($referrer_count);

		// count values that could be 0
		$cnt_tot_idfs = count($totidentifiers);
		$cnt_tot_idfs = $cnt_tot_idfs > 0 ? $cnt_tot_idfs : 1;
		$cnt_los_pool = count($los_pool);
		$cnt_los_pool = $cnt_los_pool > 0 ? $cnt_los_pool : 1;

		// average conversion rate: 100 : totidentifiers = x : totbookings
		$avg_conv_rate = 100 * count($totbookings) / $cnt_tot_idfs;
		$avg_conv_rate = round($avg_conv_rate, 2);

		// average length of stay
		$avg_los = array_sum($los_pool) / $cnt_los_pool;
		$avg_los = round($avg_los, 1);

		// compose associative values to be returned
		return [
			'from_date' 	  => $from_date,
			'to_date' 	  	  => $to_date,
			'date_type' 	  => $dt_type,
			'demanded_nights' => $demands_nights,
			'demanded_ncount' => $demands_count,
			'referrers' 	  => $referrer_count,
			'bookings' 		  => $totbookings,
			'avg_los' 		  => $avg_los,
			'avg_conv_rate'   => $avg_conv_rate,
			'tot_visitors' 	  => $cnt_tot_idfs,
			'tot_bookings' 	  => count($totbookings),
		];
	}
}

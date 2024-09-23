<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Multitask data extraction for admin widgets.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOMultitaskParser
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var  VBOMultitaskFactory
	 */
	private static $instance = null;

	/**
	 * The view/task where multitask is being used.
	 * 
	 * @var  string
	 */
	private $page_name = '';

	/**
	 * The page URI where multitask is being used.
	 * 
	 * @var  string
	 */
	private $page_uri = '';

	/**
	 * The page query values.
	 * 
	 * @var  array
	 */
	private $page_query = [];

	/**
	 * Returns the global object to immediately support chaining.
	 * Either a new instance or the existing instance.
	 * 
	 * @param 	string 	$page 	the name of the view/task.
	 * @param 	string 	$uri 	the page URI to parse.
	 *
	 * @return 	self
	 */
	public static function getInstance($page = '', $uri = '')
	{
		if (is_null(static::$instance)) {
			static::$instance = new static($page, $uri);
		}

		return static::$instance;
	}

	/**
	 * Those pages capable of receiving data for a clicked Push notification through
	 * query string can use this method to quickly validate and parse the admin widget
	 * to load and the multitask data + options to set for rendering the information.
	 * 
	 * @param 	array 	$data 		associative list of multitask data.
	 * @param 	mixed 	$payload 	JSON-encoded string or decoded object payload.
	 * 
	 * @return 	array 				numeric list of widget ID, data and options.
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public static function queryPushData(array $data, $payload)
	{
		$parsed = [
			'widget_id' 		=> '',
			'multitask_data' 	=> $data,
			'multitask_options' => [],
		];

		if (empty($payload)) {
			// do not proceed
			return array_values($parsed);
		}

		if (is_string($payload)) {
			// attempt to JSON-decode the payload encoded string
			$decoded_payload = json_decode($payload);

			/**
			 * Payloads may have been previously encoded in base64, which expects binary data as its input.
			 * In case of titles or messages in the Push notification payload with multi-byte characters,
			 * JSON-decoding the string that was base64-decoded, may result into malformed UTF-8 characters.
			 * In this case, we will try to convert any character to ASCII by ignoring what's not UTF-8.
			 */
			if (json_last_error() && json_last_error() === JSON_ERROR_UTF8) {
				// try to convert the encoding from UTF-8 to any ASCII
				$payload = json_decode(@iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $payload));
			} else {
				// assign the properly decoded value
				$payload = $decoded_payload;
			}
		}

		if (is_object($payload) && isset($payload->type) && (isset($payload->title) || isset($payload->message))) {
			// determine the type of widget that should handle the notification data
			$parsed['widget_id'] = $payload->type == 'Chat' ? 'guest_messages' : 'booking_details';

			// extract and remove raw content from payload
			$raw_content = isset($payload->content) ? (array)$payload->content : [];
			unset($payload->content);

			// build multitask options
			$parsed['multitask_options'] = array_merge(['_push' => 1], (array)$payload, $raw_content);
		}

		return array_values($parsed);
	}

	/**
	 * Class constructor.
	 * 
	 * @param 	string 	$page 	the name of the view/task.
	 * @param 	string 	$uri 	the page URI to parse.
	 */
	public function __construct($page = '', $uri = '')
	{
		// set name for view/task to consider
		$this->setPageName($page);

		// set URI to parse
		$this->setPageURI($uri);
	}

	/**
	 * Builds a multitask data object with the page information
	 * ready to be passed to the admin widget being rendered.
	 * 
	 * @return 	VBOMultitaskData
	 */
	public function getData()
	{
		/**
		 * Rendering an admin widget does support injected request values.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$injected_data = JFactory::getApplication()->input->get('multitask_data', [], 'array');

		// extra the options for multitask data, if any
		$data_options = [];
		if (isset($injected_data['_options'])) {
			// access the options received
			$data_options = (array)$injected_data['_options'];

			// separate options layer from regular data
			unset($injected_data['_options']);
		}

		// get a new multitask data object instance
		$multitask_data = new VBOMultitaskData($injected_data);

		// inject current page values
		$multitask_data->setPage($this->getPageName());
		$multitask_data->setURI($this->getPageURI());
		$multitask_data->setQuery($this->getPageQueryValues());

		// check if the page has a booking
		$page_booking_id = $this->getPageBookingId();
		if ($page_booking_id) {
			$multitask_data->setBookingId($page_booking_id);
		}

		/**
		 * Register a separate layer of data that will serve as multitask options.
		 * 
		 * @since 	1.16.5 (J) - 1.6.5 (WP)
		 */
		$multitask_data->registerDataOptions($data_options);

		// return the data object for the admin widget
		return $multitask_data;
	}

	/**
	 * Binds data to the multitask options object and returns it.
	 * 
	 * @return 	VBOMultitaskOptions
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public function getOptions()
	{
		return VBOMultitaskOptions::getInstance(JFactory::getApplication()->input->get('_options', [], 'array'));
	}

	/**
	 * Gets the current name of the view/task.
	 * 
	 * @return 	string
	 */
	public function getPageName()
	{
		return $this->page_name;
	}

	/**
	 * Sets the current name of the view/task.
	 * 
	 * @param 	string 	$page 	the name of the view/task.
	 * 
	 * @return 	self
	 */
	public function setPageName($page = '')
	{
		$this->page_name = $page;

		return $this;
	}

	/**
	 * Gets the current page URI.
	 * 
	 * @return 	string
	 */
	public function getPageURI()
	{
		return $this->page_uri;
	}

	/**
	 * Sets the current page URI.
	 * 
	 * @param 	string 	$uri 	the page URI to parse
	 * 
	 * @return 	self
	 */
	public function setPageURI($uri = '')
	{
		$this->page_uri = $uri;

		// parse current URI
		$this->parseCurrentURI();

		return $this;
	}

	/**
	 * Gets the current values for the page query.
	 * 
	 * @return 	array
	 */
	public function getPageQueryValues()
	{
		return $this->page_query;
	}

	/**
	 * Parses the current URI to detect values to extract.
	 * 
	 * @return 	void
	 */
	private function parseCurrentURI()
	{
		// reset query values
		$this->page_query = [];

		if (empty($this->page_uri)) {
			// nothing to parse
			return;
		}

		// parse URL data
		$uri_data = parse_url($this->page_uri);

		if (!isset($uri_data['query'])) {
			// no query values to parse
			return;
		}

		// parse query variables
		$uri_data['query'] = str_replace('&amp;', '&', $uri_data['query']);

		// inject parsed string values in page_query
		parse_str($uri_data['query'], $this->page_query);

		// adjust values, if needed
		if (empty($this->page_name)) {
			// try guessing the page from the query values
			if (!empty($this->page_query['view'])) {
				$this->page_name = $this->page_query['view'];
			} elseif (!empty($this->page_query['task'])) {
				$this->page_name = $this->page_query['task'];
			}
		}
	}

	/**
	 * Checks whether the page contains a booking.
	 * 
	 * @return 	bool|int 	false on failure or reservation ID.
	 */
	private function getPageBookingId()
	{
		if (!is_array($this->page_query) || !count($this->page_query)) {
			return false;
		}

		// list of pages that can contain a reservation
		$pages_with_booking = [
			'editorder',
			'editbusy',
			'bookingcheckin',
		];

		if (!in_array($this->page_name, $pages_with_booking)) {
			return false;
		}

		if (!isset($this->page_query['cid']) || !is_array($this->page_query['cid'])) {
			return false;
		}

		if (empty($this->page_query['cid'][0])) {
			return false;
		}

		// booking ID found
		return (int)$this->page_query['cid'][0];
	}
}

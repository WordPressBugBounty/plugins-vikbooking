<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking model shorten URL.
 * 
 * @since 	1.16.10 (J) - 1.6.10 (WP)
 */
class VBOModelShortenurl
{
	/** @var bool */
	protected $onlyRouted = false;

	/** @var string */
	protected $sequenceParamName = 'to';

	/** @var array */
	protected $booking = [];

	/**
	 * Proxy for immediately accessing the object.
	 * 
	 * @param 	bool 	$onlyRouted         True if the "tinyurl" menu-item/shortcode must exist.
	 * @param 	string 	$sequenceParamName  The sequence param name.
	 * 
	 * @return 	VBOModelShortenurl
	 */
	public static function getInstance($onlyRouted = false, string $sequenceParamName = 'to')
	{
		return new static($onlyRouted, $sequenceParamName);
	}

	/**
	 * Class constructor.
	 * 
	 * @param 	bool 	$onlyRouted         True if the "tinyurl" menu-item/shortcode must exist.
	 * @param 	string 	$sequenceParamName  The sequence param name.
	 */
	public function __construct($onlyRouted, string $sequenceParamName)
	{
		$this->onlyRouted = (bool) $onlyRouted;
		$this->sequenceParamName = $sequenceParamName;
	}

	/**
	 * Sets the current booking record details.
	 * 
	 * @param 	array 	$booking 	The booking record details.
	 * 
	 * @return 	self
	 */
	public function setBooking(array $booking)
	{
		$this->booking = $booking;

		return $this;
	}

	/**
	 * Routes a sequence into the original (long) URL previously shortened.
	 * 
	 * @param 	string 	$sequence 	The sequence code that identifies a URL.
	 * @param 	bool 	$hit 		True for increasing the record hits.
	 * 
	 * @return 	string 				The original (long) URL.
	 * 
	 * @throws 	Exception
	 */
	public function routeToOriginal(string $sequence, $hit = true)
	{
		// get the shorten URL record
		$record = $sequence ? $this->getItem(['sequence' => $sequence]) : null;

		if (!$record) {
			throw new Exception('The requested link could not be found.', 404);
		}

		if ($hit === true) {
			// increase visitor hits
			$record->visits += 1;

			// update record
			JFactory::getDbo()->updateObject('#__vikbooking_shortenurls', $record, 'id');
		}

		// return the original URL for the redirection
		return $record->redirect_uri;
	}

	/**
	 * Parses a shortened URL into a long URL.
	 * 
	 * @param 	string 	$url  The full shortened URL.
	 * 
	 * @return 	string        The original (long) URL.
	 * 
	 * @uses 	route()
	 */
	public function parseShortUrl(string $url)
	{
		// parse the shorten URL
		$shorten_uri = JUri::getInstance($url);

		// access the query string of the shorten URL
		$shorten_query = $shorten_uri->getQuery($to_array = true);

		// get the sequence code from the parsed URL
		$sequence = $shorten_query[$this->sequenceParamName] ?? '';

		return $this->routeToOriginal($sequence);
	}

	/**
	 * Parses a long URL into its shorten version, by generating and
	 * storing a sequence code that identifies the original URL.
	 * 
	 * @param 	string 	$url 	The long URL to shorten.
	 * 
	 * @return 	string 			The shortened URL.
	 */
	public function getShortUrl(string $url)
	{
		// check if the shorten URL record exists
		$record = $this->getItem([
			'redirect_uri' => $url,
		]);

		if ($record) {
			// build short URL from existing shorten record
			return $this->buildShortUrl($record);
		}

		// generate a unique secret sequence code identifier
		$sequence = $this->generateSequence();
		while ((bool) $this->getItem(['sequence' => $sequence])) {
			// sequence code string must be unique
			$sequence = $this->generateSequence();
		}

		// build shorten URL record
		$record = new stdClass;
		$record->sequence = $sequence;
		$record->redirect_uri = $url;
		$record->created_on = JFactory::getDate()->toSql();

		// store shorten URL record
		JFactory::getDbo()->insertObject('#__vikbooking_shortenurls', $record, 'id');

		if (!($record->id ?? null)) {
			// fallback on original URL
			return $url;
		}

		// build short URL from the newly created shorten record
		return $this->buildShortUrl($record);
	}

	/**
	 * Routes a shortened URL from a shorten URL record.
	 * 
	 * @param 	object 	$record  The shorten URL record.
	 * 
	 * @return 	string 			 The full routed URL.
	 */
	protected function buildShortUrl($record)
	{
		$dbo = JFactory::getDbo();

		// parse the original redirect URL
		$original_uri = JUri::getInstance($record->redirect_uri);

		// access the query string of the original URL
		$original_query = $original_uri->getQuery($to_array = true);

		// the views from which the booking language can be detected
		$booking_views = ['booking', 'precheckin'];

		if (in_array(($original_query['view'] ?? ''), $booking_views) && ($original_query['sid'] ?? '') && ($original_query['ts'] ?? '')) {
			if (!$this->booking) {
				// fetch the involved booking record
				$q = $dbo->getQuery(true)
					->select([
						$dbo->qn('id'),
						$dbo->qn('lang'),
					])
					->from($dbo->qn('#__vikbooking_orders'))
					->where($dbo->qn('ts') . ' = ' . $dbo->q($original_query['ts']))
					->andWhere([
						$dbo->qn('sid') . ' = ' . $dbo->q($original_query['sid']),
						$dbo->qn('idorderota') . ' = ' . $dbo->q($original_query['sid']),
					], 'OR');

				$dbo->setQuery($q, 0, 1);
				$booking = $dbo->loadAssoc();

				if ($booking) {
					$this->booking = $booking;
				}
			}
		}

		// the language for routing the URL
		$url_lang = null;
		if (!empty($this->booking['lang'])) {
			$url_lang = $this->booking['lang'];
		}

		// find the best matching menu item or post ID
		$bestitemid = VikBooking::findProperItemIdType(['tinyurl'], $url_lang);

		// build language suffix
		$lang_suffix = $bestitemid && $url_lang ? '&lang=' . $url_lang : '';

		// route final shorten URL
		$shorten_url = VikBooking::externalroute('index.php?option=com_vikbooking&view=tinyurl&' . $this->sequenceParamName . '=' . $record->sequence . $lang_suffix, false, ($bestitemid ?: null));

		if ($this->onlyRouted === true && strpos($shorten_url, 'view=tinyurl') !== false) {
			// fallback on original URL due to missing routing on menu-item/shortcode
			return $record->redirect_uri;
		}

		// return the routed "tinyurl" link
		return $shorten_url;
	}

	/**
	 * Item loading implementation.
	 *
	 * @param   mixed  $pk   An optional primary key value to load the row by,
	 *                       or an associative array of fields to match.
	 *
	 * @return  object|null  The record object on success, null otherwise.
	 */
	protected function getItem($pk)
	{
		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select('*')
			->from($dbo->qn('#__vikbooking_shortenurls'));

		if (is_array($pk)) {
			foreach ($pk as $column => $value) {
				if ($column === 'sequence') {
					// binary match (case-sensitive)
					$q->where('BINARY ' . $dbo->qn($column) . ' = ' . $dbo->q($value));
				} else {
					// regular match
					$q->where($dbo->qn($column) . ' = ' . $dbo->q($value));
				}
			}
		} else {
			$q->where($dbo->qn('id') . ' = ' . (int) $pk);
		}

		$dbo->setQuery($q, 0, 1);

		return $dbo->loadObject();
	}

	/**
	 * Generates a random sequence code string.
	 * 
	 * @param 	int 	$length  The length of the sequence code to generate.
	 * 
	 * @return 	string           The random sequence code string.
	 */
	protected function generateSequence(int $length = 12)
	{
		$dictionary = [
			range(0, 9),
			range('a', 'z'),
			range('A', 'Z'),
		];

		$sequence = '';

		for ($i = 0; $i < $length; $i++) {
			// randomize dictionary level (index)
			$level = rand(0, count($dictionary) - 1);

			// toss dictionary level char
			$token = rand(0, count($dictionary[$level]) - 1);

			// grab char token
			$sequence .= $dictionary[$level][$token];
		}

		return $sequence;
	}
}

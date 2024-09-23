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
 * Defines a data registry container for the admin widgets
 * being rendered in the multitask panel for encapsulation.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOMultitaskData extends JObject
{
	/**
	 * @var 	array 	multitask data options list.
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	protected $data_options = [];

	/**
	 * Gets the current name of the view/task.
	 * 
	 * @return 	string
	 */
	public function getPage()
	{
		return $this->get('page_name', null);
	}

	/**
	 * Sets the current name of the view/task.
	 * 
	 * @param 	string 	$page 	the name of the view/task.
	 * 
	 * @return 	self
	 */
	public function setPage($page = '')
	{
		$this->set('page_name', (string)$page);

		return $this;
	}

	/**
	 * Gets the current page URI.
	 * 
	 * @return 	string
	 */
	public function getURI()
	{
		return $this->get('page_uri', null);
	}

	/**
	 * Sets the current page URI.
	 * 
	 * @param 	string 	$uri 	the current page URI.
	 * 
	 * @return 	self
	 */
	public function setURI($uri = '')
	{
		$this->set('page_uri', (string)$uri);

		return $this;
	}

	/**
	 * Gets the current page query.
	 * 
	 * @return 	array
	 */
	public function getQuery()
	{
		return $this->get('page_query', []);
	}

	/**
	 * Sets the current page query.
	 * 
	 * @param 	array 	$query 	the current page query.
	 * 
	 * @return 	self
	 */
	public function setQuery($query = array())
	{
		$this->set('page_query', (array)$query);

		return $this;
	}

	/**
	 * Gets the current booking id.
	 * 
	 * @return 	int 	the booking id.
	 */
	public function getBookingId()
	{
		$bid = $this->get('booking_id', 0);

		if ($bid) {
			return $bid;
		}

		return $this->get('id_order', 0);
	}

	/**
	 * Sets the current booking id.
	 * 
	 * @param 	int 	$bid 	the reservation id.
	 * 
	 * @return 	self
	 */
	public function setBookingId($bid = 0)
	{
		$this->set('booking_id', (int)$bid);

		return $this;
	}

	/**
	 * Tells whether the admin widget is being rendered within a modal.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function isModalRendering()
	{
		return (bool)$this->get('_modalRendering', false);
	}

	/**
	 * If the admin widget is being rendered within a modal, some
	 * additional data for the JS events may be attached to an ID.
	 * Useful for the admin widget to clear intervals through JS.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function getModalJsIdentifier()
	{
		return $this->get('_modalJsId', '');
	}

	/**
	 * Registers custom multitask options for rendering an admin widget.
	 * Options are usually set when rendering a widget within a modal.
	 * 
	 * @param 	array 	$options 	associative list of options.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public function registerDataOptions(array $options = [])
	{
		$this->data_options = $options;

		return $this;
	}

	/**
	 * Returns the available custom options for rendering an admin widget.
	 * Useful to have a top-layer set of options that differs from regular data.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public function getDataOptions()
	{
		return $this->data_options;
	}
}

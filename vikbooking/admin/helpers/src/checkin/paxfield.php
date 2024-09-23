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
 * Defines a registry container for the pax field object to parse.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
class VBOCheckinPaxfield extends JObject
{
	/**
	 * Gets the current field number.
	 * 
	 * @return 	int 	the field number.
	 */
	public function getFieldNumber()
	{
		return $this->get('field_number', 1);
	}

	/**
	 * Sets the current field number.
	 * 
	 * @param 	int 	$num 	the field number starting from 1.
	 * 
	 * @return 	self
	 */
	public function setFieldNumber($num = 1)
	{
		$this->set('field_number', $num);

		return $this;
	}

	/**
	 * Gets the current type of guest.
	 * 
	 * @return 	string 	either "adult" or "child".
	 */
	public function getGuestType()
	{
		return $this->get('guest_type', 'adult');
	}

	/**
	 * Sets the current type of guest.
	 * 
	 * @param 	string 	$type 	either "adult" or "child".
	 * 
	 * @return 	self
	 */
	public function setGuestType($type = 'adult')
	{
		// either "adult" or "child", no surprises
		$type = $type == 'child' ? 'child' : 'adult';

		$this->set('guest_type', $type);

		return $this;
	}

	/**
	 * Gets the current guest number.
	 * 
	 * @return 	int 	the guest number.
	 */
	public function getGuestNumber()
	{
		return $this->get('guest_number', 1);
	}

	/**
	 * Sets the current guest number.
	 * 
	 * @param 	int 	$num 	the guest number starting from 1.
	 * 
	 * @return 	self
	 */
	public function setGuestNumber($num = 1)
	{
		$this->set('guest_number', $num);

		return $this;
	}

	/**
	 * Gets the current guest data.
	 * 
	 * @return 	mixed 	the guest data.
	 */
	public function getGuestData()
	{
		return $this->get('guest_data');
	}

	/**
	 * Sets the current guest data.
	 * 
	 * @param 	mixed 	$data 	the guest data.
	 * 
	 * @return 	self
	 */
	public function setGuestData($data = null)
	{
		$this->set('guest_data', $data);

		return $this;
	}

	/**
	 * Gets the current room index.
	 * 
	 * @return 	int 	the room index.
	 */
	public function getRoomIndex()
	{
		return $this->get('room_index', 0);
	}

	/**
	 * Sets the current room index.
	 * 
	 * @param 	int 	$num 	the room index starting from 0.
	 * 
	 * @return 	self
	 */
	public function setRoomIndex($num = 0)
	{
		$this->set('room_index', $num);

		return $this;
	}

	/**
	 * Gets the number of guests for the current room index.
	 * 
	 * @param 	int 	$adults 	the number of adults in the current room.
	 * @param 	int 	$children 	the number of children in the current room.
	 * 
	 * @return 	array 	the respective number of adults and children.
	 */
	public function getRoomGuests()
	{
		$adults = $this->get('room_guests_adults', 0);
		$children = $this->get('room_guests_children', 0);

		return [$adults, $children];
	}

	/**
	 * Sets the number of guests for the current room index.
	 * 
	 * @param 	int 	$adults 	the number of adults in the current room.
	 * @param 	int 	$children 	the number of children in the current room.
	 * 
	 * @return 	self
	 */
	public function setRoomGuests($adults = 0, $children = 0)
	{
		$this->set('room_guests_adults', (int)$adults);
		$this->set('room_guests_children', (int)$children);

		return $this;
	}

	/**
	 * Gets the total number of rooms.
	 * 
	 * @return 	int 	the total number of rooms.
	 */
	public function getTotalRooms()
	{
		return $this->get('total_rooms', 1);
	}

	/**
	 * Sets the total number of rooms.
	 * 
	 * @param 	int 	$num 	the total number of rooms.
	 * 
	 * @return 	self
	 */
	public function setTotalRooms($num = 1)
	{
		$this->set('total_rooms', $num);

		return $this;
	}

	/**
	 * Gets the key identifier of the current field.
	 * 
	 * @return 	string 	the key of the current field.
	 */
	public function getKey()
	{
		return $this->get('key');
	}

	/**
	 * Sets the key identifier of the current field.
	 * 
	 * @param 	string 	$key 	the key identifier.
	 * 
	 * @return 	self
	 */
	public function setKey($key)
	{
		$this->set('key', $key);

		return $this;
	}

	/**
	 * Gets the current field type attribute value.
	 * 
	 * @return 	string|array 	the type of field identifier.
	 */
	public function getType()
	{
		return $this->get('type', null);
	}

	/**
	 * Sets the current field type attribute.
	 * 
	 * @param 	string|array 	$type 	the type of field identifier.
	 * 
	 * @return 	self
	 */
	public function setType($type)
	{
		$this->set('type', $type);

		return $this;
	}
}

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
 * Defines an abstract adapter to extend the pax data collection.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
abstract class VBOCheckinAdapter implements VBOCheckinPaxfields
{
	/**
	 * The ID of the pax data collector class.
	 * 
	 * @var 	string
	 */
	protected $collector_id = '';

	/**
	 * Tells whether children should be registered.
	 * Children registration is disabled by default.
	 * 
	 * @param 	bool 	$precheckin 	true if requested for front-end pre check-in.
	 * 
	 * @return 	bool    true to also register the children.
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP) added $precheckin argument.
	 */
	public function registerChildren($precheckin = false)
	{
		// disabled by default, unless method gets overridden
		return false;
	}

	/**
	 * Returns the instance of the given pax field key.
	 * 
	 * @param 	string 	$key 		the field key identifier.
	 * 
	 * @return 	VBOCheckinPaxfield 	the requested pax field object.
	 */
	public function getField($key)
	{
		// get all the existing field attributes
		$attributes = $this->getAttributes();

		// create a new instance of the field registry object
		$pax_field = new VBOCheckinPaxfield();

		// inject key and type of field
		$field_type = (isset($attributes[$key]) ? $attributes[$key] : 'text');
		$pax_field->setKey($key);
		$pax_field->setType($field_type);

		// return the field registry object
		return $pax_field;
	}

	/**
	 * Attempts to find the first field attribute key from the given type.
	 * 
	 * @param 	string 	$type 	The type of pax field.
	 * 
	 * @return 	string 			Empty string or first field attribute key found for this type.
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public function getFieldTypeKey(string $type)
	{
		// get all the existing field attributes
		$attributes = $this->getAttributes();

		if (!in_array($type, $attributes)) {
			// unknown pax field type
			return '';
		}

		// search for the pax field key from the given type
		$key = array_search($type, $attributes);

		if ($key === false) {
			// pax field type not found
			return '';
		}

		return (string) $key;
	}

	/**
	 * Renders a specific pax field type.
	 * 
	 * @param 	VBOCheckinPaxfield 	$field 	the pax field object to render.
	 * 
	 * @return 	string  			the HTML string to display the field.
	 */
	public function render(VBOCheckinPaxfield $field)
	{
		// get the field implementor
		$implementor = $this->getFieldTypeImplementor($field);

		if ($implementor === null) {
			// could not access the implementor
			return '';
		}

		// let the handler render the field
		return $implementor->render();
	}

	/**
	 * Attempts to return an instance of the field-type implementor object being parsed.
	 * 
	 * @param 	VBOCheckinPaxfield 	$field 	the pax field object to render.
	 * 
	 * @return 	null|object 		the field implementor class of VBOCheckinPaxfieldType or null.
	 */
	public function getFieldTypeImplementor(VBOCheckinPaxfield $field)
	{
		// get the type of field to render
		$field_type = $field->getType();

		if ($field_type === null) {
			return null;
		}

		if (is_array($field_type)) {
			// convert field type to "select" string
			$field_type = 'select';
		}

		if (!is_string($field_type)) {
			// invalid field type
			return null;
		}

		// compose dinamically the implementor class name
		$field_class = $this->getFieldTypeClass($field_type);

		if (!$field_class || !class_exists($field_class)) {
			// no implementor handler found for this type of field
			return null;
		}

		// return the field handler by passing the pax field object and the data collector id
		return new $field_class($field, $this->collector_id);
	}

	/**
	 * Builds the list of back-end pax fields for the extended collection type.
	 * 
	 * @return 	array 	the list of pax fields to collect in the back-end.
	 */
	public function listFields()
	{
		return [$this->getLabels(), $this->getAttributes()];
	}

	/**
	 * Builds the list of front-end (pre-checkin) pax fields for the extended collection type.
	 * Check-in pax fields data collector implementations may override this method, if needed.
	 * 
	 * @param 	array 	$def_fields 	list of default pre-checkin field labels and attributes.
	 * 
	 * @return 	array 	the list of pax fields to collect in the front-end during pre-checkin.
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP)
	 */
	public function listPrecheckinFields(array $def_fields)
	{
		// return no labels, nor attributes by default
		return [
			[],
			[],
		];
	}

	/**
	 * Invokes a callback for the extended collection type after the pre-checkin
	 * information have been stored or updated to perform certain actions.
	 * 
	 * @param 	array 	$data 		the guest registration data stored.
	 * @param 	array 	$booking 	the booking record involved with the guests registration.
	 * @param 	array 	$customer 	optional customer record associated with the booking.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.17.5 (J) - 1.7.5 (WP)
	 */
	public function onPrecheckinDataStored(array $data, array $booking, array $customer)
	{
		// no actions to be performed by default
		return;
	}

	/**
	 * Performs a validation over the guest registration field types for a given reservation.
	 * Those responsible for storing the pre-check-in information should call this method first,
	 * which will invoke the validation method over every registration field type, and then the
	 * driver validation will be automatically called to verify the data submitted.
	 * 
	 * @param 	array 	$booking 		The booking record involved with the guests registration.
	 * @param 	array 	$booking_rooms 	The booking room records involved with the guests registration.
	 * @param 	array 	$data 			The guests registration data to validate.
	 * @param 	bool 	$precheckin 	True if validating pre-checkin fields.
	 * 
	 * @return 	void
	 * 
	 * @throws 	Exception
	 * 
	 * @uses 	validateRegistrationFields()
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public function validateRegistrationFieldTypes(array $booking, array $booking_rooms, array $data, bool $precheckin = true)
	{
		// first off, let the driver perform the validation over the field contents submitted to ensure mandatory values are set
		$this->validateRegistrationFields($booking, $booking_rooms, $data, $precheckin);

		// get the current driver's attributes
		$supported_attributes = $this->getAttributes();

		// iterate over all rooms booked to identify the custom registration field types
		foreach ($booking_rooms as $index => $booking_room) {
			// count expected room registration guests data
			$room_adults = $booking_room['adults'] ?? 1;
			$room_children = $booking_room['children'] ?? 0;
			$room_guests = $this->registerChildren($precheckin) ? ($room_adults + $room_children) : $room_adults;

			// scan room guests for the expected room guest registration data
			for ($g = 1; $g <= $room_guests; $g++) {
				if (!is_array(($data[$index][$g] ?? null))) {
					// no registration data available for this room and guest
					continue;
				}

				// iterate over the registration fields
				foreach ($supported_attributes as $field_type) {
					if (!is_string($field_type) || !$this->isCustomFieldType($field_type)) {
						// known field types will not be invoked for triggering a custom validation
						continue;
					}

					// find the first pax field key from the given type
					$pax_field_key = $this->getFieldTypeKey($field_type);

					if (!$pax_field_key) {
						// unknown pax field type
						continue;
					}

					// get an instance of the VBOCheckinPaxfield object
					$pax_field_obj = $this->getField($pax_field_key);

					// detect the current type of guest
					$guest_type = $g > $room_adults ? 'child' : 'adult';

					// set object data
					$pax_field_obj->setGuestType($guest_type)
						->setGuestNumber($g)
						->setGuestData($data[$index][$g])
						->setRoomIndex($index)
						->setBooking($booking)
						->setBookingRooms($booking_rooms)
						->setRoomGuests($room_adults, $room_children)
						->setTotalRooms(count($booking_rooms));

					// get the field implementor
					if ($implementor = $this->getFieldTypeImplementor($pax_field_obj)) {
						// invoke the registration data validation on the field implementor
						$implementor->validateGuestRegistrationData();
					}
				}
			}
		}

		// all good
		return;
	}

	/**
	 * Performs a validation over the guest registration fields data for a given reservation.
	 * Custom drivers can override this method to implement their own validation method.
	 * 
	 * @param 	array 	$booking 		The booking record involved with the guests registration.
	 * @param 	array 	$booking_rooms 	The booking room records involved with the guests registration.
	 * @param 	array 	$data 			The guests registration data to validate.
	 * @param 	bool 	$precheckin 	True if validating pre-checkin fields.
	 * 
	 * @return 	void
	 * 
	 * @throws 	Exception
	 * 
	 * @see 	validateRegistrationFieldTypes()
	 * 
	 * @since 	1.17.7 (J) - 1.7.7 (WP)
	 */
	public function validateRegistrationFields(array $booking, array $booking_rooms, array $data, bool $precheckin = true)
	{
		// no guest fields data validation performed by default
		return;
	}

	/**
	 * Tells whether the given field attribute typr correspond to a custom paxfield type.
	 * 
	 * @param 	string 	$type 	The field attribute type.
	 * 
	 * @return 	bool 				True if this is a custom field type.
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	protected function isCustomFieldType(string $type)
	{
		// get the current driver's attributes
		$supported_attributes = $this->getAttributes();

		if (!in_array($type, $supported_attributes)) {
			// unknown attribute type for this driver
			return false;
		}

		// declare the known field types that will not require a validation at field-object level
		$known_types = [
			'calendar',
			'country',
			'file',
			'number',
			'select',
			'text',
			'textarea',
		];

		return !in_array($type, $known_types);
	}

	/**
	 * Composes the field type class name given its type-string.
	 * 
	 * @param 	string 	$field_type 	the field type-string identifier.
	 * 
	 * @return 	bool|string 	the class name to use for the field, or false.
	 */
	protected function getFieldTypeClass($field_type)
	{
		if (!is_string($field_type) || empty($field_type)) {
			return false;
		}

		// base class name
		$base_paxf_class = 'VBOCheckinPaxfieldType';

		// compose field type class name
		$field_type = ucwords(str_replace(array('_', '-'), ' ', $field_type));
		$field_type = preg_replace("/[^a-zA-Z0-9]/", '', $field_type);

		return $base_paxf_class . $field_type;
	}

	/**
	 * Returns the name of the current pax data driver.
	 * 
	 * @return 	string 	the name of the driver.
	 */
	abstract public function getName();

	/**
	 * Builds the list pax fields labels.
	 * 
	 * @return 	array 	the list of pax fields labels.
	 */
	abstract public function getLabels();

	/**
	 * Builds the list pax fields attributes.
	 * 
	 * @return 	array 	the list of pax fields attributes.
	 */
	abstract public function getAttributes();
}

<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Room-Booking sub-unit record implementation.
 * 
 * @since   1.18.7 (J) - 1.8.7 (WP)
 */
final class VBOBookingSubunitRecord
{
    /**
     * @var  array
     */
    private array $data = [];

    /**
     * @var  ?int
     */
    private ?int $index = null;

    /**
     * @var  array
     */
    private array $initialData = [];

    /**
     * Binds the room booking record data properties and index.
     * 
     * @param   array   $data   The room booking record properties to bind.
     * @param   ?int    $index  Optional room booking index within the booking itself.
     */
    public function __construct(array $data, ?int $index = null)
    {
        // bind record data
        $this->bindData($data);

        // set room-booking index
        $this->index = $index;
    }

    /**
     * Returns the room booking record check-in timestamp.
     * 
     * @return  int
     */
    public function getCheckin()
    {
        return $this->data['checkin'];
    }

    /**
     * Returns the room booking record check-out timestamp.
     * 
     * @return  int
     */
    public function getCheckout()
    {
        return ($this->data['realback'] ?? 0) ?: $this->data['checkout'];
    }

    /**
     * Calculates the timestamp for the last night of stay.
     * 
     * @return  int
     */
    public function calculateLastNight()
    {
        return strtotime('23:59:59', strtotime('-1 day', $this->getCheckout()));
    }

    /**
     * Returns the room booking record last night of stay timestamp.
     * 
     * @return  int
     */
    public function getLastNight()
    {
        return ($this->data['_last_night_ts'] ?? 0) ?: $this->calculateLastNight();
    }

    /**
     * Returns the booking ID.
     * 
     * @return  int
     */
    public function getBookingID()
    {
        return $this->data['idorder'];
    }

    /**
     * Returns the room booking record ID.
     * 
     * @return  int
     */
    public function getRoomBookingID()
    {
        return $this->data['room_booking_id'];
    }

    /**
     * Returns the room booking unit index assigned, if any.
     * 
     * @return  ?int
     */
    public function getRoomUnitIndex()
    {
        return $this->data['roomindex'] ?? null;
    }

    /**
     * Sets the room booking unit index assigned, if any.
     * 
     * @param   ?int    $index          Room booking unit index assigned, or null.
     * @param   bool    $setInitial     Whether to store the initial data value.
     * 
     * @return  ?int                    The previous room booking index assigned.
     */
    public function setRoomUnitIndex(?int $index, bool $setInitial = true)
    {
        if ($index === 0) {
            // index 0 stands for no actual room unit index modification
            $index = $this->getRoomUnitIndex();
        }

        return $this->setDataValue('roomindex', $index, $setInitial);
    }

    /**
     * Returns the room booking initial unit index assigned, if any.
     * 
     * @return  ?int
     */
    public function getInitialRoomUnitIndex()
    {
        return $this->getDataValue('roomindex', null, $initial = true);
    }

    /**
     * Tells if the room booking record is being relocated.
     * 
     * @return  bool
     */
    public function isRelocating()
    {
        return (bool) ($this->data['relocate'] ?? null);
    }

    /**
     * Tells if the room booking record is a booking closure.
     * 
     * @return  bool
     */
    public function isClosure()
    {
        return !empty($this->data['closure']);
    }

    /**
     * Gets the value for a given data key.
     * 
     * @param   string  $key        The data key to get.
     * @param   mixed   $default    The default value to return.
     * @param   bool    $initial    Whether to fetch the initial data value.
     */
    public function getDataValue(string $key, $default = null, bool $initial = false)
    {
        if ($initial) {
            // attempt to return the initial data value
            return $this->initialData[$key] ?? $default;
        }

        // attempt to return the current data value
        return $this->data[$key] ?? $default;
    }

    /**
     * Sets a given value for a data key.
     * 
     * @param   string  $key         The data key to update.
     * @param   mixed   $value       The data value to set.
     * @param   bool    $setInitial  Whether to store the initial data value.
     * 
     * @return  mixed                The previous data value.
     */
    public function setDataValue(string $key, $value, bool $setInitial = true)
    {
        // access the current data value
        $currentValue = $this->data[$key] ?? null;

        // set the new data value
        $this->data[$key] = $value;

        if ($setInitial && !isset($this->initialData[$key])) {
            // store the initial value
            $this->initialData[$key] = $currentValue;
        }

        return $currentValue;
    }

    /**
     * Resets a given data property key to its initial value.
     * 
     * @param   string  $key    The data property ket to reset.
     * @param   mixed   $value  Optional initial value to set.
     * 
     * @return  void
     */
    public function resetInitialValue(string $key, $value = null)
    {
        if ($value === null) {
            // attempt to fetch the initial value
            $value = $this->initialData[$key] ?? null;
        }

        $this->data[$key] = $value;
    }

    /**
     * Resets all initial values that were previously updated.
     * 
     * @return  void
     */
    public function resetInitialValues()
    {
        foreach ($this->initialData as $key => $value) {
            // reset the previously updated key
            $this->resetInitialValue($key, $value);
        }

        // reset the initial data elements
        $this->initialData = [];
    }

    /**
     * Binds and normalizes the room booking record data properties.
     * 
     * @param   array   $data   The room booking record properties to bind.
     * 
     * @return  void
     * 
     * @throws  InvalidArgumentException
     */
    public function bindData(array $data)
    {
        // check data integrity
        $this->checkIntegrity($data);

        // assign data properties
        $this->data = $data;

        // calculate and set the last night of stay timestamp
        $this->setDataValue('_last_night_ts', $this->calculateLastNight(), $setInitial = false);
    }

    /**
     * Returns the current record data properties.
     * 
     * @return  array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns the room booking index within the booking itself.
     * 
     * @return  ?int
     */
    public function getRecordIndex()
    {
        return $this->index;
    }

    /**
     * Ensures the given room booking record data properties are valid.
     * 
     * @param   array   $data   The room booking record properties to check.
     * 
     * @return  void
     * 
     * @throws  InvalidArgumentException
     */
    private function checkIntegrity(array $data)
    {
        // determine the data validity
        $valid = true;

        if (empty($data['checkin'])) {
            $valid = false;
        }

        if (empty($data['checkout']) && empty($data['realback'])) {
            $valid = false;
        }

        if (empty($data['idorder']) && empty($data['room_booking_id'])) {
            $valid = false;
        }

        if (!$valid) {
            throw new InvalidArgumentException('Invalid room booking record data properties.', 400);
        }
    }
}

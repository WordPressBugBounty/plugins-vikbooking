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
 * Booking relocator implementation.
 * 
 * @since   1.18.7 (J) - 1.8.7 (WP)
 */
final class VBOBookingRelocator
{
    /**
     * @var  array
     */
    private array $options = [];

    /**
     * @var  ?VBOBookingRegistry
     */
    private ?VBOBookingRegistry $registry = null;

    /**
     * @var  int
     */
    private int $daysLimBound = 7;

    /**
     * Proxy to construct the object.
     * 
     * @param   array   $options    Associative list of booking information to bind.
     * 
     * @return  static
     */
    public static function getInstance(array $options)
    {
        return new static($options);
    }

    /**
     * Class constructor.
     * 
     * @param   array   $options    Associative list of booking information to bind.
     * 
     * @throws  Exception
     */
    public function __construct(array $options)
    {
        if (empty($options['id'])) {
            throw new InvalidArgumentException('Missing booking ID.', 400);
        }

        // construct full booking registry
        $this->registry = VBOBookingRegistry::getInstance(['id' => (int) $options['id']]);

        // bind internal options
        $this->options = $options;
    }

    /**
     * Calculates the necessary moves to assign a sub-unit to the current room
     * reservation by taking into account all bookings around the involved dates.
     * 
     * @return  VBOBookingSubunitMoveset   Room booking records moveset object.
     * 
     * @throws  Exception
     */
    public function findRelocation()
    {
        // identify the room ID to relocate
        $relocateRoomId = $this->options['id_room'] ?? 0;

        if (!$relocateRoomId) {
            foreach ($this->registry->getRooms() as $bookedRoom) {
                // assume we are relocating the first room booked
                $relocateRoomId = (int) $bookedRoom['idroom'];
                break;
            }
        }

        if (!$relocateRoomId || !in_array($relocateRoomId, $this->registry->getBookedListingIds())) {
            // unknown room to relocate
            throw new Exception('Unknown room ID to relocate.', 404);
        }

        // let the registry load the details of the room to relocate
        $this->registry->getRoomDetails($relocateRoomId);

        // identify an eligible room booking record to relocate
        $relocateRoomRecord = [];
        $relocateRoomIndex = 0;
        foreach ($this->registry->getRooms() as $bookedIndex => $bookedRoom) {
            if ($bookedRoom['idroom'] == $relocateRoomId && empty($bookedRoom['roomindex'])) {
                // room booking record with empty room index identified
                $relocateRoomRecord = $bookedRoom;
                $relocateRoomIndex = $bookedIndex;
                if (($this->options['booking_room_index'] ?? -1) == $bookedIndex) {
                    // exact booking room index match found
                    break;
                }
            }
        }

        if (!$relocateRoomRecord) {
            // no room records to relocate
            throw new Exception('No room records to relocate.', 404);
        }

        // make sure to set the current room index within the booking registry
        $this->registry->setCurrentRoomIndex($relocateRoomIndex);

        // build the room sub-unit matrix
        $subunitMatrix = $this->buildSubunitMatrix($relocateRoomRecord);

        // attempt allocate the current record by returning the first fitting moveset
        return $subunitMatrix->relocateRoomRecords();
    }

    /**
     * Builds and returns the room booking records matrix.
     * 
     * @param   ?array  $relocateRoomRecord     Optional room booking record to relocate.
     * 
     * @return  VBOBookingSubunitMatrix
     * 
     * @throws  Exception
     */
    private function buildSubunitMatrix(?array $relocateRoomRecord = null)
    {
        $dbo = JFactory::getDbo();

        // access the stay timestamps for the room record to relocate
        $relocateTimestamps = $this->registry->getStayTimestamps($roomLevel = true);

        // access the room ID to relocate
        $relocateRoomId = $this->registry->getCurrentRoomID();

        // build stay dates iterable period
        $stayPeriod = $this->registry->buildStayPeriodInterval('P1D', $relocateTimestamps[0], $relocateTimestamps[1]);

        // build date bounds according to settings or default values
        $backTimestamp = strtotime('00:00:00', strtotime(sprintf('-%d days', (int) ($this->options['days_bound'] ?? $this->daysLimBound)), $relocateTimestamps[0]));
        $forthTimestamp = strtotime('23:59:59', strtotime(sprintf('+%d days', (int) ($this->options['days_bound'] ?? $this->daysLimBound)), $relocateTimestamps[1]));

        // booking IDs occupied records
        $bookingBusyRecords = [];

        // query the database to gather the occupied records
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select($dbo->qn('b') . '.*')
                ->select($dbo->qn('ob.idorder'))
                ->from($dbo->qn('#__vikbooking_busy', 'b'))
                ->leftJoin($dbo->qn('#__vikbooking_ordersbusy', 'ob') . ' ON ' . $dbo->qn('b.id') . ' = ' . $dbo->qn('ob.idbusy'))
                ->where($dbo->qn('b.idroom') . ' = ' . $relocateRoomId)
                ->where($dbo->qn('b.checkin') . ' <= ' . $forthTimestamp)
                ->where($dbo->qn('b.realback') . ' >= ' . $backTimestamp)
                ->order($dbo->qn('b.id') . ' ASC')
        );

        // scan all occupied records
        foreach ($dbo->loadAssocList() as $busyRecord) {
            // start container, if needed
            $bookingBusyRecords[$busyRecord['idorder']] = $bookingBusyRecords[$busyRecord['idorder']] ?? [];
            // push booking occupied record
            $bookingBusyRecords[$busyRecord['idorder']][] = $busyRecord;
        }

        if (!$bookingBusyRecords) {
            // no records occupied, hence nothing to relocate
            throw new Exception('No occupied records, nothing to relocate.', 406);
        }

        // query the database to obtain the involved booking room records
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select([
                    $dbo->qn('or.id'),
                    $dbo->qn('or.idorder'),
                    $dbo->qn('or.roomindex'),
                    $dbo->qn('b.closure'),
                ])
                ->from($dbo->qn('#__vikbooking_ordersrooms', 'or'))
                ->leftJoin($dbo->qn('#__vikbooking_orders', 'b') . ' ON ' . $dbo->qn('or.idorder') . ' = ' . $dbo->qn('b.id'))
                ->where($dbo->qn('or.idroom') . ' = ' . $relocateRoomId)
                ->where($dbo->qn('or.idorder') . ' IN (' . implode(', ', array_map('intval', array_keys($bookingBusyRecords))) . ')')
                ->order($dbo->qn('or.idorder') . ' ASC')
                ->order($dbo->qn('or.id') . ' ASC')
        );

        $bookingRoomRecords = $dbo->loadAssocList();
        $lastBid = null;
        $indexes = [];
        foreach ($bookingRoomRecords as $bookingRoomRecord) {
            if (!($bookingBusyRecords[$bookingRoomRecord['idorder']] ?? null)) {
                // we must be dealing with a ghost record
                continue;
            }

            // guess booking busy index
            $bookingBusyIndex = $lastBid == $bookingRoomRecord['idorder'] && isset($indexes[$lastBid]) ? $indexes[$lastBid] : 0;

            // update room index, closure and room-booking record ID on proper booking busy record
            $updateBookingIndex = isset($bookingBusyRecords[$bookingRoomRecord['idorder']][$bookingBusyIndex]) ? $bookingBusyIndex : 0;
            $bookingBusyRecords[$bookingRoomRecord['idorder']][$updateBookingIndex] = array_merge(
                $bookingBusyRecords[$bookingRoomRecord['idorder']][$updateBookingIndex],
                [
                    'room_booking_id' => $bookingRoomRecord['id'],
                    'roomindex' => $bookingRoomRecord['roomindex'],
                    'closure' => $bookingRoomRecord['closure'],
                ]
            );

            // update values
            $lastBid = $bookingRoomRecord['idorder'];
            $indexes[$lastBid] = $bookingBusyIndex + 1;
        }

        // ensure the room booking record to relocate is found
        $matchFound = false;
        foreach ($bookingBusyRecords as $bid => $busyRecords) {
            foreach ($busyRecords as $bidIndex => $busyRecord) {
                if (!empty($busyRecord['roomindex'])) {
                    // already allocated
                    continue;
                }

                if ($relocateRoomRecord && ($busyRecord['room_booking_id'] ?? 0) == $relocateRoomRecord['id']) {
                    // match found from the exact given record to relocate
                    $matchFound = true;
                } elseif (!$relocateRoomRecord) {
                    // first eligible record found
                    $matchFound = true;
                }

                if ($matchFound === true) {
                    // set flag to identify the room booking record to relocate
                    $bookingBusyRecords[$bid][$bidIndex]['relocate'] = true;

                    // abort
                    break 2;
                }
            }
        }

        if (!$matchFound) {
            throw new Exception('No matching record to relocate against the occupied records.', 404);
        }

        // build a map of stay dates and related units occupied
        $daysUnitsMap = [];
        foreach ($stayPeriod as $stayNight) {
            // count units booked and involved room booking IDs for the current stay day
            $dayUnitsBooked = 0;
            $dayRoomBookingIds = [];
            $dayStayTs = $stayNight->format('U');
            foreach ($bookingBusyRecords as $busyRecords) {
                foreach ($busyRecords as $busyRecord) {
                    if ($busyRecord['checkin'] <= $dayStayTs && $busyRecord['realback'] >= $dayStayTs) {
                        // update values
                        $dayUnitsBooked++;
                        $dayRoomBookingIds[] = $busyRecord['room_booking_id'];
                    }
                }
            }

            // make sure the stay night is not overbooked
            if ($dayUnitsBooked > ($this->registry->getRoomDetails($relocateRoomId)['units'] ?? 0)) {
                // abort
                throw new Exception(sprintf('Cannot relocate room record on %s because room is overbooked.', $stayNight->format('Y-m-d')), 403);
            }

            // set values
            $daysUnitsMap[$stayNight->format('Y-m-d')] = [
                'units_booked' => $dayUnitsBooked,
                'room_booking_ids' => $dayRoomBookingIds,
            ];
        }

        // get an instance of a room-booking sub-unit matrix
        $matrix = new VBOBookingSubunitMatrix($this->registry, $daysUnitsMap);

        // inject relocation options
        $matrix->setOptions($this->options);

        // iterate all room booking records
        foreach ($bookingBusyRecords as $bid => $busyRecords) {
            foreach ($busyRecords as $bidIndex => $busyRecord) {
                // push room-booking sub-unit record to matrix
                $matrix->pushRecord(
                    new VBOBookingSubunitRecord($busyRecord, $bidIndex)
                );
            }
        }

        return $matrix;
    }
}

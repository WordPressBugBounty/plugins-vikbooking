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
 * Room-Booking sub-unit matrix implementation.
 * 
 * @since   1.18.7 (J) - 1.8.7 (WP)
 */
final class VBOBookingSubunitMatrix
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
     * @var  array
     */
    private array $daysUnitsMap = [];

    /**
     * @var  array
     */
    private array $records = [];

    /**
     * @var  ?VBOBookingSubunitRecord
     */
    private ?VBOBookingSubunitRecord $relocateRecord = null;

    /**
     * @var  ?DatePeriod
     */
    private ?DatePeriod $recordsDatePeriod = null;

    /**
     * @var  int
     */
    private int $fittingSolutionsCount = 0;

    /**
     * Generates all possible combinations to be assigned to a list of items, from the minimum index to the
     * maximum index. Calculated values for each matrix container are applied over the provided items list.
     * Returns an iterable (Generator) object that will provide a container with the items assigned values.
     * For every item in the list, the Base-N is calculated to count the possible combinations.
     * Base-N = maxIndex - minIndex + 1. Possible combinations = Base-N^itemsCount.
     * 
     * @param   array       $items          List of item objects/arrays for which combinations will be calculated.
     * @param   int         $minIndex       The minimum combination value to assign to each item.
     * @param   int         $maxIndex       The maximum combination value to assign to each item.
     * @param   ?callable   $valueCallback  Optional callback for setting the matrix iteration value on each item.
     * 
     * @return  Generator
     * 
     * @throws  InvalidArgumentException
     */
    public static function testYieldingMatrix(array $items, int $minIndex, int $maxIndex, ?callable $valueCallback = null)
    {
        if ($minIndex > $maxIndex) {
            throw new InvalidArgumentException('Minimum index must be lower than maximum.', 400);
        }

        // count total items
        $itemsCount = count($items);

        // calculate the Base-N value
        $baseN = $maxIndex - $minIndex + 1;

        // count total combinations
        $combinationsCount = pow($baseN, $itemsCount);

        // loop over the total combinations count
        for ($index = 0; $index < $combinationsCount; $index++) {
            // start current matrix container
            $container = [];

            // get initial value
            $value = $index;

            // shift combination values for all items
            for ($j = 0; $j < $itemsCount; $j++) {
                // get digit
                $digit = $value % $baseN;

                // get value
                $value = intdiv($value, $baseN);

                // calculate matrix container item value
                $itemValue = $digit + $minIndex;

                // obtain matrix container element
                if ($valueCallback) {
                    // call provided function
                    $element = call_user_func_array($valueCallback, [$items[$j], $itemValue]) ?: $items[$j];
                } else {
                    // set item value
                    $element = $itemValue;
                }

                // push container item
                $container[] = $element;
            }

            // yield current container with the current index as key
            yield $index => $container;
        }
    }

    /**
     * Class constructor will bind the registry and days-units map.
     * 
     * @param   VBOBookingRegistry  $registry      The involved booking registry.
     * @param   array               $daysUnitsMap  Associative list of days and related data.
     * 
     * @throws  InvalidArgumentException
     */
    public function __construct(VBOBookingRegistry $registry, array $daysUnitsMap)
    {
        if (!$daysUnitsMap) {
            // missing days-units mapping
            throw new InvalidArgumentException('Missing days-units mapping', 400);
        }

        // bind booking registry
        $this->registry = $registry;

        // bind days-units map
        $this->daysUnitsMap = $daysUnitsMap;
    }

    /**
     * Generates a matrix with all possible moves to apply to the room booking records.
     * For each moveset within the matrix, checks if room records can be re-assigned to
     * fit the minimum check-in and maximum check-out of the involved reservations.
     * 
     * @return  VBOBookingSubunitMoveset    First fitting moveset of room booking records.
     * 
     * @throws  OverflowException|Exception
     */
    public function relocateRoomRecords()
    {
        if (!$this->relocateRecord) {
            throw new Exception('Missing room record to relocate.', 500);
        }

        // access default script execution time
        $defaultMaxExecTime = @ini_get('max_execution_time');
        $defaultMaxExecTime = is_numeric($defaultMaxExecTime) ? (int) $defaultMaxExecTime : 0;

        // determine the script max execution time and cycle lifetime
        $maxExecTime   = intval(($this->options['max_exec_time'] ?? 0) ?: 180);
        $maxExecTime   = $maxExecTime < 10 ? 10 : $maxExecTime;
        $maxExecTime   = $defaultMaxExecTime > $maxExecTime ? $defaultMaxExecTime : $maxExecTime;
        $cycleLifetime = $maxExecTime - 20;
        $cycleLifetime = $cycleLifetime > 0 ? $cycleLifetime : ($maxExecTime - 1);

        // try to give the script a higher execution time
        @set_time_limit($maxExecTime);
        @ini_set('max_execution_time', $maxExecTime);

        // start timer
        $timerStart = time();

        // always reset fitting solutions counter
        $this->fittingSolutionsCount = 0;

        // count listing total inventory units
        $totalUnits = $this->registry->getRoomDetails($this->registry->getCurrentRoomID())['units'] ?? 0;

        if ($totalUnits < 2) {
            throw new Exception('Listing total inventory units is less than 2.', 500);
        }

        // sort records by dates closer to relocation target
        $this->sortRecords();

        // build room record objects list
        $objectsList = $this->getRecords();

        // prepend room record to relocate to the list
        array_unshift($objectsList, $this->getRelocateRecord());

        // count total number of possible moves (iterations = Base-N^totalBookings)
        $totalMoves = pow(($totalUnits + 1), count($objectsList));

        // generate all possible room booking record moves matrix
        $possbileMovesGenerator = $this->generateMovesetMatrix($objectsList, $totalUnits);

        // the Generator object is iterable, but only once
        foreach ($possbileMovesGenerator as $comboCount => $moveset) {
            // parse the combination moveset

            // check first if the cycle lifetime is over to prevent an un-handled script termination
            // perform the check every 100k iterations, which should take approximately 2 seconds
            if (($comboCount % 100000) === 0 && (time() - $timerStart) >= $cycleLifetime) {
                // terminate the iterations to prevent the server from collapsing
                throw new OverflowException(
                    'The operation was taking too long to complete. Matrix size is too large for the maximum script execution time (' . $maxExecTime . 's).',
                    508
                );
            }

            // check if the calculated moveset fits
            if ($this->relocationFits($moveset, $totalUnits) === true) {
                // increase fitting solutions counter
                $this->fittingSolutionsCount++;

                // wrap the fitting moveset into a registry
                $movesetRegistry = (new VBOBookingSubunitMoveset($moveset, $this->registry))
                    ->setIterationNumber($comboCount + 1)
                    ->setTotalMoves($totalMoves)
                    ->setSolutionsCount($this->fittingSolutionsCount)
                    ->setVerboseRelocation($this->relocationFits($moveset, $totalUnits, $verbose = true));

                if ($this->options['count_all'] ?? null) {
                    // all eligible movesets should be identified and counted
                    continue;
                }

                if ($this->options['skip_moveset_signatures'] ?? null) {
                    // some moveset should be skipped
                    if (in_array($movesetRegistry->getSignature(), (array) $this->options['skip_moveset_signatures'])) {
                        // we don't want this moveset
                        continue;
                    }
                }

                if ($this->options['skip_booking_ids'] ?? null) {
                    // some bookings should not be moved
                    if (array_intersect($movesetRegistry->getBookingIDs(), (array) $this->options['skip_booking_ids'])) {
                        // the moveset includes some bookings that should not be moved
                        continue;
                    }
                }

                // abort and return the fitting relocation moveset registry
                return $movesetRegistry;
            }
        }

        if (($this->options['count_all'] ?? null) && $this->countFittingSolutions() && isset($movesetRegistry)) {
            // return the last fitting relocation moveset registry found
            return $movesetRegistry;
        }

        // no valid combinations found after exhausting the whole matrix
        throw new Exception(
            sprintf(
                'Could not relocate room reservation after going through all possible moves (%d). Total fitting solutions: %d.',
                ($comboCount + 1),
                $this->countFittingSolutions()
            ),
            404
        );
    }

    /**
     * Returns the current room booking records.
     * 
     * @return  array
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * Returns the current room booking record to relocate.
     * 
     * @return  ?VBOBookingSubunitRecord
     */
    public function getRelocateRecord()
    {
        return $this->relocateRecord;
    }

    /**
     * Sets the room booking record to relocate.
     * 
     * @param   ?VBOBookingSubunitRecord     $record     The record to set.
     * 
     * @return  static
     */
    public function setRelocateRecord(?VBOBookingSubunitRecord $record)
    {
        $this->relocateRecord = $record;

        return $this;
    }

    /**
     * Resets the room booking records to relocate.
     * 
     * @param   bool    $main   True to also reset the main record to relocate.
     * 
     * @return  void
     */
    public function resetRecords(bool $main = false)
    {
        // empty sub-unit records
        $this->records = [];

        if ($main) {
            // reset main record to relocate
            $this->relocateRecord = null;
        }
    }

    /**
     * Injects the matrix options.
     * 
     * @param   array   $options    Options to bind.
     * 
     * @return  static
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Returns the number of fitting solutions found so far.
     * 
     * @return  int
     */
    public function countFittingSolutions()
    {
        return $this->fittingSolutionsCount;
    }

    /**
     * Adds a room-booking record wrapper to the pool, or sets it as main record.
     * 
     * @param   VBOBookingSubunitRecord     $record     The record to push.
     * 
     * @return  void
     * 
     * @throws  Exception
     */
    public function pushRecord(VBOBookingSubunitRecord $record)
    {
        if ($record->isRelocating()) {
            // ensure the record to relocate is not a closure
            if ($record->isClosure()) {
                throw new Exception('Booking closures do not support sub-unit relocation.', 500);
            }

            // ensure we only get one record to relocate
            if ($this->relocateRecord) {
                throw new Exception('Matrix can only relocate one room booking record per time.', 500);
            }

            // set record as main record, without pushing it to the queue
            $this->relocateRecord = $record;
        } else {
            // push room-booking record wrapper
            $this->records[] = $record;
        }
    }

    /**
     * Builds and returns the iterable date period for all
     * room booking records (min check-in to max check-out).
     * 
     * @return  DatePriod
     * 
     * @throws  Exception
     */
    public function getRecordsDatePeriod()
    {
        if ($this->recordsDatePeriod) {
            // date period already available
            return $this->recordsDatePeriod;
        }

        if (!$this->records && !$this->relocateRecord) {
            throw new Exception('No room booking records for calculating the iterable date period.', 500);
        }

        // gather all check-in and check-out timestamps
        $checkins = [];
        $checkouts = [];

        if ($this->relocateRecord) {
            // push relocate record details
            $checkins[] = $this->relocateRecord->getCheckin();
            $checkouts[] = $this->relocateRecord->getCheckout();
        }

        foreach ($this->records as $roomRecord) {
            // push room booking record details
            $checkins[] = $roomRecord->getCheckin();
            $checkouts[] = $roomRecord->getCheckout();
        }

        // local timezone
        $tz = new DateTimezone(date_default_timezone_get());

        // get date bounds
        $from_bound = new DateTime(date('Y-m-d H:i:s', min($checkins)), $tz);
        $to_bound = new DateTime(date('Y-m-d H:i:s', max($checkouts)), $tz);

        // set iterable dates interval (period)
        $this->recordsDatePeriod = new DatePeriod(
            // start date included by default in the result set
            $from_bound,
            // interval between recurrences within the period
            new DateInterval('P1D'),
            // end date (check-out) excluded by default from the result set
            $to_bound
        );

        // return the iterable date period
        return $this->recordsDatePeriod;
    }

    /**
     * Generates all possible moves for the room booking records. Returns an iterator, more precisely
     * a Generator object containing a list of room booking record moveset for every combination.
     * For every room booking record the Base-N is calculated to count the possible combinations.
     * Base-N = maxIndex - minIndex + 1. Possible combinations = Base-N^countRoomBookingRecords.
     * The minimum index is 0, meaning no moves, while the maximum index is the room inventory count.
     * If we had 9 room booking records plus one room booking record to relocate, and if the room
     * had 5 units in total, then the total count of possible moves would be: Base-6^10 = 60.466.176.
     * 
     * @param   array   $records    List of room booking record objects, inclusive of the one to relocate (0th).
     * @param   int     $maxIndex   Max combination value (maximum room index = total inventory count).
     * 
     * @return  Generator           For every iteration, list of room booking record moveset.
     */
    private function generateMovesetMatrix(array $records, int $maxIndex)
    {
        // count total room booking records (inclusive of the one to relocate)
        $objectsCount = count($records);

        // default minimum index (0 = no moves)
        $minIndex = 0;

        // calculate the Base-N value
        $baseN = $maxIndex - $minIndex + 1;

        // count total combinations
        $combinationsCount = pow($baseN, $objectsCount);

        // loop over the total combinations count
        for ($index = 0; $index < $combinationsCount; $index++) {
            // start current moveset list
            $moveset = [];

            // get initial value
            $value = $index;

            // shift combination values for all elements
            for ($j = 0; $j < $objectsCount; $j++) {
                // get digit
                $digit = $value % $baseN;

                // get value
                $value = intdiv($value, $baseN);

                // calculate room index for this combination
                $comboRoomIndex = $digit + $minIndex;

                // clone current room record object
                $roomObject = clone $records[$j];

                // apply room index combination
                $roomObject->setRoomUnitIndex($comboRoomIndex);

                // set moveset object
                $moveset[$j] = $roomObject;
            }

            // yield current moveset with the current index as key
            yield $index => $moveset;
        }
    }

    /**
     * Tells if the given moveset fits without any overlapping room booking.
     * 
     * @param   VBOBookingSubunitRecord[]   $moveset   List of room booking record (cloned) objects.
     * @param   ?int                        $maxIndex  Maximum room index (total inventory count).
     * @param   bool                        $verbose   Whether to describe the relocation plan.
     * 
     * @return  bool|string                            String if successful and verbose, boolean otherwise.
     * 
     * @throws  InvalidArgumentException
     */
    private function relocationFits(array $moveset, ?int $maxIndex = null, bool $verbose = false)
    {
        if (!$moveset) {
            throw new InvalidArgumentException('No room booking records in the moveset.', 500);
        }

        if (!$maxIndex) {
            // count listing total inventory units
            $maxIndex = $this->registry->getRoomDetails($this->registry->getCurrentRoomID())['units'] ?? 0;
        }

        // start verbose description
        $verboseTexts = [];

        // scan the date period for all room records to ensure we've got no duplicate indexes
        foreach ($this->getRecordsDatePeriod() as $date) {
            if ($verbose) {
                $verboseTexts[] = 'Analysing date-time ' . $date->format('Y-m-d H:i:s') . "\n";
            }

            // access current calendar date timestamp
            $currentTs = $date->format('U');

            // build the list of occupied room indexes
            $occupiedIndexes = [];

            // iterate all room record objects in the moveset
            foreach ($moveset as $roomRecord) {
                // get record room index occupied
                $occupiedIndex = $roomRecord->getRoomUnitIndex();

                if (!$occupiedIndex) {
                    // this record is not impacting the moveset
                    if ($roomRecord->isRelocating()) {
                        // room record to relocate always requires a move
                        return false;
                    }

                    // process the next moveset
                    continue;
                }

                // check if the current room record intersects the current calendar date
                if ($roomRecord->getCheckin() <= $currentTs && $roomRecord->getLastNight() >= $currentTs) {
                    // push occupied index
                    $occupiedIndexes[] = $occupiedIndex;

                    if ($verbose) {
                        $verboseTexts[] = 'Booking ID ' . $roomRecord->getBookingID() . ' is occupying the unit ' . (int) $occupiedIndex;
                    }
                }
            }

            if ($verbose) {
                $verboseTexts[] = 'Occupied indexes: ' . implode(', ', $occupiedIndexes) . "\n\n";
            }

            // count number of occupied indexes
            $totOccupiedSlots = count($occupiedIndexes);

            if (!$totOccupiedSlots) {
                // no bookings on this day
                continue;
            }

            if ($totOccupiedSlots > $maxIndex) {
                // the relocation does not fit as this day is overbooked
                return false;
            }

            if (count(array_unique($occupiedIndexes)) != $totOccupiedSlots) {
                // multiple room bookings were occupying the same index
                return false;
            }
        }

        // the relocation moveset does fit!
        return $verbose ? implode("\n", $verboseTexts) : true;
    }

    /**
     * Sorts the room booking records by dates closer to target.
     * 
     * @return  void
     */
    private function sortRecords()
    {
        // access the target checkin and checkout date timestamps
        $targetCheckinTs  = $this->relocateRecord->getCheckin();
        $targetCheckoutTs = $this->relocateRecord->getCheckout();

        // check if some bookings should be skipped
        $lowPriorityBids = (array) ($this->options['skip_booking_ids'] ?? null);

        // sort records
        usort($this->records, function($a, $b) use ($targetCheckinTs, $targetCheckoutTs, $lowPriorityBids) {
            // calculate timestamp distance for both comparison elements
            $aDistance = abs($targetCheckinTs - $a->getCheckin()) + abs($targetCheckoutTs - $a->getCheckout());
            $bDistance = abs($targetCheckinTs - $b->getCheckin()) + abs($targetCheckoutTs - $b->getCheckout());

            if ($lowPriorityBids) {
                if (in_array($a->getBookingID(), $lowPriorityBids)) {
                    $aDistance = PHP_INT_MAX;
                }
                if (in_array($b->getBookingID(), $lowPriorityBids)) {
                    $bDistance = PHP_INT_MAX;
                }
            }

            return $aDistance <=> $bDistance;
        });
    }
}

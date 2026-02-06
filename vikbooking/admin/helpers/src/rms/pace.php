<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * RMS Pace implementation
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPace
{
    /**
     * Proxy to construct the object.
     * 
     * @return  VBORmsPace
     */
    public static function getInstance()
    {
        return new static;
    }

    /**
     * Class constructor.
     */
    public function __construct()
    {}

    /**
     * Gets occupancy pace data for the RMS according to the options provided.
     * 
     * @param   ?array  $options    List of calculation options.
     * 
     * @return  array
     * 
     * @throws  Exception
     */
    public function getOccupancyData(?array $options = null)
    {
        // define default option values
        $pickupDate     = $options['pickup']['date'] ?? date('Y-m-d');
        $targetDateFrom = $options['target']['from'] ?? date('Y-m-01');
        $targetDateTo   = $options['target']['to'] ?? date('Y-m-t');
        $listingIds     = (array) ($options['listings'] ?? []);
        $periodInterval = in_array(strtoupper($options['interval'] ?? ''), ['DAY', 'MONTH']) ? $options['interval'] : 'DAY';

        // obtain target timestamps for validation
        $targetTsFrom = strtotime($targetDateFrom);
        $targetTsTo   = strtotime($targetDateTo);
        if (!$targetTsFrom || !$targetTsTo || $targetTsTo < $targetTsFrom) {
            throw new InvalidArgumentException('Invalid target (stay) dates.', 400);
        }

        // normalize period interval into a duration value
        $intervalDuration = !strcasecmp($periodInterval, 'MONTH') ? 'P1M' : 'P1D';

        // access the availability helper
        $avHelper = VikBooking::getAvailabilityInstance(true);

        // load the involved listings data (by also supporting category IDs)
        $listingsData = $avHelper->loadRooms($listingIds);

        // filter out the unpublished listings
        $listingsData = array_filter($listingsData, function($listing) {
            return !empty($listing['avail']);
        });

        if (!$listingsData) {
            throw new Exception('No listings to analyse.', 400);
        }

        if ($options['sort_rooms'] ?? null) {
            // custom rooms sorting other than default by "name"
            if (is_callable($options['sort_rooms'])) {
                // custom sorting function
                uasort($listingsData, $options['sort_rooms']);
            } else {
                // determine sorting type
                $listingsSortType = $options['sort_rooms'];
                uasort($listingsData, function($a, $b) use ($listingsSortType) {
                    if ($listingsSortType === 'occupancy') {
                        // sort by occupancy ascending
                        return ($a['totpeople'] ?? 0) <=> ($b['totpeople'] ?? 0);
                    } elseif ($listingsSortType === 'units') {
                        // sort by units ascending
                        return ($a['units'] ?? 0) <=> ($b['units'] ?? 0);
                    }

                    // apply no sorting
                    return 0;
                });
            }
        }

        // update valid listing IDs
        $listingIds = array_map('intval', array_column($listingsData, 'id'));

        // count total rooms inventory
        $totalInventoryCount = array_sum(array_column($listingsData, 'units'));

        // shorten the listings data into an associative list
        $listingsData = array_combine($listingIds, array_values(array_map(function($listing) {
            return [
                'name'  => $listing['name'],
                'units' => $listing['units'],
            ];
        }, $listingsData)));

        // build target dates pool
        $targetsPool = [
            [
                $targetTsFrom,
                $targetTsTo,
            ],
        ];

        // check for comparison instructions
        foreach (($options['compare'] ?? []) as $compareData) {
            if (!is_array($compareData) || empty($compareData['to'])) {
                // unexpected comparison instruction
                continue;
            }

            // check if week-days should match across the comparison dates
            $alignWdays = (bool) ($compareData['align_wdays'] ?? 1);

            // calculate target dates for comparison
            $compareTsFrom = $this->getComparisonTimestamp($targetTsFrom, $compareData['to'], $alignWdays);
            $compareTsTo   = $this->getComparisonTimestamp($targetTsTo, $compareData['to'], $alignWdays);
            if (is_null($compareTsFrom)) {
                // unacceptable datetime comparison value
                continue;
            }

            // push target dates to the pool for comparison
            $targetsPool[] = [
                $compareTsFrom,
                $compareTsTo,
            ];
        }

        // build the list of occupancy pace data metric objects for extracting data
        $paceDataMetrics = $this->loadOccupancyPaceDataMetrics($options);

        // build pace dataset
        $dataset = [
            'pace'            => [],
            'listings'        => $listingsData,
            'inventory_count' => $totalInventoryCount,
        ];

        // scan all target dates
        foreach ($targetsPool as $index => $targetData) {
            if (!isset($dataset['pace'][$index])) {
                // start pace index container
                $dataset['pace'][$index] = [];
            }

            // obtain the target details
            list($tsFrom, $tsTo) = $targetData;

            // ensure the end timestamp is full
            $tsTo = strtotime('23:59:59', $tsTo);

            // fetch all confirmed and cancelled bookings from pickup date, for targeted stay dates
            $bookings = $this->getIntersectingBookings([
                'pickup' => [
                    'date' => (!$index ? $pickupDate : null),
                ],
                'target' => [
                    'from_ts' => $tsFrom,
                    'to_ts'   => $tsTo,
                ],
                'listings' => $listingIds,
                'cancellation_dt' => 1,
                'tariff_taxes' => 1,
            ]);

            // preload rate flow records and events only for the first set of target dates
            $ratesRegistry = null;
            $periodEvents  = [];

            if (!$index) {
                // construct the RMS rates registry object from pickup date, for targeted stay dates
                $ratesRegistry = (new VBORmsRatesRegistry([
                    'pickup' => [
                        'date' => $pickupDate,
                    ],
                    'target' => [
                        'from_ts' => $tsFrom,
                        'to_ts'   => $tsTo,
                    ],
                    'listings' => $listingIds,
                ]))->preloadFlowRecords();

                // preload period events
                $periodEvents = VBODateHotevents::loadPeriod($tsFrom, $tsTo, $listingIds);
            }

            // obtain the iterable date period
            $datePeriod = $this->getDatePeriodInterval($tsFrom, $tsTo, $intervalDuration);

            // iterate all target date intervals
            foreach ($datePeriod as $period) {
                // build period initial pace metrics
                $periodPaceMetrics = [
                    // inject the period date object that we are parsing
                    'date' => $period,
                ];

                // construct the pace occupancy data-period registry
                $paceDataPeriod = (new VBORmsPaceOccupancyDataperiod(
                    // get the confirmed bookings for the current period
                    $this->filterPeriodBookings($period, $bookings, $datePeriod->getDateInterval(), ['status' => 'confirmed']),
                    // the datetime period to evaluate
                    $period,
                    // the date evaluation interval
                    $datePeriod->getDateInterval()
                ))
                ->setListings($listingsData)
                ->setCancellations($this->filterPeriodBookings($period, $bookings, $datePeriod->getDateInterval(), ['status' => 'cancelled']))
                ->setRatesRegistry($ratesRegistry)
                ->setHotEvents($periodEvents);

                // iterate all pace data metric objects
                foreach ($paceDataMetrics as $paceDataMetric) {
                    try {
                        // let the data metric object extract its own metrics
                        $periodPaceMetrics[$paceDataMetric->getID()] = $paceDataMetric->extract($paceDataPeriod, $periodPaceMetrics);
                    } catch (Exception $e) {
                        // catch and push the error
                        $periodPaceMetrics['_errors'] = $periodPaceMetrics['_errors'] ?? [];
                        $periodPaceMetrics['_errors'][$paceDataMetric->getID()] = $e;
                    }
                }

                // push period pace data to current index
                $dataset['pace'][$index][] = $periodPaceMetrics;
            }
        }

        return $dataset;
    }

    /**
     * Gets booking pace data for the RMS according to the options provided.
     * 
     * @param   ?array  $options    List of calculation options.
     * 
     * @return  array
     * 
     * @throws  Exception
     */
    public function getBookingData(?array $options = null)
    {
        // define default option values
        $pickupDateFrom = $options['pickup']['from'] ?? date('Y-m-01', strtotime('-1 month'));
        $pickupDateTo   = $options['pickup']['to'] ?? date('Y-m-d');
        $targetDateFrom = $options['target']['from'] ?? date('Y-m-01', strtotime('+3 months'));
        $targetDateTo   = $options['target']['to'] ?? date('Y-m-t', strtotime('+3 months'));
        $listingIds     = (array) ($options['listings'] ?? []);
        $periodInterval = in_array(strtoupper($options['interval'] ?? ''), ['DAY', 'MONTH']) ? $options['interval'] : 'DAY';

        // obtain pickup timestamps for validation
        $pickupTsFrom = strtotime($pickupDateFrom);
        $pickupTsTo   = strtotime($pickupDateTo);
        if (!$pickupTsFrom || !$pickupTsTo || $pickupTsTo < $pickupTsFrom || $pickupTsTo > time()) {
            throw new InvalidArgumentException('Invalid pickup dates.', 400);
        }

        // obtain target timestamps for validation
        $targetTsFrom = strtotime($targetDateFrom);
        $targetTsTo   = strtotime($targetDateTo);
        if (!$targetTsFrom || !$targetTsTo || $targetTsTo < $targetTsFrom) {
            throw new InvalidArgumentException('Invalid target (stay) dates.', 400);
        }

        // normalize period interval into a duration value
        $intervalDuration = !strcasecmp($periodInterval, 'MONTH') ? 'P1M' : 'P1D';

        // access the availability helper
        $avHelper = VikBooking::getAvailabilityInstance(true);

        // load the involved listings data (by also supporting category IDs)
        $listingsData = $avHelper->loadRooms($listingIds);

        // filter out the unpublished listings
        $listingsData = array_filter($listingsData, function($listing) {
            return !empty($listing['avail']);
        });

        if (!$listingsData) {
            throw new Exception('No listings to analyse.', 400);
        }

        if ($options['sort_rooms'] ?? null) {
            // custom rooms sorting other than default by "name"
            if (is_callable($options['sort_rooms'])) {
                // custom sorting function
                uasort($listingsData, $options['sort_rooms']);
            } else {
                // determine sorting type
                $listingsSortType = $options['sort_rooms'];
                uasort($listingsData, function($a, $b) use ($listingsSortType) {
                    if ($listingsSortType === 'occupancy') {
                        // sort by occupancy ascending
                        return ($a['totpeople'] ?? 0) <=> ($b['totpeople'] ?? 0);
                    } elseif ($listingsSortType === 'units') {
                        // sort by units ascending
                        return ($a['units'] ?? 0) <=> ($b['units'] ?? 0);
                    }

                    // apply no sorting
                    return 0;
                });
            }
        }

        // update valid listing IDs
        $listingIds = array_map('intval', array_column($listingsData, 'id'));

        // shorten the listings data into an associative list
        $listingsData = array_combine($listingIds, array_values(array_map(function($listing) {
            return [
                'name'  => $listing['name'],
                'units' => $listing['units'],
            ];
        }, $listingsData)));

        // build pickup and target data lists
        $pickupData = [
            $pickupTsFrom,
            $pickupTsTo,
        ];
        $targetData = [
            $targetTsFrom,
            $targetTsTo,
        ];

        // build pace dataset
        $dataset = [
            'pace'     => [],
            'listings' => $listingsData,
        ];

        // obtain the target details
        list($tsFrom, $tsTo) = $targetData;

        // ensure the end timestamp is full
        $tsTo = strtotime('23:59:59', $tsTo);

        // fetch all confirmed and cancelled bookings intersecting the targeted stay dates
        $bookings = $this->getIntersectingBookings([
            'target' => [
                'from_ts' => $tsFrom,
                'to_ts'   => $tsTo,
            ],
            'listings' => $listingIds,
            'cancellation_dt' => 1,
            'tariff_taxes' => 1,
        ]);

        // count the number of "on the books" bookings before pickup
        $otbPickupCount = $this->calculatePickupStartingBookings($pickupData, $bookings);

        // obtain the iterable date period
        $datePeriod = $this->getDatePeriodInterval($pickupTsFrom, $pickupTsTo, $intervalDuration);

        // build the list of booking pace data metric objects for extracting data
        $paceDataMetrics = $this->loadBookingPaceDataMetrics($bookings, $options);

        // iterate all target date intervals
        foreach ($datePeriod as $period) {
            // build period initial pace metrics
            $periodPaceMetrics = [
                // inject the period date object that we are parsing
                'date' => $period,
            ];

            // construct the pace data-period registry
            $paceDataPeriod = (new VBORmsPaceBookingDataperiod(
                // pass the number of "on the books" bookings before pickup
                $otbPickupCount,
                // get the confirmed and cancelled bookings for the current period
                $this->filterPeriodBookings($period, $bookings, $datePeriod->getDateInterval(), ['intersect' => 'creation']),
                // the datetime period to evaluate
                $period,
                // the date evaluation interval
                $datePeriod->getDateInterval()
            ))
            ->setListings($listingsData);

            // iterate all pace data metric objects
            foreach ($paceDataMetrics as $paceDataMetric) {
                try {
                    // let the data metric object extract its own metrics
                    $periodPaceMetrics[$paceDataMetric->getID()] = $paceDataMetric->extract($paceDataPeriod, $periodPaceMetrics);
                } catch (Exception $e) {
                    // catch and push the error
                    $periodPaceMetrics['_errors'] = $periodPaceMetrics['_errors'] ?? [];
                    $periodPaceMetrics['_errors'][$paceDataMetric->getID()] = $e;
                }
            }

            // metrics must have set the number of "on the books" reservation at the current pickup period
            // update value for the next period iteration to count new bookings and cancellations
            $otbPickupCount = $paceDataPeriod->getPickupStartingBookings();

            // push period booking pace data
            $dataset['pace'][] = $periodPaceMetrics;
        }

        return $dataset;
    }

    /**
     * Calculates the timestamp of the date to be compared against the initial date.
     * 
     * @param   int     $ts             Initial date timestamp.
     * @param   string  $compare        Initial date modifier for comparison (i.e. "-1 year").
     * @param   bool    $align_wdays    Whether to align the week-day of the comparison date.
     * 
     * @return  ?int
     */
    public function getComparisonTimestamp(int $ts, string $compare, bool $align_wdays = true)
    {
        // initialize source and target date objects with local timezone
        $source = JFactory::getDate(date('Y-m-d H:i:s', $ts));
        $target = clone $source;

        try {
            // modify target date according to compare modifier
            $target->modify($compare);
        } catch (Exception $error) {
            // unacceptable datetime comparison string
            return null;
        }

        // check if comparison requires additional operations
        if (!$align_wdays || $source->format('Ym') == $target->format('Ym')) {
            // return the target date in case no week-day alignment is needed
            // or if source and target dates share the same month and year
            return $target->getTimestamp();
        }

        // align target date to the same week day as source date, and return the timestamp for comparison
        return VBODateComparator::alignWeekDay($source, (int) $target->format('Y'))->getTimestamp();
    }

    /**
     * Builds and returns the iterable date period interval for the given dates interval.
     * 
     * @param   int     $from_ts    From date period timestamp.
     * @param   int     $to_ts      To date period timestamp.
     * @param   string  $duration   The interval specification used for DateInterval::__construct().
     * 
     * @return  DatePeriod
     */
    public function getDatePeriodInterval(int $from_ts, int $to_ts, string $duration = 'P1D')
    {
        // local timezone
        $tz = new DateTimezone(date_default_timezone_get());

        // get date bounds
        $from_bound = new DateTime(date('Y-m-d 00:00:00', $from_ts), $tz);
        $to_bound = new DateTime(date('Y-m-d 00:00:00', strtotime('+1 day', $to_ts)), $tz);

        // return iterable dates interval (period)
        return new DatePeriod(
            // start date included by default in the result set
            $from_bound,
            // interval between recurrences within the period
            new DateInterval($duration),
            // end date excluded by default from the result set
            $to_bound
        );
    }

    /**
     * Given two date timestamps, counts the nights in between.
     * 
     * @param   int     $fromTs     Start date timestamp.
     * @param   int     $toTs       End date timestamp.
     * @param   bool    $inclusive  True to increase the difference.
     * 
     * @return  int                 Difference in days.
     */
    public function countNightsDifferenceTs(int $fromTs, int $toTs, bool $inclusive = false)
    {
        // ensure the time is the same for both timestamps for an accurate difference
        $fromTs = strtotime('10:00:00', $fromTs);
        $toTs   = strtotime('10:00:00', $toTs);

        // constuct date objects from timestamps
        $tz       = new DateTimeZone(date_default_timezone_get());
        $fromDate = new DateTime("@$fromTs", $tz);
        $toDate   = new DateTime("@$toTs", $tz);

        return ((int) $fromDate->diff($toDate)->days) + (int) $inclusive;
    }

    /**
     * Calculates the starting bookings counter before pickup start.
     * 
     * @param   array  $pickupData  List of pickup range timestamps (from and to).
     * @param   array  $bookings    List of exact bookings to parse.
     * 
     * @return  int
     */
    public function calculatePickupStartingBookings(array $pickupData, array $bookings)
    {
        // extract pickup stand and end timestamps
        list($pickupFromTs, $pickupToTs) = $pickupData;

        // start counter
        $startingBookings = 0;

        foreach ($bookings as $booking) {
            if ($booking['status'] == 'confirmed' && $booking['ts'] <= $pickupFromTs) {
                // increase starting reservations count for this booking created before pickup start
                $startingBookings++;
            } elseif ($booking['status'] == 'cancelled' && $booking['ts'] <= $pickupFromTs) {
                // check the history cancellation date, and if cancelled after pickup
                if (($booking['cancellation_ts'] ?? 0) > $pickupFromTs) {
                    // this booking was confirmed before pickup start, and so we should increase the counter
                    $startingBookings++;
                }
            }
        }

        return $startingBookings;
    }

    /**
     * Returns a list of bookings intersecting the given bounds.
     * 
     * @param   array   $bounds     The bounds to fetch the bookings.
     * 
     * @return  array               List of bookings involved.
     */
    public function getIntersectingBookings(array $bounds)
    {
        $dbo = JFactory::getDbo();

        $bookings = [];
        $bookingTariffs = [];

        $q = $dbo->getQuery(true)
            ->select([
                $dbo->qn('b.id'),
                $dbo->qn('b.ts'),
                $dbo->qn('b.status'),
                $dbo->qn('b.days'),
                $dbo->qn('b.checkin'),
                $dbo->qn('b.checkout'),
                $dbo->qn('b.roomsnum'),
                $dbo->qn('b.total'),
                $dbo->qn('b.idorderota'),
                $dbo->qn('b.channel'),
                $dbo->qn('b.tot_taxes'),
                $dbo->qn('b.tot_city_taxes'),
                $dbo->qn('b.tot_fees'),
                $dbo->qn('b.tot_damage_dep'),
                $dbo->qn('b.cmms'),
                $dbo->qn('b.closure'),
                $dbo->qn('br.idroom', 'br_idroom'),
                $dbo->qn('br.adults', 'br_adults'),
                $dbo->qn('br.children', 'br_children'),
                $dbo->qn('br.idtar', 'br_idtar'),
                $dbo->qn('br.cust_cost', 'br_cust_cost'),
                $dbo->qn('br.cust_idiva', 'br_cust_idiva'),
                $dbo->qn('br.room_cost', 'br_room_cost'),
                $dbo->qn('br.otarplan', 'br_otarplan'),
            ])
            ->from($dbo->qn('#__vikbooking_orders', 'b'))
            ->leftJoin($dbo->qn('#__vikbooking_ordersrooms', 'br') . ' ON ' . $dbo->qn('b.id') . ' = ' . $dbo->qn('br.idorder'))
            ->where($dbo->qn('b.status') . ' IN (' . implode(', ', array_map([$dbo, 'q'], ['confirmed', 'cancelled'])) . ')')
            ->where($dbo->qn('b.checkin') . ' <= ' . $bounds['target']['to_ts'])
            ->where($dbo->qn('b.checkout') . ' >= ' . $bounds['target']['from_ts'])
            ->order($dbo->qn('b.id') . ' ASC')
            ->order($dbo->qn('br.id') . ' ASC');

        if (!($bounds['closures'] ?? 0)) {
            // filter bookings by excluding closures
            $q->where($dbo->qn('b.closure') . ' = 0');
        } elseif ($bounds['only_closures'] ?? 0) {
            // filter bookings by including only closures
            $q->where($dbo->qn('b.closure') . ' = 1');
        }

        if ($bounds['listings'] ?? []) {
            // filter bookings by listing IDs
            $q->where($dbo->qn('br.idroom') . ' IN (' . implode(', ', $bounds['listings']) . ')');
        }

        if (($bounds['pickup']['date'] ?? '') && $bounds['pickup']['date'] != date('Y-m-d')) {
            // filter bookings by creation timestamp
            $q->where($dbo->qn('b.ts') . ' <= ' . strtotime('23:59:59', strtotime($bounds['pickup']['date'])));
        }

        $dbo->setQuery($q);
        foreach ($dbo->loadAssocList() as $booking) {
            if (!empty($booking['idtar'])) {
                // handle booking tariff relation
                $bookingTariffs[] = [
                    'id'    => $booking['id'],
                    'idtar' => $booking['idtar'],
                ];
            }

            // build booking record and booking room data levels
            $bookingRecordData = [];
            $bookingRoomData = [];
            foreach ($booking as $prop => $val) {
                if (substr($prop, 0, 3) === 'br_') {
                    // booking-room level data
                    $realProp = substr($prop, 3);
                    $bookingRoomData[$realProp] = $val;
                } else {
                    // booking-record level data
                    $bookingRecordData[$prop] = $val;
                }
            }

            if (!isset($bookings[$booking['id']])) {
                // allocate first booking-room record
                $bookings[$booking['id']] = $bookingRecordData;
                $bookings[$booking['id']]['_rooms'] = [$bookingRoomData];
            } else {
                // push additional booking-room record
                $bookings[$booking['id']]['_rooms'][] = $bookingRoomData;
            }
        }

        if ($bounds['listings'] ?? []) {
            // normalize multi-room booking properties in case of unwanted listings filtered
            foreach ($bookings as $bid => $booking) {
                // count booked rooms and eligible listings
                $bookedRooms = $booking['roomsnum'];
                $totListings = count($booking['_rooms']);
                if ($bookedRooms > $totListings) {
                    // normalize properties
                    $bookings[$bid]['_roomsnum'] = $bookedRooms;
                    $bookings[$bid]['roomsnum']  = $totListings;
                }
            }
        }

        if (($bounds['tariff_taxes'] ?? 0) && $bookingTariffs) {
            // we need to fetch the rate plan ID from the tariff ID of the rooms booked
            // this is needed for calculation purposes of room rates before/after tax
            $uniqueTariffIds = array_values(array_filter(array_unique(array_column($bookingTariffs, 'idtar'))));

            if ($uniqueTariffIds) {
                // list of tariff-booking processed
                $tariffBidsProcessed = [];

                // query the database to obtain the rate plan ID from the list of tariff IDs
                $dbo->setQuery(
                    $dbo->getQuery(true)
                        ->select([
                            $dbo->qn('id'),
                            $dbo->qn('idprice'),
                        ])
                        ->from($dbo->qn('#__vikbooking_dispcost'))
                        ->where($dbo->qn('id') . ' IN (' . implode(', ', array_map('intval', $uniqueTariffIds)) . ')')
                );

                // scan all tariff records
                foreach ($dbo->loadAssocList() as $tariffRecord) {
                    // scan all booking tariffs
                    foreach ($bookingTariffs as $bookingTariff) {
                        if ($bookingTariff['idtar'] == $tariffRecord['id']) {
                            // set and determine current booking room index
                            $tariffBidsProcessed[$bookingTariff['id']] = ($tariffBidsProcessed[$bookingTariff['id']] ?? -1) + 1;
                            $currentBookingRoomIndex = $tariffBidsProcessed[$bookingTariff['id']];

                            if (isset($bookings[$bookingTariff['id']]['_rooms'][$currentBookingRoomIndex])) {
                                // set booking room rate plan ID
                                $bookings[$bookingTariff['id']]['_rooms'][$currentBookingRoomIndex]['idprice'] = (int) $tariffRecord['idprice'];
                            }
                        }
                    }
                }
            }
        }

        if ($bounds['cancellation_dt'] ?? 0) {
            // we need to fetch the exact cancellation date from history records
            $cancBids = array_column(array_filter($bookings, function($booking) {
                return $booking['status'] === 'cancelled';
            }), 'id');

            // build the list of history events related to booking cancellations
            if ($cancBids && $cancHistoryEvents = VikBooking::getBookingHistoryInstance(0)->getBookingEventsType('cancelled')) {
                // list of booking IDs with cancellation events processed
                $cancBidsProcessed = [];

                // query the database to fetch the needed history records
                $dbo->setQuery(
                    $dbo->getQuery(true)
                        ->select([
                            $dbo->qn('idorder'),
                            $dbo->qn('dt'),
                        ])
                        ->from($dbo->qn('#__vikbooking_orderhistory'))
                        ->where($dbo->qn('idorder') . ' IN (' . implode(', ', array_map('intval', $cancBids)) . ')')
                        ->where($dbo->qn('type') . ' IN (' . implode(', ', array_map([$dbo, 'q'], $cancHistoryEvents)) . ')')
                        ->order($dbo->qn('idorder') . ' ASC')
                        ->order($dbo->qn('dt') . ' ASC')
                );

                // scan all booking cancellation records
                foreach ($dbo->loadAssocList() as $cancRecord) {
                    if (!($cancBidsProcessed[$cancRecord['idorder']] ?? 0)) {
                        // turn flag on to process this booking only once and get the earliest (first) cancellation
                        $cancBidsProcessed[$cancRecord['idorder']] = 1;

                        // convert the cancellation date from UTC to local timezone and set booking cancellation timestamp
                        $bookings[$cancRecord['idorder']]['cancellation_ts'] = JHtml::fetch('date', $cancRecord['dt'], 'U');
                    }
                }
            }
        }

        // return a numeric list of booking records
        return array_values($bookings);
    }

    /**
     * Filters the booking records eligible for the given period and interval ("On The Books" reservations).
     * 
     * @param   DateTimeInterface   $period     The period to evaluate, either a single day or a full month.
     * @param   array               $bookings   List of bookings involved for filtering.
     * @param   ?DateInterval       $interval   Optional period interval for evaluation (day or month).
     * @param   ?array              $options    Optional list of filtering options.
     * 
     * @return  array                           List of bookings eligible with the given period, if any.
     */
    public function filterPeriodBookings(DateTimeInterface $period, array $bookings, ?DateInterval $interval = null, ?array $options = null)
    {
        $periodBookings  = [];

        // determine the range of timestamps for matching a booking, according to interval
        $intervalType = ($interval->m ?? 0) ? 'MONTH' : 'DAY';
        if (in_array(($options['intersect'] ?? null), ['creation', 'cancellation'])) {
            // booking creation or cancellation timestamp
            $matchTsFrom = strtotime('00:00:00', $period->format('U'));
            $matchTsTo = strtotime('23:59:59', $period->format('U'));
        } else {
            // booking stay dates
            $matchTsFrom = strtotime('23:59:59', $period->format('U'));
            $matchTsTo = $matchTsFrom;
        }
        if ($intervalType === 'MONTH') {
            $matchTsTo = strtotime('23:59:59', strtotime($period->format('Y-m-t')));
        }

        if (!empty($options['status'])) {
            // filter bookings by status enums
            $options['status'] = (array) $options['status'];
        }

        foreach ($bookings as $booking) {
            if (!empty($booking['closure'])) {
                // exclude closure reservations from revenue
                continue;
            }

            if (!empty($options['status']) && !in_array($booking['status'], $options['status'])) {
                // filter unwanted reservation status
                continue;
            }

            // check if the booking fits the requested date interval
            if (($options['intersect'] ?? null) === 'creation') {
                // booking creation timestamp
                if ($matchTsFrom <= ($booking['ts'] ?? 0) && ($booking['ts'] ?? 0) <= $matchTsTo) {
                    // match found, push booking record
                    $periodBookings[] = $booking;
                }
            } elseif (($options['intersect'] ?? null) === 'cancellation') {
                // booking cancellation timestamp
                if ($matchTsFrom <= ($booking['cancellation_ts'] ?? 0) && ($booking['cancellation_ts'] ?? 0) <= $matchTsTo) {
                    // match found, push booking record
                    $periodBookings[] = $booking;
                }
            } else {
                // booking stay dates
                if (($booking['checkin'] ?? 0) <= $matchTsTo && ($booking['checkout'] ?? 0) >= $matchTsFrom) {
                    // match found, push booking record
                    $periodBookings[] = $booking;
                }
            }
        }

        return $periodBookings;
    }

    /**
     * Loads the various occupancy data metric objects to run.
     * 
     * @param   ?array  $options    Optional metric settings.
     * 
     * @return  array               List of VBORmsPaceDataMetric objects.
     */
    private function loadOccupancyPaceDataMetrics(?array $options = null)
    {
        $defaultMetrics = [
            // data metric for booking IDs
            new VBORmsPaceDataMetricBookingids($options),
            // data metric "ABRN"
            new VBORmsPaceDataMetricAbrn($options),
            // data metric for sellable units
            new VBORmsPaceDataMetricSellableunits($options),
            // data metric for occupancy percent
            new VBORmsPaceDataMetricOccupancypcent($options),
            // data metric for booked rooms
            new VBORmsPaceDataMetricBookedrooms($options),
            // data metric for multi-room bookings count
            new VBORmsPaceDataMetricMultiroombookingscount($options),
            // data metric "ADR"
            new VBORmsPaceDataMetricAdr($options),
            // data metric "Room Revenue"
            new VBORmsPaceDataMetricRoomrevenue($options),
            // data metric "RevPAR"
            new VBORmsPaceDataMetricRevpar($options),
            // data metric "Gross Revenue"
            new VBORmsPaceDataMetricGrossrevenue($options),
            // data metric "Rate Variation Date"
            new VBORmsPaceDataMetricRatevariationdate($options),
            // data metric "Rate Variation Plus"
            new VBORmsPaceDataMetricRatevariationplus($options),
            // data metric "Rate Variation Minus"
            new VBORmsPaceDataMetricRatevariationminus($options),
            // data metric "Room Rate Variation"
            new VBORmsPaceDataMetricRoomratevariation($options),
            // data metric "Nightly Rates"
            new VBORmsPaceDataMetricNightlyrates($options),
            // data metric "Hot Events"
            new VBORmsPaceDataMetricHotevents($options),
        ];

        return array_merge($defaultMetrics, (array) ($options['metrics'] ?? []));
    }

    /**
     * Loads the various booking data metric objects to run.
     * 
     * @param   array   $bookings   Raw list of bookings intersecting the target dates.
     * @param   ?array  $options    Optional metric settings.
     * 
     * @return  array               List of VBORmsPaceDataMetric objects.
     */
    private function loadBookingPaceDataMetrics(array $bookings, ?array $options = null)
    {
        $defaultMetrics = [
            // data metric for new bookings
            new VBORmsPaceDataMetricNewbookings($options),
            // data metric for cancelled bookings (overwrite constructor signature)
            new VBORmsPaceDataMetricCancbookings($bookings, $options),
            // data metric for "on the books"
            new VBORmsPaceDataMetricOnthebooks($options),
            // data metric "ADR"
            new VBORmsPaceDataMetricAdr($options),
            // data metric "Room Revenue"
            new VBORmsPaceDataMetricRoomrevenue($options),
        ];

        return array_merge($defaultMetrics, (array) ($options['metrics'] ?? []));
    }
}

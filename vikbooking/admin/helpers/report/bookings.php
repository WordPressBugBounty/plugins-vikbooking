<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2026 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Bookings report.
 * 
 * @since   1.18.13 (J) - 1.8.13 (WP)
 */
class VikBookingReportBookings extends VikBookingReport
{
    /**
     * Property 'defaultKeySort' is used by the View that renders the report.
     */
    public $defaultKeySort = 'day';

    /**
     * Property 'defaultKeyOrder' is used by the View that renders the report.
     */
    public $defaultKeyOrder = 'ASC';

    /**
     * Property 'exportAllowed' is used by the View to display the export button.
     */
    public $exportAllowed = 1;

    /**
     * Debug mode is activated by passing the value 'e4j_debug' > 0
     */
    private $debug;

    /**
     * Class constructor should define the name of the report and
     * other vars. Call the parent constructor to define the DB object.
     */
    public function __construct()
    {
        $this->reportFile = basename(__FILE__, '.php');
        $this->reportName = JText::translate('VBMENUTHREE');
        $this->reportFilters = [];

        $this->cols = [];
        $this->rows = [];
        $this->footerRow = [];

        $this->debug = JFactory::getApplication()->input->getBool('e4j_debug', false);

        $this->registerExportCSVFileName();

        parent::__construct();
    }

    /**
     * Returns the name of this report.
     *
     * @return  string
     */
    public function getName()
    {
        return $this->reportName;
    }

    /**
     * Returns the name of this file without .php.
     *
     * @return  string
     */
    public function getFileName()
    {
        return $this->reportFile;
    }

    /**
     * Returns the filters of this report.
     *
     * @return  array
     */
    public function getFilters()
    {
        if ($this->reportFilters) {
            // do not run this method twice, as it could load JS and CSS files.
            return $this->reportFilters;
        }

        $app = JFactory::getApplication();

        // get VBO Application Object
        $vbo_app = VikBooking::getVboApplication();

        // load the jQuery UI Datepicker
        $this->loadDatePicker();

        // From Date Filter
        $filter_opt = array(
            'label' => '<label for="fromdate">'.JText::translate('VBOREPORTSDATEFROM').'</label>',
            'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />',
            'type' => 'calendar',
            'name' => 'fromdate'
        );
        array_push($this->reportFilters, $filter_opt);

        // To Date Filter
        $filter_opt = array(
            'label' => '<label for="todate">'.JText::translate('VBOREPORTSDATETO').'</label>',
            'html' => '<input type="text" id="todate" name="todate" value="" class="vbo-report-datepicker vbo-report-datepicker-to" />',
            'type' => 'calendar',
            'name' => 'todate'
        );
        array_push($this->reportFilters, $filter_opt);

        // Dates Type filter
        $pdatetype = $app->input->getString('dates_type', 'checkin');
        $filter_opt = array(
            'label' => '<label for="dates_type">'.JText::translate('VBPSHOWSEASONSTHREE').'</label>',
            'html' => '<select id="dates_type" name="dates_type">' .
                      '<option value="checkin"' . ($pdatetype === 'checkin' ? ' selected="selected"' : '') . '>' . JText::translate('VBPICKUPAT') . '</option>' .
                      '<option value="booking"' . ($pdatetype === 'booking' ? ' selected="selected"' : '') . '>' . JText::translate('VBPEDITBUSYTWO') . '</option>' .
                      '<option value="stay"' . ($pdatetype === 'stay' ? ' selected="selected"' : '') . '>' . JText::translate('VBO_CONDTEXT_RULE_STAYDATES') . '</option>' .
                      '</select>',
            'type' => 'select',
            'name' => 'dates_type'
        );
        array_push($this->reportFilters, $filter_opt);

        // Listings Filter
        $filter_opt = array(
            'label' => '<label for="listingsfilt">' . JText::translate('VBO_LISTINGS') . '</label>',
            'html' => '<span class="vbo-toolbar-multiselect-wrap">' . $vbo_app->renderElementsDropDown([
                'id'              => 'listingsfilt',
                'elements'        => 'listings',
                'placeholder'     => JText::translate('VBO_LISTINGS'),
                'allow_clear'     => 1,
                'attributes'      => [
                    'name' => 'listings[]',
                    'multiple' => 'multiple',
                ],
                'selected_values' => (array) JFactory::getApplication()->input->get('listings', [], 'array'),
            ]) . '</span>',
            'type' => 'select',
            'multiple' => true,
            'name' => 'listings',
        );
        array_push($this->reportFilters, $filter_opt);

        // get minimum check-in and maximum check-out for dates filters
        $df = $this->getDateFormat();
        $mincheckin = 0;
        $maxcheckout = 0;
        $q = "SELECT MIN(`checkin`) AS `mincheckin`, MAX(`checkout`) AS `maxcheckout` FROM `#__vikbooking_orders` WHERE `status`='confirmed' AND `closure`=0;";
        $this->dbo->setQuery($q);
        $data = $this->dbo->loadAssoc();
        if (!empty($data['mincheckin']) && !empty($data['maxcheckout'])) {
            $mincheckin = $data['mincheckin'];
            $maxcheckout = $data['maxcheckout'];
        }

        // calendars setup
        $pfromdate = $app->input->getString('fromdate', '');
        $ptodate = $app->input->getString('todate', '');
        $js = 'jQuery(function() {
            jQuery(".vbo-report-datepicker:input").datepicker({
                '.(!empty($mincheckin) ? 'minDate: "'.date($df, $mincheckin).'", ' : '').'
                '.(!empty($maxcheckout) ? 'maxDate: "'.date($df, $maxcheckout).'", ' : '').'
                '.(!empty($mincheckin) && !empty($maxcheckout) ? 'yearRange: "'.(date('Y', $mincheckin)).':'.date('Y', $maxcheckout).'", changeMonth: true, changeYear: true, ' : '').'
                dateFormat: "'.$this->getDateFormat('jui').'",
                onSelect: vboReportCheckDates
            });
            '.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
            '.(!empty($ptodate) ? 'jQuery(".vbo-report-datepicker-to").datepicker("setDate", "'.$ptodate.'");' : '').'
        });
        function vboReportCheckDates(selectedDate, inst) {
            if (selectedDate === null || inst === null) {
                return;
            }
            var cur_from_date = jQuery(this).val();
            if (jQuery(this).hasClass("vbo-report-datepicker-from") && cur_from_date.length) {
                var nowstart = jQuery(this).datepicker("getDate");
                var nowstartdate = new Date(nowstart.getTime());
                jQuery(".vbo-report-datepicker-to").datepicker("option", {minDate: nowstartdate});
            }
        }';
        $this->setScript($js);

        return $this->reportFilters;
    }

    /**
     * Loads the report data from the DB.
     * Returns true in case of success, false otherwise.
     * Sets the columns and rows for the report to be displayed.
     *
     * @return  bool
     */
    public function getReportData()
    {
        if ($this->getError()) {
            // export functions may set errors rather than exiting the process, and the View may continue the execution to attempt to render the report.
            return false;
        }

        if ($this->rows) {
            // method must have run already
            return true;
        }

        $app = JFactory::getApplication();

        // get the possibly injected report options
        $options = $this->getReportOptions();

        // injected options will replace request variables, if any
        $opt_fromdate = $options->get('fromdate', '');
        $opt_todate   = $options->get('todate', '');
        $opt_dt_type  = $options->get('dates_type', '');

        // input fields and other vars
        $pfromdate = $opt_fromdate ?: $app->input->getString('fromdate', '');
        $ptodate = $opt_todate ?: $app->input->getString('todate', '');
        $pdatetype = $opt_dt_type ?: $app->input->getString('dates_type', '') ?: 'checkin';

        $pkrsort = $app->input->getString('krsort', $this->defaultKeySort);
        $pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
        $pkrorder = $app->input->getString('krorder', $this->defaultKeyOrder);
        $pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
        $pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';
        $plistings = ((array) $app->input->get('listings', [], 'array')) ?: ((array) $options->get('listings', []));
        $plistings = array_filter(array_map('intval', $plistings));

        $currency_symb = VikBooking::getCurrencySymb();
        $df = $this->getDateFormat();
        $datesep = VikBooking::getDateSeparator();
        if (empty($ptodate)) {
            $ptodate = $pfromdate;
        }

        // get date timestamps
        $from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
        $to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
        if (empty($pfromdate) || empty($from_ts) || empty($to_ts) || $from_ts > $to_ts) {
            $this->setError(JText::translate('VBOREPORTSERRNODATES'));
            return false;
        }

        // preferred date format
        $df = $this->getDateFormat();
        $datesep = VikBooking::getDateSeparator();

        // determine stats calculation type
        $calc_type = 'checkin';
        if ($pdatetype === 'booking') {
            $calc_type = 'booking_dates';
        } elseif ($pdatetype === 'stay') {
            $calc_type = 'stay_dates';
        }

        // access the finance helper object
        $finance = VBOTaxonomyFinance::getInstance();

        // force calculation options
        $finance->setOptions([
            'booking_level' => true,
        ]);

        // get the financial stats for the requested dates
        try {
            $stats = $finance->getStats(date('Y-m-d', $from_ts), date('Y-m-d', $to_ts), $plistings, $calc_type);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // define the columns of the report
        $this->cols = [
            // booking ID
            [
                'key' => 'bid',
                'sortable' => 1,
                'label' => JText::translate('VBDASHUPRESONE'),
            ],
            // date
            [
                'key' => 'day',
                'sortable' => 1,
                'label' => JText::translate('VBOREPORTREVENUEDAY'),
            ],
            // guest
            [
                'key' => 'guest',
                'label' => JText::translate('VBO_GUEST'),
            ],
            // channel
            [
                'key' => 'channel',
                'attr' => [
                    'class="center"',
                ],
                'label' => JText::translate('VBOCHANNEL'),
            ],
            // rooms booked
            [
                'key' => 'roomsnum',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBLIBTEN'),
            ],
            // nights booked
            [
                'key' => 'nights',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBDAYS'),
            ],
            // taxes
            [
                'key' => 'taxes',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBCALCRATESTAX'),
            ],
            // city taxes
            [
                'key' => 'city_taxes',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBCALCRATESCITYTAX'),
            ],
            // fees
            [
                'key' => 'fees',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBCALCRATESFEES'),
            ],
            // commissions
            [
                'key' => 'cmms',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBTOTALCOMMISSIONS'),
            ],
            // damage deposit
            [
                'key' => 'damage_dep',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBO_DAMAGE_DEPOSIT'),
            ],
            // options/extras
            [
                'key' => 'extras',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBOREPORTOPTIONSEXTRAS'),
            ],
            // room revenue
            [
                'key' => 'revenue',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBO_ROOM_REVENUE'),
            ],
            // grand total
            [
                'key' => 'total',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBOREPORTSTOTALROW'),
            ],
            // amount paid
            [
                'key' => 'totalpaid',
                'attr' => [
                    'class="center"',
                ],
                'sortable' => 1,
                'label' => JText::translate('VBPEDITBUSYTOTPAID'),
            ],
        ];

        // options/extras counter
        $extrasCounter = 0;

        // grand total counter
        $grandTotalCounter = 0;

        // total paid counter
        $totalPaidCounter = 0;

        // iterate all bookings involved to build the report rows
        foreach (($stats['bids'] ?? []) as $bookingId) {
            try {
                $registry = VBOBookingRegistry::getInstance(['id' => $bookingId]);
            } catch (Exception $e) {
                // ignore broken reservation and go next
                continue;
            }

            // obtain booking-customer data
            list($customer_nominative, $booking_avatar_src, $booking_avatar_alt) = $registry->getBookingCustomerData();

            // extract booking details
            $bookingChannel = $registry->getProperty('channel');
            $bookingChannel = $registry->getProperty('idorderota') ? $bookingChannel : '';

            // get booking revenue
            $bookingRevenue = (float) $stats['_bid_stats'][$bookingId]['revenue'] ?? 0;

            // get options/extras revenue
            $extrasRevenue = max(0, 
                (float) $registry->getProperty('total', 0) - 
                $bookingRevenue - 
                ((float) $stats['_bid_stats'][$bookingId]['taxes'] ?? 0) - 
                ((float) $stats['_bid_stats'][$bookingId]['damage_deposits'] ?? 0) - 
                ((float) $stats['_bid_stats'][$bookingId]['cmms'] ?? 0)
            );

            // push report row for the current reservation
            $this->rows[] = [
                // booking ID
                [
                    'key' => 'bid',
                    'callback' => function ($val) {
                        return '<a href="index.php?option=com_vikbooking&task=editorder&cid[]='.$val.'" target="_blank"><i class="'.VikBookingIcons::i('external-link').'"></i> '.$val.'</a>';
                    },
                    'no_export_callback' => 1,
                    'value' => $registry->getID(),
                ],
                // date
                [
                    'key' => 'day',
                    'callback' => function ($val) use ($df, $datesep) {
                        return date(str_replace("/", $datesep, $df), $val);
                    },
                    'value' => $registry->getProperty('ts', 0),
                ],
                // guest
                [
                    'key' => 'guest',
                    'value' => $customer_nominative,
                ],
                // channel
                [
                    'key' => 'channel',
                    'attr' => [
                        'class="center"',
                    ],
                    'callback' => function ($val) use ($booking_avatar_src) {
                        if ($val && $booking_avatar_src) {
                            $channelparts = explode('_', $val);
                            return ($channelparts[1] ?? '') ?: $val;
                        }
                        return JText::translate('VBORDFROMSITE');
                    },
                    'value' => $bookingChannel,
                ],
                // rooms booked
                [
                    'key' => 'roomsnum',
                    'attr' => [
                        'class="center"',
                    ],
                    'value' => $registry->getProperty('roomsnum', 1),
                ],
                // nights booked
                [
                    'key' => 'nights',
                    'attr' => [
                        'class="center"',
                    ],
                    'value' => $registry->getProperty('days', 1),
                ],
                // taxes
                [
                    'key' => 'taxes',
                    'attr' => [
                        'class="center"',
                    ],
                    'callback' => function ($val) use ($currency_symb) {
                        return VikBooking::formatCurrencyNumber(
                            VikBooking::numberFormat($val),
                            $currency_symb
                        );
                    },
                    'value' => $stats['_bid_stats'][$bookingId]['tot_vat'] ?? 0,
                ],
                // city taxes
                [
                    'key' => 'city_taxes',
                    'attr' => [
                        'class="center"',
                    ],
                    'callback' => function ($val) use ($currency_symb) {
                        return VikBooking::formatCurrencyNumber(
                            VikBooking::numberFormat($val),
                            $currency_symb
                        );
                    },
                    'value' => $stats['_bid_stats'][$bookingId]['city_taxes'] ?? 0,
                ],
                // fees
                [
                    'key' => 'fees',
                    'attr' => [
                        'class="center"',
                    ],
                    'callback' => function ($val) use ($currency_symb) {
                        return VikBooking::formatCurrencyNumber(
                            VikBooking::numberFormat($val),
                            $currency_symb
                        );
                    },
                    'value' => $registry->getProperty('tot_fees', 0),
                ],
                // commissions
                [
                    'key' => 'cmms',
                    'attr' => [
                        'class="center"',
                    ],
                    'callback' => function ($val) use ($currency_symb) {
                        return VikBooking::formatCurrencyNumber(
                            VikBooking::numberFormat($val),
                            $currency_symb
                        );
                    },
                    'value' => $stats['_bid_stats'][$bookingId]['cmms'] ?? 0,
                ],
                // damage deposit
                [
                    'key' => 'damage_dep',
                    'attr' => [
                        'class="center"',
                    ],
                    'callback' => function ($val) use ($currency_symb) {
                        return VikBooking::formatCurrencyNumber(
                            VikBooking::numberFormat($val),
                            $currency_symb
                        );
                    },
                    'value' => $stats['_bid_stats'][$bookingId]['damage_deposits'] ?? 0,
                ],
                // options/extras
                [
                    'key' => 'extras',
                    'attr' => [
                        'class="center"',
                    ],
                    'callback' => function ($val) use ($currency_symb) {
                        return VikBooking::formatCurrencyNumber(
                            VikBooking::numberFormat($val),
                            $currency_symb
                        );
                    },
                    'value' => $extrasRevenue,
                ],
                // revenue
                [
                    'key' => 'revenue',
                    'attr' => [
                        'class="center"',
                    ],
                    'callback' => function ($val) use ($currency_symb) {
                        return VikBooking::formatCurrencyNumber(
                            VikBooking::numberFormat($val),
                            $currency_symb
                        );
                    },
                    'value' => $bookingRevenue,
                ],
                // grand total
                [
                    'key' => 'total',
                    'attr' => [
                        'class="center"',
                    ],
                    'callback' => function ($val) use ($currency_symb) {
                        return VikBooking::formatCurrencyNumber(
                            VikBooking::numberFormat($val),
                            $currency_symb
                        );
                    },
                    'value' => $registry->getProperty('total', 0),
                ],
                // amount paid
                [
                    'key' => 'totalpaid',
                    'attr' => [
                        'class="center"',
                    ],
                    'callback' => function ($val) use ($currency_symb) {
                        return VikBooking::formatCurrencyNumber(
                            VikBooking::numberFormat($val),
                            $currency_symb
                        );
                    },
                    'value' => $registry->getProperty('totpaid', 0),
                ],
            ];

            // increase options/extras counter
            $extrasCounter += $extrasRevenue;

            // increase grand total counter
            $grandTotalCounter += $registry->getProperty('total', 0);

            // increase total paid counter
            $totalPaidCounter += $registry->getProperty('totpaid', 0);
        }

        // sort rows
        $this->sortRows($pkrsort, $pkrorder);
        
        // push footer row with the calculated financial stats
        $this->footerRow[] = [
            // booking ID
            [
                'attr' => [
                    'class="vbo-report-total"',
                ],
                'value' => '<h3>' . JText::translate('VBOREPORTSTOTALROW') . '</h3>',
            ],
            // date
            [
                'value' => '',
            ],
            // guest
            [
                'value' => '',
            ],
            // channel
            [
                'value' => '',
            ],
            // rooms booked
            [
                'attr' => [
                    'class="center"',
                ],
                'value' => $stats['rooms_booked'] ?? 0,
            ],
            // nights booked
            [
                'attr' => [
                    'class="center"',
                ],
                'value' => $stats['nights_booked'] ?? 0,
            ],
            // taxes
            [
                'attr' => [
                    'class="center"',
                ],
                'callback' => function ($val) use ($currency_symb) {
                    return VikBooking::formatCurrencyNumber(
                        VikBooking::numberFormat($val),
                        $currency_symb
                    );
                },
                'value' => $stats['tot_vat'] ?? 0,
            ],
            // city taxes
            [
                'attr' => [
                    'class="center"',
                ],
                'callback' => function ($val) use ($currency_symb) {
                    return VikBooking::formatCurrencyNumber(
                        VikBooking::numberFormat($val),
                        $currency_symb
                    );
                },
                'value' => $stats['city_taxes'] ?? 0,
            ],
            // fees
            [
                'attr' => [
                    'class="center"',
                ],
                'callback' => function ($val) use ($currency_symb) {
                    return VikBooking::formatCurrencyNumber(
                        VikBooking::numberFormat($val),
                        $currency_symb
                    );
                },
                'value' => 0,
            ],
            // commissions
            [
                'attr' => [
                    'class="center"',
                ],
                'callback' => function ($val) use ($currency_symb) {
                    return VikBooking::formatCurrencyNumber(
                        VikBooking::numberFormat($val),
                        $currency_symb
                    );
                },
                'value' => $stats['ota_cmms'] ?? 0,
            ],
            // damage deposit
            [
                'attr' => [
                    'class="center"',
                ],
                'callback' => function ($val) use ($currency_symb) {
                    return VikBooking::formatCurrencyNumber(
                        VikBooking::numberFormat($val),
                        $currency_symb
                    );
                },
                'value' => $stats['damage_deposits'] ?? 0,
            ],
            // options/extras
            [
                'attr' => [
                    'class="center"',
                ],
                'callback' => function ($val) use ($currency_symb) {
                    return VikBooking::formatCurrencyNumber(
                        VikBooking::numberFormat($val),
                        $currency_symb
                    );
                },
                'value' => $extrasCounter,
            ],
            // room revenue
            [
                'attr' => [
                    'class="center"',
                ],
                'callback' => function ($val) use ($currency_symb) {
                    return VikBooking::formatCurrencyNumber(
                        VikBooking::numberFormat($val),
                        $currency_symb
                    );
                },
                'value' => $stats['revenue'] ?? 0,
            ],
            // grand total
            [
                'attr' => [
                    'class="center"',
                ],
                'callback' => function ($val) use ($currency_symb) {
                    return VikBooking::formatCurrencyNumber(
                        VikBooking::numberFormat($val),
                        $currency_symb
                    );
                },
                'value' => $grandTotalCounter,
            ],
            // amount paid
            [
                'attr' => [
                    'class="center"',
                ],
                'callback' => function ($val) use ($currency_symb) {
                    return VikBooking::formatCurrencyNumber(
                        VikBooking::numberFormat($val),
                        $currency_symb
                    );
                },
                'value' => $totalPaidCounter,
            ],
        ];

        // Debug
        if ($this->debug) {
            $this->setWarning('path to report file = '.urlencode(dirname(__FILE__)).'<br/>');
            $this->setWarning('$stats:<pre>'.print_r($stats, true).'</pre><br/>');
        }

        return true;
    }

    /**
     * Registers the name to give to the CSV file being exported.
     * 
     * @return  void
     */
    private function registerExportCSVFileName()
    {
        $app = JFactory::getApplication();

        $pfromdate = $app->input->getString('fromdate', '');
        $ptodate = $app->input->getString('todate', '');

        $this->setExportCSVFileName($this->reportName . '-' . str_replace('/', '_', $pfromdate) . '-' . str_replace('/', '_', $ptodate) . '.csv');
    }
}

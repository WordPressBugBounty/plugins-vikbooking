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
 * RMS Booking Pace report implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
class VikBookingReportRmsBookingPace extends VikBookingReport
{
    /**
     * Property 'defaultKeySort' is used by the View that renders the report.
     */
    public $defaultKeySort = 'line';

    /**
     * Property 'defaultKeyOrder' is used by the View that renders the report.
     */
    public $defaultKeyOrder = 'ASC';

    /**
     * Property 'exportAllowed' is used by the View to display the export button.
     */
    public $exportAllowed = 1;

    /**
     * @var  array
     */
    protected array $paceOptions = [];

    /**
     * Class constructor will define some default properties.
     */
    public function __construct()
    {
        $this->reportFile = basename(__FILE__, '.php');
        $this->reportName = JText::translate('VBO_RMS_BOOKING_PACE');
        $this->reportFilters = [];

        $this->cols = [];
        $this->rows = [];
        $this->footerRow = [];

        $this->registerExportCSVFileName();
    }

    /**
     * @inheritDoc
     */
    public function preflight()
    {
        return class_exists('VikChannelManagerConfig');
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
        $dbo = JFactory::getDbo();

        // get VBO Application Object
        $vbo_app = VikBooking::getVboApplication();

        // load the jQuery UI Datepicker
        $this->loadDatePicker();

        // load Charts assets
        $this->loadChartsAssets();

        // default date format
        $df = $this->getDateFormat();

        // obtain min check-in and max check-out timestamps
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select([
                    'MIN(' . $dbo->qn('ts') . ') AS ' . $dbo->qn('mints'),
                    'MIN(' . $dbo->qn('checkin') . ') AS ' . $dbo->qn('mincheckin'),
                    'MAX(' . $dbo->qn('checkout') . ') AS ' . $dbo->qn('maxcheckout'),
                ])
                ->from($dbo->qn('#__vikbooking_orders'))
                ->where($dbo->qn('status') . ' = ' . $dbo->q('confirmed'))
                ->where($dbo->qn('closure') . ' = 0')
        );
        $data = $dbo->loadAssoc();
        $mintsdt = date($df, $data['mints'] ?? time());
        $mincheckindt = date($df, $data['mincheckin'] ?? time());
        $maxcheckoutdt = date($df, $data['maxcheckout'] ?? time());
        $minyearts = date('Y', $data['mints'] ?? time());
        $maxyearts = date('Y');
        $minyear = date('Y', $data['mincheckin'] ?? time());
        $maxyear = date('Y', $data['maxcheckout'] ?? time());

        // start building filters
        $this->reportFilters = [
            // pickup date from
            [
                'label'   => '<label for="pickup_date_from">' . JText::translate('VBO_PICKUP_DATE_START') . '</label>',
                'html'    => '<input type="text" id="pickup_date_from" name="pickup_date_from" value="' . date(str_replace('d', '01', $df), strtotime('-1 month')) . '" class="vbo-report-datepicker vbo-report-datepicker-pickupdt vbo-report-datepicker-pickup-date-from" />',
                'type'    => 'calendar',
                'name'    => 'pickup_date_from',
                'default' => date(str_replace('d', '01', $df), strtotime('-1 month')),
            ],
            // pickup date to
            [
                'label'   => '<label for="pickup_date_to">' . JText::translate('VBO_PICKUP_DATE_END') . '</label>',
                'html'    => '<input type="text" id="pickup_date_to" name="pickup_date_to" value="' . date($df) . '" class="vbo-report-datepicker vbo-report-datepicker-pickupdt vbo-report-datepicker-pickup-date-to" />',
                'type'    => 'calendar',
                'name'    => 'pickup_date_to',
                'default' => date($df),
            ],
            // target date from
            [
                'label'   => '<label for="target_date_from">' . JText::translate('VBO_TARGET_DATE_START') . '</label>',
                'html'    => '<input type="text" id="target_date_from" name="target_date_from" value="' . date(str_replace('d', '01', $df), strtotime('+3 months')) . '" class="vbo-report-datepicker vbo-report-datepicker-targetdt vbo-report-datepicker-target-date-from" />',
                'type'    => 'calendar',
                'name'    => 'target_date_from',
                'default' => date(str_replace('d', '01', $df), strtotime('+3 months')),
            ],
            // target date to
            [
                'label'   => '<label for="target_date_to">' . JText::translate('VBO_TARGET_DATE_END') . '</label>',
                'html'    => '<input type="text" id="target_date_to" name="target_date_to" value="' . date(str_replace('d', 't', $df), strtotime('+3 months')) . '" class="vbo-report-datepicker vbo-report-datepicker-targetdt vbo-report-datepicker-target-date-to" />',
                'type'    => 'calendar',
                'name'    => 'target_date_to',
                'default' => date(str_replace('d', 't', $df), strtotime('+3 months')),
            ],
            // listing IDs
            [
                'label' => '<label for="listingsfilt">' . ucfirst(strtolower(JText::translate('VBROOMFILTER'))) . '</label>',
                'html'  => '<span class="vbo-toolbar-multiselect-wrap">' . $vbo_app->renderElementsDropDown([
                    'id'              => 'listingsfilt',
                    'elements'        => 'listings',
                    'load_categories' => 1,
                    'categories_lbl'  => JText::translate('VBOCATEGORYFILTER'),
                    'placeholder'     => JText::translate('VBO_LISTINGS'),
                    'allow_clear'     => 1,
                    'attributes'      => [
                        'name' => 'listings[]',
                        'multiple' => 'multiple',
                    ],
                    'selected_values' => (array) $app->input->get('listings', [], 'array'),
                ]) . '</span>',
                'type'     => 'select',
                'multiple' => true,
                'name'     => 'listings',
            ],
        ];

        // set script for rendering the datepicker calendars
        $juidf = $this->getDateFormat('jui');
        $defPickupFromDt = $app->input->getString('pickup_date_from') ?: date(str_replace('d', '01', $df), strtotime('-1 month'));
        $defPickupToDt = $app->input->getString('pickup_date_to') ?: date($df);
        $defTargetFromDt = $app->input->getString('target_date_from') ?: date(str_replace('d', '01', $df), strtotime('+3 months'));
        $defTargetToDt = $app->input->getString('target_date_to') ?: date(str_replace('d', 't', $df), strtotime('+3 months'));
        $js = <<<JAVASCRIPT
jQuery(() => {
    jQuery('.vbo-report-datepicker-pickupdt:input').datepicker({
        minDate: '$mintsdt',
        maxDate: '+0D',
        yearRange: '$minyearts:$maxyearts',
        changeMonth: true,
        changeYear: true,
        dateFormat: '$juidf',
        onSelect: vboReportCheckPickupDates,
    });
    jQuery('.vbo-report-datepicker-targetdt:input').datepicker({
        minDate: '$mincheckindt',
        maxDate: '$maxcheckoutdt',
        yearRange: '$minyear:$maxyear',
        changeMonth: true,
        changeYear: true,
        dateFormat: '$juidf',
        onSelect: vboReportCheckTargetDates,
    });
    jQuery('.vbo-report-datepicker-pickup-date-from').datepicker('setDate', '$defPickupFromDt');
    jQuery('.vbo-report-datepicker-pickup-date-to').datepicker('setDate', '$defPickupToDt');
    jQuery('.vbo-report-datepicker-target-date-from').datepicker('setDate', '$defTargetFromDt');
    jQuery('.vbo-report-datepicker-target-date-to').datepicker('setDate', '$defTargetToDt');
    function vboReportCheckPickupDates(selectedDate, inst) {
        if (selectedDate === null || inst === null) {
            return;
        }
        let cur_from_date = jQuery(this).val();
        if (jQuery(this).hasClass('vbo-report-datepicker-pickup-date-from') && cur_from_date.length) {
            let nowstart = jQuery(this).datepicker('getDate');
            let nowstartdate = new Date(nowstart.getTime());
            jQuery('.vbo-report-datepicker-pickup-date-to').datepicker('option', {minDate: nowstartdate});
        }
    }
    function vboReportCheckTargetDates(selectedDate, inst) {
        if (selectedDate === null || inst === null) {
            return;
        }
        let cur_from_date = jQuery(this).val();
        if (jQuery(this).hasClass('vbo-report-datepicker-target-date-from') && cur_from_date.length) {
            let nowstart = jQuery(this).datepicker('getDate');
            let nowstartdate = new Date(nowstart.getTime());
            jQuery('.vbo-report-datepicker-target-date-to').datepicker('option', {minDate: nowstartdate});
        }
    }
});
JAVASCRIPT;

        $this->setScript($js);

        // return all filters
        return $this->reportFilters;
    }

    /**
     * Allows the report to define the sub-filters template.
     * 
     * @return  ?string
     */
    public function getSubFiltersTpl()
    {
        $app = JFactory::getApplication();

        // current target dates
        $currentTargetFrom = $app->input->getString('target_date_from', '');
        $currentTargetTo = $app->input->getString('target_date_to', '');

        // default date format
        $df = $this->getDateFormat();

        // months map
        $monthsMap = [
            JText::translate('VBSHORTMONTHONE'),
            JText::translate('VBSHORTMONTHTWO'),
            JText::translate('VBSHORTMONTHTHREE'),
            JText::translate('VBSHORTMONTHFOUR'),
            JText::translate('VBSHORTMONTHFIVE'),
            JText::translate('VBSHORTMONTHSIX'),
            JText::translate('VBSHORTMONTHSEVEN'),
            JText::translate('VBSHORTMONTHEIGHT'),
            JText::translate('VBSHORTMONTHNINE'),
            JText::translate('VBSHORTMONTHTEN'),
            JText::translate('VBSHORTMONTHELEVEN'),
            JText::translate('VBSHORTMONTHTWELVE'),
        ];

        // starting month
        $monthStart = new DateTime(date('Y-m-01'), new DateTimeZone(date_default_timezone_get()));

        // build selectable months template
        $selMonths = '';
        for ($i = 0; $i < 12; $i++) {
            $monthIndex = (int) $monthStart->format('n') - 1;
            $monthName = $monthsMap[$monthIndex] . ' ' . $monthStart->format('y');
            $setTargetFrom = $monthStart->format(str_replace('d', '01', $df));
            $setTargetTo = $monthStart->format(str_replace('d', 't', $df));
            $activeClass = $currentTargetFrom == $setTargetFrom && $currentTargetTo == $setTargetTo ? ' vbo-report-subfilter-month-active' : '';
            $selMonths .= <<<HTML
            <span class="vbo-report-subfilter-month$activeClass" data-target-from="$setTargetFrom" data-target-to="$setTargetTo">$monthName</span>
            HTML;
            $monthStart->modify('+1 month');
        }

        // build the sub-filters template string
        $tpl = <<<HTML
        <div class="vbo-report-subfilters-rms">
            <div class="vbo-report-subfilter-months">$selMonths</div>
        </div>
        <script>
            VBOCore.DOMLoaded(() => {
                document.querySelectorAll('.vbo-report-subfilter-month').forEach((selMonth) => {
                    selMonth.addEventListener('click', () => {
                        // gather month dates
                        let fromDt = selMonth.getAttribute('data-target-from');
                        let toDt = selMonth.getAttribute('data-target-to');
                        // update values in datepicker calendars
                        jQuery('.vbo-report-datepicker-target-date-from').datepicker('setDate', fromDt);
                        jQuery('.vbo-report-datepicker-target-date-to').datepicker('setDate', toDt);
                        // remove active month class from all buttons
                        document.querySelectorAll('.vbo-report-subfilter-month.vbo-report-subfilter-month-active').forEach((activeMonth) => {
                            activeMonth.classList.remove('vbo-report-subfilter-month-active');
                        });
                        // add active month class to current button
                        selMonth.classList.add('vbo-report-subfilter-month-active');
                        // trigger event to (re)load report data
                        VBOCore.emitEvent('vbo_report_reload');
                    });
                });
            });
        </script>
        HTML;

        return $tpl;
    }

    /**
     * Loads report data by setting the columns and rows to be displayed.
     *
     * @return  bool
     */
    public function getReportData()
    {
        if ($this->getError()) {
            // export functions may set errors rather than exiting the process, and
            // the View may continue the execution to attempt to render the report.
            return false;
        }

        if ($this->rows) {
            // method must have run already
            return true;
        }

        $app = JFactory::getApplication();

        // default environment values
        $df = $this->getDateFormat();
        $datesep = VikBooking::getDateSeparator();
        $currency_symb = VikBooking::getCurrencySymb();

        // week days short names map
        $wdays_map = [
            JText::translate('VBSUN'),
            JText::translate('VBMON'),
            JText::translate('VBTUE'),
            JText::translate('VBWED'),
            JText::translate('VBTHU'),
            JText::translate('VBFRI'),
            JText::translate('VBSAT'),
        ];

        // get the possibly injected report options
        $options = $this->getReportOptions();

        // collect report filters
        $pickup_date_from = $options->get('pickup_date_from') ?: $app->input->getString('pickup_date_from') ?: date(str_replace('d', '01', $df), strtotime('-1 month'));
        $pickup_date_to   = $options->get('pickup_date_to') ?: $app->input->getString('pickup_date_to') ?: date($df);
        $target_date_from = $options->get('target_date_from') ?: $app->input->getString('target_date_from') ?: date(str_replace('d', '01', $df), strtotime('+3 months'));
        $target_date_to   = $options->get('target_date_to') ?: $app->input->getString('target_date_to') ?: date(str_replace('d', 't', $df), strtotime('+3 months'));
        $interval         = $options->get('interval') ?: $app->input->getString('interval') ?: 'DAY';
        $listing_ids      = (array) ($options->get('listings') ?: $app->input->getString('listings') ?: []);

        // determine sorting and ordering
        $krsort  = $app->input->getString('krsort') ?: $this->defaultKeySort;
        $krorder = $app->input->getString('krorder') ?: $this->defaultKeyOrder;
        $krorder = $krorder == 'DESC' ? 'DESC' : 'ASC';

        // tell whether sorting is allowed
        $sortingAllowed = 1;

        // build pace options
        $this->paceOptions = [
            'pickup' => [
                'from' => date('Y-m-d', VikBooking::getDateTimestamp($pickup_date_from)),
                'to'   => date('Y-m-d', VikBooking::getDateTimestamp($pickup_date_to)),
            ],
            'target' => [
                'from' => date('Y-m-d', VikBooking::getDateTimestamp($target_date_from)),
                'to'   => date('Y-m-d', VikBooking::getDateTimestamp($target_date_to)),
            ],
            'interval'   => $interval,
            'listings'   => $listing_ids,
            'sort_rooms' => 'occupancy',
        ];

        try {
            // obtain booking pace data
            $paceData = VBORmsPace::getInstance()->getBookingData($this->paceOptions);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // define the report columns
        $this->cols = [
            // period start date
            [
                'key' => 'date',
                'label' => JText::translate('VBPVIEWORDERSONE'),
                'sortable' => $sortingAllowed,
            ],
            // new bookings
            [
                'key' => 'nb',
                'label' => JText::translate('VBO_NEW_BOOKINGS'),
                'center' => 1,
                'sortable' => $sortingAllowed,
            ],
            // cancellations
            [
                'key' => 'cb',
                'label' => JText::translate('VBO_CANCELLATIONS'),
                'center' => 1,
                'sortable' => $sortingAllowed,
            ],
            // on the books
            [
                'key' => 'otb',
                'label' => JText::translate('VBCUSTOMERTOTBOOKINGS'),
                'sortable' => $sortingAllowed,
                'center' => 1,
            ],
            // adr
            [
                'key' => 'adr',
                'label' => JText::translate('VBOREPORTREVENUEADR'),
                'sortable' => $sortingAllowed,
                'center' => 1,
            ],
            // Room Revenue
            [
                'key' => 'roomrev',
                'label' => JText::translate('VBO_GROSS_BOOKING_VALUE'),
                'sortable' => $sortingAllowed,
                'center' => 1,
            ],
        ];

        // start global line counters
        $lineCounter = 0;

        // start metric indexes and values for the various column keys
        $metricIndexes = [];
        $metricValues  = [];

        // iterate booking pace data pickup dates
        foreach ($paceData['pace'] as $parseIndex => $metrics) {
            // increase line counter
            $lineCounter++;

            // build row metric columns
            $metricRowCols = [
                // period start date
                [
                    'key' => 'date',
                    'callback' => function($dt) use ($df, $datesep, $wdays_map) {
                        return sprintf('%s, %s', $wdays_map[$dt->format('w')] ?? '', $dt->format(str_replace("/", $datesep, $df)));
                    },
                    'value' => $metrics['date'],
                ],
                // new bookings
                [
                    'key' => 'nb',
                    'callback' => function($value) {
                        if ($value > 0) {
                            return '<span class="label label-success">' . $value . '</span>';
                        }
                        return '<span class="label">' . $value . '</span>';
                    },
                    'callback_export' => function($value) {
                        return $value;
                    },
                    'value' => $metrics['nb'],
                    'center' => 1,
                ],
                // cancellations
                [
                    'key' => 'cb',
                    'callback' => function($value) {
                        if ($value > 0) {
                            return '<span class="label label-error">' . $value . '</span>';
                        }
                        return '<span class="label">' . $value . '</span>';
                    },
                    'callback_export' => function($value) {
                        return $value;
                    },
                    'value' => $metrics['cb'],
                    'center' => 1,
                ],
                // on the books
                [
                    'key' => 'otb',
                    'callback' => function($value) {
                        if ($value > 0) {
                            return '<span class="label label-info">' . $value . '</span>';
                        }
                        return '<span class="label">' . $value . '</span>';
                    },
                    'callback_export' => function($value) {
                        return $value;
                    },
                    'value' => $metrics['otb'],
                    'center' => 1,
                ],
                // adr
                [
                    'key' => 'adr',
                    'value' => $metrics['adr'],
                    'center' => 1,
                    'callback' => function($metric) {
                        return VikBooking::formatCurrencyNumber($metric);
                    },
                ],
                // Room Revenue
                [
                    'key' => 'roomrev',
                    'value' => $metrics['roomrev'],
                    'center' => 1,
                    'callback' => function($metric) {
                        return VikBooking::formatCurrencyNumber($metric);
                    },
                ],
            ];

            if (!$metricIndexes) {
                // store the indexes for each metric key
                $metricIndexes = array_values(array_column(array_filter($metricRowCols, function($colData) {
                    return empty($colData['ignore_view']);
                }), 'key'));
            }

            // update metric values for current target to be used for the footer calculations
            $metricValues['nb']  = ($metricValues['nb'] ?? 0) + $metrics['nb'];
            $metricValues['cb']  = ($metricValues['cb'] ?? 0) + $metrics['cb'];
            $metricValues['otb'] = max(($metricValues['otb'] ?? 0), $metrics['otb']);

            // push metric row with calculated columns
            $this->rows[] = $metricRowCols;
        }

        if ($sortingAllowed) {
            // sort rows
            $this->sortRows($krsort, $krorder);
        }

        // count total cells per row and total metric values for the footer
        $cellsPerRow = count($metricIndexes);
        $footerColsCount = count($metricValues) ?: 1;
        $footerColspan = floor($cellsPerRow / $footerColsCount);

        // build report footer row
        $this->footerRow[] = [
            // new bookings
            [
                'callback' => function($metricValue) {
                    // prepare template variables
                    $blockTitle = JText::translate('VBO_NEW_BOOKINGS');
                    $metricCls = 'vbo-report-footer-rms-metric-target';

                    // return the HTML string to build this footer cell
                    return <<<HTML
                    <div class="vbo-report-footer-rms-wrap">
                        <div class="vbo-report-footer-rms-title">$blockTitle</div>
                        <div class="vbo-report-footer-rms-metrics">
                            <div class="vbo-report-footer-rms-metric $metricCls">
                                <div class="vbo-report-footer-rms-metric-value">$metricValue</div>
                            </div>
                        </div>
                    </div>
                    HTML;
                },
                'callback_export' => function($metricValue) {
                    return $metricValue;
                },
                'value' => $metricValues['nb'],
                'html' => 1,
                'center' => 1,
                'colspan' => $footerColspan,
            ],
            // cancellations
            [
                'callback' => function($metricValue) {
                    // prepare template variables
                    $blockTitle = JText::translate('VBO_CANCELLATIONS');
                    $metricCls = 'vbo-report-footer-rms-metric-target';

                    // return the HTML string to build this footer cell
                    return <<<HTML
                    <div class="vbo-report-footer-rms-wrap">
                        <div class="vbo-report-footer-rms-title">$blockTitle</div>
                        <div class="vbo-report-footer-rms-metrics">
                            <div class="vbo-report-footer-rms-metric $metricCls">
                                <div class="vbo-report-footer-rms-metric-value">$metricValue</div>
                            </div>
                        </div>
                    </div>
                    HTML;
                },
                'callback_export' => function($metricValue) {
                    return $metricValue;
                },
                'value' => $metricValues['cb'],
                'html' => 1,
                'center' => 1,
                'colspan' => $footerColspan,
            ],
            // on the books
            [
                'callback' => function($metricValue) {
                    // prepare template variables
                    $blockTitle = JText::translate('VBCUSTOMERTOTBOOKINGS');
                    $metricCls = 'vbo-report-footer-rms-metric-target';

                    // return the HTML string to build this footer cell
                    return <<<HTML
                    <div class="vbo-report-footer-rms-wrap">
                        <div class="vbo-report-footer-rms-title">$blockTitle</div>
                        <div class="vbo-report-footer-rms-metrics">
                            <div class="vbo-report-footer-rms-metric $metricCls">
                                <div class="vbo-report-footer-rms-metric-value">$metricValue</div>
                            </div>
                        </div>
                    </div>
                    HTML;
                },
                'callback_export' => function($metricValue) {
                    return $metricValue;
                },
                'value' => $metricValues['otb'],
                'html' => 1,
                'center' => 1,
                'colspan' => $footerColspan,
            ],
        ];

        if ($cellsPerRow > ($footerColspan * $footerColsCount)) {
            // add empty cells to footer rows
            for ($i = 1; $i <= ($cellsPerRow - ($footerColspan * $footerColsCount)); $i++) {
                // push empty row
                $this->footerRow[0][] = [
                    'value' => '',
                ];
            }
        }

        // process completed with success
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getChartTitle()
    {
        $df = $this->getDateFormat();
        $datesep = VikBooking::getDateSeparator();

        $pickup_title_parts = [
            $this->rows[0][0]['value'] ?? null,
            $this->rows[count($this->rows) - 1][0]['value'] ?? null,
        ];

        $target_title_parts = [
            $this->paceOptions['target']['from'] ?? null,
            $this->paceOptions['target']['to'] ?? null,
        ];

        return JText::translate('VBO_RMS_BOOKING_PACE') . ' ' . implode(' - ', array_map(function($dt) use ($df, $datesep) {
            return $dt->format(str_replace('/', $datesep, $df));
        }, array_filter($pickup_title_parts))) . ', ' . implode(' - ', array_map(function($dt) use ($df, $datesep) {
            return date(str_replace('/', $datesep, $df), strtotime($dt));
        }, array_filter($target_title_parts)));
    }

    /**
     * @inheritDoc
     */
    public function getChart(?array $data = null)
    {
        if (!$this->getReportRows() && !$this->getReportData()) {
            return '';
        }

        // build chart labels and dataset values
        $chartLabels = [];
        $chartDatasetValues = [
            'nb'  => [],
            'cb'  => [],
            'otb' => [],
        ];

        // iterate all report rows
        foreach ($this->getReportRows() as $row) {
            // push date to labels (first key of each row)
            $chartLabels[] = $row[0]['callback']($row[0]['value']);
            // push dataset values
            $chartDatasetValues['nb'][] = $row[1]['value'];
            $chartDatasetValues['cb'][] = $row[2]['value'];
            $chartDatasetValues['otb'][] = $row[3]['value'];
        }

        // the canvas element ID and tag
        $canvas_id   = 'vbo-report-booking-pace-chart-canvas-' . uniqid();
        $canvas_html = '<canvas id="' . $canvas_id . '" class="vbo-report-chart-canvas"></canvas>';

        if (empty($this->chartScript)) {
            // prepare JSON variables
            $chartLabelsJSON = json_encode($chartLabels);
            $nbDatasetJSON = json_encode($chartDatasetValues['nb']);
            $cbDatasetJSON = json_encode($chartDatasetValues['cb']);
            $otbDatasetJSON = json_encode($chartDatasetValues['otb']);

            $tnNb  = json_encode(JText::translate('VBO_NEW_BOOKINGS'));
            $tnCb  = json_encode(JText::translate('VBO_CANCELLATIONS'));
            $tnOtb = json_encode(JText::translate('VBCUSTOMERTOTBOOKINGS'));

            // prepare the necessary script to render the Chart
            $this->chartScript = <<<JAVASCRIPT
            VBOCore.DOMLoaded(() => {
                const vboReportCanvas = document.getElementById('$canvas_id');
                const vboReportCtx = vboReportCanvas.getContext('2d');
                const vboReportData = {
                    labels: $chartLabelsJSON,
                    datasets: [
                        {
                            label: $tnNb,
                            data: $nbDatasetJSON,
                            borderColor: 'rgba(0, 100, 0, 1)',
                            backgroundColor: 'rgba(0, 100, 0, 0.2)',
                        },
                        {
                            label: $tnCb,
                            data: $cbDatasetJSON,
                            borderColor: 'rgba(220, 20, 60, 1)',
                            backgroundColor: 'rgba(220, 20, 60, 0.2)',
                        },
                        {
                            label: $tnOtb,
                            data: $otbDatasetJSON,
                            borderColor: 'rgba(30, 144, 255, 1)',
                            backgroundColor: 'rgba(30, 144, 255, 0.2)',
                        },
                    ],
                };
                const vboReportChart = new Chart(vboReportCtx, {
                    type: 'line',
                    data: vboReportData,
                    options: {
                        responsive: true,
                        legend: {
                            display: true,
                        },
                        // axes handling
                        scales: {
                            // Y Axis properties
                            yAxes: [{
                                // make sure the chart starts at 0
                                ticks: {
                                    // format value as currency
                                    callback: (value) => parseInt(value),
                                    beginAtZero: true,
                                    stepSize: 1,
                                },
                            }],
                        },
                    },
                });
            });
            JAVASCRIPT;

            // set the necessary script
            $this->setScript($this->chartScript);
        }

        // return the canvas HTML
        return $canvas_html;
    }

    /**
     * @inheritDoc
     */
    public function getChartMetaData($position = null, $data = null)
    {
        if ((!$this->getReportRows() && !$this->getReportData()) || !$position) {
            return [];
        }

        $metrics = [
            'nb' => 0,
            'cb' => 0,
            'otb' => [],
        ];

        // iterate all report rows
        foreach ($this->getReportRows() as $row) {
            // sum new bookings
            $metrics['nb'] += $row[1]['value'];
            // sum cancellations
            $metrics['cb'] += $row[2]['value'];
            // push "on the books" stat
            $metrics['otb'][] = $row[3]['value'];
        }

        $metaData = [
            'bottom' => [
                [
                    'key'   => 'nb',
                    'label' => JText::translate('VBO_NEW_BOOKINGS'),
                    'value' => $metrics['nb'],
                    'class' => 'vbo-report-chart-meta-max',
                ],
                [
                    'key'   => 'cb',
                    'label' => JText::translate('VBO_CANCELLATIONS'),
                    'value' => $metrics['cb'],
                    'class' => 'vbo-report-chart-meta-min',
                ],
            ],
        ];

        return $metaData[$position] ?? [];
    }

    /**
     * Registers the name to give to the CSV file being exported.
     * 
     * @return  void
     */
    protected function registerExportCSVFileName()
    {
        $app = JFactory::getApplication();

        $nameValues = array_map(function($dtString) {
            return date('Y-m-d', VikBooking::getDateTimestamp($dtString));
        }, array_filter([
            $app->input->getString('pickup_date_from', ''),
            $app->input->getString('pickup_date_to', ''),
            $app->input->getString('target_date_from', ''),
            $app->input->getString('target_date_to', ''),
        ]));

        $this->setExportCSVFileName($this->reportName . '-' . implode('_', array_filter($nameValues)) . '.csv');
    }
}

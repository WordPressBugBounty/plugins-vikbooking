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
 * RMS Occupancy Pace report implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
class VikBookingReportRmsOccupancyPace extends VikBookingReport
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
        $this->reportName = JText::translate('VBO_RMS_OCCUPANCY_PACE');
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
                    'MIN(' . $dbo->qn('checkin') . ') AS ' . $dbo->qn('mincheckin'),
                    'MAX(' . $dbo->qn('checkout') . ') AS ' . $dbo->qn('maxcheckout'),
                ])
                ->from($dbo->qn('#__vikbooking_orders'))
                ->where($dbo->qn('status') . ' = ' . $dbo->q('confirmed'))
                ->where($dbo->qn('closure') . ' = 0')
        );
        $data = $dbo->loadAssoc();
        $mincheckindt  = date($df, $data['mincheckin'] ?? time());
        $maxcheckoutdt = date($df, $data['maxcheckout'] ?? time());
        $minyear = date('Y', $data['mincheckin'] ?? time());
        $maxyear = date('Y', $data['maxcheckout'] ?? time());

        // default values
        $compare_to = $app->input->getString('compare_to', '-1 year');

        // comparison options
        $compareOpts = [
            [
                'name'  => JText::translate('VBO_SAMET_LAST_YEAR'),
                'value' => '-1 year',
            ],
            [
                'name'  => JText::sprintf('VBO_SAMET_YEARS_AGO', 2),
                'value' => '-2 years',
            ],
            [
                'name'  => JText::sprintf('VBO_SAMET_YEARS_AGO', 3),
                'value' => '-3 years',
            ],
            [
                'name'  => JText::sprintf('VBO_SAMET_YEARS_AGO', 4),
                'value' => '-4 years',
            ],
            [
                'name'  => JText::sprintf('VBO_SAMET_YEARS_AGO', 5),
                'value' => '-5 years',
            ],
        ];

        // start building filters
        $this->reportFilters = [
            // pickup date
            [
                'label'   => '<label for="pickup_date">' . JText::translate('VBO_PICKUP_DATE') . '</label>',
                'html'    => '<input type="text" id="pickup_date" name="pickup_date" value="' . date($df) . '" class="vbo-report-datepicker vbo-report-datepicker-pickupdt vbo-report-datepicker-pickup-date" />',
                'type'    => 'calendar',
                'name'    => 'pickup_date',
                'default' => date($df),
            ],
            // target date from
            [
                'label'   => '<label for="target_date_from">' . JText::translate('VBO_TARGET_DATE_START') . '</label>',
                'html'    => '<input type="text" id="target_date_from" name="target_date_from" value="' . date(str_replace('d', '01', $df)) . '" class="vbo-report-datepicker vbo-report-datepicker-targetdt vbo-report-datepicker-target-date-from" />',
                'type'    => 'calendar',
                'name'    => 'target_date_from',
                'default' => date(str_replace('d', '01', $df)),
            ],
            // target date to
            [
                'label'   => '<label for="target_date_to">' . JText::translate('VBO_TARGET_DATE_END') . '</label>',
                'html'    => '<input type="text" id="target_date_to" name="target_date_to" value="' . date(str_replace('d', 't', $df)) . '" class="vbo-report-datepicker vbo-report-datepicker-targetdt vbo-report-datepicker-target-date-to" />',
                'type'    => 'calendar',
                'name'    => 'target_date_to',
                'default' => date(str_replace('d', 't', $df)),
            ],
            // compare against
            [
                'label'   => '<label for="compare_to">' . JText::translate('VBO_COMPARE_AGAINST') . '</label>',
                'html'    => '<select id="compare_to" name="compare_to"><option value=""></option>' . implode("\n", array_map(function($compareOpt) use ($compare_to) {
                    return '<option value="' . $compareOpt['value'] . '"' . ($compareOpt['value'] == $compare_to ? ' selected="selected"' : '') . '>' . $compareOpt['name'] . '</option>';
                }, $compareOpts)) . '</select>',
                'type'    => 'select',
                'name'    => 'compare_to',
            ],
            // align weekdays
            [
                'label'   => '<label>' . JText::translate('VBO_ALIGN_WEEKDAYS') . '</label>',
                'html'    => '<span class="vbo-toggle-small">' . $vbo_app->printYesNoButtons('align_wdays', JText::translate('VBYES'), JText::translate('VBNO'), (int) $app->input->getBool('align_wdays', false), 1, 0) . '</span>',
                'type'    => 'checkbox',
                'name'    => 'align_wdays',
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
        $defPickupDt = $app->input->getString('pickup_date') ?: date($df);
        $defTargetFromDt = $app->input->getString('target_date_from') ?: date(str_replace('d', '01', $df));
        $defTargetToDt = $app->input->getString('target_date_to') ?: date(str_replace('d', 't', $df));
        $js = <<<JAVASCRIPT
jQuery(() => {
    jQuery('.vbo-report-datepicker-pickupdt:input').datepicker({
        minDate: '-5Y',
        maxDate: '+0D',
        dateFormat: '$juidf',
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
    jQuery('.vbo-report-datepicker-pickupdt').datepicker('setDate', '$defPickupDt');
    jQuery('.vbo-report-datepicker-target-date-from').datepicker('setDate', '$defTargetFromDt');
    jQuery('.vbo-report-datepicker-target-date-to').datepicker('setDate', '$defTargetToDt');
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

        // environment vars for template
        $rateHistoryTitle = json_encode(JText::translate('VBO_RATE_HISTORY'));
        $nameTitle = json_encode(JText::translate('VBPVIEWROOMONE'));
        $cancText = json_encode(JText::translate('VBANNULLA'));
        $applyText = json_encode(JText::translate('VBAPPLY'));
        $reportAjaxUrl = $this->getAjaxUrl();
        $addFestAjaxUrl = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=add_fest');

        // build the sub-filters template string, inclusive of the necessary script
        $tpl = <<<HTML
        <div class="vbo-report-subfilters-rms">
            <div class="vbo-report-subfilter-months">$selMonths</div>
        </div>
        <script>
            /**
             * @var     HTMLElement
             */
            let rateHistoryModal;

            /**
             * Loads the rate history and appends it to the given element.
             * 
             * @param   HTMLElement     modalBody   The HTML element where the result should be appended.
             * @param   number          listingId   The listing ID for which rate history should be fetched.
             * @param   string          day         The day (night) for loading the rate history.
             * 
             * @return  void
             */
            const vboRenderRateHistory = function(modalBody, listingId, day) {
                // start loading animation
                VBOCore.emitEvent('vbo-rms-occpace-ratehistory-loading');

                // make the request
                VBOCore.doAjax(
                    "$reportAjaxUrl",
                    {
                        call: 'renderRateHistory',
                        call_args: [
                            listingId,
                            day,
                        ],
                    },
                    (resp) => {
                        // stop loading
                        VBOCore.emitEvent('vbo-rms-occpace-ratehistory-loading');

                        try {
                            // decode the response (if needed)
                            let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;

                            if ((modalBody[0] || modalBody).querySelector('.vbo-rms-rate-history-wrap')) {
                                // modal body already has previously loaded contents
                                (modalBody[0] || modalBody).innerHTML = '';
                            }

                            // append response content to modal body
                            modalBody.append(obj_res['html']);

                            if (obj_res.hasOwnProperty('chart') && obj_res['chart']) {
                                let canvas = (modalBody[0] || modalBody).querySelector('canvas');
                                if (canvas) {
                                    let canvasCtx = canvas.getContext('2d');
                                    let chartObj = new Chart(canvasCtx, {
                                        type: 'line',
                                        data: obj_res['chart'],
                                        options: {
                                            responsive: true,
                                            legend: {
                                                display: true,
                                            },
                                            interaction: {
                                                mode: 'index',
                                                intersect: false,
                                            },
                                            scales: {
                                                yAxes: [
                                                    {
                                                        id: 'y',
                                                        position: 'left',
                                                        scaleLabel: {
                                                            display: true,
                                                            labelString: obj_res?.chart?.datasets[0]?.label || '',
                                                            fontColor: obj_res?.chart?.datasets[0]?.borderColor,
                                                        },
                                                    },
                                                    {
                                                        id: 'y1',
                                                        position: 'right',
                                                        scaleLabel: {
                                                            display: true,
                                                            labelString: obj_res?.chart?.datasets[1]?.label || '',
                                                            fontColor: obj_res?.chart?.datasets[1]?.borderColor,
                                                        },
                                                        grid: {
                                                            drawOnChartArea: false,
                                                        },
                                                    },
                                                ],
                                            }
                                        }
                                    });
                                }
                            }
                        } catch (err) {
                            console.error('Error decoding the response', err, resp);
                        }
                    },
                    (error) => {
                        // display error message
                        alert(error.responseText);

                        // stop loading
                        VBOCore.emitEvent('vbo-rms-occpace-ratehistory-loading');
                    }
                );
            };

            VBOCore.DOMLoaded(() => {

                /**
                 * Register click event on months.
                 */
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

                /**
                 * Subscribe to global event for when new rates have been applied.
                 * (i.e. through the widget "bookings_calendar")
                 */
                document.addEventListener('vbo-room-rates-updated', (e) => {
                    if (rateHistoryModal && e?.detail?.room_id && e?.detail?.ymd) {
                        // refresh rate history modal content for the updated day
                        vboRenderRateHistory(rateHistoryModal, e.detail.room_id, e.detail.ymd);
                    }
                });

                /**
                 * Add body click event delegation for elements that may be later added to the DOM.
                 */
                document.body.addEventListener('click', (e) => {
                    if (e.target.matches('.vbo-report-cell-metric') || e.target.closest('.vbo-report-cell-metric')) {
                        // metric clicked
                        const metricEl = !e.target.matches('.vbo-report-cell-metric') ? e.target.closest('.vbo-report-cell-metric') : e.target;
                        const metricType = metricEl.getAttribute('data-type');
                        if (metricType === 'nightly-rate' || metricType === 'vrplus' || metricType === 'vrminus') {
                            // show rates history for the clicked listing and stay date
                            let day = metricEl.getAttribute('data-day');
                            let listingId = metricEl.getAttribute('data-listing-id');

                            // display modal
                            rateHistoryModal = VBOCore.displayModal({
                                suffix:        'rms_occpace_ratehistory',
                                title:         $rateHistoryTitle,
                                lock_scroll:   true,
                                draggable:     true,
                                enlargeable:   true,
                                extra_class:   'vbo-modal-rounded vbo-modal-tall vbo-modal-nofooter vbo-modal-footerpad',
                                loading_event: 'vbo-rms-occpace-ratehistory-loading',
                                dismiss_event: 'vbo-rms-occpace-ratehistory-dismiss',
                            });

                            // load and render rate history
                            vboRenderRateHistory(rateHistoryModal, listingId, day);
                        } else if (metricType === 'abrn') {
                            // show bookings for this day and listing
                            let day = metricEl.getAttribute('data-day');
                            let listingId = metricEl.getAttribute('data-listing-id');
                            // display widget
                            VBOCore.handleDisplayWidgetNotification({
                                widget_id: 'bookings_calendar'
                            }, {
                                offset: day,
                                day: day,
                                id_room: listingId,
                            });
                        }

                        // do not proceed
                        return;
                    }

                    if (e.target.matches('.vbo-panel-tabs-cmd[data-newrate]') || e.target.closest('.vbo-panel-tabs-cmd[data-newrate]')) {
                        // set-new-rate button clicked
                        const btnEl = !e.target.matches('.vbo-panel-tabs-cmd[data-newrate]') ? e.target.closest('.vbo-panel-tabs-cmd[data-newrate]') : e.target;
                        let day = btnEl.getAttribute('data-day');
                        let listingId = btnEl.getAttribute('data-listing-id');
                        let priceId = btnEl.getAttribute('data-price-id');

                        // display widget
                        VBOCore.handleDisplayWidgetNotification({
                            widget_id: 'bookings_calendar'
                        }, {
                            offset: day,
                            day: day,
                            id_room: listingId,
                            id_price: priceId,
                            roomrates: 1,
                        });

                        // do not proceed
                        return;
                    }

                    if (e.target.matches('.vbo-panel-tabs-cmd[data-cmd]') || e.target.closest('.vbo-panel-tabs-cmd[data-cmd]')) {
                        // rate history navigation button clicked
                        const btnEl = !e.target.matches('.vbo-panel-tabs-cmd[data-cmd]') ? e.target.closest('.vbo-panel-tabs-cmd[data-cmd]') : e.target;
                        let day = btnEl.getAttribute('data-day');
                        let listingId = btnEl.getAttribute('data-listing-id');

                        // load and render rate history
                        vboRenderRateHistory(rateHistoryModal, listingId, day);

                        // do not proceed
                        return;
                    }

                    if (e.target.matches('.vbo-panel-tabs-tab[data-tab]') || e.target.closest('.vbo-panel-tabs-tab[data-tab]')) {
                        // panel tab clicked
                        const tabEl = !e.target.matches('.vbo-panel-tabs-tab[data-tab]') ? e.target.closest('.vbo-panel-tabs-tab[data-tab]') : e.target;
                        const panelsTabsWrap = tabEl.closest('.vbo-panel-tabs-wrap');
                        const tabType = tabEl.getAttribute('data-tab');
                        // iterate all panels
                        panelsTabsWrap.querySelectorAll('.vbo-panel-tabs-panel').forEach((panel) => {
                            let panelType = panel.getAttribute('data-panel');
                            if (panelType === tabType) {
                                // show panel
                                panel.style.display = '';
                                tabEl.classList.add('active');
                            } else {
                                // hide panel
                                panel.style.display = 'none';
                                // remove tab active class
                                panelsTabsWrap.querySelector('.vbo-panel-tabs-tab[data-tab="' + panelType + '"]').classList.remove('active');
                            }
                        });

                        // do not proceed
                        return;
                    }

                    if (e.target.matches('.vbo-report-cell-comparison[data-type="abrn"]') || e.target.closest('.vbo-report-cell-comparison[data-type="abrn"]')) {
                        // global rooms occupancy metric clicked
                        const btnEl = !e.target.matches('.vbo-report-cell-comparison[data-type="abrn"]') ? e.target.closest('.vbo-report-cell-comparison[data-type="abrn"]') : e.target;
                        let day = btnEl.getAttribute('data-day');

                        // display widget
                        VBOCore.handleDisplayWidgetNotification({
                            widget_id: 'bookings_calendar'
                        }, {
                            offset: day,
                            day: day,
                        });

                        // do not proceed
                        return;
                    }

                    if (e.target.matches('button[data-event-day]') || e.target.closest('button[data-event-day]')) {
                        // add event button clicked
                        const btnEl = !e.target.matches('button[data-event-day]') ? e.target.closest('button[data-event-day]') : e.target;
                        const day = btnEl.getAttribute('data-event-day');

                        // display dialog modal
                        let dialogBody = document.createElement('div');
                        dialogBody.classList.add('vbo-admin-container', 'vbo-admin-container-full', 'vbo-admin-container-compact');
                        let wrap = document.createElement('div');
                        wrap.classList.add('vbo-params-wrap');
                        let paramContainer = document.createElement('div');
                        paramContainer.classList.add('vbo-param-container');
                        let paramLabel = document.createElement('div');
                        paramLabel.classList.add('vbo-param-label');
                        paramLabel.innerText = $nameTitle;
                        let paramSetting = document.createElement('div');
                        paramSetting.classList.add('vbo-param-setting');
                        let festControl = document.createElement('input');
                        festControl.setAttribute('type', 'text');

                        // build dialog body
                        paramSetting.append(festControl);
                        paramContainer.append(paramLabel);
                        paramContainer.append(paramSetting);
                        wrap.append(paramContainer);
                        dialogBody.append(wrap);

                        // build dialog buttons
                        let dialogCancelBtn = document.createElement('button');
                        dialogCancelBtn.setAttribute('type', 'button');
                        dialogCancelBtn.classList.add('btn');
                        dialogCancelBtn.innerText = $cancText;
                        dialogCancelBtn.addEventListener('click', () => {
                            VBOCore.emitEvent('vbo-rms-occpace-newevent-dismiss');
                        });
                        let dialogApplyBtn = document.createElement('button');
                        dialogApplyBtn.setAttribute('type', 'button');
                        dialogApplyBtn.classList.add('btn', 'btn-success');
                        dialogApplyBtn.innerText = $applyText;
                        dialogApplyBtn.addEventListener('click', () => {
                            if (festControl.value) {
                                // start loading
                                VBOCore.emitEvent('vbo-rms-occpace-newevent-loading');

                                // make the request
                                VBOCore.doAjax(
                                    "$addFestAjaxUrl",
                                    {
                                        dt: day,
                                        name: festControl.value,
                                    },
                                    (resp) => {
                                        // stop loading
                                        VBOCore.emitEvent('vbo-rms-occpace-newevent-loading');

                                        try {
                                            // decode the response (if needed), and append the content to the modal body
                                            let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;

                                            // add the festivity name to the cell that originated the click
                                            let eventEl = document.createElement('div');
                                            eventEl.classList.add('vbo-report-cell-texts');
                                            eventEl.innerText = festControl.value;
                                            btnEl.replaceWith(eventEl);

                                            // dismiss modal
                                            VBOCore.emitEvent('vbo-rms-occpace-newevent-dismiss');
                                        } catch (err) {
                                            console.error('Error decoding the response', err, resp);
                                        }
                                    },
                                    (error) => {
                                        // display error message
                                        alert(error.responseText);

                                        // stop loading
                                        VBOCore.emitEvent('vbo-rms-occpace-newevent-loading');
                                    }
                                );
                            }
                        });

                        // display modal
                        VBOCore.displayModal({
                            suffix:        'rms_occpace_newevent',
                            title:         day,
                            body:          dialogBody,
                            lock_scroll:   true,
                            footer_left:   dialogCancelBtn,
                            footer_right:  dialogApplyBtn,
                            extra_class:   'vbo-modal-rounded vbo-modal-dialog',
                            loading_event: 'vbo-rms-occpace-newevent-loading',
                            dismiss_event: 'vbo-rms-occpace-newevent-dismiss',
                        });

                        // do not proceed
                        return;
                    }
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
        $pickup_date      = $options->get('pickup_date') ?: $app->input->getString('pickup_date') ?: date($df);
        $target_date_from = $options->get('target_date_from') ?: $app->input->getString('target_date_from') ?: date(str_replace('d', '01', $df));
        $target_date_to   = $options->get('target_date_to') ?: $app->input->getString('target_date_to') ?: date(str_replace('d', 't', $df));
        $compare_to       = $options->get('compare_to') ?: $app->input->getString('compare_to') ?: '';
        $interval         = $options->get('interval') ?: $app->input->getString('interval') ?: 'DAY';
        $align_wdays      = (bool) ($options->get('align_wdays') ?: $app->input->getString('align_wdays') ?: 0);
        $listing_ids      = (array) ($options->get('listings') ?: $app->input->getString('listings') ?: []);

        // determine sorting and ordering
        $krsort  = $app->input->getString('krsort') ?: $this->defaultKeySort;
        $krorder = $app->input->getString('krorder') ?: $this->defaultKeyOrder;
        $krorder = $krorder == 'DESC' ? 'DESC' : 'ASC';

        // build comparison data set(s)
        $compareDatas = [];
        if ($compare_to) {
            $compareDatas[] = [
                'to' => $compare_to,
                'align_wdays' => $align_wdays,
            ];
        }

        // tell whether sorting is allowed
        $sortingAllowed = $compareDatas ? 0 : 1;

        // build pace options
        $this->paceOptions = [
            'pickup' => [
                'date' => date('Y-m-d', VikBooking::getDateTimestamp($pickup_date)),
            ],
            'target' => [
                'from' => date('Y-m-d', VikBooking::getDateTimestamp($target_date_from)),
                'to'   => date('Y-m-d', VikBooking::getDateTimestamp($target_date_to)),
            ],
            'compare'    => $compareDatas,
            'interval'   => $interval,
            'listings'   => $listing_ids,
            'sort_rooms' => 'occupancy',
        ];

        try {
            // obtain occupancy pace data
            $paceData = VBORmsPace::getInstance()->getOccupancyData($this->paceOptions);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // obtain the total inventory count
        $totalInventoryCount = $paceData['inventory_count'] ?? 1;

        // obtain the first N listing details
        $maxListingCols = (int) ($options->get('max_listings') ?: (!$listing_ids ? 4 : 10));
        $showcaseListings = array_slice(($paceData['listings'] ?? []), 0, $maxListingCols, true);

        // define the default report columns
        $defaultCols = [
            // line number
            [
                'key' => 'line',
                'label' => 'Line',
                // hide this field in the View and do not export it
                'ignore_view' => 1,
                'ignore_export' => 1,
            ],
            // period start date
            [
                'key' => 'date',
                'label' => JText::translate('VBPVIEWORDERSONE'),
                'sortable' => $sortingAllowed,
            ],
            // events
            [
                'key' => 'events',
                'label' => JText::translate('VBO_EVENTS'),
                'center' => 1,
            ],
            // abrn
            [
                'key' => 'abrn',
                'label' => JText::translate('VBOROOMSOCCUPANCY'),
                'sortable' => $sortingAllowed,
                'center' => 1,
                'tip' => JText::sprintf('VBOREPORTTOTROOMSHELP', $totalInventoryCount),
                'tip_pos' => 'bottom',
            ],
            // sellable (unsold) units
            [
                'key' => 'sellable',
                'label' => JText::translate('VBOROOMSUNSOLD'),
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
            // RevPar
            [
                'key' => 'revpar',
                'label' => JText::translate('VBOREPORTREVENUEREVPAR'),
                'sortable' => $sortingAllowed,
                'center' => 1,
            ],
            // rate variation breakpoint
            [
                'key' => 'ratevr',
                'label' => JText::translate('VBO_VARIATION'),
                'center' => 1,
            ],
            // Gross Booking Revenue
            [
                'key' => 'grossrev',
                'label' => JText::translate('VBO_GROSS_BOOKING_VALUE'),
                'sortable' => $sortingAllowed,
                'center' => 1,
            ],
        ];

        // build final report columns by pushing one column per showcased listing
        $this->cols = [];
        foreach ($defaultCols as $defaultCol) {
            // push column
            $this->cols[] = $defaultCol;

            // check for exact insert-point
            if ($defaultCol['key'] === 'abrn') {
                // iterate all listings to showcase
                foreach ($showcaseListings as $listing_id => $listing_data) {
                    // build "compact" listing name
                    $listingCompactName = $this->compactName($listing_data['name'], 12, '..');
                    $nameCompacted = $listingCompactName != $listing_data['name'];

                    // push listing-level "abrn" column
                    $this->cols[] = [
                        'key' => 'abrn_' . $listing_id,
                        'label' => $listingCompactName,
                        'sortable' => 1,
                        'center' => 1,
                        'tip' => ($nameCompacted ? $listing_data['name'] : null),
                        'tip_pos' => 'bottom',
                        'tip_label' => ($nameCompacted ? strtoupper($listingCompactName) : null),
                    ];
                }
            }
        }

        // count all pace data targets
        $paceTargetsCount = count($paceData['pace']);

        // start global and target line counters
        $lineCounter = 0;
        $lineTargetCounter = 0;

        // start metric indexes and values for the various column keys
        $metricIndexes = [];
        $metricValues  = [];

        // start target names
        $startingTs = strtotime($this->paceOptions['target']['from']);
        $targetNames = [
            // main target
            substr($this->paceOptions['target']['from'], 0, 4),
        ];
        foreach ($this->paceOptions['compare'] as $cmp) {
            // push target comparison year as name
            $targetNames[] = date('Y', strtotime($cmp['to'], $startingTs));
        }

        // build report row classes list
        $rowClasses = [];
        $rowGroupIndex = 0;

        // iterate the main pace data target dates
        foreach (($paceData['pace'][0] ?? []) as $parseIndex => $metrics) {
            // increase main target line counter
            $lineTargetCounter++;

            // loop through all pace data target indexes
            for ($targetIndex = 0; $targetIndex < $paceTargetsCount; $targetIndex++) {
                // prior loading of comparison metrics for main target
                $comparisonResults = [];
                if (!$targetIndex && $paceTargetsCount > 1) {
                    $comparisonResults = [
                        'abrn' => $paceData['pace'][($targetIndex + 1)][$parseIndex]['abrn'] ?? null,
                    ];
                }

                // determine the current period (date) in Y-m-d format
                $currentPeriodYmd = $paceData['pace'][$targetIndex][$parseIndex]['date']->format('Y-m-d');

                // build default row columns to be pushed
                $defaultMetricRowCols = [
                    // line number
                    [
                        'key' => 'line',
                        'value' => $lineCounter,
                        'ignore_view' => 1,
                        'ignore_export' => 1,
                    ],
                    // period start date
                    [
                        'key' => 'date',
                        'callback' => function($dt) use ($targetIndex, $df, $datesep, $wdays_map) {
                            $icn = $targetIndex ? '<i class="' . VikBookingIcons::i('history') . '"></i> ' : '';
                            return $icn . sprintf('%s, %s', $wdays_map[$dt->format('w')] ?? '', $dt->format(str_replace("/", $datesep, $df)));
                        },
                        'callback_export' => function($dt) use ($df, $datesep, $wdays_map) {
                            return sprintf('%s, %s', $wdays_map[$dt->format('w')] ?? '', $dt->format(str_replace("/", $datesep, $df)));
                        },
                        'value' => $paceData['pace'][$targetIndex][$parseIndex]['date'],
                    ],
                    // events
                    [
                        'key' => 'events',
                        'html' => 1,
                        'callback' => function($events) use ($targetIndex, $currentPeriodYmd) {
                            if (!$events) {
                                if ($targetIndex) {
                                    return '';
                                }

                                // add button to create a new event
                                $addEvIcn = '<i class="' . VikBookingIcons::i('plus', 'icn-nomargin') . '"></i>';
                                return <<<HTML
                                <button class="btn vbo-btn-square" data-event-day="$currentPeriodYmd">$addEvIcn</button>
                                HTML;
                            }
                            $evString = implode(', ', array_column($events, 'name'));
                            return <<<HTML
                            <div class="vbo-report-cell-texts"><span>$evString</span></div>
                            HTML;
                        },
                        'callback_export' => function($events) {
                            return $events ? implode(', ', array_column($events, 'name')) : '';
                        },
                        'value' => $paceData['pace'][$targetIndex][$parseIndex]['events'],
                        'center' => 1,
                    ],
                    // abrn
                    [
                        'key' => 'abrn',
                        'html' => 1,
                        'callback' => function($value) use ($targetIndex, $totalInventoryCount, $comparisonResults, $currentPeriodYmd) {
                            $pcentOcc = $value * 100 / ($totalInventoryCount ?: 1);
                            if ($pcentOcc > 60) {
                                $highlightCls = ' label-success';
                            } elseif ($pcentOcc > 20) {
                                $highlightCls = ' label-warning';
                            } else {
                                $highlightCls = ' label-error';
                            }

                            if ($targetIndex) {
                                // comparison row will get no coloring
                                $highlightCls = '';
                            }

                            // build comparison result template
                            $compareTpl = '';
                            if (($comparisonResults['abrn'] ?? null) !== null) {
                                // tell if we are up or down compared to a past year
                                $going_up = $value > (int) $comparisonResults['abrn'];
                                $goingCls = $going_up ? 'up' : 'down';
                                $goingIcn = '<i class="' . VikBookingIcons::i($going_up ? 'arrow-up' : 'arrow-down') . '"></i>';
                                $highlightCls = $going_up ? ' label-success' : ' label-error';

                                // set comparison result template
                                $compareTpl = <<<HTML
                                <span class="vbo-report-cell-compare-result $goingCls">$goingIcn</span>
                                HTML;

                                if ($value == $comparisonResults['abrn']) {
                                    // do not set any comparison result when the result is identical
                                    $compareTpl = '';
                                    $highlightCls = '';
                                }
                            }

                            return <<<HTML
                            <div class="vbo-report-cell-comparison" data-type="abrn" data-day="$currentPeriodYmd">
                                <span class="label$highlightCls">$value</span>
                                $compareTpl
                            </div>
                            HTML;
                        },
                        'callback_export' => function($value) {
                            return $value;
                        },
                        'value' => $paceData['pace'][$targetIndex][$parseIndex]['abrn'],
                        'center' => 1,
                    ],
                    // sellable (unsold) units
                    [
                        'key' => 'sellable',
                        'callback' => function($value) use ($targetIndex) {
                            if (!$value) {
                                return '<span class="label label-success">' . $value . '</span>';
                            }
                            if (!$targetIndex) {
                                return '<span class="label label-warning">' . $value . '</span>';
                            }
                            return '<span class="label">' . $value . '</span>';
                        },
                        'callback_export' => function($value) {
                            return $value;
                        },
                        'value' => $paceData['pace'][$targetIndex][$parseIndex]['sellable'],
                        'center' => 1,
                    ],
                    // adr
                    [
                        'key' => 'adr',
                        'value' => $paceData['pace'][$targetIndex][$parseIndex]['adr'],
                        'center' => 1,
                        'callback' => function($metric) {
                            return VikBooking::formatCurrencyNumber($metric);
                        },
                    ],
                    // RevPar
                    [
                        'key' => 'revpar',
                        'value' => $paceData['pace'][$targetIndex][$parseIndex]['revpar'],
                        'center' => 1,
                        'callback' => function($metric) {
                            return VikBooking::formatCurrencyNumber($metric);
                        },
                    ],
                    // rate variation breakpoint
                    [
                        'key' => 'ratevr',
                        'html' => 1,
                        'callback' => function($rateVrData) use ($listing_id, $currentPeriodYmd) {
                            list($rateVrDt, $rateVrPlus, $rateVrMinus) = $rateVrData;
                            if (!$rateVrPlus && !$rateVrMinus) {
                                return '';
                            }

                            // build variation plus and minus template variables
                            $icnPlusCls = VikBookingIcons::i('plus');
                            $icnMinusCls = VikBookingIcons::i('minus');

                            return <<<HTML
                            <div class="vbo-report-cell-metrics">
                                <div class="vbo-report-cell-metric" data-type="vrplus" data-day="$currentPeriodYmd" data-listing-id="$listing_id">
                                    <span class="label label-success"><i class="$icnPlusCls"></i> $rateVrPlus</span>
                                </div>
                                <div class="vbo-report-cell-metric" data-type="vrminus" data-day="$currentPeriodYmd" data-listing-id="$listing_id">
                                    <span class="label label-error"><i class="$icnMinusCls"></i> $rateVrMinus</span>
                                </div>
                            </div>
                            HTML;
                        },
                        'callback_export' => function($rateVrData) {
                            list($rateVrDt, $rateVrPlus, $rateVrMinus) = $rateVrData;
                            if (!$rateVrPlus && !$rateVrMinus) {
                                return '';
                            }
                            return sprintf('+%d, -%d', $rateVrPlus, $rateVrMinus);
                        },
                        'value' => [
                            $paceData['pace'][$targetIndex][$parseIndex]['ratevrdt'],
                            $paceData['pace'][$targetIndex][$parseIndex]['ratevrplus'],
                            $paceData['pace'][$targetIndex][$parseIndex]['ratevrminus'],
                        ],
                        'center' => 1,
                    ],
                    // Gross Booking Revenue
                    [
                        'key' => 'grossrev',
                        'value' => array_sum((array) ($paceData['pace'][$targetIndex][$parseIndex]['grossrev'] ?? [])),
                        'center' => 1,
                        'callback' => function($metric) {
                            return VikBooking::formatCurrencyNumber($metric);
                        },
                    ],
                ];

                // build final row columns to be pushed
                $metricRowCols = [];
                foreach ($defaultMetricRowCols as $defaultMetricRowCol) {
                    // push column
                    $metricRowCols[] = $defaultMetricRowCol;

                    // check for exact insert-point
                    if ($defaultMetricRowCol['key'] === 'abrn') {
                        // iterate all listings to showcase
                        foreach ($showcaseListings as $listing_id => $listing_data) {
                            // gather listing-level nightly rate and total units
                            $listingNightlyRate = $paceData['pace'][$targetIndex][$parseIndex]['nightlyrates'][$listing_id] ?? null;
                            $listingUnits = $paceData['listings'][$listing_id]['units'] ?? 0;

                            // push listing-level "abrn" column value
                            $metricRowCols[] = [
                                'key' => 'abrn_' . $listing_id,
                                'html' => 1,
                                'callback' => function($value) use ($targetIndex, $listing_id, $currentPeriodYmd, $listingNightlyRate, $listingUnits) {
                                    $pcentOcc = $value * 100 / ($listingUnits ?: 1);
                                    if ($pcentOcc > 60) {
                                        $highlightCls = ' label-success';
                                    } elseif ($pcentOcc > 20) {
                                        $highlightCls = ' label-warning';
                                    } else {
                                        $highlightCls = ' label-error';
                                    }

                                    if ($targetIndex) {
                                        // comparison row will get no coloring
                                        $highlightCls = '';
                                    }

                                    // build nightly rate metric template
                                    $metricTpl = '';
                                    if ($listingNightlyRate) {
                                        $listingNightlyRate = VikBooking::formatCurrencyNumber($listingNightlyRate);
                                        $metricTpl = <<<HTML
                                        <div class="vbo-report-cell-metric" data-type="nightly-rate" data-day="$currentPeriodYmd" data-listing-id="$listing_id">
                                            <span class="label label-info">$listingNightlyRate</span>
                                        </div>
                                        HTML;
                                    } else {
                                        // display an empty box when no rate details available
                                        $metricTpl = <<<HTML
                                        <div class="vbo-report-cell-metric" data-type="empty-nightly-rate">
                                            <span class="label">---</span>
                                        </div>
                                        HTML;
                                    }

                                    return <<<HTML
                                    <div class="vbo-report-cell-metrics">
                                        <div class="vbo-report-cell-metric" data-type="abrn" data-day="$currentPeriodYmd" data-listing-id="$listing_id">
                                            <span class="label$highlightCls">$value</span>
                                        </div>
                                        $metricTpl
                                    </div>
                                    HTML;
                                },
                                'callback_export' => function($value) {
                                    return $value;
                                },
                                'value' => $paceData['pace'][$targetIndex][$parseIndex]['bookedrooms'][$listing_id] ?? 0,
                                'center' => 1,
                            ];
                        }
                    }
                }

                if (!$metricIndexes) {
                    // store the indexes for each metric key
                    $metricIndexes = array_values(array_column(array_filter($metricRowCols, function($colData) {
                        return empty($colData['ignore_view']);
                    }), 'key'));
                }

                // update metric values for current target to be used for the footer calculations
                $metricValues['abrn'][$targetIndex]     = ($metricValues['abrn'][$targetIndex] ?? 0) + $paceData['pace'][$targetIndex][$parseIndex]['abrn'];
                $metricValues['adr'][$targetIndex]      = ($metricValues['adr'][$targetIndex] ?? 0) + $paceData['pace'][$targetIndex][$parseIndex]['adr'];
                $metricValues['revpar'][$targetIndex]   = ($metricValues['revpar'][$targetIndex] ?? 0) + $paceData['pace'][$targetIndex][$parseIndex]['revpar'];
                $metricValues['grossrev'][$targetIndex] = ($metricValues['grossrev'][$targetIndex] ?? []) + (array) ($paceData['pace'][$targetIndex][$parseIndex]['grossrev'] ?? []);

                // push metric row with calculated columns
                $this->rows[] = $metricRowCols;

                // push row group class(es)
                $rowClasses[] = array_values(array_filter([
                    sprintf('vbo-report-row-group-%d', $rowGroupIndex),
                    ($targetIndex ? 'vbo-report-row-comparison' : ''),
                ]));

                // increase counter
                $lineCounter++;
            }

            // update rows group index
            $rowGroupIndex = 1 - $rowGroupIndex;
        }

        // set report row classes
        $this->setReportRowClasses($rowClasses);

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
            // push one single column for "abrn", "adr", "RevPar" and "Gross Booking Revenue"
            [
                'callback' => function($allMetrics) use ($lineTargetCounter, $totalInventoryCount, $targetNames) {
                    // build all metrics template
                    $allMetricsTpl = <<<HTML
                    <div class="vbo-report-footer-rms-list">
                    HTML;

                    // abrn
                    $metrics = $allMetrics['abrn'] ?? [];
                    $blockTitle = JText::translate('VBOROOMSOCCUPANCY');
                    $blockMetricsTpl = '';
                    $targetMetrics = [];
                    foreach ($metrics as $targetIndex => $totalValue) {
                        // calculate metric average value
                        $avgValue = round($totalValue / ($lineTargetCounter ?: 1), 2);

                        // set metric for this target
                        $targetMetrics[$targetIndex] = $avgValue;

                        // calculate average occupancy percent
                        $avgOccPcent = round($avgValue * 100 / ($totalInventoryCount ?: 1), 2);

                        // obtain target name
                        $targetName = $targetNames[$targetIndex] ?? '';

                        // set metric template value
                        $metricCls = !$targetIndex ? 'vbo-report-footer-rms-metric-target' : 'vbo-report-footer-rms-metric-comparison';
                        $blockMetricsTpl .= <<<HTML
                        <div class="vbo-report-footer-rms-metric $metricCls">
                            <div class="vbo-report-footer-rms-metric-year"><span>$targetName:</span></div>
                            <div class="vbo-report-footer-rms-metric-value">$avgValue <span>($avgOccPcent%)</span></div>
                        </div>
                        HTML;
                    }

                    // growth template
                    $growthTpl = '';
                    if (count($targetMetrics) > 1) {
                        // calculate the percent of growth
                        $growthPcent = round(abs(($targetMetrics[0] - $targetMetrics[1]) / ($targetMetrics[1] ?: 1) * 100), 2);
                        $growthCls = $targetMetrics[0] > $targetMetrics[1] ? 'up' : 'down';
                        $growthIcn = '<i class="' . VikBookingIcons::i($targetMetrics[0] > $targetMetrics[1] ? 'arrow-up' : 'arrow-down') . '"></i>';
                        $growthTpl = <<<HTML
                        <div class="vbo-report-footer-rms-growth $growthCls">$growthIcn $growthPcent%</div>
                        HTML;
                    }

                    // set the HTML string
                    $allMetricsTpl .= <<<HTML
                    <div class="vbo-report-footer-rms-wrap">
                        <div class="vbo-report-footer-rms-title">
                            <span>$blockTitle</span>
                            $growthTpl
                        </div>
                        <div class="vbo-report-footer-rms-metrics">$blockMetricsTpl</div>
                    </div>
                    HTML;

                    // adr
                    $metrics = $allMetrics['adr'] ?? [];
                    $blockTitle = JText::translate('VBOREPORTREVENUEADR');
                    $blockMetricsTpl = '';
                    $targetMetrics = [];
                    foreach ($metrics as $targetIndex => $totalValue) {
                        // calculate metric average value
                        $avgValue = round($totalValue / ($lineTargetCounter ?: 1), 2);
                        $avgValueFormat = VikBooking::formatCurrencyNumber($avgValue);

                        // set metric for this target
                        $targetMetrics[$targetIndex] = $avgValue;

                        // obtain target name
                        $targetName = $targetNames[$targetIndex] ?? '';

                        // set metric template value
                        $metricCls = !$targetIndex ? 'vbo-report-footer-rms-metric-target' : 'vbo-report-footer-rms-metric-comparison';
                        $blockMetricsTpl .= <<<HTML
                        <div class="vbo-report-footer-rms-metric $metricCls">
                            <div class="vbo-report-footer-rms-metric-year"><span>$targetName:</span></div>
                            <div class="vbo-report-footer-rms-metric-value">$avgValueFormat</div>
                        </div>
                        HTML;
                    }

                    // growth template
                    $growthTpl = '';
                    if (count($targetMetrics) > 1) {
                        // calculate the percent of growth
                        $growthPcent = round(abs(($targetMetrics[0] - $targetMetrics[1]) / ($targetMetrics[1] ?: 1) * 100), 2);
                        $growthCls = $targetMetrics[0] > $targetMetrics[1] ? 'up' : 'down';
                        $growthIcn = '<i class="' . VikBookingIcons::i($targetMetrics[0] > $targetMetrics[1] ? 'arrow-up' : 'arrow-down') . '"></i>';
                        $growthTpl = <<<HTML
                        <div class="vbo-report-footer-rms-growth $growthCls">$growthIcn $growthPcent%</div>
                        HTML;
                    }

                    // set the HTML string
                    $allMetricsTpl .= <<<HTML
                    <div class="vbo-report-footer-rms-wrap">
                        <div class="vbo-report-footer-rms-title">
                            <span>$blockTitle</span>
                            $growthTpl
                        </div>
                        <div class="vbo-report-footer-rms-metrics">$blockMetricsTpl</div>
                    </div>
                    HTML;

                    // RevPAR
                    $metrics = $allMetrics['revpar'] ?? [];
                    $blockTitle = JText::translate('VBOREPORTREVENUEREVPAR');
                    $blockMetricsTpl = '';
                    $targetMetrics = [];
                    foreach ($metrics as $targetIndex => $totalValue) {
                        // calculate metric average value
                        $avgValue = round($totalValue / ($lineTargetCounter ?: 1), 2);
                        $avgValueFormat = VikBooking::formatCurrencyNumber($avgValue);

                        // set metric for this target
                        $targetMetrics[$targetIndex] = $avgValue;

                        // obtain target name
                        $targetName = $targetNames[$targetIndex] ?? '';

                        // set metric template value
                        $metricCls = !$targetIndex ? 'vbo-report-footer-rms-metric-target' : 'vbo-report-footer-rms-metric-comparison';
                        $blockMetricsTpl .= <<<HTML
                        <div class="vbo-report-footer-rms-metric $metricCls">
                            <div class="vbo-report-footer-rms-metric-year"><span>$targetName:</span></div>
                            <div class="vbo-report-footer-rms-metric-value">$avgValueFormat</div>
                        </div>
                        HTML;
                    }

                    // growth template
                    $growthTpl = '';
                    if (count($targetMetrics) > 1) {
                        // calculate the percent of growth
                        $growthPcent = round(abs(($targetMetrics[0] - $targetMetrics[1]) / ($targetMetrics[1] ?: 1) * 100), 2);
                        $growthCls = $targetMetrics[0] > $targetMetrics[1] ? 'up' : 'down';
                        $growthIcn = '<i class="' . VikBookingIcons::i($targetMetrics[0] > $targetMetrics[1] ? 'arrow-up' : 'arrow-down') . '"></i>';
                        $growthTpl = <<<HTML
                        <div class="vbo-report-footer-rms-growth $growthCls">$growthIcn $growthPcent%</div>
                        HTML;
                    }

                    // set the HTML string
                    $allMetricsTpl .= <<<HTML
                    <div class="vbo-report-footer-rms-wrap">
                        <div class="vbo-report-footer-rms-title">
                            <span>$blockTitle</span>
                            $growthTpl
                        </div>
                        <div class="vbo-report-footer-rms-metrics">$blockMetricsTpl</div>
                    </div>
                    HTML;

                    // gross booking revenue
                    $metrics = $allMetrics['grossrev'] ?? [];
                    $blockTitle = JText::translate('VBO_GROSS_BOOKING_VALUE');
                    $blockMetricsTpl = '';
                    $targetMetrics = [];
                    foreach ($metrics as $targetIndex => $totalAssoc) {
                        // calculate metric total value
                        $metricTotalValue = array_sum((array) $totalAssoc);

                        // set metric for this target
                        $targetMetrics[$targetIndex] = $metricTotalValue;

                        // obtain target name
                        $targetName = $targetNames[$targetIndex] ?? '';

                        // count total value per current target
                        $totalValue = VikBooking::formatCurrencyNumber($metricTotalValue);

                        // set metric template value
                        $metricCls = !$targetIndex ? 'vbo-report-footer-rms-metric-target' : 'vbo-report-footer-rms-metric-comparison';
                        $blockMetricsTpl .= <<<HTML
                        <div class="vbo-report-footer-rms-metric $metricCls">
                            <div class="vbo-report-footer-rms-metric-year"><span>$targetName:</span></div>
                            <div class="vbo-report-footer-rms-metric-value">$totalValue</div>
                        </div>
                        HTML;
                    }

                    // growth template
                    $growthTpl = '';
                    if (count($targetMetrics) > 1) {
                        // calculate the percent of growth
                        $growthPcent = round(abs(($targetMetrics[0] - $targetMetrics[1]) / ($targetMetrics[1] ?: 1) * 100), 2);
                        $growthCls = $targetMetrics[0] > $targetMetrics[1] ? 'up' : 'down';
                        $growthIcn = '<i class="' . VikBookingIcons::i($targetMetrics[0] > $targetMetrics[1] ? 'arrow-up' : 'arrow-down') . '"></i>';
                        $growthTpl = <<<HTML
                        <div class="vbo-report-footer-rms-growth $growthCls">$growthIcn $growthPcent%</div>
                        HTML;
                    }

                    // return the HTML string to build this footer cell
                    $allMetricsTpl .= <<<HTML
                    <div class="vbo-report-footer-rms-wrap">
                        <div class="vbo-report-footer-rms-title">
                            <span>$blockTitle</span>
                            $growthTpl
                        </div>
                        <div class="vbo-report-footer-rms-metrics">$blockMetricsTpl</div>
                    </div>
                    HTML;

                    // close list-wrapper div
                    $allMetricsTpl .= <<<HTML
                    </div>
                    HTML;

                    // return the final HTML string template
                    return $allMetricsTpl;
                },
                'value' => $metricValues,
                'html' => 1,
                'colspan' => $cellsPerRow,
                'ignore_export' => 1,
            ],
        ];

        // process completed with success
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultLayoutType(bool $hasChart = false)
    {
        // always display just the data-sheet by default with no chart
        return 'sheet';
    }

    /**
     * @inheritDoc
     */
    public function getChartTitle()
    {
        $df = $this->getDateFormat();
        $datesep = VikBooking::getDateSeparator();

        $target_title_parts = [
            $this->paceOptions['target']['from'] ?? null,
            $this->paceOptions['target']['to'] ?? null,
        ];

        return JText::translate('VBO_RMS_OCCUPANCY_PACE') .
            ' (' . date(str_replace('/', $datesep, $df), strtotime($this->paceOptions['pickup']['date'])) . ') ' . 
            implode(' - ', array_map(function($dt) use ($df, $datesep) {
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

        // count number of datasets (target + comparison count)
        $datasetsCount = count((array) ($this->paceOptions['compare'] ?? [])) + 1;

        // build target date start datetime object
        $targetStartDt = new DateTime($this->paceOptions['target']['from'], new DateTimeZone(date_default_timezone_get()));

        // build chart labels and dataset values
        $chartLabels = [];
        $chartDatasetValues = [];
        $chartDatasetLabels = [];

        // scan all target date rows to build the labels
        for ($targetIndex = 0; $targetIndex < count($this->rows); $targetIndex += $datasetsCount) {
            // push formatted target date label (second row index)
            $chartLabels[] = $this->rows[$targetIndex][1]['callback']($this->rows[$targetIndex][1]['value']);
        }

        // build dataset containers
        for ($i = 0; $i < $datasetsCount; $i++) {
            // start empty dataset container
            $chartDatasetValues[$i] = [];
        }

        // scan all report rows to build the dataset values
        foreach ($this->rows as $index => $row) {
            // find the apposite dataset index
            $datasetIndex = $index % $datasetsCount;

            // iterate all row columns to find the needed ones
            foreach ($row as $col) {
                if (($col['key'] ?? '') === 'date' && !isset($chartDatasetLabels[$datasetIndex])) {
                    // build dataset label
                    if (!$index) {
                        // first target date
                        $chartDatasetLabels[$datasetIndex] = $col['value']->format('Y');
                    } elseif ($index && isset($chartDatasetLabels[0])) {
                        // comparison date, calculate the years of difference
                        $dateDiff = $targetStartDt->diff($col['value']);
                        $yearsDiff = (int) ($dateDiff->y ?? 0);
                        if (($dateDiff->m ?? 0) > 10) {
                            $yearsDiff += 1;
                        }
                        $chartDatasetLabels[$datasetIndex] = $yearsDiff === 1 ? JText::translate('VBO_SAMET_LAST_YEAR') : JText::sprintf('VBO_SAMET_YEARS_AGO', $yearsDiff);
                    }

                    // scan next
                    continue;
                }
                if (($col['key'] ?? '') === 'abrn') {
                    // push "abrn" metric as current dataset value
                    $chartDatasetValues[$datasetIndex][] = $col['value'];
                    break;
                }
            }

            if (!($chartDatasetLabels[$datasetIndex] ?? null)) {
                // prevent empty dataset labels
                $chartDatasetLabels[$datasetIndex] = 'date';
            }
        }

        // the canvas element ID and tag
        $canvas_id   = 'vbo-report-occupancy-pace-chart-canvas-' . uniqid();
        $canvas_html = '<canvas id="' . $canvas_id . '" class="vbo-report-chart-canvas"></canvas>';

        if (empty($this->chartScript)) {
            // prepare JSON variables
            $chartLabelsJSON = json_encode($chartLabels);
            $chartDatasetValuesJSON = json_encode($chartDatasetValues);
            $chartDatasetLabelsJSON = json_encode($chartDatasetLabels);

            // prepare the necessary script to render the Chart
            $this->chartScript = <<<JAVASCRIPT
            VBOCore.DOMLoaded(() => {
                const vboReportCanvas = document.getElementById('$canvas_id');
                const vboReportCtx = vboReportCanvas.getContext('2d');
                const vboReportData = {
                    labels: $chartLabelsJSON,
                    datasets: [],
                };
                const vboReportDatasetValues = $chartDatasetValuesJSON;
                const vboReportDatasetLabels = $chartDatasetLabelsJSON;
                vboReportDatasetValues.forEach((datasetData, index) => {
                    let borderColor = 'rgba(255, 178, 102, 1)';
                    let backgroundColor = 'rgba(255, 178, 102, 0.2)';
                    if (!index) {
                        // target dates
                        borderColor = 'rgba(30, 144, 255, 1)';
                        backgroundColor = 'rgba(30, 144, 255, 0.2)';
                    } else if (!index) {
                        // more than one comparison value
                        borderColor = 'rgba(255, 255, 153, 1)';
                        backgroundColor = 'rgba(255, 255, 153, 0.2)';
                    }
                    vboReportData.datasets.push({
                        label: vboReportDatasetLabels[index] || '',
                        data: datasetData,
                        borderColor: borderColor,
                        backgroundColor: backgroundColor,
                    });
                });
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
     * Renders the rate history for a given listing and date.
     * 
     * @param   int     $listingId  The VikBooking listing ID.
     * @param   string  $night      The targeted night of stay (Y-m-d).
     * 
     * @return  array
     * 
     * @throws  Exception
     */
    public function renderRateHistory(int $listingId, string $night)
    {
        // default environment values
        $df = $this->getDateFormat();
        $datesep = VikBooking::getDateSeparator();
        $currency_symb = VikBooking::getCurrencySymb();
        $todayDt = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone(date_default_timezone_get()));

        // validate arguments
        $listingDetails = VikBooking::getRoomInfo($listingId);
        $stayTs = VikBooking::getDateTimestamp($night);

        if (!$listingDetails) {
            throw new Exception('Invalid listing ID.', 404);
        }

        if (!$stayTs) {
            throw new Exception('Invalid target stay date.', 400);
        }

        // listing full name and stay date
        $listingName = $listingDetails['name'];
        $stayDate = date(str_replace('/', $datesep, $df), $stayTs);
        $stayDateDt = new DateTime(date('Y-m-d H:i:s', $stayTs), new DateTimeZone(date_default_timezone_get()));
        $stayInfo = getdate($stayTs);
        $stayDayYmd = date('Y-m-d', $stayTs);
        $prevDayYmd = date('Y-m-d', strtotime('-1 day', $stayTs));
        $nextDayYmd = date('Y-m-d', strtotime('+1 day', $stayTs));
        $isPastStay = $stayTs < strtotime('00:00:00');

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

        // env
        $targetTitle = JText::translate('VBO_TARGET_DATE');
        $dtTitle = JText::translate('VBPVIEWORDERSONE');
        $rateTitle = JText::translate('VBEDITORDERSEVEN');
        $durationTitle = JText::translate('VBO_DURATION');
        $variationTitle = JText::translate('VBO_VARIATION');
        $daysTitle = strtolower(JText::translate('VBCONFIGSEARCHPMAXDATEDAYS'));
        $dayTitle = strtolower(JText::translate('VBODAY'));
        $tabTitleList = JText::translate('VBO_LIST');
        $tabTitleChart = JText::translate('VBOCHARTONLY');
        $prevTitle = JText::translate('VBO_PREV');
        $nextTitle = JText::translate('VBO_NEXT');
        $newRateTitle = ucfirst(strtolower(JText::translate('VBRATESOVWSETNEWRATE')));
        $listingIcn = '<i class="' . VikBookingIcons::i('home') . '"></i>';
        $clockIcn = '<i class="' . VikBookingIcons::i('clock') . '"></i>';
        $chartIcn = '<i class="' . VikBookingIcons::i('chart-line') . '"></i>';
        $plusIcn = '<i class="' . VikBookingIcons::i('plus') . '"></i>';
        $minusIcn = '<i class="' . VikBookingIcons::i('minus') . '"></i>';
        $prevIcn = '<i class="' . VikBookingIcons::i('chevron-left') . '"></i>';
        $nextIcn = '<i class="' . VikBookingIcons::i('chevron-right') . '"></i>';
        $ratesIcn = '<i class="' . VikBookingIcons::i('tag') . '"></i>';
        $wdayName = $wdays_map[$stayInfo['wday']];

        // fetch all confirmed and cancelled bookings intersecting the targeted stay date
        $bookings = VBORmsPace::getInstance()->getIntersectingBookings([
            'target' => [
                'from_ts' => $stayTs,
                'to_ts'   => strtotime('23:59:59', $stayTs),
            ],
            'listings' => [$listingDetails['id']],
            'cancellation_dt' => 1,
            'tariff_taxes' => 1,
        ]);

        // construct the RMS rates registry object for the targeted stay date
        $ratesRegistry = (new VBORmsRatesRegistry([
            'target' => [
                'from_ts' => $stayTs,
                'to_ts'   => $stayTs,
            ],
            'listings' => [$listingDetails['id']],
        ]))->preloadFlowRecords();

        // build rate history intervals for all pricing modifications
        $rateHistoryIntervals = [];

        // obtain main rate plan ID under evaluation
        $mainRatePlanId = $ratesRegistry->getMainRatePlanId();

        // obtain flow records data
        $flowRecords = $ratesRegistry->getFlowRecords($ascending = true);

        // iterate flow records
        $flowData = [];
        foreach ($flowRecords as $flowRecord) {
            // pricing modification record date
            $creationDt = new DateTime($flowRecord['created_on'], new DateTimeZone(date_default_timezone_get()));

            if (!$flowData) {
                // start container
                $flowData = [
                    'created_on'   => (clone $creationDt),
                    'lasted_until' => null,
                    'nightly_fee'  => $flowRecord['nightly_fee'],
                ];

                // go next
                continue;
            }

            if ($flowRecord['nightly_fee'] != $flowData['nightly_fee']) {
                // pricing modification will adjust the "lasted until" date in previous node
                $prevCreationDt = clone $creationDt;
                $prevCreationDt->modify('-1 second');
                $flowData['lasted_until'] = $prevCreationDt;

                // push rate history interval
                $rateHistoryIntervals[] = $flowData;

                // start a new container
                $flowData = [
                    'created_on'   => (clone $creationDt),
                    'lasted_until' => null,
                    'nightly_fee'  => $flowRecord['nightly_fee'],
                ];
            }
        }

        if ($flowRecords && empty($flowData['lasted_until'])) {
            // push last, even first-and-only, rate history interval
            $rateHistoryIntervals[] = $flowData;
        }

        // build history template
        $historyTpl = '';

        // scan all pricing modification intervals
        foreach ($rateHistoryIntervals as $flowIndex => $pricingModification) {
            // obtain record details
            $creationYmd = $pricingModification['created_on']->format(str_replace('/', $datesep, $df) . ' H:i:s');
            $nightlyRate = VikBooking::formatCurrencyNumber($pricingModification['nightly_fee']);

            // start rate duration and booking variation metrics
            $durationDays      = '---';
            $variationBookings = '---';

            // determine the "lasted until" date
            if ($pricingModification['lasted_until']) {
                // known end date
                $lastedUntilDt = $pricingModification['lasted_until'];
            } else {
                // last rate applied to target date
                $lastedUntilDt = $stayDateDt < $todayDt ? $stayDateDt : $todayDt;
            }

            // calculate rate duration
            $datesDiff = $lastedUntilDt->diff($pricingModification['created_on']);
            if ($datesDiff->days) {
                // duration is at least one day
                $durationDays = sprintf('%d %s', $datesDiff->days, ($datesDiff->days == 1 ? $dayTitle : $daysTitle));
            } else {
                // duration is just a few hours/minutes
                $durationDays = sprintf('%d %s', 0, JText::translate('VBTRKDIFFSECS'));
                $durationHours = $datesDiff->h;
                $durationMinutes = $datesDiff->i;
                $durationSeconds = $datesDiff->s;
                if ($durationHours) {
                    $durationDays = sprintf('%d %s', $durationHours, ($durationHours == 1 ? JText::translate('VBO_HOUR') : JText::translate('VBCONFIGONETENEIGHT')));
                } elseif ($durationMinutes) {
                    $durationDays = sprintf('%d %s', $durationMinutes, ($durationMinutes == 1 ? JText::translate('VBO_MINUTE') : JText::translate('VBTRKDIFFMINS')));
                } elseif ($durationSeconds) {
                    $durationDays = sprintf('%d %s', $durationSeconds, ($durationSeconds == 1 ? JText::translate('VBO_SECOND') : JText::translate('VBTRKDIFFSECS')));
                }
            }

            // calculate booking variations
            $variationData = [
                'nb' => 0,
                'cb' => 0,
            ];
            $variationFromTs = $pricingModification['created_on']->format('U');
            $variationToTs = $pricingModification['lasted_until'] ? $pricingModification['lasted_until']->format('U') : null;
            // scan all bookings intersecting the stay date
            foreach ($bookings as $booking) {
                if (($variationToTs !== null && $booking['ts'] >= $variationFromTs && $booking['ts'] < $variationToTs) || (!$variationToTs && $booking['ts'] >= $variationFromTs)) {
                    // there was a new confirmed booking
                    $variationData['nb']++;
                }
                if (!empty($booking['cancellation_ts'])) {
                    if (($variationToTs !== null && $booking['cancellation_ts'] >= $variationFromTs && $booking['cancellation_ts'] < $variationToTs) || (!$variationToTs && $booking['cancellation_ts'] >= $variationFromTs)) {
                        // there was a booking cancellation
                        $variationData['cb']++;
                    }
                }
            }

            if ($variationData['nb'] || $variationData['cb']) {
                // build variation bookings template
                $variationNb = $variationData['nb'];
                $variationCb = $variationData['cb'];
                $variationBookings = <<<HTML
                <span class="label label-success" data-type="vrplus">$plusIcn $variationNb</span>
                <span class="label label-error" data-type="vrminus">$minusIcn $variationCb</span>
                HTML;
            }

            // set history record
            $historyTpl .= <<<HTML
            <div class="vbo-rms-rate-history-record">
                <span class="vbo-rms-rate-history-record-dt">
                    <span class="vbo-rms-rate-history-record-title">$dtTitle</span>
                    <span class="vbo-rms-rate-history-record-value">$clockIcn $creationYmd</span>
                </span>
                <span class="vbo-rms-rate-history-record-duration">
                    <span class="vbo-rms-rate-history-record-title">$durationTitle</span>
                    <span class="vbo-rms-rate-history-record-value">$durationDays</span>
                </span>
                <span class="vbo-rms-rate-history-record-variation">
                    <span class="vbo-rms-rate-history-record-title">$variationTitle</span>
                    <span class="vbo-rms-rate-history-record-value">$variationBookings</span>
                </span>
                <span class="vbo-rms-rate-history-record-rate">
                    <span class="vbo-rms-rate-history-record-title">$rateTitle</span>
                    <span class="vbo-rms-rate-history-record-value">$nightlyRate</span>
                </span>
            </div>
            HTML;
        }

        if (!$rateHistoryIntervals) {
            // add message that no rate history records were found
            $historyTpl = '<p class="warn">' . JText::translate('VBO_NO_RECORDS_FOUND') . '</p>';
        }

        // build chart data (if any), tabs and panels template strings
        $chartData = [];
        $tabChartTpl = '';
        $panelChartTpl = '';
        if (count($rateHistoryIntervals) > 1) {
            // chart is available only if we have at least one rate modification record
            $tabChartTpl = <<<HTML
            <div class="vbo-panel-tabs-tab" data-tab="chart"><span>$chartIcn $tabTitleChart</span></div>
            HTML;

            $panelChartTpl = <<<HTML
            <div class="vbo-panel-tabs-panel vbo-rms-rate-history-chart" data-panel="chart" style="display: none;">
                <canvas class="vbo-report-chart-inmodal vbo-rms-rate-history-chart-canvas"></canvas>
            </div>
            HTML;

            // build chart data for rendering the chart
            $chartData = [
                'labels' => [],
                'datasets' => [
                    // push dataset for "rate"
                    [
                        'label' => $rateTitle,
                        'data' => [],
                        'borderColor' => 'rgba(30, 144, 255, 1)',
                        'backgroundColor' => 'rgba(30, 144, 255, 0)',
                        'yAxisID' => 'y',
                        'pointRadius' => 0,
                        'pointHoverRadius' => 0,
                    ],
                    // push dataset for "bookings"
                    [
                        'label' => JText::translate('VBMENUTHREE'),
                        'data' => [],
                        'borderColor' => 'rgba(0, 100, 0, 1)',
                        'backgroundColor' => 'rgba(0, 100, 0, 0)',
                        'yAxisID' => 'y1',
                        'pointRadius' => 0,
                        'pointHoverRadius' => 0,
                    ],
                ],
            ];

            // construct the first and last dates
            $firstIntervalDt = clone $rateHistoryIntervals[0]['created_on'];
            if ($rateHistoryIntervals[count($rateHistoryIntervals) - 1]['lasted_until']) {
                // known end date
                $lastIntervalDt = clone $pricingModification['lasted_until'];
            } else {
                // calculate end date
                $lastIntervalDt = clone ($stayDateDt < $todayDt ? $stayDateDt : $todayDt);
            }

            // obtain iterable date period from first and last interval dates
            $intervalPeriod = new DatePeriod(
                $firstIntervalDt,
                new DateInterval('P1D'),
                $lastIntervalDt
            );

            // set chart data
            foreach ($intervalPeriod as $periodDt) {
                // push chart label (x-axis)
                $chartData['labels'][] = $periodDt->format(str_replace('/', $datesep, $df));

                // obtain start and end timestamps for the current day
                $periodTsFrom = strtotime('00:00:00', $periodDt->format('U'));
                $periodTsTo   = strtotime('23:59:59', $periodDt->format('U'));

                // get the rate as of the current period and push it to the apposite dataset
                $periodRate = 0;
                foreach ($rateHistoryIntervals as $pricingModification) {
                    if ($periodDt >= $pricingModification['created_on'] && (empty($pricingModification['lasted_until']) || $periodDt < $pricingModification['lasted_until'])) {
                        $periodRate = $pricingModification['nightly_fee'];
                        break;
                    }
                }
                $chartData['datasets'][0]['data'][] = $periodRate;

                // iterate all bookings to count the "OTB" as of the current period
                $otbCount = 0;
                foreach ($bookings as $booking) {
                    if ($booking['ts'] >= $periodTsFrom && $booking['ts'] <= $periodTsTo) {
                        // new booking
                        $otbCount++;
                    }
                    if (!empty($booking['cancellation_ts']) && $booking['cancellation_ts'] >= $periodTsFrom && $booking['cancellation_ts'] <= $periodTsTo) {
                        // booking cancellation
                        $otbCount--;
                    }
                }

                // push OTB value to apposite dataset
                $chartData['datasets'][1]['data'][] = $otbCount;
            }
        }

        // build set-new-rate template
        $newRateTpl = '';
        if (!$isPastStay) {
            $newRateTpl = <<<HTML
            <div class="vbo-panel-tabs-cmd" data-newrate="1" data-day="$stayDayYmd" data-listing-id="$listingId" data-price-id="$mainRatePlanId"><span>$ratesIcn $newRateTitle</span></div>
            HTML;
        }

        // build template for rendering
        $tpl = <<<HTML
        <div class="vbo-rms-rate-history-wrap">
            <div class="vbo-rms-rate-history-details">
                <div class="vbo-rms-rate-history-listing">$listingIcn $listingName</div>
                <div class="vbo-rms-rate-history-target">
                    <span class="vbo-rms-rate-history-target-title">$targetTitle</span>
                    <span class="vbo-rms-rate-history-target-value">$wdayName, $stayDate</span>
                </div>
            </div>
            <div class="vbo-panel-tabs-wrap">
                <div class="vbo-panel-tabs-tabs">
                    <div class="vbo-panel-tabs-tab active" data-tab="list"><span>$clockIcn $tabTitleList</span></div>
                    $tabChartTpl
                    <div class="vbo-panel-tabs-right">
                        $newRateTpl
                        <div class="vbo-panel-tabs-cmd" data-cmd="prev" data-day="$prevDayYmd" data-listing-id="$listingId"><span>$prevIcn $prevTitle</span></div>
                        <div class="vbo-panel-tabs-cmd" data-cmd="next" data-day="$nextDayYmd" data-listing-id="$listingId"><span>$nextTitle $nextIcn</span></div>
                    </div>
                </div>
                <div class="vbo-panel-tabs-panels">
                    <div class="vbo-panel-tabs-panel vbo-rms-rate-history-list" data-panel="list">$historyTpl</div>
                    $panelChartTpl
                </div>
            </div>
        </div>
        HTML;

        return [
            'html'  => $tpl,
            'chart' => $chartData ?: null,
        ];
    }

    /**
     * Given a name, usually a listing name, compacts it into a code.
     * 
     * @param   string  $name    The full (listing) name to compact.
     * @param   ?int    $length  Optional string length to obtain.
     * @param   ?string $concat  Optional string to contact when shortening.
     * 
     * @return  string           The compacted name.
     */
    protected function compactName(string $name, ?int $length = null, ?string $concat = null)
    {
        // strip vowels and white spaces
        $name = preg_replace('/[a|e|i|o|u|y|\s]/i', '', $name);

        if (intval($length) > 0 && strlen($name) > $length) {
            if (function_exists('mb_substr')) {
                $name = mb_substr($name, 0, $length, 'UTF-8');
            } else {
                $name = substr($name, 0, $length);
            }
            if ($concat) {
                $name .= $concat;
            }
        }

        return $name;
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
            $app->input->getString('pickup_date', ''),
            $app->input->getString('target_date_from', ''),
            $app->input->getString('target_date_to', ''),
        ]));

        $this->setExportCSVFileName($this->reportName . '-' . implode('_', array_filter($nameValues)) . '.csv');
    }
}

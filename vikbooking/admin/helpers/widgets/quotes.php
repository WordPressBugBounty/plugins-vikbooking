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
 * Class handler for admin widget "quotes".
 * 
 * @since   1.18.8 (J) - 1.8.8 (WP)
 */
class VikBookingAdminWidgetQuotes extends VikBookingAdminWidget
{
    /**
     * The instance counter of this widget.
     *
     * @var     int
     */
    protected static $instance_counter = -1;

    /**
     * The number of records to show per page.
     *
     * @var     int
     */
    protected $records_per_page = 6;

    /**
     * The total number of skeleton loading elements.
     *
     * @var     int
     */
    protected $tot_skeletons = 4;

    /**
     * The distance threshold in pixels between the current scroll
     * position and the end of the list for triggering the loading
     * of a next page within an infinite scroll mechanism.
     *
     * @var     int
     */
    protected $px_distance_threshold = 140;

    /**
     * Class constructor will define the widget name and identifier.
     */
    public function __construct()
    {
        // call parent constructor
        parent::__construct();

        $this->widgetName = JText::translate('VBO_QUOTES');
        $this->widgetDescr = JText::translate('VBO_W_QUOTES_DESCR');
        $this->widgetId = basename(__FILE__, '.php');

        $this->widgetIcon = '<i class="' . VikBookingIcons::i('file-alt') . '"></i>';
        $this->widgetStyleName = 'orange';
    }

    /**
     * Custom method for this widget only to load the next page of quote records.
     * The method is called by the admin controller through an AJAX request.
     * The visibility should be public, it should not exit the process, and
     * any content sent to output will be returned to the AJAX response.
     * In this case we return an array because this method requires "return":1.
     * 
     * @return  array
     */
    public function loadNextQuotes()
    {
        $input = JFactory::getApplication()->input;

        $wrapper  = $input->getString('wrapper', '');
        $unsent   = $input->getBool('unsent', false);
        $page_num = $input->getUInt('page_num', 1);
        $page_num = $page_num ?: 1;

        // access the quote model
        $quoteModel = VBOMvcModel::getInstance('quote');

        // build query filters
        $filters = [];

        if ($unsent) {
            // filter by those quotes that were not sent before
            $filters['sent'] = [
                [
                    'operand' => '=',
                    'value'   => '0',
                ],
            ];
        }

        // determine the query limit start
        $lim_start = ($page_num - 1) * $this->records_per_page;

        // get and build the latest quotes
        $html_content = $this->buildQuotesHTML(
            $quoteModel->loadBookingRecords($lim_start, $this->records_per_page, ['filters' => $filters]),
            ($page_num - 1)
        );

        // return an associative array of values
        return [
            'html'        => $html_content,
            'page_number' => $page_num,
            'pages_count' => ceil($quoteModel->countRecordsFound() / $this->records_per_page),
        ];
    }

    /**
     * Main method to invoke the widget.
     * 
     * @param   ?VBOMultitaskData   $data
     * 
     * @return  void
     */
    public function render(?VBOMultitaskData $data = null)
    {
        // increase widget's instance counter
        static::$instance_counter++;

        // check whether the widget is being rendered via AJAX
        $is_ajax = $this->isAjaxRendering();

        // generate a unique ID for the wrapper instance
        $wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
        $wrapper_id = 'vbo-widget-quotes-' . $wrapper_instance;

        // check permissions
        $vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
        if (!$vbo_auth_bookings) {
            // permissions are not met
            return;
        }

        // check multitask data
        $js_modal_id        = '';
        $is_modal_rendering = false;
        if ($data) {
            // access Multitask data
            $is_modal_rendering = $data->isModalRendering();
            if ($is_modal_rendering) {
                // get modal JS identifier
                $js_modal_id = $data->getModalJsIdentifier();
            }
        }

        // check if a booking ID was requested for loading (ignore multi-task data)
        $bookingId = $this->options()->fetchBookingId();

        // access the quote model
        $quoteModel = VBOMvcModel::getInstance('quote');

        // start quotes list
        $quotes = [];

        // load all quotes or the one assigned to the given booking ID
        if ($bookingId) {
            // make sure the booking exists
            $bookingRecord = VikBooking::getBookingInfoFromID((int) $bookingId);
            if (!empty($bookingRecord['idquote'])) {
                // load the involved quote record
                $quotes = $quoteModel->loadBookingRecords(0, 1, [
                    'filters' => [
                        'id' => (int) $bookingRecord['idquote'],
                    ],
                ]);
            }
        } else {
            // load latest quotes by default
            $quotes = $quoteModel->loadBookingRecords(0, $this->records_per_page);
        }

        // immediately count the number of pages to show all quotes
        $pages_count = ceil($quoteModel->countRecordsFound() / $this->records_per_page);

        ?>
        <div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" data-instance="<?php echo $wrapper_instance; ?>">
            <div class="vbo-admin-widget-head">
                <div class="vbo-admin-widget-head-inline">
                    <h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
                </div>
            </div>
            <div class="vbo-w-quote-wrapper">
                <div class="vbo-w-quote-items-list" data-page-number="1" data-pages-count="<?php echo $pages_count; ?>">
                <?php
                // output all quotes
                echo $this->buildQuotesHTML($quotes);

                // check if we are displaying one quote from the requested booking ID
                if ($bookingId && count($quotes) === 1) {
                    ?>
                    <div class="vbo-w-list-back">
                        <span><?php VikBookingIcons::e('arrow-left'); ?> <?php echo JText::translate('VBO_SEE_ALL'); ?></span>
                    </div>
                    <?php
                }

                // check if we have no results
                if (!$quotes) {
                    ?>
                    <div class="vbo-widget-quotes-loadmore-info">
                        <p><?php echo JText::translate('VBO_NO_RECORDS_FOUND'); ?></p>
                    </div>
                    <?php
                }
                ?>
                </div>
                <div class="vbo-widget-quotes-loadmore-hidden" style="display: none;">
                    <button type="button" class="btn vbo-widget-quotes-loadmore-manual"><?php echo JText::translate('VBO_LOAD_MORE'); ?> <?php VikBookingIcons::e('chevron-right', 'icn-nomargin'); ?></button>
                </div>
            </div>
            <div class="vbo-w-quote-helper-wrap" style="display: none;">
                <div class="vbo-w-quote-send-tplmessage">
                    <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
                        <div class="vbo-params-wrap">
                            <div class="vbo-params-container">
                                <div class="vbo-params-block">
                                    <div class="vbo-param-container">
                                        <div class="vbo-param-label"><?php echo JText::translate('VBO_MESSAGE_TEMPLATE'); ?></div>
                                        <div class="vbo-param-setting">
                                            <select data-field="send-ma-tpl"></select>
                                        </div>
                                    </div>
                                    <div class="vbo-param-container">
                                        <div class="vbo-param-label"><?php echo JText::translate('VBOPREVIEW'); ?></div>
                                        <div class="vbo-param-setting">
                                            <div class="vbo-ma-send-tplmessage-preview"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        if (static::$instance_counter === 0 || $is_ajax) {
            /**
             * Print the JS code only once for all instances of this widget.
             */
            ?>
        <script type="text/javascript">

            /**
             * Returns the skeletons loading HTML.
             */
            function vboWidgetQuotesGetSkeletons() {
                var skeletons = '<div class="vbo-dashboard-guests-latest vbo-widget-quotes-skeletons">' + "\n";

                for (var i = 0; i < <?php echo $this->tot_skeletons; ?>; i++) {
                    skeletons += '<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">' + "\n";
                    skeletons += '  <div class="vbo-dashboard-guest-activity-avatar">' + "\n";
                    skeletons += '      <div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>' + "\n";
                    skeletons += '  </div>' + "\n";
                    skeletons += '  <div class="vbo-dashboard-guest-activity-content">' + "\n";
                    skeletons += '      <div class="vbo-dashboard-guest-activity-content-head">' + "\n";
                    skeletons += '          <div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>' + "\n";
                    skeletons += '      </div>' + "\n";
                    skeletons += '      <div class="vbo-dashboard-guest-activity-content-subhead">' + "\n";
                    skeletons += '          <div class="vbo-skeleton-loading vbo-skeleton-loading-subtitle"></div>' + "\n";
                    skeletons += '      </div>' + "\n";
                    skeletons += '      <div class="vbo-dashboard-guest-activity-content-info-msg">' + "\n";
                    skeletons += '          <div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>' + "\n";
                    skeletons += '      </div>' + "\n";
                    skeletons += '  </div>' + "\n";
                    skeletons += '</div>' + "\n";
                }

                skeletons += '</div>' + "\n";

                return skeletons;
            }

            /**
             * Loads the next page of records.
             */
            function vboWidgetQuotesLoadNextPage(wrapper, reload) {
                const recordsList = document
                    .querySelector('#' + wrapper)
                    ?.querySelector('.vbo-w-quote-items-list');

                if (!recordsList) {
                    throw new Error('Could not find records list element');
                }

                // ensure we've got other pages to load
                let pageNumber = parseInt(recordsList.getAttribute('data-page-number')) || 1;
                let pagesCount = parseInt(recordsList.getAttribute('data-pages-count')) || 1;

                if (reload) {
                    // force the page to reload from 0
                    pageNumber = -1;
                    pagesCount = pageNumber + 2;
                }

                if (pageNumber >= pagesCount) {
                    // no more pages available, abort
                    return;
                }

                // load the next page of records (or reload them from start)

                // append loading skeletons
                recordsList
                    .insertAdjacentHTML('beforeend', vboWidgetQuotesGetSkeletons());

                // the widget method to call
                let call_method = 'loadNextQuotes';

                // make a request to load the next page of records
                VBOCore.doAjax(
                    "<?php echo $this->getExecWidgetAjaxUri(); ?>",
                    {
                        widget_id: "<?php echo $this->getIdentifier(); ?>",
                        call:      call_method,
                        return:    1,
                        page_num:  parseInt(pageNumber) + 1,
                        wrapper:   wrapper,
                        tmpl:      "component"
                    },
                    (response) => {
                        try {
                            if (!response.hasOwnProperty(call_method)) {
                                console.error('Unexpected JSON response', response);
                                return false;
                            }

                            // remove loading skeletons
                            recordsList
                                .querySelector('.vbo-widget-quotes-skeletons')
                                .remove();

                            // append HTML with the new records and set page infos
                            recordsList
                                .setAttribute('data-page-number', response[call_method]['page_number']);
                            recordsList
                                .setAttribute('data-pages-count', response[call_method]['pages_count']);
                            recordsList
                                .insertAdjacentHTML('beforeend', response[call_method]['html']);

                            // turn custom property off for the page loading
                            recordsList.pageLoading = false;

                            if (reload) {
                                // set up infinite scroll loading
                                vboWidgetQuotesSetupInfiniteScroll(wrapper);
                            }

                            // set up records click listeners for the new records read
                            vboWidgetQuotesRegisterClickListeners(wrapper);
                        } catch(err) {
                            console.error('could not parse JSON response', err, response);
                        }
                    },
                    (error) => {
                        // display the error
                        alert(error.responseText);

                        // turn custom property off for the page loading
                        recordsList.pageLoading = false;

                        // remove loading skeletons
                        recordsList
                            .querySelector('.vbo-widget-quotes-skeletons')
                            .remove();
                    }
                );
            }

            /**
             * Setups the infinite scroll loading.
             */
            function vboWidgetQuotesSetupInfiniteScroll(wrapper) {
                const recordsList = document
                    .querySelector('#' + wrapper)
                    .querySelector('.vbo-w-quote-items-list');

                if (!recordsList) {
                    throw new Error('Could not find quote list element');
                }

                // ensure we've got more pages to load
                let pageNumber = parseInt(recordsList.getAttribute('data-page-number')) || 1;
                let pagesCount = parseInt(recordsList.getAttribute('data-pages-count')) || 1;

                if (pageNumber >= pagesCount) {
                    // no pagination needed
                    return;
                }

                // get wrapper dimensions
                let listViewHeight = recordsList.offsetHeight;
                let listGlobHeight = recordsList.scrollHeight;
                let listScrollTop  = recordsList.scrollTop;

                if (listViewHeight >= listGlobHeight) {
                    // no scrolling detected, show manual loading
                    document
                        .querySelector('#' + wrapper)
                        .querySelector('.vbo-widget-quotes-loadmore-hidden')
                        .style
                        .display = 'block';

                    return;
                }

                // inject custom property to identify the wrapper ID
                recordsList.wrapperId = wrapper;

                // register infinite scroll event handler
                recordsList
                    .addEventListener('scroll', vboWidgetQuotesInfiniteScroll);
            }

            /**
             * Infinite scroll event handler.
             */
            function vboWidgetQuotesInfiniteScroll(e) {
                // access the injected wrapper ID property
                let wrapper = e.currentTarget.wrapperId;

                if (!wrapper) {
                    return;
                }

                // register throttling callback
                VBOCore.throttleTimer(() => {
                    // access the current records list
                    const recordsList = document
                        .querySelector('#' + wrapper)
                        ?.querySelector('.vbo-w-quote-items-list');

                    if (!recordsList) {
                        return;
                    }

                    // ensure we've got more pages to load
                    let pageNumber = parseInt(recordsList.getAttribute('data-page-number')) || 1;
                    let pagesCount = parseInt(recordsList.getAttribute('data-pages-count')) || 1;

                    if (pageNumber >= pagesCount) {
                        // unregister the infinite scroll
                        recordsList
                            .removeEventListener('scroll', vboWidgetQuotesInfiniteScroll);

                        // display message for all records loaded
                        if (pagesCount > 1) {
                            let widget_content = document
                                .querySelector('#' + wrapper);

                            // hide the eventually displayed manual loading
                            widget_content
                                .querySelector('.vbo-widget-quotes-loadmore-hidden')
                                .style
                                .display = 'none';

                            if (!widget_content.querySelector('.vbo-widget-quotes-loadmore-info')) {
                                // append the message stating that all records have been displayed
                                let infoDiv = document
                                    .createElement('div');
                                infoDiv.classList
                                    .add('vbo-widget-quotes-loadmore-info');

                                let infoTxt = document
                                    .createElement('p');
                                infoTxt.append(<?php echo json_encode(JText::translate('VBO_NOMORE_RECORDS_DISPLAY')); ?>);

                                infoDiv.append(infoTxt);

                                widget_content.querySelector('.vbo-w-quote-items-list').append(infoDiv);
                            }
                        }

                        return;
                    }

                    // make sure the loading of a next page isn't running
                    if (recordsList.pageLoading) {
                        // abort
                        return;
                    }

                    // get wrapper dimensions
                    let listViewHeight = recordsList.offsetHeight;
                    let listGlobHeight = recordsList.scrollHeight;
                    let listScrollTop  = recordsList.scrollTop;

                    if (!listScrollTop || listViewHeight >= listGlobHeight) {
                        // no scrolling detected at all
                        return;
                    }

                    // calculate missing distance to the end of the list
                    let listEndDistance = listGlobHeight - (listViewHeight + listScrollTop);

                    if (listEndDistance < <?php echo $this->px_distance_threshold; ?>) {
                        // inject custom property to identify a next page is loading
                        recordsList.pageLoading = true;

                        // load the next page of records
                        vboWidgetQuotesLoadNextPage(wrapper);
                    }
                }, 500);
            }

            /**
             * Registers the click listener on all the eligible record entries.
             */
            function vboWidgetQuotesRegisterClickListeners(wrapper) {
                const wrapperEl = document.querySelector('#' + wrapper);
                const quotes = wrapperEl
                    .querySelector('.vbo-w-quote-items-list')
                    .querySelectorAll('.vbo-w-quote-item:not([data-listening])');

                quotes.forEach((quote) => {
                    // get quote ID
                    const quoteId = quote.getAttribute('data-quote-id');

                    // immediately set attribute flag with listening enabled
                    quote.setAttribute('data-listening', 1);

                    // register click event to edit the quote itself
                    quote.querySelector('.vbo-w-quote-item-head-edit').addEventListener('click', (e) => {
                        // build link element and simulate the click on it
                        const link = document.createElement('a');
                        link.href = '<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&view=managequote', false); ?>' + '&quote_id=' + quoteId;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.click();
                    });

                    // register click event to edit the booking solution
                    quote.querySelectorAll('.vbo-w-quote-solution-edit').forEach((solutionEdit) => {
                        solutionEdit.addEventListener('click', (e) => {
                            // get booking ID
                            const bookingId = e.target.closest('.vbo-w-quote-solution').getAttribute('data-booking-id');
                            // build link element and simulate the click on it
                            const link = document.createElement('a');
                            link.href = '<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editbusy', false); ?>' + '&cid[0]=' + bookingId;
                            link.target = '_blank';
                            link.rel = 'noopener noreferrer';
                            link.click();
                        });
                    });

                    // register click event to quickly see the booking details
                    quote.querySelectorAll('.vbo-w-quote-solution-revid').forEach((solutionLink) => {
                        solutionLink.addEventListener('click', (e) => {
                            // get booking ID
                            const bookingId = e.target.closest('.vbo-w-quote-solution').getAttribute('data-booking-id');
                            // open widget
                            VBOCore.handleDisplayWidgetNotification({widget_id: 'booking_details'}, {
                                bid: bookingId,
                                modal_options: {
                                    suffix: 'widget_modal_inner_booking_details',
                                },
                            });
                        });
                    });

                    // register click event to send the quote via email
                    quote.querySelector('.vbo-w-quote-send-btn')?.addEventListener('click', (e) => {
                        const btnEl = e.target.matches('button') ? e.target : e.target.closest('button');
                        const icnEl = btnEl.querySelector('i');
                        let icnClass = icnEl.getAttribute('class');

                        // build modal buttons element
                        let modalBtnsEl = document.createElement('div');

                        // modal send via email button
                        let mailBtnEl = document.createElement('button');
                        mailBtnEl.setAttribute('type', 'button');
                        mailBtnEl.classList.add('btn', 'vbo-stacked-btn', 'vbo-gray-icon-btn');
                        mailBtnEl.innerHTML = '<?php VikBookingIcons::e('envelope'); ?> Email';
                        mailBtnEl.addEventListener('click', () => {
                            // dismiss modal
                            VBOCore.emitEvent('wquote-choose-send-method-dismiss');

                            // start loading
                            btnEl.disabled = true;
                            icnEl.setAttribute('class', '');
                            icnEl.classList.add(...String('<?php echo VikBookingIcons::i('circle-notch', 'fa-spin fa-fw'); ?>').split(' '));

                            // make the request
                            VBOCore.doAjax(
                                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=quote.sendMail'); ?>",
                                {
                                    quote_id: quoteId,
                                },
                                (response) => {
                                    // change button to show the message was sent
                                    btnEl.classList.add('btn-success');
                                    btnEl.classList.remove('btn-primary');
                                    btnEl.innerHTML = '<?php VikBookingIcons::e('check'); ?> ' + <?php echo json_encode(JText::translate('VBO_SENT')); ?>;
                                },
                                (error) => {
                                    alert(error.responseText || 'An error occurred.');
                                    btnEl.disabled = false;
                                    icnEl.setAttribute('class', '');
                                    icnEl.classList.add(...String(icnClass).split(' '));
                                }
                            );
                        });
                        modalBtnsEl.append(mailBtnEl);

                        // modal send via whatsapp button
                        let messagingBtnEl = document.createElement('button');
                        messagingBtnEl.setAttribute('type', 'button');
                        messagingBtnEl.classList.add('btn', 'vbo-stacked-btn', 'vbo-green-icon-btn');
                        messagingBtnEl.innerHTML = '<?php VikBookingIcons::e('fab fa-whatsapp', 'vbo-enabled-icon'); ?> WhatsApp';
                        messagingBtnEl.addEventListener('click', () => {
                            // build modal buttons
                            let cancelBtn = document.createElement('button');
                            cancelBtn.setAttribute('type', 'button');
                            cancelBtn.classList.add('btn');
                            cancelBtn.textContent = <?php echo json_encode(JText::translate('VBANNULLA')); ?>;
                            cancelBtn.addEventListener('click', () => {
                                VBOCore.emitEvent('wquote-choose-macc-data-dismiss');
                            });
                            let sendBtn = document.createElement('button');
                            sendBtn.setAttribute('type', 'button');
                            sendBtn.classList.add('btn', 'btn-primary');
                            sendBtn.innerHTML = '<?php VikBookingIcons::e('paper-plane'); ?> ' + <?php echo json_encode(JText::translate('VBO_SEND_MESSAGE')); ?>;
                            sendBtn.addEventListener('click', () => {
                                // gather messaging account configuration to send
                                let configSelEl = document.querySelector('select[data-field="send-ma-tpl"][data-active-choice="1"]');
                                if (!configSelEl || !configSelEl.value) {
                                    alert('Please select a valid messaging account configuration.');
                                    return;
                                }

                                // obtain selected template details
                                let tplIdentifierParts = configSelEl.value.split(':');

                                // dismiss modal to choose the messaging account configuration
                                VBOCore.emitEvent('wquote-choose-macc-data-dismiss');

                                // dismiss modal to choose the sending method
                                VBOCore.emitEvent('wquote-choose-send-method-dismiss');

                                // start loading
                                btnEl.disabled = true;
                                icnEl.setAttribute('class', '');
                                icnEl.classList.add(...String('<?php echo VikBookingIcons::i('circle-notch', 'fa-spin fa-fw'); ?>').split(' '));

                                // make the request
                                VBOCore.doAjax(
                                    "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=quote.sendMessaging'); ?>",
                                    {
                                        account_id: tplIdentifierParts[0],
                                        phone_id:   tplIdentifierParts[1],
                                        config_id:  tplIdentifierParts[2],
                                        quote_id:   quoteId,
                                    },
                                    (response) => {
                                        // change button to show the message was sent
                                        btnEl.classList.add('btn-success');
                                        btnEl.classList.remove('btn-primary');
                                        btnEl.innerHTML = '<?php VikBookingIcons::e('check'); ?> ' + <?php echo json_encode(JText::translate('VBO_SENT')); ?>;
                                    },
                                    (error) => {
                                        alert(error.responseText || 'An error occurred.');
                                        btnEl.disabled = false;
                                        icnEl.setAttribute('class', '');
                                        icnEl.classList.add(...String(icnClass).split(' '));
                                    }
                                );
                            });

                            // display modal for choosing the messaging configuration data to use for sending the quote
                            let messagingDataBody = VBOCore.displayModal({
                                suffix:        'wquote-choose-macc-data',
                                extra_class:   'vbo-modal-rounded',
                                title:         <?php echo json_encode(sprintf('%s #{id} - %s', JText::translate('VBO_BTYPE_QUOTE'), JText::translate('VBO_SEND_MESSAGE'))); ?>.replace('{id}', quoteId),
                                draggable:     false,
                                footer_left:   cancelBtn,
                                footer_right:  sendBtn,
                                dismiss_event: 'wquote-choose-macc-data-dismiss',
                                loading_event: 'wquote-choose-macc-data-loading',
                            });

                            // start loading
                            VBOCore.emitEvent('wquote-choose-macc-data-loading');

                            // make the request to load all messaging configurations data
                            VBOCore.doAjax(
                                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=quote.getMessagingConfigurations'); ?>",
                                {
                                    quote_id: quoteId,
                                },
                                (response) => {
                                    if (!Array.isArray(response?.data) || !response.data.length) {
                                        alert('No messaging accounts configured through the Channel Manager.');
                                        VBOCore.emitEvent('wquote-choose-macc-data-dismiss');
                                        return;
                                    }
                                    // stop loading
                                    VBOCore.emitEvent('wquote-choose-macc-data-loading');
                                    // save response data
                                    const maConfigs = response.data;
                                    // clone helper
                                    const cloneHelper = wrapperEl.querySelector('.vbo-w-quote-send-tplmessage').cloneNode(true);
                                    const clonedSelEl = cloneHelper.querySelector('select[data-field="send-ma-tpl"]');
                                    const previewEl   = cloneHelper.querySelector('.vbo-ma-send-tplmessage-preview');
                                    // identify select element as active choice
                                    clonedSelEl.setAttribute('data-active-choice', 1);
                                    // set messaging account configuration options
                                    maConfigs.forEach((maConfig, maIndex) => {
                                        let configOptEl = document.createElement('option');
                                        configOptEl.value = maConfig?.identifier;
                                        configOptEl.textContent = (maConfig?.name || '') + ' (' + (maConfig?.lang || '?') + ')';
                                        if (!maIndex) {
                                            configOptEl.selected = true;
                                        }
                                        clonedSelEl.append(configOptEl);
                                    });
                                    // register change event for the template preview
                                    clonedSelEl.addEventListener('change', (e) => {
                                        const configId = e.target.value;
                                        let configFound = false;
                                        previewEl.innerHTML = '';
                                        maConfigs.forEach((maConfig) => {
                                            if (configFound) {
                                                return;
                                            }
                                            if (maConfig.identifier == configId) {
                                                // update preview content and turn flag on
                                                configFound = true;
                                                previewEl.innerHTML = maConfig?.preview_html || '';
                                            }
                                        });
                                    });
                                    // append helper to modal body
                                    (messagingDataBody[0] || messagingDataBody).append(cloneHelper);
                                    // trigger change event to load the first preview
                                    clonedSelEl.dispatchEvent(new Event('change'));
                                },
                                (error) => {
                                    alert(error.responseText || 'An error occurred.');
                                    VBOCore.emitEvent('wquote-choose-macc-data-dismiss');
                                }
                            );
                        });
                        modalBtnsEl.append(messagingBtnEl);

                        // display modal for choosing the quote sending method (stacked buttons)
                        VBOCore.displayModal({
                            suffix:        'wquote-choose-send-method',
                            extra_class:   'vbo-modal-rounded vbo-modal-choice vbo-modal-footer-stacked',
                            title:         <?php echo json_encode(JText::translate('VBO_CHOOSE_SEND_METHOD')); ?>,
                            body:          <?php echo json_encode(JText::translate('VBO_HOW_SEND_MESSAGE')); ?>,
                            draggable:     false,
                            lock_scroll:   true,
                            footer_center: modalBtnsEl,
                            dismiss_event: 'wquote-choose-send-method-dismiss',
                        });
                    });
                });

                // check if we have a button to go back and see all quotes (booking ID injected)
                wrapperEl.querySelector('.vbo-w-list-back')?.addEventListener('click', () => {
                    const listEl = wrapperEl?.querySelector('.vbo-w-quote-items-list');
                    // set counters to force the loading of the first page (0 will be increased to 1)
                    listEl.setAttribute('data-page-number', -1);
                    // make sure to use a value greater than zero
                    listEl.setAttribute('data-pages-count', 1);
                    // empty the list of quote items
                    listEl.querySelectorAll('.vbo-w-quote-item').forEach((quoteItem) => {
                        quoteItem.remove();
                    });
                    // get rid of the see all button
                    wrapperEl.querySelector('.vbo-w-list-back').remove();
                    // re-load records from the first page
                    vboWidgetQuotesLoadNextPage(wrapper, true);
                });
            }

        </script>
            <?php
        }
        ?>

        <script type="text/javascript">

            VBOCore.DOMLoaded(() => {

                // listen to the manual load-more button
                document.getElementById('<?php echo $wrapper_id; ?>').querySelector('.vbo-widget-quotes-loadmore-manual')?.addEventListener('click', (e) => {
                    // load the next page of records
                    vboWidgetQuotesLoadNextPage('<?php echo $wrapper_id; ?>');
                });

                // set up infinite scroll loading
                vboWidgetQuotesSetupInfiniteScroll('<?php echo $wrapper_id; ?>');

                // set up quotes click listeners
                vboWidgetQuotesRegisterClickListeners('<?php echo $wrapper_id; ?>');

            });

        </script>

        <?php
    }

    /**
     * Given a list of quote objects, builds and returns the HTML rendering code.
     * 
     * @param   array   $quotes     List of quote objects to render.
     * @param   int     $page_num   Optional page number.
     * 
     * @return  string
     */
    protected function buildQuotesHTML(array $quotes, int $page_num = 0)
    {
        if (!$quotes) {
            return '';
        }

        // start output buffering
        ob_start();

        foreach ($quotes as $quote) {
            // tell if the quote is expired
            $isExpired = false;
            if (!empty($quote->valid_until) && JFactory::getDate($quote->valid_until)->getTimestamp() < time()) {
                $isExpired = true;
            }
            ?>
            <div class="vbo-w-quote-item" data-quote-id="<?php echo $quote->id; ?>">

                <div class="vbo-w-quote-item-head">
                    <div class="vbo-w-quote-item-head-left">
                        <h4><?php
                        if ($quote->preferred) {
                            ?>
                            <span class="vbo-w-quote-preferred"><?php VikBookingIcons::e('star', 'icn-nomargin vbo-yellow'); ?></span>
                            <?php
                        }
                        echo $quote->name;
                        ?></h4>
                        <div class="vbo-w-quote-creation-dt">
                            <span><?php echo JHtml::fetch('date', $quote->created_on, 'd M Y H:i'); ?></span>
                        </div>
                        <div class="vbo-w-quote-customer">
                            <span class="vbo-w-quote-customer-name"><?php VikBookingIcons::e('user'); ?> <?php echo trim(sprintf('%s %s', (string) $quote->first_name, (string) $quote->last_name)); ?></span>
                        <?php
                        if (!empty($quote->email)) {
                            ?>
                            <span class="vbo-w-quote-customer-email"><?php VikBookingIcons::e('envelope'); ?> <?php echo $quote->email; ?></span>
                            <?php
                        }
                        if (!empty($quote->phone)) {
                            ?>
                            <span class="vbo-w-quote-customer-phone"><a href="tel:<?php echo preg_replace('/[^0-9\+]+/', '', $quote->phone); ?>"><?php VikBookingIcons::e('phone'); ?> <?php echo $quote->phone; ?></a></span>
                            <?php
                        }
                        ?>
                        </div>
                    </div>
                    <div class="vbo-w-quote-item-head-right" data-section="status">
                        <span class="vbo-w-quote-item-head-validity<?php echo $isExpired ? ' text-red' : ''; ?>"><?php echo sprintf('%s %s', JText::translate('VBO_EXPIRES'), ($quote->valid_until ? JHtml::fetch('date', $quote->valid_until, 'd M Y') : '----')); ?></span>
                    <?php
                    if ($quote->viewed) {
                        ?>
                        <span class="badge-medium badge-transp-blue vbo-bold vbo-w-quote-item-head-status"><?php VikBookingIcons::e('check-double'); ?> <?php echo JText::translate('VBO_OPENED'); ?></span>
                        <?php
                        if (!empty($quote->email) || !empty($quote->phone)) {
                            ?>
                        <button type="button" class="btn btn-small vbo-w-quote-item-head-status vbo-w-quote-send-btn"><?php VikBookingIcons::e('paper-plane'); ?> <?php echo JText::translate('VBO_SEND'); ?></button>
                            <?php
                        }
                    } elseif ($quote->sent) {
                        ?>
                        <button type="button" class="btn btn-primary btn-small vbo-w-quote-item-head-status vbo-w-quote-send-btn"><?php VikBookingIcons::e('check'); ?> <?php echo JText::translate('VBO_SENT'); ?></button>
                        <?php
                    } elseif (!empty($quote->email) || !empty($quote->phone)) {
                        ?>
                        <button type="button" class="btn btn-small vbo-w-quote-item-head-status vbo-w-quote-send-btn"><?php VikBookingIcons::e('paper-plane'); ?> <?php echo JText::translate('VBO_SEND'); ?></button>
                        <?php
                    }
                    ?>
                        <span class="vbo-w-quote-item-head-edit"><?php VikBookingIcons::e('pencil-alt', 'icn-nomargin'); ?></span>
                    </div>
                </div>

                <div class="vbo-w-quote-item-body">
                <?php
                foreach ($quote->solutions as $indexSol => $solution) {
                    // count booking solution values
                    $totRooms    = count($solution->rooms);
                    $totAdults   = array_sum(array_column($solution->rooms, 'adults'));
                    $totChildren = array_sum(array_column($solution->rooms, 'children'));
                    ?>
                    <div class="vbo-w-quote-solution" data-booking-id="<?php echo $solution->id; ?>">
                        <div class="vbo-w-quote-solution-roominfo">
                            <div class="vbo-w-quote-solution-title">
                                <h4><?php echo sprintf('%s #%d', JText::translate('VBO_OPTION'), ++$indexSol); ?></h4>
                                <a class="badge badge-info vbo-w-quote-solution-revid"><?php VikBookingIcons::e('external-link'); ?> <?php echo sprintf('#%d', $solution->id); ?></a>
                                <div class="vbo-w-quote-solution-bookdates">
                                    <?php VikBookingIcons::e('calendar'); ?>
                                    <span><?php echo JHtml::fetch('date', date('Y-m-d', $solution->checkin), 'd M Y'); ?></span>
                                    <?php VikBookingIcons::e('arrow-right'); ?>
                                    <span><?php echo JHtml::fetch('date', date('Y-m-d', $solution->checkout), 'd M Y'); ?></span>
                                </div>
                            </div>
                            <div class="vbo-w-quote-solution-infoparty">
                                <span><?php VikBookingIcons::e('bed'); ?> <?php echo sprintf('%d %s', $totRooms, JText::translate($totRooms == 1 ? 'VBEDITORDERTHREE' : 'VBPVIEWORDERSTHREE')); ?></span>
                                <span><?php VikBookingIcons::e('male'); ?> <?php echo sprintf('%d %s', $totAdults, JText::translate($totAdults == 1 ? 'VBMAILADULT' : 'VBMAILADULTS')); ?></span>
                                <span><?php VikBookingIcons::e('baby'); ?> <?php echo sprintf('%d %s', $totChildren, JText::translate($totChildren == 1 ? 'VBMAILCHILD' : 'VBMAILCHILDREN')); ?></span>
                            </div>
                            <div class="vbo-w-quote-solution-rooms">
                            <?php
                            foreach ($solution->rooms as $solutionRoom) {
                                ?>
                                <span class="badge-small"><?php echo $solutionRoom->name; ?></span>
                                <?php
                            }
                            ?>
                            </div>
                        </div>
                        <div class="vbo-w-quote-solution-priceinfo-wrap">
                            <div class="vbo-w-quote-solution-priceinfo">
                                <div class="vbo-w-quote-solution-price"><?php echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($solution->total), VikBooking::getCurrencySymb()); ?></div>
                                <div class="vbo-w-quote-solution-info">
                                <?php
                                if ($solution->status == 'confirmed') {
                                    ?>
                                    <span class="badge-medium badge-icon badge-success vbo-bold"><?php VikBookingIcons::e('check'); ?> <?php echo JText::translate('VBCONFIRMED'); ?></span>
                                    <?php
                                } elseif ($solution->status == 'standby') {
                                    ?>
                                    <span class="badge-medium badge-icon badge-warning vbo-bold"><?php VikBookingIcons::e('clock'); ?> <?php echo JText::translate('VBSTANDBY'); ?></span>
                                    <?php
                                } elseif ($solution->status == 'cancelled') {
                                    ?>
                                    <span class="badge-medium badge-icon badge-error vbo-bold"><?php VikBookingIcons::e('ban'); ?> <?php echo JText::translate('VBCANCELLED'); ?></span>
                                    <?php
                                }
                                ?>
                                </div>
                            </div>
                            <div class="vbo-w-quote-solution-edit">
                                <?php VikBookingIcons::e('pencil-alt'); ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                </div>

            </div>
            <?php
        }

        // get the HTML buffer
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}

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
 * Obtain vars from arguments received in the layout file.
 * 
 * @var string  $caller     The identifier of who's calling this layout file.
 * @var array   $rooms      List of room records involved.
 * @var array   $tmfilters  Optional list of task manager filters.
 */
extract($displayData);

// access the application
$app = JFactory::getApplication();
$vbo_app = VikBooking::getVboApplication();

// load context menu assets
$vbo_app->loadContextMenuAssets();

// preload chat assets
VBOFactory::getChatMediator()->useAssets();

// gather permissions
$vbo_auth_pricing = JFactory::getUser()->authorise('core.vbo.pricing', 'com_vikbooking');
$vbo_auth_pms = JFactory::getUser()->authorise('core.vbo.pms', 'com_vikbooking');

// load all the existing task manager areas/projects
$taskAreas = VBOTaskModelArea::getInstance()->getItems();

// currency symbol and formatting options
$currencysymb = VikBooking::getCurrencySymb();
list($currency_digits, $currency_decimals, $currency_thousands) = explode(':', VikBooking::getNumberFormatData());

// check whether VCM is available
$vcm_enabled = VikBooking::vcmAutoUpdate();

// build room-ota relations for pricing alterations, if any
$room_ota_relations = [];
foreach (array_column(($rooms ?? []), 'id') as $rid) {
    // always get a new instance of the VikChannelManagerLogos class
    $vcm_logos = VikBooking::getVcmChannelsLogo('', true);
    // load channels (firsr) and accounts (after) for this listing
    $room_ota_channels = is_object($vcm_logos) && method_exists($vcm_logos, 'getVboRoomLogosMapped') ? $vcm_logos->getVboRoomLogosMapped($rid) : [];
    $room_ota_accounts = is_object($vcm_logos) && method_exists($vcm_logos, 'getRoomOtaAccounts') ? $vcm_logos->getRoomOtaAccounts() : [];
    // filter channels not available as accounts (i.e. iCal)
    if (count($room_ota_channels) != count(($room_ota_accounts[$rid] ?? []))) {
        $ota_account_names = array_map('strtolower', array_column(($room_ota_accounts[$rid] ?? []), 'channel'));
        $room_ota_channels = array_filter($room_ota_channels, function($chid) use ($ota_account_names) {
            return in_array(strtolower($chid), $ota_account_names);
        }, ARRAY_FILTER_USE_KEY);
    }
    if ($room_ota_channels && ($room_ota_accounts[$rid] ?? [])) {
        $room_ota_relations[$rid] = [
            'channels' => $room_ota_channels,
            'accounts' => $room_ota_accounts[$rid],
        ];
    }
}

// build room names map
$room_names_map = array_combine(array_column(($rooms ?? []), 'id'), array_column(($rooms ?? []), 'name'));

// access the current task manager filters
$tmfilters = (array) (($tmfilters ?? []) ?: $app->input->get('tmfilters', [], 'array'));

// check if the tasks should be automatically loaded for certain area/project IDs
$activeAreas = array_values(array_filter(array_map('intval', $tmfilters['area_ids'] ?? [])));

?>

<button type="button" class="btn vbo-context-menu-btn vbo-context-menu-btn-raw vbo-context-menu-overview-actions">
    <span class="vbo-context-menu-lbl"><?php echo JText::translate('VBCRONACTIONS'); ?></span>
    <span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
</button>

<div class="vbo-overview-action-raterestr-helper" style="display: none;">

    <div class="vbo-overview-action-raterestr-wrap">
        <div class="vbo-overview-action-raterestr-info">
            <div class="vbo-overview-action-raterestr-listings-info">
                <?php VikBookingIcons::e('bed'); ?>
                <span class="vbo-overview-action-raterestr-listings"></span>
            </div>
            <div class="vbo-overview-action-raterestr-dates"></div>
        </div>
        <div class="vbo-roverw-setnewrate">
            <div class="vbo-roverw-setnewrate-title">
                <h4><?php VikBookingIcons::e('calculator'); ?> <?php echo JText::translate('VBO_RATES_AND_RESTR'); ?></h4>
            </div>
            <div class="vbo-roverw-flexnew">
                <div class="vbo-roverw-newrwrap">
                    <h4><?php VikBookingIcons::e('edit'); ?> <?php echo JText::translate('VBRATESOVWSETNEWRATE'); ?></h4>
                    <div class="vbo-roverw-newrcont">
                        <label for="roverw-newrate" class="vbo-roverw-setnewrate-currency"><?php echo $currencysymb; ?></label>
                        <input type="number" step="any" min="0" id="roverw-newrate" value="" placeholder="" size="7" />
                    </div>
                </div>
                <div class="vbo-roverw-newrestr-wrap" style="display: none;">
                    <div class="vbo-roverw-newrestrcont">
                        <h4><?php VikBookingIcons::e('ban'); ?> <?php echo JText::translate('VBOMINIMUMSTAYSET'); ?></h4>
                        <div class="vbo-roverw-newrestrcont-inner">
                            <label for="roverw-newrestr" class="vbo-roverw-setnewrestr-lbl"><?php echo JText::translate('VBDAYS'); ?></label>
                            <input type="number" step="1" min="0" id="roverw-newrestr" value="" size="7" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="vbo-roverw-setnewrate-inner">
                <div class="vbo-roverw-setnewrate-vcm">
                    <div class="vbo-roverw-setnewrate-vcm-head">
                        <span class="<?php echo $vcm_enabled < 0 ? 'vbo-vcm-notinstalled' : 'vbo-vcm-installed'; ?>">
                            <?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOUPDRATESONCHANNELS'), 'content' => ($vcm_enabled < 0 ? JText::translate('VBCONFIGVCMAUTOUPDMISS') : JText::translate('VBOUPDRATESONCHANNELSHELP')), 'icon_class' => VikBookingIcons::i('rocket'))); ?>
                            <?php echo JText::translate('VBOUPDRATESONCHANNELS'); ?>
                        </span>
                    </div>
                    <div class="vbo-roverw-setnewrate-vcm-body vbo-toggle-small">
                        <?php
                        echo $vbo_app->printYesNoButtons('roverw-newrate-vcm', JText::translate('VBYES'), JText::translate('VBNO'), ($vcm_enabled > 0 ? 1 : 0), 1, 0, 'vboActionVcmRestrictionsSupported();', ['blue']);

                        if ($vcm_enabled < 0) {
                            // disable the toggle button when VCM is not available
                            ?>
                        <script type="text/javascript">
                            jQuery(function() {
                                jQuery('input[name="roverw-newrate-vcm"]').prop('disabled', true);
                            });
                        </script>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="vbo-roverw-setnewrate-vcm-otas"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="vbo-roverw-setnewrate-vcm-ota-pricing-alteration">
        <div class="vbo-roverw-setnewrate-vcm-ota-alteration-elem">
            <select data-alter-rule="rmodsop">
                <option value="1">+</option>
                <option value="0">-</option>
            </select>
        </div>
        <div class="vbo-roverw-setnewrate-vcm-ota-alteration-elem">
            <input type="number" value="" step="any" min="0" data-alter-rule="rmodsamount" />
        </div>
        <div class="vbo-roverw-setnewrate-vcm-ota-alteration-elem">
            <select data-alter-rule="rmodsval">
                <option value="1">%</option>
                <option value="0"><?php echo $currencysymb; ?></option>
            </select>
        </div>
    </div>

</div>

<script type="text/javascript">
    /**
     * Register room-ota relations map.
     */
    const vboActionRoomOtaRels = <?php echo json_encode($room_ota_relations); ?>;

    /**
     * Register room namings map.
     */
    const vboActionRoomNames = <?php echo json_encode($room_names_map); ?>;

    /**
     * Register room rate plans map.
     */
    const vboActionRoomRplansMap = {};

    /**
     * Register function for loading the room rates.
     */
    const vboActionLoadRoomRates = (from_date, to_date, room_ids) => {
        let ctx_elem = document
            .querySelector('.vbo-context-menu-overview-actions');

        let lbl_elem = ctx_elem
            .querySelector('.vbo-context-menu-lbl');

        let orig_lbl = lbl_elem.innerText;

        // start loading animation
        ctx_elem.loading = 1;
        lbl_elem.innerHTML = '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>';

        // make the request
        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=pricing.loadRoomRates'); ?>",
            {
                room_ids: room_ids,
                from_date: from_date,
                to_date: to_date,
                restrictions: true,
            },
            (resp) => {
                try {
                    // decode the response (if needed), and append the content to the modal body
                    resp = typeof resp === 'string' ? JSON.parse(resp) : resp;

                    // hide any previously loaded room rate row, if any
                    vboActionHideRoomRates();

                    // get the date object for today at midnight as limit for past dates
                    let todayMidnight = new Date().setHours(0, 0, 0, 0);

                    // populate rates for all listings by scanning all month tables
                    document
                        .querySelectorAll('table.vboverviewtable[data-month-from]')
                        // iterate months
                        .forEach((month) => {
                            month
                                .querySelectorAll('.roomname[data-roomid]')
                                // iterate month listings
                                .forEach((roomRowCell) => {
                                    if (roomRowCell.matches('.subroomname')) {
                                        // skip sub-unit rows
                                        return;
                                    }

                                    // get current listing ID
                                    let listingId = roomRowCell.getAttribute('data-roomid');

                                    if (!resp.hasOwnProperty(listingId)) {
                                        // no rates returned for this room id
                                        return;
                                    }

                                    // access the "parent row"
                                    let parentRow = roomRowCell.closest('tr');

                                    // check whether the listing is a multi-unit room-type
                                    let isMultiUnit = (roomRowCell.getAttribute('data-units') || 1) > 1;

                                    // build room-rates row for the current listing
                                    let ratesRow = document.createElement('tr');
                                    ratesRow.classList.add('vbo-roomrates-row');
                                    ratesRow.setAttribute('data-roomid', listingId);

                                    // declare flag for rate plan id
                                    let ratePlanId = '';

                                    // build first row-cell and append it to the row
                                    let ratesCell = document.createElement('td');
                                    ratesCell.classList.add('vbo-roomrates-cell-first');
                                    ratesCell.innerHTML = '<?php VikBookingIcons::e('calculator'); ?>';
                                    let cellNameEl = document.createElement('span');
                                    cellNameEl.classList.add('vbo-roomrates-cell-name');
                                    cellNameEl.innerText = <?php echo json_encode(JText::translate('VBO_RATES_AND_RESTR')); ?>;
                                    ratesCell.append(cellNameEl);
                                    ratesRow.append(ratesCell);

                                    // iterate over the "parent row" day cells of the current listing and month
                                    parentRow
                                        .querySelectorAll('td.vbo-grid-avcell[data-day]')
                                        // iterate month listing days
                                        .forEach((roomRowDay) => {
                                            // get the day Y-m-d value
                                            let ymd = roomRowDay.getAttribute('data-day');

                                            // check whether the day is in the past
                                            let isPast = todayMidnight > new Date(ymd);

                                            // build month-day cell with pricing details
                                            let rateDayCell = document.createElement(isMultiUnit ? 'td' : 'span');
                                            // differentiate a multi-unit room row with independent cell from a single-unit cell on main availability row
                                            rateDayCell.classList.add(isMultiUnit ? 'vbo-roomrates-cell-day' : 'vbo-grid-cell-rate');
                                            if (isMultiUnit) {
                                                // set data attribute to independent cell
                                                rateDayCell.setAttribute('data-day', ymd);
                                                // add the same class to independent cell to identify a rate-cell
                                                rateDayCell.classList.add('vbo-grid-avcell-rates');
                                                // check if it's a past date
                                                if (isPast) {
                                                    rateDayCell.setAttribute('data-ispast', '1');
                                                }
                                            } else {
                                                // add class to parent cell that will be containing the rates
                                                roomRowDay.classList.add('vbo-grid-avcell-rates');
                                                // check if it's a past date
                                                if (isPast) {
                                                    roomRowDay.setAttribute('data-ispast', '1');
                                                }
                                            }

                                            // access the pricing information for this day
                                            if (resp[listingId].hasOwnProperty(ymd)) {
                                                if (!ratePlanId) {
                                                    // set rate plan id value-flag for data attribute on main rate row
                                                    ratePlanId = resp[listingId][ymd].idprice;
                                                    ratesRow.setAttribute('data-rateid', ratePlanId);
                                                    // update global rate plans map
                                                    vboActionRoomRplansMap[listingId] = resp[listingId][ymd].idprice;
                                                }

                                                // build rate amount element
                                                let rateAmountEl = document.createElement('span');
                                                rateAmountEl.classList.add('vbo-roomrates-cell-rate-amount');
                                                rateAmountEl.innerHTML = VBOCore.getCurrency().format(resp[listingId][ymd].cost);

                                                // append rate amount element to cell
                                                rateDayCell.append(rateAmountEl);

                                                if ((resp[listingId][ymd]?.restrictions?.minlos || 0) > 0) {
                                                    // build min-los element
                                                    let minLosEl = document.createElement('span');
                                                    minLosEl.classList.add('vbo-roomrates-cell-minlos');
                                                    minLosEl.innerHTML = '<?php VikBookingIcons::e('moon'); ?> ' + resp[listingId][ymd].restrictions.minlos;

                                                    // append min-los element to cell
                                                    rateDayCell.append(minLosEl);
                                                }
                                            }

                                            if (isMultiUnit) {
                                                // append rate cell to main rate row
                                                ratesRow.append(rateDayCell);
                                            } else if (roomRowDay.matches('.notbusy')) {
                                                // single-unit listing with free cell, append the rate information to the parent row, under the current cell
                                                roomRowDay.insertAdjacentElement('beforeend', rateDayCell);
                                            }
                                        });

                                    if (isMultiUnit) {
                                        // append the main rate row to the DOM
                                        parentRow.insertAdjacentElement('afterend', ratesRow);
                                    }
                                });
                        });

                    // iterating completed, stop loading
                    lbl_elem.innerHTML = '';
                    lbl_elem.innerText = orig_lbl;
                    ctx_elem.loading = 0;

                    // register events for the room-rate cells
                    vboActionRegisterRoomRateEvents();

                    // restore any previous temporary data for setting new rates within a queue
                    let previousQueue = VBOCore.getAdminDock().loadTemporaryData(
                        {
                            id: '_tmp',
                            persist_id: 'setnewrates',
                        },
                        (queueData) => {
                            // temporary data restored from dock
                            vboActionDisplayRatesQueue(queueData);
                        },
                        (queueData) => {
                            // temporary data removed from dock
                            document.querySelectorAll('.vbo-cell-pending-update').forEach((pending_cell) => {
                                pending_cell.classList.remove('vbo-cell-pending-update');
                            });
                        }
                    );

                    if (previousQueue && previousQueue?.data && Array.isArray(previousQueue?.data)) {
                        // ensure the cells of the previous queue will get the pending update class
                        let previousQueueData = previousQueue.data;
                        let todayMidnight = new Date().setHours(0, 0, 0, 0);

                        // get all month tables to find the requested listing-day cells
                        let monthTables = document
                            .querySelectorAll('table.vboverviewtable[data-month-from]');

                        // scan the previous request data objects
                        previousQueueData.forEach((prevData) => {
                            if (!prevData?.id_room || !prevData?.fromdate || !prevData?.todate) {
                                // invalid rates data format
                                return;
                            }

                            // pre-flight check to identify the row for the current listing
                            let listingRateRow = document.querySelector('.vbo-roomrates-row[data-roomid="' + prevData.id_room + '"]');
                            if (!listingRateRow) {
                                // may be a single-unit listing
                                listingRateRow = document.querySelector('.vboverviewtablerow[data-roomid="' + prevData.id_room + '"]');
                            }

                            if (!listingRateRow) {
                                // listing no longer in the page, regardless of the month
                                return;
                            }

                            // build the date objects
                            let iterDate = new Date(prevData.fromdate);
                            let end = new Date(prevData.todate);

                            // loop through the dates interval
                            while (iterDate <= end) {
                                if (iterDate < todayMidnight) {
                                    // go to next day by cloning the date object
                                    iterDate = new Date(iterDate.setDate(iterDate.getDate() + 1));

                                    // do not proceed with a date in the past
                                    continue;
                                }

                                // build the current day key in Y-m-d format
                                let day_key = VBOCore.formatDate(iterDate, 'Y-m-d');

                                // iterate over all months to identify the requested listing-day rate cell
                                let listingDayRateCell = null;
                                monthTables.forEach((monthTable) => {
                                    if (listingDayRateCell) {
                                        // desired cell was found already
                                        return;
                                    }

                                    // identify, again, the row for the current listing, but in the current month
                                    let listingRateRow = monthTable.querySelector('.vbo-roomrates-row[data-roomid="' + prevData.id_room + '"]');
                                    if (!listingRateRow) {
                                        // may be a single-unit listing
                                        listingRateRow = monthTable.querySelector('.vboverviewtablerow[data-roomid="' + prevData.id_room + '"]');
                                    }

                                    if (!listingRateRow) {
                                        // should not happen as it was found above during the pre-flight check
                                        return;
                                    }

                                    // query the desired cell
                                    listingDayRateCell = listingRateRow
                                        .querySelector('.vbo-grid-avcell-rates[data-day="' + day_key + '"]');
                                });

                                if (!listingDayRateCell) {
                                    // go to next day by cloning the date object
                                    iterDate = new Date(iterDate.setDate(iterDate.getDate() + 1));

                                    // listing cell date no longer in the document
                                    continue;
                                }

                                // find rate cell for single-unit listing
                                let dayRateCellAmount = listingDayRateCell.querySelector('.vbo-grid-cell-rate');
                                if (!dayRateCellAmount) {
                                    // must be a multi-unit listing with a dedicated cell for the rate
                                    dayRateCellAmount = listingDayRateCell;
                                }

                                if (dayRateCellAmount) {
                                    // restore the pending update class
                                    listingDayRateCell.classList.add('vbo-cell-pending-update');

                                    let rateAmountEl = listingDayRateCell.querySelector('.vbo-roomrates-cell-rate-amount');
                                    if (rateAmountEl) {
                                        // cell is not occupied by a reservation, update the rate from the previous queue
                                        rateAmountEl.innerHTML = VBOCore.getCurrency().format(prevData.rate);

                                        if (prevData.minlos) {
                                            // update minimum stay from the previous queue
                                            let minlosEl = listingDayRateCell.querySelector('.vbo-roomrates-cell-minlos');
                                            minlosEl.innerHTML = '<?php VikBookingIcons::e('moon'); ?> ' + prevData.minlos;
                                        }
                                    }
                                }

                                // go to next day by cloning the date object
                                iterDate = new Date(iterDate.setDate(iterDate.getDate() + 1));
                            }
                        });
                    }
                } catch (err) {
                    console.error('Error decoding the response', err, resp);
                }
            },
            (error) => {
                // display error message
                alert(error.responseText);

                // stop loading
                lbl_elem.innerHTML = '';
                lbl_elem.innerText = orig_lbl;
                ctx_elem.loading = 0;
            }
        );
    };

    /**
     * Register function to hide the room rates.
     */
    const vboActionHideRoomRates = () => {
        // hide rows for multi-unit room-types
        document
            .querySelectorAll('.vbo-roomrates-row')
            .forEach((row) => {
                row.remove();
            });

        // hide rate cells for single-unit listings
        document
            .querySelectorAll('.vbo-grid-cell-rate')
            .forEach((cell) => {
                cell.closest('td').classList.remove('vbo-grid-avcell-rates');
                cell.remove();
            });
    };

    /**
     * Build default object to handle the selection of the room rates.
     */
    const vboActionRoomRateData = {
        start: null,
        end: null,
        listingIdStart: null,
        listingIdEnd: null,
        traverseDir: null,
        listingIds: [],
    };

    /**
     * Register function to gather the list of involved room rate cells matrix for selection.
     * 
     * @param   Date    start           Date object for the first selection.
     * @param   Date    end             Date object for the last selection.
     * @param   number  listingStart    Listing ID for the first selection.
     * @param   number  listingEnd      Listing ID for the last selection.
     * 
     * @return  Array   Linear array of rows (listings) and list of dates.
     */
    const vboActionRoomRateGetCellsMatrix = (start, end, listingStart, listingEnd) => {
        let matrix = [];

        if (!start instanceof Date || !end instanceof Date) {
            // abort for invalid arguments
            return matrix;
        }

        // start the pool of listings involved in the selection
        let listingsInvolved = [listingStart];

        // start container for the first day-cell clicked on the initial listing
        let initialCell;

        // build the start day key in Y-m-d format
        let start_day_key = VBOCore.formatDate(start, 'Y-m-d');

        // identify the initial cell clicked
        initialCell = document
            .querySelector('.vbo-roomrates-row[data-roomid="' + listingStart + '"] .vbo-grid-avcell-rates[data-day="' + start_day_key + '"]');

        if (!initialCell) {
            // single-unit listings have a different row class
            initialCell = document
                .querySelector('.vboverviewtablerow[data-roomid="' + listingStart + '"] .vbo-grid-avcell-rates[data-day="' + start_day_key + '"]');
        }

        // check whether the selection affects multiple listings and eventually gather their IDs
        if (listingStart != listingEnd) {
            // traverse row elements heading toward the document end (down) to identify the landing listing ID row and all listings involved
            let nextSiblingRow = initialCell.closest('tr').nextElementSibling;
            while (nextSiblingRow != null) {
                if ((!nextSiblingRow.matches('tr.vbo-roomrates-row') && !nextSiblingRow.matches('tr.vboverviewtablerow')) || nextSiblingRow.matches('.vboverviewtablerow-subunit')) {
                    // invalid or sub-unit row
                    // go to the next listing row downwards
                    nextSiblingRow = nextSiblingRow.nextElementSibling;
                    continue;
                }

                let currentListing = nextSiblingRow.getAttribute('data-roomid');

                if (!currentListing || isNaN(currentListing)) {
                    // invalid row
                    // go to the next listing row downwards
                    nextSiblingRow = nextSiblingRow.nextElementSibling;
                    continue;
                }

                // push listing ID involved
                listingsInvolved.push(currentListing);

                if (currentListing == listingEnd) {
                    // all rows found
                    vboActionRoomRateData.traverseDir = 'down';
                    break;
                }

                // go to the next listing row downwards
                nextSiblingRow = nextSiblingRow.nextElementSibling;
            }

            // check if the end of the selection was made upwards
            if (!listingsInvolved.includes(listingEnd)) {
                // reset pool of listings involved
                listingsInvolved = [listingStart];

                // traverse row elements heading toward the document root (up) to identify the landing listing ID row and all listings involved
                let prevSiblingRow = initialCell.closest('tr').previousElementSibling;
                while (prevSiblingRow != null) {
                    if ((!prevSiblingRow.matches('tr.vbo-roomrates-row') && !prevSiblingRow.matches('tr.vboverviewtablerow')) || prevSiblingRow.matches('.vboverviewtablerow-subunit')) {
                        // invalid or sub-unit row
                        // go to the previous listing row upwards
                        prevSiblingRow = prevSiblingRow.previousElementSibling;
                        continue;
                    }

                    let currentListing = prevSiblingRow.getAttribute('data-roomid');

                    if (!currentListing || isNaN(currentListing)) {
                        // invalid row
                        // go to the previous listing row upwards
                        prevSiblingRow = prevSiblingRow.previousElementSibling;
                        continue;
                    }

                    // push listing ID involved
                    listingsInvolved.push(currentListing);

                    if (currentListing == listingEnd) {
                        // all rows found
                        vboActionRoomRateData.traverseDir = 'up';
                        break;
                    }

                    // go to the previous listing row upwards
                    prevSiblingRow = prevSiblingRow.previousElementSibling;
                }
            }

            if (!listingsInvolved.includes(listingEnd)) {
                // could not identify the landing row, neither by traversing the document downwards, nor upwards
                // reset the pool of listings involved
                listingsInvolved = [listingStart];
            }
        }

        // update listing IDs involved
        vboActionRoomRateData.listingIds = listingsInvolved;

        // iterate all listing rows (IDs) involved to build the matrix of rows and cells selected
        listingsInvolved.forEach((listingId) => {
            // start matrix row for the current listing
            let listingCells = [];

            // clone the start date object
            let iterDate = new Date(start);

            // loop through the dates interval
            while (iterDate <= end) {
                // build the current day key in Y-m-d format
                let day_key = VBOCore.formatDate(iterDate, 'Y-m-d');

                // query the desired cell
                let listingDayRateCell = document
                    .querySelector('.vbo-roomrates-row[data-roomid="' + listingId + '"] .vbo-grid-avcell-rates[data-day="' + day_key + '"]');

                if (!listingDayRateCell) {
                    // single-unit listings have a different row class
                    listingDayRateCell = document
                        .querySelector('.vboverviewtablerow[data-roomid="' + listingId + '"] .vbo-grid-avcell-rates[data-day="' + day_key + '"]');
                }

                if (listingDayRateCell) {
                    // push listing cell
                    listingCells.push(listingDayRateCell);
                }

                // go to next day by cloning again the date object
                iterDate = new Date(iterDate.setDate(iterDate.getDate() + 1));
            }

            if (listingCells.length) {
                // push row to matrix for the current listing
                matrix.push(listingCells);
            }
        });

        return matrix;
    };

    /**
     * Register function to handle the click event on a room rate cell.
     */
    const vboActionRoomRateHandleClick = (cell, event) => {
        if (vboActionRoomRateData.end) {
            // selection terminated
            return;
        }

        let day = cell.getAttribute('data-day'), listingId;

        if (!day) {
            return;
        }

        if (cell.matches('.vbo-roomrates-cell-day')) {
            // multi-unit room row cell
            listingId = cell.closest('tr.vbo-roomrates-row').getAttribute('data-roomid');
        } else {
            // single-unit room row cell
            listingId = cell.closest('tr').querySelector('td.roomname').getAttribute('data-roomid');
        }

        if (!listingId) {
            return;
        }

        if (!vboActionRoomRateData.start) {
            // start selection
            let startDate = new Date(day);
            let todayMidnight = new Date().setHours(0, 0, 0, 0);

            if (todayMidnight > startDate) {
                // starting a selection on a past date is forbidden
                return;
            }

            // populate values to start the selection
            vboActionRoomRateData.start = startDate;
            vboActionRoomRateData.listingIdStart = listingId;

            // set cell class
            cell.classList.add('vbo-cell-selected');
            cell.classList.add('vbo-cell-selected-first');

            // abort
            return;
        }

        let endDate = new Date(day);

        if (endDate < vboActionRoomRateData.start) {
            // date in the past clicked, reset selection
            vboActionRoomRateHandleReset();

            // start selection
            vboActionRoomRateData.start = endDate;

            // set cell class
            cell.classList.add('vbo-cell-selected');
            cell.classList.add('vbo-cell-selected-first');

            // abort
            return;
        }

        // stop event propagation
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // terminate the selection
        vboActionRoomRateData.end = endDate;
        vboActionRoomRateData.listingIdEnd = listingId;
        cell.classList.add('vbo-cell-selected');
        cell.classList.add('vbo-cell-selected-last');

        // obtain the listings involved
        let listingsInvolved = [];
        let involvedCellsMatrix = vboActionRoomRateGetCellsMatrix(vboActionRoomRateData.start, vboActionRoomRateData.end, vboActionRoomRateData.listingIdStart, vboActionRoomRateData.listingIdEnd);
        // iterate over all the involved cells to apply the selected class
        involvedCellsMatrix.forEach((listingCells, rowIndex) => {
            // push listing ID involved
            listingsInvolved.push(listingCells[0].closest('tr').getAttribute('data-roomid'));
        });

        // define the modal cancel button
        let cancel_btn = jQuery('<button></button>')
            .attr('type', 'button')
            .addClass('btn')
            .text(<?php echo json_encode(JText::translate('VBANNULLA')); ?>)
            .on('click', () => {
                VBOCore.emitEvent('vbo-overv-setnewrates-dismiss');
            });

        // define the modal apply buttons content
        let apply_btn = jQuery('<button></button>')
            .attr('type', 'button')
            .addClass('btn')
            .addClass('btn-primary')
            .text(<?php echo json_encode(JText::translate('VBAPPLY')); ?>)
            .on('click', () => {
                // apply new rates
                vboActionApplyNewRates();
            });

        let actions_btn = jQuery('<button></button>')
            .attr('type', 'button')
            .addClass('btn')
            .addClass('btn-primary')
            .html('<?php VikBookingIcons::e('ellipsis-h'); ?>');

        // define the context menu for the actions button
        jQuery(actions_btn).vboContextMenu({
            placement: 'top-left',
            buttons: [
                {
                    class: 'vbo-context-menu-entry-secondary',
                    text: <?php echo json_encode(JText::translate('VBAPPLY')); ?>,
                    icon: '<?php echo VikBookingIcons::i('rocket'); ?>',
                    separator: true,
                    action: (root, event) => {
                        // apply new rates
                        vboActionApplyNewRates();
                    },
                },
                {
                    class: 'vbo-context-menu-entry-secondary',
                    text: <?php echo json_encode(JText::translate('VBO_ADD_TO_QUEUE')); ?>,
                    icon: '<?php echo VikBookingIcons::i('stopwatch'); ?>',
                    action: (root, event) => {
                        // queue the rates update request
                        vboActionQueueRatesUpdate();
                    },
                },
            ],
        });

        let apply_btn_content = jQuery('<div></div>')
            .addClass('btn-group')
            .addClass('vbo-context-menu-btn-group')
            .append(apply_btn)
            .append(actions_btn);

        // populate OTA relations and operation details
        vboActionOvervSetAllRoomRelations(listingsInvolved);

        // check if restrictions can be managed
        vboActionVcmRestrictionsSupported();

        // handle modal display with a small delay
        setTimeout(() => {
            // display modal
            let modalBody = VBOCore.displayModal({
                suffix:         'overv_setnewrates_modal',
                title:          <?php echo json_encode(JText::translate('VBRATESOVWSETNEWRATE')); ?>,
                extra_class:    'vbo-modal-rounded vbo-modal-tall',
                body_prepend:   true,
                lock_scroll:    true,
                draggable:      true,
                footer_left:    cancel_btn,
                footer_right:   apply_btn_content,
                loading_event:  'vbo-overv-setnewrates-loading',
                dismiss_event:  'vbo-overv-setnewrates-dismiss',
                progress_event: 'vbo-overv-setnewrates-progress',
                loading_body:  '<?php VikBookingIcons::e('refresh', 'fa-spin fa-3x fa-fw'); ?>',
                onDismiss:      () => {
                    // reset dates selection
                    vboActionRoomRateHandleReset();

                    // reset room-ota relations
                    jQuery('.vbo-roverw-setnewrate-vcm-otas').html('');

                    // move the element back to its original position
                    jQuery('.vbo-overview-action-raterestr-wrap').appendTo('.vbo-overview-action-raterestr-helper');
                },
            });

            jQuery('.vbo-overview-action-raterestr-wrap').appendTo(modalBody);
        }, 100);
    };

    /**
     * Register function to handle the mouseover event on a room rate cell.
     */
    const vboActionRoomRateHandleHover = (cell) => {
        if (!vboActionRoomRateData.start || vboActionRoomRateData.end) {
            // abort when the selection has not been started or has been terminated
            return;
        }

        let day = cell.getAttribute('data-day'), listingId;

        if (!day) {
            return;
        }

        if (cell.matches('.vbo-roomrates-cell-day')) {
            // multi-unit room row cell
            listingId = cell.closest('tr.vbo-roomrates-row').getAttribute('data-roomid');
        } else {
            // single-unit room row cell
            listingId = cell.closest('tr').querySelector('td.roomname').getAttribute('data-roomid');
        }

        if (!listingId) {
            return;
        }

        let dayDate = new Date(day);

        // always delete the selected class from any middle-cell
        document
            .querySelectorAll('.vbo-cell-selected')
            .forEach((prevCell) => {
                // remove the selected class from any middle-cell
                prevCell.classList.remove('vbo-cell-selected');
                prevCell.classList.remove('vbo-cell-selected-last');
                prevCell.classList.remove('vbo-cell-selected-middle-row-down');
                prevCell.classList.remove('vbo-cell-selected-middle-row-up');
                if (!prevCell.classList.contains('vbo-cell-selected-initial')) {
                    prevCell.classList.remove('vbo-cell-selected-first');
                }
            });

        if (dayDate >= vboActionRoomRateData.start) {
            // date in the future hovered
            let involvedCellsMatrix = vboActionRoomRateGetCellsMatrix(vboActionRoomRateData.start, dayDate, vboActionRoomRateData.listingIdStart, listingId);
            // iterate over all the involved cells to apply the selected class
            involvedCellsMatrix.forEach((listingCells, rowIndex) => {
                listingCells.forEach((selectedCell, cellIndex) => {
                    selectedCell.classList.add('vbo-cell-selected');
                    if (!rowIndex && !cellIndex) {
                        // initial cell identified
                        selectedCell.classList.add('vbo-cell-selected-initial');
                    }
                    if (rowIndex > 0 && vboActionRoomRateData.traverseDir) {
                        // middle row-cell, check if downwards or upwards
                        selectedCell.classList.add('vbo-cell-selected-middle-row-' + vboActionRoomRateData.traverseDir);
                    }
                    if (cellIndex == 0) {
                        // first row-cell
                        selectedCell.classList.add('vbo-cell-selected-first');
                    } else if (++cellIndex == listingCells.length) {
                        // last row-cell
                        selectedCell.classList.add('vbo-cell-selected-last');
                    }
                });
            });
        } else {
            // date in the past hovered, remove the rest of the classes
            document
                .querySelectorAll('.vbo-cell-selected-initial')
                .forEach((prevCell) => {
                    // remove the selected class from the initial cell
                    prevCell.classList.remove('vbo-cell-selected-initial');
                    prevCell.classList.remove('vbo-cell-selected-first');
                });
        }
    };

    /**
     * Register function to handle the reset of the room rate cell selections.
     */
    const vboActionRoomRateHandleReset = () => {
        // reset values
        vboActionRoomRateData.start = null;
        vboActionRoomRateData.end = null;
        vboActionRoomRateData.listingIdStart = null;
        vboActionRoomRateData.listingIdEnd = null;
        vboActionRoomRateData.traverseDir = null;
        vboActionRoomRateData.listingIds = [];

        // remove room-rate selected class from cells
        document
            .querySelectorAll('.vbo-cell-selected')
            .forEach((cell) => {
                cell.classList.remove('vbo-cell-selected');
                cell.classList.remove('vbo-cell-selected-first');
                cell.classList.remove('vbo-cell-selected-last');
                cell.classList.remove('vbo-cell-selected-initial');
                cell.classList.remove('vbo-cell-selected-middle-row-down');
                cell.classList.remove('vbo-cell-selected-middle-row-up');
            });
    };

    /**
     * Register function to add event listeners for the room rate cells.
     */
    const vboActionRegisterRoomRateEvents = () => {
        // scan all rate cells
        document
            .querySelectorAll('.vbo-grid-avcell-rates')
            .forEach((rateCell) => {
                // click listener
                rateCell.addEventListener('click', (e) => {
                    if (!e || !e.target) {
                        return;
                    }

                    let element = e.target;

                    if (!element.matches('.vbo-grid-avcell-rates')) {
                        element = element.closest('.vbo-grid-avcell-rates');
                    }

                    vboActionRoomRateHandleClick(element, e);
                });

                // mouseover listener
                rateCell.addEventListener('mouseover', (e) => {
                    if (!e || !e.target) {
                        return;
                    }

                    let element = e.target;

                    if (!element.matches('.vbo-grid-avcell-rates')) {
                        element = element.closest('.vbo-grid-avcell-rates');
                    }

                    vboActionRoomRateHandleHover(element);
                });
            });
    };

    /**
     * Register function to render the CM operation results into HTML.
     * 
     * @param   object  vcm_response    The raw "setnewrates" endpoint response key "vcm".
     * @param   string  listingName     The name of the listing involved.
     * 
     * @return  string
     */
    const vboActionRenderCMResult = (vcm_response, listingName) => {
        if (!Array.isArray(vcm_response)) {
            // make sure the result is a list of result objects
            vcm_response = [vcm_response];
        }

        let htmlres = '';

        vcm_response.forEach((obj) => {
            htmlres += '<div class="vbo-vcm-rates-res-rplan-wrap">';

            if (obj.hasOwnProperty('rplan_name')) {
                htmlres += '<div class="vbo-vcm-rates-res-rplan-data">';
                htmlres += '<strong>' + listingName + ' - ' + obj['rplan_name'] + '</strong>';
                if (obj.hasOwnProperty('is_derived') && obj['is_derived']) {
                    htmlres += ' <span class="label label-info">' + <?php echo json_encode(JText::translate('VBO_IS_DERIVED_RATE')); ?> + '</span>';
                }
                htmlres += '</div>';
            }
            
            if (obj.hasOwnProperty('channels_success')) {
                htmlres += '<div class="vbo-vcm-rates-res-success">';
                for (let ch_id in obj['channels_success']) {
                    htmlres += '<div class="vbo-vcm-rates-res-channel">';
                    htmlres += '    <div class="vbo-vcm-rates-res-channel-esit">';
                    htmlres += '        <i class="<?php echo VikBookingIcons::i('check'); ?>"></i>';
                    htmlres += '    </div>';
                    htmlres += '    <div class="vbo-vcm-rates-res-channel-logo">';
                    if (obj['channels_updated'].hasOwnProperty(ch_id) && obj['channels_updated'][ch_id]['logo'].length) {
                        htmlres += '<img src="'+obj['channels_updated'][ch_id]['logo']+'" />';
                    } else {
                        htmlres += '<span>'+obj['channels_success'][ch_id]+'</span>';
                    }
                    htmlres += '    </div>';
                    htmlres += '</div>';
                }

                if (obj.hasOwnProperty('channels_bkdown')) {
                    htmlres += '<div class="vbo-vcm-rates-res-bkdown">';
                    htmlres += '    <div><pre>'+obj['channels_bkdown']+'</pre></div>';
                    htmlres += '</div>';
                }
                htmlres += '</div>';
            }

            if (obj.hasOwnProperty('channels_warnings')) {
                htmlres += '<div class="vbo-vcm-rates-res-warning">';
                for (let ch_id in obj['channels_warnings']) {
                    htmlres += '<div class="vbo-vcm-rates-res-channel">';
                    htmlres += '    <div class="vbo-vcm-rates-res-channel-esit">';
                    htmlres += '        <i class="<?php echo VikBookingIcons::i('exclamation-triangle'); ?>"></i>';
                    htmlres += '    </div>';
                    htmlres += '    <div class="vbo-vcm-rates-res-channel-logo">';
                    if (obj['channels_updated'].hasOwnProperty(ch_id) && obj['channels_updated'][ch_id]['logo'].length) {
                        htmlres += '<img src="'+obj['channels_updated'][ch_id]['logo']+'" />';
                    } else if (obj['channels_updated'].hasOwnProperty(ch_id)) {
                        htmlres += '<span>'+obj['channels_updated'][ch_id]['name']+'</span>';
                    }
                    htmlres += '    </div>';
                    htmlres += '    <div class="vbo-vcm-rates-res-channel-det">';
                    htmlres += '        <pre>'+obj['channels_warnings'][ch_id]+'</pre>';
                    htmlres += '    </div>';
                    htmlres += '</div>';
                }
                htmlres += '</div>';
            }

            if (obj.hasOwnProperty('channels_errors')) {
                htmlres += '<div class="vbo-vcm-rates-res-error">';
                for (let ch_id in obj['channels_errors']) {
                    htmlres += '<div class="vbo-vcm-rates-res-channel">';
                    htmlres += '    <div class="vbo-vcm-rates-res-channel-esit">';
                    htmlres += '        <i class="<?php echo VikBookingIcons::i('times'); ?>"></i>';
                    htmlres += '    </div>';
                    htmlres += '    <div class="vbo-vcm-rates-res-channel-logo">';
                    if (obj['channels_updated'].hasOwnProperty(ch_id) && obj['channels_updated'][ch_id]['logo'].length) {
                        htmlres += '    <img src="'+obj['channels_updated'][ch_id]['logo']+'" />';
                    } else if (obj['channels_updated'].hasOwnProperty(ch_id)) {
                        htmlres += '    <span>'+obj['channels_updated'][ch_id]['name']+'</span>';
                    }
                    htmlres += '    </div>';
                    htmlres += '    <div class="vbo-vcm-rates-res-channel-det">';
                    htmlres += '        <pre>'+obj['channels_errors'][ch_id]+'</pre>';
                    htmlres += '    </div>';
                    htmlres += '</div>';
                }
                htmlres += '</div>';
            }

            htmlres += '</div>';
        });

        return htmlres;
    };

    /**
     * Register function to apply new rates after a matrix selection.
     */
    const vboActionApplyNewRates = () => {
        let involvedCellsMatrix = vboActionRoomRateGetCellsMatrix(vboActionRoomRateData.start, vboActionRoomRateData.end, vboActionRoomRateData.listingIdStart, vboActionRoomRateData.listingIdEnd);
        let listingsInvolved = [];
        involvedCellsMatrix.forEach((listingCells, rowIndex) => {
            // push listing ID involved
            listingsInvolved.push(listingCells[0].closest('tr').getAttribute('data-roomid'));
        });

        let setrate = parseFloat(document.querySelector('#roverw-newrate').value);
        let setminlos = document.querySelector('#roverw-newrestr').value;
        let invoke_vcm = document.querySelector('input[name="roverw-newrate-vcm"]:checked') ? 1 : 0;

        if (!listingsInvolved.length) {
            alert('No listings to update.');
            return;
        }

        if (isNaN(setrate) || setrate <= 0) {
            alert('Invalid rate amount.');
            return;
        }

        // check the OTA pricing alteration rules, if any
        let ota_pricing = {};
        if (invoke_vcm) {
            // scan all OTA alteration rules, if any
            document.querySelectorAll('.vbo-roverw-setnewrate-ota-pricing-currentvalue[data-alteration]').forEach((elem) => {
                // channel alteration string
                let alter_string = elem.getAttribute('data-alteration');
                if (!alter_string) {
                    alter_string = '';
                }

                // access the parent node to get the OTA channel identifier
                let ota_id = elem
                    .closest('.vbo-roverw-setnewrate-vcm-ota-relation-channel[data-otaid]')
                    .getAttribute('data-otaid');

                if (!ota_id || !alter_string || alter_string == '+0%' || alter_string == '+0*') {
                    // avoid pushing an empty alteration command
                    return;
                }

                // push OTA pricing alteration command
                ota_pricing[ota_id] = alter_string;
            });
        }

        if (!Object.keys(ota_pricing).length) {
            // unset the object for the request
            ota_pricing = null;
        }

        // build the list of update requests (one per listing)
        let requestList = [];
        listingsInvolved.forEach((listingId) => {
            requestList.push({
                id_room: listingId,
                id_price: vboActionRoomRplansMap[listingId],
                rate: setrate,
                vcm: invoke_vcm,
                minlos: setminlos,
                fromdate: VBOCore.formatDate(vboActionRoomRateData.start, 'Y-m-d'),
                todate: VBOCore.formatDate(vboActionRoomRateData.end, 'Y-m-d'),
                rateclosed: 0,
                ota_pricing: ota_pricing,
                skip_derived: 0,
            });
        });

        // count the number of requests
        let requestCount = requestList.length;

        // start the response container for every request
        let responseContainer = [];

        // start loading
        VBOCore.emitEvent('vbo-overv-setnewrates-loading');

        // set progress
        VBOCore.emitEvent('vbo-overv-setnewrates-progress', {
            progress_content: '1 / ' + requestCount,
        });

        // dispatch the rates update requests
        vboActionDispatchRatesUpdateRequests(
            requestList,
            (obj_res, listingName) => {
                // one request was completed, push the result
                responseContainer.push(Object.assign(obj_res, {listingName: listingName}));

                // update progress
                VBOCore.emitEvent('vbo-overv-setnewrates-progress', {
                    progress_content: (requestList.length ? (requestCount - requestList.length + 1) : requestCount) + ' / ' + requestCount,
                });
            },
            () => {
                // process completed
                let showOTAResponse = false;
                let htmlOTAResponse = [];

                // iterate the operation results
                responseContainer.forEach((response) => {
                    if (typeof response !== 'object' || !response.hasOwnProperty('vcm')) {
                        // nothing to say about this response
                        return;
                    }

                    // turn flag on
                    showOTAResponse = true;

                    // build HTML response string and add it to the list
                    htmlOTAResponse.push(vboActionRenderCMResult(response.vcm, response.listingName));
                });

                if (showOTAResponse && htmlOTAResponse.length) {
                    // display the modal with the update operation results
                    VBOCore.displayModal({
                        suffix:      'vbo-vcm-rates-res',
                        extra_class: 'vbo-modal-rounded vbo-modal-tall vbo-modal-nofooter',
                        title:       <?php echo json_encode(JText::translate('VBOVCMRATESRES')); ?>,
                        body:        '<div class="vbo-vcm-rates-res-container' + (htmlOTAResponse.length > 1 ? ' vbo-vcm-ota-multicalendar-response' : '') + '">' + htmlOTAResponse.join("\n") + '</div>',
                        draggable:   true,
                    });
                }

                // update new rates on the involved cells
                involvedCellsMatrix.forEach((listingCells, rowIndex) => {
                    listingCells.forEach((selectedCell, cellIndex) => {
                        let rateAmountEl = selectedCell.querySelector('.vbo-roomrates-cell-rate-amount');
                        if (!rateAmountEl) {
                            // cell is occupied by a reservation, nothing to update
                            return;
                        }
                        rateAmountEl.innerHTML = VBOCore.getCurrency().format(setrate);
                        if (setminlos) {
                            let minlosEl = selectedCell.querySelector('.vbo-roomrates-cell-minlos');
                            minlosEl.innerHTML = '<?php VikBookingIcons::e('moon'); ?> ' + setminlos;
                        }
                        selectedCell.classList.add('vbo-cell-new-update');
                    });
                });

                // dismiss the modal upon completion, after having updated the new rates on the involved cells
                VBOCore.emitEvent('vbo-overv-setnewrates-dismiss');
            },
            (err_mess, unrecoverable) => {
                // stop loading in case of error
                VBOCore.emitEvent('vbo-overv-setnewrates-loading');
            }
        );
    };

    /**
     * Register function to queue a rates update request.
     */
    const vboActionQueueRatesUpdate = () => {
        let involvedCellsMatrix = vboActionRoomRateGetCellsMatrix(vboActionRoomRateData.start, vboActionRoomRateData.end, vboActionRoomRateData.listingIdStart, vboActionRoomRateData.listingIdEnd);
        let listingsInvolved = [];
        involvedCellsMatrix.forEach((listingCells, rowIndex) => {
            // push listing ID involved
            listingsInvolved.push(listingCells[0].closest('tr').getAttribute('data-roomid'));
        });

        let setrate = parseFloat(document.querySelector('#roverw-newrate').value);
        let setminlos = document.querySelector('#roverw-newrestr').value;
        let invoke_vcm = document.querySelector('input[name="roverw-newrate-vcm"]:checked') ? 1 : 0;

        if (!listingsInvolved.length) {
            alert('No listings to update.');
            return;
        }

        if (isNaN(setrate) || setrate <= 0) {
            alert('Invalid rate amount.');
            return;
        }

        // check the OTA pricing alteration rules, if any
        let ota_pricing = {};
        if (invoke_vcm) {
            // scan all OTA alteration rules, if any
            document.querySelectorAll('.vbo-roverw-setnewrate-ota-pricing-currentvalue[data-alteration]').forEach((elem) => {
                // channel alteration string
                let alter_string = elem.getAttribute('data-alteration');
                if (!alter_string) {
                    alter_string = '';
                }

                // access the parent node to get the OTA channel identifier
                let ota_id = elem
                    .closest('.vbo-roverw-setnewrate-vcm-ota-relation-channel[data-otaid]')
                    .getAttribute('data-otaid');

                if (!ota_id || !alter_string || alter_string == '+0%' || alter_string == '+0*') {
                    // avoid pushing an empty alteration command
                    return;
                }

                // push OTA pricing alteration command
                ota_pricing[ota_id] = alter_string;
            });
        }

        if (!Object.keys(ota_pricing).length) {
            // unset the object for the request
            ota_pricing = null;
        }

        // build the list of update requests (one per listing)
        let requestList = [];
        listingsInvolved.forEach((listingId) => {
            requestList.push({
                id_room: listingId,
                id_price: vboActionRoomRplansMap[listingId],
                _roomName: vboActionRoomNames[listingId],
                rate: setrate,
                vcm: invoke_vcm,
                minlos: setminlos,
                fromdate: VBOCore.formatDate(vboActionRoomRateData.start, 'Y-m-d'),
                todate: VBOCore.formatDate(vboActionRoomRateData.end, 'Y-m-d'),
                rateclosed: 0,
                ota_pricing: ota_pricing,
                skip_derived: 0,
            });
        });

        // queue the current requests to the admin-dock as temporary data
        VBOCore.getAdminDock().addTemporaryData(
            {
                id: '_tmp',
                persist_id: 'setnewrates',
                name: <?php echo json_encode(JText::translate('VBO_PENDING_QUEUE')); ?>,
                icon: '<?php VikBookingIcons::e('stopwatch'); ?>',
                style: 'orange',
            },
            requestList,
            (queueData) => {
                // temporary data restored from dock
                vboActionDisplayRatesQueue(queueData);
            },
            (queueData) => {
                // temporary data removed from dock
                document.querySelectorAll('.vbo-cell-pending-update').forEach((pending_cell) => {
                    pending_cell.classList.remove('vbo-cell-pending-update');
                });
            }
        );

        // update new rates on the involved cells
        involvedCellsMatrix.forEach((listingCells, rowIndex) => {
            listingCells.forEach((selectedCell, cellIndex) => {
                let rateAmountEl = selectedCell.querySelector('.vbo-roomrates-cell-rate-amount');
                if (!rateAmountEl) {
                    // cell is occupied by a reservation, nothing to update
                    return;
                }
                rateAmountEl.innerHTML = VBOCore.getCurrency().format(setrate);
                if (setminlos) {
                    let minlosEl = selectedCell.querySelector('.vbo-roomrates-cell-minlos');
                    minlosEl.innerHTML = '<?php VikBookingIcons::e('moon'); ?> ' + setminlos;
                }
                selectedCell.classList.add('vbo-cell-pending-update');
            });
        });

        // dismiss the modal upon completion, after having updated the new rates on the involved cells
        VBOCore.emitEvent('vbo-overv-setnewrates-dismiss');
    };

    /**
     * Displays the information about the current rates queue data.
     * 
     * @param   Array   ratesData   The current queue data.
     */
    const vboActionDisplayRatesQueue = (ratesData) => {
        // define the modal cancel button
        let cancel_btn = jQuery('<button></button>')
            .attr('type', 'button')
            .addClass('btn')
            .text(<?php echo json_encode(JText::translate('VBANNULLA')); ?>)
            .on('click', () => {
                // minimize again the temporary data to the dock by dismissing the modal
                VBOCore.emitEvent('vbo-overv-setnewrates-queue-dismiss');
            });

        // define the modal apply button
        let apply_btn = jQuery('<button></button>')
            .attr('type', 'button')
            .addClass('btn btn-success')
            .html('<?php VikBookingIcons::e('rocket'); ?> ' + <?php echo json_encode(JText::translate('VBAPPLY')); ?>)
            .on('click', function() {
                // count the number of requests
                let requestCount = ratesData.length;

                if (!requestCount) {
                    throw new Error('Rates queue is empty');
                }

                // start the response container for every request
                let responseContainer = [];

                // start loading
                VBOCore.emitEvent('vbo-overv-setnewrates-queue-loading');

                // set progress
                VBOCore.emitEvent('vbo-overv-setnewrates-queue-progress', {
                    progress_content: '1 / ' + requestCount,
                });

                // dispatch the rates update requests
                vboActionDispatchRatesUpdateRequests(
                    ratesData,
                    (obj_res, listingName) => {
                        // one request was completed, push the result
                        responseContainer.push(Object.assign(obj_res, {listingName: listingName}));

                        // update progress
                        VBOCore.emitEvent('vbo-overv-setnewrates-queue-progress', {
                            progress_content: (ratesData.length ? (requestCount - ratesData.length + 1) : requestCount) + ' / ' + requestCount,
                        });
                    },
                    () => {
                        // process completed
                        let showOTAResponse = false;
                        let htmlOTAResponse = [];

                        // iterate the operation results
                        responseContainer.forEach((response) => {
                            if (typeof response !== 'object' || !response.hasOwnProperty('vcm')) {
                                // nothing to say about this response
                                return;
                            }

                            // turn flag on
                            showOTAResponse = true;

                            // build HTML response string and add it to the list
                            htmlOTAResponse.push(vboActionRenderCMResult(response.vcm, response.listingName));
                        });

                        if (showOTAResponse && htmlOTAResponse.length) {
                            // display the modal with the update operation results
                            VBOCore.displayModal({
                                suffix:      'vbo-vcm-rates-res',
                                extra_class: 'vbo-modal-rounded vbo-modal-tall vbo-modal-nofooter',
                                title:       <?php echo json_encode(JText::translate('VBOVCMRATESRES')); ?>,
                                body:        '<div class="vbo-vcm-rates-res-container' + (htmlOTAResponse.length > 1 ? ' vbo-vcm-ota-multicalendar-response' : '') + '">' + htmlOTAResponse.join("\n") + '</div>',
                                draggable:   true,
                            });
                        }

                        // update the involved cells
                        document.querySelectorAll('.vbo-grid-avcell-rates.vbo-cell-pending-update').forEach((pendingCell) => {
                            pendingCell.classList.remove('vbo-cell-pending-update');
                            pendingCell.classList.add('vbo-cell-new-update');
                        });

                        // dismiss the modal upon completion, after having updated the involved cells
                        VBOCore.emitEvent('vbo-overv-setnewrates-queue-dismiss');

                        // at last, dismiss the admin-dock entry upon completion
                        VBOCore.getAdminDock().removeDockElementById('_tmp');
                    },
                    (err_mess, unrecoverable) => {
                        // do nothing in case of error when inside a queue of requests
                    }
                );
            });

        // build modal body
        let modalBodyEl = document.createElement('div');
        modalBodyEl.classList.add('vbo-rates-queue-wrapper');
        ratesData.forEach((updateData) => {
            // create queue update container
            let updateEl = document.createElement('div');
            updateEl.classList.add('vbo-rates-queue-data');

            // contain the listing
            let updateRoomEl = document.createElement('span');
            updateRoomEl.classList.add('vbo-rates-queue-data-listing');
            updateRoomEl.innerText = vboActionRoomNames[updateData.id_room] || updateData._roomName || updateData.id_room;
            updateEl.append(updateRoomEl);

            // contain the dates
            let updateDatesEl = document.createElement('span');
            updateDatesEl.classList.add('vbo-rates-queue-data-dates');
            updateDatesEl.innerText = updateData.fromdate + ' - ' + updateData.todate;
            updateEl.append(updateDatesEl);

            // contain the rate
            let updateRatesEl = document.createElement('span');
            updateRatesEl.classList.add('vbo-rates-queue-data-rate');
            updateRatesEl.innerHTML = VBOCore.getCurrency().format(updateData.rate);
            updateEl.append(updateRatesEl);

            if (updateData.minlos) {
                // contain the minimum stay
                let updateMinlosEl = document.createElement('span');
                updateMinlosEl.classList.add('vbo-rates-queue-data-minlos');
                updateMinlosEl.innerHTML = '<?php VikBookingIcons::e('moon'); ?> ' + updateData.minlos;
                updateEl.append(updateMinlosEl);
            }

            // append queue update container
            modalBodyEl.append(updateEl);
        });

        // display modal
        VBOCore.displayModal({
            suffix:         'overv_setnewrates_queue_modal',
            title:          <?php echo json_encode(JText::translate('VBO_PENDING_QUEUE')); ?>,
            extra_class:    'vbo-modal-rounded vbo-modal-tall',
            body:           modalBodyEl,
            body_prepend:   true,
            lock_scroll:    true,
            draggable:      true,
            footer_left:    cancel_btn,
            footer_right:   apply_btn,
            loading_event:  'vbo-overv-setnewrates-queue-loading',
            dismiss_event:  'vbo-overv-setnewrates-queue-dismiss',
            progress_event: 'vbo-overv-setnewrates-queue-progress',
            loading_body:  '<?php VikBookingIcons::e('refresh', 'fa-spin fa-3x fa-fw'); ?>',
            onDismiss:      () => {
                if (!ratesData.length) {
                    // temporary data queue is empty
                    return;
                }

                // minimize again the temporary data to the dock
                VBOCore.getAdminDock().addTemporaryData(
                    {
                        id: '_tmp',
                        persist_id: 'setnewrates',
                        name: <?php echo json_encode(JText::translate('VBO_PENDING_QUEUE')); ?>,
                        icon: '<?php VikBookingIcons::e('stopwatch'); ?>',
                        style: 'orange',
                    },
                    ratesData,
                    (queueData) => {
                        // temporary data restored from dock
                        vboActionDisplayRatesQueue(queueData);
                    },
                    (queueData) => {
                        // temporary data removed from dock
                        document.querySelectorAll('.vbo-cell-pending-update').forEach((pending_cell) => {
                            pending_cell.classList.remove('vbo-cell-pending-update');
                        });
                    }
                );
            },
        });
    };

    /**
     * Register function to process a list of rates update requests one after the other.
     * 
     * @param   array       requests    List of rates update request objects.
     * @param   function    onProgress  Optional callback when a request is completed.
     * @param   function    onComplete  Optional callback when all requests have been completed.
     * @param   function    onError     Optional callback in case of request error.
     * 
     * @return  void
     */
    const vboActionDispatchRatesUpdateRequests = (requests, onProgress, onComplete, onError) => {
        if (!Array.isArray(requests) || !requests.length) {
            if (typeof onComplete === 'function') {
                onComplete();
            }

            // abort
            return;
        }

        // obtain the request to process
        const request = requests.shift();

        // perform the request
        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=pricing.setnewrates'); ?>",
            request,
            (res) => {
                if (typeof res === 'string' && res.indexOf('e4j.error') === 0) {
                    // display error
                    let err_mess = res.replace('e4j.error.', '');
                    alert(err_mess);

                    if (typeof onError === 'function') {
                        // unrecoverable error
                        onError(err_mess, true);
                    }

                    // abort
                    return;
                }

                try {
                    // check the response
                    let obj_res = typeof res === 'string' ? JSON.parse(res) : res;

                    if (typeof onProgress === 'function') {
                        // call the given function by passing the operation result object and the room name
                        onProgress(obj_res, (vboActionRoomNames[request.id_room] || request.id_room));
                    }

                    // recursively call the same function to process the next request
                    vboActionDispatchRatesUpdateRequests(requests, onProgress, onComplete, onError);
                } catch(err) {
                    // display error
                    alert(err);

                    if (typeof onError === 'function') {
                        // unrecoverable error
                        onError(err, true);
                    }
                }
            },
            (err) => {
                // display error
                let err_mess = err.responseText || 'Request failed due to connection error';
                alert(err_mess);

                if (typeof onError === 'function') {
                    // connection error
                    onError(err_mess, false);
                }
            }
        );
    };

    /**
     * Register function to populate the room ota relations upon selecting a matrix of listings + dates.
     */
    const vboActionOvervSetAllRoomRelations = (listingsInvolved) => {
        // build the current day key in Y-m-d format
        let start_day_key = VBOCore.formatDate(vboActionRoomRateData.start, 'Y-m-d');
        let end_day_key = VBOCore.formatDate(vboActionRoomRateData.end, 'Y-m-d');

        // TODO format dates according to settings

        let listingsText = listingsInvolved.length > 1 ? listingsInvolved.length + ' ' + <?php echo json_encode(JText::translate('VBO_LISTINGS')); ?> : (vboActionRoomNames[listingsInvolved[0]] || listingsInvolved[0]);

        // set operation details first
        jQuery('.vbo-overview-action-raterestr-listings').text(listingsText);
        jQuery('.vbo-overview-action-raterestr-dates').text(start_day_key + ' - ' + end_day_key);

        // the room-ota relations wrapper
        let wrapper = jQuery('.vbo-roverw-setnewrate-vcm-otas');

        // always empty the wrapper
        wrapper.html('');

        // store a list of parsed channel IDs
        let parsedChannelIds = [];

        listingsInvolved.forEach((room_id) => {
            let rplan_id = vboActionRoomRplansMap[room_id];
            if (!room_id || !rplan_id || !vboActionRoomOtaRels.hasOwnProperty(room_id)) {
                // nothing to render for the current listing
                return;
            }

            // start counter
            let ota_ch_counter = 0;

            // build and append room-OTA relations
            for (const ota_name in vboActionRoomOtaRels[room_id]['channels']) {
                // get the current channel ID
                let channel_id = vboActionRoomOtaRels[room_id]['accounts'][ota_ch_counter]['idchannel'];

                if (parsedChannelIds.includes(channel_id)) {
                    // do not display the same channel multiple times
                    return;
                }

                // push channel ID parsed
                parsedChannelIds.push(channel_id);

                // build ota readable name
                let ota_read_name = ota_name;
                ota_read_name = ota_read_name.replace(/api$/, '');
                ota_read_name = ota_read_name.replace(/^(google)(hotel|vr)$/i, '$1 $2');

                // build room-ota relation block and elements
                let ota_block = jQuery('<div></div>');
                ota_block.addClass('vbo-roverw-setnewrate-vcm-ota-relation');

                let ota_block_inner = jQuery('<div></div>');
                ota_block_inner
                    .addClass('vbo-roverw-setnewrate-vcm-ota-relation-pricing')
                    .attr('data-ota', (ota_name + '').toLowerCase());

                let ota_block_channel = jQuery('<div></div>');
                ota_block_channel
                    .addClass('vbo-roverw-setnewrate-vcm-ota-relation-channel')
                    .attr('data-otaid', vboActionRoomOtaRels[room_id]['accounts'][ota_ch_counter]['idchannel'])
                    .append('<img src="' + vboActionRoomOtaRels[room_id]['channels'][ota_name] + '" />')
                    .append('<span>' + ota_read_name + '</span>');

                let ota_pricing_value = jQuery('<span></span>');
                ota_pricing_value
                    .addClass('vbo-roverw-setnewrate-vcm-ota-pricing-startvalue')
                    .html('<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>')
                    .on('click', function() {
                        jQuery(this)
                            .closest('.vbo-roverw-setnewrate-vcm-ota-relation-pricing')
                            .find('.vbo-roverw-setnewrate-vcm-ota-channel-pricing')
                            .toggle();
                    });

                let ota_block_pricing = jQuery('<div></div>');
                ota_block_pricing
                    .addClass('vbo-roverw-setnewrate-vcm-ota-channel-pricing')
                    .css('display', 'none')
                    .append(jQuery('.vbo-roverw-setnewrate-vcm-ota-pricing-alteration').first().clone());

                // register "input" event for select/input elements to control the channel alteration rule overrides
                ota_block_pricing.find('select, input').on('input', function() {
                    let input_elem = jQuery(this);

                    // get the current channel alteration command
                    let ota_alteration_command = input_elem
                        .closest('.vbo-roverw-setnewrate-vcm-ota-relation-pricing')
                        .find('.vbo-roverw-setnewrate-ota-pricing-currentvalue[data-alteration]')
                        .attr('data-alteration');

                    // access alteration rule and input value
                    let rmod_type  = input_elem.attr('data-alter-rule');
                    let rmod_value = input_elem.val();

                    if (!ota_alteration_command || !rmod_type || !(rmod_value + '').length) {
                        return;
                    }

                    // check what pricing factor was changed
                    if (rmod_type == 'rmodsop') {
                        // increase or decrease rate
                        let command_old_val = ota_alteration_command.substr(0, 1);
                        let command_new_val = parseInt(rmod_value) == 1 ? '+' : '-';
                        ota_alteration_command = ota_alteration_command.replace(command_old_val, command_new_val);
                    } else if (rmod_type == 'rmodsamount') {
                        // amount
                        let command_op  = ota_alteration_command.substr(0, 1);
                        let command_val = ota_alteration_command.substr(-1, 1);
                        let command_old_val = ota_alteration_command.replace(command_op, '').replace(command_val, '');
                        let command_new_val = parseFloat(rmod_value);
                        ota_alteration_command = ota_alteration_command.replace(command_old_val, command_new_val);
                    } else if (rmod_type == 'rmodsval') {
                        // percent or absolute
                        let command_old_val = ota_alteration_command.substr(-1, 1);
                        let command_new_val = parseInt(rmod_value) == 1 ? '%' : '*';
                        ota_alteration_command = ota_alteration_command.replace(command_old_val, command_new_val);
                    }

                    // check if the channel requires a specific currency
                    let ota_currency_data = input_elem
                        .closest('.vbo-roverw-setnewrate-vcm-ota-relation-pricing')
                        .find('.vbo-roverw-setnewrate-ota-pricing-willvalue')
                        .attr('data-currency');
                    if (ota_currency_data) {
                        // decode currency data instructions
                        try {
                            ota_currency_data = JSON.parse(ota_currency_data);
                        } catch (e) {
                            ota_currency_data = {};
                        }
                    }

                    // define the current channel alteration string (readable)
                    let ota_alteration_string = ota_alteration_command;

                    // finalize the current channel alteration string (readable)
                    let ota_alteration_val = ota_alteration_string.substr(-1, 1);
                    if (ota_alteration_val != '%') {
                        ota_alteration_string = ota_alteration_string.replace(ota_alteration_val, '') + ((ota_currency_data && ota_currency_data?.symbol ? ota_currency_data.symbol : '') || <?php echo json_encode($currencysymb) ?: '"$"'; ?>);
                    }

                    // update the alteration rule command attribute
                    input_elem
                        .closest('.vbo-roverw-setnewrate-vcm-ota-relation-pricing')
                        .find('.vbo-roverw-setnewrate-ota-pricing-currentvalue[data-alteration]')
                        .attr('data-alteration', ota_alteration_command);

                    // update the alteration rule string tag text
                    input_elem
                        .closest('.vbo-roverw-setnewrate-vcm-ota-relation-pricing')
                        .find('.vbo-roverw-setnewrate-ota-pricing-currentvalue[data-alteration]')
                        .html(ota_alteration_string);

                    // get the current rate to set
                    let current_room_rate = jQuery('#roverw-newrate').val();
                    if (current_room_rate) {
                        // dispatch the event to trigger the re-calculation of the OTA rates
                        VBOCore.emitEvent('vbo-roverv-setnewrate-calc-ota-pricing', {
                            rate: current_room_rate,
                        });
                    }
                });

                // append elements to wrapper
                ota_block_channel.append(ota_pricing_value);
                ota_block_inner.append(ota_block_channel);
                ota_block_inner.append(ota_block_pricing);
                ota_block.append(ota_block_inner);
                wrapper.append(ota_block);

                // increase OTA channel counter
                ota_ch_counter++;
            }
        });

        // obtain the details for the first involved listing
        let firstListingId = listingsInvolved[0];
        let firstRplanId = vboActionRoomRplansMap[listingsInvolved[0]];

        // trigger an AJAX request to load the current alteration rules, if any, for the first listing involved
        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=pricing.loadOtaAlterationRules'); ?>",
            {
                room_id: firstListingId,
                rate_id: firstRplanId,
            },
            (res) => {
                var obj_res = typeof res === 'string' ? JSON.parse(res) : res;
                let alter_room_rates = obj_res['rmod'] == '1' || obj_res['rmod'] == 1;

                // scan all room OTAs
                jQuery('.vbo-roverw-setnewrate-vcm-otas').find('.vbo-roverw-setnewrate-vcm-ota-relation').each(function(key, elem) {
                    // get the current OTA identifier and whether pricing is altered
                    let ota_wrap = jQuery(elem);
                    let ota_id = ota_wrap.find('.vbo-roverw-setnewrate-vcm-ota-relation-channel').attr('data-otaid');
                    let alter_ota_rates = alter_room_rates && obj_res['channels'] && (obj_res['channels'].includes(ota_id) || obj_res['channels'].includes(parseInt(ota_id)));
                    if (!alter_ota_rates && alter_room_rates && obj_res.hasOwnProperty('rmod_channels') && obj_res['rmod_channels'].hasOwnProperty(ota_id)) {
                        alter_ota_rates = true;
                    }

                    // check if the current channel is using a different currency
                    let ota_currency_data = {};
                    if (obj_res.hasOwnProperty('cur_rplans') && obj_res['cur_rplans'].hasOwnProperty(ota_id)) {
                        let ota_check_currency = obj_res['cur_rplans'][ota_id];
                        if (obj_res.hasOwnProperty('currency_data_options') && obj_res['currency_data_options'].hasOwnProperty(ota_check_currency)) {
                            // set custom currency data returned
                            ota_currency_data = obj_res['currency_data_options'][ota_check_currency];
                        }
                    }

                    // build pricing alteration strings
                    let alteration_command = '';
                    let alteration_string  = '';

                    // default alteration factors (no pricing alteration rules)
                    let alter_op = '+';
                    let alter_amount = '0';
                    let alter_val = '%';

                    if (alter_ota_rates) {
                        // check how rates are altered for this channel
                        if (obj_res.hasOwnProperty('rmod_channels') && obj_res['rmod_channels'].hasOwnProperty(ota_id)) {
                            // ota-level pricing alteration rule
                            if (parseInt(obj_res['rmod_channels'][ota_id]['rmod']) == 1) {
                                alter_op = parseInt(obj_res['rmod_channels'][ota_id]['rmodop']) == 1 ? '+' : '-';
                                alter_amount = obj_res['rmod_channels'][ota_id]['rmodamount'];
                                alter_val = parseInt(obj_res['rmod_channels'][ota_id]['rmodval']) == 1 ? '%' : '*';
                            }
                        } else {
                            // room-level pricing alteration rule
                            alter_op = parseInt(obj_res['rmodop']) == 1 ? '+' : '-';
                            alter_amount = obj_res['rmodamount'] || '0';
                            alter_val = parseInt(obj_res['rmodval']) == 1 ? '%' : '*';
                        }
                    }

                    // construct alteration strings
                    alteration_command = alter_op + (alter_amount + '') + (alter_val + '');
                    alteration_string  = alter_op + (alter_amount + '') + (alter_val == '%' ? '%' : (ota_currency_data?.symbol || <?php echo json_encode($currencysymb) ?: '"$"'; ?>));

                    // stop room-ota loading and set alteration string
                    let alteration_elem = jQuery('<span></span>');
                    alteration_elem
                        .addClass('vbo-roverw-setnewrate-ota-pricing-currentvalue')
                        .attr('data-alteration', alteration_command)
                        .html(alteration_string);

                    let will_alter_elem = jQuery('<span></span>').addClass('vbo-roverw-setnewrate-ota-pricing-willvalue');

                    if (ota_currency_data.symbol) {
                        // set currency data object
                        will_alter_elem.attr('data-currency', JSON.stringify(ota_currency_data));
                    }

                    // set elements
                    ota_wrap
                        .find('.vbo-roverw-setnewrate-vcm-ota-pricing-startvalue')
                        .html('')
                        .append(will_alter_elem)
                        .append(alteration_elem)
                        .append('<?php VikBookingIcons::e('edit', 'edit-ota-pricing'); ?>');

                    // populate default values for input element overrides
                    ota_wrap.find('select[data-alter-rule="rmodsop"]').val(alter_op == '+' ? 1 : 0);
                    ota_wrap.find('input[data-alter-rule="rmodsamount"]').val(parseInt(alter_amount) > 0 ? alter_amount : '');
                    ota_wrap.find('select[data-alter-rule="rmodsval"]').val(alter_val == '%' ? 1 : 0);
                });

                // check the current rate value
                let current_room_rate = jQuery('#roverw-newrate').val();
                if (current_room_rate) {
                    // dispatch the event to allow the actual calculation of the OTA rate
                    VBOCore.emitEvent('vbo-roverv-setnewrate-calc-ota-pricing', {
                        rate: current_room_rate,
                        room_id: firstListingId,
                        rate_id: firstRplanId,
                    });
                }
            },
            (err) => {
                alert(err.responseText || 'Request Failed');
            }
        );
    };

    /**
     * Restrictions can be updated only if VCM is available and toggled ON, because
     * the creation and transmission is made through the Connector Class of VCM.
     * On top of that, the OTA pricing alteration rule overrides will toggle a status class.
     */
    const vboActionVcmRestrictionsSupported = () => {
        if (!jQuery('input[name="roverw-newrate-vcm"]').prop('disabled') && jQuery('input[name="roverw-newrate-vcm"]').prop('checked')) {
            jQuery('#roverw-newrestr').val('');
            jQuery('.vbo-roverw-newrestr-wrap').show();
            jQuery('.vbo-roverw-setnewrate-vcm-ota-relation').removeClass('vbo-roverw-setnewrate-vcm-ota-relation-disabled');
        } else {
            jQuery('.vbo-roverw-newrestr-wrap').hide();
            jQuery('.vbo-roverw-setnewrate-vcm-ota-relation').addClass('vbo-roverw-setnewrate-vcm-ota-relation-disabled');
        }
    };

    /**
     * Register function for loading the tasks of a given area/project id.
     * 
     * @param   number      areaId      The area/project ID to render.
     * @param   string      from_date   The Y-m-d starting date.
     * @param   string      to_date     The Y-m-d ending date.
     * @param   Array       room_ids    List of listing IDs to include.
     * @param   function    onComplete  Optional callback to invoke upon completion.
     */
    const vboActionLoadAreaTasks = (areaId, from_date, to_date, room_ids, onComplete) => {
        let ctx_elem = document
            .querySelector('.vbo-context-menu-overview-actions');

        let lbl_elem = ctx_elem
            .querySelector('.vbo-context-menu-lbl');

        let orig_lbl = lbl_elem.innerText;

        // start loading animation
        ctx_elem.loading = 1;
        lbl_elem.innerHTML = '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>';

        // make the request
        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.loadAreaListingTasks'); ?>",
            {
                area_id: areaId,
                room_ids: room_ids,
                from_date: from_date,
                to_date: to_date,
            },
            (resp) => {
                try {
                    // decode the response (if needed), and append the content to the modal body
                    resp = typeof resp === 'string' ? JSON.parse(resp) : resp;

                    // hide any previously loaded area task, if any
                    vboActionHideAreaTasks(areaId);

                    // populate tasks for all listings by scanning all month tables
                    document
                        .querySelectorAll('table.vboverviewtable[data-month-from]')
                        // iterate months
                        .forEach((month) => {
                            // detect if we are using the "old" table layout
                            let isTableLayout = month.classList.contains('vbo-overv-sticky-table-head-on');

                            month
                                .querySelectorAll('.roomname[data-roomid]')
                                // iterate month listings
                                .forEach((roomRowCell) => {
                                    if (roomRowCell.matches('.subroomname')) {
                                        // skip sub-unit rows
                                        return;
                                    }

                                    // get current listing ID
                                    let listingId = roomRowCell.getAttribute('data-roomid');

                                    if (!resp.listings.hasOwnProperty(listingId) && !resp.listingIds.includes(listingId) && !resp.listingIds.includes(parseInt(listingId))) {
                                        if (resp.listingIds.length) {
                                            // no tasks returned for this uneligible room id
                                            return;
                                        }
                                    }

                                    // access the "parent row"
                                    let parentRow = roomRowCell.closest('tr');

                                    // build area-tasks row for the current listing
                                    let areaTasksRow = document.createElement('tr');
                                    areaTasksRow.classList.add('vbo-tm-row');
                                    areaTasksRow.setAttribute('data-roomid', listingId);
                                    areaTasksRow.setAttribute('data-area-id', areaId);

                                    // build first row-cell and append it to the row
                                    let areaTasksCell = document.createElement('td');
                                    areaTasksCell.classList.add('vbo-tm-row-cell-first');
                                    areaTasksCell.innerHTML = resp.area?.icon_class ? '<i class="' + resp.area.icon_class + '"></i> ' : '';
                                    let areaNameEl = document.createElement('span');
                                    areaNameEl.classList.add('vbo-tm-row-area-name');
                                    areaNameEl.innerText = resp.area.name;
                                    areaTasksCell.append(areaNameEl);
                                    areaTasksRow.append(areaTasksCell);

                                    // iterate over the "parent row" day cells of the current listing and month
                                    parentRow
                                        .querySelectorAll('td.vbo-grid-avcell[data-day]')
                                        // iterate month listing days
                                        .forEach((roomRowDay) => {
                                            // get the day Y-m-d value
                                            let ymd = roomRowDay.getAttribute('data-day');

                                            // build month-day cell with pricing details
                                            let tasksDayCell = document.createElement('td');
                                            tasksDayCell.classList.add('vbo-tm-row-cell-day');
                                            tasksDayCell.setAttribute('data-day', ymd);

                                            // access the tasks information for this day
                                            if (resp.listings.hasOwnProperty(listingId) && resp.listings[listingId].hasOwnProperty(ymd)) {
                                                // iterate all tasks for the current listing and day
                                                let totTasks = resp.listings[listingId][ymd].length;
                                                resp.listings[listingId][ymd].forEach((task, index) => {
                                                    if (index > 2) {
                                                        // no more tasks to display for the current listing and day
                                                        return;
                                                    }

                                                    // build task element
                                                    let taskEl = document.createElement('div');
                                                    taskEl.classList.add('vbo-tm-row-cell-task');
                                                    taskEl.setAttribute('data-task-id', task.id);
                                                    taskEl.setAttribute('data-area-id', task.area_id);
                                                    taskEl.setAttribute('data-task-bid', (task.bid + ''));
                                                    if (task.color) {
                                                        taskEl.classList.add('vbo-tm-color');
                                                        taskEl.classList.add(task.color);
                                                    }
                                                    if (isTableLayout) {
                                                        taskEl.classList.add('vbo-tm-row-cell-task-notitle');
                                                        taskEl.innerHTML = '&nbsp;';
                                                    } else {
                                                        taskEl.innerText = task.title;
                                                    }

                                                    // append task element to current cell
                                                    tasksDayCell.append(taskEl);

                                                    if ((index + 1) > 2 && totTasks > (2 + 1)) {
                                                        // limit the number of tasks per listing per day by displaying a link to the TM view for this day
                                                        let exceeding = totTasks - (index + 1);
                                                        let moreTasksTxt = '+' + exceeding + ' ' + (exceeding > 1 ? <?php echo json_encode(JText::translate('VBO_TASKS')); ?> : <?php echo json_encode(JText::translate('VBO_TASK')); ?>).toLowerCase();

                                                        // build element
                                                        let moreTasksEl = document.createElement('div');
                                                        moreTasksEl.classList.add('vbo-tm-calendar-month-day-more')
                                                        moreTasksEl.setAttribute('data-day', ymd);
                                                        moreTasksEl.innerText = moreTasksTxt;
                                                        moreTasksEl.addEventListener('click', () => {
                                                            window.location.href = '<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&view=taskmanager&mode=calendar&filters[calendar_type]=day&filters[calendar_day]=%s', false); ?>'.replace('%s', ymd);
                                                        });

                                                        // append element to current cell
                                                        tasksDayCell.append(moreTasksEl);

                                                        return;
                                                    }
                                                });
                                            }

                                            // append tasks cell to main area-tasks row
                                            areaTasksRow.append(tasksDayCell);
                                        });

                                    // append the main area-tasks row to the DOM
                                    parentRow.insertAdjacentElement('afterend', areaTasksRow);
                                });
                        });

                    // iterating completed, stop loading
                    lbl_elem.innerHTML = '';
                    lbl_elem.innerText = orig_lbl;
                    ctx_elem.loading = 0;

                    // register events for the area tasks
                    vboActionRegisterAreaTaskEvents(areaId);

                    if (typeof onComplete === 'function') {
                        // invoke the callback upon completion
                        onComplete(areaId);
                    }
                } catch (err) {
                    console.error('Error decoding the response', err, resp);
                }
            },
            (error) => {
                // display error message
                alert(error.responseText);

                // stop loading
                lbl_elem.innerHTML = '';
                lbl_elem.innerText = orig_lbl;
                ctx_elem.loading = 0;
            }
        );
    };

    /**
     * Register function to process a list of area/project IDs for which the tasks should be loaded.
     * 
     * @param   Array       areaIds     The list of area/project IDs to process.
     * @param   string      from_date   The Y-m-d starting date.
     * @param   string      to_date     The Y-m-d ending date.
     * @param   Array       room_ids    List of listing IDs to include.
     * @param   function    onProgress  Function to invoke upon progressing to a next request.
     */
    const vboActionDispatchAreasLoading = (areaIds, from_date, to_date, room_ids, onProgress) => {
        if (!Array.isArray(areaIds) || !areaIds.length) {
            // abort when the queue is exhausted
            return;
        }

        // obtain the area ID to process
        const areaId = areaIds.shift();

        // load the tasks for the current area/project ID
        vboActionLoadAreaTasks(areaId, from_date, to_date, room_ids, (areaProcessed) => {
            if (typeof onProgress === 'function') {
                // request completed, invoke the callback upon progressing to the next request to process
                onProgress(areaProcessed);
            }

            // perform a recursive call for the next area/project ID to process, if any
            vboActionDispatchAreasLoading(areaIds, from_date, to_date, room_ids, onProgress);
        });
    };

    /**
     * Register function to hide the tasks of a given area/project id.
     */
    const vboActionHideAreaTasks = (areaId) => {
        document
            .querySelectorAll('.vbo-tm-row[data-area-id="' + areaId + '"]')
            .forEach((row) => {
                row.remove();
            });
    };

    /**
     * Register function to add event listeners for the tasks of a given area (edit task, new task, hover task).
     */
    const vboActionRegisterAreaTaskEvents = (areaId) => {
        // edit and hover existing tasks
        document
            .querySelectorAll('.vbo-tm-row-cell-task')
            .forEach((taskElement) => {
                if (taskElement.clickListener) {
                    // listener added already
                    return;
                }

                // get the clicked task, area and booking IDs
                const taskId = taskElement.getAttribute('data-task-id');
                const areaId = taskElement.getAttribute('data-area-id');
                const taskBid = taskElement.getAttribute('data-task-bid');

                // define the click event for editing the task
                taskElement.addEventListener('click', () => {
                    // define the modal delete button
                    let delete_btn = jQuery('<button></button>')
                        .attr('type', 'button')
                        .addClass('btn btn-danger')
                        .text(<?php echo json_encode(JText::translate('VBELIMINA')); ?>)
                        .on('click', function() {
                            if (!confirm(<?php echo json_encode(JText::translate('VBDELCONFIRM')); ?>)) {
                                return false;
                            }

                            // disable button to prevent double submissions
                            let submit_btn = jQuery(this);
                            submit_btn.prop('disabled', true);

                            // start loading animation
                            VBOCore.emitEvent('vbo-tm-edittask-loading');

                            // make the request
                            VBOCore.doAjax(
                                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.deleteTask'); ?>",
                                {
                                    data: {
                                        id: taskId,
                                    },
                                },
                                (resp) => {
                                    // parse all context menu buttons to identify the current area/project id
                                    jQuery('.vbo-context-menu-overview-actions').vboContextMenu('buttons').forEach((btn) => {
                                        if (btn.areaId == areaId) {
                                            btn.activeState = false;
                                            // trigger action to reload the area/project tasks
                                            btn.action();
                                        }
                                    });

                                    // dismiss the modal
                                    VBOCore.emitEvent('vbo-tm-edittask-dismiss');
                                },
                                (error) => {
                                    // display error message
                                    alert(error.responseText);

                                    // re-enable submit button
                                    submit_btn.prop('disabled', false);

                                    // stop loading
                                    VBOCore.emitEvent('vbo-tm-edittask-loading');
                                }
                            );
                        });

                    // define the modal save button
                    let save_btn = jQuery('<button></button>')
                        .attr('type', 'button')
                        .addClass('btn btn-success')
                        .text(<?php echo json_encode(JText::translate('VBSAVE')); ?>)
                        .on('click', function() {
                            // disable button to prevent double submissions
                            let submit_btn = jQuery(this);
                            submit_btn.prop('disabled', true);

                            // start loading animation
                            VBOCore.emitEvent('vbo-tm-edittask-loading');

                            // get form data
                            const taskForm = new FormData(document.querySelector('#vbo-tm-task-manage-form'));

                            // build query parameters for the request
                            let qpRequest = new URLSearchParams(taskForm);

                            // make sure the request always includes the assignees query parameter, even if the list is empty
                            if (!qpRequest.has('data[assignees][]')) {
                                qpRequest.append('data[assignees][]', []);
                            }

                            // make sure the request always includes the tags query parameter, even if the list is empty
                            if (!qpRequest.has('data[tags][]')) {
                                qpRequest.append('data[tags][]', []);
                            }

                            // make the request
                            VBOCore.doAjax(
                                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.updateTask'); ?>",
                                qpRequest.toString(),
                                (resp) => {
                                    // parse all context menu buttons to identify the current area/project id
                                    jQuery('.vbo-context-menu-overview-actions').vboContextMenu('buttons').forEach((btn) => {
                                        if (btn.areaId == areaId) {
                                            btn.activeState = false;
                                            // trigger action to reload the area/project tasks
                                            btn.action();
                                        }
                                    });

                                    // dismiss the modal
                                    VBOCore.emitEvent('vbo-tm-edittask-dismiss');
                                },
                                (error) => {
                                    // display error message
                                    alert(error.responseText);

                                    // re-enable submit button
                                    submit_btn.prop('disabled', false);

                                    // stop loading
                                    VBOCore.emitEvent('vbo-tm-edittask-loading');
                                }
                            );
                        });

                    // display modal
                    let modalBody = VBOCore.displayModal({
                        suffix:         'tm_edittask_modal',
                        title:          <?php echo json_encode(JText::translate('VBO_TASK')); ?> + ' #' + taskId,
                        extra_class:    'vbo-modal-rounded vbo-modal-taller vbo-modal-large',
                        body_prepend:   true,
                        lock_scroll:    true,
                        escape_dismiss: false,
                        footer_left:    delete_btn,
                        footer_right:   save_btn,
                        loading_event:  'vbo-tm-edittask-loading',
                        dismiss_event:  'vbo-tm-edittask-dismiss',
                    });

                    // start loading animation
                    VBOCore.emitEvent('vbo-tm-edittask-loading');

                    // make the request
                    VBOCore.doAjax(
                        "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.renderLayout'); ?>",
                        {
                            type: 'tasks.managetask',
                            data: {
                                task_id: taskId,
                                area_id: areaId,
                                form_id: 'vbo-tm-task-manage-form',
                            },
                        },
                        (resp) => {
                            // stop loading
                            VBOCore.emitEvent('vbo-tm-edittask-loading');

                            try {
                                // decode the response (if needed), and append the content to the modal body
                                let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;
                                modalBody.append(obj_res['html']);
                            } catch (err) {
                                console.error('Error decoding the response', err, resp);
                            }
                        },
                        (error) => {
                            // display error message
                            alert(error.responseText);

                            // stop loading
                            VBOCore.emitEvent('vbo-tm-edittask-loading');
                        }
                    );
                });

                // define the mouseover/mouseout events for the current task
                if (taskBid) {
                    // highlight the booking(s) assigned to the current task when hovered
                    taskElement.addEventListener('mouseover', () => {
                        // highlight all cells for this booking
                        let bidSnakeElements = document.querySelectorAll('td.vbo-grid-avcell[data-bids*="-' + taskBid + '-"] > .vbo-tableaux-booking');
                        bidSnakeElements.forEach((snake, index) => {
                            if (snake.matches('.vbo-tableaux-booking-checkout')) {
                                // this must be a check-out snake on the check-in date of the desired booking snake
                                return;
                            }
                            // add the highlight class
                            snake.classList.add('vbo-tableaux-booking-task-highlight');
                            if (++index == bidSnakeElements.length && !snake.matches('.vbo-tableaux-booking-checkout')) {
                                // look for the check-out snake for the same booking
                                let parentCell = snake.closest('td').nextElementSibling;
                                if (parentCell && parentCell.querySelector('.vbo-tableaux-booking-checkout')) {
                                    // add the highlight class also to the check-out snake
                                    parentCell
                                        .querySelector('.vbo-tableaux-booking-checkout')
                                        .classList
                                        .add('vbo-tableaux-booking-task-highlight');
                                }
                            }
                        });
                    });

                    // un-highlight the booking(s) assigned to the current task when mouse goes out
                    taskElement.addEventListener('mouseout', () => {
                        document
                            .querySelectorAll('.vbo-tableaux-booking-task-highlight')
                            .forEach((el) => {
                                el.classList.remove('vbo-tableaux-booking-task-highlight');
                            });
                    });
                }

                // turn flag on for listener set
                taskElement.clickListener = true;
            });

        // create new tasks
        document
            .querySelectorAll('.vbo-tm-row[data-area-id="' + areaId + '"] .vbo-tm-row-cell-day')
            .forEach((tmCell) => {
                const listingId = tmCell.closest('tr').getAttribute('data-roomid');
                const day = tmCell.getAttribute('data-day');

                tmCell.addEventListener('click', (e) => {
                    if (e.target && (e.target.matches('.vbo-tm-row-cell-task') || e.target.parentNode.matches('.vbo-tm-row-cell-task') || e.target.matches('.vbo-tm-calendar-month-day-more'))) {
                        // an existing task for this cell was clicked (or the "see more" element), so we abort the process
                        return;
                    }

                    // define the modal cancel button
                    let cancel_btn = jQuery('<button></button>')
                        .attr('type', 'button')
                        .addClass('btn')
                        .text(<?php echo json_encode(JText::translate('VBANNULLA')); ?>)
                        .on('click', () => {
                            VBOCore.emitEvent('vbo-tm-newtask-dismiss');
                        });

                    // define the modal save button
                    let save_btn = jQuery('<button></button>')
                        .attr('type', 'button')
                        .addClass('btn btn-success')
                        .text(<?php echo json_encode(JText::translate('VBSAVE')); ?>)
                        .on('click', function() {
                            // disable button to prevent double submissions
                            let submit_btn = jQuery(this);
                            submit_btn.prop('disabled', true);

                            // start loading animation
                            VBOCore.emitEvent('vbo-tm-newtask-loading');

                            // get form data
                            const taskForm = new FormData(document.querySelector('#vbo-tm-task-manage-form'));

                            // build query parameters for the request
                            let qpRequest = new URLSearchParams(taskForm).toString();

                            // make the request
                            VBOCore.doAjax(
                                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.createTask'); ?>",
                                qpRequest,
                                (resp) => {
                                    // parse all context menu buttons to identify the current area/project id
                                    jQuery('.vbo-context-menu-overview-actions').vboContextMenu('buttons').forEach((btn) => {
                                        if (btn.areaId == areaId) {
                                            btn.activeState = false;
                                            // trigger action to reload the area/project tasks
                                            btn.action();
                                        }
                                    });

                                    // dismiss the modal
                                    VBOCore.emitEvent('vbo-tm-newtask-dismiss');
                                },
                                (error) => {
                                    // display error message
                                    alert(error.responseText);

                                    // re-enable submit button
                                    submit_btn.prop('disabled', false);

                                    // stop loading
                                    VBOCore.emitEvent('vbo-tm-newtask-loading');
                                }
                            );
                        });

                    // display modal
                    let modalBody = VBOCore.displayModal({
                        suffix:         'tm_newtask_modal',
                        title:          <?php echo json_encode(JText::translate('VBO_NEW_TASK')); ?>,
                        extra_class:    'vbo-modal-rounded vbo-modal-taller vbo-modal-large',
                        body_prepend:   true,
                        lock_scroll:    true,
                        escape_dismiss: false,
                        footer_left:    cancel_btn,
                        footer_right:   save_btn,
                        loading_event:  'vbo-tm-newtask-loading',
                        dismiss_event:  'vbo-tm-newtask-dismiss',
                    });

                    // start loading animation
                    VBOCore.emitEvent('vbo-tm-newtask-loading');

                    // make the request
                    VBOCore.doAjax(
                        "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.renderLayout'); ?>",
                        {
                            type: 'tasks.managetask',
                            data: {
                                area_id: areaId,
                                form_id: 'vbo-tm-task-manage-form',
                                filters: {
                                    calendar_day: day,
                                    id_room: listingId,
                                },
                            },
                        },
                        (resp) => {
                            // stop loading
                            VBOCore.emitEvent('vbo-tm-newtask-loading');

                            try {
                                // decode the response (if needed), and append the content to the modal body
                                let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;
                                modalBody.append(obj_res['html']);
                            } catch (err) {
                                console.error('Error decoding the response', err, resp);
                            }
                        },
                        (error) => {
                            // display error message
                            alert(error.responseText);

                            // stop loading
                            VBOCore.emitEvent('vbo-tm-newtask-loading');
                        }
                    );
                });
            });
    };

    jQuery(function() {

        // all task areas
        const taskAreas = <?php echo json_encode($taskAreas); ?>;

        // configure the currency object
        VBOCore.getCurrency({
            symbol:     <?php echo json_encode($currencysymb) ?: '"$"'; ?>,
            digits:     <?php echo intval($currency_digits); ?>,
            decimals:   <?php echo json_encode($currency_decimals) ?: '"."'; ?>,
            thousands:  <?php echo json_encode($currency_thousands) ?: '","'; ?>,
            noDecimals: 1,
        });

        // build buttons
        let btns = [
            {
                class: 'btngroup',
                text: <?php echo json_encode(JText::translate('VBMENUFARES')); ?>,
                disabled: true,
            },
            {
                activeState: false,
                separator: (taskAreas.length > 0),
                class: 'vbo-context-menu-entry-secondary',
                text: <?php echo json_encode(JText::translate('VBO_RATES_AND_RESTR')); ?>,
                icon: () => {
                    return this.activeState ? '<?php echo VikBookingIcons::i('toggle-on'); ?>' : '<?php echo VikBookingIcons::i('toggle-off'); ?>';
                },
                action: (root, event) => {
                    // get all month tables
                    let monthTables = document.querySelectorAll('table.vboverviewtable[data-month-from]');
                    if (!monthTables.length) {
                        return;
                    }

                    // get the date bounds
                    let from_date = monthTables[0].getAttribute('data-month-from');
                    let to_date = monthTables[(monthTables.length - 1)].getAttribute('data-month-to');

                    // get all listing IDs from the first table
                    let listingIds = [];
                    monthTables[0]
                        .querySelectorAll('.roomname[data-roomid]')
                        .forEach((roomRowCell) => {
                            if (roomRowCell.matches('.subroomname')) {
                                // skip sub-unit rows
                                return;
                            }
                            listingIds.push(roomRowCell.getAttribute('data-roomid'));
                        });

                    // toggle state
                    this.activeState = !this.activeState;

                    if (this.activeState) {
                        // load room rates
                        vboActionLoadRoomRates(from_date, to_date, listingIds);
                    } else {
                        // hide room rates
                        vboActionHideRoomRates();
                    }
                },
                disabled: () => {
                    let ctx_elem = document
                        .querySelector('.vbo-context-menu-overview-actions');

                    return <?php echo !$vbo_auth_pricing ? 'true' : 'false'; ?> || ctx_elem?.loading == 1;
                },
            },
        ];

        if (taskAreas.length) {
            // push group button
            btns.push({
                class: 'btngroup',
                text: <?php echo json_encode(JText::translate('VBO_TASK_MANAGER')); ?>,
                disabled: true,
            });

            taskAreas.forEach((area, index) => {
                // push area/project button
                btns.push({
                    activeState: false,
                    areaId: area.id,
                    class: 'vbo-context-menu-entry-secondary',
                    text: area.name,
                    separator: false,
                    icon: function() {
                        return this.activeState === true ? '<?php echo VikBookingIcons::i('check-square'); ?>' : '<?php echo VikBookingIcons::i('far fa-square'); ?>';
                    },
                    action: function(root, event) {
                        // get all month tables
                        let monthTables = document.querySelectorAll('table.vboverviewtable[data-month-from]');
                        if (!monthTables.length) {
                            return;
                        }

                        // get the date bounds
                        let from_date = monthTables[0].getAttribute('data-month-from');
                        let to_date = monthTables[(monthTables.length - 1)].getAttribute('data-month-to');

                        // get all listing IDs from the first table
                        let listingIds = [];
                        monthTables[0]
                            .querySelectorAll('.roomname[data-roomid]')
                            .forEach((roomRowCell) => {
                                if (roomRowCell.matches('.subroomname')) {
                                    // skip sub-unit rows
                                    return;
                                }
                                listingIds.push(roomRowCell.getAttribute('data-roomid'));
                            });

                        // toggle active state
                        this.activeState = !this.activeState;

                        if (this.activeState) {
                            // render tasks for the selected area
                            vboActionLoadAreaTasks(this.areaId, from_date, to_date, listingIds);
                        } else {
                            // hide tasks for the selected area
                            vboActionHideAreaTasks(this.areaId);
                        }
                    },
                    disabled: () => {
                        let ctx_elem = document
                            .querySelector('.vbo-context-menu-overview-actions');

                        return <?php echo !$vbo_auth_pms ? 'true' : 'false'; ?> || ctx_elem?.loading == 1;
                    },
                });
            });
        }

        // start context menu on the proper button element
        jQuery('.vbo-context-menu-overview-actions').vboContextMenu({
            placement: 'bottom-left',
            buttons: btns,
        });

        // register listener to reset the room-rate selection upon Esc keyup event
        window.addEventListener('keyup', (e) => {
            if (!e.key || e.key != 'Escape') {
                return;
            }

            if (vboActionRoomRateData.start) {
                // reset selection
                vboActionRoomRateHandleReset();
            }
        });

        // register listener for the "input" event on the "set new rate" input field of type number
        document.querySelector('#roverw-newrate').addEventListener('input', VBOCore.debounceEvent((e) => {
            // dispatch the event to calculate the new OTA pricing value
            VBOCore.emitEvent('vbo-roverv-setnewrate-calc-ota-pricing', {rate: e.target.value});
        }, 200));

        // register listener for when a new rate is set to update what will be the OTA pricing value
        document.addEventListener('vbo-roverv-setnewrate-calc-ota-pricing', VBOCore.debounceEvent((e) => {
            if (!e || !e.detail || !e.detail.rate) {
                // invalid event data
                return;
            }

            // get the new PMS rate
            let rate_amount = parseFloat(e.detail.rate);

            // access the currency object
            let currencyObj = VBOCore.getCurrency();

            // scan all OTA alteration rules, if any
            document.querySelectorAll('.vbo-roverw-setnewrate-ota-pricing-currentvalue[data-alteration]').forEach((elem) => {
                // channel alteration string
                let alter_string = elem.getAttribute('data-alteration');
                if (!alter_string) {
                    alter_string = '+0%';
                }

                // default alteration factors (no pricing alteration rules)
                let alter_op = alter_string.substr(0, 1);
                let alter_val = alter_string.substr(-1, 1);
                let alter_amount = parseFloat(alter_string.replace(alter_op, '').replace(alter_val, ''));

                // calculate what the rate will be on the OTA
                let ota_rate_amount = rate_amount;

                if (!isNaN(alter_amount) && Math.abs(alter_amount) > 0) {
                    if (alter_op == '+') {
                        // increase rate
                        if (alter_val == '%') {
                            // percent
                            let amount_inc = currencyObj.multiply(alter_amount, 0.01);
                            amount_inc = currencyObj.multiply(rate_amount, amount_inc);
                            ota_rate_amount = currencyObj.sum(rate_amount, amount_inc);
                        } else {
                            // absolute
                            ota_rate_amount = currencyObj.sum(rate_amount, alter_amount);
                        }
                    } else {
                        // discount rate
                        if (alter_val == '%') {
                            // percent
                            let amount_inc = currencyObj.multiply(alter_amount, 0.01);
                            amount_inc = currencyObj.multiply(rate_amount, amount_inc);
                            ota_rate_amount = currencyObj.diff(rate_amount, amount_inc);
                        } else {
                            // absolute
                            ota_rate_amount = currencyObj.diff(rate_amount, alter_amount);
                        }
                    }
                }

                // get the element containing the calculated ota pricing
                let will_alter_elem = elem
                    .closest('.vbo-roverw-setnewrate-vcm-ota-pricing-startvalue')
                    .querySelector('.vbo-roverw-setnewrate-ota-pricing-willvalue');

                // define the currency options
                let ota_currency_options = {};

                // check if the channel requires a specific currency
                let ota_currency_data = will_alter_elem.getAttribute('data-currency');
                if (ota_currency_data) {
                    // decode currency data instructions
                    try {
                        ota_currency_data = JSON.parse(ota_currency_data);
                    } catch (e) {
                        ota_currency_data = {};
                    }

                    // set custom currency options
                    if (ota_currency_data['symbol']) {
                        ota_currency_options['symbol'] = ota_currency_data['symbol'];
                    }
                    if (ota_currency_data['decimals']) {
                        ota_currency_options['digits'] = ota_currency_data['decimals'];
                    }
                    if (ota_currency_data['decimals_sep']) {
                        ota_currency_options['decimals'] = ota_currency_data['decimals_sep'];
                    }
                    if (ota_currency_data['thousands_sep']) {
                        ota_currency_options['thousands'] = ota_currency_data['thousands_sep'];
                    }
                }

                // set calculated OTA rate value
                will_alter_elem.innerHTML = currencyObj.format(ota_rate_amount, ota_currency_options);
            });
        }, 200));

    <?php
    // check if some area/project IDs should be rendered
    if ($activeAreas) {
        ?>
        // get all month tables to obtain the date bounds and the listing IDs
        let from_date, to_date;
        let listingIds = [];
        let activeAreas = <?php echo json_encode($activeAreas); ?>;
        let monthTables = document.querySelectorAll('table.vboverviewtable[data-month-from]');

        if (monthTables.length) {
            // get the date bounds
            from_date = monthTables[0].getAttribute('data-month-from');
            to_date = monthTables[(monthTables.length - 1)].getAttribute('data-month-to');
            // get all listing IDs from the first table
            monthTables[0]
                .querySelectorAll('.roomname[data-roomid]')
                .forEach((roomRowCell) => {
                    if (roomRowCell.matches('.subroomname')) {
                        // skip sub-unit rows
                        return;
                    }
                    listingIds.push(roomRowCell.getAttribute('data-roomid'));
                });
        }

        if (listingIds.length) {
            // dispatch consequent requests, one for each active area ID
            vboActionDispatchAreasLoading(activeAreas, from_date, to_date, listingIds, (areaDisplayed) => {
                // parse all context menu buttons to identify the current area/project id
                jQuery('.vbo-context-menu-overview-actions').vboContextMenu('buttons').forEach((btn) => {
                    if (btn.areaId == areaDisplayed) {
                        // enable area/project active state
                        btn.activeState = true;
                    }
                });
            });
        }
        <?php
    }
    ?>

    });
</script>

<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      E4J srl
 * @copyright   Copyright (C) 2026 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Include the VBOCore JS class.
 */
VikBooking::getVboApplication()->loadCoreJS();

// slider libraries
$document = JFactory::getDocument();
if (VikBooking::loadJquery()) {
    //JHtml::fetch('jquery.framework', true, true);
    JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-1.12.4.min.js');
}
$document->addStyleSheet(VBO_SITE_URI . 'resources/vik-dots-slider.css');
JHtml::fetch('script', VBO_SITE_URI . 'resources/vik-dots-slider.js');

// quote valid until date object
$validUntilDt = !empty($this->quoteData->valid_until) ? JFactory::getDate($this->quoteData->valid_until) : null;

// whether the quote is expired
$isExpired = $validUntilDt && $validUntilDt < JFactory::getDate('now') ? true : false;

// currency symbol
$currencysymb = VikBooking::getCurrencySymb();

if (!$this->quoteData) {
    ?>
<p class="err"><?php echo JText::translate('VBO_QUOTE_ERR_NOTFOUND'); ?></p>
    <?php
    // abort
    return;
}

if ($isExpired) {
    ?>
<p class="err"><?php echo JText::translate('VBO_QUOTE_ERR_EXPIRED'); ?></p>
    <?php
    // abort
    return;
}

// count confirmed, pending, cancelled and overall quote booking solutions
$confirmedBookings = array_values(array_filter($this->quoteData->solutions, function($quoteSolution) {
    return ($quoteSolution->status ?? '') == 'confirmed';
}));
$pendingBookings = array_values(array_filter($this->quoteData->solutions, function($quoteSolution) {
    return ($quoteSolution->status ?? '') == 'standby';
}));
$cancelledBookings = array_values(array_filter($this->quoteData->solutions, function($quoteSolution) {
    return ($quoteSolution->status ?? '') == 'cancelled';
}));
$overallBookings = count($this->quoteData->solutions);

if (!$overallBookings || count($cancelledBookings) == $overallBookings) {
    // quote has been cancelled completely, raise an error
    ?>
<p class="err"><?php echo JText::translate('VBO_QUOTE_ERR_CANCELLED'); ?></p>
    <?php
    // abort
    return;
}

// tell if the quote has been confirmed
$isConfirmed = $confirmedBookings ? true : false;

// always filter out cancelled booking solutions
$this->quoteData->solutions = array_values(array_filter($this->quoteData->solutions, function($quoteSolution) {
    return ($quoteSolution->status ?? '') != 'cancelled';
}));

// route room-detail URLs only once
$roomRoutedUris = array_combine(array_keys($this->roomsData), array_values(array_map(function($roomId) {
    return VikBooking::externalroute('index.php?option=com_vikbooking&view=roomdetails&roomid=' . $roomId);
}, array_keys($this->roomsData))));

// rate plan meals map
$rate_plan_meals_map = [
    'breakfast' => JText::translate('VBO_MEAL_BREAKFAST'),
    'lunch'     => JText::translate('VBO_MEAL_LUNCH'),
    'dinner'    => JText::translate('VBO_MEAL_DINNER'),
];

?>

<div class="vbo-quote-container">

    <div class="vbo-quote-head">
        <div class="vbo-quote-head-info">
            <div class="vbo-quote-head-preinfo">
                <div class="vbo-quote-head-id"><?php echo JText::translate('VBO_QUOTE'); ?> #<span><?php echo $this->quoteData->uuid; ?></span></div>
            </div>
            <h3 class="vbo-quote-head-header"><?php echo trim(sprintf('%s %s', (string) $this->quoteData->first_name, (string) $this->quoteData->last_name)); ?></h3>
            <p class="vbo-quote-head-description"><?php echo nl2br((string) $this->quoteData->notes); ?></p>
        </div>
    <?php
    if (!$isConfirmed && !empty($this->quoteData->valid_until)) {
        // calculate remaining time in days/hours/minutes
        $remainingPeriodDt = $validUntilDt->diff(JFactory::getDate('now'));
        $daysLeft = (int) ($remainingPeriodDt->days ?? 0);
        $hoursLeft = (int) ($remainingPeriodDt->h ?? 0);
        $minutesLeft = (int) ($remainingPeriodDt->i ?? 0);
        // set remaining components
        $remainingPeriodParts = [];
        if ($daysLeft > 0) {
            // push days left
            $remainingPeriodParts[] = sprintf('%d %s', $daysLeft, JText::translate($daysLeft === 1 ? 'VBO_DAY' : 'VBO_DAYS'));
        }
        if ($hoursLeft > 0 && $daysLeft < 5) {
            // push hours left
            $remainingPeriodParts[] = sprintf('%d %s', $hoursLeft, JText::translate($hoursLeft === 1 ? 'VBHOUR' : 'VBHOURS'));
        }
        if (!$daysLeft && $hoursLeft < 6 && $minutesLeft > 0) {
            // push minutes left
            $remainingPeriodParts[] = sprintf('%d %s', $minutesLeft, JText::translate($minutesLeft === 1 ? 'VBMINUTE' : 'VBMINUTES'));
        }
        ?>
        <div class="vbo-quote-head-validity">
            <div class="vbo-quote-head-validity-label">
                <?php VikBookingIcons::e('clock'); ?>
                <span><?php echo JText::translate('VBO_QUOTE_IS_VALID_UNT'); ?> <strong><?php echo trim(str_replace('(00:00)', '', JHtml::fetch('date', $this->quoteData->valid_until, 'd M Y (H:i)'))); ?></strong></span>
                <span class="vbo-quote-head-validity-date">— <?php echo strtolower(JText::sprintf('VBO_PERIOD_LEFT', implode(', ', $remainingPeriodParts))); ?></span>
            </div>
        </div>
        <?php
    }
    ?>
    </div>

    <div class="vbo-quote-body">
        <div class="vbo-quote-solutions-list">

        <?php
        // scan all quote booking solutions
        foreach ($this->quoteData->solutions as $solIndex => $quoteSolution) {
            // tell if the stay dates are on the same year
            $staySameYear = date('Y', $quoteSolution->checkin) == date('Y', $quoteSolution->checkout);
            // count total adults, children and rooms
            $totalAdults   = array_sum(array_column($quoteSolution->rooms, 'adults'));
            $totalChildren = array_sum(array_column($quoteSolution->rooms, 'children'));
            $totalRooms    = count($quoteSolution->rooms);
            // tell if we should "hide" this quote solution by default
            $isHid = $isConfirmed && $quoteSolution->status != 'confirmed';
            // rate components collector
            $rateComponents = [
                'applied'  => 0,
                'original' => 0,
            ];
            ?>
            <div class="vbo-quote-solution">
                
                <div class="vbo-quote-sol-head">
                    <div class="vbo-quote-sol-head-left">
                        <div class="vbo-quote-sol-head-info">
                            <span class="vbo-quote-sol-head-title"><?php echo JText::translate('VBO_SOLUTION'); ?> <span class="vbo-quote-sol-head-numb">#<?php echo ++$solIndex; ?></span></span>
                            <div class="vbo-quote-sol-head-details">
                                <span class="vbo-quote-sol-dates">
                                    <?php VikBookingIcons::e('calendar'); ?>
                                    <span><?php echo JFactory::getDate(date('Y-m-d H:i:s', $quoteSolution->checkin))->format($staySameYear ? 'd M' : 'd M Y'); ?></span>
                                    <?php VikBookingIcons::e('arrow-right'); ?>
                                    <span><?php echo JFactory::getDate(date('Y-m-d H:i:s', $quoteSolution->checkout))->format('d M Y'); ?></span>
                                </span>
                                <span class="vbo-quote-sol-party vbo-quote-sol-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo sprintf('%d %s', $quoteSolution->nights, JText::translate($quoteSolution->nights == 1 ? 'VBDAY' : 'VBDAYS')); ?></span>
                                <span class="vbo-quote-sol-party vbo-quote-sol-adults"><?php VikBookingIcons::e('user-friends'); ?> <?php echo sprintf('%d %s', $totalAdults, JText::translate($totalAdults == 1 ? 'VBMAILADULT' : 'VBMAILADULTS')); ?></span>
                            <?php
                            if ($totalChildren) {
                                ?>
                                <span class="vbo-quote-sol-party vbo-quote-sol-children"><?php VikBookingIcons::e('baby'); ?> <?php echo sprintf('%d %s', $totalChildren, JText::translate($totalChildren == 1 ? 'VBMAILCHILD' : 'VBMAILCHILDREN')); ?></span>
                                <?php
                            }
                            ?>
                            </div>
                        </div>
                    </div>

                    <div class="vbo-quote-sol-head-right">
                        <div class="vbo-quote-sol-head-price"><?php echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($quoteSolution->total), $currencysymb, ['<span class="vbo_currency">%s</span>', '<span class="vbo_price">%s</span>']); ?></div>
                        <span class="vbo-quote-sol-toggle" data-visible="<?php echo $isHid ? 0 : 1; ?>"><?php VikBookingIcons::e('chevron-up'); ?></span>
                    </div>
                </div>

                <div class="vbo-quote-sol-body" style="<?php echo $isHid ? 'display: none;' : ''; ?>">
                <?php
                // scan all quote booking solution rooms
                foreach ($quoteSolution->rooms as $solRoomIndex => $bookingRoom) {
                    // attempt to calculate the average nightly rate for the current booking room
                    $roomNightlyRate = 0;
                    if (!empty($bookingRoom->cust_cost)) {
                        $roomNightlyRate = $bookingRoom->cust_cost / $quoteSolution->nights;
                    } elseif (!empty($bookingRoom->room_cost)) {
                        $roomNightlyRate = $bookingRoom->room_cost / $quoteSolution->nights;
                    }

                    // access tariff data, if any
                    $tariffData = !empty($bookingRoom->idtar) ? VBORoomHelper::getInstance()->getTariffData($bookingRoom->idtar) : null;

                    // get the rooom booking eligible options, if any
                    $eligibleOptions = VBORoomHelper::getInstance()->getEligibleOptions($bookingRoom->id, [
                        'checkin'  => date('Y-m-d', $quoteSolution->checkin),
                        'checkout' => date('Y-m-d', $quoteSolution->checkout),
                        'adults'   => $bookingRoom->adults,
                        'children' => $bookingRoom->children,
                        'rate_id'  => !empty($bookingRoom->idtar) ? ($tariffData['idprice'] ?? null) : null,
                        '_rooms'   => $this->roomsData,
                    ]);

                    // handle room rate plan details
                    $ratePlanData = null;
                    if (!empty($bookingRoom->cust_cost) && !empty($bookingRoom->cust_cpolicy_id)) {
                        // we have a custom rate with a cancellation policy selected
                        $ratePlanData = VikBooking::getPriceInfo($bookingRoom->cust_cpolicy_id, $this->vbo_tn);
                    } elseif (!empty($bookingRoom->idtar) && !empty($tariffData['idprice'])) {
                        // use a system rate plan
                        $ratePlanData = VikBooking::getPriceInfo($tariffData['idprice'], $this->vbo_tn);
                    }

                    // handle original room rate in case of custom rate
                    $origRoomRateData = null;
                    if (!empty($bookingRoom->cust_cost)) {
                        // read all room rates
                        $av_helper = VikBooking::getAvailabilityInstance(true);
                        $av_helper->ignoreRestrictions(true);
                        $av_helper->ignoreAvailability(true);
                        $av_helper->setStayDates(date('Y-m-d', $quoteSolution->checkin), date('Y-m-d', $quoteSolution->checkout));
                        $av_helper->setRoomParty($bookingRoom->adults, $bookingRoom->children);
                        $roomRates = $av_helper->getRates([
                            'hash'       => md5('vbo.e4j.vbo'),
                            'req_type'   => 'hotel_availability',
                            'nights'     => $av_helper->countNightsOfStay($quoteSolution->checkin, $quoteSolution->checkout),
                            'num_rooms'  => 1,
                            'only_rates' => 1,
                            'forced_room_ids' => [$bookingRoom->id],
                        ]);
                        // check if rates were obtained for this stay
                        if ($roomRates[$bookingRoom->id] ?? []) {
                            // obtain room-rate data
                            if ($ratePlanData) {
                                // attempt to extract the connected rate plan
                                $origRoomRateData = array_values(array_filter($roomRates[$bookingRoom->id], function($attRoomRate) use ($ratePlanData) {
                                    return ($attRoomRate['idprice'] ?? 0) == $ratePlanData['id'];
                                }))[0] ?? null;
                            }
                            if (!$origRoomRateData) {
                                // fallback onto the last room rate obtained
                                $origRoomRateData = end($roomRates[$bookingRoom->id]) ?: null;
                            }
                            // increase rate components
                            $rateComponents['applied']  += $bookingRoom->cust_cost;
                            $rateComponents['original'] += $origRoomRateData['cost'];
                        }
                    }
                    ?>
                    <div class="vbo-quote-sol-room">
                    <?php
                    if (!empty($this->roomsData[$bookingRoom->id]['img'])) {
                        // build image gallery, if available
                        $gallery_data = [];
                        if (!empty($this->roomsData[$bookingRoom->id]['moreimgs'])) {
                            $moreimages = explode(';;', $this->roomsData[$bookingRoom->id]['moreimgs']);
                            foreach (array_filter($moreimages) as $mimg) {
                                // push thumb URL
                                $gallery_data[] = $mimg;
                            }
                        }
                        ?>
                        <div class="vbo-dots-slider-selector">
                            <a 
                                href="<?php echo $roomRoutedUris[$bookingRoom->id] ?? '#'; ?>"
                                target="_blank"
                                class="vbo-quote-sol-roomlink"
                                data-gallery="<?php echo $gallery_data ? JHtml::fetch('esc_attr', implode('|', $gallery_data)) : ''; ?>"
                            >
                                <img class="vbo-quote-sol-img" src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $this->roomsData[$bookingRoom->id]['img']; ?>" alt="<?php echo JHtml::fetch('esc_attr', ($this->roomsData[$bookingRoom->id]['name'] ?? $bookingRoom->name)); ?>" />
                            </a>
                        </div>
                        <?php
                    }
                    ?>
                        <div class="vbo-quote-sol-room-desc">
                            <div class="vbo-quote-sol-room-info">
                                <span class="vbo-quote-sol-room-title"><?php VikBookingIcons::e('bed'); ?> <a href="<?php echo $roomRoutedUris[$bookingRoom->id] ?? '#'; ?>" target="_blank"><?php echo ($this->roomsData[$bookingRoom->id]['name'] ?? $bookingRoom->name); ?></a></span>
                            <?php
                            if ($roomNightlyRate) {
                                ?>
                                <div class="vbo-quote-sol-room-price-block">
                                <?php
                                if (!empty($origRoomRateData['cost'])) {
                                    // display the original room rate like if it was a discounted price
                                    $origNightlyRate = $origRoomRateData['cost'] / $quoteSolution->nights;
                                    if ($origNightlyRate > $roomNightlyRate) {
                                        ?>
                                    <span class="vbo-quote-sol-room-price-disc"><?php echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($origNightlyRate), $currencysymb, ['<span class="vbo_currency">%s</span>', '<span class="vbo_price">%s</span>']); ?> /<?php echo strtolower(JText::translate('VBSEARCHRESNIGHT')); ?></span>
                                        <?php
                                    }
                                }
                                ?>
                                    <span class="vbo-quote-sol-room-price-wrap">
                                        <span class="vbo-quote-sol-room-price"><?php echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($roomNightlyRate), $currencysymb, ['<span class="vbo_currency">%s</span>', '<span class="vbo_price">%s</span>']); ?></span> 
                                        <span class="vbo-quote-sol-room-price-lbl">/<?php echo strtolower(JText::translate('VBSEARCHRESNIGHT')); ?></span>
                                    </span>
                                </div>
                                <?php
                            }
                            ?>
                            </div>
                            <div class="vbo-quote-sol-room-details">
                                <span class="vbo-quote-sol-room-guests">
                                    <span><?php VikBookingIcons::e('user-friends'); ?> <?php echo sprintf('%d %s', $bookingRoom->adults, JText::translate($bookingRoom->adults == 1 ? 'VBMAILADULT' : 'VBMAILADULTS')); ?></span>
                                <?php
                                if (($bookingRoom->children ?? 0) > 0) {
                                    ?>
                                    <span><?php VikBookingIcons::e('baby'); ?> <?php echo sprintf('%d %s', $bookingRoom->children, JText::translate($bookingRoom->children == 1 ? 'VBMAILCHILD' : 'VBMAILCHILDREN')); ?></span>
                                    <?php
                                }
                                ?>
                                </span>
                                <span class="vbo-quote-sol-room-policy"><?php echo $ratePlanData['name'] ?? JText::translate('VBOROOMCUSTRATEPLAN'); ?></span>
                            </div>
                        <?php
                        if (!empty($this->roomsData[$bookingRoom->id]['smalldesc'])) {
                            // display the room/listing description
                            // prepare CMS contents depending on platform
                            $this->roomsData[$bookingRoom->id] = VBORoomHelper::getInstance()->prepareCMSContents($this->roomsData[$bookingRoom->id], ['smalldesc']);
                            ?>
                            <div class="vbo-quote-sol-room-description">
                                <?php echo $this->roomsData[$bookingRoom->id]['smalldesc']; ?>
                            </div>
                            <?php
                        }
                        if ($ratePlanData) {
                            // gather meal plan and cancellation policy details
                            $cancellationPolicy = null;
                            $rate_plan_meals = [];
                            if (!empty($ratePlanData['meal_plans'])) {
                                $rate_plan_meals = is_array($ratePlanData['meal_plans']) ? $ratePlanData['meal_plans'] : (array) json_decode($ratePlanData['meal_plans'], true);
                                if (count($rate_plan_meals) === 1 && !strcasecmp(($rate_plan_meals[0] ?? ''), 'breakfast')) {
                                    // when only breakfast is included, do not display the meal plans label
                                    $rate_plan_meals = [];
                                    $ratePlanData['breakfast_included'] = 1;
                                }
                            }
                            ?>
                            <div class="vbo-quote-sol-room-policy-details">
                            <?php
                            if ($rate_plan_meals) {
                                $rate_plan_meals_incl = array_map(function($rate_plan_meal) use ($rate_plan_meals_map) {
                                    return $rate_plan_meals_map[$rate_plan_meal] ?? $rate_plan_meal;
                                }, $rate_plan_meals);
                                ?>
                                <p class="vbo-quote-sol-room-policy-meals"><?php echo rtrim(JText::translate('VBO_MEAL_PLANS_INCL'), ':') . ': ' . implode(', ', $rate_plan_meals_incl); ?></p>
                                <?php
                            } elseif (!empty($ratePlanData['breakfast_included'])) {
                                ?>
                                <p class="vbo-quote-sol-room-policy-meals"><?php echo JText::translate('VBBREAKFASTINCLUDED'); ?></p>
                                <?php
                            }
                            if (!empty($ratePlanData['free_cancellation'])) {
                                // refundable rate
                                if ($ratePlanData['canc_deadline'] > 0) {
                                    ?>
                                <p class="vbo-quote-sol-room-policy-refund"><?php echo JText::sprintf('VBFREECANCELLATIONWITHIN', (int) $ratePlanData['canc_deadline']); ?></p>
                                    <?php
                                } else {
                                    ?>
                                <p class="vbo-quote-sol-room-policy-refund"><?php echo JText::translate('VBFREECANCELLATION'); ?></p>
                                    <?php
                                }
                                // check if we have a custom cancellation policy to display
                                if (!empty($ratePlanData['canc_policy'])) {
                                    $cancellationPolicy = strpos($ratePlanData['canc_policy'], '<') !== false ? $ratePlanData['canc_policy'] : nl2br($ratePlanData['canc_policy']);
                                }
                            } elseif ($ratePlanData && empty($ratePlanData['free_cancellation'])) {
                                // non-refundable rate
                                ?>
                                <p class="vbo-quote-sol-room-policy-refund" data-non-refundable="1"><?php echo JText::translate('VBONONREFUNDRATE'); ?></p>
                                <?php
                            }
                            if ($cancellationPolicy) {
                                ?>
                                <p class="vbo-quote-sol-room-policy-info"><?php echo $cancellationPolicy; ?></p>
                                <?php
                            }
                            ?>
                            </div>
                            <?php
                        }
                        if (!empty($bookingRoom->optionals) || !empty($bookingRoom->extracosts)) {
                            ?>
                            <ul class="vbo-quote-sol-room-extras">
                            <?php
                            // obtain the list of computed room booking options
                            $roomOptsData = !empty($bookingRoom->optionals) ? VBORoomHelper::getInstance()->computeBookingOptions((array) $bookingRoom, $eligibleOptions) : [];

                            // translate booking option records, if any
                            $this->vbo_tn->translateContents($roomOptsData, '#__vikbooking_optionals');

                            // scan computed room booking options, if any
                            foreach ($roomOptsData as $roomOptData) {
                                ?>
                                <li><?php VikBookingIcons::e('toolbox'); ?> <?php echo $roomOptData['name']; ?> <span class="vbo-quote-sol-room-features-price"><?php echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($roomOptData['cost']), $currencysymb, ['<span class="vbo_currency">%s</span>', '<span class="vbo_price">%s</span>']); ?></span></li>
                                <?php
                            }

                            // scan all extra services, if any
                            foreach ((array) ($bookingRoom->extracosts ?? []) as $extraService) {
                                ?>
                                <li><?php VikBookingIcons::e('toolbox'); ?> <?php echo $extraService['name']; ?> <span class="vbo-quote-sol-room-features-price"><?php echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($extraService['cost']), $currencysymb, ['<span class="vbo_currency">%s</span>', '<span class="vbo_price">%s</span>']); ?></span></li>
                                <?php
                            }
                            ?>
                            </ul>
                            <?php
                        }
                        ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
                </div>
                <div class="vbo-quote-sol-footer" style="<?php echo $isHid ? 'display: none;' : ''; ?>">
                    <div class="vbo-quote-sol-total">
                        <div class="vbo-quote-sol-total-label"><?php echo JText::translate('VBTOTAL'); ?></div>
                        <div class="vbo-quote-sol-total-price">
                        <?php
                        if ($rateComponents['applied'] && $rateComponents['original'] > $rateComponents['applied']) {
                            // calculate the total cost without the custom discounted rate
                            $originalTotal = $quoteSolution->total - $rateComponents['applied'] + $rateComponents['original'];
                            // display striked-through amount
                            ?>
                            <span class="vbo-quote-sol-total-price-disc"><?php
                            echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($originalTotal), $currencysymb, ['<span class="vbo_currency">%s</span>', '<span class="vbo_price">%s</span>']);
                            ?></span>
                            <?php
                        }

                        // display quote solution total amount
                        echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($quoteSolution->total), $currencysymb, ['<span class="vbo_currency">%s</span>', '<span class="vbo_price">%s</span>']);
                        ?>
                        </div>
                        <div class="vbo-quote-sol-total-subtext"><?php
                        echo implode(', ', [
                            sprintf('%d %s', $totalRooms, JText::translate($totalRooms === 1 ? 'VBSEARCHRESROOM' : 'VBSEARCHRESROOMS')),
                            sprintf('%d %s', $quoteSolution->nights, JText::translate($quoteSolution->nights == 1 ? 'VBSEARCHRESNIGHT' : 'VBSEARCHRESNIGHTS')),
                        ]);
                        ?></div>
                    </div>
                    <div class="vbo-quote-sol-footer-actions">
                    <?php
                    if (!$isConfirmed && $quoteSolution->status == 'standby') {
                        // display button to accept the quote booking solution
                        ?>
                        <a class="btn vbo-accept" href="<?php echo VikBooking::externalroute('index.php?option=com_vikbooking&view=booking&sid=' . ($quoteSolution->sid ?? '') . '&ts=' . ($quoteSolution->ts ?? '')); ?>"><?php VikBookingIcons::e('check'); ?> <?php echo JText::translate('VBO_ACCEPT'); ?></a>
                        <div class="vbo-quote-sol-footer-comment">
                            <span><?php echo JText::translate('VBO_PAY_TO_CONFIRM'); ?></span>
                        </div>
                        <?php
                    } elseif ($quoteSolution->status == 'confirmed') {
                        // display label to tell the quote booking solution was confirmed
                        ?>
                        <a class="btn vbo-success" href="<?php echo VikBooking::externalroute('index.php?option=com_vikbooking&view=booking&sid=' . ($quoteSolution->sid ?? '') . '&ts=' . ($quoteSolution->ts ?? '')); ?>"><?php VikBookingIcons::e('check-double'); ?> <?php echo JText::translate('VBCONFIRMED'); ?></a>
                        <?php
                    }
                    ?>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
        </div>
    </div>

</div>

<script type="text/javascript">
    VBOCore.DOMLoaded(() => {

        /**
         * Register listener to toggle the visibility of the quote solution details.
         */
        document.querySelectorAll('.vbo-quote-solution').forEach((solutionEl) => {
            const headEl = solutionEl.querySelector('.vbo-quote-sol-head');
            const toggleEl = solutionEl.querySelector('.vbo-quote-sol-toggle');
            headEl.addEventListener('click', (e) => {
                let isVisible = toggleEl.getAttribute('data-visible') == '1';
                let solutionBodyEl = solutionEl.querySelector('.vbo-quote-sol-body');
                let solutionFooterEl = solutionEl.querySelector('.vbo-quote-sol-footer');
                let icnEl = toggleEl.querySelector('i');
                icnEl.setAttribute('class', '');
                if (isVisible) {
                    // hide elements
                    solutionBodyEl.style.display = 'none';
                    solutionFooterEl.style.display = 'none';
                    icnEl.classList.add(...('<?php echo VikBookingIcons::i('chevron-down'); ?>'.split(' ')));
                } else {
                    // show elements
                    solutionBodyEl.style.display = '';
                    solutionFooterEl.style.display = '';
                    icnEl.classList.add(...('<?php echo VikBookingIcons::i('chevron-up'); ?>'.split(' ')));
                }
                // toggle data attribute
                toggleEl.setAttribute('data-visible', isVisible ? 0 : 1);
            });

        });

        /**
         * Define room galleries.
         */
        document.querySelectorAll('.vbo-dots-slider-selector').forEach((sliderEl) => {
            let galleryEl = sliderEl.querySelector('.vbo-quote-sol-roomlink');
            let galleryData = (galleryEl.getAttribute('data-gallery') || '').split('|').filter(i => i);
            if (!galleryData.length) {
                return;
            }
            let thumbsBaseUri = '<?php echo VBO_SITE_URI . 'resources/uploads/thumb_'; ?>';
            let images = [];
            for (let i = 0; i < galleryData.length; i++) {
                if (!galleryData[i].length) {
                    continue;
                }
                images.push(thumbsBaseUri + galleryData[i]);
            }
            // move original main photo and make it hidden
            let mainPhoto = galleryEl.querySelector('img');
            if (mainPhoto) {
                mainPhoto.style.display = 'none';
                galleryEl.closest('.vbo-quote-sol-room').append(mainPhoto);
            }
            // render slider
            let slideHref = galleryEl.getAttribute('href');
            let slideClass = galleryEl.getAttribute('class');
            jQuery(sliderEl).html('').vikDotsSlider({
                images: images,
                navButPrevContent: '<?php VikBookingIcons::e('chevron-left'); ?>',
                navButNextContent: '<?php VikBookingIcons::e('chevron-right'); ?>',
                onDisplaySlide: function() {
                    let content = jQuery(this).children().clone(true, true);
                <?php
                if (VBOPlatformDetection::isWordPress()) {
                    /**
                     * @wponly  We do not re-construct the A tag.
                     *          We just append the slide image.
                     */
                    ?>
                    jQuery(this).html('').append(content);
                    <?php
                } else {
                    ?>
                    let link = jQuery('<a target="_blank"></a>').attr('href', slideHref).attr('class', slideClass).append(content);
                    jQuery(this).html('').append(link);
                    <?php
                }
                ?>
                }
            });
        });

    });
</script>
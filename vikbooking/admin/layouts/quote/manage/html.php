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
 * Obtain vars from arguments received in the layout file.
 * 
 * @var  ?object  $quote        Optional quote record to edit.
 * @var  array    $customer     Optional customer default values.
 * @var  array    $inquiry      Optional inquiry values to populate.
 * @var  ?int     $session_id   Optional Chat Session ID source.
 * @var  array    $prefMessages Optional list of preferred message objects.
 */
extract($displayData);

// get VBO application
$vbo_app = VikBooking::getVboApplication();

// currency symbol
$currencysymb = VikBooking::getCurrencySymb();

// load all option records
$optionRecords = VBORoomHelper::getInstance()->loadAnyOptions();

// load all tax rates
$taxRates = VikBooking::getAvailabilityInstance()->getTaxRates();

// load all payment methods
$paymentMethods = VBOMvcModel::getInstance('payment')->getItems(
    // clauses
    [
        // exclude those payment methods that automatically confirm the booking
        // quote solutions (bookings) must be confirmed by placing an online payment
        'setconfirmed' => 0,
    ],
    // start
    0,
    // lim
    0,
    // cols
    [],
    // ordering
    [
        'published' => 'DESC',
        'name' => 'ASC',
    ]
);

// build default message template
$defaultMessage = '';
$siteLogo = VBOFactory::getConfig()->get('sitelogo');
$companyName = VikBooking::getFrontTitle();
if ($siteLogo) {
    $logoSrc = VBO_ADMIN_URI . 'resources/' . $siteLogo;
    $logoAlt = htmlspecialchars($companyName);
    $defaultMessage .= <<<HTML
    <p style="text-align: center;"><img src="$logoSrc" alt="$defaultMessage" /></p>
    HTML;
}
$messageBody = preg_replace('/<br\s*\/?\s*>\R/', '<br>', nl2br(JText::translate('VBO_QUOTE_DEF_MESS')));
$defaultMessage .= <<<HTML
<h1 style="text-align: center;">
    <span style="font-family: verdana;">$companyName</span>
</h1>
<hr class="vbo-editor-hl-mailwrapper">
$messageBody
<p>$companyName</p>
<hr class="vbo-editor-hl-mailwrapper">
<p><br></p>
HTML;

?>
<div class="vbo-quote-wrapper">
    <div class="vbo-quote-sections">

    <?php
    if ($quote) {
        // route quote URI
        $quoteUri = VikBooking::externalroute(
            "index.php?option=com_vikbooking&view=quote&ref={$quote->uuid}" . (($quote->solutions[0]->lang ?? null) ? '&lang=' . $quote->solutions[0]->lang : ''),
            false
        );
        ?>
        <div class="vbo-quote-section vbo-quote-section-current" data-quote-id="<?php echo $quote->id; ?>">
            <div class="vbo-quote-section-head vbo-quote-section-head-sb">
                <div class="vbo-quote-section-head-title">
                    <span class="vbo-bg-icon"><?php VikBookingIcons::e('file-alt'); ?></span>
                    <span class="vbo-quote-section-name"><?php echo sprintf('%s #%d', JText::translate('VBO_BTYPE_QUOTE'), $quote->id); ?></span>
                </div>
                <div class="vbo-quote-edit-link">
                    <a class="btn btn-small vbo-config-btn" target="_blank" href="<?php echo $quoteUri; ?>"><?php VikBookingIcons::e('external-link'); ?> <?php echo JText::translate('VBVIEWORDFRONT'); ?></a>
                </div>
            </div>
            <div class="vbo-quote-section-body">
                <div class="vbo-quote-edit-dt">
                    <label><?php echo JText::translate('VBOINVCREATIONDATE'); ?></label>
                    <span><?php echo sprintf('%s (%s)', JHtml::fetch('date', $quote->created_on, 'd M Y H:i'), $quote->created_by ?? ''); ?></span>
                </div>
                <div class="vbo-quote-edit-uuid" data-quote-uuid="<?php echo $quote->uuid; ?>">
                    <label>UUID</label>
                    <span class="label"><?php echo $quote->uuid; ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

        <div class="vbo-quote-section vbo-quote-section-customer">
            <div class="vbo-quote-section-head">
                <span class="vbo-bg-icon"><?php VikBookingIcons::e('user'); ?></span>
                <span class="vbo-quote-section-name"><?php echo JText::translate('VBOCUSTOMER'); ?></span>
            </div>
            <div class="vbo-quote-section-body">
                <div class="vbo-quote-customer-choose">
                    <div class="vbo-singleselect-inline-elems-wrap vbo-search-elems-wrap">
                    <?php
                    /**
                     * Display a search elements dropdown for searching customers.
                     */
                    $selectedCustomer = [];
                    if (!empty($customer['id'])) {
                        $selectedCustomer = [
                            'id'   => $customer['id'],
                            'text' => sprintf('%s %s', $customer['first_name'] ?? '', $customer['last_name'] ?? ''),
                        ];
                    } elseif (!empty($quote->idcustomer)) {
                        $selectedCustomer = [
                            'id'   => $quote->idcustomer,
                            'text' => sprintf('%s %s', $quote->first_name ?? '', $quote->last_name ?? ''),
                        ];
                    }
                    echo $vbo_app->renderSearchElementsDropDown([
                        'id' => 'vbo-quote-search-customer',
                        'elements' => 'customers',
                        'placeholder' => JText::translate('VBOSEARCHEXISTCUST'),
                        'allow_clear' => true,
                        'attributes'  => [
                            'name' => 'quote[customer][id]',
                            'data-field' => 'customer-id',
                        ],
                        'style_selection' => true,
                        'selection_class' => 'vbo-sel2-selected-search-elem-full',
                        'selection_event' => 'vbo-quote-customer-chosen',
                        'load_assets' => false,
                        'selected_value' => $selectedCustomer ?: null,
                        'width' => '300px',
                    ]);
                    ?>
                    </div>
                </div>
                <div class="vbo-quote-customer-fields">
                    <div class="vbo-quote-customer-field">
                        <label for="vbo-quote-customer-fname"><?php echo JText::translate('VBCUSTOMERFIRSTNAME'); ?></label>
                        <input type="text" id="vbo-quote-customer-fname" data-field="customer-firstname" value="<?php echo JHtml::fetch('esc_attr', $customer['first_name'] ?? $quote->first_name ?? ''); ?>" />
                    </div>
                    <div class="vbo-quote-customer-field">
                        <label for="vbo-quote-customer-lname"><?php echo JText::translate('VBCUSTOMERLASTNAME'); ?></label>
                        <input type="text" id="vbo-quote-customer-lname" data-field="customer-lastname" value="<?php echo JHtml::fetch('esc_attr', $customer['last_name'] ?? $quote->last_name ?? ''); ?>" />
                    </div>
                    <div class="vbo-quote-customer-field">
                        <label for="vbo-quote-customer-email"><?php echo JText::translate('VBCUSTOMEREMAIL'); ?></label>
                        <input type="email" id="vbo-quote-customer-email" data-field="customer-email" value="<?php echo JHtml::fetch('esc_attr', $customer['email'] ?? $quote->email ?? ''); ?>" />
                    </div>
                    <div class="vbo-quote-customer-field">
                        <label for="vbo-quote-customer-phone"><?php echo JText::translate('VBCUSTOMERPHONE'); ?></label>
                        <?php
                        echo $vbo_app->printPhoneInputField([
                            'id' => 'vbo-quote-customer-phone',
                            'name' => 'quote[customer][phone]',
                            'value' => JHtml::fetch('esc_attr', $customer['phone'] ?? $quote->phone ?? ''),
                            'class' => 'vbo-calendar-cfield-phone',
                            'data-field' => 'customer-phone',
                            'data-isphone' => '1',
                        ], [
                            'fullNumberOnBlur' => true,
                            'countryDataEvent' => 'vbo-quote-get-phone-country-data',
                        ]);
                    ?>
                        <input type="hidden" data-field="customer-country" value="<?php echo JHtml::fetch('esc_attr', $customer['country'] ?? $quote->country_3_code ?? ''); ?>" />
                    </div>
                </div>
            </div>
        </div>

        <div class="vbo-quote-section vbo-quote-section-options">
            <div class="vbo-quote-section-head">
                <span class="vbo-bg-icon"><?php VikBookingIcons::e('clipboard-list'); ?></span>
                <span class="vbo-quote-section-name"><?php echo JText::translate('VBO_QUOTE_OPTIONS'); ?></span>
            </div>
            <div class="vbo-quote-section-body" data-section="options">
            <?php
            // if in edit-mode, display the current quote booking solutions
            foreach ((array) ($quote->solutions ?? []) as $solIndex => $solution) {
                // count booking solution values
                $totRooms    = count($solution->rooms);
                $totAdults   = array_sum(array_column($solution->rooms, 'adults'));
                $totChildren = array_sum(array_column($solution->rooms, 'children'));
                ?>
                <div class="vbo-quote-new-option-wrap" data-active="0">
                    <div class="vbo-quote-option-title">
                        <h4><?php echo JText::translate('VBO_OPTION'); ?> #<span class="vbo-quote-option-number"><?php echo ++$solIndex; ?></span></h4>
                        <div class="vbo-quote-option-summary">
                            <span class="vbo-quote-option-summary-id"><?php VikBookingIcons::e('hashtag'); ?> <?php echo JText::translate('VBDASHUPRESONE'); ?> <span><?php echo $solution->id; ?></span></span>
                        <?php
                        // display booking solution status badge
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
                            <span class="vbo-quote-option-summary-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo JText::translate('VBDAYS'); ?> <span data-counter="nights"><?php echo $solution->nights; ?></span></span>
                            <span class="vbo-quote-option-summary-rooms"><?php VikBookingIcons::e('bed'); ?> <?php echo JText::translate('VBPVIEWORDERSTHREE'); ?> <span data-counter="rooms"><?php echo $totRooms; ?></span></span>
                            <span class="vbo-quote-option-summary-guests"><?php VikBookingIcons::e('users'); ?> <?php echo JText::translate('VBPVIEWORDERSPEOPLE'); ?> <span data-counter="guests"><?php echo $totAdults + $totChildren; ?></span></span>
                        </div>
                    </div>
                    <div class="vbo-quote-option-dates">
                        <div class="vbo-quote-option-date">
                            <label><?php echo JText::translate('VBPICKUPAT'); ?></label>
                            <div class="input-append">
                                <input type="text" autocomplete="off" size="10" data-field="checkin" readonly value="<?php echo date('Y-m-d', $solution->checkin); ?>" />
                                <button type="button" class="btn btn-secondary"><?php VikBookingIcons::e('calendar', 'icn-nomargin'); ?></button>
                            </div>
                        </div>
                        <div class="vbo-quote-option-date">
                            <label><?php echo JText::translate('VBRELEASEAT'); ?></label>
                            <div class="input-append">
                                <input type="text" autocomplete="off" size="10" data-field="checkout" readonly value="<?php echo date('Y-m-d', $solution->checkout); ?>" />
                                <button type="button" class="btn btn-secondary"><?php VikBookingIcons::e('calendar', 'icn-nomargin'); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="vbo-quote-option-rooms">
                    <?php
                    foreach ($solution->rooms as $roomSolIndex => $roomSolution) {
                        ?>
                        <div class="vbo-quote-option-new-room-wrap">
                            <div class="vbo-quote-option-room-title">
                                <h4><?php VikBookingIcons::e('bed'); ?> <span><?php echo JText::translate('VBEDITORDERTHREE'); ?> #<span class="vbo-quote-option-room-number"><?php echo ++$roomSolIndex; ?></span></span></h4>
                            </div>
                            <div class="vbo-quote-option-room-values">
                                <div class="vbo-quote-option-room-value" data-type="listing">
                                    <label><?php echo JText::translate('VBO_LISTING'); ?></label>
                                    <span><?php echo $roomSolution->name; ?></span>
                                </div>
                                <div class="vbo-quote-option-room-value" data-type="adults">
                                    <label><?php echo JText::translate('VBEDITORDERADULTS'); ?></label>
                                    <span><?php echo $roomSolution->adults; ?></span>
                                </div>
                                <div class="vbo-quote-option-room-value" data-type="children">
                                    <label><?php echo JText::translate('VBEDITORDERCHILDREN'); ?></label>
                                    <span><?php echo $roomSolution->children; ?></span>
                                </div>
                            <?php
                            if (!empty($roomSolution->optionals)) {
                                // raw string expected
                                $optionsList = array_values(array_unique(array_map(function($optStr) use ($optionRecords) {
                                    // get the option ID
                                    $optId = explode(':', $optStr)[0];
                                    // find the corresponding name
                                    foreach ($optionRecords as $optionRecord) {
                                        if ($optionRecord['id'] == $optId) {
                                            return $optionRecord['name'];
                                        }
                                    }
                                    // default to option ID
                                    return $optId;
                                }, array_filter(explode(';', $roomSolution->optionals)))));
                                ?>
                                <div class="vbo-quote-option-room-value" data-type="options">
                                    <label><?php echo JText::translate('VBPEDITBUSYEIGHT'); ?></label>
                                    <span><?php echo implode(', ', $optionsList); ?></span>
                                </div>
                                <?php
                            }
                            if (!empty($roomSolution->extracosts)) {
                                // array of arrays expected
                                ?>
                                <div class="vbo-quote-option-room-value" data-type="extras">
                                    <label><?php echo JText::translate('VBPEDITBUSYEXTRACOSTS'); ?></label>
                                    <span><?php echo implode(', ', array_column($roomSolution->extracosts, 'name')); ?></span>
                                </div>
                                <?php
                            }
                            ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    </div>
                    <div class="vbo-quote-option-edit-booking">
                        <a href="index.php?option=com_vikbooking&task=editbusy&cid[]=<?php echo $solution->id; ?>" target="_blank" class="btn"><?php VikBookingIcons::e('edit'); ?> <?php echo JText::translate('VBMODRES'); ?></a>
                    </div>
                    <div class="vbo-quote-option-total">
                        <h4><?php VikBookingIcons::e('file-invoice-dollar'); ?> <span><?php echo JText::translate('VBPVIEWORDERSSEVEN'); ?></span> <span class="vbo-quote-option-total-cost"><?php echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat($solution->total), $currencysymb); ?></span></h4>
                    </div>
                </div>
                <?php
            }
            ?>
            </div>
            <div class="vbo-quote-section-footer" style="<?php echo $quote ? 'display: none;' : ''; ?>">
                <button type="button" class="btn vbo-quote-option-add-btn"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBO_NEW_OPTION'); ?></button>
            </div>
        </div>

        <div class="vbo-quote-section vbo-quote-section-message">
            <div class="vbo-quote-section-head-wrap">
                <div class="vbo-quote-section-head">
                    <span class="vbo-bg-icon"><?php VikBookingIcons::e('comment'); ?></span>
                    <span class="vbo-quote-section-name"><?php echo JText::translate('VBSENDSMSCUSTCONT'); ?></span>
                </div>
            <?php
            if ($prefMessages) {
                ?>
                <div class="vbo-quote-section-floating">
                    <label for="vbo-quote-message-preftpl"><?php VikBookingIcons::e('star'); ?> <?php echo JText::translate('VBO_SEL_PREF_MESS'); ?></label>
                    <select id="vbo-quote-message-preftpl" data-field="message-preferred-tpl">
                        <option value=""></option>
                    <?php
                    foreach ($prefMessages as $prefMessage) {
                        ?>
                        <option value="<?php echo $prefMessage->id; ?>"<?php echo $quote && $quote->id == $prefMessage->id ? ' selected="selected"' : ''; ?>><?php echo sprintf('%s (%s)', $prefMessage->name, JHtml::fetch('date', $prefMessage->created_on, 'd M Y H:i')); ?></option>
                        <?php
                    }
                    ?>
                    </select>
                </div>
                <?php
            }
            ?>
            </div>
            <div class="vbo-quote-section-body">
                <div class="vbo-quote-mail-subject">
                    <label for="vbo-quote-mail-subject"><?php echo JText::translate('VBSENDEMAILCUSTSUBJ'); ?></label>
                    <input type="text" id="vbo-quote-mail-subject" data-field="mail-subject" value="<?php echo JHtml::fetch('esc_attr', ($quote->subject ?? '')); ?>" />
                </div>
                <div class="vbo-quote-mail-message">
                <?php
                echo $vbo_app->renderVisualEditor(
                    'quote_message_content',
                    ($quote ? $quote->message : $defaultMessage),
                    [
                        'id' => 'vbo-quote-message-content',
                        'style' => 'min-height: 50px; width: 100%;',
                    ],
                    [
                        'modes' => [
                            'visual',
                            'text',
                        ],
                        'gen_ai' => [
                            'placeholders' => 1,
                            'environment' => 'quote',
                        ],
                    ],
                    [
                        '{first_name}',
                        '{last_name}',
                        '{checkin_date}',
                        '{checkout_date}',
                        '{num_nights}',
                        '{tot_adults}',
                        '{tot_children}',
                        '{tot_guests}',
                        '{quote_link}',
                    ]
                );
                ?>
                </div>
                <div class="vbo-quote-notes">
                    <label for="vbo-quote-notes"><?php echo JText::translate('VBPSHOWPAYMENTSTHREE'); ?></label>
                    <textarea id="vbo-quote-notes" data-field="notes"><?php echo JHtml::fetch('esc_textarea', ($quote->notes ?? '')); ?></textarea>
                    <div class="vbo-quote-label-help"><?php echo JText::translate('VBO_QUOTE_NOTES_HELP'); ?></div>
                </div>
                <div class="vbo-quote-mail-preferred vbo-toggle-mini">
                    <label for="quote_message_preferred-on"><?php echo JText::translate('VBO_SAVE_PREF'); ?></label>
                    <?php echo $vbo_app->printYesNoButtons('quote_message_preferred', JText::translate('VBYES'), JText::translate('VBNO'), (!$quote || !empty($quote->preferred) ? 1 : 0), 1, 0); ?>
                    <div class="vbo-quote-label-help"><?php echo JText::translate('VBO_PREF_MESS_HELP'); ?></div>
                </div>
            </div>
        </div>

        <div class="vbo-quote-section vbo-quote-section-validity">
            <div class="vbo-quote-section-head">
                <span class="vbo-bg-icon"><?php VikBookingIcons::e('clock'); ?></span>
                <span class="vbo-quote-section-name"><?php echo JText::translate('VBO_VALIDITY'); ?></span>
            </div>
            <div class="vbo-quote-section-body">
                <div class="vbo-quote-validity-date">
                    <label for="vbo-quote-valid-until"><?php echo JText::translate('VBO_QUOTE_VALIDITY'); ?> <span class="vbo-quote-validity-date-help"><?php VikBookingIcons::e('circle-question', 'icn-nomargin'); ?></span></label>
                    <?php
                    $validUntilVal = JFactory::getDate('+2 days 23:59')->format('Y-m-d H:i');
                    if ($quote) {
                        if (!empty($quote->valid_until)) {
                            $validUntilVal = JHtml::fetch('date', $quote->valid_until, 'Y-m-d H:i');
                        } else {
                            $validUntilVal = '';
                        }
                    }
                    echo $vbo_app->renderDateTimePicker([
                        'id'    => 'vbo-quote-valid-until',
                        'min'   => JFactory::getDate('now')->format('Y-m-d\TH:i'),
                        'value' => $validUntilVal,
                        'attributes' => [
                            'data-field' => 'valid-until',
                        ],
                    ]);
                    ?>
                </div>
            <?php
            if ($paymentMethods) {
                ?>
                <div class="vbo-quote-payment-block">
                    <label for="vbo-quote-payment-id"><?php echo JText::translate('VBLIBPAYNAME'); ?></label>
                    <select id="vbo-quote-payment-id" data-field="quote-payment">
                        <option value="">- <?php echo JText::translate('VBO_USE_DEFAULT'); ?> -</option>
                    <?php
                    foreach ($paymentMethods as $paymentMethod) {
                        $paymentIdentifier = sprintf('%d=%s', $paymentMethod->id, $paymentMethod->name);
                        $isPaymentSel = ($quote && ($quote->solutions[0]->idpayment ?? '') == $paymentIdentifier);
                        ?>
                        <option value="<?php echo JHtml::fetch('esc_attr', $paymentIdentifier); ?>"<?php echo $isPaymentSel ? ' selected="selected"' : ''; ?>><?php echo $paymentMethod->name; ?></option>
                        <?php
                    }
                    ?>
                    </select>
                </div>
                <?php
            }
            ?>
                <div class="vbo-quote-name-block">
                    <label for="vbo-quote-ref-name"><?php echo JText::translate('VBO_QUOTE_NAME'); ?></label>
                    <input type="text" id="vbo-quote-ref-name" data-field="quote-name" placeholder="<?php echo date('Y-m-d H:i'); ?>" value="<?php echo $quote ? JHtml::fetch('esc_attr', $quote->name) : ''; ?>" />
                </div>
            </div>
        </div>

        <div class="vbo-quote-section-buttons">
            <?php
            if ($quote) {
                ?>
            <div class="vbo-quote-section-delete">
                <button type="button" class="btn btn-large btn-danger vbo-quote-delete-btn"><?php VikBookingIcons::e('ban'); ?> <?php echo JText::translate('VBELIMINA'); ?></button>
            </div>
                <?php
            }
            ?>

            <div class="vbo-quote-section-send">
                <a name="quote-apply"></a>
            <?php
            if ($quote) {
                ?>
                <button type="button" class="btn btn-large vbo-config-btn vbo-quote-update-btn"><?php VikBookingIcons::e('check'); ?> <?php echo JText::translate('VBADMINNOTESUPD'); ?></button>
                <?php
            } else {
                ?>
                <div class="vbo-quote-send-later vbo-toggle-mini">
                    <label for="quote_send_later-on"><?php echo JText::translate('VBO_SEND_LATER'); ?></label>
                    <?php echo $vbo_app->printYesNoButtons('quote_send_later', JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0); ?>
                </div>
                <button type="button" class="btn btn-large vbo-config-btn vbo-quote-submit-btn"><?php VikBookingIcons::e('paper-plane'); ?> <?php echo JText::translate('VBO_SEND_QUOTE'); ?></button>
                <?php
            }
            ?>
            </div>
        </div>

    </div>
</div>

<div class="vbo-quote-helper" style="display: none;">

    <div class="vbo-quote-new-option-wrap" data-active="0">
        <div class="vbo-quote-option-title">
            <h4><?php echo JText::translate('VBO_OPTION'); ?> #<span class="vbo-quote-option-number"></span></h4>
            <div class="vbo-quote-option-summary">
                <span class="vbo-quote-option-summary-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo JText::translate('VBDAYS'); ?> <span data-counter="nights">0</span></span>
                <span class="vbo-quote-option-summary-rooms"><?php VikBookingIcons::e('bed'); ?> <?php echo JText::translate('VBPVIEWORDERSTHREE'); ?> <span data-counter="rooms">0</span></span>
                <span class="vbo-quote-option-summary-guests"><?php VikBookingIcons::e('users'); ?> <?php echo JText::translate('VBPVIEWORDERSPEOPLE'); ?> <span data-counter="guests">0</span></span>
            </div>
            <div class="vbo-quote-option-delete">
                <button type="button" class="btn btn-small vbo-btn-transparent vbo-quote-option-remove-btn"><?php VikBookingIcons::e('trash', 'no-margin'); ?></button>
            </div>
        </div>
        <div class="vbo-quote-option-dates">
            <div class="vbo-quote-option-date">
                <label><?php echo JText::translate('VBPICKUPAT'); ?></label>
                <div class="input-append">
                    <input type="text" autocomplete="off" size="10" data-field="checkin" />
                    <button type="button" class="btn btn-secondary checkindate-trig"><?php VikBookingIcons::e('calendar', 'icn-nomargin'); ?></button>
                </div>
            </div>
            <div class="vbo-quote-option-date">
                <label><?php echo JText::translate('VBRELEASEAT'); ?></label>
                <div class="input-append">
                    <input type="text" autocomplete="off" size="10" data-field="checkout" />
                    <button type="button" class="btn btn-secondary checkoutdate-trig"><?php VikBookingIcons::e('calendar', 'icn-nomargin'); ?></button>
                </div>
            </div>
        </div>
        <div class="vbo-quote-option-rooms"></div>
        <div class="vbo-quote-option-rooms-add">
            <button type="button" class="btn vbo-quote-option-room-add-btn"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBOBOOKADDROOM'); ?></button>
        </div>
        <div class="vbo-quote-option-total" style="display: none;">
            <h4><?php VikBookingIcons::e('file-invoice-dollar'); ?> <span><?php echo JText::translate('VBPVIEWORDERSSEVEN'); ?></span> <span class="vbo-quote-option-total-cost"><?php echo VikBooking::formatCurrencyNumber(VikBooking::numberFormat(0), $currencysymb); ?></span></h4>
        </div>
    </div>

    <div class="vbo-quote-option-new-room-wrap">
        <div class="vbo-quote-option-room-title">
            <h4><?php VikBookingIcons::e('bed'); ?> <span><?php echo JText::translate('VBEDITORDERTHREE'); ?> #<span class="vbo-quote-option-room-number"></span></span></h4>
            <div class="vbo-quote-option-room-delete">
                <button type="button" class="btn btn-small vbo-btn-transparent vbo-quote-option-room-remove-btn"><?php VikBookingIcons::e('trash', 'no-margin'); ?></button>
            </div>
        </div>
        <div class="vbo-quote-option-availability">
            <span></span>
        </div>
        <div class="vbo-quote-option-room-values">
            <div class="vbo-quote-option-room-value" data-type="listing">
                <label><?php echo JText::translate('VBO_LISTING'); ?></label>
                <select data-field="listing">
                    <option value=""></option>
                <?php
                foreach (VikBooking::getAvailabilityInstance(true)->loadRooms([], 0, true) as $listing) {
                    ?>
                    <option value="<?php echo $listing['id']; ?>"><?php echo $listing['name']; ?></option>
                    <?php
                }
                ?>
                </select>
            </div>
            <div class="vbo-quote-option-room-value" data-type="adults">
                <label><?php echo JText::translate('VBEDITORDERADULTS'); ?></label>
                <input type="number" data-field="adults" value="2" step="1" min="0" />
            </div>
            <div class="vbo-quote-option-room-value" data-type="children">
                <label><?php echo JText::translate('VBEDITORDERCHILDREN'); ?></label>
                <input type="number" data-field="children" value="0" step="1" min="0" />
            </div>
            <div class="vbo-quote-option-room-value" data-type="roomrate">
                <label><?php echo JText::translate('VBOROVWSELRPLAN'); ?></label>
                <select data-field="roomrate">
                    <option value=""></option>
                </select>
                <div class="vbo-quote-label-help text-red" data-type="rate-error" style="display: none;"></div>
            </div>
            <div class="vbo-quote-option-room-value" data-type="customrate">
                <div class="vbo-quote-customrate-value">
                    <label><?php echo JText::translate('VBOROOMCUSTRATEPLANADD'); ?></label>
                    <div class="vbo-input-currency-wrap">
                        <span><?php echo VikBooking::getCurrencySymb(); ?></span>
                        <input type="number" data-field="customrate" value="" min="0" />
                    </div>
                </div>
            <?php
            if ($taxRates) {
                ?>
                <div class="vbo-quote-customrate-idtax" style="display: none;">
                    <label><?php echo JText::translate('VBNEWOPTFOUR'); ?></label>
                    <select data-field="customrate-idtax">
                        <option value=""></option>
                    <?php
                    foreach ($taxRates as $taxRate) {
                        ?>
                        <option value="<?php echo $taxRate['id']; ?>"><?php echo sprintf('%s - %s', (string) $taxRate['name'], $taxRate['aliq'] . '%'); ?></option>
                        <?php
                    }
                    ?>
                    </select>
                </div>
                <?php
            }
            ?>
            </div>
        </div>
        <div class="vbo-editbooking-room-services vbo-quote-option-room-fees">
            <h4><?php echo JText::translate('VBPEDITBUSYEIGHT'); ?></h4>
            <div class="vbo-editbooking-room-services-wrap">
            <?php
            // display all option records, hidden by default
            foreach ($optionRecords as $optionRecord) {
                $isMultiQuantity = boolval($optionRecord['hmany']);
                $isCostPercent = boolval($optionRecord['pcentroom']);
                $isChildAge = (!empty($optionRecord['ifchildren']) && !empty($optionRecord['ageintervals']));
                $displayCost = $isCostPercent ? $optionRecord['cost'] . '%' : VikBooking::formatCurrencyNumber(VikBooking::numberFormat($optionRecord['cost']), $currencysymb);
                ?>
                <div class="vbo-editbooking-room-service" data-eligible="0" data-child-age="<?php echo (int) $isChildAge; ?>" data-option-id="<?php echo $optionRecord['id']; ?>" style="display: none;">
                    <div class="vbo-editbooking-room-service-inner">
                        <label><?php echo $optionRecord['name']; ?></label>
                        <div class="vbo-editbooking-room-service-price" data-cost-value="<?php echo $optionRecord['cost']; ?>" data-cost-pcent="<?php echo (int) $isCostPercent; ?>"><?php echo $displayCost; ?></div>
                    </div>
                    <div class="vbo-editbooking-room-service-check" data-multiple-quantity="<?php echo (int) $isMultiQuantity; ?>">
                <?php
                if (!$isChildAge) {
                    // display an input field by default
                    if ($isMultiQuantity) {
                        // multiple quantity
                        ?>
                        <input type="number" min="0" data-field="option" data-option-id="<?php echo $optionRecord['id']; ?>" value="" />
                        <?php
                    } else {
                        // single quantity
                        ?>
                        <input type="checkbox" data-field="option" data-option-id="<?php echo $optionRecord['id']; ?>" value="1" />
                        <?php
                    }
                }
                ?>
                    </div>
                </div>
                <?php
            }
            ?>
            </div>
        </div>
        <div class="vbo-quote-option-room-extras">
            <div class="vbo-quote-option-room-extras-inner">
                <h4><?php echo JText::translate('VBPEDITBUSYEXTRACOSTS'); ?></h4>
                <button type="button" class="btn vbo-quote-option-room-extra-add-btn"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBPEDITBUSYADDEXTRAC'); ?></button>
            </div>
            <div class="vbo-quote-option-room-extras-list"></div>
        </div>
    </div>

    <div class="vbo-quote-option-room-new-extra-wrap">
        <div class="vbo-editbooking-room-extracost">
            <div class="vbo-ebusy-extracosts-cellname">
                <input type="text" value="" data-field="extra-name" placeholder="<?php echo JHtml::fetch('esc_attr', JText::translate('VBPEDITBUSYEXTRACNAME')); ?>" />
            </div>
            <div class="vbo-ebusy-extracosts-cellcost">
                <div class="vbo-input-currency-wrap">
                    <span><?php echo VikBooking::getCurrencySymb(); ?></span>
                    <input type="number" data-field="extra-cost" value="" min="0" />
                </div>
            </div>
        <?php
        if ($taxRates) {
            ?>
            <div class="vbo-ebusy-extracosts-celltax">
                <select data-field="extra-taxrate">
                    <option value=""><?php echo JText::translate('VBNEWOPTFOUR'); ?></option>
                <?php
                foreach ($taxRates as $taxRate) {
                    ?>
                    <option value="<?php echo $taxRate['id']; ?>"><?php echo sprintf('%s - %s', (string) $taxRate['name'], $taxRate['aliq'] . '%'); ?></option>
                    <?php
                }
                ?>
                </select>
            </div>
            <?php
        }
        ?>
            <div class="vbo-ebusy-extracosts-cellrm">
                <button type="button" class="btn btn-small vbo-btn-transparent vbo-quote-option-extra-remove-btn"><?php VikBookingIcons::e('trash', 'no-margin'); ?></button>
            </div>
        </div>
    </div>

</div>

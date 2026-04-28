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

// load scripts
$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadVisualEditorAssets();
$vbo_app->loadDatePicker();
$vbo_app->loadDatesRangePicker();

// load environment data
$currencysymb = VikBooking::getCurrencySymb();
$currencypos  = VikBooking::getCurrencyPosition();
list($currency_digits, $currency_decimals, $currency_thousands) = explode(':', VikBooking::getNumberFormatData());

?>
<script>
    VBOCore.DOMLoaded(() => {

        /**
         * Whether we are in edit-mode for an existing quote.
         */
        const quoteEditMode = <?php echo $quote ? 'true' : 'false'; ?>;

        /**
         * The current inquiry payload to start populating values.
         */
        const inquiryData = <?php echo $inquiry ? json_encode($inquiry) : 'null'; ?>;

        /**
         * List of preferred message objects.
         */
        const prefMessages = <?php echo json_encode($prefMessages); ?>;

        /**
         * Configure currency object.
         */
        VBOCore.getCurrency({
            symbol:     <?php echo json_encode($currencysymb) ?: '"$"'; ?>,
            position:   <?php echo json_encode($currencypos); ?>,
            digits:     <?php echo intval($currency_digits); ?>,
            decimals:   <?php echo json_encode($currency_decimals) ?: '"."'; ?>,
            thousands:  <?php echo json_encode($currency_thousands) ?: '","'; ?>,
            noDecimals: 1,
        });

        /**
         * Register global event delegation on clicks for any quote option (hidden in edit-mode).
         */
        const quoteOptions = document.querySelector('.vbo-quote-section-body[data-section="options"]');
        quoteOptions.addEventListener('click', (e) => {

            /**
             * Datepicker check-in trigger icon.
             */
            if (e.target.matches('.checkindate-trig') || e.target.closest('.checkindate-trig')) {
                let btnElement = e.target.matches('.checkindate-trig') ? e.target : e.target.closest('.checkindate-trig');
                let inputElement = btnElement.previousElementSibling;
                if (inputElement && inputElement.classList.contains('hasDatepicker')) {
                    inputElement.focus();
                }

                // do not proceed
                return;
            }

            /**
             * Datepicker check-out trigger icon.
             */
            if (e.target.matches('.checkoutdate-trig') || e.target.closest('.checkoutdate-trig')) {
                let btnElement = e.target.matches('.checkoutdate-trig') ? e.target : e.target.closest('.checkoutdate-trig');
                let inputElement = btnElement.previousElementSibling;
                if (inputElement) {
                    // find the check-in input field
                    let checkinEl = inputElement.closest('.vbo-quote-option-dates')?.querySelector('input[data-field="checkin"]');
                    if (checkinEl) {
                        checkinEl.focus();
                    }
                }

                // do not proceed
                return;
            }

            /**
             * Remove quote option button.
             */
            if (e.target.matches('.vbo-quote-option-remove-btn') || e.target.closest('.vbo-quote-option-remove-btn')) {
                e.target.closest('.vbo-quote-new-option-wrap').remove();
                // re-number every quote option
                document.querySelectorAll('.vbo-quote-new-option-wrap').forEach((el, index) => {
                    el.querySelector('.vbo-quote-option-number').textContent = ++index;
                });

                // do not proceed
                return;
            }

            /**
             * Remove quote option room button.
             */
            if (e.target.matches('.vbo-quote-option-room-remove-btn') || e.target.closest('.vbo-quote-option-room-remove-btn')) {
                let quoteOptEl = e.target.closest('.vbo-quote-new-option-wrap');
                e.target.closest('.vbo-quote-option-new-room-wrap').remove();
                // re-number every quote option room
                let quoteOptRoomEl = quoteOptEl.querySelectorAll('.vbo-quote-option-new-room-wrap');
                quoteOptRoomEl.forEach((el, index) => {
                    el.querySelector('.vbo-quote-option-room-number').textContent = ++index;
                });
                if (!quoteOptRoomEl.length) {
                    // hide total element
                    quoteOptEl.querySelector('.vbo-quote-option-total').style.display = 'none';
                }
                // set quote-option room counter
                quoteOptEl.querySelector('[data-counter="rooms"]').textContent = quoteOptRoomEl.length;
                // update quote-option guests counter
                setTotalGuestsCount(quoteOptEl);
                // update solution total amount
                countQuoteTotal(quoteOptEl);


                // do not proceed
                return;
            }

            /**
             * Remove quote option room extra-service button.
             */
            if (e.target.matches('.vbo-quote-option-extra-remove-btn') || e.target.closest('.vbo-quote-option-extra-remove-btn')) {
                let quoteOptEl = e.target.closest('.vbo-quote-new-option-wrap');
                e.target.closest('.vbo-quote-option-room-new-extra-wrap').remove();
                // update solution total amount
                countQuoteTotal(quoteOptEl);

                // do not proceed
                return;
            }

            /**
             * Add-new-quote-option-room button.
             */
            if (e.target.matches('.vbo-quote-option-room-add-btn') || e.target.closest('.vbo-quote-option-room-add-btn')) {
                const quoteOptEl = e.target.closest('.vbo-quote-new-option-wrap');
                const quoteOptTarget = quoteOptEl?.querySelector('.vbo-quote-option-rooms');
                // count new option room number
                const quoteOptRoomNum = quoteOptTarget.querySelectorAll('.vbo-quote-option-new-room-wrap').length + 1;
                // display total element
                quoteOptEl.querySelector('.vbo-quote-option-total').style.display = '';
                // clone helper nodes
                const cloneEl = document.querySelector('.vbo-quote-helper').querySelector('.vbo-quote-option-new-room-wrap').cloneNode(true);
                // append cloned nodes
                quoteOptTarget.appendChild(cloneEl);
                // set quote-option room number and counter
                cloneEl.querySelector('.vbo-quote-option-room-number').textContent = quoteOptRoomNum;
                quoteOptEl.querySelector('[data-counter="rooms"]').textContent = quoteOptRoomNum;
                // update quote-option guests counter
                setTotalGuestsCount(quoteOptEl);

                // listen to the change event for the room select element
                cloneEl.querySelector('select[data-field="listing"]').addEventListener('change', (e) => {
                    // access currently selected listing and stay dates
                    const listingSelEl = e.target;
                    let listingId = listingSelEl.value;
                    let quoteOptionEl = listingSelEl.closest('.vbo-quote-new-option-wrap');
                    let checkinDate = quoteOptionEl.querySelector('input[data-field="checkin"]')?.value;
                    let checkoutDate = quoteOptionEl.querySelector('input[data-field="checkout"]')?.value;
                    let optionRoomEl = listingSelEl.closest('.vbo-quote-option-new-room-wrap');
                    let quoteOptionAvailEl = optionRoomEl.querySelector('.vbo-quote-option-availability').querySelector('span');
                    if (!listingId || !checkinDate || !checkoutDate) {
                        // check nothing
                        quoteOptionAvailEl.textContent = '';
                        return;
                    }
                    // check availability
                    VBOCore.doAjax(
                        "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=isroombookable'); ?>",
                        {
                            rid:   listingId,
                            fdate: checkinDate,
                            tdate: checkoutDate,
                        },
                        (response) => {
                            if (response?.status == 1) {
                                // room is available on the selected dates
                                quoteOptionAvailEl.classList.remove('vbo-message-error');
                                quoteOptionAvailEl.classList.add('vbo-message-success');
                                quoteOptionAvailEl.textContent = <?php echo json_encode(JText::translate('VBO_AV_CONFIRMED_DATES')); ?>;
                            } else {
                                // room is NOT available on the selected dates
                                quoteOptionAvailEl.classList.remove('vbo-message-success');
                                quoteOptionAvailEl.classList.add('vbo-message-error');
                                if (response?.err) {
                                    quoteOptionAvailEl.textContent = response.err;
                                } else {
                                    quoteOptionAvailEl.textContent = 'N/A';
                                }
                                // reset selection
                                listingSelEl.value = '';
                            }
                            // load room rates
                            fetchRatePlans(quoteOptionEl, optionRoomEl);
                        },
                        (error) => {
                            // reset selection
                            listingSelEl.value = '';
                            // display message
                            quoteOptionAvailEl.classList.add('vbo-message-error');
                            quoteOptionAvailEl.textContent = '';
                            alert(error.responseText || 'Request for checking the availability failed.');
                        }
                    );
                });

                // listen to the change event for the room rate select element
                cloneEl.querySelector('select[data-field="roomrate"]').addEventListener('change', (e) => {
                    // update solution total amount
                    countQuoteTotal(e.target.closest('.vbo-quote-new-option-wrap'));
                });

                // listen to the input event for the room custom rate element
                cloneEl.querySelector('input[data-field="customrate"]').addEventListener('input', VBOCore.debounceEvent((e) => {
                    let parentContainer = e.target.closest('.vbo-quote-option-room-value[data-type="customrate"]');
                    let customRateTaxEl = parentContainer.querySelector('.vbo-quote-customrate-idtax');
                    if (customRateTaxEl) {
                        // show or hide the tax rate element for the custom rate
                        customRateTaxEl.style.display = e.target?.value ? '' : 'none';
                    }
                    // update solution total amount
                    countQuoteTotal(e.target.closest('.vbo-quote-new-option-wrap'));
                }, 200));

                // listen to the input event for the adults input element
                cloneEl.querySelector('input[data-field="adults"]').addEventListener('input', VBOCore.debounceEvent((e) => {
                    let quoteOptionEl = e.target?.closest('.vbo-quote-new-option-wrap');
                    let optionRoomEl = e.target?.closest('.vbo-quote-option-new-room-wrap');
                    // update total guests counter
                    setTotalGuestsCount(quoteOptionEl);
                    // load room rates
                    fetchRatePlans(quoteOptionEl, optionRoomEl);
                }, 200));

                // listen to the input event for the children input element
                cloneEl.querySelector('input[data-field="children"]').addEventListener('input', VBOCore.debounceEvent((e) => {
                    let quoteOptionEl = e.target?.closest('.vbo-quote-new-option-wrap');
                    let optionRoomEl = e.target?.closest('.vbo-quote-option-new-room-wrap');
                    // update total guests counter
                    setTotalGuestsCount(quoteOptionEl);
                    // load room rates
                    fetchRatePlans(quoteOptionEl, optionRoomEl);
                }, 200));

                // listen to the input event for every room option/extra with multiple quantity
                cloneEl.querySelectorAll('input[type="number"][data-field="option"]').forEach((roomOptExtraEl) => {
                    roomOptExtraEl.addEventListener('input', VBOCore.debounceEvent((e) => {
                        // update solution total amount
                        countQuoteTotal(e.target.closest('.vbo-quote-new-option-wrap'));
                    }, 200));
                });

                // listen to the change event for every room option/extra with single quantity
                cloneEl.querySelectorAll('input[type="checkbox"][data-field="option"]').forEach((roomOptExtraEl) => {
                    roomOptExtraEl.addEventListener('change', (e) => {
                        // update solution total amount
                        countQuoteTotal(e.target.closest('.vbo-quote-new-option-wrap'));
                    });
                });

                // make every room option/extra label click-able with proper for/id attributes
                cloneEl.querySelectorAll('.vbo-editbooking-room-service[data-eligible]').forEach((roomOptWrapEl) => {
                    let parentEl = roomOptWrapEl.closest('.vbo-quote-new-option-wrap');
                    let labelEl  = roomOptWrapEl.querySelector('.vbo-editbooking-room-service-inner').querySelector('label');
                    let inputEl  = roomOptWrapEl.querySelector('.vbo-editbooking-room-service-check').querySelector('input');
                    let inputOptId = inputEl?.getAttribute('data-option-id');
                    if (!inputEl || !inputOptId) {
                        // we ignore select elements for children age as there could be more than one element
                        return;
                    }
                    let solutionIndex = [...parentEl.parentNode.children].indexOf(parentEl) + 1;
                    let attrValue = 'room-sol-opt-inp-' + solutionIndex + '-' + quoteOptRoomNum + '-' + inputOptId;
                    // set proper attributes
                    labelEl.setAttribute('for', attrValue);
                    inputEl.setAttribute('id', attrValue);
                });

                // do not proceed
                return;
            }

            /**
             * Add-new-quote-option-room-extra button.
             */
            if (e.target.matches('.vbo-quote-option-room-extra-add-btn') || e.target.closest('.vbo-quote-option-room-extra-add-btn')) {
                const quoteOptRoomEl = e.target.closest('.vbo-quote-option-new-room-wrap');
                const quoteOptTarget = quoteOptRoomEl?.querySelector('.vbo-quote-option-room-extras-list');
                // clone helper nodes
                const cloneEl = document.querySelector('.vbo-quote-helper').querySelector('.vbo-quote-option-room-new-extra-wrap').cloneNode(true);
                // append cloned nodes
                quoteOptTarget.appendChild(cloneEl);

                // listen to the input event for the room extra service name (name is required for the cost to be applied)
                cloneEl.querySelector('input[data-field="extra-name"]').addEventListener('input', VBOCore.debounceEvent((e) => {
                    // update solution total amount
                    countQuoteTotal(e.target.closest('.vbo-quote-new-option-wrap'));
                }, 200));

                // listen to the input event for the room extra service cost
                cloneEl.querySelector('input[data-field="extra-cost"]').addEventListener('input', VBOCore.debounceEvent((e) => {
                    // update solution total amount
                    countQuoteTotal(e.target.closest('.vbo-quote-new-option-wrap'));
                }, 200));

                // do not proceed
                return;
            }

        });

        /**
         * Register click event on add-new-quote-option button (hidden in edit-mode).
         */
        let quoteOptCounter = 0;
        document.querySelector('.vbo-quote-option-add-btn')?.addEventListener('click', () => {
            // increase global counter (that should never be decreased to avoid conflicts with date-picker calendars)
            quoteOptCounter++;
            // count new option number
            const quoteOptNum = quoteOptions.querySelectorAll('.vbo-quote-new-option-wrap').length + 1;
            // clone helper nodes
            const cloneEl = document.querySelector('.vbo-quote-helper').querySelector('.vbo-quote-new-option-wrap').cloneNode(true);
            // set active attribute
            cloneEl.setAttribute('data-active', 1);
            // append cloned nodes
            quoteOptions.appendChild(cloneEl);
            // set option number
            cloneEl.querySelector('.vbo-quote-option-number').textContent = quoteOptNum;
            // get date elements
            const checkinEl = cloneEl.querySelector('input[data-field="checkin"]');
            const checkoutEl = cloneEl.querySelector('input[data-field="checkout"]');
            // build and set unique ID attribute
            let checkinElSel = 'vbo-quote-option-checkin-' + quoteOptCounter;
            let checkoutElSel = 'vbo-quote-option-checkout-' + quoteOptCounter;
            checkinEl.setAttribute('id', checkinElSel);
            checkoutEl.setAttribute('id', checkoutElSel);
            // set unique "for" attribute to labels
            checkinEl.closest('.vbo-quote-option-date').querySelector('label').setAttribute('for', checkinElSel);
            checkoutEl.closest('.vbo-quote-option-date').querySelector('label').setAttribute('for', checkoutElSel);
            // render datepicker calendars
            jQuery('#' + checkinElSel).vboDatesRangePicker({
                checkout: '#' + checkoutElSel,
                showOn: "focus",
                minDate: '0d',
                maxDate: '+2y',
                dateFormat: 'yy-mm-dd',
                numberOfMonths: 2,
                responsiveNumMonths: {
                    threshold: 860,
                },
                onSelect: {
                    checkin: (selectedDate) => {
                        let nowcheckin = jQuery('#' + checkinElSel).vboDatesRangePicker('getCheckinDate');
                        let nowcheckindate = new Date(nowcheckin.getTime());
                        nowcheckindate.setDate(nowcheckindate.getDate());
                        jQuery('#' + checkinElSel).vboDatesRangePicker('checkout', 'minDate', nowcheckindate);
                        // calculate nights
                        let totNights = calcNights(checkinEl.value, checkoutEl.value);
                        cloneEl.querySelector('[data-counter="nights"]').textContent = totNights || '0';
                        if (totNights > 0) {
                            // check if any listings were selected
                            cloneEl.querySelectorAll('select[data-field="listing"]').forEach((listingSel) => {
                                if (listingSel.value) {
                                    // trigger change event to re-calculate the availability
                                    listingSel.dispatchEvent(new Event('change'));
                                }
                            });
                        }
                    },
                    checkout: (selectedDate) => {
                        if (!jQuery('#' + checkinElSel).vboDatesRangePicker('getCheckoutDate')) {
                            return;
                        }
                        // calculate nights
                        let totNights = calcNights(checkinEl.value, checkoutEl.value);
                        cloneEl.querySelector('[data-counter="nights"]').textContent = totNights || '0';
                        if (totNights > 0) {
                            // check if any listings were selected
                            cloneEl.querySelectorAll('select[data-field="listing"]').forEach((listingSel) => {
                                if (listingSel.value) {
                                    // trigger change event to re-calculate the availability
                                    listingSel.dispatchEvent(new Event('change'));
                                }
                            });
                        }
                    },
                },
                labels: {
                    checkin: <?php echo json_encode(JText::translate('VBPICKUPROOM')); ?>,
                    checkout: <?php echo json_encode(JText::translate('VBRETURNROOM')); ?>,
                },
                bottomCommands: {
                    clear: <?php echo json_encode(JText::translate('VBO_CLEAR_DATES')); ?>,
                    close: <?php echo json_encode(JText::translate('VBO_CLOSE')); ?>,
                    onClear: () => {
                        // calculate nights
                        let totNights = calcNights(checkinEl.value, checkoutEl.value);
                        cloneEl.querySelector('[data-counter="nights"]').textContent = '0';
                    },
                },
                environment: {
                    section: 'admin',
                    autoHide: true,
                },
            });
        });

        /**
         * Register event listener for the quote customer selection.
         */
        document.addEventListener('vbo-quote-customer-chosen', (e) => {
            if (e?.detail?.element?.id) {
                if (e?.detail?.element?.first_name) {
                    document.querySelector('input[data-field="customer-firstname"]').value = e.detail.element.first_name;
                }
                if (e?.detail?.element?.last_name) {
                    document.querySelector('input[data-field="customer-lastname"]').value = e.detail.element.last_name;
                }
                if (e?.detail?.element?.email) {
                    document.querySelector('input[data-field="customer-email"]').value = e.detail.element.email;
                }
                if (e?.detail?.element?.phone) {
                    let customerPhoneFieldEl = document.querySelector('input[data-field="customer-phone"]');
                    if (customerPhoneFieldEl) {
                        customerPhoneFieldEl.value = e.detail.element.phone;
                        customerPhoneFieldEl.dispatchEvent(new Event('input'));
                        customerPhoneFieldEl.dispatchEvent(new Event('blur'));
                    }
                }
                if (e?.detail?.element?.country) {
                    document.querySelector('input[data-field="customer-country"]').value = e.detail.element.country;
                }
            }
        });

        /**
         * Register event listener for the "send later" toggle button (if available).
         */
        document.querySelector('input[type="checkbox"][name="quote_send_later"]')?.addEventListener('change', (e) => {
            let submitBtn = document.querySelector('.vbo-quote-submit-btn');
            let submitIcn = submitBtn.querySelector('i');
            submitIcn.setAttribute('class', '');
            if (e.target?.checked) {
                submitBtn.lastChild.textContent = <?php echo json_encode(' ' . JText::translate('VBO_SAVE_QUOTE')); ?>;
                submitIcn.classList.add(...String('<?php echo VikBookingIcons::i('save'); ?>').split(' '));
            } else {
                submitBtn.lastChild.textContent = <?php echo json_encode(' ' . JText::translate('VBO_SEND_QUOTE')); ?>;
                submitIcn.classList.add(...String('<?php echo VikBookingIcons::i('paper-plane'); ?>').split(' '));
            }
        });

        /**
         * Register event listener for the selection of a message template (if available).
         */
        document.querySelector('select[data-field="message-preferred-tpl"]')?.addEventListener('change', (e) => {
            let quoteId = e.target.value;
            let textAreaEl = document.querySelector('textarea#vbo-quote-message-content');
            if (!quoteId || !textAreaEl || !Array.isArray(prefMessages)) {
                return;
            }

            let messageData = null;
            prefMessages.forEach((prefMessage) => {
                if (messageData) {
                    return;
                }
                if (prefMessage?.id == quoteId) {
                    messageData = prefMessage;
                }
            });

            if (messageData) {
                textAreaEl.value = messageData.message;
                textAreaEl.dispatchEvent(new Event('change'));
                document.querySelector('input[data-field="mail-subject"]').value = messageData.subject || '';
                document.querySelector('textarea[data-field="notes"]').value = messageData.notes || '';
                let savePreferred = document.querySelector('input[type="checkbox"][name="quote_message_preferred"]');
                if (savePreferred) {
                    savePreferred.checked = false;
                }
            }
        });

        /**
         * Register event listener to obtain the selected country data via phone input.
         */
        document.addEventListener('vbo-quote-get-phone-country-data', (e) => {
            if (e?.detail?.iso2) {
                document.querySelector('input[data-field="customer-country"]').value = e.detail.iso2;
            }
        });

        /**
         * Register event listener for the valid until help icon.
         */
        document.querySelector('.vbo-quote-validity-date-help')?.addEventListener('click', (e) => {
            VBOCore.displayModal({
                extra_class: 'vbo-modal-rounded vbo-modal-tooltip',
                body:        <?php echo json_encode(JText::translate('VBO_LOCK_UNTIL_HELP')); ?>,
                lock_scroll: true,
                draggable:   false,
            });
        });

        /**
         * Register event listener for the quote submit (save new) button.
         */
        document.querySelector('.vbo-quote-submit-btn')?.addEventListener('click', (e) => {
            const submitBtn = e.target.matches('button') ? e.target : e.target.closest('button');
            const submitIcn = submitBtn.querySelector('i');
            let submitIcnClass = submitIcn.getAttribute('class');

            const optionElements = document.querySelectorAll('.vbo-quote-new-option-wrap[data-active="1"]');
            if (!optionElements.length) {
                alert('Please add at least one booking solution.');
                return false;
            }

            // quickly validate that we've got at least one valid booking solution
            let validSolutions = 0;
            optionElements.forEach((optionEl) => {
                let checkinDate = optionEl.querySelector('input[data-field="checkin"]')?.value;
                let checkoutDate = optionEl.querySelector('input[data-field="checkout"]')?.value;
                if (!checkinDate || !checkoutDate) {
                    return;
                }
                optionEl.querySelectorAll('.vbo-quote-option-new-room-wrap').forEach((optionRoomEl) => {
                    let listingId = optionRoomEl.querySelector('select[data-field="listing"]')?.value;
                    if (listingId) {
                        validSolutions++;
                    }
                });
            });
            if (!validSolutions) {
                alert('Please configure at least one room booking solution.');
                return false;
            }

            // disable button and start loading animation
            submitBtn.disabled = true;
            submitIcn.setAttribute('class', '');
            submitIcn.classList.add(...String('<?php echo VikBookingIcons::i('circle-notch', 'fa-spin fa-fw'); ?>').split(' '));

            // collect quotation values
            let quoteValues = {
                name:       document.querySelector('input[data-field="quote-name"]')?.value,
                validity:   document.querySelector('input[data-field="valid-until"]')?.value,
                id_payment: document.querySelector('select[data-field="quote-payment"]')?.value,
                customer: {
                    id:         document.querySelector('select[data-field="customer-id"]')?.value,
                    first_name: document.querySelector('input[data-field="customer-firstname"]')?.value,
                    last_name:  document.querySelector('input[data-field="customer-lastname"]')?.value,
                    email:      document.querySelector('input[data-field="customer-email"]')?.value,
                    phone:      document.querySelector('input[data-field="customer-phone"]')?.value,
                    country:    document.querySelector('input[data-field="customer-country"]')?.value,
                },
                message: {
                    subject:   document.querySelector('input[data-field="mail-subject"]')?.value,
                    content:   document.querySelector('textarea[name="quote_message_content"]')?.value,
                    notes:     document.querySelector('textarea[data-field="notes"]')?.value,
                    send:      document.querySelector('input[type="checkbox"][name="quote_send_later"]')?.checked ? 0 : 1,
                    preferred: document.querySelector('input[type="checkbox"][name="quote_message_preferred"]')?.checked ? 1 : 0,
                },
                solutions: [],
            };

            // iterate all booking solutions
            optionElements.forEach((optionEl) => {
                let checkinDate = optionEl.querySelector('input[data-field="checkin"]')?.value;
                let checkoutDate = optionEl.querySelector('input[data-field="checkout"]')?.value;
                if (!checkinDate || !checkoutDate) {
                    // skip invalid booking solution
                    return;
                }

                // build booking solution object
                let bookingSolution = {
                    checkin:  checkinDate,
                    checkout: checkoutDate,
                    rooms:    [],
                };

                // iterate all booking rooms
                optionEl.querySelectorAll('.vbo-quote-option-new-room-wrap').forEach((optionRoomEl) => {
                    let listingId = optionRoomEl.querySelector('select[data-field="listing"]')?.value;
                    if (!listingId) {
                        // skip invalid booking room
                        return;
                    }

                    // room rate plan element
                    let roomRateEl = optionRoomEl.querySelector('select[data-field="roomrate"]');

                    // build booking room object
                    let bookingRoom = {
                        id:          listingId,
                        adults:      optionRoomEl.querySelector('input[data-field="adults"]')?.value,
                        children:    optionRoomEl.querySelector('input[data-field="children"]')?.value,
                        id_price:    roomRateEl?.value,
                        room_cost:   roomRateEl?.value ? Number(roomRateEl.options[roomRateEl.selectedIndex].getAttribute('data-cost')) : 0,
                        cust_cost:   optionRoomEl.querySelector('input[data-field="customrate"]')?.value,
                        id_tax:      optionRoomEl.querySelector('select[data-field="customrate-idtax"]')?.value,
                        options:     [],
                        extras:      [],
                    };

                    // iterate all booking room option records
                    optionRoomEl.querySelectorAll('.vbo-editbooking-room-service[data-eligible="1"]').forEach((optRecordEl) => {
                        let isChildAge = optRecordEl.getAttribute('data-child-age') == 1;
                        // build booking room option object
                        let bookingRoomOption = {
                            id:            optRecordEl.getAttribute('data-option-id'),
                            quantity:      0,
                            cost:          0,
                            is_child_age:  isChildAge ? 1 : 0,
                            age_intervals: [],
                        };

                        if (isChildAge) {
                            // one drop-down select element per child is expected
                            let childAgeFulfilled = true;
                            optRecordEl.querySelectorAll('select[data-field="child-age"]').forEach((childAgeSelEl, childAgeSelIndex) => {
                                if (!childAgeSelEl.value) {
                                    childAgeFulfilled = false;
                                    return;
                                }
                                // push child age interval value
                                bookingRoomOption.age_intervals.push(childAgeSelEl.value);
                                // check computed cost
                                let childIntervalCost = Number(childAgeSelEl.options[childAgeSelEl.selectedIndex].getAttribute('data-computed-cost'));
                                if (isNaN(childIntervalCost)) {
                                    return;
                                }
                                // update option computed cost
                                bookingRoomOption.cost += childIntervalCost;
                            });

                            if (childAgeFulfilled === true) {
                                // push booking room option
                                bookingRoom.options.push(bookingRoomOption);
                            }
                        } else {
                            // regular option
                            let checkboxEl = optRecordEl.querySelector('input[type="checkbox"]');
                            let numberEl = optRecordEl.querySelector('input[type="number"]');
                            let priceEl = optRecordEl.querySelector('.vbo-editbooking-room-service-price');
                            let computedCost = 0;
                            if (checkboxEl && checkboxEl.checked) {
                                // single-quantity option checked
                                computedCost = Number(priceEl.getAttribute('data-computed-cost'));
                                // set option quantity
                                bookingRoomOption.quantity = 1;
                            } else if (numberEl && numberEl.value && parseInt(numberEl.value) > 0) {
                                // multiple quantity option fulfilled
                                let quantity = parseInt(numberEl.value);
                                computedCost = Number(priceEl.getAttribute('data-computed-cost'));
                                if (computedCost && !isNaN(computedCost)) {
                                    computedCost = computedCost * quantity;
                                }
                                // set option quantity
                                bookingRoomOption.quantity = quantity;
                            }
                            if (computedCost && !isNaN(computedCost)) {
                                // set option computed cost
                                bookingRoomOption.cost = computedCost;
                            }

                            if (bookingRoomOption.quantity || bookingRoomOption.cost) {
                                // push booking room option
                                bookingRoom.options.push(bookingRoomOption);
                            }
                        }
                    });

                    // iterate all booking room extra services
                    optionRoomEl.querySelectorAll('.vbo-quote-option-room-new-extra-wrap').forEach((extraEl) => {
                        let extraName = extraEl.querySelector('input[data-field="extra-name"]')?.value;
                        let extraCost = Number(extraEl.querySelector('input[data-field="extra-cost"]')?.value);
                        if (!extraName || !extraCost || isNaN(extraCost)) {
                            // ignore when extra service name or cost are missing or invalid
                            return;
                        }

                        // push booking room extra service
                        bookingRoom.extras.push({
                            name: extraName,
                            cost: extraCost,
                            vat:  extraEl.querySelector('select[data-field="extra-taxrate"]')?.value,
                        });
                    });

                    if ((bookingRoom.id_price && bookingRoom.room_cost) || bookingRoom.cust_cost) {
                        // push booking room
                        bookingSolution.rooms.push(bookingRoom);
                    }
                });

                if (bookingSolution.rooms.length) {
                    // push booking solution
                    quoteValues.solutions.push(bookingSolution);
                }
            });

            // make the request to save the quotation record and related booking solutions
            VBOCore.doAjax(
                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=quote.save'); ?>",
                {
                    quote: quoteValues,
                    session_id: <?php echo (int) $session_id; ?>,
                },
                (response) => {
                    if (response?.sending_error) {
                        // display an alert message just for the email sending error
                        alert(response.sending_error);
                    }
                    // display toast message on success
                    VBOToast.enqueue(new VBOToastMessage({
                        title:  <?php echo json_encode(JText::translate('VBPVIEWORDERSEIGHT')); ?>,
                        body:   <?php echo json_encode(JText::translate('VBOCHECKINSTATUSUPDATED')); ?>,
                        status: VBOToast.SUCCESS_STATUS,
                        delay:  {
                            min: 6000,
                            max: 20000,
                            tolerance: 4000,
                        },
                        action: () => {
                            VBOToast.dispose(true);
                        }
                    }));
                    // redirect to bookings list on success
                    location.href = '<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=orders&quote_saved=1', false); ?>';
                },
                (error) => {
                    // display error
                    alert(error.responseText || 'Could not complete the operation.');
                    // restore button
                    submitBtn.disabled = false;
                    submitIcn.setAttribute('class', '');
                    submitIcn.classList.add(...submitIcnClass.split(' '));
                }
            );
        });

        /**
         * Register event listener for the quote update (edit quote) button.
         */
        document.querySelector('.vbo-quote-update-btn')?.addEventListener('click', (e) => {
            const submitBtn = e.target.matches('button') ? e.target : e.target.closest('button');
            const submitIcn = submitBtn.querySelector('i');
            let submitIcnClass = submitIcn.getAttribute('class');

            const quoteId = document.querySelector('.vbo-quote-section-current[data-quote-id]')?.getAttribute('data-quote-id');
            if (!quoteId) {
                alert('Could not find the quote ID to update.');
                return false;
            }

            // disable button and start loading animation
            submitBtn.disabled = true;
            submitIcn.setAttribute('class', '');
            submitIcn.classList.add(...String('<?php echo VikBookingIcons::i('circle-notch', 'fa-spin fa-fw'); ?>').split(' '));

            // collect quotation values for update
            let quoteValues = {
                id:         quoteId,
                name:       document.querySelector('input[data-field="quote-name"]')?.value,
                validity:   document.querySelector('input[data-field="valid-until"]')?.value,
                id_payment: document.querySelector('select[data-field="quote-payment"]')?.value,
                customer: {
                    id:         document.querySelector('select[data-field="customer-id"]')?.value,
                    first_name: document.querySelector('input[data-field="customer-firstname"]')?.value,
                    last_name:  document.querySelector('input[data-field="customer-lastname"]')?.value,
                    email:      document.querySelector('input[data-field="customer-email"]')?.value,
                    phone:      document.querySelector('input[data-field="customer-phone"]')?.value,
                    country:    document.querySelector('input[data-field="customer-country"]')?.value,
                },
                message: {
                    subject:   document.querySelector('input[data-field="mail-subject"]')?.value,
                    content:   document.querySelector('textarea[name="quote_message_content"]')?.value,
                    notes:     document.querySelector('textarea[data-field="notes"]')?.value,
                    preferred: document.querySelector('input[type="checkbox"][name="quote_message_preferred"]')?.checked ? 1 : 0,
                },
            };

            // make the request to update the current quotation record
            VBOCore.doAjax(
                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=quote.update'); ?>",
                {
                    quote: quoteValues,
                },
                (response) => {
                    // display toast message on success
                    VBOToast.enqueue(new VBOToastMessage({
                        title:  <?php echo json_encode(JText::translate('VBPVIEWORDERSEIGHT')); ?>,
                        body:   <?php echo json_encode(JText::translate('VBOCHECKINSTATUSUPDATED')); ?>,
                        status: VBOToast.SUCCESS_STATUS,
                        delay:  {
                            min: 6000,
                            max: 20000,
                            tolerance: 4000,
                        },
                        action: () => {
                            VBOToast.dispose(true);
                        }
                    }));
                    // reload the page on success
                    location.reload();
                },
                (error) => {
                    // display error
                    alert(error.responseText || 'Could not complete the operation.');
                    // restore button
                    submitBtn.disabled = false;
                    submitIcn.setAttribute('class', '');
                    submitIcn.classList.add(...submitIcnClass.split(' '));
                }
            );
        });

        /**
         * Register event listener for the quote delete (edit quote) button.
         */
        document.querySelector('.vbo-quote-delete-btn')?.addEventListener('click', (e) => {
            const submitBtn = e.target.matches('button') ? e.target : e.target.closest('button');
            const submitIcn = submitBtn.querySelector('i');
            let submitIcnClass = submitIcn.getAttribute('class');

            const quoteId = document.querySelector('.vbo-quote-section-current[data-quote-id]')?.getAttribute('data-quote-id');
            if (!quoteId) {
                alert('Could not find the quote ID to delete.');
                return false;
            }

            if (!confirm(<?php echo json_encode(JText::translate('VBDELCONFIRM')); ?>)) {
                return false;
            }

            // disable button and start loading animation
            submitBtn.disabled = true;
            submitIcn.setAttribute('class', '');
            submitIcn.classList.add(...String('<?php echo VikBookingIcons::i('circle-notch', 'fa-spin fa-fw'); ?>').split(' '));

            // make the request to delete the current quotation record
            VBOCore.doAjax(
                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=quote.delete'); ?>",
                {
                    quote_id: quoteId,
                },
                (response) => {
                    // display toast message on success
                    VBOToast.enqueue(new VBOToastMessage({
                        title:  <?php echo json_encode(JText::translate('VBPVIEWORDERSEIGHT')); ?>,
                        body:   <?php echo json_encode(JText::translate('VBOCHECKINSTATUSUPDATED')); ?>,
                        status: VBOToast.SUCCESS_STATUS,
                        delay:  {
                            min: 6000,
                            max: 20000,
                            tolerance: 4000,
                        },
                        action: () => {
                            VBOToast.dispose(true);
                        }
                    }));
                    // redirect to bookings list on success
                    location.href = '<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=orders&quote_deleted=1', false); ?>';
                },
                (error) => {
                    // display error
                    alert(error.responseText || 'Could not complete the operation.');
                    // restore button
                    submitBtn.disabled = false;
                    submitIcn.setAttribute('class', '');
                    submitIcn.classList.add(...submitIcnClass.split(' '));
                }
            );
        });

        /**
         * Register function to calculate the nights between two date strings.
         * 
         * @param   string  checkin     The check-in date.
         * @param   string  checkout    The check-out date.
         * 
         * @return  ?number
         */
        const calcNights = (checkin, checkout) => {
            if (!checkin || !checkout) {
                return null;
            }
            let checkinDate = new Date(checkin);
            let checkoutDate = new Date(checkout);
            let utc1 = Date.UTC(checkinDate.getFullYear(), checkinDate.getMonth(), checkinDate.getDate());
            let utc2 = Date.UTC(checkoutDate.getFullYear(), checkoutDate.getMonth(), checkoutDate.getDate());

            return Math.ceil((utc2 - utc1) / (1000 * 60 * 60 * 24));
        };

        /**
         * Register function to update the total guests count within a quote option.
         * 
         * @param   Element     quoteOptionEl   The quote-option element.
         * 
         * @return  undefined
         */
        const setTotalGuestsCount = (quoteOptionEl) => {
            if (!quoteOptionEl) {
                return;
            }
            let guests = 0;
            quoteOptionEl.querySelectorAll('input[data-field="adults"]').forEach((field) => {
                guests += !isNaN(field.value) ? parseInt(field.value) : 0;
            });
            quoteOptionEl.querySelectorAll('input[data-field="children"]').forEach((field) => {
                guests += !isNaN(field.value) ? parseInt(field.value) : 0;
            });
            quoteOptionEl.querySelector('[data-counter="guests"]').textContent = guests;
        };

        /**
         * Register function to update the rate plans for a room within a quote option.
         * The function will also load the eligible options to show their pricing info.
         * 
         * @param   Element     quoteOptionEl   The quote-option element.
         * @param   Element     optionRoomEl    The quote-option-room element.
         * 
         * @return  undefined
         */
        const fetchRatePlans = (quoteOptionEl, optionRoomEl) => {
            if (!quoteOptionEl || !optionRoomEl) {
                throw new Error('Invalid element arguments provided.');
            }

            let listingId      = optionRoomEl.querySelector('select[data-field="listing"]')?.value;
            let roomRateEl     = optionRoomEl.querySelector('select[data-field="roomrate"]');
            let rateErrorEl    = optionRoomEl.querySelector('.vbo-quote-label-help[data-type="rate-error"]');
            let checkinDate    = quoteOptionEl.querySelector('input[data-field="checkin"]')?.value;
            let checkoutDate   = quoteOptionEl.querySelector('input[data-field="checkout"]')?.value;
            let previousRateId = roomRateEl?.value;
            let numAdults      = optionRoomEl.querySelector('input[data-field="adults"]')?.value;
            let numChildren    = optionRoomEl.querySelector('input[data-field="children"]')?.value;
            let numNights      = parseInt(quoteOptionEl.querySelector('[data-counter="nights"]')?.innerText || 1);

            // ensure the number of nights of stay is a number
            numNights = isNaN(numNights) ? 1 : numNights;

            // always reset the rate plan select element
            roomRateEl.value = '';
            roomRateEl.querySelectorAll('option').forEach((opt) => {
                if (!opt.getAttribute('value')) {
                    // keep the empty option
                    return;
                }
                opt.remove();
            });

            // always empty and hide the rate error element
            rateErrorEl.textContent = '';
            rateErrorEl.style.display = 'none';

            if (!listingId || !checkinDate || !checkoutDate) {
                // do nothing
                return;
            }
            // make the request
            VBOCore.doAjax(
                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=calc_rates'); ?>",
                {
                    id_room:       listingId,
                    checkinfdate:  checkinDate,
                    checkoutfdate: checkoutDate,
                    num_adults:    numAdults,
                    num_children:  numChildren,
                    units:         1,
                    only_rates:    1,
                },
                (response) => {
                    // list of rate plan objects expected in case of success
                    if (!Array.isArray(response) || typeof response[0] === 'string' || (response[1] || 0) == -1) {
                        // error expected, but the drop down element is already empty
                        if (Array.isArray(response) && typeof response[0] === 'string') {
                            // display the error encountered
                            rateErrorEl.textContent = response[0].replace('e4j.error.', '');
                            rateErrorEl.style.display = '';
                        }
                    } else {
                        // check if rates are inclusive of tax
                        let ratesTaxInclusive = <?php echo VikBooking::ivaInclusa() ? 1 : 0; ?>;

                        // populate option elements
                        response.forEach((ratePlan) => {
                            let ratePlanOptEl = document.createElement('option');
                            ratePlanOptEl.setAttribute('value', ratePlan?.idprice);
                            ratePlanOptEl.setAttribute('data-cost', ratesTaxInclusive ? ratePlan?.tot : ratePlan?.net);
                            ratePlanOptEl.textContent = ratePlan?.name || 'Rate plan';
                            ratePlanOptEl.textContent += ' - ' + (ratesTaxInclusive ? ratePlan?.ftot : ratePlan?.fnet);
                            if (previousRateId && previousRateId == ratePlan?.idprice) {
                                ratePlanOptEl.selected = true;
                            }
                            roomRateEl.append(ratePlanOptEl);
                        });

                        // fetch eligible options
                        VBOCore.doAjax(
                            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=listings.get_eligible_options'); ?>",
                            {
                                listing_id: listingId,
                                adults:     numAdults,
                                children:   numChildren,
                                checkin:    checkinDate,
                                checkout:   checkoutDate,
                            },
                            (optionRecords) => {
                                if (!Array.isArray(optionRecords)) {
                                    // unexpected response format
                                    console.error('listings.get_eligible_options: unexpected response format.', optionRecords);
                                    return;
                                }

                                // obtain all the eligible option record IDs
                                let eligibleIds = [];
                                optionRecords.forEach((optRecord) => {
                                    eligibleIds.push(Number(optRecord.id));
                                });

                                // display or hide the option record elements based on eligibility
                                optionRoomEl.querySelectorAll('.vbo-editbooking-room-service[data-option-id]').forEach((optEl) => {
                                    let optionRecordId = Number(optEl.getAttribute('data-option-id'));
                                    // check eligibility
                                    if (!optionRecordId || !eligibleIds.includes(optionRecordId)) {
                                        // hide non-eligible element
                                        optEl.style.display = 'none';
                                        optEl.setAttribute('data-eligible', 0);
                                    } else {
                                        // show eligible element
                                        optEl.style.display = '';
                                        optEl.setAttribute('data-eligible', 1);
                                    }
                                });

                                // update computed pricing and mandatory selection for every eligible option record
                                optionRecords.forEach((optRecord) => {
                                    let optEl = optionRoomEl.querySelector('.vbo-editbooking-room-service[data-option-id="' + optRecord.id + '"]');
                                    let isChildAge = optEl.getAttribute('data-child-age') == 1;
                                    let optPriceEl = optEl.querySelector('.vbo-editbooking-room-service-price');
                                    let optInputWrapEl = optEl.querySelector('.vbo-editbooking-room-service-check');
                                    let isMultiQuantity = optInputWrapEl?.getAttribute('data-multiple-quantity') == 1;

                                    // reset price content
                                    optPriceEl.textContent = '';
                                    optPriceEl.setAttribute('data-computed-cost', '');

                                    // handle type of option
                                    if (isChildAge && optRecord?.ageintervals) {
                                        // handle children age intervals element
                                        optInputWrapEl.innerHTML = '';

                                        if (typeof optRecord?._computed_children_costs === 'object' && typeof optRecord?._computed_age_intervals === 'object') {
                                            // loop over all children
                                            for (ch = 1; ch <= Number(numChildren); ch++) {
                                                if (!optRecord._computed_children_costs[ch] || !optRecord._computed_age_intervals[ch]) {
                                                    // unknown values for this child number
                                                    continue;
                                                }
                                                if (!Array.isArray(optRecord._computed_children_costs[ch]) || !optRecord._computed_children_costs[ch].length) {
                                                    // missing cost values for this child number
                                                    continue;
                                                }
                                                if (!Array.isArray(optRecord._computed_age_intervals[ch]) || !optRecord._computed_age_intervals[ch].length) {
                                                    // missing interval values for this child number
                                                    continue;
                                                }

                                                // build drop down element container
                                                let containerEl = document.createElement('div');
                                                containerEl.classList.add('vbo-quote-child-age-wrap');

                                                // build label element
                                                let labelEl = document.createElement('label');
                                                labelEl.textContent = <?php echo json_encode(JText::translate('VBMAILCHILD') . ' #'); ?> + ch;

                                                // build drop down select element
                                                let selectEl = document.createElement('select');
                                                selectEl.setAttribute('data-field', 'child-age');
                                                selectEl.addEventListener('change', (e) => {
                                                    // update solution total amount
                                                    countQuoteTotal(quoteOptionEl);
                                                });

                                                // scan all child age intervals and related costs
                                                optRecord._computed_age_intervals[ch].forEach((ageInterval, intervalIndex) => {
                                                    let ageIntervalCost = Number(optRecord._computed_children_costs[ch][intervalIndex]);
                                                    let ageOptEl = document.createElement('option');
                                                    ageOptEl.setAttribute('data-computed-cost', ageIntervalCost);
                                                    ageOptEl.value = ++intervalIndex;
                                                    ageOptEl.textContent = ageInterval + ' (' + VBOCore.getCurrency().format(ageIntervalCost) + ')';
                                                    selectEl.append(ageOptEl);
                                                });

                                                // append elements to DOM
                                                containerEl.append(labelEl);
                                                containerEl.append(selectEl);
                                                optInputWrapEl.append(containerEl);
                                            }
                                        }
                                    } else {
                                        // handle regular option record
                                        optPriceEl.innerHTML = VBOCore.getCurrency().format(optRecord?._computed_cost || optRecord.cost);
                                        optPriceEl.setAttribute('data-computed-cost', optRecord?._computed_cost || optRecord.cost);

                                        // check if we have a mandatory fee
                                        if (optRecord?.forcesel == 1) {
                                            // pre-select state
                                            if (isMultiQuantity) {
                                                // input number expected
                                                let forceSettings = (optRecord?.forceval || '').split('-');
                                                let quantity = parseInt(forceSettings[0] || 1);
                                                if (forceSettings[1] && forceSettings[1] == 1) {
                                                    // forced quantity to be multiplied per night of stay
                                                    quantity = quantity * numNights;
                                                }
                                                if (forceSettings[2] && forceSettings[2] == 1) {
                                                    // forced quantity to be multiplied per child
                                                    quantity = quantity * Number(numChildren);
                                                }
                                                optInputWrapEl.querySelector('input').value = quantity;
                                            } else {
                                                // checkbox expected
                                                optInputWrapEl.querySelector('input[type="checkbox"]').checked = true;
                                            }
                                        }
                                    }
                                });

                                setTimeout(() => {
                                    // update solution total amount
                                    countQuoteTotal(quoteOptionEl);
                                }, 200);
                            },
                            (error) => {
                                // hide all options in case of error
                                optionRoomEl.querySelectorAll('.vbo-editbooking-room-service[data-option-id]').forEach((optEl) => {
                                    optEl.style.display = 'none';
                                });
                            }
                        );
                    }
                },
                (error) => {
                    alert(error.responseText || 'Could not fetch room rates.');
                }
            );
        };

        /**
         * Register function to count the total amount for the given quote option.
         * 
         * @param   Element     quoteOptionEl   The quote-option element.
         * 
         * @return  undefined
         */
        const countQuoteTotal = (quoteOptionEl) => {
            if (!quoteOptionEl) {
                return;
            }

            let totalAmount = 0;

            // iterate all rooms within the current quote
            quoteOptionEl.querySelectorAll('.vbo-quote-option-new-room-wrap').forEach((quoteOptRoomEl, roomIndex) => {
                let listingId = quoteOptRoomEl.querySelector('select[data-field="listing"]')?.value;
                if (!listingId) {
                    // do not increase the total amount counter when room is missing
                    return;
                }
                let roomCost   = 0;
                let roomRateId = quoteOptRoomEl.querySelector('select[data-field="roomrate"]')?.value;
                let customRate = quoteOptRoomEl.querySelector('input[data-field="customrate"]')?.value;
                if (!roomRateId && !customRate) {
                    // do not increase the total amount counter when rate is missing, either a rate plan or a custom rate
                    return;
                }
                if (customRate) {
                    roomCost = Number(customRate);
                } else {
                    let roomRateEl = quoteOptRoomEl.querySelector('select[data-field="roomrate"]');
                    roomCost = Number(roomRateEl.options[roomRateEl.selectedIndex].getAttribute('data-cost'));
                }
                if (!roomCost || isNaN(roomCost)) {
                    // do not increase the total amount counter in case of invalid room cost
                    return;
                }

                // add room cost to solution total amount
                totalAmount += roomCost;

                // scan all room eligible and checked options
                quoteOptRoomEl.querySelectorAll('.vbo-editbooking-room-service[data-eligible="1"]').forEach((optRecordEl) => {
                    let optRecordId = optRecordEl.getAttribute('data-option-id');
                    let isChildAge = optRecordEl.getAttribute('data-child-age') == 1;
                    if (isChildAge) {
                        // one drop-down select element per child is expected
                        optRecordEl.querySelectorAll('select[data-field="child-age"]').forEach((childAgeSelEl, childAgeSelIndex) => {
                            if (!childAgeSelEl.value) {
                                return;
                            }
                            let childIntervalCost = Number(childAgeSelEl.options[childAgeSelEl.selectedIndex].getAttribute('data-computed-cost'));
                            if (isNaN(childIntervalCost)) {
                                return;
                            }
                            // add child age interval cost to solution total amount
                            totalAmount += childIntervalCost;
                        });
                    } else {
                        // regular option
                        let checkboxEl = optRecordEl.querySelector('input[type="checkbox"]');
                        let numberEl = optRecordEl.querySelector('input[type="number"]');
                        let priceEl = optRecordEl.querySelector('.vbo-editbooking-room-service-price');
                        let computedCost = 0;
                        if (checkboxEl && checkboxEl.checked) {
                            // single-quantity option checked
                            computedCost = Number(priceEl.getAttribute('data-computed-cost'));
                        } else if (numberEl && numberEl.value && parseInt(numberEl.value) > 0) {
                            // multiple quantity option fulfilled
                            let quantity = parseInt(numberEl.value);
                            computedCost = Number(priceEl.getAttribute('data-computed-cost'));
                            if (computedCost && !isNaN(computedCost)) {
                                computedCost = computedCost * quantity;
                            }
                        }
                        if (computedCost && !isNaN(computedCost)) {
                            // add option cost to solution total amount
                            totalAmount += computedCost;
                        }
                    }
                });

                // scan all room extra services
                quoteOptRoomEl.querySelectorAll('.vbo-quote-option-room-new-extra-wrap').forEach((extraEl) => {
                    let extraName = extraEl.querySelector('input[data-field="extra-name"]')?.value;
                    let extraCost = Number(extraEl.querySelector('input[data-field="extra-cost"]')?.value);
                    if (!extraName || !extraCost || isNaN(extraCost)) {
                        // ignore when extra service name or cost are missing or invalid
                        return;
                    }
                    // add extra service cost to solution total amount
                    totalAmount += extraCost;
                });
            });

            // update quote total amount
            quoteOptionEl.querySelector('.vbo-quote-option-total-cost').innerHTML = VBOCore.getCurrency().format(totalAmount);
        };

        /**
         * Simulate click event on add-new-quote-option button to add the first option.
         * Eventually populate solutions with given inquiry data.
         */
        if (!quoteEditMode) {
            // add the first quote solution when creating a new one
            document.querySelector('.vbo-quote-option-add-btn').click();

            if (inquiryData?.checkin && inquiryData?.checkout) {
                // populate values from inquiry data onto the first quote solution
                let quoteSolution = document.querySelector('.vbo-quote-new-option-wrap[data-active="1"]');
                if (quoteSolution) {
                    // set stay dates
                    quoteSolution.querySelector('input[data-field="checkin"]').value = inquiryData.checkin;
                    quoteSolution.querySelector('input[data-field="checkout"]').value = inquiryData.checkout;
                    // calculate and set nights
                    let totNights = calcNights(inquiryData.checkin, inquiryData.checkout);
                    quoteSolution.querySelector('[data-counter="nights"]').textContent = totNights || '0';

                    if (Array.isArray(inquiryData?.parties) && inquiryData.parties.length) {
                        // select the add-room button for the current solution
                        let addRoomBtn = quoteSolution.querySelector('.vbo-quote-option-room-add-btn');
                        // iterate all room parties
                        inquiryData.parties.forEach((party, partyIndex) => {
                            // add a new room to the current solution
                            addRoomBtn.click();
                            // select the newly added room-booking element
                            let roomBookingEls = quoteSolution.querySelectorAll('.vbo-quote-option-new-room-wrap');
                            if (roomBookingEls[partyIndex]) {
                                // attempt to set the number of guests
                                if (party?.adults) {
                                    roomBookingEls[partyIndex].querySelector('input[data-field="adults"]').value = party.adults;
                                }
                                if (party?.children) {
                                    roomBookingEls[partyIndex].querySelector('input[data-field="children"]').value = party.children;
                                }
                                if (Array.isArray(inquiryData?.rooms)) {
                                    let setRoomId = null;
                                    if (inquiryData.rooms[partyIndex]?.id) {
                                        // set exactly preferred room ID
                                        setRoomId = inquiryData.rooms[partyIndex]?.id;
                                    } else if (inquiryData.rooms[0]?.id) {
                                        // set first preferred room ID
                                        setRoomId = inquiryData.rooms[0]?.id;
                                    }
                                    if (setRoomId) {
                                        // set room selected
                                        let roomBookingRoomEl = roomBookingEls[partyIndex].querySelector('select[data-field="listing"]');
                                        roomBookingRoomEl.value = setRoomId;
                                        roomBookingRoomEl.dispatchEvent(new Event('change'));
                                    }
                                }
                            }
                        });
                    }
                }
            }
        }

        /**
         * Simulate blur event on phone input field in case it's got a value to format for update.
         */
        setTimeout(() => {
            document.querySelector('input[data-field="customer-phone"]')?.dispatchEvent(new Event('blur'));
        }, 200);

    });
</script>

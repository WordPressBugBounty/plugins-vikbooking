<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2023 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "virtual terminal".
 * 
 * @since 	1.16.4 (J) - 1.6.4 (WP)
 */
class VikBookingAdminWidgetVirtualTerminal extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget. Since we do not load individual parameters
	 * for each widget's instance, we use a static counter to determine its settings.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * Tells whether VCM is installed and updated.
	 * 
	 * @var 	bool
	 */
	protected $vcm_exists = false;

	/**
	 * The payment processor record loaded.
	 * 
	 * @var 	array
	 */
	protected $payment_method = [];

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_VIRTUALTERMINAL_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_VIRTUALTERMINAL_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('credit-card') . '"></i>';
		$this->widgetStyleName = 'red';

		// whether VCM is available
		if (is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			$this->vcm_exists = true;
		}
	}

	/**
	 * Custom method for this widget only to load the virtual terminal CC form.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 */
	public function loadTerminalForm()
	{
		$dbo = JFactory::getDbo();

		$wrapper = VikRequest::getString('wrapper', '', 'request');
		$bid = VikRequest::getString('bid', '', 'request');

		if (!$bid) {
			// booking ID is mandatory
			VBOHttpDocument::getInstance()->close(500, JText::translate('VBPEDITBUSYONE'));
		}

		// get booking details
		$booking_info = VikBooking::getBookingInfoFromID($bid);

		if (!$booking_info) {
			// booking record not found, try to see if an OTA reservation ID was given
			$dbo->setQuery(
				$dbo->getQuery(true)
					->select($dbo->qn('id'))
					->from($dbo->qn('#__vikbooking_orders'))
					->where($dbo->qn('idorderota') . ' = ' . $dbo->q($bid))
					->where($dbo->qn('channel') . ' IS NOT NULL'),
				0, 1
			);

			$ota_id = $dbo->loadResult();

			if ($ota_id) {
				// overwrite booking ID value
				$bid = $ota_id;

				// get booking details
				$booking_info = VikBooking::getBookingInfoFromID($bid);
			}

			if (!$booking_info) {
				// booking record could not be found
				VBOHttpDocument::getInstance()->close(404, JText::translate('VBPEDITBUSYONE'));
			}
		}

		// check if direct charge is supported by the current payment processor
		if (!$this->getPaymentProcessor($booking_info)) {
			// unable to proceed due to unsupported transaction
			return [
				'html' => '<p class="err">' . JText::translate('VBO_PAY_PROCESS_NO_DIRECT_CHARGE') . '</p>',
			];
		}

		// build complete credit card payload, if any
		$cc_payload_str = '';
		$booking_info['paymentlog'] = (string)$booking_info['paymentlog'];
		if (stripos($booking_info['paymentlog'], 'card number') !== false && strpos($booking_info['paymentlog'], '*') !== false) {
			// matched a log for an OTA CC
			$cc_payload_str = $booking_info['paymentlog'];
		} elseif (preg_match("/(([\d\*]{4,4}\s*){4,4})|(([\d\*]{4,6}\s*){3,3})/", $booking_info['paymentlog'])) {
			// matched a credit card
			$cc_payload_str = $booking_info['paymentlog'];
		}

		// check if this is an OTA reservation with remotely decoded CC details
		$remote_cc_data = [];
		if ($this->vcm_exists && !empty($booking_info['idorderota']) && !empty($booking_info['channel'])) {
			// make sure the permissions are met
			if (!JFactory::getUser()->authorise('core.admin', 'com_vikchannelmanager')) {
				// insufficient permissions to handle CC details
				return [
					'html' => '<p class="err">' . JText::translate('JERROR_ALERTNOAUTHOR') . '</p>',
				];
			}

			// channel source
			$channel_source = (string)$booking_info['channel'];
			if (strpos($booking_info['channel'], '_') !== false) {
				$channelparts = explode('_', $booking_info['channel']);
				$channel_source = $channelparts[0];
			}

			// only updated versions of VCM will support remote CC decoding for OTA reservations
			if (class_exists('VCMOtaBooking')) {
				// invoke the OTA Booking helper class from VCM
				$cc_helper = VCMOtaBooking::getInstance([
					'channel_source' => $channel_source,
					'ota_id' 		 => $booking_info['idorderota'],
				], $anew = true);

				if (method_exists($cc_helper, 'decodeCreditCardDetails')) {
					$remote_cc_data = $cc_helper->decodeCreditCardDetails();
					// make sure the response was valid
					if (!$remote_cc_data || !empty($remote_cc_data['error'])) {
						// we ignore the error by simply resetting the array
						$remote_cc_data = [];
					}
				}
			}
		}

		// merge remotely decoded CC details to parsed payment log (if any)
		$cc_value_pairs = array_merge($remote_cc_data, $this->parseCreditCardValuePairs($cc_payload_str, $remote_cc_data));

		// currency code
		$currency_code = !empty($booking_info['chcurrency']) ? $booking_info['chcurrency'] : VikBooking::getCurrencyName();
		if (empty($currency_code) || strlen((string)$currency_code) != 3) {
			// fallback to currency transaction code
			$currency_code = VikBooking::getCurrencyCodePp();
		}

		// check known CC values to build the hidden transaction values
		$known_tn_vals  = [];
		$hidden_tn_vals = [];
		if (isset($cc_value_pairs['name'])) {
			$known_tn_vals['name'] = $cc_value_pairs['name'];
		}
		if (isset($cc_value_pairs['card_number'])) {
			$known_tn_vals['card_number'] = $cc_value_pairs['card_number'];
		}
		if (isset($cc_value_pairs['expiration_date'])) {
			$known_tn_vals['expiration_date'] = $cc_value_pairs['expiration_date'];
		}
		if (isset($cc_value_pairs['cvv'])) {
			$known_tn_vals['cvv'] = $cc_value_pairs['cvv'];
		}
		foreach ($cc_value_pairs as $key => $value) {
			if (!isset($known_tn_vals[$key])) {
				$hidden_tn_vals[$key] = $value;
			}
		}

		/**
		 * Consider calculating an outstanding balance in case of previous payments.
		 * 
		 * @since 	1.16.7 (J) - 1.6.7 (WP)
		 */
		$default_total = $booking_info['total'];
		if ($booking_info['totpaid'] > 0 && $booking_info['totpaid'] < $booking_info['total']) {
			// default to the outstanding balance
			$default_total = $booking_info['total'] - $booking_info['totpaid'];
		}

		// start output buffering
		ob_start();

		?>

		<div class="vbo-vterminal-cc-container">

			<div class="vbo-vterminal-cc-row-group vbo-vterminal-cc-row-group-amount">
				<div class="vbo-vterminal-cc-row vbo-vterminal-cc-row-currency">
					<div class="vbo-vterminal-cc-lbl"><?php echo JText::translate('VBOCPARAMCURRENCY'); ?></div>
					<div class="vbo-vterminal-cc-val">
						<input type="text" autocomplete="off" value="<?php echo JHtml::fetch('esc_attr', $currency_code); ?>" data-vt-cc-field="currency" />
					</div>
				</div>
				<div class="vbo-vterminal-cc-row vbo-vterminal-cc-row-amount">
					<div class="vbo-vterminal-cc-lbl"><?php echo JText::translate('VBO_CC_AMOUNT'); ?></div>
					<div class="vbo-vterminal-cc-val">
						<input type="number" value="<?php echo $default_total; ?>" min="0" step="any" data-vt-cc-field="amount" />
					</div>
				</div>
			</div>

			<div class="vbo-vterminal-cc-row-group vbo-vterminal-cc-row-group-cardholder">
				<div class="vbo-vterminal-cc-row vbo-vterminal-cc-row-cardholder">
					<div class="vbo-vterminal-cc-lbl"><?php echo JText::translate('VBISNOMINATIVE'); ?></div>
					<div class="vbo-vterminal-cc-val">
						<input type="text" autocomplete="off" value="<?php echo isset($known_tn_vals['name']) ? JHtml::fetch('esc_attr', $cc_value_pairs['name']) : ''; ?>" data-vt-cc-field="cardholder" />
					</div>
				</div>
			</div>

			<div class="vbo-vterminal-cc-row-group vbo-vterminal-cc-row-group-ccpan">
				<div class="vbo-vterminal-cc-row vbo-vterminal-cc-row-ccpan">
					<div class="vbo-vterminal-cc-lbl"><?php echo JText::translate('VBO_CC_NUMBER'); ?></div>
					<div class="vbo-vterminal-cc-val vbo-vterminal-cc-val-withlogo">
						<input type="text" autocomplete="off" value="<?php echo isset($known_tn_vals['card_number']) ? JHtml::fetch('esc_attr', $cc_value_pairs['card_number']) : ''; ?>" data-vt-cc-field="card_number" />
						<span class="vbo-vterminal-cc-type-logo"></span>
					</div>
				</div>
			</div>

			<div class="vbo-vterminal-cc-row-group vbo-vterminal-cc-row-group-ccextra">
				<div class="vbo-vterminal-cc-row vbo-vterminal-cc-row-ccexpiry">
					<div class="vbo-vterminal-cc-lbl"><?php echo JText::translate('VBO_CC_EXPIRY_DT'); ?></div>
					<div class="vbo-vterminal-cc-val">
						<input type="text" autocomplete="off" placeholder="MM/YYYY" value="<?php echo isset($known_tn_vals['expiration_date']) ? JHtml::fetch('esc_attr', $cc_value_pairs['expiration_date']) : ''; ?>" data-vt-cc-field="expiry" />
					</div>
				</div>
				<div class="vbo-vterminal-cc-row vbo-vterminal-cc-row-cvc">
					<div class="vbo-vterminal-cc-lbl"><?php echo JText::translate('VBO_CC_CVV'); ?></div>
					<div class="vbo-vterminal-cc-val">
						<input type="text" autocomplete="off" value="<?php echo isset($known_tn_vals['cvv']) ? JHtml::fetch('esc_attr', $cc_value_pairs['cvv']) : ''; ?>" data-vt-cc-field="cvv" />
					</div>
				</div>
			</div>

			<div class="vbo-vterminal-cc-row-group vbo-vterminal-cc-row-group-submit">
				<div class="vbo-vterminal-cc-row vbo-vterminal-cc-row-submit">
					<div class="vbo-vterminal-cc-val">
						<button type="button" class="btn vbo-config-btn" onclick="vboWidgetVTerminalChargeCard('<?php echo $wrapper; ?>');"><?php VikBookingIcons::e('credit-card'); ?> <?php echo JText::translate('VBO_CC_DOCHARGE'); ?></button>
					</div>
				</div>
			</div>

		<?php
		// print hidden transaction values, if any
		foreach ($hidden_tn_vals as $key => $value) {
			?>
			<input type="hidden" value="<?php echo JHtml::fetch('esc_attr', $value); ?>" data-vt-cc-field="<?php echo JHtml::fetch('esc_attr', $key); ?>" />
			<?php
		}
		?>

		</div>

		<script type="text/javascript">

			// store the last card type detected
			var vbo_vt_last_cc_type = '';

			// subscribe to the keyup event for the card number field
			var vbo_vt_cc_num_f = document.querySelector('#<?php echo $wrapper; ?>').querySelector('[data-vt-cc-field="card_number"]');
			vbo_vt_cc_num_f.addEventListener('keyup', (e) => {
				if (!e || !e.target) {
					return;
				}

				// the current CC number value
				var cc_value = e.target.value;

				// invoke the helper class to handle the card number
				const card = new VBOCreditCard(cc_value);

				// detect card type and format card number
				var card_type = card.getCardType();
				var cc_text   = card.formatCreditCard(card_type);

				// set formatted CC number value
				e.target.value = cc_text;

				// handle CC logo
				if (vbo_vt_last_cc_type != card_type) {
					var card_logo_uri = card.getCardLogoURI(card_type);
					var card_logo_pnode = e.target.parentNode;
					var card_logo_wrap = card_logo_pnode.querySelector('.vbo-vterminal-cc-type-logo');
					if (!card_type) {
						// hide CC logo
						card_logo_wrap.innerHTML = '';
						card_logo_pnode.classList.remove('vbo-vterminal-cc-type-logo-detected');
						card_logo_pnode.classList.add('vbo-vterminal-cc-type-logo-unknown');
					} else if (card_logo_uri) {
						// set CC logo
						card_logo_wrap.innerHTML = '<img src="' + card_logo_uri + '" />';
						card_logo_pnode.classList.remove('vbo-vterminal-cc-type-logo-unknown');
						card_logo_pnode.classList.add('vbo-vterminal-cc-type-logo-detected');
					}

					// overwrite value
					vbo_vt_last_cc_type = card_type;
				}
			});

			// subscribe to the keydown and keyup events for the card expiration date field
			var vbo_vt_cc_exp_f = document.querySelector('#<?php echo $wrapper; ?>').querySelector('[data-vt-cc-field="expiry"]');
			vbo_vt_cc_exp_f.addEventListener('keydown', (e) => {
				if (!e || !e.key) {
					return;
				}

				if (!isNaN(e.key) || e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) {
					return true;
				}

				switch (e.key) {
					case "ArrowLeft":
					case "ArrowRight":
					case "Enter":
					case "Escape":
					case "Delete":
					case "Backspace":
					case "Tab":
					case "/":
						return true;
					default:
						event.preventDefault();
						return false;
				}
			});

			vbo_vt_cc_exp_f.addEventListener('keyup', (e) => {
				if (!e || !e.target) {
					return;
				}

				var date = e.target.value;

				if (!date) {
					return;
				}

				if (date.length === 2 && date.indexOf('/') < 0) {
					e.target.value += '/';
					return;
				}

				if (date.length > 7) {
					e.target.value = e.target.value.substr(0, 7);
					return;
				}
			});

			// subscribe to the blur event to make sure the year is full
			vbo_vt_cc_exp_f.addEventListener('blur', (e) => {
				if (!e || !e.target) {
					return;
				}

				var date = e.target.value;

				if (!date || date.length >= 7 || date.indexOf('/') < 0) {
					return;
				}

				var parts = date.split('/');

				if (parts[1].length === 2) {
					var today = new Date;
					var year = (today.getFullYear() + '').substr(0, 2);

					e.target.value = parts[0] + '/' + year + parts[1];
				}

				return;
			});

			// default state for CC number and expiry input fields
			setTimeout(() => {
				if (vbo_vt_cc_num_f.value) {
					// trigger keyup event to format the current CC and display the logo
					vbo_vt_cc_num_f.dispatchEvent(new Event('keyup'));
				}
				if (vbo_vt_cc_exp_f.value) {
					// trigger blur event to format the current CC expiration date
					vbo_vt_cc_exp_f.dispatchEvent(new Event('blur'));
				}
			}, 128);

		</script>

		<?php

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return array(
			'html' => $html_content,
		);
	}

	/**
	 * Custom method for this widget only to debit a credit card.
	 * 
	 * @return 	array|void
	 */
	public function doDirectCharge()
	{
		$wrapper = VikRequest::getString('wrapper', '', 'request');
		$bid 	 = VikRequest::getInt('bid', 0, 'request');
		$card 	 = VikRequest::getVar('card', [], 'request');

		if (!$bid || !$card) {
			// booking ID and CC details are mandatory
			VBOHttpDocument::getInstance()->close(500, JText::translate('VBPEDITBUSYONE'));
		}

		// get booking details
		$booking_info = VikBooking::getBookingInfoFromID($bid);

		if (!$booking_info) {
			// booking record not found
			VBOHttpDocument::getInstance()->close(404, JText::translate('VBPEDITBUSYONE'));
		}

		// get the eligible payment processor by passing the card details
		$processor = $this->getPaymentProcessor($booking_info, $card);

		if (!$processor) {
			VBOHttpDocument::getInstance()->close(500, JText::translate('VBO_PAY_PROCESS_NO_DIRECT_CHARGE'));
		}

		// default transaction response
		$array_result = [
			'verified' => 0,
		];

		try {
			// perform the transaction
			$array_result = $processor->directCharge();
		} catch (Exception $e) {
			// set error message
			$array_result['log'] = sprintf(JText::translate('VBO_CC_TN_ERROR') . " \n%s", $e->getMessage());
		}

		if ($array_result['verified'] != 1) {
			// erroneous response
			if (!empty($array_result['log']) && is_string($array_result['log'])) {
				VBOHttpDocument::getInstance()->close(500, $array_result['log']);
			} else {
				VBOHttpDocument::getInstance()->close(500, 'Operation failed');
			}
		}

		// valid transaction response!

		// update booking details
		$dbo = JFactory::getDbo();

		// get the amount paid
		$tn_amount = isset($array_result['tot_paid']) ? (float)$array_result['tot_paid'] : null;

		// get the log string, if any
		$tn_log = !empty($array_result['log']) ? $array_result['log'] : '';

		// update record
		$upd_record = new stdClass;
		$upd_record->id = $booking_info['id'];
		if ($tn_amount) {
			// update amount paid
			$upd_record->totpaid = $booking_info['totpaid'] + $tn_amount;
			// update payable amount (if needed)
			$new_payable = $booking_info['payable'] - $tn_amount;
			$new_payable = $new_payable < 0 ? 0 : $new_payable;
			$upd_record->payable = $new_payable;
		}
		if ($tn_log) {
			$upd_record->paymentlog = $booking_info['paymentlog'] . "\n\n" . date('c') . "\n" . $tn_log;
		}
		$upd_record->paymcount = ((int)$booking_info['paymcount'] + 1);

		// update reservation record
		$dbo->updateObject('#__vikbooking_orders', $upd_record, 'id');

		// payment processor name
		$pay_process_name = $this->payment_method ? $this->payment_method['name'] : 'CC Direct Charge';

		// handle transaction data to eventually support a later transaction of type refund
		$tn_data = isset($array_result['transaction']) ? $array_result['transaction'] : null;
		if ($tn_amount) {
			// check event data payload to store
			if (is_array($tn_data)) {
				// set key
				$tn_data['amount_paid'] = $tn_amount;
			} elseif (is_object($tn_data)) {
				// set property
				$tn_data->amount_paid = $tn_amount;
			} elseif (!$tn_data) {
				// build an array (we add the payment name because we know there is no other transaction data)
				$tn_data = [
					'amount_paid' 	 => $tn_amount,
					'payment_method' => $pay_process_name,
				];
			}
		}

		/**
		 * Check if the payment processor returned the information about the amount of processing fees.
		 * 
		 * @since 	1.16.9 (J) - 1.6.9 (WP)
		 */
		if ($tn_data && isset($array_result['tot_fees']) && $array_result['tot_fees']) {
			// check event data payload to store
			if (is_array($tn_data)) {
				// set key
				$tn_data['processing_fees'] = (float)$array_result['tot_fees'];
			} elseif (is_object($tn_data)) {
				// set property
				$tn_data->processing_fees = (float)$array_result['tot_fees'];
			}
		}

		// current admin-user
		$now_user = JFactory::getUser();

		// Booking History
		$ev_descr = JText::translate('VBO_W_VIRTUALTERMINAL_TITLE') . " - {$pay_process_name} ({$now_user->name})";
		VikBooking::getBookingHistoryInstance()->setBid($booking_info['id'])->setExtraData($tn_data)->store('P' . ($booking_info['paymcount'] > 0 ? 'N' : '0'), $ev_descr);

		return [
			'success' => 1,
			'log' 	  => $tn_log,
		];
	}

	/**
	 * Preload the necessary assets.
	 * 
	 * @return 	void
	 */
	public function preload()
	{
		// JS lang def
		JText::script('VBOUPLOADFILEDONE');
	}

	/**
	 * Main method to invoke the widget. Contents will be loaded
	 * through AJAX requests, not via PHP when the page loads.
	 * 
	 * @param 	VBOMultitaskData 	$data
	 * 
	 * @return 	void
	 */
	public function render(VBOMultitaskData $data = null)
	{
		// increase widget's instance counter
		static::$instance_counter++;

		// check whether the widget is being rendered via AJAX when adding it through the customizer
		$is_ajax = $this->isAjaxRendering();

		// generate a unique ID for the sticky notes wrapper instance
		$wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
		$wrapper_id = 'vbo-widget-vterminal-' . $wrapper_instance;

		// get permissions
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		if (!$vbo_auth_bookings) {
			// display nothing
			return;
		}

		// check multitask data
		$page_bid 	 = 0;
		$js_modal_id = '';
		if ($data) {
			$is_modal_rendering = $data->isModalRendering();
			$page_bid = $data->getBookingID() ?: $this->options()->fetchBookingId();
			if ($is_modal_rendering) {
				// get modal JS identifier
				$js_modal_id = $data->getModalJsIdentifier();
			}
		}

		if (!$page_bid) {
			// unable to continue from a page outside the booking details or booking ID set
			?>
		<p class="warn"><?php echo JText::translate('VBPEDITBUSYONE'); ?></p>
			<?php
			return;
		}

		?>
		<div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" data-instance="<?php echo $wrapper_instance; ?>" data-pagebid="<?php echo $page_bid; ?>" data-modalid="<?php echo $js_modal_id; ?>">
			<div class="vbo-admin-widget-head">
				<div class="vbo-admin-widget-head-inline">
					<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
				</div>
			</div>
			<div class="vbo-widget-vterminal-wrap">
				<div class="vbo-widget-vterminal-inner">

					<div class="vbo-widget-vterminal-form"></div>

				</div>
			</div>
		</div>
		<?php

		if (static::$instance_counter === 0 || $is_ajax) {
			/**
			 * Print the JS code only once for all instances of this widget.
			 * The real rendering is made through AJAX, not when the page loads.
			 */
			?>

		<script type="text/javascript">

			/**
			 * Default icons for status.
			 */
			var vbo_vt_icon_error   = '<?php VikBookingIcons::e('exclamation-circle'); ?>';
			var vbo_vt_icon_success = '<?php VikBookingIcons::e('check-circle'); ?>';

			/**
			 * Perform the request to load the virtual terminal form.
			 */
			function vboWidgetVTerminalFormLoad(wrapper, page_bid) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// check if multitask data passed a booking ID for the current page
				var force_bid = 0;
				if (typeof page_bid !== 'undefined' && page_bid) {
					force_bid = page_bid;
				}

				// the widget method to call
				var call_method = 'loadTerminalForm';

				// make a request to load the bookings calendar
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						bid: force_bid,
						wrapper: wrapper,
						tmpl: "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// replace HTML content
							widget_instance.find('.vbo-widget-vterminal-form').html(obj_res[call_method]['html']);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// remove the skeleton loading
						widget_instance.find('.vbo-widget-vterminal-form').find('.vbo-skeleton-loading').remove();
						// display error message
						alert(error.responseText);
					}
				);
			}

			/**
			 * Performs the request to charge the given card details.
			 */
			function vboWidgetVTerminalChargeCard(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// the trigger button
				var charge_cmd = widget_instance.find('.vbo-vterminal-cc-row-submit').find('button');

				// disable button
				charge_cmd.prop('disabled', true);

				if (VBOCore.options.default_loading_body) {
					// show loading spinner
					charge_cmd.find('i').replaceWith(VBOCore.options.default_loading_body);
				}

				// gather all CC fields
				var cc_fields = {};
				widget_instance.find('.vbo-widget-vterminal-form').find('[data-vt-cc-field]').each(function() {
					var cc_f_key = jQuery(this).attr('data-vt-cc-field');
					cc_fields[cc_f_key] = jQuery(this).val();
				});

				// get the booking ID for the transaction
				var force_bid = widget_instance.attr('data-pagebid');

				// the widget method to call
				var call_method = 'doDirectCharge';

				// make a request to load the bookings calendar
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						bid: force_bid,
						card: cc_fields,
						wrapper: wrapper,
						tmpl: "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// turn flag on
							vbo_widget_vt_last_tn = 1;

							// update button status
							charge_cmd.removeClass('vbo-config-btn').addClass('btn-success').html(vbo_vt_icon_success + ' ' + Joomla.JText._('VBOUPLOADFILEDONE'));

							// check if we need to dismiss the modal widget
							var js_modal_id = widget_instance.attr('data-modalid');
							if (js_modal_id) {
								setTimeout(() => {
									// dismiss modal widget
									VBOCore.emitEvent('vbo-dismiss-widget-modal' + js_modal_id);
								}, 1500);
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// display error message
						alert(error.responseText);
						// restore button
						charge_cmd.prop('disabled', false);
						charge_cmd.find('i').replaceWith(vbo_vt_icon_error);
					}
				);
			}

			/**
			 * Generate the HTML skeleton loading string to build the form.
			 */
			function vboWidgetVTerminalFormSkeleton(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var def_loading = '';
				def_loading += '<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">';
				def_loading += '	<div class="vbo-dashboard-guest-activity-avatar">';
				def_loading += '		<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>';
				def_loading += '	</div>';
				def_loading += '	<div class="vbo-dashboard-guest-activity-content">';
				def_loading += '		<div class="vbo-dashboard-guest-activity-content-head">';
				def_loading += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>';
				def_loading += '		</div>';
				def_loading += '		<div class="vbo-dashboard-guest-activity-content-subhead">';
				def_loading += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-subtitle"></div>';
				def_loading += '		</div>';
				def_loading += '		<div class="vbo-dashboard-guest-activity-content-info-msg">';
				def_loading += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>';
				def_loading += '		</div>';
				def_loading += '	</div>';
				def_loading += '</div>';

				widget_instance.find('.vbo-widget-vterminal-form').html(def_loading);
			}

			/**
			 * Triggers when the multitask panel opens.
			 */
			function vboWidgetVTerminalMultitaskOpen(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// check if a booking ID was set for this page
				var page_bid = widget_instance.attr('data-pagebid');
				if (!page_bid || page_bid < 1) {
					return false;
				}

				// show loading skeletons
				vboWidgetVTerminalFormSkeleton(wrapper);

				// load data by injecting the current booking ID
				vboWidgetVTerminalFormLoad(wrapper, page_bid);
			}

			/**
			 * Credit Card Detector Class.
			 */
			function VBOCreditCard(card) {
				this.set(card);
			}

			VBOCreditCard.prototype.set = function(card) {
				this.cards_logo_uri_base = '<?php echo VBO_ADMIN_URI . 'resources/js_upload/images/'; ?>';
				this.card = [];
				for (var i = 0; i < card.length; i++) {
					var ch = card.charCodeAt(i);
					if (ch >= 48 && ch <= 57) {
						this.card.push(ch-48);
					}
				}
			}

			VBOCreditCard.prototype.get = function() {
				return this.card;
			}

			VBOCreditCard.prototype.isEnoughSpace = function(len) {
				return ( this.card.length >= len );
			}

			VBOCreditCard.prototype.isEmpty = function() {
				return !this.isEnoughSpace(1);
			}

			VBOCreditCard.prototype.isValid = function() {
				const type = this.getCardType();

				if (type.length && this.card.length == VBOCreditCard.properties[type].size) {
					return true;
				}

				return false;
			}

			VBOCreditCard.prototype.getNumberToIndex = function(i) {
				var n = 0;
				var factor = 1;
				for (i = i-1 ; i >= 0; i--) {
					n += factor * this.card[i];
					factor *= 10;
				}
				return n;
			}

			VBOCreditCard.prototype.isVisa = function() {
				return this.matchBrandRanges([
						[4]
					]);
			}

			VBOCreditCard.prototype.isMasterCard = function() {
				return this.matchBrandRanges([
						[51, 55],
						[2221, 2720]
					]);
			}

			VBOCreditCard.prototype.isAmericanExpress = function() {
				return this.matchBrandRanges([
						[34],
						[37]
					]);
			}

			VBOCreditCard.prototype.isDiners = function() {
				return this.matchBrandRanges([
						[300, 305],
						[36],
						[38, 39]
					]);
			}

			VBOCreditCard.prototype.isDiscover = function() {
				return this.matchBrandRanges([
						[6011],
						[65],
						[622126, 622925],
						[644, 649]
					]);
			}

			VBOCreditCard.prototype.isJCB = function() {
				return this.matchBrandRanges([
						[3528, 3589]
					]);
			}

			VBOCreditCard.prototype.getCardType = function() {
				if (this.isVisa()) {
					return VBOCreditCard.VISA;
				} else if (this.isMasterCard()) {
					return VBOCreditCard.MASTERCARD;
				} else if (this.isAmericanExpress()) {
					return VBOCreditCard.AMERICAN_EXPRESS;
				} else if (this.isDiners()) {
					return VBOCreditCard.DINERS;
				} else if (this.isDiscover()) {
					return VBOCreditCard.DISCOVER;
				} else if (this.isJCB()) {
					return VBOCreditCard.JCB;
				}

				return '';
			}

			VBOCreditCard.prototype.getCardLogoURI = function(type) {
				if (!type) {
					return '';
				}

				return this.cards_logo_uri_base + type + '.png';
			}

			VBOCreditCard.prototype.matchBrandRanges = function(ranges) {

				for (var i = 0; i < ranges.length; i++) {
					var r = ranges[i];

					if (r.length == 1) {

						if (this.isEnoughSpace((''+r[0]).length) && this.getNumberToIndex((''+r[0]).length) == r[0]) {
							return true;
						} 

					} else if (r.length == 2) {

						var len = Math.max( (''+r[0]).length, (''+r[1]).length );

						if (this.isEnoughSpace(len)) {

							var val = this.getNumberToIndex(len);

							if (r[0] <= val && val <= r[1]) {
								return true;
							}

						}

					}

				}

				return false;
			}

			VBOCreditCard.prototype.formatCreditCard = function(card_type) {
				if (card_type === undefined) {
					card_type = this.getCardType();
				}

				var blank_spaces = [];
				if (card_type.length  > 0) {
					blank_spaces = VBOCreditCard.properties[card_type]['blank'];
				}

				var cc_str = '';
				for (var i = 0; i < this.card.length; i++) {
					cc_str += this.card[i];
					if (blank_spaces.indexOf(i+1) != -1) {
						cc_str += ' ';
					}
				}

				return cc_str;
			}

			VBOCreditCard.properties = {
				'visa': {
					'size': 16,
					'blank': [4, 8, 12]
				},
				'mastercard': {
					'size': 16,
					'blank': [4, 8, 12]
				},
				'amex': {
					'size': 15,
					'blank': [4, 10]
				},
				'discover': {
					'size': 16,
					'blank': [4, 8, 12]
				},
				'diners': {
					'size': 14,
					'blank': [4, 8, 12]
				},
				'jcb': {
					'size': 16,
					'blank': [4, 8, 12]
				},
			};

			VBOCreditCard.VISA = 'visa';
			VBOCreditCard.MASTERCARD = 'mastercard';
			VBOCreditCard.AMERICAN_EXPRESS = 'amex';
			VBOCreditCard.DINERS = 'diners';
			VBOCreditCard.DISCOVER = 'discover';
			VBOCreditCard.JCB = 'jcb';

		</script>
			<?php
		}
		?>

		<script type="text/javascript">

			// store the last processed transaction
			var vbo_widget_vt_last_tn = null;

			jQuery(function() {

				// show loading skeletons
				vboWidgetVTerminalFormSkeleton('<?php echo $wrapper_id; ?>');

				// when document is ready, load bookings calendar for this widget's instance
				vboWidgetVTerminalFormLoad('<?php echo $wrapper_id; ?>', '<?php echo $page_bid; ?>');

				// subscribe to the multitask-panel-open event
				document.addEventListener(VBOCore.multitask_open_event, function() {
					vboWidgetVTerminalMultitaskOpen('<?php echo $wrapper_id; ?>');
				});

				// subscribe to the multitask-panel-close event to emit the event for the lastly created booking ID
				document.addEventListener(VBOCore.multitask_close_event, function() {
					if (vbo_widget_vt_last_tn) {
						// emit the event with data for anyone who is listening to it
						VBOCore.emitEvent('vbo_new_payment_transaction', {
							tn: vbo_widget_vt_last_tn
						});
					}
				});

			<?php
			if ($js_modal_id) {
				// widget can be dismissed through the modal
				?>
				// subscribe to the modal-dismissed event to emit the event for the lastly created booking ID
				document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $js_modal_id; ?>', function() {
					if (vbo_widget_vt_last_tn) {
						// emit the event with data for anyone who is listening to it
						VBOCore.emitEvent('vbo_new_payment_transaction', {
							tn: vbo_widget_vt_last_tn
						});
					}
				});
				<?php
			}
			?>

			});

		</script>

		<?php
	}

	/**
	 * Attempts to invoke the eligible payment processor assigned to the given reservation.
	 * 
	 * @param 	array 	$booking 	the reservation record as an associative array.
	 * @param 	array 	$card 		the card details collected through the Virtual Terminal.
	 * 
	 * @return 	object|null 		the payment processor dispatcher instance or null.
	 */
	protected function getPaymentProcessor(array $booking, array $card = [])
	{
		$processor = null;
		$payment   = [];

		if (!empty($booking['idpayment'])) {
			$payment = VikBooking::getPayment($booking['idpayment']);
		}

		if ($card) {
			// inject CC details for the payment processor
			$booking['card'] = $card;
		}

		if ($payment && VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly 	The payment gateway is loaded 
			 * 			through the apposite dispatcher.
			 */
			JLoader::import('adapter.payment.dispatcher');
			$processor = JPaymentDispatcher::getInstance('vikbooking', $payment['file'], $booking, $payment['params']);
		} elseif ($payment && VBOPlatformDetection::isJoomla()) {
			/**
			 * @joomlaonly 	The Payment Factory library will invoke the gateway.
			 */
			require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'factory.php';
			$processor = VBOPaymentFactory::getPaymentInstance($payment['file'], $booking, $payment['params']);
		}

		if ($processor && method_exists($processor, 'isDirectChargeSupported') && $processor->isDirectChargeSupported()) {
			// set payment method flag loaded
			$this->payment_method = $payment;

			// return the valid payment processor instance
			return $processor;
		}

		return null;
	}

	/**
	 * Given a raw string of credit card key-value pairs from payments log,
	 * parse the corresponding keys and values into an associative array.
	 * In case of conflicting keys with the remotely decoded CC details,
	 * attempts to replace the masked numbers with asterisks.
	 * 
	 * @param 	string 	$cc_payload 		the raw CC details from payment logs.
	 * @param 	array 	$remote_cc_data 	associative array of decoded CC data.
	 * 
	 * @return 	array 						associative or empty array.
	 */
	protected function parseCreditCardValuePairs($cc_payload, array $remote_cc_data = [])
	{
		$cc_value_pairs = [];

		if (empty($cc_payload)) {
			return $cc_value_pairs;
		}

		$cc_lines = preg_split("/(\r\n|\n|\r)/", $cc_payload);

		foreach ($cc_lines as $cc_line) {
			if (strpos($cc_line, ':') === false) {
				continue;
			}

			$cc_line_parts = explode(':', $cc_line);

			if (empty($cc_line_parts[0]) || !strlen(trim($cc_line_parts[1]))) {
				continue;
			}

			$key   = str_replace(' ', '_', strtolower($cc_line_parts[0]));
			$value = trim($cc_line_parts[1]);

			if (isset($cc_value_pairs[$key])) {
				/**
				 * Do not overwrite existing keys because this probably means that the
				 * credit card was updated by an OTA like Booking.com, hence the payment
				 * logs string in VBO may contain the information of two different cards.
				 * New credit card details are always pre-pended by VCM in the payment logs.
				 * 
				 * @since 	1.16.5 (J) - 1.6.5 (WP)
				 */
				continue;
			}

			if (!empty($remote_cc_data[$key]) && is_string($remote_cc_data[$key]) && strpos($value, '*') !== false) {
				// replace masked numbers with remote content
				$value = $this->replaceMaskedNumbers($value, $remote_cc_data[$key]);
			}

			$cc_value_pairs[$key] = $value;
		}

		return $cc_value_pairs;
	}

	/**
	 * Given a local and a remote credit card number string with
	 * masked symbols, replaces the values in the corresponding
	 * positions with the unmasked numbers.
	 * 
	 * @param 	string 	$local 		current string with masked values.
	 * @param 	string 	$remote 	remote string with unmasked values.
	 * 
	 * @return 	string 				the local string with unmasked values.
	 */
	protected function replaceMaskedNumbers($local, $remote)
	{
		// split anything but numbers
		$numbers = preg_split("/([^0-9]+)/", trim($remote));

		if ($numbers) {
			// filter empty values
			$numbers = array_filter($numbers);
		}

		if (!$numbers) {
			// unable to proceed
			return $local;
		}

		// split anything but stars (asterisks)
		$stars = preg_split("/([^\*]+)/", trim($local));

		if ($stars) {
			// filter empty values
			$stars = array_filter($stars);
		}

		if (!$stars) {
			// unable to proceed
			return $local;
		}

		// replace masked symbols with numbers at their first occurrence
		foreach ($numbers as $k => $unmasked) {
			if (!isset($stars[$k])) {
				continue;
			}

			$masked_pos = strpos($local, $stars[$k]);

			if ($masked_pos === false) {
				continue;
			}

			$local = substr_replace($local, $unmasked, $masked_pos, strlen($stars[$k]));
		}

		// return the string with possibly unmasked values
		return $local;
	}
}

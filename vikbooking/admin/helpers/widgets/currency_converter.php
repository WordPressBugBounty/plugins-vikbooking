<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2022 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "currency converter".
 * 
 * @since 	1.15.4 (J) - 1.5.10 (WP)
 */
class VikBookingAdminWidgetCurrencyConverter extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_CURRCONV_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_CURRCONV_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('funnel-dollar') . '"></i>';
		$this->widgetStyleName = 'violet';

		// load widget's settings
		$this->widgetSettings = $this->loadSettings();
		if (!is_object($this->widgetSettings)) {
			$this->widgetSettings = new stdClass;
		}
	}

	/**
	 * Custom method for this widget only to exchange the rates between currencies.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * It's the actual rendering of the widget.
	 */
	public function exchangeRates()
	{
		$wrapper = VikRequest::getString('wrapper', '', 'request');

		$from_currency = VikRequest::getString('from_currency', '', 'request');
		$to_currency = VikRequest::getString('to_currency', '', 'request');
		$amount = VikRequest::getFloat('amount', 1, 'request');

		if (empty($from_currency) || empty($to_currency)) {
			// use the website default currency
			$from_currency = VikBooking::getCurrencyName();

			// use the default to currency
			$to_currency = !strcasecmp($from_currency, 'EUR') ? 'USD' : 'EUR';
		}

		if ($amount <= 0) {
			$amount = 1;
		}

		// import currency converter class
		VikBooking::import('currencyconverter');

		// get numbering format data
		$format = VikBooking::getNumberFormatData();

		// get the instance of the converter object
		$converter = new VboCurrencyConverter($from_currency, $to_currency, [$amount], explode(':', $format));

		// collect values
		$converter_error = null;
		$exchanged = null;
		$exchange_rate = null;
		
		// make sure from currency is supported for conversion
		if (!$converter->currencyExists($from_currency)) {
			$converter_error = sprintf('Currency %s is not supported', $from_currency);
		} elseif (!$converter->currencyExists($to_currency)) {
			$converter_error = sprintf('Currency %s is not supported', $to_currency);
		}

		if (!$converter_error) {
			// exchange the given rates
			$exchanged = $converter->convert($get_floats = true);
		}

		if (!$converter_error && (!is_array($exchanged) || !count($exchanged))) {
			// something went wrong during the conversion
			$converter_error = $converter->getError();
			$converter_error = empty($converter_error) ? 'Error while converting rates' : $converter_error;
		} else {
			// get the conversion rate
			$exchange_rate = $converter->getConversionRate();

			// update widget's settings with the lastly used currencies
			$this->widgetSettings->from_currency = $from_currency;
			$this->widgetSettings->to_currency 	 = $to_currency;
			$this->updateSettings(json_encode($this->widgetSettings));
		}

		// start output buffering
		ob_start();

		?>
		<div class="vbo-widget-currconv-result-data">
		<?php
		if ($converter_error || empty($exchanged)) {
			?>
			<p class="err"><?php echo $converter_error; ?></p>
			<?php
		} else {
			?>
			<p class="info"><?php echo JText::sprintf('VBO_CONV_RES_OTA_CURRENCY_EXC', $from_currency . ' ' . $amount, $to_currency . ' ' . $exchanged[0]); ?></p>
			<p class="info"><?php echo JText::translate('VBO_CONV_RATE'); ?> <span class="badge badge-primary"><?php echo $exchange_rate; ?></span></p>
			<?php
		}
		?>
		</div>
		<?php

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return [
			'html' 		  	=> $html_content,
			'exchanged'   	=> $exchanged,
			'exchange_rate' => $exchange_rate,
		];
	}

	/**
	 * Preload the necessary CSS/JS assets.
	 * 
	 * @return 	void
	 */
	public function preload()
	{
		// load assets
		$this->vbo_app->loadSelect2();
	}

	public function render(VBOMultitaskData $data = null)
	{
		// increase widget's instance counter
		static::$instance_counter++;

		// check whether the widget is being rendered via AJAX when adding it through the customizer
		$is_ajax = $this->isAjaxRendering();

		// generate a unique ID for the sticky notes wrapper instance
		$wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
		$wrapper_id = 'vbo-widget-currconv-' . $wrapper_instance;

		// import currency converter class
		if (!VikBooking::import('currencyconverter')) {
			// display nothing
			return;
		}

		// the website default currency
		$def_currency = !empty($this->widgetSettings->from_currency) ? $this->widgetSettings->from_currency : VikBooking::getCurrencyName();

		// the default to currency
		$def_to_currency = !strcasecmp($def_currency, 'EUR') ? 'USD' : 'EUR';
		if (!empty($this->widgetSettings->to_currency)) {
			$def_to_currency = $this->widgetSettings->to_currency;
		}

		// get numbering format data
		$format = VikBooking::getNumberFormatData();

		// get the instance of the converter object
		$converter = new VboCurrencyConverter($def_currency, $def_to_currency, [1], explode(':', $format));

		// grab all currency names
		$all_currencies = $converter->getCurrencyNames();

		?>
		<div class="vbo-admin-widget-wrapper">
			<div class="vbo-admin-widget-head">
				<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
			</div>
			<div id="<?php echo $wrapper_id; ?>" class="vbo-widget-currconv-wrap" data-instance="<?php echo $wrapper_instance; ?>">
				<div class="vbo-widget-currconv-filters">
					<div class="vbo-widget-currconv-filters-main">
						<div class="vbo-widget-currconv-filter vbo-widget-currconv-filter-from">
							<label for="vbo-widget-currconv-from-<?php echo $wrapper_instance; ?>"><?php echo JText::translate('VBO_CONV_FROM'); ?></label>
							<select id="vbo-widget-currconv-from-<?php echo $wrapper_instance; ?>" class="vbo-widget-currconv-sel vbo-widget-currconv-from">
							<?php
							foreach ($all_currencies as $code => $name) {
								?>
								<option value="<?php echo $code; ?>"<?php echo !strcasecmp($def_currency, $code) ? ' selected="selected"' : ''; ?>><?php echo $name; ?></option>
								<?php
							}
							?>
							</select>
						</div>
						<div class="vbo-widget-currconv-filter vbo-widget-currconv-filter-amount">
							<label for="vbo-widget-currconv-amount-<?php echo $wrapper_instance; ?>"><?php echo JText::translate('VBPSHOWSEASONSFOUR'); ?></label>
							<input type="number" id="vbo-widget-currconv-amount-<?php echo $wrapper_instance; ?>" class="vbo-widget-currconv-amount" min="0.01" step="any" value="1" />
						</div>
					</div>
					<div class="vbo-widget-currconv-filters-secondary">
						<div class="vbo-widget-currconv-filter vbo-widget-currconv-filter-to">
							<label for="vbo-widget-currconv-to-<?php echo $wrapper_instance; ?>"><?php echo JText::translate('VBO_CONV_TO'); ?></label>
							<select id="vbo-widget-currconv-to-<?php echo $wrapper_instance; ?>" class="vbo-widget-currconv-sel vbo-widget-currconv-to">
							<?php
							foreach ($all_currencies as $code => $name) {
								?>
								<option value="<?php echo $code; ?>"<?php echo !strcasecmp($def_to_currency, $code) ? ' selected="selected"' : ''; ?>><?php echo $name; ?></option>
								<?php
							}
							?>
							</select>
						</div>
					</div>
					<div class="vbo-widget-currconv-filters-submit">
						<div class="vbo-widget-currconv-filter vbo-widget-currconv-filter-submit">
							<button type="button" class="btn vbo-config-btn" onclick="vboWidgetCurrConvCalc('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('exchange-alt'); ?><?php echo JText::translate('VBRATESOVWRATESCALCULATORCALC'); ?></button>
						</div>
					</div>
				</div>
				<div class="vbo-widget-currconv-result"></div>
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
			 * Perform the request to exchange rates.
			 */
			function vboWidgetCurrConvExchange(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// get vars for making the request
				var from_currency = widget_instance.find('.vbo-widget-currconv-from').val();
				var to_currency   = widget_instance.find('.vbo-widget-currconv-to').val();
				var amount 		  = widget_instance.find('.vbo-widget-currconv-amount').val();

				// the widget method to call
				var call_method = 'exchangeRates';

				// make a request to load the available room rates
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						from_currency: from_currency,
						to_currency: to_currency,
						amount: amount,
						tmpl: "component"
					},
					function(response) {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method) || !obj_res[call_method]) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// replace HTML with exchange rates
							widget_instance.find('.vbo-widget-currconv-result').html(obj_res[call_method]['html']);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					function(error) {
						// empty result container
						widget_instance.find('.vbo-widget-currconv-result').html('');
						console.error(error);
					}
				);
			}

			/**
			 * Calculate the exchange rates.
			 */
			function vboWidgetCurrConvCalc(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show loading spinner
				widget_instance.find('.vbo-widget-currconv-result').html('<p style="text-align: center;"><?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?></p>');

				// load data
				vboWidgetCurrConvExchange(wrapper);
			}

		</script>
			<?php
		}
		?>

		<script type="text/javascript">

			jQuery(function() {

				// render select2
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-currconv-sel').select2({
					width: "200px",
				});

			});
			
		</script>

		<?php
	}
}

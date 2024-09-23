<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "finance".
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VikBookingAdminWidgetFinance extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget. Since we do not load individual parameters
	 * for each widget's instance, we use a static counter to determine its settings.
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

		$this->widgetName = JText::translate('VBO_W_FINANCE_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_FINANCE_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('piggy-bank') . '"></i>';
		$this->widgetStyleName = 'red';
	}

	/**
	 * Preload the necessary CSS/JS assets.
	 * 
	 * @return 	void
	 */
	public function preload()
	{
		// load assets for datepicker
		$this->vbo_app->loadDatePicker();

		// load assets for contextual menu
		$this->vbo_app->loadContextMenuAssets();

		// check for daily welcome message with stats
		$this->setupWelcomeStats();

		// lang vars for JS
		JText::script('VBO_COMPARE_WITH_LAST_Y');
		JText::script('VBO_COMPARE_WITH_PREV_Q');
		JText::script('VBO_COMPARE_WITH_PREV_M');
		JText::script('VBO_COMPARE_WITH_PREV_W');
		JText::script('VBO_COMPARE_WITH_PREV_D');
		JText::script('VBNOTRACKINGS');
	}

	/**
	 * Custom method for this widget only to load the financial statistics.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * It's the actual rendering of the widget which also allows navigation.
	 */
	public function loadFinancialStats()
	{
		$wrapper = VikRequest::getString('wrapper', '', 'request');

		$fromdate = VikRequest::getString('fromdate', '', 'request');
		$todate   = VikRequest::getString('todate', '', 'request');
		$step 	  = VikRequest::getString('step', 'quarter', 'request');
		$date_dir = VikRequest::getInt('date_dir', 0, 'request');
		if (empty($fromdate)) {
			// default to current quarter (3 full months)
			$now_info = getdate();
			$end_info = getdate(mktime(0, 0, 0, ($now_info['mon'] + 2), 1, $now_info['year']));
			$to_ts    = mktime(23, 59, 59, $end_info['mon'], date('t', $end_info[0]), $end_info['year']);
			$fromdate = date('Y-m-d', mktime(0, 0, 0, $now_info['mon'], 1, $now_info['year']));
			$todate   = date('Y-m-d', $to_ts);
		}

		// convert dates to timestamps to support custom date formats
		$from_ts = VikBooking::getDateTimestamp($fromdate);
		$to_ts 	 = VikBooking::getDateTimestamp($todate, 23, 59, 59);

		// check dates navigation and step
		$step_name = '';
		if ($date_dir > 0 || $date_dir < 0) {
			// calculate forward or backward dates for navigation
			list($from_ts, $to_ts, $step_name) = $this->calcDatesNavigation($from_ts, $to_ts, $step, $date_dir);
		}

		// convert final dates to Y-m-d
		$fromdate = date('Y-m-d', $from_ts);
		$todate   = date('Y-m-d', $to_ts);

		// stats calculation type
		$calc_type = !strcasecmp($step, 'booking_dates') ? 'booking_dates' : 'stay_dates';

		// access the finance helper object
		$finance = VBOTaxonomyFinance::getInstance();

		// currency symbol
		$currencysymb = VikBooking::getCurrencySymb();

		// get the financial stats for the requested dates
		try {
			$stats = $finance->getStats($fromdate, $todate, [], $calc_type);
		} catch (Exception $e) {
			// make the AJAX request fail nicely
			VBOHttpDocument::getInstance()->close($e->getCode(), $e->getMessage());
		}

		// language string identifier for step-comparison
		$js_step_comp_str = 'VBO_COMPARE_WITH_PREV_Q';
		if ($step == 'month') {
			$js_step_comp_str = 'VBO_COMPARE_WITH_PREV_M';
		} elseif ($step == 'week') {
			$js_step_comp_str = 'VBO_COMPARE_WITH_PREV_W';
		} elseif (!strcasecmp($step, 'booking_dates')) {
			$js_step_comp_str = 'VBO_COMPARE_WITH_PREV_D';
		}

		// start output buffering
		ob_start();

		?>
		<div class="vbo-widget-finance-data-blocks">

			<div class="vbo-widget-finance-data-block" data-typestat="gross_revenue">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBO_GROSS_BOOKING_VALUE'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['gross_revenue']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['gross_revenue']); ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-widget-finance-data-block" data-typestat="taxes">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBOINVCOLTAX'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['taxes']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['taxes']); ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-widget-finance-data-block" data-typestat="cmms">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBTOTALCOMMISSIONS'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['cmms']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['cmms']); ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-widget-finance-data-block" data-typestat="revenue">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBOREPORTREVENUE'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['revenue']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['revenue']); ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-widget-finance-data-block" data-typestat="ibe_revenue">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBOREPORTREVENUEREVWEB'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['ibe_revenue']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['ibe_revenue']); ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-widget-finance-data-block" data-typestat="ota_revenue">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBOREPORTREVENUEREVOTA'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['ota_revenue']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['ota_revenue']); ?></span>
						</span>
					</div>
				</div>
			</div>

		<?php
		if ($stats['cmm_savings'] > 0) {
			?>
			<div class="vbo-widget-finance-data-block" data-typestat="ota_avg_cmms">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBO_AVG_COMMISSIONS'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value">
							<span><?php echo $stats['ota_avg_cmms']; ?>%</span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-widget-finance-data-block" data-typestat="cmm_savings">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBO_COMMISSION_SAVINGS'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['cmm_savings']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['cmm_savings']); ?></span>
						</span>
					</div>
				</div>
			</div>
			<?php
		}
		?>

			<div class="vbo-widget-finance-data-block" data-typestat="tot_bookings">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBCUSTOMERTOTBOOKINGS'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value">
							<span><?php echo $stats['tot_bookings']; ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-widget-finance-data-block" data-typestat="nights_booked">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBOGRAPHTOTNIGHTSLBL'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo htmlspecialchars(trim(str_replace('%', '', JText::translate('VBOREPORTREVENUEPOCC'))) . ' ' . $stats['occupancy'] . '%'); ?>">
							<span><?php echo $stats['nights_booked'] . '/' . $stats['tot_inventory']; ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-widget-finance-data-block" data-typestat="avg_los">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBTRKAVGLOS'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value">
							<span><?php echo $stats['avg_los']; ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-widget-finance-data-block" data-typestat="rooms_booked">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBLIBTEN'); ?></span>
						<span class="vbo-widget-finance-stat-cmd"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-amount">
						<span class="vbo-widget-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo htmlspecialchars(JText::translate('VBOGRAPHTOTUNITSLBL') . ' ' . $stats['room_units']); ?>">
							<span><?php echo $stats['rooms_booked']; ?></span>
						</span>
					</div>
				</div>
			</div>

		</div>

		<div class="vbo-widget-finance-data-block-rankings">

			<div class="vbo-widget-finance-data-block-rank" data-typestat="country_ranks">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBOSTATSTOPCOUNTRIES'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-ranks">
					<?php
					foreach ($stats['country_ranks'] as $country_rank) {
						?>
						<div class="vbo-widget-finance-stat-rank">
							<div class="vbo-widget-finance-stat-rank-logo">
							<?php
							if (is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'countries' . DIRECTORY_SEPARATOR . $country_rank['code'] . '.png')) {
								?>
								<img src="<?php echo VBO_ADMIN_URI . 'resources/countries/' . $country_rank['code'] . '.png'; ?>" />
								<?php
							} else {
								VikBookingIcons::e('globe');
							}
							?>
							</div>
							<div class="vbo-widget-finance-stat-rank-score">
								<div class="vbo-widget-finance-stat-rank-name">
									<span><?php echo $country_rank['name']; ?></span>
								</div>
								<div class="vbo-widget-finance-stat-rank-amount">
									<span class="vbo-currency"><?php echo $currencysymb; ?></span>
									<span class="vbo-price vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($country_rank['revenue']); ?>"><?php echo $finance->numberFormatShort($country_rank['revenue']); ?></span>
								</div>
								<div class="vbo-widget-finance-stat-rank-pcent">
									<progress class="vbo-widget-finance-progress" value="<?php echo $country_rank['pcent']; ?>" max="100"><?php echo $country_rank['pcent']; ?>%</progress>
								</div>
							</div>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</div>

			<div class="vbo-widget-finance-data-block-rank" data-typestat="pos_revenue">
				<div class="vbo-widget-finance-stat">
					<div class="vbo-widget-finance-stat-info">
						<span class="vbo-widget-finance-stat-name"><?php echo JText::translate('VBOCHANNELS'); ?></span>
					</div>
					<div class="vbo-widget-finance-stat-ranks">
					<?php
					foreach ($stats['pos_revenue'] as $pos_revenue) {
						$say_pos_name = ucfirst($pos_revenue['name']);
						?>
						<div class="vbo-widget-finance-stat-rank">
							<div class="vbo-widget-finance-stat-rank-logo">
							<?php
							if (!empty($pos_revenue['logo'])) {
								?>
								<img src="<?php echo $pos_revenue['logo']; ?>" />
								<?php
								// adjust channel name
								$say_pos_name = strtolower($pos_revenue['name']) == 'airbnbapi' ? 'Airbnb' : $say_pos_name;
								$say_pos_name = strtolower($pos_revenue['name']) == 'googlehotel' ? 'Google Hotel' : $say_pos_name;
							} elseif (!strcasecmp($pos_revenue['name'], 'website')) {
								VikBookingIcons::e('hotel');
								// adjust channel name
								$say_pos_name = JText::translate('VBORDFROMSITE');
							} else {
								VikBookingIcons::e('globe');
							}
							?>
							</div>
							<div class="vbo-widget-finance-stat-rank-score">
								<div class="vbo-widget-finance-stat-rank-name">
									<span><?php echo $say_pos_name; ?></span>
								</div>
								<div class="vbo-widget-finance-stat-rank-amount">
									<span class="vbo-currency"><?php echo $currencysymb; ?></span>
									<span class="vbo-price vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($pos_revenue['revenue']); ?>"><?php echo $finance->numberFormatShort($pos_revenue['revenue']); ?></span>
								</div>
								<div class="vbo-widget-finance-stat-rank-pcent">
									<progress class="vbo-widget-finance-progress" value="<?php echo $pos_revenue['pcent']; ?>" max="100"><?php echo $pos_revenue['pcent']; ?>%</progress>
								</div>
							</div>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</div>

		</div>

		<input type="hidden" class="vbo-widget-finance-json-stats" value="" />
		<input type="hidden" class="vbo-widget-finance-stats-from" value="" />
		<input type="hidden" class="vbo-widget-finance-stats-to" value="" />

		<script type="text/javascript">
			jQuery(function() {

				let vbo_wfinance_compare_auto = jQuery('#<?php echo $wrapper; ?>').find('.vbo-finance-compare-auto').val();

				let vbo_wfinance_ctx_btns = [
					{
						icon: '<?php echo VikBookingIcons::i('balance-scale-left'); ?>',
						text: Joomla.JText._('<?php echo $js_step_comp_str; ?>'),
						separator: false,
						action: (root, config) => {
							vboWidgetFinanceCompare('<?php echo $wrapper; ?>', '<?php echo $step; ?>');
						},
					},
					{
						icon: '<?php echo VikBookingIcons::i('balance-scale-right'); ?>',
						text: Joomla.JText._('VBO_COMPARE_WITH_LAST_Y'),
						separator: false,
						action: (root, config) => {
							vboWidgetFinanceCompare('<?php echo $wrapper; ?>', 'year');
						},
					},
				];

				if (vbo_wfinance_compare_auto.length) {
					vbo_wfinance_ctx_btns[1].separator = true;
					vbo_wfinance_ctx_btns.push({
						icon: '<?php echo VikBookingIcons::i('ban'); ?>',
						text: Joomla.JText._((vbo_wfinance_compare_auto == 'year' ? 'VBO_COMPARE_WITH_LAST_Y' : '<?php echo $js_step_comp_str; ?>')),
						class: 'vbo-context-menu-entry-danger',
						separator: false,
						action: (root, config) => {
							// turn off the flag to automatically compare data
							jQuery('#<?php echo $wrapper; ?>').find('.vbo-finance-compare-auto').val('');
							// reload data
							vboWidgetFinanceLoad('<?php echo $wrapper; ?>');
						},
					});
				}

				jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-finance-stat-cmd').vboContextMenu({
					placement: 'bottom-right',
					buttons: vbo_wfinance_ctx_btns,
				});
			});
		</script>

		<?php

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// take care of the readable dates interval
		list($format_from_date, $format_to_date) = $this->parseReadableDateInterval($from_ts, $to_ts, $this->df, $step);

		// return an associative array of values
		return [
			'html' 		  => $html_content,
			'fromdate' 	  => $fromdate,
			'f_fromdate'  => $format_from_date,
			'todate' 	  => $todate,
			'f_todate' 	  => $format_to_date,
			'step' 		  => $step,
			'step_name'   => $step_name,
			'stats' 	  => $stats,
		];
	}

	/**
	 * Custom method for this widget only to load the comparison values.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * Returns the necessary HTML to display comparison values with past dates.
	 */
	public function loadComparisonStats()
	{
		$wrapper = VikRequest::getString('wrapper', '', 'request');

		$fromdate 	= VikRequest::getString('fromdate', '', 'request');
		$todate   	= VikRequest::getString('todate', '', 'request');
		$step 	  	= VikRequest::getString('step', 'quarter', 'request');
		$prev_stats = VikRequest::getVar('stats', array());
		$date_dir 	= -1;

		if (empty($fromdate) || empty($todate) || empty($prev_stats)) {
			// make the AJAX request fail nicely
			VBOHttpDocument::getInstance()->close(500, 'Missing data to calculate comparison values');
		}

		// convert dates to timestamps to support custom date formats
		$from_ts = VikBooking::getDateTimestamp($fromdate);
		$to_ts 	 = VikBooking::getDateTimestamp($todate, 23, 59, 59);

		// stats calculation type
		$calc_type = 'stay_dates';
		if (!strcasecmp($step, 'booking_dates')) {
			$calc_type = 'booking_dates';
		}

		// calculate backward dates for comparison
		list($from_ts, $to_ts, $step_name) = $this->calcDatesNavigation($from_ts, $to_ts, $step, $date_dir);

		// convert final dates to Y-m-d
		$fromdate = date('Y-m-d', $from_ts);
		$todate   = date('Y-m-d', $to_ts);

		// access the finance helper object
		$finance = VBOTaxonomyFinance::getInstance();

		// currency symbol
		$currencysymb = VikBooking::getCurrencySymb();

		// get the financial stats for the requested dates and use them for comparison
		try {
			$compare_stats = $finance->getStats($fromdate, $todate, [], $calc_type);
		} catch (Exception $e) {
			// make the AJAX request fail nicely
			VBOHttpDocument::getInstance()->close($e->getCode(), $e->getMessage());
		}

		// define the "compare vs" string
		switch ($step) {
			case 'year':
				$compare_vs_str = JText::translate('VBO_VS_LAST_Y');
				break;
			case 'quarter':
				$compare_vs_str = JText::translate('VBO_VS_PREV_Q');
				break;
			case 'month':
				$compare_vs_str = JText::translate('VBO_VS_PREV_M');
				break;
			case 'week':
				$compare_vs_str = JText::translate('VBO_VS_PREV_W');
				break;
			case 'booking_dates':
				$compare_vs_str = JText::translate('VBO_VS_PREV_D');
				break;
			default:
				$compare_vs_str = '';
				break;
		}

		// build comparison values
		$comparison = [];

		// stats that require percent values for comparison
		$pcent_stats = [
			'gross_revenue' => [],
			'taxes' 		=> [],
			'cmms' 			=> [
				'reverse'  => 1,
			],
			'revenue' 		=> [],
			'ibe_revenue' 	=> [],
			'ota_revenue' 	=> [],
			'ota_avg_cmms' 	=> [
				'reverse'  => 1,
				'no_pcent' => 1,
				'fixednum' => 1,
			],
			'cmm_savings' 	=> [],
			'tot_bookings' 	=> [
				'fixednum' => 1,
			],
			'nights_booked' => [
				'fixednum' => 1,
			],
			'avg_los' 		=> [
				'fixednum' => 1,
			],
			'rooms_booked' 	=> [
				'fixednum' => 1,
			],
		];

		foreach ($pcent_stats as $stat_name => $stat_opt) {
			if (!isset($compare_stats[$stat_name])) {
				continue;
			}

			// make sure the previous value is set
			$prev_stats[$stat_name] = !isset($prev_stats[$stat_name]) ? 0 : $prev_stats[$stat_name];

			// difference
			$diff = isset($stat_opt['reverse']) ? ($compare_stats[$stat_name] - $prev_stats[$stat_name]) : ($prev_stats[$stat_name] - $compare_stats[$stat_name]);

			// calculate values
			$comparison[$stat_name] = [
				'amount'   => $compare_stats[$stat_name],
				'amount_f' => VikBooking::numberFormat($compare_stats[$stat_name]),
				'amount_s' => $finance->numberFormatShort($compare_stats[$stat_name]),
				'diff' 	   => $diff,
				'diff_f'   => VikBooking::numberFormat($diff),
				'diff_s'   => $finance->numberFormatShort($diff),
				'pcent'    => isset($stat_opt['no_pcent']) ? null : $finance->calcAbsPercent($prev_stats[$stat_name], $compare_stats[$stat_name]),
			];

			// inject property for fixed number with no currency
			if (isset($stat_opt['fixednum']) && $stat_opt['fixednum']) {
				$comparison[$stat_name]['fixednum'] = $stat_opt['fixednum'];
			}
		}

		// return an associative array of values
		return [
			'no_data'  => ($compare_stats['tot_bookings'] < 1 ? 1 : 0),
			'compare'  => $comparison,
			'vs_str'   => $compare_vs_str,
			'currency' => $currencysymb,
			'stats'    => $compare_stats,
		];
	}

	/**
	 * Custom method for this widget only to retrieve the daily
	 * welcome statistics about the bookings of the day before.
	 * 
	 * @return 	array 	the associative list of statistics.
	 */
	public function getWelcomeStats()
	{
		// get yesterday's date
		$yesterday = date('Y-m-d', strtotime('yesterday'));

		// access the finance helper object
		$finance = VBOTaxonomyFinance::getInstance();

		// get the financial stats for the requested dates
		try {
			$stats = $finance->getStats($yesterday, $yesterday, $rooms = [], $type = 'booking_dates');
		} catch (Exception $e) {
			// make the AJAX request fail nicely
			VBOHttpDocument::getInstance()->close($e->getCode(), $e->getMessage());
		}

		return $stats;
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
		$wrapper_id = 'vbo-widget-finance-' . $wrapper_instance;

		// check permissions
		$vbo_auth_pricing = JFactory::getUser()->authorise('core.vbo.pricing', 'com_vikbooking');
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		if (!$vbo_auth_pricing || !$vbo_auth_bookings) {
			// base permissions are not met
			return;
		}

		// we make use of the rates flow and occupancy ranking report helper classes
		$report = VikBooking::getReportInstance('rates_flow');
		if (!$report) {
			// display nothing
			return;
		}

		// get minimum and maximum nights updated for dates filters
		list($mindate, $maxdate) = $this->getMinDatesFinance();
		$mindate = empty($mindate) ? time() : $mindate;
		$maxdate = empty($maxdate) ? $mindate : $maxdate;

		// dates navigation preference
		$cookie = JFactory::getApplication()->input->cookie;
		$cookie_step = $cookie->get('vbo_widget_finance_step', 'quarter', 'string');

		// determine default period name and dates
		$df = $report->getDateFormat();
		$dtpicker_df = $report->getDateFormat('jui');
		$now_info = getdate();
		if ($cookie_step == 'week') {
			// next week
			$from_ts = mktime(0, 0, 0, $now_info['mon'], $now_info['mday'], $now_info['year']);
			$to_ts   = mktime(0, 0, 0, $now_info['mon'], ($now_info['mday'] + 7), $now_info['year']);
			$fromdate = date($df, $from_ts);
			$todate   = date($df, $to_ts);
			$period_name = JText::translate('VBOWEEK');
		} elseif ($cookie_step == 'month') {
			// current full month
			$from_ts  = mktime(0, 0, 0, $now_info['mon'], 1, $now_info['year']);
			$fromdate = date($df, $from_ts);
			$to_ts 	  = mktime(23, 59, 59, $now_info['mon'], date('t', $now_info[0]), $now_info['year']);
			$todate   = date($df, $to_ts);
			$period_name = JText::translate('VBPVIEWRESTRICTIONSTWO');
		} else {
			// default to next 3 (total) months from today's month
			$from_ts  = mktime(0, 0, 0, $now_info['mon'], 1, $now_info['year']);
			$fromdate = date($df, $from_ts);
			$end_info = getdate(mktime(0, 0, 0, ($now_info['mon'] + 2), 1, $now_info['year']));
			$to_ts    = mktime(23, 59, 59, $end_info['mon'], date('t', $end_info[0]), $end_info['year']);
			$todate   = date($df, $to_ts);
			$period_name = JText::translate('VBO_QUARTER');
		}

		// check multitask data
		if ($data) {
			$inj_fromdate = $data->get('fromdate', $this->options()->get('fromdate', ''));
			$inj_todate   = $data->get('todate', $this->options()->get('todate', ''));
			$inj_type 	  = $data->get('type', $this->options()->get('type', ''));

			if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $inj_fromdate) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $inj_todate)) {
				// valid dates injected in Y-m-d format
				$fromdate = $inj_fromdate;
				$todate   = $inj_todate;
				$to_info = getdate(strtotime($todate));
				$from_ts = strtotime($fromdate);
				$to_ts = mktime(23, 59, 59, $to_info['mon'], $to_info['mday'], $to_info['year']);
				if (!empty($inj_type)) {
					$cookie_step = $inj_type;
					if (!strcasecmp($inj_type, 'booking_dates')) {
						$period_name = JText::translate('VBOSTATSMODETS');
					}
				}
			}
		}

		// take care of the readable dates interval
		list($format_from_date, $format_to_date) = $this->parseReadableDateInterval($from_ts, $to_ts, $df, $cookie_step);
		$interval_name = $format_from_date . ' - ' . $format_to_date;
		if ($format_from_date == $format_to_date) {
			$interval_name = $format_from_date;
		}

		?>
		<div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" data-instance="<?php echo $wrapper_instance; ?>">
			<div class="vbo-admin-widget-head">
				<div class="vbo-admin-widget-head-inline">
					<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
					<div class="vbo-admin-widget-head-commands">

						<div class="vbo-reportwidget-commands">
							<div class="vbo-reportwidget-commands-main">
								<div class="vbo-reportwidget-command-dates">
									<div class="vbo-reportwidget-period-name"><?php echo $period_name; ?></div>
									<div class="vbo-reportwidget-period-date"><?php echo $interval_name; ?></div>
								</div>
								<div class="vbo-reportwidget-command-chevron vbo-reportwidget-command-prev">
									<span class="vbo-widget-finance-dt-prev" onclick="vboWidgetFinanceDatesNav('<?php echo $wrapper_id; ?>', -1);"><?php VikBookingIcons::e('chevron-left'); ?></span>
								</div>
								<div class="vbo-reportwidget-command-chevron vbo-reportwidget-command-next">
									<span class="vbo-widget-finance-dt-next" onclick="vboWidgetFinanceDatesNav('<?php echo $wrapper_id; ?>', 1);"><?php VikBookingIcons::e('chevron-right'); ?></span>
								</div>
							</div>
							<div class="vbo-reportwidget-command-dots">
								<span class="vbo-widget-command-togglefilters vbo-widget-finance-togglefilters" onclick="vboWidgetFinanceToggleFilters('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
							</div>
						</div>
						<div class="vbo-reportwidget-filters">
							<div class="vbo-reportwidget-filter">
								<span class="vbo-reportwidget-datepicker">
									<?php VikBookingIcons::e('calendar', 'vbo-widget-finance-caltrigger'); ?>
									<input type="text" class="vbo-finance-dtpicker-from" value="<?php echo $fromdate; ?>" />
								</span>
							</div>
							<div class="vbo-reportwidget-filter">
								<span class="vbo-reportwidget-datepicker">
									<?php VikBookingIcons::e('calendar', 'vbo-widget-finance-caltrigger'); ?>
									<input type="text" class="vbo-finance-dtpicker-to" value="<?php echo $todate; ?>" />
								</span>
							</div>
							<div class="vbo-reportwidget-filter">
								<select class="vbo-finance-period-nav" onchange="vboWidgetFinanceChangePeriod(this.value);">
									<option value="week"<?php echo $cookie_step == 'week' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOWEEK'); ?></option>
									<option value="month"<?php echo $cookie_step == 'month' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPVIEWRESTRICTIONSTWO'); ?></option>
									<option value="quarter"<?php echo $cookie_step == 'quarter' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_QUARTER'); ?></option>
									<option value="booking_dates"<?php echo !strcasecmp($cookie_step, 'booking_dates') ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPCHOOSEBUSYORDATE'); ?></option>
								</select>
							</div>
							<div class="vbo-reportwidget-filter vbo-reportwidget-filter-confirm">
								<input type="hidden" class="vbo-finance-compare-auto" value="" />
								<button type="button" class="btn vbo-config-btn" onclick="vboWidgetFinanceChangeDates('<?php echo $wrapper_id; ?>');"><?php echo JText::translate('VBADMINNOTESUPD'); ?></button>
							</div>
						</div>

					</div>
				</div>
			</div>
			<div class="vbo-widget-finance-wrap">
				<div class="vbo-widget-finance-inner">
					<div class="vbo-widget-finance-list">
						<div class="vbo-widget-finance-skeleton-blocks">
						<?php
						// display a few loading skeletons
						for ($i = 0; $i < 10; $i++) {
							?>
							<div class="vbo-widget-finance-skeleton-block">
								<div class="vbo-widget-finance-skeleton-top">
									<div class="vbo-skeleton-loading vbo-skeleton-loading-finance-top"></div>
								</div>
								<div class="vbo-widget-finance-skeleton-bottom">
									<div class="vbo-skeleton-loading vbo-skeleton-loading-finance-bottom"></div>
								</div>
							</div>
							<?php
						}
						?>
						</div>
					</div>
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
			 * Display the loading skeletons.
			 */
			function vboWidgetFinanceSkeletons(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}
				widget_instance.find('.vbo-widget-finance-list').html('');
				var skeleton = '';
				skeleton += '<div class="vbo-widget-finance-skeleton-blocks">' + "\n";
				for (var i = 0; i < 10; i++) {
					skeleton += '<div class="vbo-widget-finance-skeleton-block">';
					skeleton += '	<div class="vbo-widget-finance-skeleton-top">';
					skeleton += '		<div class="vbo-skeleton-loading vbo-skeleton-loading-finance-top"></div>';
					skeleton += '	</div>';
					skeleton += '	<div class="vbo-widget-finance-skeleton-bottom">';
					skeleton += '		<div class="vbo-skeleton-loading vbo-skeleton-loading-finance-bottom"></div>';
					skeleton += '	</div>';
					skeleton += '</div>' + "\n";
				}
				skeleton += '</div>' + "\n";
				// append skeletons
				jQuery(skeleton).appendTo(widget_instance.find('.vbo-widget-finance-list'));
			}

			/**
			 * Perform the request to load the financial stats.
			 */
			function vboWidgetFinanceLoad(wrapper, dates_direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// check if a navigation of dates was requested (0 = no dates nav)
				if (typeof dates_direction === 'undefined') {
					dates_direction = 0;
				}

				// get vars for making the request
				var from_date 		= widget_instance.find('.vbo-finance-dtpicker-from').val();
				var to_date 		= widget_instance.find('.vbo-finance-dtpicker-to').val();
				var dates_step 		= widget_instance.find('.vbo-finance-period-nav').val();
				var auto_compare 	= widget_instance.find('.vbo-finance-compare-auto').val();

				// the widget method to call
				var call_method = 'loadFinancialStats';

				// make a request to load the financial stats
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						fromdate: from_date,
						todate: to_date,
						step: dates_step,
						date_dir: dates_direction,
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

							// replace HTML with new stats
							widget_instance.find('.vbo-widget-finance-list').html(obj_res[call_method]['html']);

							// populate JSON data for comparison
							widget_instance.find('input.vbo-widget-finance-json-stats').val(JSON.stringify(obj_res[call_method]['stats']));
							widget_instance.find('input.vbo-widget-finance-stats-from').val(obj_res[call_method]['fromdate']);
							widget_instance.find('input.vbo-widget-finance-stats-to').val(obj_res[call_method]['todate']);

							if (dates_direction > 0 || dates_direction < 0) {
								// set new dates calculated - note: if maxDate or minDate is exceeded, the datepicker calendars won't update!
								try {
									// construct a solid date object without using the raw Y-m-d string as only argument
									var dfrom_parts = obj_res[call_method]['fromdate'].split('-');
									var dfrom_obj 	= new Date(parseInt(dfrom_parts[0]), (parseInt(dfrom_parts[1]) - 1), parseInt(dfrom_parts[2]), 0, 0, 0);
									widget_instance.find('.vbo-finance-dtpicker-from').datepicker('setDate', dfrom_obj);

									// restore the original minimum and maximum dates for the "to date" calendar to avoid issues setting a date
									widget_instance.find('.vbo-finance-dtpicker-to').datepicker('option', {
										minDate: "<?php echo date($df, $mindate); ?>",
										maxDate: "<?php echo date($df, $maxdate); ?>",
									});

									// do the same for the to date by using a precise date object instance
									var dto_parts 	= obj_res[call_method]['todate'].split('-');
									var dto_obj 	= new Date(parseInt(dto_parts[0]), (parseInt(dto_parts[1]) - 1), parseInt(dto_parts[2]), 0, 0, 0);
									widget_instance.find('.vbo-finance-dtpicker-to').datepicker('setDate', dto_obj);
								} catch(e) {
									// just log the error
									console.error(e);
								}
							}

							// update step name
							if (obj_res[call_method]['step_name'] && obj_res[call_method]['step_name'].length) {
								widget_instance.find('.vbo-reportwidget-period-name').text(obj_res[call_method]['step_name']);
							}

							// update readable dates interval
							var readable_period = obj_res[call_method]['f_fromdate'] + ' - ' + obj_res[call_method]['f_todate'];
							if (obj_res[call_method]['f_fromdate'] == obj_res[call_method]['f_todate']) {
								// a full month does not need an interval
								readable_period = obj_res[call_method]['f_fromdate'];
							}
							widget_instance.find('.vbo-reportwidget-period-date').text(readable_period);

							if (auto_compare.length) {
								// trigger the stats comparison
								setTimeout(() => {
									vboWidgetFinanceCompare(wrapper, (auto_compare == 'year' ? 'year' : dates_step));
								}, 400);
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// remove the skeleton loading
						widget_instance.find('.vbo-widget-finance-list').find('.vbo-widget-finance-skeleton-blocks').remove();
						console.error(error);
						alert(error.responseText);
					}
				);
			}

			/**
			 * Load stats comparison values.
			 */
			function vboWidgetFinanceCompare(wrapper, period) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// append loading elements
				widget_instance.find('.vbo-widget-finance-data-blocks').find('.vbo-widget-finance-stat').each(function(k, v) {
					if (jQuery(this).find('.vbo-widget-finance-stat-compare').length) {
						jQuery(this).find('.vbo-widget-finance-stat-compare').remove();
					}
					var comparison_el = jQuery('<div></div>').addClass('vbo-widget-finance-stat-compare');
					var loading_el = jQuery('<div></div>').addClass('vbo-widget-finance-compare-amount');
					loading_el.append('<i class="<?php echo VikBookingIcons::i('circle-notch', 'fa-spin fa-fw'); ?>"></i>');
					comparison_el.append(loading_el);
					jQuery(this).append(comparison_el);
				});

				// get vars for making the request
				var prev_stats = JSON.parse(widget_instance.find('.vbo-widget-finance-json-stats').val());
				var from_date  = widget_instance.find('.vbo-widget-finance-stats-from').val();
				var to_date    = widget_instance.find('.vbo-widget-finance-stats-to').val();

				// the widget method to call
				var call_method = 'loadComparisonStats';

				// make a request to load the comparison values
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						fromdate: from_date,
						todate: to_date,
						step: period,
						stats: prev_stats,
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

							// turn on the flag to automatically compare data
							widget_instance.find('.vbo-finance-compare-auto').val(period);

							if (obj_res[call_method]['no_data']) {
								// no data available for comparison
								widget_instance.find('.vbo-widget-finance-data-blocks').find('.vbo-widget-finance-stat').each(function(k, v) {
									if (jQuery(this).find('.vbo-widget-finance-stat-compare').length) {
										jQuery(this).find('.vbo-widget-finance-stat-compare').remove();
									}
									let comparison_el = jQuery('<div></div>').addClass('vbo-widget-finance-stat-compare');
									let amount_el = jQuery('<div></div>').addClass('vbo-widget-finance-compare-amount');
									comparison_el.append(amount_el);
									let pcent_el = jQuery('<div></div>').addClass('vbo-widget-finance-compare-pcent');
									pcent_el.append('<span></span>').addClass('vbo-widget-finance-compare-txt').text(Joomla.JText._('VBNOTRACKINGS'));
									comparison_el.append(pcent_el);
									jQuery(this).append(comparison_el);
								});

								// do not proceed
								return;
							}

							// parse all comparison values
							for (let stat_name in obj_res[call_method]['compare']) {
								if (!obj_res[call_method]['compare'].hasOwnProperty(stat_name)) {
									continue;
								}

								let stat_elem = widget_instance.find('.vbo-widget-finance-data-block[data-typestat="' + stat_name + '"]');
								if (!stat_elem || !stat_elem.length) {
									continue;
								}

								// clean up loading element
								if (stat_elem.find('.vbo-widget-finance-stat-compare').length) {
									stat_elem.find('.vbo-widget-finance-stat-compare').remove();
								}

								// check if currency is needed
								let use_currency = (!obj_res[call_method]['compare'][stat_name].hasOwnProperty('fixednum') || !obj_res[call_method]['compare'][stat_name]['fixednum']);

								// the new comparison element
								let comparison_el = jQuery('<div></div>').addClass('vbo-widget-finance-stat-compare');

								// amount block
								let amount_block = jQuery('<div></div>').addClass('vbo-widget-finance-compare-amount');
								let amount_value = jQuery('<span></span>').addClass('vbo-widget-finance-compare-amount-value');
								if (use_currency) {
									amount_value.addClass('vbo-tooltip vbo-tooltip-top').attr('data-tooltiptext', obj_res[call_method]['currency'] + ' ' + obj_res[call_method]['compare'][stat_name]['amount_f']);
									amount_value.append('<span class="vbo-currency">' + obj_res[call_method]['currency'] + '</span>');
								}
								if (obj_res[call_method]['compare'][stat_name]['pcent'] != null) {
									amount_value.append('<span class="vbo-price">' + obj_res[call_method]['compare'][stat_name]['amount_s'] + '</span>');
								} else {
									amount_value.append('<span class="vbo-price">' + obj_res[call_method]['compare'][stat_name]['amount'] + '%</span>');
								}
								amount_block.append(amount_value);

								// percent block
								let pcent_block = jQuery('<div></div>').addClass('vbo-widget-finance-compare-pcent');
								let pcent_icon  = '';
								if (obj_res[call_method]['compare'][stat_name]['diff'] == 0) {
									pcent_block.addClass('vbo-widget-finance-compare-pcent-equal');
									pcent_icon  = '<?php VikBookingIcons::e('equals') ?>';
								} else if (obj_res[call_method]['compare'][stat_name]['diff'] > 0) {
									pcent_block.addClass('vbo-widget-finance-compare-pcent-up');
									pcent_icon  = '<?php VikBookingIcons::e('arrow-up') ?>';
								} else if (obj_res[call_method]['compare'][stat_name]['diff'] < 0) {
									pcent_block.addClass('vbo-widget-finance-compare-pcent-down');
									pcent_icon  = '<?php VikBookingIcons::e('arrow-down') ?>';
								}
								if (obj_res[call_method]['compare'][stat_name]['pcent'] != null) {
									pcent_block.append('<span class="vbo-widget-finance-compare-val">' + pcent_icon + ' ' + obj_res[call_method]['compare'][stat_name]['pcent'] + '%</span>');
								}
								pcent_block.append('<span class="vbo-widget-finance-compare-txt">' + obj_res[call_method]['vs_str'] + '</span>');
								
								// append amount block
								comparison_el.append(amount_block);
								// append percent block
								comparison_el.append(pcent_block);

								// append comparison element
								stat_elem.find('.vbo-widget-finance-stat').append(comparison_el);
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// remove loading icons
						widget_instance.find('.vbo-widget-finance-data-blocks').find('.vbo-widget-finance-stat-compare').remove();
						console.error(error);
						alert(error.responseText);
					}
				);

			}

			/**
			 * Navigate between the date steps and load the stats.
			 */
			function vboWidgetFinanceDatesNav(wrapper, direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show loading skeletons
				vboWidgetFinanceSkeletons(wrapper);

				// launch dates navigation and load records
				vboWidgetFinanceLoad(wrapper, direction);
			}

			/**
			 * Load stats for the selected dates.
			 */
			function vboWidgetFinanceChangeDates(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// hide filters when making a new request
				widget_instance.find('.vbo-reportwidget-filters').hide();

				// show loading skeletons
				vboWidgetFinanceSkeletons(wrapper);

				// load data
				vboWidgetFinanceLoad(wrapper);
			}

			/**
			 * Toggle dates and step filters.
			 */
			function vboWidgetFinanceToggleFilters(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				widget_instance.find('.vbo-reportwidget-filters').toggle();
			}

			/**
			 * Datepicker dates selection.
			 */
			function vboWidgetFinanceCheckDates(selectedDate, inst) {
				if (selectedDate === null || inst === null) {
					return;
				}
				var cur_from_date = jQuery(this).val();
				if (jQuery(this).hasClass("vbo-finance-dtpicker-from") && cur_from_date.length) {
					var nowstart = jQuery(this).datepicker("getDate");
					var nowstartdate = new Date(nowstart.getTime());
					jQuery(".vbo-finance-dtpicker-to").datepicker("option", {minDate: nowstartdate});
				}
			}

			/**
			 * Navigation period cookie onchange.
			 */
			function vboWidgetFinanceChangePeriod(period) {
				// always exclude "booking_dates"
				if (period && period === 'booking_dates') {
					return;
				}
				// update cookie for the step selected
				var nd = new Date();
				nd.setTime(nd.getTime() + (365*24*60*60*1000));
				document.cookie = "vbo_widget_finance_step=" + period + "; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
			}

		</script>
			<?php
		}
		?>

		<script type="text/javascript">

			jQuery(function() {

				// render datepicker calendars for dates navigation
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-finance-dtpicker-from, .vbo-finance-dtpicker-to').datepicker({
					minDate: "<?php echo date($df, $mindate); ?>",
					maxDate: "<?php echo date($df, $maxdate); ?>",
					yearRange: "<?php echo date('Y', $mindate); ?>:<?php echo date('Y', $maxdate); ?>",
					changeMonth: true,
					changeYear: true,
					dateFormat: "<?php echo $dtpicker_df; ?>",
					onSelect: vboWidgetFinanceCheckDates
				});

				// triggering for datepicker calendar icons
				jQuery('i.vbo-widget-finance-caltrigger').click(function() {
					var jdp = jQuery(this).parent().find('input.hasDatepicker');
					if (jdp.length) {
						jdp.focus();
					}
				});

				// when document is ready, load stats for this widget's instance
				vboWidgetFinanceLoad('<?php echo $wrapper_id; ?>');

			});

		</script>

		<?php
	}

	/**
	 * Returns an array with the minimum and maximum dates booked.
	 * 
	 * @return 	array 	to be used with list() to get the min/max stay date timestamps.
	 */
	protected function getMinDatesFinance()
	{
		$dbo = JFactory::getDbo();

		$mindate = null;
		$maxdate = null;

		$q = "SELECT MIN(`checkin`) AS `mindate`, MAX(`checkout`) AS `maxdate` FROM `#__vikbooking_orders` 
			WHERE `status` = 'confirmed' AND `closure` = 0";
		$dbo->setQuery($q);
		$data = $dbo->loadAssoc();
		if ($data) {
			if (!empty($data['mindate']) && !empty($data['maxdate'])) {
				$mindate = $data['mindate'];
				$maxdate = $data['maxdate'];
			}
		}

		return [$mindate, $maxdate];
	}

	/**
	 * Calculates the new dates interval for navigation according to step.
	 * Internal method for this widget only.
	 * 
	 * @param 	int 	$from_ts 	current from date timestamp.
	 * @param 	int 	$to_ts 		current end date timestamp.
	 * @param 	string 	$step		the navigation step to take (week, month, quarter or booking_dates).
	 * @param 	int 	$date_dir 	less than 0 backward nav, more than 0 forward.
	 * 
	 * @return 	array 				the new from and end date timestamps, plus the step name.
	 */
	protected function calcDatesNavigation($from_ts, $to_ts, $step, $date_dir)
	{
		if (empty($from_ts) || (!($date_dir > 0) && !($date_dir < 0))) {
			return [$from_ts, $to_ts, ''];
		}

		// start/end date information
		$from_info = getdate($from_ts);
		$to_info   = getdate($to_ts);

		// next or prev day (booking_dates)
		if (!strcasecmp($step, 'booking_dates')) {
			if ($date_dir > 0) {
				// forward dates navigation
				$from_ts = mktime(0, 0, 0, $to_info['mon'], ($to_info['mday'] + 1), $to_info['year']);
				$to_ts 	 = mktime(23, 59, 59, $to_info['mon'], ($to_info['mday'] + 1), $to_info['year']);
			} elseif ($date_dir < 0) {
				// backward dates navigation
				$from_ts = mktime(0, 0, 0, $from_info['mon'], ($from_info['mday'] - 1), $from_info['year']);
				$to_ts 	 = mktime(23, 59, 59, $from_info['mon'], ($from_info['mday'] - 1), $from_info['year']);
			}

			return [$from_ts, $to_ts, JText::translate('VBPCHOOSEBUSYORDATE')];
		}

		// next or prev week
		if ($step == 'week') {
			if ($date_dir > 0) {
				// forward dates navigation
				$from_ts = $to_ts;
				$to_ts   = strtotime("+1 week", $to_ts);
			} elseif ($date_dir < 0) {
				// backward dates navigation
				$to_ts   = $from_ts;
				$from_ts = strtotime("-1 week", $from_ts);
			}

			return [$from_ts, $to_ts, JText::translate('VBOWEEK')];
		}

		// next or prev month
		if ($step == 'month') {
			if ($date_dir > 0) {
				// forward dates navigation
				$from_ts = mktime(0, 0, 0, ($from_info['mon'] + 1), 1, $from_info['year']);
				$to_ts 	 = mktime(0, 0, 0, ($from_info['mon'] + 1), date('t', $from_ts), $from_info['year']);
			} elseif ($date_dir < 0) {
				// backward dates navigation
				$from_ts = mktime(0, 0, 0, ($from_info['mon'] - 1), 1, $from_info['year']);
				$to_ts 	 = mktime(0, 0, 0, ($from_info['mon'] - 1), date('t', $from_ts), $from_info['year']);
			}

			return [$from_ts, $to_ts, JText::translate('VBPVIEWRESTRICTIONSTWO')];
		}

		// next or prev year (keep the same exact dates)
		if ($step == 'year') {
			if ($date_dir > 0) {
				// forward dates navigation
				$from_ts = mktime(0, 0, 0, $from_info['mon'], $from_info['mday'], ($from_info['year'] + 1));
				$to_ts 	 = mktime(0, 0, 0, $to_info['mon'], $to_info['mday'], ($to_info['year'] + 1));
			} elseif ($date_dir < 0) {
				// backward dates navigation
				$from_ts = mktime(0, 0, 0, $from_info['mon'], $from_info['mday'], ($from_info['year'] - 1));
				$to_ts 	 = mktime(0, 0, 0, $to_info['mon'], $to_info['mday'], ($to_info['year'] - 1));
			}

			return [$from_ts, $to_ts, JText::translate('VBCONFIGSEARCHPMAXDATEYEARS')];
		}

		// next or prev quarter by default
		if ($date_dir > 0) {
			// forward dates navigation
			$from_ts = mktime(0, 0, 0, ($from_info['mon'] + 3), 1, $from_info['year']);
			$end_ts  = mktime(0, 0, 0, ($from_info['mon'] + 5), 1, $from_info['year']);
			$to_ts 	 = mktime(0, 0, 0, ($from_info['mon'] + 5), date('t', $end_ts), $from_info['year']);
		} elseif ($date_dir < 0) {
			// backward dates navigation
			$from_ts = mktime(0, 0, 0, ($from_info['mon'] - 3), 1, $from_info['year']);
			$end_ts  = mktime(0, 0, 0, ($from_info['mon'] - 1), 1, $from_info['year']);
			$to_ts 	 = mktime(0, 0, 0, ($from_info['mon'] - 1), date('t', $end_ts), $from_info['year']);
		}

		return [$from_ts, $to_ts, JText::translate('VBO_QUARTER')];
	}

	/**
	 * Parse an interval of date timestamps into a readable interval of dates according to step.
	 * Internal method for this widget only.
	 * 
	 * @param 	int 	$from_ts 	current from date timestamp.
	 * @param 	int 	$to_ts 		current end date timestamp.
	 * @param 	string 	$vbo_df 	the date format in VBO.
	 * @param 	string 	$step		the navigation step to take (week, month or quarter).
	 * 
	 * @return 	array 				the formatted from and to date interval strings.
	 */
	protected function parseReadableDateInterval($from_ts, $to_ts, $vbo_df, $step)
	{
		$from_info = getdate($from_ts);
		$to_info   = getdate($to_ts);
		$format_from_date = date($vbo_df, $from_ts);
		$format_to_date   = date($vbo_df, $to_ts);

		if ($step == 'week') {
			// include day of week
			$format_from_date = VikBooking::sayWeekDay($from_info['wday']) . ', ' . $format_from_date;
			$format_to_date   = VikBooking::sayWeekDay($to_info['wday']) . ', ' . $format_to_date;
		} elseif ($step == 'month') {
			// check if it's a full month to say its name
			if ($from_info['mon'] == $to_info['mon'] && $from_info['year'] == $to_info['year']) {
				if ($from_info['mday'] == 1 && $to_info['mday'] == date('t', $to_ts)) {
					$format_from_date = VikBooking::sayMonth($from_info['mon']) . ' ' . $from_info['year'];
					$format_to_date   = VikBooking::sayMonth($to_info['mon']) . ' ' . $to_info['year'];
				}
			}
		} elseif ($step == 'quarter') {
			// check if it's a full quarter
			if ($from_info['mday'] == 1 && $to_info['mday'] == date('t', $to_ts)) {
				$format_from_date = VikBooking::sayMonth($from_info['mon']) . ' ' . $from_info['year'];
				$format_to_date   = VikBooking::sayMonth($to_info['mon']) . ' ' . $to_info['year'];
			}
		}

		return [$format_from_date, $format_to_date];
	}

	/**
	 * Sets up the necessary script to load the welcome message for the user.
	 * On WordPress this method may run outside Vik Booking, and so Javascript,
	 * may not be able to access language definitions from the DOM.
	 * 
	 * @return 	bool 	true if welcome was set up for the user, or false.
	 */
	protected function setupWelcomeStats()
	{
		// avoid welcome stats on certain pages
		if (VBOPlatformDetection::isWordPress()) {
			global $pagenow;
			$skip_pages = ['update.php', 'plugins.php', 'plugin-install.php'];
			if (isset($pagenow) && in_array($pagenow, $skip_pages)) {
				return false;
			}
		}

		$admin_user 	 = JFactory::getUser();
		$admin_user_name = $admin_user->name;

		if (!$admin_user_name || !$admin_user->authorise('core.vbo.bookings', 'com_vikbooking')) {
			// user not authorized
			return false;
		}

		// get yesterday's date
		$yesterday_info = getdate(strtotime('yesterday'));
		$yesterday_read = implode(' ', [VikBooking::sayWeekDay($yesterday_info['wday'], true), $yesterday_info['mday'], VikBooking::sayMonth($yesterday_info['mon'], true)]);
		$yesterday_read = htmlspecialchars($yesterday_read);
		$yesterday = date('Y-m-d', $yesterday_info[0]);

		// the payload for getting the welcome stats for yesterday
		$payload = [
			'fromdate' => $yesterday,
			'todate'   => $yesterday,
			'type' 	   => 'booking_dates',
		];

		// check if we are inside Vik Booking
		$in_vbo  = (int)(JFactory::getApplication()->input->get('option', '') === 'com_vikbooking');
		$vbo_uri = VBOPlatformDetection::isWordPress() ? 'admin.php?page=vikbooking' : 'index.php?option=com_vikbooking';
		if (!$in_vbo && VBOPlatformDetection::isWordPress()) {
			// append query string value to render the admin widget
			$q_cmds = [
				'load_widget' 	 => $this->widgetId,
				'multitask_data' => $payload,
			];
			$vbo_uri .= '&' . http_build_query($q_cmds);
		}

		// build data (the JS code may not be able to access script language definitions if running outside Vik Booking)
		$aj_endpoint  = $this->getExecWidgetAjaxUri();
		$widget_id 	  = $this->getIdentifier();
		$website_logo = $this->getIconUrl();
		$website_logo = $website_logo ? $website_logo : '';
		$greetings 	  = VikBooking::strTrimLiteral(htmlspecialchars(JText::sprintf('VBO_WELCOME_ADMIN_USER', $admin_user_name)));
		$greetings_ms = VikBooking::strTrimLiteral(htmlspecialchars(JText::translate('VBO_BOOKINGS_YESTERDAY')));
		$modal_title  = VikBooking::strTrimLiteral(htmlspecialchars(JText::translate('VBOYESTERDAY') . ', ' . $yesterday_read));
		$payload_json = json_encode($payload);
		$sugg_notifs_btn = '<button class="vbo-suggest-notifications-btn" type="button"><i class="' . VikBookingIcons::i('bell', 'can-shake') . '"></i></button>';

		JFactory::getDocument()->addScriptDeclaration(
<<<JS
(function($) {
	'use strict';

	$(function() {
		try {
			if (!VBOCore.storageSupported() || VBOCore.storageGetItem('vbo_finance_last_dt') == '$yesterday') {
				return false;
			}
		} catch(e) {
			return false;
		}

		let call_method = 'getWelcomeStats';
		VBOCore.doAjax(
			"$aj_endpoint",
			{
				widget_id: "$widget_id",
				call: call_method,
				return: 1,
				tmpl: "component"
			},
			(response) => {
				try {
					var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
					if (!obj_res.hasOwnProperty(call_method)) {
						throw new Error('Unexpected JSON response');
					}

					VBOCore.storageSetItem('vbo_finance_last_dt', '$yesterday');

					if (!obj_res[call_method].hasOwnProperty('tot_bookings') || !obj_res[call_method]['tot_bookings']) {
						return;
					}

					let vbo_notif_click_handler = 'VBOCore.handleGoto';
					if ($in_vbo) {
						vbo_notif_click_handler = () => {
							try {
								let yesterday_modal = VBOCore.displayModal({
									suffix: 	   'finance-welcome',
									extra_class:   'vbo-modal-rounded vbo-modal-tall vbo-modal-nofooter',
									title: 		   '<span class="vbo-suggest-notifications-wrap"><span>$modal_title</span>' + (VBOCore.notificationsEnabled() === false ? '$sugg_notifs_btn' : '') + '</span>',
									dismiss_event: 'vbo-dismiss-modal-finance-welcome',
									loading_event: 'vbo-loading-modal-finance-welcome',
								});

								VBOCore.suggestNotifications('.vbo-suggest-notifications-btn');
								VBOCore.emitEvent('vbo-loading-modal-finance-welcome');

								VBOCore.renderAdminWidget('$this->widgetId', $payload_json).then((content) => {
									VBOCore.emitEvent('vbo-loading-modal-finance-welcome');
									yesterday_modal.append(content);
								}).catch((error) => {
									VBOCore.emitEvent('vbo-dismiss-modal-finance-welcome');
									alert(error);
								});
							} catch(e) {
								document.location.href = '$vbo_uri';
							}
						};
					}

					setTimeout(() => {
						VBOCore.dispatchNotification({
							title: 	 '$greetings',
							message: ('$greetings_ms').replace('%d', obj_res[call_method]['tot_bookings']),
							icon: 	 '$website_logo',
							onclick: vbo_notif_click_handler,
							gotourl: '$vbo_uri',
						});
					}, 500);
				} catch(e) {
					console.error(e);
				}
			},
			(error) => {
				console.error(error.responseText);
			}
		);
	});
})(jQuery);
JS
		);

		return true;
	}

	/**
	 * Returns the URL to the default site logo.
	 * 
	 * @return 	string|null
	 */
	protected function getIconUrl()
	{
		$config = VBOFactory::getConfig();

		// back-end custom logo
		$use_logo = $config->get('backlogo');
		if (empty($use_logo) || !strcasecmp($use_logo, 'vikbooking.png')) {
			// fallback to company (site) logo
			$use_logo = $config->get('sitelogo');
		}

		if (!empty($use_logo) && strcasecmp($use_logo, 'vikbooking.png')) {
			// uploaded logo found
			$use_logo = VBO_ADMIN_URI . 'resources/' . $use_logo;
		} else {
			$use_logo = null;
		}

		return $use_logo;
	}
}

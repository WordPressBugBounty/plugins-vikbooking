<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Obtain vars from arguments received in the layout file.
 * This is the layout file for the "finance" operator tool.
 * 
 * @var string 	$tool 		   The tool identifier.
 * @var array 	$operator      The operator record accessing the tool.
 * @var object 	$permissions   The operator-tool permissions registry.
 * @var string 	$tool_uri 	   The base URI for rendering this tool.
 */
extract($displayData);

// access environment objects
$app     = JFactory::getApplication();
$vbo_app = VikBooking::getVboApplication();

// load datepicker calendar assets
$vbo_app->loadDatePicker();

// get listings assigned to the current operator (no listings equals to all listings)
$listings = array_filter(
	array_map('intval', (array) $permissions->get('rooms', []))
);

// get the name of all listings, if not all
$listing_names = [];
if ($listings) {
	foreach (VikBooking::getAvailabilityInstance()->loadRooms() as $room_id => $room_data) {
		if (in_array($room_id, $listings)) {
			// push listing name
			$listing_names[] = $room_data['name'];
		}
	}
}

// access one report object
$report = VikBooking::getReportInstance('rates_flow');

// access the finance helper object
$finance = VBOTaxonomyFinance::getInstance();

// currency symbol
$currencysymb = VikBooking::getCurrencySymb();

// get date format
$df = $report->getDateFormat();

// get dates from filters
$from_date = $app->getUserStateFromRequest('vbo.operators.tools.finance.fromdt', 'from_date', date($df, mktime(0, 0, 0, date('n'), 1, date('Y'))), 'string');
$to_date   = $app->getUserStateFromRequest('vbo.operators.tools.finance.todt', 'to_date', date($df), 'string');

// get calculation type from filters ("stay_dates" or "booking_dates")
$calc_type = $app->getUserStateFromRequest('vbo.operators.tools.finance.type', 'calc_type', 'stay_dates', 'string');

// get the financial stats for the requested dates
try {
	$stats = $finance->getStats($from_date, $to_date, $listings, $calc_type);
} catch (Exception $e) {
	// propagate the error
	VBOHttpDocument::getInstance()->close($e->getCode(), $e->getMessage());
}

?>

<div class="vbo-operator-tool-finance-wrap">
	<div class="vbo-operator-tool-top-filters vbo-operator-tool-finance-filters">
		<div class="vbo-operator-tool-top-filters-inner">
			<form action="<?php echo $tool_uri; ?>" method="post">
				<div class="vbo-operator-tool-top-filter vbo-operator-tool-dt-filter">
					<label for="vbo-tool-finance-from-dt"><?php echo JText::translate('VBOPROMORENTFROM'); ?></label>
					<?php echo $vbo_app->getCalendar($from_date, 'from_date', 'vbo-tool-finance-from-dt'); ?>
				</div>
				<div class="vbo-operator-tool-top-filter vbo-operator-tool-dt-filter">
					<label for="vbo-tool-finance-to-dt"><?php echo JText::translate('VBOPROMORENTTO'); ?></label>
					<?php echo $vbo_app->getCalendar($to_date, 'to_date', 'vbo-tool-finance-to-dt'); ?>
				</div>
				<div class="vbo-operator-tool-top-filter vbo-operator-tool-submit-filter">
					<button type="submit" class="btn btn-primary vbo-pref-color-btn"><?php echo JText::translate('VBSEARCHBUTTON'); ?></button>
				</div>
			</form>
			<div class="vbo-operator-tool-listings">
				<span><?php echo $listing_names ? JText::sprintf('VBO_LISTING_NAMES', implode(', ', $listing_names)) : JText::translate('VBO_ALL_LISTINGS'); ?></span>
			</div>
		</div>
	</div>
	<div class="vbo-operator-tool-finance-stats">

		<div class="vbo-tool-finance-data-blocks">

			<div class="vbo-tool-finance-data-block" data-typestat="gross_revenue">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBO_GROSS_BOOKING_VALUE'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['gross_revenue']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['gross_revenue']); ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-tool-finance-data-block" data-typestat="taxes">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBOINVCOLTAX'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['taxes']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['taxes']); ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-tool-finance-data-block" data-typestat="cmms">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBTOTALCOMMISSIONS'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['cmms']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['cmms']); ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-tool-finance-data-block" data-typestat="revenue">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBOREPORTREVENUE'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['revenue']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['revenue']); ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-tool-finance-data-block" data-typestat="ibe_revenue">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBOREPORTREVENUEREVWEB'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['ibe_revenue']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['ibe_revenue']); ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-tool-finance-data-block" data-typestat="ota_revenue">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBOREPORTREVENUEREVOTA'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['ota_revenue']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['ota_revenue']); ?></span>
						</span>
					</div>
				</div>
			</div>

		<?php
		if ($stats['cmm_savings'] > 0) {
			?>
			<div class="vbo-tool-finance-data-block" data-typestat="ota_avg_cmms">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBO_AVG_COMMISSIONS'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value">
							<span><?php echo $stats['ota_avg_cmms']; ?>%</span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-tool-finance-data-block" data-typestat="cmm_savings">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBO_COMMISSION_SAVINGS'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($stats['cmm_savings']); ?>">
							<span class="vbo-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-price"><?php echo $finance->numberFormatShort($stats['cmm_savings']); ?></span>
						</span>
					</div>
				</div>
			</div>
			<?php
		}
		?>

			<div class="vbo-tool-finance-data-block" data-typestat="tot_bookings">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBCUSTOMERTOTBOOKINGS'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value">
							<span><?php echo $stats['tot_bookings']; ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-tool-finance-data-block" data-typestat="nights_booked">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBOGRAPHTOTNIGHTSLBL'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo htmlspecialchars(trim(str_replace('%', '', JText::translate('VBOREPORTREVENUEPOCC'))) . ' ' . $stats['occupancy'] . '%'); ?>">
							<span><?php echo $stats['nights_booked'] . '/' . $stats['tot_inventory']; ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-tool-finance-data-block" data-typestat="avg_los">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBTRKAVGLOS'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value">
							<span><?php echo $stats['avg_los']; ?></span>
						</span>
					</div>
				</div>
			</div>

			<div class="vbo-tool-finance-data-block" data-typestat="rooms_booked">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBLIBTEN'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-amount">
						<span class="vbo-tool-finance-stat-amount-value vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo htmlspecialchars(JText::translate('VBOGRAPHTOTUNITSLBL') . ' ' . $stats['room_units']); ?>">
							<span><?php echo $stats['rooms_booked']; ?></span>
						</span>
					</div>
				</div>
			</div>

		</div>

	<?php
	if (($stats['country_ranks'] ?? []) || ($stats['pos_revenue'] ?? [])) {
		?>
		<div class="vbo-tool-finance-data-block-rankings">

			<div class="vbo-tool-finance-data-block-rank" data-typestat="country_ranks">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBOSTATSTOPCOUNTRIES'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-ranks">
					<?php
					foreach ($stats['country_ranks'] as $country_rank) {
						?>
						<div class="vbo-tool-finance-stat-rank">
							<div class="vbo-tool-finance-stat-rank-logo">
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
							<div class="vbo-tool-finance-stat-rank-score">
								<div class="vbo-tool-finance-stat-rank-name">
									<span><?php echo $country_rank['name']; ?></span>
								</div>
								<div class="vbo-tool-finance-stat-rank-amount">
									<span class="vbo-currency"><?php echo $currencysymb; ?></span>
									<span class="vbo-price vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($country_rank['revenue']); ?>"><?php echo $finance->numberFormatShort($country_rank['revenue']); ?></span>
								</div>
								<div class="vbo-tool-finance-stat-rank-pcent">
									<progress class="vbo-tool-finance-progress" value="<?php echo $country_rank['pcent']; ?>" max="100"><?php echo $country_rank['pcent']; ?>%</progress>
								</div>
							</div>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</div>

			<div class="vbo-tool-finance-data-block-rank" data-typestat="pos_revenue">
				<div class="vbo-tool-finance-stat">
					<div class="vbo-tool-finance-stat-info">
						<span class="vbo-tool-finance-stat-name"><?php echo JText::translate('VBOCHANNELS'); ?></span>
					</div>
					<div class="vbo-tool-finance-stat-ranks">
					<?php
					foreach ($stats['pos_revenue'] as $pos_revenue) {
						$say_pos_name = ucfirst($pos_revenue['name']);
						?>
						<div class="vbo-tool-finance-stat-rank">
							<div class="vbo-tool-finance-stat-rank-logo">
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
							<div class="vbo-tool-finance-stat-rank-score">
								<div class="vbo-tool-finance-stat-rank-name">
									<span><?php echo $say_pos_name; ?></span>
								</div>
								<div class="vbo-tool-finance-stat-rank-amount">
									<span class="vbo-currency"><?php echo $currencysymb; ?></span>
									<span class="vbo-price vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo $currencysymb . ' ' . VikBooking::numberFormat($pos_revenue['revenue']); ?>"><?php echo $finance->numberFormatShort($pos_revenue['revenue']); ?></span>
								</div>
								<div class="vbo-tool-finance-stat-rank-pcent">
									<progress class="vbo-tool-finance-progress" value="<?php echo $pos_revenue['pcent']; ?>" max="100"><?php echo $pos_revenue['pcent']; ?>%</progress>
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
		<?php
	}
	?>

	</div>
</div>

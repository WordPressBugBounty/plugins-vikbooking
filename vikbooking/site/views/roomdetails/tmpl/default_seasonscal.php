<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 e4j - E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Seasons calendar.
 */

// JSON-decode room parameters
$rparams = json_decode($this->room['params'], true);

$currencysymb = VikBooking::getCurrencySymb();
$vbdateformat = VikBooking::getDateFormat();
$datesep = VikBooking::getDateSeparator();
if ($vbdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

if ($this->seasons_cal) {
	// seasons calendar
	$price_types_show = intval(VikBooking::getRoomParam('seasoncal_prices', $rparams)) == 1 ? false : true;
	$los_show = intval(VikBooking::getRoomParam('seasoncal_restr', $rparams)) == 1 ? true : false;
	?>
<div class="vbo-seasonscalendar-cont">
	<h4><?php echo JText::translate('VBOSEASONSCALENDAR'); ?></h4>
	<div class="table-responsive">
		<table class="table vbo-seasons-calendar-table">
			<tr class="vbo-seasons-calendar-nightsrow">
				<td>&nbsp;</td>
			<?php
			foreach ($this->seasons_cal['offseason'] as $numnights => $ntars) {
				?>
				<td><span><?php echo JText::sprintf(($numnights > 1 ? 'VBOSEASONCALNUMNIGHTS' : 'VBOSEASONCALNUMNIGHT'), $numnights); ?></span></td>
				<?php
			}
			?>
			</tr>
			<tr class="vbo-seasons-calendar-offseasonrow">
				<td>
					<span class="vbo-seasons-calendar-offseasonname"><?php echo JText::translate('VBOSEASONSCALOFFSEASONPRICES'); ?></span>
				</td>
			<?php
			foreach ($this->seasons_cal['offseason'] as $numnights => $tars) {
				?>
				<td>
					<div class="vbo-seasons-calendar-offseasoncosts">
						<?php
						foreach ($tars as $tar) {
							?>
						<div class="vbo-seasons-calendar-offseasoncost">
							<?php
							if ($price_types_show) {
							?>
							<span class="vbo-seasons-calendar-pricename"><?php echo $tar['name']; ?></span>
							<?php
							}
							?>
							<span class="vbo-seasons-calendar-pricecost">
								<span class="vbo_currency"><?php echo $currencysymb; ?></span><span class="vbo_price"><?php echo VikBooking::numberFormat($tar['cost']); ?></span>
							</span>
						</div>
							<?php
							if (!$price_types_show) {
								break;
							}
						}
						?>
					</div>
				</td>
				<?php
			}
			?>
			</tr>
		<?php
		foreach ($this->seasons_cal['seasons'] as $s_id => $s) {
			$restr_diff_nights = [];
			if ($los_show && array_key_exists($s_id, $this->seasons_cal['restrictions'])) {
				$restr_diff_nights = VikBooking::compareSeasonRestrictionsNights($this->seasons_cal['restrictions'][$s_id]);
			}
			?>
			<tr class="vbo-seasons-calendar-seasonrow">
				<td>
					<div class="vbo-seasons-calendar-seasondates">
						<span class="vbo-seasons-calendar-seasonfrom"><?php echo date(str_replace("/", $datesep, $df), $s['from_ts']); ?></span>
						<span class="vbo-seasons-calendar-seasondates-separe">-</span>
						<span class="vbo-seasons-calendar-seasonto"><?php echo date(str_replace("/", $datesep, $df), $s['to_ts']); ?></span>
					</div>
					<span class="vbo-seasons-calendar-seasonname"><?php echo $s['spname']; ?></span>
			<?php
			if ($los_show && array_key_exists($s_id, $this->seasons_cal['restrictions']) && count($restr_diff_nights) == 0) {
				//Season Restrictions
				$season_restrictions = [];
				foreach ($this->seasons_cal['restrictions'][$s_id] as $restr) {
					$season_restrictions = $restr;
					break;
				}
				?>
					<div class="vbo-seasons-calendar-restrictions">
				<?php
				if ($season_restrictions['minlos'] > 1) {
					?>
						<span class="vbo-seasons-calendar-restriction-minlos"><?php echo JText::translate('VBORESTRMINLOS'); ?><span class="vbo-seasons-calendar-restriction-minlos-badge"><?php echo $season_restrictions['minlos']; ?></span></span>
					<?php
				}
				if (array_key_exists('maxlos', $season_restrictions) && $season_restrictions['maxlos'] > 1) {
					?>
						<span class="vbo-seasons-calendar-restriction-maxlos"><?php echo JText::translate('VBORESTRMAXLOS'); ?><span class="vbo-seasons-calendar-restriction-maxlos-badge"><?php echo $season_restrictions['maxlos']; ?></span></span>
					<?php
				}
				if (array_key_exists('wdays', $season_restrictions) && count($season_restrictions['wdays']) > 0) {
					?>
						<div class="vbo-seasons-calendar-restriction-wdays">
							<label><?php echo JText::translate((count($season_restrictions['wdays']) > 1 ? 'VBORESTRARRIVWDAYS' : 'VBORESTRARRIVWDAY')); ?></label>
					<?php
					foreach ($season_restrictions['wdays'] as $wday) {
						?>
							<span class="vbo-seasons-calendar-restriction-wday"><?php echo VikBooking::sayWeekDay($wday); ?></span>
						<?php
					}
					?>
						</div>
					<?php
				} elseif ((array_key_exists('cta', $season_restrictions) && count($season_restrictions['cta']) > 0) || (array_key_exists('ctd', $season_restrictions) && count($season_restrictions['ctd']) > 0)) {
					if (array_key_exists('cta', $season_restrictions) && count($season_restrictions['cta']) > 0) {
						?>
						<div class="vbo-seasons-calendar-restriction-wdays vbo-seasons-calendar-restriction-cta">
							<label><?php echo JText::translate('VBORESTRWDAYSCTA'); ?></label>
						<?php
						foreach ($season_restrictions['cta'] as $wday) {
							?>
							<span class="vbo-seasons-calendar-restriction-wday"><?php echo VikBooking::sayWeekDay(str_replace('-', '', $wday)); ?></span>
							<?php
						}
						?>
						</div>
						<?php
					}
					if (array_key_exists('ctd', $season_restrictions) && count($season_restrictions['ctd']) > 0) {
						?>
						<div class="vbo-seasons-calendar-restriction-wdays vbo-seasons-calendar-restriction-ctd">
							<label><?php echo JText::translate('VBORESTRWDAYSCTD'); ?></label>
						<?php
						foreach ($season_restrictions['ctd'] as $wday) {
							?>
							<span class="vbo-seasons-calendar-restriction-wday"><?php echo VikBooking::sayWeekDay(str_replace('-', '', $wday)); ?></span>
							<?php
						}
						?>
						</div>
						<?php
					}
				}
				?>
					</div>
				<?php
			}
			?>
				</td>
			<?php
			if (array_key_exists($s_id, $this->seasons_cal['season_prices']) && $this->seasons_cal['season_prices'][$s_id]) {
				foreach ($this->seasons_cal['season_prices'][$s_id] as $numnights => $tars) {
					$show_day_cost = true;
					if ($los_show && array_key_exists($s_id, $this->seasons_cal['restrictions']) && array_key_exists($numnights, $this->seasons_cal['restrictions'][$s_id])) {
						if ($this->seasons_cal['restrictions'][$s_id][$numnights]['allowed'] === false) {
							$show_day_cost = false;
						}
					}
					?>
				<td>
				<?php
				if ($show_day_cost) {
				?>
					<div class="vbo-seasons-calendar-seasoncosts">
					<?php
					foreach ($tars as $tar) {
						//print the types of price that are not being modified by this special price with opacity
						$not_affected = (!array_key_exists('origdailycost', $tar));
						//
						?>
						<div class="vbo-seasons-calendar-seasoncost<?php echo ($not_affected ? ' vbo-seasons-calendar-seasoncost-notaffected' : ''); ?>">
						<?php
						if ($price_types_show) {
							?>
							<span class="vbo-seasons-calendar-pricename"><?php echo $tar['name']; ?></span>
							<?php
						}
						?>
							<span class="vbo-seasons-calendar-pricecost">
								<span class="vbo_currency"><?php echo $currencysymb; ?></span><span class="vbo_price"><?php echo VikBooking::numberFormat($tar['cost']); ?></span>
							</span>
						</div>
						<?php
						if (!$price_types_show) {
							break;
						}
					}
					?>
					</div>
				<?php
				} else {
					?>
					<div class="vbo-seasons-calendar-seasoncosts-disabled"></div>
					<?php
				}
				?>
				</td>
				<?php
				}
			}
			?>
			</tr>
			<?php
		}
	?>
		</table>
	</div>
</div>
<?php
}

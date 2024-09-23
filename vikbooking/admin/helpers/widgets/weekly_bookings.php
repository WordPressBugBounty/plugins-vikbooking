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
 * Class handler for admin widget "weekly bookings" (donut charts).
 * 
 * @since 	1.4.0
 */
class VikBookingAdminWidgetWeeklyBookings extends VikBookingAdminWidget
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

		$this->widgetName = JText::translate('VBO_W_WEEKLYB_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_WEEKLYB_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		/**
		 * Define widget and icon and style name.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('calendar-check') . '"></i>';
		$this->widgetStyleName = 'brown';
	}

	public function render(VBOMultitaskData $data = null)
	{
		// increase widget's instance counter
		static::$instance_counter++;

		// check whether the widget is being rendered via AJAX when adding it through the customizer
		$is_ajax = $this->isAjaxRendering();

		// widget instance identifier
		$widget_instance = $is_ajax ? -1 : static::$instance_counter;

		JFactory::getDocument()->addScript(VBO_ADMIN_URI.'resources/donutChart.js');
		$wdaysmap = array(
			'0' => JText::translate('VBSUNDAY'),
			'1' => JText::translate('VBMONDAY'),
			'2' => JText::translate('VBTUESDAY'),
			'3' => JText::translate('VBWEDNESDAY'),
			'4' => JText::translate('VBTHURSDAY'),
			'5' => JText::translate('VBFRIDAY'),
			'6' => JText::translate('VBSATURDAY')
		);
		$today_end_ts = mktime(23, 59, 59, date("n"), date("j"), date("Y"));
		$busy = VikBooking::getAdminWidgetsInstance()->loadBusyRecordsUnclosed();
		$info_rooms = array(
			'unpublished_rooms' => VikBooking::getAdminWidgetsInstance()->getRoomsData('unpublished_rooms'),
			'tot_rooms_units' => VikBooking::getAdminWidgetsInstance()->getRoomsData('tot_rooms_units'),
		);
		$no_av_rooms = false;
		if (!$info_rooms['tot_rooms_units']) {
			$no_av_rooms = true;
			$info_rooms['tot_rooms_units'] = 1;
		}

		// chart for rooms sold today and all week
		?>
	<script type="text/javascript">
	function renderDonutChart(elemid, params) {
		// default object parameters
		defparams = {
			start: 0,
			tot_booked_today: 0, // should be specified
			tot_rooms_units: 1, // should be specified
			size: 160,
			animationSpeed: 3,
			textColor: "#22485d",
			titlePosition: "outer-top", //outer-bottom, outer-top, inner-bottom, inner-top
			titleText: "",
			titleColor: '#333333',
			outer_color: '#2a762c', // should be specified
			innerCircleColor: '#ffffff',
			innerCircleStroke: '#333333'
		}
		// merge given parameters with the default ones
		Object.assign(defparams, params);
		// adjust prop unitText
		defparams.unitText = " / " + defparams.tot_rooms_units;
		// render donut chart
		var todaychart = new donutChart(elemid);
		todaychart.draw({
			start: defparams.start,
			end: defparams.tot_booked_today,
			maxValue: defparams.tot_rooms_units,
			size: defparams.size,
			unitText: defparams.unitText,
			animationSpeed: defparams.animationSpeed,
			textColor: defparams.textColor,
			titlePosition: defparams.titlePosition,
			titleText: defparams.titleText,
			titleColor: defparams.titleColor,
			outerCircleColor: defparams.outer_color,
			innerCircleColor: defparams.innerCircleColor,
			innerCircleStroke: defparams.innerCircleStroke
		});
	}
	</script>

	<div class="vbo-admin-widget-wrapper">
		<div class="vbo-admin-widget-head">
			<div class="vbo-admin-widget-head-inline">
				<h4><?php echo $this->widgetIcon; ?> <span><?php echo JText::translate('VBDASHWEEKGLOBAVAIL'); ?></span></h4>
				<div class="vbo-admin-widget-head-commands">
					<div class="vbo-reportwidget-commands">
						<div class="vbo-reportwidget-commands-main">
							<div class="vbo-reportwidget-command-chevron vbo-dash-chart-prev" style="display: none;">
								<span class="vbo-dash-chart-nav"><?php VikBookingIcons::e('chevron-left'); ?></span>
							</div>
							<div class="vbo-reportwidget-command-chevron vbo-dash-chart-next">
								<span class="vbo-dash-chart-nav"><?php VikBookingIcons::e('chevron-right'); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="vbo-dashboard-charts-wrapper">
		<?php
		$info_end = getdate($today_end_ts);
		for ($i = 0; $i < 7; $i++) {
			$today_ts = mktime($info_end['hours'], $info_end['minutes'], $info_end['seconds'], $info_end['mon'], ($info_end['mday'] + $i), $info_end['year']);
			$today_info = getdate($today_ts);
			$tot_booked_today = 0;
			if (count($busy) > 0) {
				foreach ($busy as $idroom => $rbusy) {
					if (in_array($idroom, $info_rooms['unpublished_rooms'])) {
						continue;
					}
					foreach ($rbusy as $b) {
						$tmpone = getdate($b['checkin']);
						$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
						$tmptwo = getdate($b['checkout']);
						$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
						if ($today_ts >= $ritts && $today_ts < $conts) {
							$tot_booked_today++;
						}
					}
				}
			}
			$percentage_booked = $no_av_rooms ? 0 : round((100 * $tot_booked_today / $info_rooms['tot_rooms_units']), 2);

			$outer_color = '#ff4d4d'; //red
			if ($percentage_booked > 33 && $percentage_booked <= 66) {
				$outer_color = '#ffa64d'; //orange
			} elseif ($percentage_booked > 66 && $percentage_booked < 100) {
				$outer_color = '#2a762c'; //green
			} elseif ($percentage_booked >= 100) {
				$outer_color = '#2482b4'; //light-blue
			}
			?>
			<div class="vbo-dashboard-chart-container" id="vbo-dashboard-chart-<?php echo $widget_instance; ?>-container-<?php echo ($i + 1); ?>">
				<span class="vbo-dashboard-chart-date"><?php echo $i == 0 ? JText::translate('VBTODAY').', ' : ''; ?><?php echo $wdaysmap[(string)$today_info['wday']]; ?> <?php echo $today_info['mday']; ?></span>
			</div>
			<script type="text/javascript">
			renderDonutChart("vbo-dashboard-chart-<?php echo $widget_instance; ?>-container-<?php echo ($i + 1); ?>", {
				tot_booked_today: <?php echo $tot_booked_today; ?>, 
				tot_rooms_units: <?php echo $info_rooms['tot_rooms_units']; ?>, 
				outer_color: '<?php echo $outer_color; ?>'
			});
			</script>
			<?php
		}
		// next week first day for navigation between donut charts
		$nextwk_ymd = date('Y-m-d', mktime(0, 0, 0, $today_info['mon'], ($today_info['mday'] + 1), $today_info['year']));
		?>
			<script type="text/javascript">
			var nextwk_ymd = '<?php echo $nextwk_ymd; ?>';
			jQuery(document).ready(function() {
				jQuery('.vbo-dash-chart-nav').click(function() {
					var instance_elem = jQuery(this).closest('.vbo-admin-widget-wrapper');
					var direction = jQuery(this).parent().hasClass('vbo-dash-chart-prev') ? 'prev' : 'next';
					var jqxhr = jQuery.ajax({
						type: "POST",
						url: "<?php echo $this->getExecWidgetAjaxUri('index.php?option=com_vikbooking&task=donut_charts_data'); ?>",
						data: {
							direction: direction,
							fromdt: nextwk_ymd,
							tmpl: "component"
						}
					}).done(function(res) {
						try {
							var obj_res = typeof res === 'string' ? JSON.parse(res) : res;
							// clean up current HTML
							instance_elem.find('.vbo-dashboard-charts-wrapper').html('');
							//
							for (var i = 0; i < obj_res.data.length; i++) {
								var identifier = 'vbo-dashboard-chart-<?php echo $widget_instance; ?>-container-' + obj_res.data[i]['ymd'];
								var container = '<div class="vbo-dashboard-chart-container" id="' + identifier + '">' +
												'	<span class="vbo-dashboard-chart-date">' + obj_res.data[i]['lbl'] + '</span>' +
												'</div>';
								instance_elem.find('.vbo-dashboard-charts-wrapper').append(container);
								var donut_params = {
									tot_booked_today: obj_res.data[i]['tot_booked'], 
									tot_rooms_units: obj_res.tot_units, 
									outer_color: obj_res.data[i]['color']
								}
								renderDonutChart(identifier, donut_params);
							}
							// update date for the next request and navigation data
							nextwk_ymd = obj_res.tod;
							if (obj_res.prevweek === true) {
								instance_elem.find('.vbo-dash-chart-prev').fadeIn();
							} else {
								instance_elem.find('.vbo-dash-chart-prev').hide();
							}
							if (obj_res.nextweek === true) {
								instance_elem.find('.vbo-dash-chart-next').fadeIn();
							} else {
								instance_elem.find('.vbo-dash-chart-next').hide();
							}

						} catch(err) {
							console.error('could not parse JSON response', err, res);
						}
					}).fail(function(err) {
						alert(err);
					});
				});
			});
			</script>
		</div>
		<div class="vbo-dashboard-refresh-container" style="display: none;">
			<div class="vbo-dashboard-refresh-head"><span class="vbo-dashboard-refresh-label"><?php echo JText::translate('VBDASHNEXTREFRESH'); ?></span> <span class="vbo-dashboard-refresh-minutes">05</span>:<span class="vbo-dashboard-refresh-seconds">00</span></div>
			<span class="vbo-dashboard-refresh-stop"> </span>
			<span class="vbo-dashboard-refresh-play" style="display: none;"> </span>
		</div>
	</div>
	<script type="text/javascript">
		var vbo_dash_counter = 300;
		var vbo_t;
		var vbo_m = 5;
		var vbo_s = 0;
		var vbo_t_on = false;
		function vboRefreshTimer() {
			vbo_dash_counter--;
			if (vbo_dash_counter <= 0) {
				vbo_t_on = false;
				clearTimeout(vbo_t);
				location.reload();
				return true;
			}
			vbo_m = Math.floor(vbo_dash_counter / 60);
			vbo_s = Math.floor((vbo_dash_counter - (vbo_m * 60)));
			jQuery(".vbo-dashboard-refresh-minutes").text("0"+vbo_m);
			jQuery(".vbo-dashboard-refresh-seconds").text((parseInt(vbo_s) < 10 ? "0"+vbo_s : vbo_s));
			vbo_t = setTimeout(vboRefreshTimer, 1000);
		}
		function vboStartTimer() {
			vbo_t = setTimeout(vboRefreshTimer, 1000);
			vbo_t_on = true;
		}
		jQuery(document).ready(function() {
			/**
			 * We disable the automatic reload timer. To restore it, remove the display: none
			 * attribute to the DIV.vbo-dashboard-refresh-container and decomment "vboStartTimer();".
			 * 
			 * @since 	1.14 (J) - 1.4.0 (WP)
			 */
			// vboStartTimer();
			//

			jQuery(".vbo-dashboard-refresh-stop").click(function() {
				if (vbo_t_on) {
					vbo_t_on = false;
					clearTimeout(vbo_t);
					jQuery(".vbo-dashboard-refresh-play").fadeIn();
				} else {
					jQuery(this).parent().fadeOut();
				}
			});
			jQuery(".vbo-dashboard-refresh-play").click(function() {
				if (!vbo_t_on) {
					// vboStartTimer();
					jQuery(this).fadeOut();
				}
			});
		});
	</script>
		<?php
	}
}

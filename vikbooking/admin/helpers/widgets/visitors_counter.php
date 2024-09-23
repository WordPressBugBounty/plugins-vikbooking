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
 * Class handler for admin widget "visitors counter".
 * 
 * @since 	1.4.0
 */
class VikBookingAdminWidgetVisitorsCounter extends VikBookingAdminWidget
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

		$this->widgetName = JText::translate('VBO_W_VISITCOUNT_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_VISITCOUNT_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		/**
		 * Define widget and icon and style name.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('binoculars') . '"></i>';
		$this->widgetStyleName = 'blue';
	}

	/**
	 * Custom method for this widget only to count the visitors.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 */
	public function countVisitors()
	{
		// load the tracker object without starting to track any data
		VikBooking::getTracker(true);

		// total unique and active visitors today
		$today_from = date('Y-m-d') . ' 00:00:00';
		$today_to = date('Y-m-d') . ' 23:59:59';
		$tot_today = VikBookingTracker::countTrackedRecords($today_from, $today_to);

		// total unique and active visitors this month until the end of today
		$month_from = date('Y-m') . '-01 00:00:00';
		$tot_month = VikBookingTracker::countTrackedRecords($month_from, $today_to);

		// total unique and active visitors last month until the end of today's month day
		$now = getdate();
		$last_month_from = date('Y-m-d H:i:s', mktime(0, 0, 0, ($now['mon'] - 1), 1, $now['year']));
		$last_month_to = date('Y-m-d H:i:s', mktime(23, 59, 59, ($now['mon'] - 1), $now['mday'], $now['year']));
		$tot_last_month = VikBookingTracker::countTrackedRecords($last_month_from, $last_month_to);

		// percentage of this month and last month (tot_month : x = tot_last_month : 100)
		$last_mon_divisor = $tot_last_month < 1 ? 1 : $tot_last_month;
		$pcent_month_full = $tot_month * 100 / $last_mon_divisor;
		$pcent_month = 0;
		if ($pcent_month_full > 0 && $pcent_month_full < 100) {
			// less visitors (-x %)
			$pcent_month = round((100 - $pcent_month_full), 1);
			$pcent_month = $pcent_month - ($pcent_month * 2);
		} elseif ($tot_last_month > 0 && $pcent_month_full > 100) {
			// more visitors (+x %)
			$pcent_month = '+' . round(($pcent_month_full - 100), 1);
		} elseif ($pcent_month_full > 0 && $tot_month != $tot_last_month) {
			// more visitors (+x %)
			$pcent_month = '+' . round($pcent_month_full, 1);
		}

		echo implode(';', array($tot_today, $tot_month, $tot_last_month, $pcent_month));
	}

	public function render(VBOMultitaskData $data = null)
	{
		// increase widget's instance counter
		static::$instance_counter++;

		// check whether the widget is being rendered via AJAX when adding it through the customizer
		$is_ajax = $this->isAjaxRendering();

		// generate a unique ID for the sticky notes wrapper instance
		$wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
		$wrapper_id = 'vbo-widget-visitscounter-' . $wrapper_instance;

		?>
		<div class="vbo-admin-widget-wrapper">
			<div class="vbo-admin-widget-head">
				<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
			</div>
			<div id="<?php echo $wrapper_id; ?>" class="vbo-widget-visitscounter-wrap">
				<div class="vbo-widget-visitscounter-number">
					<span class="vbo-widget-visitscounter-number-count" data-period="tot_today">0</span>
					<div class="vbo-widget-visitscounter-number-lbl"><?php echo JText::translate('VBO_W_VISITCOUNT_VTODAY'); ?></div>
				</div>
				<div class="vbo-widget-visitscounter-number">
					<span class="vbo-widget-visitscounter-number-count" data-period="tot_month">0</span>
					<div class="vbo-widget-visitscounter-number-lbl"><?php echo JText::translate('VBO_W_VISITCOUNT_VTMON'); ?></div>
				</div>
				<div class="vbo-widget-visitscounter-number">
					<span class="vbo-widget-visitscounter-number-count" data-period="tot_last_month">0</span>
					<div class="vbo-widget-visitscounter-number-lbl"><?php echo JText::translate('VBO_W_VISITCOUNT_VLMON'); ?></div>
				</div>
				<div class="vbo-widget-visitscounter-number">
					<span class="vbo-widget-visitscounter-number-count" data-period="pcent_month">0 %</span>
					<div class="vbo-widget-visitscounter-number-lbl"><?php echo JText::translate('VBO_W_VISITCOUNT_VDIFF'); ?></div>
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
			 * Calculates the proper duration of the animation given the steps.
			 * 
			 * @param 	int 	steps 	the number of steps to animate (target number).
			 * 
			 * @return 	int 	 		the suggested duration for the animation in ms.
			 */
			function vboWidgetVscCounterDuration(steps) {
				var min_duration = 500,
					max_duration = 10000,
					tms_per_step = 250;

				var duration = tms_per_step * steps;

				if (duration < min_duration) {
					return min_duration;
				}

				if (duration > max_duration) {
					return max_duration;
				}

				return duration;
			}
			
			/**
			 * Updates the counter(s) by making an AJAX request and starts their animation.
			 */
			function vboWidgetVscCountVisitors() {
				// the widget method to call
				var call_method = 'countVisitors';

				// make a silent request to count the visitors
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						tmpl: "component"
					},
					function(response) {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return;
							}

							// response must contain 4 values separated by ;
							var data_numbers = obj_res[call_method].split(';');
							if (data_numbers.length != 4) {
								return;
							}

							// compose stats vars
							var stat_vars = {
								tot_today: parseInt(data_numbers[0]),
								tot_month: parseInt(data_numbers[1]),
								tot_last_month: parseInt(data_numbers[2]),
								pcent_month: data_numbers[3]
							}
							
							// update all counter values (in case of multiple instances)
							jQuery('.vbo-widget-visitscounter-number-count').each(function() {
								var counter_type = jQuery(this).attr('data-period');
								if (!counter_type || !stat_vars.hasOwnProperty(counter_type)) {
									// continue as this property is not available
									return;
								}

								if (counter_type == 'pcent_month') {
									// this is not a real counter
									jQuery(this).text(stat_vars[counter_type] + ' %');
									// continue
									return;
								}

								var current_counter = parseInt(jQuery(this).text());
								if (current_counter >= stat_vars[counter_type]) {
									// do nothing if we do not have a higher counter value
									return;
								}

								// make sure the duration is valid for these steps
								var counter_duration = current_counter > 0 ? vboWidgetVscCounterDuration(stat_vars[counter_type] - current_counter) : vboWidgetVscCounterDuration(stat_vars[counter_type]);

								// set new counter value and property, then start counter animation
								jQuery(this).text(stat_vars[counter_type]).prop('Counter', current_counter).animate({
									Counter: jQuery(this).text()
								}, {
									duration: counter_duration,
									easing: 'swing',
									step: function (cur) {
										jQuery(this).text(Math.ceil(cur));
									}
								});
							});
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					function(error) {
						console.error(error);
						// make counter value empty
						jQuery('.vbo-widget-visitscounter-number-count').text('');
					}
				);
			}

			jQuery(document).ready(function() {
				// run the AJAX request when the page loads
				vboWidgetVscCountVisitors();

				// set an interval of 5 minutes for updating the counter value
				setInterval(vboWidgetVscCountVisitors, (1000 * 60 * 5));
			});
		</script>
			<?php
		}
	}
}

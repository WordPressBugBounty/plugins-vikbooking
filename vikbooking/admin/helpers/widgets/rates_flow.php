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
 * Class handler for admin widget "rates flow".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
class VikBookingAdminWidgetRatesFlow extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget. Since we do not load individual parameters
	 * for each widget's instance, we use a static counter to determine its settings.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * Default number of rates flow alterations per page.
	 * 
	 * @var 	int
	 */
	protected $alterations_per_page = 2;

	/**
	 * Suggested limit of records per page.
	 * 
	 * @var 	int
	 */
	protected $lim_page_records = 8;

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBOREPORTRATESFLOW');
		$this->widgetDescr = JText::translate('VBO_W_RATESFLOW_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('chart-line') . '"></i>';
		$this->widgetStyleName = 'violet';
	}

	/**
	 * Custom method for this widget only to load the rates flow records.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * It's the actual rendering of the widget which also allows navigation.
	 */
	public function loadRatesFlowRecords()
	{
		$offset = VikRequest::getInt('offset', 0, 'request');
		$length = VikRequest::getInt('length', $this->alterations_per_page, 'request');
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

		// get rates flow report instance
		$report = VikBooking::getReportInstance('rates_flow');
		if (!$report) {
			// do nothing
			return;
		}

		// get VBO date format
		$vbo_df = $report->getDateFormat();

		// set report options
		$report->setReportOptions(array(
			'fetch' 	=> 'alterations',
			'krsort' 	=> 'created_on',
			'krorder' 	=> 'DESC',
			'fromdate' 	=> $fromdate,
			'todate' 	=> $todate,
			'lim' 		=> $length,
			'limstart' 	=> $offset,
		));

		// generate report data
		$alterations = $report->getReportValues();
		$alterations = !is_array($alterations) ? array() : $alterations;

		// grab report columns for paging
		$columns = $report->getColumnsValues();

		// check next page and next offset
		$has_next_page = (isset($columns['paging']) && !empty($columns['paging']['has_next_page']));
		$next_offset   = isset($columns['paging']) && !empty($columns['paging']['limstart']) ? $columns['paging']['limstart'] : 0;
		$current_page  = isset($columns['paging']) && !empty($columns['paging']['page_num']) ? $columns['paging']['page_num'] : 1;

		// check festivities, by getting the next ones
		$fests = VikBooking::getFestivitiesInstance();
		$next_fests = $fests->loadFestDates($fromdate, $todate);

		// start output buffering
		ob_start();

		// error, warning or info messages should go on top
		if (strlen($report->getError())) {
			?>
			<p class="err"><?php echo $report->getError(); ?></p>
			<?php
		}
		if (strlen($report->getWarning())) {
			?>
			<p class="warn"><?php echo $report->getWarning(); ?></p>
			<?php
		}
		if (!count($alterations)) {
			?>
			<p class="info"><?php echo JText::translate('VBONORESULTS'); ?></p>
			<?php
		}

		// loop through all rates flow records
		foreach ($alterations as $rflow) {
			// attempt to get the festivity name for the updated dates
			$day_from_ts = strtotime($rflow['day_from']['value']);
			$day_to_ts 	 = strtotime($rflow['day_to']['value']);
			$fest_name 	 = $this->findDatesFestName($next_fests, $day_from_ts, $day_to_ts);

			$restr_infos = array();
			if (is_object($rflow['data']['value']) && isset($rflow['data']['value']->Restrictions)) {
				$restr_obj = $rflow['data']['value']->Restrictions;
				if (isset($restr_obj->minLOS) && (int)$restr_obj->minLOS > 1) {
					array_push($restr_infos, JText::translate('VBOMINIMUMSTAY') . ': ' . $restr_obj->minLOS);
				}
				if (isset($restr_obj->cta)) {
					if ((is_bool($restr_obj->cta) && $restr_obj->cta === true) || (is_string($restr_obj->cta) && !strcasecmp($restr_obj->cta, 'true'))) {
						array_push($restr_infos, 'CTA');
					}
				}
				if (isset($restr_obj->ctd)) {
					if ((is_bool($restr_obj->ctd) && $restr_obj->ctd === true) || (is_string($restr_obj->ctd) && !strcasecmp($restr_obj->ctd, 'true'))) {
						array_push($restr_infos, 'CTD');
					}
				}
			}

			?>
			<div class="vbo-widget-ratesflow-record" data-recordid="<?php echo $rflow['id']['value']; ?>">
				<div class="vbo-widget-ratesflow-avatar">
				<?php
				if (!empty($rflow['channel_id']['_logo'])) {
					// channel logo has got the highest priority
					?>
					<img class="vbo-widget-ratesflow-avatar-profile" src="<?php echo $rflow['channel_id']['_logo']; ?>" />
					<?php
				} else {
					// we use an icon as fallback
					VikBookingIcons::e('hotel', 'vbo-widget-ratesflow-avatar-icon');
				}
				?>
				</div>
				<div class="vbo-widget-ratesflow-content">
					<div class="vbo-widget-ratesflow-content-head">
						<div class="vbo-widget-ratesflow-content-info-details">
							<h4><?php echo $rflow['channel_id']['display_value']; ?></h4>
							<div class="vbo-widget-ratesflow-content-info-dates">
								<span class="vbo-widget-ratesflow-date-from"><?php echo $rflow['day_from']['display_value']; ?></span>
							<?php
							if ($rflow['day_from']['value'] != $rflow['day_to']['value']) {
								?>
								<span class="vbo-widget-ratesflow-date-sep">-</span>
								<span class="vbo-widget-ratesflow-date-to"><?php echo $rflow['day_to']['display_value']; ?></span>
								<?php
							}
							if (!empty($fest_name)) {
								?>
								<span class="vbo-widget-ratesflow-date-fest"><?php echo $fest_name; ?></span>
								<?php
							}
							?>
							</div>
						</div>
						<div class="vbo-widget-ratesflow-content-info-rates">
						<?php
						$higher_rates = null;
						$higher_class = '';
						$higher_icon  = 'equals';
						$higher_pcfee = 0;
						if (!empty($rflow['base_fee']) && !empty($rflow['base_fee']['value'])) {
							if ($rflow['base_fee']['value'] < $rflow['nightly_fee']['value']) {
								$higher_rates = true;
								$higher_class = ' vbo-widget-ratesflow-rate-changes-higher';
								$higher_icon  = 'sort-up';
								$higher_pcfee = round((($rflow['nightly_fee']['value'] * 100 / $rflow['base_fee']['value']) - 100), 2);
							} elseif ($rflow['base_fee']['value'] > $rflow['nightly_fee']['value']) {
								$higher_rates = false;
								$higher_class = ' vbo-widget-ratesflow-rate-changes-lower';
								$higher_icon  = 'sort-down';
								$higher_pcfee = round((($rflow['base_fee']['value'] * 100 / $rflow['nightly_fee']['value']) - 100), 2);
							}
						}
						?>
							<div class="vbo-widget-ratesflow-nightly-rate">
								<span><?php echo $rflow['nightly_fee']['display_value']; ?></span>
							</div>
							<div class="vbo-widget-ratesflow-rate-changes<?php echo $higher_class; ?>">
							<?php
							if (!empty($rflow['channel_alter']['value'])) {
								?>
								<div class="vbo-widget-ratesflow-rate-alter">
									<span><?php echo $rflow['channel_alter']['display_value']; ?></span>
								</div>
								<?php
							}
							?>
								<div class="vbo-widget-ratesflow-rate-growth">
									<span><?php VikBookingIcons::e($higher_icon); ?> <?php echo $higher_pcfee; ?>%</span>
								</div>
							</div>
						</div>
					</div>
					<div class="vbo-widget-ratesflow-content-info-msg">
						<span class="vbo-widget-ratesflow-content-info-msg-room"><?php echo $rflow['vbo_room_id']['display_value']; ?></span>
					<?php
					if (count($restr_infos)) {
						?>
						<span class="vbo-widget-ratesflow-content-info-msg-restr"><?php echo implode(', ', $restr_infos); ?></span>
						<?php
					}
					?>
						<span class="vbo-widget-ratesflow-content-info-msg-cdate"><?php echo $rflow['created_on']['display_value']; ?></span>
					</div>
				</div>
			</div>
			<?php
		}

		// append navigation
		?>
		<div class="vbo-widget-commands vbo-widget-commands-right">
			<div class="vbo-widget-commands-main">
			<?php
			if ($offset > 0) {
				// show backward navigation button
				?>
				<div class="vbo-widget-command-chevron vbo-widget-command-prev">
					<span class="vbo-widget-command-chevron-prev" onclick="vboWidgetRatesFlowNavigate('<?php echo $wrapper; ?>', -1);"><?php VikBookingIcons::e('chevron-left'); ?></span>
				</div>
				<?php
			}
			if ($has_next_page) {
				// show forward navigation button
				?>
				<div class="vbo-widget-command-chevron vbo-widget-command-next">
					<span class="vbo-widget-command-chevron-next" onclick="vboWidgetRatesFlowNavigate('<?php echo $wrapper; ?>', 1);"><?php VikBookingIcons::e('chevron-right'); ?></span>
				</div>
			<?php
			}
			?>
			</div>
		</div>
		<?php

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// take care of the readable dates interval
		list($format_from_date, $format_to_date) = $this->parseReadableDateInterval($from_ts, $to_ts, $vbo_df, $step);

		// return an associative array of values
		return array(
			'html' 		  => $html_content,
			'fromdate' 	  => $fromdate,
			'f_fromdate'  => $format_from_date,
			'todate' 	  => $todate,
			'f_todate' 	  => $format_to_date,
			'step' 		  => $step,
			'step_name'   => $step_name,
			'tot_records' => count($alterations),
			'next_page'   => (int)$has_next_page,
			'page_num' 	  => (int)$current_page,
			'offset' 	  => $next_offset,
		);
	}

	/**
	 * Preload the necessary CSS/JS assets.
	 * 
	 * @return 	void
	 */
	public function preload()
	{
		// load assets
		$this->vbo_app->loadDatePicker();
	}

	/**
	 * Main method to invoke the widget. Contents will be loaded
	 * through AJAX requests, not via PHP when the page loads.
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
		$wrapper_id = 'vbo-widget-ratesflow-' . $wrapper_instance;

		// get permissions
		$vbo_auth_pricing = JFactory::getUser()->authorise('core.vbo.pricing', 'com_vikbooking');

		// get rates flow report instance
		$report = VikBooking::getReportInstance('rates_flow');
		if (!$report || !$vbo_auth_pricing) {
			// display nothing
			return;
		}

		// get the total number of unique channels updated
		$tot_channels_updated = $report->countRatesFlowChannels();
		if ($tot_channels_updated > 0 && ($this->alterations_per_page * $tot_channels_updated) < $this->lim_page_records) {
			$best_lim_page = floor($this->lim_page_records / $tot_channels_updated);
			$this->alterations_per_page = $best_lim_page > $this->alterations_per_page ? $best_lim_page : $this->alterations_per_page;
		}

		// count average records per page
		$avg_per_page = floor(($this->alterations_per_page + $this->lim_page_records) / 2);
		$avg_per_page = $avg_per_page < $this->alterations_per_page ? $this->alterations_per_page : $avg_per_page;

		// get minimum and maximum nights updated for dates filters
		list($mindate, $maxdate) = $report->getMinDatesRatesFlow();
		$mindate = empty($mindate) ? time() : $mindate;
		$maxdate = empty($maxdate) ? $mindate : $maxdate;

		// dates navigation preference
		$cookie = JFactory::getApplication()->input->cookie;
		$cookie_step = $cookie->get('vbo_widget_ratesflow_step', 'quarter', 'string');

		// determine default period name and dates
		$df = $report->getDateFormat();
		$dtpicker_df = $report->getDateFormat('jui');
		$now_info = getdate();
		if ($cookie_step == 'weekend') {
			// next weekend
			$from_ts = strtotime("next Friday");
			$to_ts   = strtotime("next Sunday");
			if ($to_ts < $from_ts) {
				// this weekend
				$from_ts = strtotime("last Friday");
			}
			$fromdate = date($df, $from_ts);
			$todate   = date($df, $to_ts);
			$period_name = JText::translate('VBOWEEKND');
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

		// take care of the readable dates interval
		list($format_from_date, $format_to_date) = $this->parseReadableDateInterval($from_ts, $to_ts, $df, $cookie_step);
		$interval_name = $format_from_date . ' - ' . $format_to_date;
		if ($format_from_date == $format_to_date) {
			$interval_name = $format_from_date;
		}

		?>
		<div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" data-instance="<?php echo $wrapper_instance; ?>" data-offset="0" data-nowoffset="0" data-prevoffset="-1" data-length="<?php echo $this->alterations_per_page; ?>">
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
									<span class="vbo-widget-ratesflow-dt-prev" onclick="vboWidgetRatesFlowDatesNav('<?php echo $wrapper_id; ?>', -1);"><?php VikBookingIcons::e('chevron-left'); ?></span>
								</div>
								<div class="vbo-reportwidget-command-chevron vbo-reportwidget-command-next">
									<span class="vbo-widget-ratesflow-dt-next" onclick="vboWidgetRatesFlowDatesNav('<?php echo $wrapper_id; ?>', 1);"><?php VikBookingIcons::e('chevron-right'); ?></span>
								</div>
							</div>
							<div class="vbo-reportwidget-command-dots">
								<span class="vbo-widget-command-togglefilters vbo-widget-ratesflow-togglefilters" onclick="vboWidgetRatesFlowToggleFilters('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
							</div>
						</div>
						<div class="vbo-reportwidget-filters">
							<div class="vbo-reportwidget-filter">
								<span class="vbo-reportwidget-datepicker">
									<?php VikBookingIcons::e('calendar', 'vbo-widget-ratesflow-caltrigger'); ?>
									<input type="text" class="vbo-ratesflow-dtpicker-from" value="<?php echo $fromdate; ?>" />
								</span>
							</div>
							<div class="vbo-reportwidget-filter">
								<span class="vbo-reportwidget-datepicker">
									<?php VikBookingIcons::e('calendar', 'vbo-widget-ratesflow-caltrigger'); ?>
									<input type="text" class="vbo-ratesflow-dtpicker-to" value="<?php echo $todate; ?>" />
								</span>
							</div>
							<div class="vbo-reportwidget-filter">
								<select class="vbo-ratesflow-period-nav" onchange="vboWidgetRatesFlowChangePeriod(this.value);">
									<option value="weekend"<?php echo $cookie_step == 'weekend' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOWEEKND'); ?></option>
									<option value="month"<?php echo $cookie_step == 'month' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPVIEWRESTRICTIONSTWO'); ?></option>
									<option value="quarter"<?php echo $cookie_step == 'quarter' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_QUARTER'); ?></option>
								</select>
							</div>
							<div class="vbo-reportwidget-filter vbo-reportwidget-filter-confirm">
								<button type="button" class="btn vbo-config-btn" onclick="vboWidgetRatesFlowChangeDates('<?php echo $wrapper_id; ?>');"><?php echo JText::translate('VBADMINNOTESUPD'); ?></button>
							</div>
						</div>

					</div>
				</div>
			</div>
			<div class="vbo-widget-ratesflow-wrap">
				<div class="vbo-widget-ratesflow-inner">
					<div class="vbo-widget-ratesflow-list">
					<?php
					for ($i = 0; $i < $avg_per_page; $i++) {
						?>
						<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">
							<div class="vbo-dashboard-guest-activity-avatar">
								<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>
							</div>
							<div class="vbo-dashboard-guest-activity-content">
								<div class="vbo-dashboard-guest-activity-content-head">
									<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>
								</div>
								<div class="vbo-dashboard-guest-activity-content-info-msg">
									<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>
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
			function vboWidgetRatesFlowSkeletons(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}
				widget_instance.find('.vbo-widget-ratesflow-list').html('');
				for (var i = 0; i < <?php echo $avg_per_page; ?>; i++) {
					var skeleton = '';
					skeleton += '<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">';
					skeleton += '	<div class="vbo-dashboard-guest-activity-avatar">';
					skeleton += '		<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>';
					skeleton += '	</div>';
					skeleton += '	<div class="vbo-dashboard-guest-activity-content">';
					skeleton += '		<div class="vbo-dashboard-guest-activity-content-head">';
					skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>';
					skeleton += '		</div>';
					skeleton += '		<div class="vbo-dashboard-guest-activity-content-info-msg">';
					skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>';
					skeleton += '		</div>';
					skeleton += '	</div>';
					skeleton += '</div>';
					// append skeleton
					jQuery(skeleton).appendTo(widget_instance.find('.vbo-widget-ratesflow-list'));
				}
			}

			/**
			 * Perform the request to load the rates flow records.
			 */
			function vboWidgetRatesFlowLoad(wrapper, dates_direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// check if a navigation of dates was requested (0 = no dates nav)
				if (typeof dates_direction === 'undefined') {
					dates_direction = 0;
				}

				// get vars for making the request
				var current_offset  = parseInt(widget_instance.attr('data-offset'));
				var length_per_page = parseInt(widget_instance.attr('data-length'));
				var from_date 		= widget_instance.find('.vbo-ratesflow-dtpicker-from').val();
				var to_date 		= widget_instance.find('.vbo-ratesflow-dtpicker-to').val();
				var dates_step 		= widget_instance.find('.vbo-ratesflow-period-nav').val();

				// the widget method to call
				var call_method = 'loadRatesFlowRecords';

				// make a request to load the rates flow records
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						offset: current_offset,
						length: length_per_page,
						fromdate: from_date,
						todate: to_date,
						step: dates_step,
						date_dir: dates_direction,
						wrapper: wrapper,
						tmpl: "component"
					},
					function(response) {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// adjust current offset for backward navigation
							if (obj_res[call_method]['page_num'] && obj_res[call_method]['page_num'] > 1) {
								widget_instance.attr('data-nowoffset', current_offset);
							} else {
								// first page
								widget_instance.attr('data-nowoffset', '0');
							}

							// replace HTML with new rates flow records
							widget_instance.find('.vbo-widget-ratesflow-list').html(obj_res[call_method]['html']);
							
							// check results
							var tot_records = obj_res[call_method]['tot_records'] || 0;
							var new_offset  = obj_res[call_method]['offset'] || 0;
							if (tot_records) {
								// set new offset for pagination next nav
								widget_instance.attr('data-offset', new_offset);
							}

							if (dates_direction > 0 || dates_direction < 0) {
								// set new dates calculated
								try {
									// construct a solid date object without using the raw Y-m-d string as only argument
									var dfrom_parts = obj_res[call_method]['fromdate'].split('-');
									var dfrom_obj 	= new Date(parseInt(dfrom_parts[0]), (parseInt(dfrom_parts[1]) - 1), parseInt(dfrom_parts[2]), 0, 0, 0);
									widget_instance.find('.vbo-ratesflow-dtpicker-from').datepicker('setDate', dfrom_obj);

									// restore the original minimum and maximum dates for the "to date" calendar to avoid issues setting a date
									widget_instance.find('.vbo-ratesflow-dtpicker-to').datepicker('option', {
										minDate: "<?php echo date($df, $mindate); ?>",
										maxDate: "<?php echo date($df, $maxdate); ?>",
									});

									// do the same for the to date by using a precise date object instance
									var dto_parts 	= obj_res[call_method]['todate'].split('-');
									var dto_obj 	= new Date(parseInt(dto_parts[0]), (parseInt(dto_parts[1]) - 1), parseInt(dto_parts[2]), 0, 0, 0);
									widget_instance.find('.vbo-ratesflow-dtpicker-to').datepicker('setDate', dto_obj);
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

							if (!isNaN(tot_records) && parseInt(tot_records) < 1) {
								// no results can indicate the offset is invalid or too high
								if (!isNaN(new_offset) && parseInt(new_offset) > 0) {
									// reset offset to 0
									widget_instance.attr('data-offset', 0);
									// show loading skeletons
									vboWidgetRatesFlowSkeletons(wrapper);
									// reload the first page
									vboWidgetRatesFlowLoad(wrapper);
								}
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					function(error) {
						// remove the skeleton loading
						widget_instance.find('.vbo-widget-ratesflow-list').find('.vbo-dashboard-guest-activity-skeleton').remove();
						console.error(error);
					}
				);
			}

			/**
			 * Navigate between the various pages of the rates flow records.
			 */
			function vboWidgetRatesFlowNavigate(wrapper, direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show loading skeletons
				vboWidgetRatesFlowSkeletons(wrapper);

				var instance_val = widget_instance.attr('data-instance');

				// check direction and update offsets for nav
				if (direction > 0) {
					// navigate forward
					var current_offset = widget_instance.attr('data-nowoffset');
					widget_instance.attr('data-prevoffset', current_offset);
					// push history
					try {
						vbo_w_ratesflow_history[instance_val].unshift(current_offset);
					} catch(e) {
						// do nothing
					}
				} else {
					// navigate backward
					var prev_offset = widget_instance.attr('data-prevoffset');
					// check and update history
					try {
						var last_offset = vbo_w_ratesflow_history[instance_val].shift();
						if (typeof last_offset !== 'undefined') {
							widget_instance.attr('data-prevoffset', last_offset);
							prev_offset = last_offset;
						}
					} catch(e) {
						// do nothing
					}
					prev_offset = prev_offset >= 0 ? prev_offset : 0;
					widget_instance.attr('data-offset', prev_offset);
				}

				// launch navigation
				vboWidgetRatesFlowLoad(wrapper);
			}

			/**
			 * Navigate between the date steps and load the rates flow records.
			 */
			function vboWidgetRatesFlowDatesNav(wrapper, direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show loading skeletons
				vboWidgetRatesFlowSkeletons(wrapper);

				// reset offsets to the first state
				widget_instance.attr('data-offset', '0');
				widget_instance.attr('data-nowoffset', '0');
				widget_instance.attr('data-prevoffset', '-1');

				// launch dates navigation and load records
				vboWidgetRatesFlowLoad(wrapper, direction);
			}

			/**
			 * Load rates flow records for the selected dates.
			 */
			function vboWidgetRatesFlowChangeDates(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// hide filters when making a new request
				widget_instance.find('.vbo-reportwidget-filters').hide();

				// show loading skeletons
				vboWidgetRatesFlowSkeletons(wrapper);

				// reset offsets to the first state
				widget_instance.attr('data-offset', '0');
				widget_instance.attr('data-nowoffset', '0');
				widget_instance.attr('data-prevoffset', '-1');

				// load data
				vboWidgetRatesFlowLoad(wrapper);
			}

			/**
			 * Toggle dates and step filters.
			 */
			function vboWidgetRatesFlowToggleFilters(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				widget_instance.find('.vbo-reportwidget-filters').toggle();
			}

			/**
			 * Datepicker dates selection.
			 */
			function vboWidgetRatesFlowCheckDates(selectedDate, inst) {
				if (selectedDate === null || inst === null) {
					return;
				}
				var cur_from_date = jQuery(this).val();
				if (jQuery(this).hasClass("vbo-ratesflow-dtpicker-from") && cur_from_date.length) {
					var nowstart = jQuery(this).datepicker("getDate");
					var nowstartdate = new Date(nowstart.getTime());
					jQuery(".vbo-ratesflow-dtpicker-to").datepicker("option", {minDate: nowstartdate});
				}
			}

			/**
			 * Navigation period cookie onchange.
			 */
			function vboWidgetRatesFlowChangePeriod(period) {
				// update cookie for the step selected
				var nd = new Date();
				nd.setTime(nd.getTime() + (365*24*60*60*1000));
				document.cookie = "vbo_widget_ratesflow_step=" + period + "; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
			}
			
		</script>
			<?php
		}
		?>

		<script type="text/javascript">

			// declare widgets object history array
			if (typeof vbo_w_ratesflow_history === 'undefined') {
				vbo_w_ratesflow_history = {
					"<?php echo $wrapper_instance; ?>": []
				};
			}

			jQuery(document).ready(function() {

				// render datepicker calendars for dates navigation
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-ratesflow-dtpicker-from, .vbo-ratesflow-dtpicker-to').datepicker({
					minDate: "<?php echo date($df, $mindate); ?>",
					maxDate: "<?php echo date($df, $maxdate); ?>",
					yearRange: "<?php echo date('Y', $mindate); ?>:<?php echo date('Y', $maxdate); ?>",
					changeMonth: true,
					changeYear: true,
					dateFormat: "<?php echo $dtpicker_df; ?>",
					onSelect: vboWidgetRatesFlowCheckDates
				});

				// triggering for datepicker calendar icons
				jQuery('i.vbo-widget-ratesflow-caltrigger').click(function() {
					var jdp = jQuery(this).parent().find('input.hasDatepicker');
					if (jdp.length) {
						jdp.focus();
					}
				});

				// when document is ready, load rates flow records for this widget's instance
				vboWidgetRatesFlowLoad('<?php echo $wrapper_id; ?>');

			});
			
		</script>

		<?php
	}

	/**
	 * Checks if the given range of dates belong to a festivity.
	 * Internal method for this widget only.
	 * 
	 * @param 	array 	$next_fests		the list of festivities to parse.
	 * @param 	int 	$day_from_ts 	from date timestamp.
	 * @param 	int 	$day_to_ts 		to date timestamp.
	 * 
	 * @return 	mixed 					string fest name on success, or false.
	 */
	protected function findDatesFestName($next_fests, $day_from_ts, $day_to_ts)
	{
		if (!is_array($next_fests) || empty($day_from_ts) || empty($day_to_ts)) {
			return false;
		}

		// we also support closest matches
		$closest_matches = array();

		foreach ($next_fests as $fest_data) {
			if (empty($fest_data['festinfo'])) {
				continue;
			}
			$fest = $fest_data['festinfo'][0];
			$earliest_ts = 0;
			$latest_ts 	 = 0;

			if ($fest->from_ts <= $day_from_ts && $fest->to_ts >= $day_to_ts) {
				// fest found wrapping all dates updated
				return $fest->trans_name;
			}

			if (isset($fest->all_timestamps) && count($fest->all_timestamps) > 1) {
				// check bridge-dates
				$earliest_ts = $fest->all_timestamps[0];
				$latest_ts 	 = $fest->all_timestamps[(count($fest->all_timestamps) - 1)] + 86399;
				if ($earliest_ts <= $day_from_ts && $latest_ts >= $day_to_ts) {
					// fest found with bridge-dates wrapping all dates updated
					return $fest->trans_name;
				}
			}

			// check if at least one date updated in the interval is matching
			$days_diff = floor(($day_to_ts - $day_from_ts) / 86400);
			$days_to_start = null;
			if ($fest->from_ts <= $day_from_ts && $fest->to_ts >= $day_from_ts) {
				// this is a closest match, because the first interval day updated is a fest
				$days_to_start = floor(($day_to_ts - $fest->to_ts) / 86400);
			} elseif ($fest->from_ts <= $day_to_ts && $fest->to_ts >= $day_to_ts) {
				// this is a closest match, because the last interval day updated is a fest
				$days_to_start = floor(($fest->from_ts - $day_from_ts) / 86400);
			} elseif ($earliest_ts > 0 && $earliest_ts <= $day_from_ts && $latest_ts >= $day_from_ts) {
				// this is a closest match, because the first interval day updated is a fest-bridge
				$days_to_start = floor(($day_to_ts - $latest_ts) / 86400);
			} elseif ($earliest_ts > 0 && $earliest_ts <= $day_to_ts && $latest_ts >= $day_to_ts) {
				// this is a closest match, because the last interval day updated is a fest-bridge
				$days_to_start = floor(($earliest_ts - $day_from_ts) / 86400);
			}
			if ($days_to_start !== null && abs($days_to_start) <= 4 && $days_diff < 15) {
				// push close match
				array_push($closest_matches, array(
					'fest_name'  => $fest->trans_name,
					'days_diff'  => $days_diff,
					'days_start' => abs($days_to_start),
				));
			}
		}

		// count closest matches, if any
		$tot_matches = count($closest_matches);
		if ($tot_matches === 1) {
			// return the first closest match
			return $closest_matches[0]['fest_name'];
		}
		if ($tot_matches > 1) {
			// sort closest matches
			usort($closest_matches, function($a, $b) {
				// sort by days diff ASC
				$sort_diff = ($a['days_diff'] < $b['days_diff']) ? -1 : 1;
				// sort by days to start ASC
				$sort_diff += ($a['days_start'] < $b['days_start']) ? -1 : 1;
				return $sort_diff;
			});
			// return the best close match
			return $closest_matches[0]['fest_name'];
		}

		return false;
	}

	/**
	 * Calculates the new dates interval for navigation according to step.
	 * Internal method for this widget only.
	 * 
	 * @param 	int 	$from_ts 	current from date timestamp.
	 * @param 	int 	$to_ts 		current end date timestamp.
	 * @param 	string 	$step		the navigation step to take (weekend, month or quarter).
	 * @param 	int 	$date_dir 	less than 0 backward nav, more than 0 forward.
	 * 
	 * @return 	array 				the new from and end date timestamps, plus the step name.
	 */
	protected function calcDatesNavigation($from_ts, $to_ts, $step, $date_dir)
	{
		if (empty($from_ts) || (!($date_dir > 0) && !($date_dir < 0))) {
			return array($from_ts, $to_ts, '');
		}

		// start date information
		$from_info = getdate($from_ts);

		// next or prev weekend
		if ($step == 'weekend') {
			if ($date_dir > 0) {
				// forward dates navigation
				$from_ts = strtotime("next Friday", $from_info[0]);
				$to_ts   = strtotime("next Sunday", $from_info[0]);
				if ($to_ts < $from_ts) {
					// week after
					$to_ts = strtotime("+1 week", $to_ts);
				}
			} elseif ($date_dir < 0) {
				// backward dates navigation
				$from_ts = strtotime("last Friday", $from_info[0]);
				$to_ts   = strtotime("last Sunday", $from_info[0]);
				if ($to_ts < $from_ts) {
					// week before
					$from_ts = strtotime("-1 week", $from_ts);
				}
			}
			return array($from_ts, $to_ts, JText::translate('VBOWEEKND'));
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
			return array($from_ts, $to_ts, JText::translate('VBPVIEWRESTRICTIONSTWO'));
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
		return array($from_ts, $to_ts, JText::translate('VBO_QUARTER'));
	}

	/**
	 * Parse an interval of date timestamps into a readable interval of dates according to step.
	 * Internal method for this widget only.
	 * 
	 * @param 	int 	$from_ts 	current from date timestamp.
	 * @param 	int 	$to_ts 		current end date timestamp.
	 * @param 	string 	$vbo_df 	the date format in VBO.
	 * @param 	string 	$step		the navigation step to take (weekend, month or quarter).
	 * 
	 * @return 	array 				the formatted from and to date interval strings.
	 */
	protected function parseReadableDateInterval($from_ts, $to_ts, $vbo_df, $step)
	{
		$from_info = getdate($from_ts);
		$to_info   = getdate($to_ts);
		$format_from_date = date($vbo_df, $from_ts);
		$format_to_date   = date($vbo_df, $to_ts);
		if ($step == 'weekend') {
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

		return array($format_from_date, $format_to_date);
	}
}

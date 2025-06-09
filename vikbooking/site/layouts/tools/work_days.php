<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Obtain vars from arguments received in the layout file.
 * This is the layout file for the "work_days" operator tool.
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

// load assets
$vbo_app->loadDatePicker();
$vbo_app->loadSelect2();

$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$usedf = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$usedf = 'm/d/Y';
} else {
	$usedf = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();

// get the current operator permissions
$perm_work_days_exceptions = (bool) $permissions->get('work_days_exceptions', 0);

if ($app->input->getBool('update_operator', false) && JSession::checkToken()) {
	// safely update values upon form submit
	$dbo = JFactory::getDbo();

	// normalize to linear array
	$work_days_week = (array) $app->input->get('work_days_week', [], 'array');
	$work_days_week_schedule = array_combine(array_keys($work_days_week), array_values($work_days_week));
	$work_days_week = [];
	foreach ($work_days_week_schedule as $wday => $whours) {
		$work_days_week[] = [
			'wday'  => $wday,
			'hours' => $whours,
		];
	}

	// build operator update values
	$update = [
		'id' => $operator['id'],
		'work_days_week' => json_encode($work_days_week),
	];

	if ($perm_work_days_exceptions) {
		// normalize to linear array
		$work_days_exceptions = (array) $app->input->get('work_days_exceptions', [], 'array');
		foreach ($work_days_exceptions as &$wexceptions) {
			if (is_scalar($wexceptions)) {
				$wexceptions = json_decode($wexceptions, true);
			}
		}
		unset($wexceptions);

		// set operator update value
		$update['work_days_exceptions'] = json_encode($work_days_exceptions);
	}

	$update = (object) $update;
	$dbo->updateObject('#__vikbooking_operators', $update, 'id');

	$app->enqueueMessage(JText::translate('VBOSUBMITPRECHECKINTNKS'), 'success');
	$app->redirect($tool_uri);
	$app->close();
}

?>

<form action="<?php echo $tool_uri; ?>" method="POST">
	<div class="vbo-site-container vbo-site-container-full vbo-site-container-compact">
		<div class="vbo-params-wrap">
			<div class="vbo-params-container">
				<div class="vbo-params-block vbo-params-block-full-setting">

					<div class="vbo-param-container">
						<div class="vbo-param-setting">
							<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_WORK_DAYS_WEEK_HELP'); ?></span>
						</div>
					</div>

				<?php
				// manage work days week schedules
				$work_days_list = [1, 2, 3, 4, 5, 6, 0];
				foreach ($work_days_list as $w) {
					?>
					<div class="vbo-param-container">
						<div class="vbo-param-label"><?php echo VikBooking::sayWeekDay($w); ?></div>
						<div class="vbo-param-setting">
							<select name="work_days_week[<?php echo $w; ?>]" id="vbo-mngoper-work-days-week-<?php echo $w; ?>">
								<option value="0"><?php echo JText::translate('VBO_DAY_OFF'); ?></option>
								<optgroup label="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_WORKING_HOURS')); ?>">
								<?php
								for ($h = 1; $h <= 24; $h++) {
									$is_selected = false;
									foreach (($operator['work_days_week'] ?? []) as $wday_data) {
										if ($wday_data['wday'] == $w) {
											$is_selected = $is_selected || (($wday_data['hours'] ?? 0) == $h);
										}
									}
									?>
									<option value="<?php echo $h; ?>"<?php echo $is_selected ? ' selected="selected"' : ''; ?>><?php echo sprintf('%d %s', $h, ($h > 1 ? JText::translate('VBO_HOURS') : JText::translate('VBO_HOUR'))); ?></option>
									<?php
								}
								?>
								</optgroup>
							</select>
						</div>
					</div>
					<?php
				}

				if ($perm_work_days_exceptions) {
					// manage the work days exceptions
					?>
					<div class="vbo-param-container">
						<div class="vbo-param-setting">
							<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_WORK_DAYS_EXCEPTIONS_HELP'); ?></span>
						</div>
					</div>

					<div class="vbo-param-container">
						<div class="vbo-param-label"><?php echo JText::translate('VBO_EXCEPTIONS'); ?></div>
						<div class="vbo-param-setting">
							<div class="btn-toolbar vbo-mng-oper-work-dates">
								<div class="btn-group pull-left">
									<?php echo $vbo_app->getCalendar('', 'wdexceptfrom', 'wdexceptfrom', '%Y-%m-%d', array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true', 'placeholder' => JText::translate('VBCONFIGCLOSINGDATEFROM'))); ?>
								</div>
								<div class="btn-group pull-left">
									<?php echo $vbo_app->getCalendar('', 'wdexceptto', 'wdexceptto', '%Y-%m-%d', array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true', 'placeholder' => JText::translate('VBCONFIGCLOSINGDATETO'))); ?>
								</div>
								<div class="btn-group pull-left">
									<select id="wdexcepthours">
										<option value="0"><?php echo JText::translate('VBO_DAY_OFF'); ?></option>
										<optgroup label="<?php echo $this->escape(JText::translate('VBO_WORKING_HOURS')); ?>">
										<?php
										for ($h = 1; $h <= 24; $h++) {
											?>
											<option value="<?php echo $h; ?>"><?php echo sprintf('%d %s', $h, ($h > 1 ? JText::translate('VBO_HOURS') : JText::translate('VBO_HOUR'))); ?></option>
											<?php
										}
										?>
										</optgroup>
									</select>
								</div>
								<div class="btn-group pull-left">
									<button type="button" class="btn" onclick="vboMngOperAddDaysException();"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBCONFIGCLOSINGDATEADD'); ?></button>
								</div>
							</div>
							<div id="vbo-mngoper-work-days-exceptions" style="display: block;">
							<?php
							$active_wday_exceptions = $operator['work_days_exceptions'] ?? [];
							foreach ($active_wday_exceptions as $day_exception) {
								$day_exception_signature = implode('::', [($day_exception['from'] ?? ''), ($day_exception['to'] ?? '')]);
								?>
								<input type="hidden" name="work_days_exceptions[]" value="<?php echo JHtml::fetch('esc_attr', json_encode($day_exception)); ?>" />
								<div class="vbo-closed-date-entry<?php echo !$day_exception['hours'] ? ' vbo-closed-date-entry-dayoff' : ''; ?>" data-date-period="<?php echo JHtml::fetch('esc_attr', $day_exception_signature); ?>">
									<span><?php echo date(str_replace("/", $datesep, $usedf), strtotime($day_exception['from'])); ?></span>
								<?php
								if (!empty($day_exception['to']) && $day_exception['from'] != $day_exception['to']) {
									?>
									<span> - </span>
									<span><?php echo date(str_replace("/", $datesep, $usedf), strtotime($day_exception['to'])); ?></span>
									<?php
								}
								?>
									<span> - </span>
									<span><?php echo !($day_exception['hours'] ?? 0) ? JText::translate('VBO_DAY_OFF') : sprintf('%d %s', $day_exception['hours'], ($day_exception['hours'] > 1 ? JText::translate('VBCONFIGONETENEIGHT') : JText::translate('VBO_HOUR'))); ?></span>
									<span class="vbo-closed-date-rm" onclick="vboMngOperRemoveDaysException('<?php echo JHtml::fetch('esc_attr', $day_exception_signature); ?>');"><?php VikBookingIcons::e('times'); ?></span>
								</div>
								<?php
							}
							?>
							</div>
						</div>
					</div>
					<?php
				}
				?>

					<div class="vbo-param-container">
						<div class="vbo-param-setting">
							<?php echo JHtml::fetch('form.token'); ?>
							<input type="hidden" name="update_operator" value="1" />
							<button type="submit" class="btn vbo-pref-color-btn"><?php echo JText::translate('VBSAVE'); ?></button>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
</form>

<script type="text/javascript">
	function vboMngOperAddDaysException() {
		let from = document.querySelector('#wdexceptfrom');
		let to = document.querySelector('#wdexceptto');
		let hours = document.querySelector('#wdexcepthours');
		let interval = [];

		if (!from || !from.value) {
			return false;
		}

		interval.push(from.value);

		if (to && to.value && new Date(from.value) < new Date(to.value)) {
			interval.push(to.value);
		} else {
			interval.push(from.value);
		}

		let hidden_inp = document.createElement('input');
		hidden_inp.setAttribute('type', 'hidden');
		hidden_inp.setAttribute('name', 'work_days_exceptions[]');
		hidden_inp.value = JSON.stringify({
			from: interval[0],
			to: interval[1],
			hours: parseInt(hours.value),
		});

		let interval_elem = document.createElement('div');
		interval_elem.classList.add('vbo-closed-date-entry');
		if (hours.value == 0) {
			interval_elem.classList.add('vbo-closed-date-entry-dayoff');
		}
		interval_elem.setAttribute('data-date-period', interval.join('::'));

		let interval_from = document.createElement('span');
		interval_from.innerText = interval[0];
		interval_elem.append(interval_from);

		let interval_sep = document.createElement('span');
		interval_sep.innerText = ' - ';

		if (interval[0] != interval[1]) {
			interval_elem.append(interval_sep);

			let interval_to = document.createElement('span');
			interval_to.innerText = interval[1];
			interval_elem.append(interval_to);
		}

		let interval_sep_clone = interval_sep.cloneNode(true);
		interval_elem.append(interval_sep_clone);

		let interval_hours = document.createElement('span');
		interval_hours.innerText = hours.value > 0 ? hours.value + ' ' + (hours.value > 1 ? <?php echo json_encode(JText::translate('VBO_HOURS')); ?> : <?php echo json_encode(JText::translate('VBO_HOUR')); ?>) : <?php echo json_encode(JText::translate('VBO_DAY_OFF')); ?>;
		interval_elem.append(interval_hours);

		let interval_delete = document.createElement('span');
		interval_delete.classList.add('vbo-closed-date-rm');
		interval_delete.innerHTML = '<?php VikBookingIcons::e('times'); ?>';
		interval_delete.addEventListener('click', () => {
			vboMngOperRemoveDaysException(interval.join('::'));
		});
		interval_elem.append(interval_delete);

		let container = document.querySelector('#vbo-mngoper-work-days-exceptions');

		container.append(hidden_inp);
		container.append(interval_elem);

		from.value = '';
		to.value = '';
		hours.value = 0;
	}

	function vboMngOperRemoveDaysException(interval) {
		document.querySelectorAll('.vbo-closed-date-entry[data-date-period="' + interval + '"]').forEach((container) => {
			let hidden_inp = container.previousElementSibling;
			if (hidden_inp && hidden_inp.matches('input[name="work_days_exceptions[]"]')) {
				hidden_inp.remove();
			}
			container.remove();
		});
	}
</script>

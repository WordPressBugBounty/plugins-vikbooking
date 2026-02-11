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

$rows = $this->rows;
$arrbusy = $this->arrbusy;
$wmonthsel = $this->wmonthsel;
$tsstart = $this->tsstart;
$lim0 = $this->lim0;
$navbut = $this->navbut;

$app = JFactory::getApplication();
$vbo_app = VikBooking::getVboApplication();

// load context menu assets
$vbo_app->loadContextMenuAssets();

// prepare JS currency
$vbo_app->prepareJavaScriptCurrency();

$vbo_auth_pricing = JFactory::getUser()->authorise('core.vbo.pricing', 'com_vikbooking');

JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.min.js');
JHtml::fetch('script', VBO_ADMIN_URI.'resources/js_upload/jquery.stickytableheaders.min.js');

$file_base = VBOPlatformDetection::isWordPress() ? 'admin.php' : 'index.php';

// JS lang defs
JText::script('VBSAVE');
JText::script('VBPVIEWORDERSTHREE');
JText::script('VBEDITORDERTHREE');
JText::script('VBDAYS');
JText::script('VBDAY');
JText::script('VBMAILADULTS');
JText::script('VBMAILADULT');
JText::script('VBMAILCHILDREN');
JText::script('VBMAILCHILD');
JText::script('VBO_MISSING_SUBUNIT');
JText::script('VBO_SHOW_CANCELLATIONS');
JText::script('VBO_DRAG_SUBUNITS_SAMEDATE');
JText::script('VBPVIEWORDERCHANNEL');
JText::script('VBANNULLA');
JText::script('VIKLOADING');
JText::script('VBOVWDNDERRNOTENCELLS');
JText::script('VBOVWDNDMOVINGBID');
JText::script('VBOVWDNDMOVINGROOM');
JText::script('VBOVWDNDMOVINGDATES');
JText::script('VBEDITORDERROOMSNUM');
JText::script('VBEDITORDERADULTS');
JText::script('VBPICKUPAT');
JText::script('VBRELEASEAT');
JText::script('VBEDITORDERCHILDREN');
JText::script('VBEDITORDERNINE');
JText::script('VBPEDITBUSYTOTPAID');
JText::script('VBOFEATASSIGNUNIT');
JText::script('VBO_SPLIT_STAY');
JText::script('VBO_CONF_SWAP_RNUMB');
JText::script('VBO_ERR_SUBUN_MOVE_SUBUN');
JText::script('VBO_BTYPE_OVERBOOKING');

$days_labels = [
	JText::translate('VBSUN'),
	JText::translate('VBMON'),
	JText::translate('VBTUE'),
	JText::translate('VBWED'),
	JText::translate('VBTHU'),
	JText::translate('VBFRI'),
	JText::translate('VBSAT')
];

$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$currencysymb = VikBooking::getCurrencySymb();

$pdebug = VikRequest::getInt('e4j_debug', '', 'request');
$session = JFactory::getSession();
$show_type = $session->get('vbUnitsShowType', '');
$mnum = $session->get('vbOvwMnum', '1');
$mnum = intval($mnum);
$pcategory_id = $session->get('vbOvwCatid', 0);
$cookie = $app->input->cookie;
$cookie_uleft = $cookie->get('vboAovwUleft', '', 'string');
$cookie_sticky_heads = $cookie->get('vboAovwStheads', 'off', 'string');
$table_scroll_layout = $cookie->get('vboAovwScroll', (count($rows) > 3 ? 'inline' : 'tables'), 'string');
$colortags = VikBooking::loadBookingsColorTags();
$long_list = (count($rows) * $mnum) > 10;

// View mode - Classic or Tags
$pbmode = $session->get('vbTagsMode', 'classic');
$tags_view_supported = false;

// Room Units Distinctive Features
$rooms_features_map = [];
$rooms_features_bookings = [];
$rooms_bids_pools = [];
$room_bookings_pool = [];
$bids_checkins = [];
$index_loop = 0;
foreach ($rows as $kr => $room) {
	if ($room['units'] <= 1) {
		$tags_view_supported = true;
	} else {
		if (!empty($room['params']) && $room['units'] <= 150) {
			//sub-room units only if room type has 150 units at most
			$room_params = json_decode($room['params'], true);
			if (is_array($room_params) && isset($room_params['features']) && is_array($room_params['features'])) {
				$rooms_features_map[$room['id']] = [];
				foreach ($room_params['features'] as $rind => $rfeatures) {
					foreach ($rfeatures as $fname => $fval) {
						if (strlen($fval)) {
							// $rooms_features_map[$room['id']][$rind] = '#'.$rind.' - '.JText::translate($fname).': '.$fval;
							$rooms_features_map[$room['id']][$rind] = "#{$rind} - {$fval}";
							break;
						}
					}
				}
				if (!$rooms_features_map[$room['id']]) {
					unset($rooms_features_map[$room['id']]);
				} else {
					foreach ($rooms_features_map[$room['id']] as $rind => $indexdata) {
						$clone_room = $room;
						$clone_room['unit_index'] = (int)$rind;
						$clone_room['unit_index_str'] = $indexdata;
						array_splice($rows, ($kr + 1 + $index_loop), 0, [$clone_room]);
						$index_loop++;
					}

					/**
					 * Multi-unit room types with enough sub-units can display a "landing-unit-row" for
					 * temporarily "parking" the bookings through drag & drop for an easier re-allocation.
					 * Beside the holding area, we also add a row for the unassigned room bookings.
					 * 
					 * @since 	1.18.2 (J) - 1.8.2 (WP)
					 * @since 	1.18.3 (J) - 1.8.3 (WP) 2 sub-units are sufficient.
					 */
					if ($room['units'] >= 2) {
						// set holding area row
						$clone_room['unit_index'] = 0;
						$clone_room['unit_index_str'] = JText::translate('VBO_HOLDING_AREA');
						array_splice($rows, ($kr + 1 + $index_loop), 0, [$clone_room]);
						$index_loop++;

						// set unassigned row
						$clone_room['unit_index'] = -1;
						$clone_room['unit_index_str'] = JText::translate('VBO_MISSING_SUBUNIT');
						array_splice($rows, ($kr + 1 + $index_loop), 0, [$clone_room]);
						$index_loop++;
					}
				}
			}
		}
	}
}

if (!$tags_view_supported) {
	$pbmode = 'classic';
}

// locked (stand-by) records
$arrlocked = [];
if (array_key_exists('tmplock', $arrbusy)) {
	if (count($arrbusy['tmplock']) > 0) {
		$arrlocked = $arrbusy['tmplock'];
	}
	unset($arrbusy['tmplock']);
}

/**
 * Global closing dates.
 * 
 * @since 	1.17.1 (J) - 1.7.1 (WP)
 */
$globally_closed = VikBooking::getClosingDates();

?>
<script type="text/Javascript">
function vboUnitsLeftOrBooked() {
	let set_to = jQuery('#uleftorbooked').val();
	jQuery('.vbo-overv-avcell[data-units-booked]').each(function() {
		let counter_el = jQuery(this).find('a');
		if (!counter_el.length) {
			return;
		}
		counter_el.first().text(jQuery(this).attr('data-'+set_to));
	});
	let nd = new Date();
	nd.setTime(nd.getTime() + (365*24*60*60*1000));
	document.cookie = "vboAovwUleft="+set_to+"; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
}
if (typeof jQuery.fn.tooltip === 'function') {
	jQuery(".hasTooltip").tooltip();
} else {
	jQuery.fn.tooltip = function(){};
}
var vboFests = <?php echo json_encode($this->festivities); ?>;
var vboRdayNotes = <?php echo json_encode($this->rdaynotes); ?>;
</script>
<form action="index.php?option=com_vikbooking&amp;task=overv" method="post" name="vboverview" class="vbo-avov-form">
	<div class="btn-toolbar vbo-avov-toolbar" id="filter-bar" style="width: 100%; display: inline-block;">
		<div class="btn-group pull-left">
			<?php echo $wmonthsel; ?>
		</div>
		<div class="btn-group pull-left">
			<select name="mnum" onchange="document.vboverview.submit();">
			<?php
			for($i = 1; $i <= 18; $i++) {
				?>
				<option value="<?php echo $i; ?>"<?php echo $i == $mnum ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOVWNUMMONTHS').' '.$i; ?></option>
				<?php
			}
			?>
			</select>
		</div>
	<?php
	if (count($this->categories)) {
		?>
		<div class="btn-group pull-left">
			<select name="category_id" onchange="document.vboverview.submit();">
				<option value="0">- <?php echo JText::translate('VBPVIEWROOMTWO'); ?> -</option>
			<?php
			foreach ($this->categories as $catid => $catname) {
				?>
				<option value="<?php echo $catid; ?>"<?php echo $catid == $pcategory_id ? ' selected="selected"' : ''; ?>><?php echo $catname; ?></option>
				<?php
			}
			?>
			</select>
		</div>
		<?php
	}

	$stickyheaders_cmd_on = '';
	$stickyheaders_cmd_off = '';
	if ($long_list) {
		if (VBOPlatformDetection::isJoomla()) {
			/**
			 * @joomlaonly 	fixed offset selector is ".navbar"
			 */
			$stickyheaders_cmd_on = "jQuery('table.vboverviewtable').stickyTableHeaders({cacheHeaderHeight: true, fixedOffset: (jQuery('.navbar').length ? jQuery('.navbar') : jQuery('#subhead-container'))});";
		} else {
			/**
			 * @wponly 	fixed offset selector is "#wpadminbar"
			 */
			$stickyheaders_cmd_on = "jQuery('table.vboverviewtable').stickyTableHeaders({cacheHeaderHeight: true, fixedOffset: jQuery('#wpadminbar')});";
		}
		$stickyheaders_cmd_off = "jQuery('table.vboverviewtable').stickyTableHeaders('destroy'); jQuery('th.bluedays').attr('style', '');";
	}
	?>
		<script type="text/javascript">
			function vboToggleStickyTableHeaders(val) {
				let sticky_heads_cval = 'off';
				if (val > 0) {
					sticky_heads_cval = 'on';
					jQuery('table.vboverviewtable').addClass('vbo-overv-sticky-table-head-on');
					jQuery('table.vboverviewtable').removeClass('vbo-overv-sticky-table-head-off');
					<?php echo $stickyheaders_cmd_on; ?>
					// stop listening to the document scroll event to handle the fixed table heads
					document.removeEventListener('scroll', vboOvervHandleVScrollTableHeads);
					// scan all tables for listening to their horizontal scrolling event
					let tables = document.querySelectorAll('table.vboverviewtable.vbo-overv-sticky-table-head-off');
					tables.forEach((table) => {
						// stop listening to the table horizontal scroll events
						table.parentNode.removeEventListener('scroll', vboOvervHandleHScrollTableHead);
					});
				} else {
					jQuery('table.vboverviewtable').addClass('vbo-overv-sticky-table-head-off');
					jQuery('table.vboverviewtable').removeClass('vbo-overv-sticky-table-head-on');
					<?php echo $stickyheaders_cmd_off; ?>
					// listen to the document scroll event to handle the fixed table heads
					document.addEventListener('scroll', vboOvervHandleVScrollTableHeads);
					// scan all tables for listening to their horizontal scrolling event
					let tables = document.querySelectorAll('table.vboverviewtable.vbo-overv-sticky-table-head-off');
					tables.forEach((table) => {
						table.parentNode.addEventListener('scroll', vboOvervHandleHScrollTableHead);
					});
				}
				let nd = new Date();
				nd.setTime(nd.getTime() + (365*24*60*60*1000));
				document.cookie = "vboAovwStheads=" + sticky_heads_cval + "; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
			}
		</script>
	<?php
	if ($long_list || $tags_view_supported === true) {
		// group into an apposite context menu the layout and view types
		?>
		<script type="text/javascript">
			const vboOvervLayoutBtns = [];
		<?php
		if ($long_list) {
			// push buttons to choose between the scroll or table layout
			?>
			vboOvervLayoutBtns.push({
				class: 'btngroup',
				text: <?php echo json_encode(JText::translate('VBO_LAYOUT')); ?>,
				disabled: true,
			});
			vboOvervLayoutBtns.push({
				class: 'vbo-context-menu-entry-secondary',
				text: <?php echo json_encode(JText::translate('VBO_INLINE_SCROLL')); ?>,
				icon: '<?php echo $cookie_sticky_heads == 'off' && $table_scroll_layout == 'inline' ? VikBookingIcons::i('check-square') : VikBookingIcons::i('far fa-square'); ?>',
				action: (root, event) => {
					let nd = new Date();
					nd.setTime(nd.getTime() + (365*24*60*60*1000));
					document.cookie = "vboAovwScroll=inline; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
					vboToggleStickyTableHeaders(0);
					document.vboverview.submit();
				},
			});
			vboOvervLayoutBtns.push({
				class: 'vbo-context-menu-entry-secondary',
				text: <?php echo json_encode(JText::translate('VBO_SCROLL')); ?>,
				icon: '<?php echo $cookie_sticky_heads == 'off' && $table_scroll_layout != 'inline' ? VikBookingIcons::i('check-square') : VikBookingIcons::i('far fa-square'); ?>',
				action: (root, event) => {
					let nd = new Date();
					nd.setTime(nd.getTime() + (365*24*60*60*1000));
					document.cookie = "vboAovwScroll=tables; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
					vboToggleStickyTableHeaders(0);
					document.vboverview.submit();
				},
			});
			vboOvervLayoutBtns.push({
				class: 'vbo-context-menu-entry-secondary',
				text: <?php echo json_encode(JText::translate('VBO_TABLE')); ?>,
				icon: '<?php echo empty($cookie_sticky_heads) || $cookie_sticky_heads == 'on' ? VikBookingIcons::i('check-square') : VikBookingIcons::i('far fa-square'); ?>',
				action: (root, event) => {
					vboToggleStickyTableHeaders(1);
					document.vboverview.submit();
				},
				separator: <?php echo $tags_view_supported === true ? 'true' : 'false'; ?>,
			});
			<?php
		}
		if ($tags_view_supported === true) {
			// push buttons to choose between classic or tags view
			?>
			vboOvervLayoutBtns.push({
				class: 'btngroup',
				text: <?php echo json_encode(JText::translate('VBMENUTHREE')); ?>,
				disabled: true,
			});
			vboOvervLayoutBtns.push({
				class: 'vbo-context-menu-entry-secondary',
				text: <?php echo json_encode(JText::translate('VBOAVOVWBMODECLASSIC')); ?>,
				icon: '<?php echo $pbmode != 'tags' ? VikBookingIcons::i('check-square') : VikBookingIcons::i('far fa-square'); ?>',
				action: (root, event) => {
					let inp = document.createElement('input');
					inp.setAttribute('type', 'hidden');
					inp.setAttribute('name', 'bmode');
					inp.value = 'classic';
					document.vboverview.append(inp);
					document.vboverview.submit();
				},
			});
			vboOvervLayoutBtns.push({
				class: 'vbo-context-menu-entry-secondary',
				text: <?php echo json_encode(JText::translate('VBOAVOVWBMODETAGS')); ?>,
				icon: '<?php echo $pbmode == 'tags' ? VikBookingIcons::i('check-square') : VikBookingIcons::i('far fa-square'); ?>',
				action: (root, event) => {
					let inp = document.createElement('input');
					inp.setAttribute('type', 'hidden');
					inp.setAttribute('name', 'bmode');
					inp.value = 'tags';
					document.vboverview.append(inp);
					document.vboverview.submit();
				},
			});
			<?php
		}
		?>

			jQuery(function() {

				// define the context menu for the actions button
		        jQuery('.vbo-context-menu-overview-layout').vboContextMenu({
		            placement: 'bottom-left',
		            buttons: vboOvervLayoutBtns,
		        });

			});
		</script>

		<div class="btn-group pull-left">
			<button type="button" class="btn vbo-context-menu-btn vbo-context-menu-btn-raw vbo-context-menu-overview-layout">
				<span class="vbo-context-menu-lbl"><?php echo JText::translate('VBO_LAYOUT'); ?></span>
				<span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
			</button>
		</div>
		<?php
	}

	?>
		<div class="btn-group pull-left">
		<?php
		// display overview context-menu
		echo JLayoutHelper::render('overview.actions', ['caller' => 'overv', 'rooms' => $rows]);
		?>
		</div>
		<div class="btn-group pull-right">
			<select name="units_show_type" id="uleftorbooked" onchange="vboUnitsLeftOrBooked();"><option value="units-booked"<?php echo (!empty($cookie_uleft) && $cookie_uleft == 'units-booked' ? ' selected="selected"' : ''); ?>><?php echo JText::translate('VBOVERVIEWUBOOKEDFILT'); ?></option><option value="units-left"<?php echo $show_type == 'units-left' || (!empty($cookie_uleft) && $cookie_uleft == 'units-left') ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOVERVIEWULEFTFILT'); ?></option></select>
		</div>
		<div class="btn-group pull-right vbo-avov-legend">
			<!-- <span class="vbo-overview-legend-init"><?php echo JText::translate('VBOVERVIEWLEGEND'); ?></span> -->
			<div class="vbo-overview-legend-red">
				<span class="vbo-overview-legend-box">&nbsp;</span>
				<span class="vbo-overview-legend-title"><?php echo JText::translate('VBOVERVIEWLEGRED'); ?></span>
			</div>
			<div class="vbo-overview-legend-yellow">
				<span class="vbo-overview-legend-box">&nbsp;</span>
				<span class="vbo-overview-legend-title"><?php echo JText::translate('VBOVERVIEWLEGYELLOW'); ?></span>
			</div>
			<div class="vbo-overview-legend-green">
				<span class="vbo-overview-legend-box">&nbsp;</span>
				<span class="vbo-overview-legend-title"><?php echo JText::translate('VBOVERVIEWLEGGREEN'); ?></span>
			</div>
			<div class="vbo-overview-legend-green vbo-overview-legend-dnd">
				<span class="vbo-overview-legend-box"><i class="vboicn-enlarge" style="margin: 1px; display: block; text-align: center;"></i></span>
				<span class="vbo-overview-legend-title"><?php echo JText::translate('VBOVERVIEWLEGDND'); ?></span>
			</div>
		</div>
	</div>
<?php
// propagate the current task manager filters
foreach ((array) $app->input->get('tmfilters', [], 'array') as $tm_filter_key => $tm_filter_val) {
	if (is_scalar($tm_filter_val)) {
		?>
	<input type="hidden" name="tmfilters[<?php echo $tm_filter_key; ?>]" value="<?php echo JHtml::fetch('esc_attr', $tm_filter_val); ?>" />
		<?php
	} elseif (is_array($tm_filter_val) && $tm_filter_val && array_keys($tm_filter_val) == range(0, count($tm_filter_val) - 1)) {
		// add support for linear arrays
		foreach ($tm_filter_val as $tm_filter_val_sub) {
			?>
	<input type="hidden" name="tmfilters[<?php echo $tm_filter_key; ?>][]" value="<?php echo JHtml::fetch('esc_attr', $tm_filter_val_sub); ?>" />
			<?php
		}
	}
}
?>
</form>

<?php
$todayymd = date('Y-m-d');
$last_displayed_day_ts = strtotime(date('Y-m-t 23:59:59', strtotime(sprintf('+%d %s', $mnum - 1, (($mnum - 1) === 1 ? 'month' : 'months')), $tsstart)));
$last_displayed_day_info = getdate($last_displayed_day_ts);
$nowts = getdate($tsstart);
$curts = $nowts;
for ($mind = 1; $mind <= ($table_scroll_layout === 'inline' ? 1 : $mnum); $mind++) {
	$monthname = VikBooking::sayMonth($curts['mon']);
	$month_names_cache = [
		$curts['mon'] => $monthname,
	];
	$displayMonthLabel = sprintf('%s %d', $monthname, $curts['year']);
	if ($table_scroll_layout === 'inline') {
		$startMonthName = VikBooking::sayMonth($nowts['mon'], true);
		$endMonthName = VikBooking::sayMonth($last_displayed_day_info['mon'], true);
		if ($nowts['year'] != $last_displayed_day_info['year']) {
			$displayMonthLabel = sprintf('%s %d - %s %d', $startMonthName, $nowts['year'], $endMonthName, $last_displayed_day_info['year']);
		} else {
			if ($mnum > 1) {
				$displayMonthLabel = sprintf('%s - %s %d', $startMonthName, $endMonthName, $last_displayed_day_info['year']);
			} else {
				$displayMonthLabel = sprintf('%s %d', $monthname, $last_displayed_day_info['year']);
			}
		}
	}
	?>
<div class="vbo-overv-montable-wrap">
	<div class="vbo-table-responsive">
		<table class="vboverviewtable vbo-roverview-table vbo-table <?php echo $cookie_sticky_heads == 'off' ? 'vbo-overv-sticky-table-head-off' : 'vbo-overv-sticky-table-head-on'; ?>" data-table-index="<?php echo ($mind - 1); ?>" data-month-from="<?php echo date('Y-m-01', $curts[0]); ?>" data-month-to="<?php echo $table_scroll_layout === 'inline' ? date('Y-m-d', $last_displayed_day_ts) : date('Y-m-t', $curts[0]); ?>">
			<thead>
				<tr class="vboverviewtablerowone">
					<th class="bluedays skip-bluedays-click vbo-overview-month" data-initial-period="<?php echo JHtml::fetch('esc_attr', $displayMonthLabel); ?>"><?php echo $displayMonthLabel; ?></th>
				<?php
				$moncurts = $curts;
				$mon = $moncurts['mon'];
				// loop until the end of the range of dates, or until the end of the current month
				while (($table_scroll_layout === 'inline' && $moncurts[0] < $last_displayed_day_ts) || ($table_scroll_layout !== 'inline' && $moncurts['mon'] == $mon)) {
					$curdayymd = date('Y-m-d', $moncurts[0]);
					if (!($month_names_cache[$moncurts['mon']] ?? null)) {
						$month_names_cache[$moncurts['mon']] = VikBooking::sayMonth($moncurts['mon']);
					}
					$read_day  = $days_labels[$moncurts['wday']] . ' ' . $moncurts['mday'] . ' ' . $month_names_cache[$moncurts['mon']] . ' ' . $curts['year'];
					$displayDayLabel = $days_labels[$moncurts['wday']];
					$headCellClasses = [
						'bluedays',
						($todayymd == $curdayymd ? 'vbo-overv-todaycell' : null),
						(isset($this->festivities[$curdayymd]) ? 'vbo-overv-festcell' : null),
					];
					if ($moncurts['mday'] == 1 && $table_scroll_layout === 'inline') {
						$displayDayLabel = sprintf('%s, %s', VikBooking::sayMonth($moncurts['mon'], true), $days_labels[$moncurts['wday']]);
						if ($nowts['mon'] != $moncurts['mon']) {
							$headCellClasses[] = 'vbo-overv-cell-newmonth';
						}
					}
					?>
					<th class="<?php echo implode(' ', array_filter($headCellClasses)); ?>" data-ymd="<?php echo $curdayymd; ?>" data-readymd="<?php echo $read_day; ?>">
						<span class="vbo-overw-tablewday"><?php echo $displayDayLabel; ?></span>
						<span class="vbo-overw-tablemday"><?php echo $moncurts['mday']; ?></span>
					</th>
					<?php
					// parse the next day
					$moncurts = getdate(mktime(0, 0, 0, $moncurts['mon'], ($moncurts['mday'] + 1), $moncurts['year']));
				}
				?>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($rows as $room) {
				$moncurts = $curts;
				$mon = $moncurts['mon'];
				$room_tags_view = $pbmode == 'tags' && $tags_view_supported === true && $room['units'] <= 1 ? true : false;
				$is_subunit = array_key_exists('unit_index', $room);
				$is_subunit_tmp_row = $is_subunit && empty($room['unit_index']);
				$is_subunit_unassigned_row = $is_subunit && ($room['unit_index'] ?? 0) == -1;

				// build the list of classes for the current room-row
				$row_classes = [
					'vboverviewtablerow',
					($is_subunit ? 'vboverviewtablerow-subunit' : ''),
					($is_subunit_tmp_row ? 'vboverviewtablerow-subunit-tmp' : ''),
					($is_subunit_unassigned_row ? 'vboverviewtablerow-subunit-unassigned' : ''),
					(($is_subunit || $room['units'] == 1) && $cookie_sticky_heads == 'off' ? 'vbo-overv-row-snake' : ''),
				];

				// build the main cell icon identifier
				$main_cell_icn_id = 'bed';
				if ($is_subunit_tmp_row) {
					$main_cell_icn_id = 'parking';
				} elseif ($is_subunit_unassigned_row) {
					$main_cell_icn_id = 'exclamation-triangle';
				}

				?>
				<tr class="<?php echo implode(' ', array_filter($row_classes)); ?>"<?php echo !$is_subunit ? ' data-roomid="' . $room['id'] . '"' : ''; ?><?php echo $is_subunit ? ' data-subroomid="' . $room['id'] . '-' . $room['unit_index'] . '"' : ''; ?><?php echo $is_subunit_tmp_row ? ' style="display: none;"' : ''; ?>>
				<?php
				if ($is_subunit) {
					?>
					<td class="roomname subroomname" data-roomid="<?php echo '-' . $room['id']; ?>">
						<span class="vbo-overview-subroomunits"><?php VikBookingIcons::e($main_cell_icn_id); ?></span>
						<span class="vbo-overview-subroomname"><?php echo $room['unit_index_str']; ?></span>
					</td>
					<?php
				} else {
					?>
					<td class="roomname" data-roomid="<?php echo $room['id']; ?>" data-units="<?php echo $room['units']; ?>">
						<span class="vbo-overview-room-info">
							<span class="vbo-overview-roomunits"><?php echo $room['units']; ?></span>
							<span class="vbo-overview-roomname"><?php echo $room['name']; ?></span>
						<?php
						if (isset($rooms_features_map[$room['id']])) {
							?>
							<span class="vbo-overview-subroom-toggle"><i class="<?php echo VikBookingIcons::i('chevron-down', 'hasTooltip'); ?>" style="margin: 0;" title="<?php echo JHtml::fetch('esc_attr', JText::translate('VBOVERVIEWTOGGLESUBROOM')); ?>"></i></span>
							<?php
						}
						?>
						</span>
					</td>
					<?php
				}

				// loop until the end of the range of dates, or until the end of the current month
				$room_bids_pool = [];
				$room_bookings = [];
				while (($table_scroll_layout === 'inline' && $moncurts[0] < $last_displayed_day_ts) || ($table_scroll_layout !== 'inline' && $moncurts['mon'] == $mon)) {
					// init cell values
					$dclass = 'vbo-grid-avcell vbo-overv-avcell ' . (!$is_subunit ? "notbusy" : "subnotbusy");
					$is_checkin = false;
					$is_sharedcal = false;
					$is_closure = false;
					$lastbidcheckout = null;
					$dalt = "";
					$bid = "";
					$bids_pool = [];
					$totfound = 0;
					$prev_day_key = date('Y-m-d', strtotime('-1 day', $moncurts[0]));
					$cur_day_key = date('Y-m-d', $moncurts[0]);

					// check for global closing dates
					foreach ($globally_closed as $glob_closed) {
						if ($moncurts[0] >= $glob_closed['from'] && $moncurts[0] <= $glob_closed['to']) {
							$dclass .= ' vbo-overv-globally-closed';
						}
					}

					// check availability
					if (!empty($arrbusy[$room['id']]) && !$is_subunit) {
						foreach ($arrbusy[$room['id']] as $b) {
							$tmpone = getdate($b['checkin']);
							$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
							$tmptwo = getdate($b['checkout']);
							$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
							if (!($moncurts[0] >= $ritts && $moncurts[0] < $conts)) {
								// booking does not involve the current day
								continue;
							}
							$dclass = "vbo-grid-avcell vbo-overv-avcell busy";
							$bid = $b['idorder'];
							$is_sharedcal = !empty($b['sharedcal']) ? true : $is_sharedcal;
							$is_closure = !empty($b['closure']) ? true : $is_closure;
							$bid_str = '-' . $bid . '-';
							if (!in_array($bid_str, $bids_pool)) {
								$bids_pool[] = $bid_str;
							}
							if (isset($rooms_features_map[$room['id']])) {
								// multi-unit room with distinctive features defined
								if (!isset($room_bids_pool[$cur_day_key])) {
									$room_bids_pool[$cur_day_key] = [];
									$room_bookings[$cur_day_key]  = [];
								}
								$room_bids_pool[$cur_day_key][] = (int)$bid;
								$room_bookings[$cur_day_key][]  = $b;
							} elseif ($room['units'] == 1) {
								// single-unit room
								if (!isset($room_bookings[$cur_day_key])) {
									$room_bookings[$cur_day_key] = [];
								}
								$room_bookings[$cur_day_key][] = $b;
							}
							if ($moncurts[0] == $ritts) {
								$dalt = JText::translate('VBPICKUPAT')." ".date('H:i', $b['checkin']);
								$is_checkin = true;
								$lastbidcheckout = $b['checkout'];
								$bids_checkins[$bid] = $bids_checkins[$bid] ?? [];
								$bids_checkins[$bid][] = $cur_day_key;
							} elseif ($moncurts[0] == $conts) {
								$dalt = JText::translate('VBRELEASEAT')." ".date('H:i', $b['checkout']);
							}
							$totfound++;
						}
					}

					// locked (stand-by) records
					if ($room_tags_view === true && isset($arrlocked[$room['id']]) && $arrlocked[$room['id']] && !$is_subunit) {
						foreach ($arrlocked[$room['id']] as $l) {
							$tmpone = getdate($l['checkin']);
							$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
							$tmptwo = getdate($l['checkout']);
							$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
							if ($moncurts[0] >= $ritts && $moncurts[0] < $conts) {
								$dclass = strpos($dclass, "notbusy") !== false ? "busytmplock" : "busy busytmplock";
								$bid = $l['idorder'];
								if (!in_array($bid, $bids_pool)) {
									if (count($bids_pool) > 0) {
										array_unshift($bids_pool, '-'.$bid.'-');
									} else {
										$bids_pool[] = '-'.$bid.'-';
									}
								}
								if ($moncurts[0] == $ritts) {
									$dalt = JText::translate('VBPICKUPAT')." ".date('H:i', $l['checkin']);
								} elseif ($moncurts[0] == $conts) {
									$dalt = JText::translate('VBRELEASEAT')." ".date('H:i', $l['checkout']);
								}
								$totfound++;
							}
						}
					}

					// handle single-unit room reservations
					if ($room['units'] == 1 && !isset($rooms_features_map[$room['id']]) && isset($room_bookings[$cur_day_key]) && $room_bookings[$cur_day_key]) {
						// set the first (and only) booking
						$day_booking = [];
						foreach ($room_bookings[$cur_day_key] as $day_res) {
							if (!$day_res['closure'] && !$day_res['sharedcal']) {
								$day_booking = $day_res;
								break;
							}
						}
						if ($day_booking) {
							if (!isset($room_bookings_pool[$room['id']])) {
								$room_bookings_pool[$room['id']] = [];
							}
							if (!isset($room_bookings_pool[$room['id']][$cur_day_key])) {
								$room_bookings_pool[$room['id']][$cur_day_key] = [];
							}
							$room_bookings_pool[$room['id']][$cur_day_key][0] = $day_booking;
						}
					}

					$useday = $moncurts['mday'] < 10 ? "0{$moncurts['mday']}" : $moncurts['mday'];
					$dclass .= $totfound < $room['units'] && $totfound > 0 ? ' vbo-partially' : '';
					$dclass .= $is_sharedcal ? ' busy-sharedcalendar' : '';
					$dclass .= $is_closure ? ' busy-closure' : '';
					$dstyle = '';
					$astyle = '';
					if ($room_tags_view === true && $totfound > 0) {
						$last_bid = intval(str_replace('-', '', $bids_pool[(count($bids_pool) - 1)]));
						$binfo = VikBooking::getBookingInfoFromID($last_bid);
						if ($binfo) {
							$bcolortag = VikBooking::applyBookingColorTag($binfo);
							if ($bcolortag) {
								$bcolortag['name'] = JText::translate($bcolortag['name']);
								$dstyle = " style=\"background-color: ".$bcolortag['color']."; color: ".(array_key_exists('fontcolor', $bcolortag) ? $bcolortag['fontcolor'] : '#ffffff').";\" data-lastbid=\"".$last_bid."\"";
								$astyle = " style=\"color: ".(array_key_exists('fontcolor', $bcolortag) ? $bcolortag['fontcolor'] : '#ffffff').";\"";
								$dclass .= ' vbo-hascolortag';
							}
						}
					}
					if ($is_subunit && isset($rooms_bids_pools[$room['id']][$cur_day_key]) && isset($rooms_features_bookings[$room['id']][$room['unit_index']]) && isset($room_bookings_pool[$room['id']][$cur_day_key][$room['unit_index']])) {
						foreach ($rooms_bids_pools[$room['id']][$cur_day_key] as $bid) {
							$bid = intval(str_replace('-', '', $bid));
							if (in_array($bid, $rooms_features_bookings[$room['id']][$room['unit_index']])) {
								$room['units'] = 1;
								$totfound = 1;
								$dclass = "vbo-grid-avcell vbo-overv-avcell subroom-busy";
								$is_checkin = isset($bids_checkins[$bid]) && in_array($cur_day_key, $bids_checkins[$bid]) ? true : $is_checkin;
								if (count($room_bookings_pool[$room['id']][$cur_day_key]) > 1 && !empty($room_bookings_pool[$room['id']][$cur_day_key][$room['unit_index']]['checkin'])) {
									// ensure multi-room bookings display the proper check-in day in case of different nights of stay
									$is_checkin = date('Y-m-d', $room_bookings_pool[$room['id']][$cur_day_key][$room['unit_index']]['checkin']) == $cur_day_key;
								}

								/**
								 * In case of mixed room-types (hotels inventory and listings), we allow
								 * the tags to be applied on sub-units for multi-unit room types.
								 * 
								 * @since 	1.17.5 (J) - 1.7.5 (WP)
								 */
								if ($tags_view_supported && $binfo = VikBooking::getBookingInfoFromID($bid)) {
									$bcolortag = VikBooking::applyBookingColorTag($binfo);
									if ($bcolortag) {
										$bcolortag['name'] = JText::translate($bcolortag['name']);
										$dstyle = " style=\"background-color: ".$bcolortag['color']."; color: ".(array_key_exists('fontcolor', $bcolortag) ? $bcolortag['fontcolor'] : '#ffffff').";\" data-lastbid=\"".$bid."\"";
										$astyle = " style=\"color: ".(array_key_exists('fontcolor', $bcolortag) ? $bcolortag['fontcolor'] : '#ffffff').";\"";
										$dclass .= ' vbo-hascolortag';
									}
								}

								// abort
								break;
							}
						}
					}
					$write_units = $show_type == 'units-left' || (!empty($cookie_uleft) && $cookie_uleft == 'units-left') ? ($room['units'] - $totfound) : $totfound;
					// check today's date
					$curdayymd = date('Y-m-d', $moncurts[0]);
					if ($todayymd == $curdayymd) {
						$dclass .= ' vbo-overv-todaycell';
					}
					if (isset($this->festivities[$curdayymd])) {
						$dclass .= ' vbo-overv-festcell';
					}

					/**
					 * Critical dates defined at room-day level.
					 * 
					 * @since 	1.13.5 (J) - 1.3.5 (WP)
					 */
					$rdaynote_keyid = $cur_day_key . '_' . $room['id'] . '_' . (isset($room['unit_index']) ? $room['unit_index'] : '0');
					if (isset($this->rdaynotes[$rdaynote_keyid])) {
						// note exists for this combination of date, room ID and subunit
						$dclass .= ' vbo-roomdaynote-full';
						$rdaynote_icn = 'sticky-note';
					} else {
						// no notes for this cell
						$dclass .= ' vbo-roomdaynote-empty';
						$rdaynote_icn = 'far fa-sticky-note';
					}
					$critical_note = '<span class="vbo-roomdaynote-trigger" data-roomday="' . $rdaynote_keyid . '"><i class="' . VikBookingIcons::i($rdaynote_icn, 'vbo-roomdaynote-display') . '"></i></span>';
					if ($is_subunit_tmp_row || $is_subunit_unassigned_row) {
						$critical_note = '';
					}

					/**
					 * Closures overlapping one real reservation for a single-unit room should still
					 * allow the display of the booking snake if it's just one real reservation.
					 */
					$closure_on_real_res = false;
					if ($totfound === 2 && !$is_subunit && $room['units'] == 1 && $cookie_sticky_heads == 'off' && strpos($dclass, "busy-closure") !== false) {
						if (isset($room_bookings_pool[$room['id']]) && isset($room_bookings_pool[$room['id']][$cur_day_key]) && $room_bookings_pool[$room['id']][$cur_day_key]) {
							if (isset($room_bookings_pool[$room['id']][$cur_day_key][0]) && !$room_bookings_pool[$room['id']][$cur_day_key][0]['closure']) {
								// found a real booking on a date with at least one closure
								$closure_on_real_res = true;
							}
						}
					}

					// prepare cell content
					$day_has_closure = false;
					if ($totfound === 1 || $closure_on_real_res) {
						// start booking snake content
						$day_booking_snake = '';
						// collect the current day booking data
						$day_booking_data = [];
						if (isset($room_bookings_pool[$room['id']][$cur_day_key]) && $room_bookings_pool[$room['id']][$cur_day_key]) {
							if ($is_subunit && isset($room_bookings_pool[$room['id']][$cur_day_key][$room['unit_index']])) {
								$day_booking_data = $room_bookings_pool[$room['id']][$cur_day_key][$room['unit_index']];
							} elseif (!$is_subunit && isset($room_bookings_pool[$room['id']][$cur_day_key][0])) {
								$day_booking_data = $room_bookings_pool[$room['id']][$cur_day_key][0];
							}
						}

						// whether we need to prepend a check-out snake for the last reservation
						if (isset($room_bookings_pool[$room['id']][$prev_day_key]) && $room_bookings_pool[$room['id']][$prev_day_key]) {
							if ($is_subunit && isset($room_bookings_pool[$room['id']][$prev_day_key][$room['unit_index']])) {
								if (date('Y-m-d', $room_bookings_pool[$room['id']][$prev_day_key][$room['unit_index']]['checkout']) == $cur_day_key) {
									// prepend checkout snake
									$day_booking_snake .= '<div class="vbo-tableaux-booking vbo-tableaux-booking-singleunit vbo-tableaux-booking-checkout"><span>&nbsp;</span></div>';
								}
							} elseif (!$is_subunit && $room['units'] == 1 && isset($room_bookings_pool[$room['id']][$prev_day_key][0])) {
								if (date('Y-m-d', $room_bookings_pool[$room['id']][$prev_day_key][0]['checkout']) == $cur_day_key) {
									// prepend checkout snake
									$day_booking_snake .= '<div class="vbo-tableaux-booking vbo-tableaux-booking-singleunit vbo-tableaux-booking-checkout"><span>&nbsp;</span></div>';
								}
							}
						}

						// update if the current day is actually a closure
						$day_has_closure = $day_has_closure || !empty($day_booking_data['closure']);

						if ($room['units'] == 1 && $day_booking_data && !$day_booking_data['closure']) {
							// build tableaux-style snake container for guest
							$customer_descr = '';
							if ($is_checkin) {
								// customer details
								if (!empty($day_booking_data['first_name']) || !empty($day_booking_data['last_name'])) {
									// check if we need to display a profile picture or a channel logo
									$booking_avatar_src = null;
									$booking_avatar_alt = null;
									if (!empty($day_booking_data['pic'])) {
										// customer profile picture
										$booking_avatar_src = strpos($day_booking_data['pic'], 'http') === 0 ? $day_booking_data['pic'] : VBO_SITE_URI . 'resources/uploads/' . $day_booking_data['pic'];
										$booking_avatar_alt = basename($booking_avatar_src);
									} elseif (!empty($day_booking_data['idorderota']) && !empty($day_booking_data['channel'])) {
										// channel logo
										$logo_helper = VikBooking::getVcmChannelsLogo($day_booking_data['channel'], $get_istance = true);
										if ($logo_helper !== false) {
											$booking_avatar_src = $logo_helper->getSmallLogoURL();
											$booking_avatar_alt = $logo_helper->provenience;
										}
									}

									if (!empty($booking_avatar_src)) {
										// make sure the alt attribute is not too long in case of broken images
										$booking_avatar_alt = !empty($booking_avatar_alt) && strlen($booking_avatar_alt) > 15 ? '...' . substr($booking_avatar_alt, -12) : $booking_avatar_alt;
										// append booking avatar image
										$customer_descr .= '<span class="vbo-tableaux-booking-avatar"><img src="' . $booking_avatar_src . '" class="vbo-tableaux-booking-avatar-img" decoding="async" loading="lazy" ' . (!empty($booking_avatar_alt) ? 'alt="' . htmlspecialchars($booking_avatar_alt) . '" ' : '') . '/></span>';
									}

									// customer name
									$customer_fullname = trim($day_booking_data['first_name'] . ' ' . $day_booking_data['last_name']);
									if (strlen($customer_fullname) > 26) {
										if (function_exists('mb_substr')) {
											$customer_fullname = trim(mb_substr($customer_fullname, 0, 26, 'UTF-8')) . '..';
										} else {
											$customer_fullname = trim(substr($customer_fullname, 0, 26)) . '..';
										}
									}
									$customer_descr .= '<span class="vbo-tableaux-guest-name">' . $customer_fullname . '</span>';
								} else {
									// parse the customer data string
									$custdata_parts = explode("\n", $day_booking_data['custdata']);
									$enoughinfo = false;
									if (count($custdata_parts) > 2 && strpos($custdata_parts[0], ':') !== false && strpos($custdata_parts[1], ':') !== false) {
										// get the first two fields
										$custvalues = array();
										foreach ($custdata_parts as $custdet) {
											if (strlen($custdet) < 1) {
												continue;
											}
											$custdet_parts = explode(':', $custdet);
											if (count($custdet_parts) >= 2) {
												unset($custdet_parts[0]);
												array_push($custvalues, trim(implode(':', $custdet_parts)));
											}
											if (count($custvalues) > 1) {
												break;
											}
										}
										if (count($custvalues) > 1) {
											$enoughinfo = true;
											$customer_nominative = trim(implode(' ', $custvalues));
											if (strlen($customer_nominative) > 26) {
												if (function_exists('mb_substr')) {
													$customer_nominative = trim(mb_substr($customer_nominative, 0, 26, 'UTF-8')) . '..';
												} else {
													$customer_nominative = trim(substr($customer_nominative, 0, 26)) . '..';
												}
											}
											if (!empty($day_booking_data['idorderota']) && !empty($day_booking_data['channel'])) {
												// add support for the channel logo for the imported OTA reservations with no customer record
												$logo_helper = VikBooking::getVcmChannelsLogo($day_booking_data['channel'], $get_istance = true);
												if ($logo_helper !== false) {
													$booking_avatar_src = $logo_helper->getSmallLogoURL();
													$booking_avatar_alt = $logo_helper->provenience;
													// make sure the alt attribute is not too long in case of broken images
													$booking_avatar_alt = !empty($booking_avatar_alt) && strlen($booking_avatar_alt) > 15 ? '...' . substr($booking_avatar_alt, -12) : $booking_avatar_alt;
													// append booking avatar image
													$customer_descr .= '<span class="vbo-tableaux-booking-avatar"><img src="' . $booking_avatar_src . '" class="vbo-tableaux-booking-avatar-img" decoding="async" loading="lazy" ' . (!empty($booking_avatar_alt) ? 'alt="' . htmlspecialchars($booking_avatar_alt) . '" ' : '') . '/></span>';
												}
											}
											// set customer nominative built
											$customer_descr .= '<span class="vbo-tableaux-guest-name">' . $customer_nominative . '</span>';
										}
									}
									if (!$enoughinfo) {
										$customer_descr .= '<span class="vbo-tableaux-guest-name">#' . $day_booking_data['idorder'] . '</span>';
									}
								}
							}
							// set value
							$day_booking_snake .= '<div class="vbo-tableaux-booking vbo-tableaux-booking-singleunit ' . ($is_checkin ? 'vbo-tableaux-booking-checkin' : 'vbo-tableaux-booking-stay') . '"' . ($is_checkin ? ' data-nights="' . $day_booking_data['days'] . '" draggable="true"' : '') . '><span>' . ($is_checkin ? $customer_descr : '&nbsp;') . '</span></div>';
						}

						if ($cookie_sticky_heads != 'off') {
							// not supported with the sticky table headers layout
							$day_booking_snake = '';
						}

						$write_units = strpos($dclass, 'subroom-busy') !== false ? '' : $write_units;
						if ($table_scroll_layout === 'inline') {
							// one table for the whole range, determine if dragging this cell would move a non-displayed check-out
							$stopdrag = $lastbidcheckout && $lastbidcheckout > $last_displayed_day_ts;
							// check if we are displaying the first day of a new month
							if ($moncurts['mday'] == 1 && $nowts['mon'] != $moncurts['mon']) {
								$dclass .= ' vbo-overv-cell-newmonth';
							}
						} else {
							// one table per month, determine if dragging this cell would move a non-displayed check-out
							$stopdrag = ($mind == $mnum && !is_null($lastbidcheckout) && (int) date('n', $lastbidcheckout) != (int) $mon);
						}
						$dclass .= $is_checkin === true ? ' vbo-checkinday' : '';

						// build proper link
						if ($totfound > 1) {
							// there must be a closure overlapping a real booking
							$cell_link = 'index.php?option=com_vikbooking&task=choosebusy&idroom=' . $room['id'] . '&ts=' . $moncurts[0] . '&goto=overv';
						} else {
							// regular situation with one booking only
							$cell_link = 'index.php?option=com_vikbooking&task=editbusy&cid[]=' . $bid . '&goto=overv';
						}
						?>
						<td align="center" class="<?php echo $dclass; ?>"<?php echo $dstyle; ?> data-day="<?php echo $cur_day_key; ?>" data-units-booked="<?php echo $totfound; ?>" data-units-left="<?php echo ($room['units'] - $totfound); ?>" data-bids="<?php echo strpos($dclass, 'subroom-busy') !== false ? "-{$bid}-" : implode(',', $bids_pool); ?>">
						<?php
						if (!$day_booking_snake) {
							if ($is_checkin === true && !$stopdrag && !$day_has_closure && !$is_subunit_unassigned_row) {
								?>
								<span class="vbo-draggable-sp" draggable="true">
									<a href="<?php echo $cell_link; ?>" class="<?php echo strpos($dclass, 'subroom-busy') === false ? 'vbo-overview-redday' : 'vbo-overview-subredday'; ?>"<?php echo $astyle . (!empty($dalt) ? ' title="' . JHtml::fetch('esc_attr', $dalt) . '"' : ''); ?>><?php echo $write_units; ?></a>
								</span>
								<?php
							} else {
								?>
								<a href="<?php echo $cell_link; ?>" class="<?php echo strpos($dclass, 'subroom-busy') === false ? 'vbo-overview-redday' : 'vbo-overview-subredday'; ?>"<?php echo $astyle . (!empty($dalt) ? ' title="' . JHtml::fetch('esc_attr', $dalt) . '"' : ''); ?>><?php echo $write_units; ?></a>
								<?php
							}
						}
						echo $day_booking_snake . $critical_note;
						?>
						</td>
						<?php
					} elseif ($totfound > 1) {
						if ($table_scroll_layout === 'inline') {
							// check if we are displaying the first day of a new month
							if ($moncurts['mday'] == 1 && $nowts['mon'] != $moncurts['mon']) {
								$dclass .= ' vbo-overv-cell-newmonth';
							}
						}
						?>
						<td align="center" class="<?php echo $dclass; ?>"<?php echo $dstyle; ?> data-day="<?php echo $cur_day_key; ?>" data-units-booked="<?php echo $totfound; ?>" data-units-left="<?php echo ($room['units'] - $totfound); ?>" data-bids="<?php echo implode(',', $bids_pool); ?>">
							<a href="index.php?option=com_vikbooking&task=choosebusy&idroom=<?php echo $room['id']; ?>&ts=<?php echo $moncurts[0]; ?>&goto=overv" class="vbo-overview-redday"<?php echo $astyle; ?>><?php echo $write_units; ?></a>
							<?php echo $critical_note; ?>
						</td>
						<?php
					} else {
						// no booked records
						if ($table_scroll_layout === 'inline') {
							// check if we are displaying the first day of a new month
							if ($moncurts['mday'] == 1 && $nowts['mon'] != $moncurts['mon']) {
								$dclass .= ' vbo-overv-cell-newmonth';
							}
						}
						?>
						<td align="center" class="<?php echo $dclass; ?>" data-day="<?php echo $cur_day_key; ?>" data-bids="">
						<?php
						if ($cookie_sticky_heads == 'off' && isset($room_bookings_pool[$room['id']]) && isset($room_bookings_pool[$room['id']][$prev_day_key]) && $room_bookings_pool[$room['id']][$prev_day_key]) {
							if ($is_subunit && isset($room_bookings_pool[$room['id']][$prev_day_key][$room['unit_index']])) {
								if (date('Y-m-d', $room_bookings_pool[$room['id']][$prev_day_key][$room['unit_index']]['checkout']) == $cur_day_key && !($room_bookings_pool[$room['id']][$prev_day_key][$room['unit_index']]['closure'] ?? 0)) {
									// prepend checkout snake
									echo '<div class="vbo-tableaux-booking vbo-tableaux-booking-singleunit vbo-tableaux-booking-checkout"><span>&nbsp;</span></div>';
								}
							} elseif (!$is_subunit && $room['units'] == 1 && isset($room_bookings_pool[$room['id']][$prev_day_key][0])) {
								if (date('Y-m-d', $room_bookings_pool[$room['id']][$prev_day_key][0]['checkout']) == $cur_day_key) {
									// prepend checkout snake
									echo '<div class="vbo-tableaux-booking vbo-tableaux-booking-singleunit vbo-tableaux-booking-checkout"><span>&nbsp;</span></div>';
								}
							}
						}
						echo $critical_note;
						?>
						</td>
						<?php
					}

					// iterate to next day
					$moncurts = getdate(mktime(0, 0, 0, $moncurts['mon'], ($moncurts['mday'] + 1), $moncurts['year']));
				}

				// room row parsed, check if we have to parse a sub-unit next
				if (!$is_subunit && $room_bids_pool && isset($rooms_features_map[$room['id']])) {
					/**
					 * Load bookings for distinctive features when parsing the parent $room array.
					 * Load also the eventually unassigned bookings.
					 * 
					 * @since 	1.18.2 (J) - 1.8.2 (WP)
					 * @since 	1.18.3 (J) - 1.8.3 (WP) improved support for multi-room bookings with different stay dates.
					 */
					list($room_indexes_bids, $room_unassigned_bids) = VikBooking::loadRoomIndexesBookings($room['id'], $room_bids_pool, $unassigned = true);
					if ($room_indexes_bids || $room_unassigned_bids) {
						// set room bookings pool values
						$rooms_bids_pools[$room['id']] = $room_bids_pool;
					}
					if ($room_indexes_bids) {
						// set bookings allocated per room index
						$rooms_features_bookings[$room['id']] = $room_indexes_bids;
						// build a list of room index positions for every booking ID
						$bid_rindex_pos = [];
						foreach ($room_indexes_bids as $rindex => $rindex_bids) {
							foreach ((array) $rindex_bids as $seek_bid) {
								$bid_rindex_pos[$seek_bid] = $bid_rindex_pos[$seek_bid] ?? [];
								$bid_rindex_pos[$seek_bid][] = $rindex;
							}
						}
						// map sub-unit room bookings
						foreach ($room_indexes_bids as $rindex => $rindex_bids) {
							foreach ((array) $rindex_bids as $seek_bid) {
								// collect a list of busy IDs affecting the current room reservation record
								$room_res_busy_ids = [];
								foreach ($room_bookings as $day_ress) {
									foreach ($day_ress as $day_res) {
										if ($day_res['idorder'] == $seek_bid && !in_array($day_res['id'], $room_res_busy_ids)) {
											// push busy ID
											$room_res_busy_ids[] = $day_res['id'];
										}
									}
								}
								// sort the busy IDs affecting the current room reservation record
								sort($room_res_busy_ids);
								// iterate all room bookings
								foreach ($room_bookings as $day_key => $day_ress) {
									// filter all bookings by getting only the needed one(s)
									$eligible_day_reservations = array_values(array_filter($day_ress, function($day_res) use ($seek_bid) {
										// there could be multiple valid bookings in case of multi-room bookings
										return $day_res['idorder'] == $seek_bid;
									}));
									// identify the position of this room index for the current booking ID
									$rindex_res_busy_pos = array_search($rindex, $bid_rindex_pos[$seek_bid] ?? []);
									// identify the busy ID that belongs to the current room index in case of multi-room bookings
									$rindex_res_busy_id = $rindex_res_busy_pos !== false ? ($room_res_busy_ids[$rindex_res_busy_pos] ?? 0) : 0;
									// filter all day reservations by getting only the one with the proper busy ID
									$rindex_day_reservations = array_values(array_filter($eligible_day_reservations, function($res) use ($rindex_res_busy_id) {
										return $rindex_res_busy_id && $res['id'] == $rindex_res_busy_id;
									}));
									if ($rindex_day_reservations[0] ?? []) {
										// allocate the proper room-day-index booking even in case of multi-room bookings
										$room_bookings_pool[$room['id']][$day_key][$rindex] = $rindex_day_reservations[0];
									}
								}
							}
						}
					}
					if ($room_unassigned_bids) {
						// map room unassigned bookings with missing sub-unit (placeholder index = -1)
						$use_rindex = -1;
						// always set all bookings that are missing a sub-unit, not only the first booking record found
						$rooms_features_bookings[$room['id']][$use_rindex] = $room_unassigned_bids;
						// start unassigned bookings intersection container
						$unassigned_bids_intersect = [];
						// start counter
						$unassigned_bids_count = 0;
						// iterate the unassigned reservations
						foreach ($room_unassigned_bids as $seek_bid) {
							// scan all room bookings
							foreach ($room_bookings as $day_key => $day_ress) {
								foreach ($day_ress as $day_res) {
									if ($day_res['idorder'] != $seek_bid || !empty($day_res['closure'])) {
										// unwanted booking
										continue;
									}
									if ($room_bookings_pool[$room['id']][$day_key][$use_rindex] ?? []) {
										/**
										 * Even if there could be multiple room bookings without a sub-unit on the same room and day,
										 * we cannot allow to push more than booking, because the row for the missing sub-units is
										 * just one, and it is therefore impossible to display multiple reservations under the same day.
										 */
										continue;
									}
									/**
									 * Make sure the booking with missing sub-unit that we are about to add is not intersecting other
									 * bookings for the same room that were previously pushed as missing sub-units. This technique
									 * allows us to push unlimited bookings with missing sub-units, rather than just the first one
									 * found in the current month (scroll=tables), or in the current period (scroll=inline), but we
									 * need to make sure the eventually multiple bookings added are not intersecting with each other,
									 * or the display of the bookings would be wrong with overlapping data on certain dates.
									 * 
									 * @since 	1.18.6 (J) - 1.8.6 (WP)
									 */
									$is_intersecting_others = false;
									foreach ($unassigned_bids_intersect as $previous_bid => $previous_intersection) {
										if ($previous_bid == $day_res['idorder']) {
											// this is fine, we need to keep pushing this booking for all stay dates
											continue;
										}
										if (strtotime('00:00:00', $day_res['checkin']) <= $previous_intersection['checkout'] && strtotime('00:00:00', $day_res['checkout']) >= $previous_intersection['checkin']) {
											// intersection found, this missing sub-unit room reservation would interfere
											// with another reservation that was previously processed, so we skip it
											$is_intersecting_others = true;
											break;
										}
									}
									if ($is_intersecting_others) {
										// do not push this booking under the rooms booking pool for this day
										// it can live in the rooms feature bookings array, but not in the pool
										continue;
									}
									// start containers, if needed
									if (!isset($room_bookings_pool[$room['id']])) {
										$room_bookings_pool[$room['id']] = [];
									}
									if (!isset($room_bookings_pool[$room['id']][$day_key])) {
										$room_bookings_pool[$room['id']][$day_key] = [];
									}
									// set room-day booking for the unassigned index
									$room_bookings_pool[$room['id']][$day_key][$use_rindex] = $day_res;
									// set room-day booking intersection data
									$unassigned_bids_intersect[$day_res['idorder']] = [
										'checkin'  => strtotime('00:00:00', $day_res['checkin']),
										'checkout' => strtotime('00:00:00', $day_res['checkout']),
									];
									// increase counter
									$unassigned_bids_count++;
								}
							}
						}
						if (!$unassigned_bids_count) {
							// there were probably only closures, so we unset the bookings for the unassigned row index
							$rooms_features_bookings[$room['id']][$use_rindex] = [];
						}
					}
				}

				// close row
				?>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
	</div>
</div>
	<?php
	// iterate to next month
	$curts = getdate(mktime(0, 0, 0, ($nowts['mon'] + $mind), $nowts['mday'], $nowts['year']));
}

//Prepare modal
?>
<script type="text/javascript">
var hasNewBooking = false;
var last_room_click = '',
	last_date_click = '',
	next_date_click = '';
function vboJModalHideCallback() {
	if (hasNewBooking === true) {
		location.reload();
	}
}
function vboCallOpenJModal(identif, baseurl, options) {
	options = typeof options !== 'object' ? {} : options;

	if (last_room_click && last_room_click.length) {
		baseurl += '&cid[]='+last_room_click;
		options.room_id = last_room_click;
		last_room_click = '';
	}
	if (last_date_click && last_date_click.length) {
		baseurl += '&checkin='+last_date_click;
		options.checkin = last_date_click;
		last_date_click = '';
	}
	if (next_date_click && next_date_click.length) {
		baseurl += '&checkout='+next_date_click;
		options.checkout = next_date_click;
		next_date_click = '';
	}

	// determine what type of modal to render
	if (<?php echo VBOFactory::getConfig()->getBool('overv_newbook_legacy_modal', false) ? 'true' : 'false'; ?>) {
		// use the ("legacy") modal from Bootstrap rendering the View calendar within an iFrame
		vboOpenJModal(identif, baseurl);
	} else {
		// by default we rely on the VikBooking's native modal to render the admin-widget "bookings_calendar"
		VBOCore.handleDisplayWidgetNotification({
			widget_id: 'bookings_calendar',
		}, {
			id_room: options?.room_id || null,
			offset: options?.checkin || null,
			day: options?.checkin || null,
			checkout: options?.checkout || null,
			newbook: 1,
		});
	}
}
</script>
<?php
echo $vbo_app->getJmodalScript('', 'vboJModalHideCallback();', '');
echo $vbo_app->getJmodalHtml('vbo-new-res', JText::translate('VBOSHOWQUICKRES'));
//end Prepare modal
?>
<div class="vbo-ovrv-flt-butn" onclick="vboCallOpenJModal('vbo-new-res', 'index.php?option=com_vikbooking&task=calendar&overv=1&tmpl=component');"><span><i class="vboicn-user-plus"></i> <?php echo JText::translate('VBOSHOWQUICKRES'); ?></span></div>
<div class="vbo-info-overlay-block">
	<div class="vbo-info-overlay-loading-dnd">
		<span class="vbo-loading-dnd-head"></span>
		<span class="vbo-loading-dnd-body"></span>
		<span class="vbo-loading-dnd-footer"><?php echo JText::translate('VIKLOADING'); ?></span>
		<span id="vbo-dnd-response" class="vbo-loading-dnd-response"></span>
		<canvas id="vbo-dnd-canvas-success" height="250"></canvas>
	</div>
</div>

<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="overv" />
	<input type="hidden" name="month" value="<?php echo $tsstart; ?>" />
	<input type="hidden" name="mnum" value="<?php echo $mnum; ?>" />
	<input type="hidden" name="category_id" value="<?php echo $pcategory_id; ?>" />
	<?php echo '<br/>'.$navbut; ?>
</form>

<a class="vbo-basenavuri-details" href="index.php?option=com_vikbooking&task=editorder&goto=overv&cid[]=%d" style="display: none;"></a>
<a class="vbo-basenavuri-edit" href="index.php?option=com_vikbooking&task=editbusy&goto=overv&cid[]=%d" style="display: none;"></a>

<script type="text/Javascript">
const vboOvervMonthNames = <?php echo json_encode($month_names_cache ?? (new stdClass)); ?>;

var hovtimer;
var hovtip = false;
var vbodialogorph_on = false;
var isdragging = false;
var debug_mode = '<?php echo $pdebug; ?>';
var bctags_count = <?php echo count($colortags); ?>;
var bctags_pool = <?php echo json_encode($colortags); ?>;
<?php
if ($colortags) {
	$bctags_tip = '<div class=\"vbo-overview-tip-bctag-subtip-inner\">';
	foreach ($colortags as $ctagk => $ctagv) {
		$bctags_tip .= '<div class=\"vbo-overview-tip-bctag-subtip-circle hasTooltip\" data-ctagkey=\"'.$ctagk.'\" data-ctagcolor=\"'.$ctagv['color'].'\" title=\"'.addslashes(JText::translate($ctagv['name'])).'\"><div class=\"vbo-overview-tip-bctag-subtip-circlecont\" style=\"background-color: '.$ctagv['color'].';\"></div></div>';
	}
	$bctags_tip .= '</div>';
	?>
var bctags_tip = "<?php echo $bctags_tip; ?>";
	<?php
}
?>
</script>

<script type="text/Javascript">
/**
 * Render the units view mode
 */
vboUnitsLeftOrBooked();

/**
 * Orphans dialog
 */
function hideVboDialogOverv(action) {
	if (vbodialogorph_on === true) {
		jQuery(".vbo-orphans-overlay-block").fadeOut(400, function () {
			jQuery(".vbo-info-overlay-content").show();
		});
		vbodialogorph_on = false;
	}
	// check action
	if (action < 0) {
		// stop reminding, set cookie
		let nd = new Date();
		nd.setTime(nd.getTime() + (365*24*60*60*1000));
		document.cookie = "vboHideOrphans=1; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
	}
}

/* DnD global vars */
var cellspool = [];
var newcellspool = [];

/* DnD Count the number of consecutive cells for this booking */
function countBookingCells(cellobj, bidstart, roomid) {
	var totnights = 1;
	var loop = true;
	var cellelem = cellobj;
	cellspool.push(cellelem);
	while (loop === true) {
		var next = cellelem.next('td');
		if (next === undefined || !next.length) {
			// attempt to go to the month after
			var nextmonth = false;
			var partable = cellelem.closest('.vbo-overv-montable-wrap').next('.vbo-overv-montable-wrap').find('table.vboverviewtable');
			if (partable !== undefined && partable.length) {
				partable.find('tr.vboverviewtablerow').each(function() {
					var roomexists = jQuery(this).find('td').first();
					if (roomexists !== undefined && roomexists.length) {
						if (roomexists.attr('data-roomid') == roomid) {
							nextmonth = true;
							next = roomexists.next('td');
							return true;
						}
					}
				});
			}
			if (nextmonth === false) {
				// nothing was found in the month after
				loop = false;
				break;
			}
		}
		cellelem = next;
		var nextbids = cellelem.attr('data-bids');
		if (nextbids.length && nextbids.indexOf(bidstart) >= 0) {
			cellspool.push(cellelem);
			totnights++;
		} else {
			loop = false;
			break;
		}
	}
	return totnights;
}

/* DnD Count the number of consecutive free date-cells for moving the booking onto the landing cell selected for drop */
function countCellFreeNights(landobj, roomid, totnights, moving_bids) {
	var freenights = 1;
	var loop = true;
	var cellelem = landobj;

	// populate cells pool
	newcellspool.push(cellelem);

	while (loop === true) {
		var next = cellelem.next('td');
		if (next === undefined || !next.length) {
			// attempt to go to the month after
			var nextmonth = false;
			var partable = cellelem.closest('.vbo-overv-montable-wrap').next('.vbo-overv-montable-wrap').find('table.vboverviewtable');
			if (partable !== undefined && partable.length) {
				partable.find('tr.vboverviewtablerow').each(function() {
					var roomexists = jQuery(this).find('td').first();
					if (roomexists !== undefined && roomexists.length) {
						if (roomexists.attr('data-roomid') == roomid) {
							nextmonth = true;
							next = roomexists.next('td');
							return true;
						}
					}
				});
			}
			if (nextmonth === false) {
				// nothing was found in the month after
				loop = false;
				break;
			}
		}
		cellelem = next;
		var cell_bids = cellelem.attr('data-bids');
		if (!cellelem.hasClass('busy') || (cellelem.hasClass('busy') && cellelem.hasClass('vbo-partially'))) {
			// bookings of 1 night can stop here because there is availability for one day
			if (parseInt(totnights) === 1) {
				loop = false;
				break;
			}
			// push free cell
			newcellspool.push(cellelem);
			freenights++;
			if (freenights >= totnights) {
				loop = false;
				break;
			}
		} else {
			// cell is occupied, check if it's occupied by the booking we are moving or not
			if (!cell_bids || cell_bids != moving_bids) {
				loop = false;
				break;
			}
			// cell is occupied by the same booking we are moving, so this cell counts as free
			newcellspool.push(cellelem);
			freenights++;
			if (freenights >= totnights) {
				loop = false;
				break;
			}
		}
	}

	return freenights;
}

/* DnD function to perform the ajax request of the booking modification */
function doAlterBooking(bid, roomid, landrid) {
	var nowdatefrom = jQuery(newcellspool[0]).attr('data-day');
	var nowdateto = jQuery(newcellspool[(newcellspool.length -1)]).attr('data-day');

	// perform the request
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=alterbooking'); ?>",
		{
			tmpl: "component",
			idorder: bid,
			oldidroom: roomid,
			idroom: landrid,
			fromdate: nowdatefrom,
			todate: nowdateto,
			e4j_debug: debug_mode
		},
		(res) => {
			if (res.indexOf('e4j.error') >= 0 ) {
				alert(res.replace("e4j.error.", ""));
				//restore the old cells
				jQuery('td.vbo-dragging-cells-tmp').removeClass('vbo-dragging-cells-tmp');
				jQuery('td.vbo-dragged-cells-tmp').removeClass('vbo-dragged-cells-tmp');
				//
				jQuery(".vbo-info-overlay-block").hide();
			} else {
				//move to the new cells and if there are already bookings on those dates, reload the page without moving the blocks
				var obj_res = typeof res === 'string' ? JSON.parse(res) : res;
				var mustReload = obj_res.esit < 1 ? true : false;
				//always force reload if booking was made for more than one room
				var samebidcells = jQuery("td.vbo-checkinday[data-bids='"+bid+"']");
				if (samebidcells.length > 1) {
					mustReload = true;
				}
				//
				jQuery(newcellspool).each(function(k, v) {
					var cur_units_booked = parseInt(jQuery(cellspool[k]).find('a').parent('td').attr('data-units-booked'));
					if ((v.hasClass('busy') && v.attr('data-bids') != jQuery(cellspool[k]).attr('data-bids')) || isNaN(cur_units_booked) || cur_units_booked > 1) {
						mustReload = true;
						return false;
					}
				});
				if (mustReload !== true) {
					/* switch cells */
					var switchmap = [];
					jQuery(cellspool).each(function(k, v) {
						var switchcell = {
							hcont: v.html(),
							cl: v.attr('class'),
							bids: v.attr('data-bids'),
							tit: v.attr('title')
						};
						switchmap[k] = switchcell;
						v.html("").attr('class', 'notbusy').attr('data-bids', '').attr('title', '');
					});
					jQuery(switchmap).each(function(k, v) {
						jQuery(newcellspool[k]).html(v.hcont).attr('class', v.cl).attr('data-bids', v.bids).attr('title', v.tit);
						// re-bind hover event
						jQuery(newcellspool[k]).hover(function() {
							registerHoveringTooltip(this);
						}, unregisterHoveringTooltip);
						//
					});
					jQuery('td.vbo-dragging-cells-tmp').removeClass('vbo-dragging-cells-tmp');
					jQuery('td.vbo-dragged-cells-tmp').removeClass('vbo-dragged-cells-tmp');
				}
				if (obj_res.esit < 1) {
					//some errors occurred after executing certain functions
					if (obj_res.message.length) {
						alert(obj_res.message);
					}
					document.location.href='<?php echo $file_base; ?>?option=com_vikbooking&task=overv';
				} else {
					finalizeDndUpdate(mustReload, obj_res);
				}
			}
		},
		(err) => {
			alert("Request Failed");
			// restore the old cells
			jQuery('td.vbo-dragging-cells-tmp').removeClass('vbo-dragging-cells-tmp');
			jQuery('td.vbo-dragged-cells-tmp').removeClass('vbo-dragged-cells-tmp');
			//
			jQuery(".vbo-info-overlay-block").hide();
		}
	);
}

/* DnD function to animate a success checkmark. The function may refresh the page once complete as this could be launched when there are multiple units for the rooms */
function finalizeDndUpdate(mustReload, obj_res) {
	jQuery('#vbo-dnd-response').html(obj_res.message+' '+obj_res.vcm);
	jQuery('.vbo-loading-dnd-footer').hide();
	var start = 100;
	var mid = 145;
	var end = 250;
	var width = 22;
	var leftX = start;
	var leftY = start;
	var rightX = mid - (width / 2.7);
	var rightY = mid + (width / 2.7);
	var animationSpeed = 20;
	var closingdelay = 700;
	var ctx = document.getElementById('vbo-dnd-canvas-success').getContext('2d');
	ctx.lineWidth = width;
	ctx.strokeStyle = 'rgba(0, 150, 0, 1)';
	for (var i = start; i < mid; i++) {
		var drawLeft = window.setTimeout(function () {
			ctx.beginPath();
			ctx.moveTo(start, start);
			ctx.lineTo(leftX, leftY);
			ctx.stroke();
			leftX++;
			leftY++;
		}, 1 + (i * animationSpeed) / 3);
	}
	for (var i = mid; i < end; i++) {
		var drawRight = window.setTimeout(function () {
			ctx.beginPath();
			ctx.moveTo(leftX, leftY);
			ctx.lineTo(rightX, rightY);
			ctx.stroke();
			rightX++;
			rightY--;
		}, 1 + (i * animationSpeed) / 3);
	}
	//hide modal window
	window.setTimeout(function () {
		if (obj_res.vcm.length) {
			var vcmbtn = '<br clear="all"/><button type="button" class="btn btn-danger" onclick="'+(mustReload === true ? 'document.location.href=\'<?php echo $file_base; ?>?option=com_vikbooking&task=overv\'' : 'closeEsitDialog();')+'">' + Joomla.JText._('VBANNULLA') + '</button>';
			jQuery('#vbo-dnd-response').append(vcmbtn);
		} else {
			if (mustReload === true) {
				document.location.href='<?php echo $file_base; ?>?option=com_vikbooking&task=overv';
			} else {
				jQuery('.vbo-info-overlay-block').fadeOut(400, function(){
					//clear/reset canvas in case of previous drawing and response text
					ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
					jQuery('#vbo-dnd-response').html("");
					//
				});
			}
		}
	}, closingdelay + (i * animationSpeed) / 3);
	//
}

/* DnD function that can be called by those with VCM that have disabled the automated updates. Simply closes the modal window */
function closeEsitDialog() {
	jQuery('.vbo-info-overlay-block').fadeOut(400, function(){
		//clear/reset canvas in case of previous drawing and response text
		var ctx = document.getElementById('vbo-dnd-canvas-success').getContext('2d');
		ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
		jQuery('#vbo-dnd-response').html("");
		//
	});
}

/* Hover Tooltip functions */
function registerHoveringTooltip(that) {
	if (hovtip) {
		return false;
	}
	if (hovtimer) {
		clearTimeout(hovtimer);
		hovtimer = null;
	}
	var elem = jQuery(that);
	var cellheight = elem.outerHeight();
	var celldata = new Array();
	if (elem.hasClass('subroom-busy')) {
		celldata.push(elem.parent('tr').attr('data-subroomid'));
		celldata.push(elem.attr('data-day'));
	}
	hovtimer = setTimeout(() => {
		if (isdragging || (vboActionRoomRateData && vboActionRoomRateData?.start)) {
			// prevent tooltip from popping up if dragging or if selecting room rates
			unregisterHoveringTooltip();

			return;
		}

		// turn flag on
		hovtip = true;

		// calculate cell-element position
		let pos_top = elem.offset().top;
		let pos_left = elem.offset().left;
		let elem_height = elem.outerHeight();
		let screen_width = window?.screen?.width || 0;

		// build tooltip block element
		let tooltip_block = jQuery('<div></div>');
		tooltip_block.addClass('vbo-overview-tipblock');
		tooltip_block.append("<div class=\"vbo-overview-tipinner\"><span class=\"vbo-overview-tiploading\">" + Joomla.JText._('VIKLOADING') + "</span></div>");
		tooltip_block.append("<div class=\"vbo-overview-tipexpander\" style=\"display: none;\"><div class=\"vbo-overview-expandtoggle\"><i class=\"<?php echo VikBookingIcons::i('expand'); ?>\"></i></div></div>");

		// calculate block position
		tooltip_block.css('top', (pos_top + elem_height - 16) + 'px');
		if (screen_width > 600 && (pos_left + 400) > screen_width) {
			// place the tooltip starting from right
			tooltip_block.css('left', (pos_left - (400 - elem.outerWidth())) + 'px');
		} else {
			// regular placing starting from left
			tooltip_block.css('left', (pos_left - 6) + 'px');
		}

		// append block to body
		tooltip_block.appendTo(jQuery('body'));

		// load tooltip bookings
		loadTooltipBookings(elem.attr('data-bids'), celldata);
	}, 1500);
}

function unregisterHoveringTooltip() {
	clearTimeout(hovtimer);
	hovtimer = null;
}

/**
 * @deprecated  1.16.10 (J) - 1.6.10 (WP)
 */
function adjustHoveringTooltip() {
	setTimeout(() => {
		var ver_difflim = 35;
		var hor_difflim = 20;
		var tip_block = jQuery('.vbo-overview-tipblock');
		if (!tip_block || !tip_block.length) {
			return;
		}
		var table_wrap = tip_block.closest('.vbo-overv-montable-wrap');

		if (tip_block.outerHeight() > table_wrap.outerHeight()) {
			// tooltip is too tall to fit in the table, render the modal instead
			tip_block.hide().closest('td.busy').trigger('click');
			return;
		}

		// vertical positioning
		var otop = tip_block.offset().top;
		if (otop > 0 && otop < ver_difflim) {
			// adjust tooltip position
			tip_block.css('bottom', '-=' + (ver_difflim - otop));
		} else if (otop < table_wrap.offset().top) {
			// tooltip exceeds wrapping table, move it underneath the cell
			var extra_padding_top = 0;
			var tip_inner_padtop = tip_block.find('.vbo-overview-tipinner').css('padding-top');
			if (tip_inner_padtop) {
				// get only numbers
				extra_padding_top = tip_inner_padtop.replace(/[^0-9]/g, '');
				extra_padding_top = !isNaN(extra_padding_top) ? parseInt(extra_padding_top) : 0;
			}
			tip_block.css('bottom', 'auto').css('top', (tip_block.parent('td').outerHeight() - extra_padding_top));
		}

		// horizontal positioning
		if (tip_block.offset().left < table_wrap.offset().left) {
			var left_diff = table_wrap.offset().left - tip_block.offset().left;
			if (left_diff > hor_difflim) {
				// tooltip exceeds table on the left, move it to the right of the cell (i.e 1st day of month)
				tip_block.css('right', tip_block.css('left'));
				tip_block.css('left', 'unset');
			}
		}
	}, 60);
}

function hideVboTooltip() {
	jQuery('.vbo-overview-tipblock').remove();
	hovtip = false;
}

function loadTooltipBookings(bids, celldata) {
	if (!bids || bids === undefined || !bids.length) {
		hideVboTooltip();
		return false;
	}

	var subroomdata = celldata.length ? celldata[0] : '';

	// perform the request
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=getbookingsinfo'); ?>",
		{
			tmpl: "component",
			idorders: bids,
			subroom: subroomdata
		},
		(res) => {
			try {
				var obj_res = typeof res === 'string' ? JSON.parse(res) : res;
				jQuery('.vbo-overview-tiploading').remove();
				var container = jQuery('.vbo-overview-tipinner');
				jQuery(obj_res).each(function(k, v) {
					// get base navigation URIs
					var base_uri_details = jQuery('.vbo-basenavuri-details').attr('href');
					var base_uri_edit = jQuery('.vbo-basenavuri-edit').attr('href');

					// build content
					var bcont = "<div class=\"vbo-overview-tip-bookingcont\">";
					bcont += "<div class=\"vbo-overview-tip-bookingcont-left\">";
					bcont += "<div class=\"vbo-overview-tip-bid\"><span class=\"vbo-overview-tip-lbl\">ID <span class=\"vbo-overview-tip-lbl-innerleft\"><a href=\"" + base_uri_edit.replace('%d', v.id) + "\"><i class=\"<?php echo VikBookingIcons::i('edit'); ?>\"></i></a></span></span><span class=\"vbo-overview-tip-cnt\">"+v.id+"</span></div>";
					bcont += "<div class=\"vbo-overview-tip-bstatus\"><span class=\"vbo-overview-tip-lbl\"><?php echo addslashes(JText::translate('VBPVIEWORDERSEIGHT')); ?></span><span class=\"vbo-overview-tip-cnt\"><div class=\"badge "+(v.status == 'confirmed' ? 'badge-success' : 'badge-warning')+"\">"+v.status_lbl+"</div></span></div>";
					bcont += "<div class=\"vbo-overview-tip-bdate\"><span class=\"vbo-overview-tip-lbl\"><?php echo addslashes(JText::translate('VBPVIEWORDERSONE')); ?></span><span class=\"vbo-overview-tip-cnt\"><a href=\"" + base_uri_details.replace('%d', v.id) + "\">"+v.ts+"</a></span></div>";
					if (bctags_count > 0) {
						var bctag_title = '';
						var bctag_color = '#ffffff';
						if (v.colortag.hasOwnProperty('color')) {
							bctag_color = v.colortag.color;
							bctag_title = v.colortag.name;
						}
						bcont += "<div class=\"vbo-overview-tip-bctag-wrap\"><div class=\"vbo-overview-tip-bctag\" data-bid=\""+v.id+"\" data-ctagcolor=\""+bctag_color+"\" style=\"background-color: "+bctag_color+"; color: "+v.colortag.fontcolor+";\"><i class=\"vboicn-price-tags\"></i></div><span class=\"vbo-overview-tip-bctag-name\">" + bctag_title + "</span></div>";
					}
					bcont += "</div>";
					bcont += "<div class=\"vbo-overview-tip-bookingcont-right\">";
					bcont += "<div class=\"vbo-overview-tip-bcustomer\"><span class=\"vbo-overview-tip-lbl\"><?php echo addslashes(JText::translate('VBOCUSTOMER')); ?></span><span class=\"vbo-overview-tip-cnt\">"+v.cinfo+"</span></div>";
					if (v.roomsnum > 1) {
						bcont += "<div class=\"vbo-overview-tip-brooms\"><span class=\"vbo-overview-tip-lbl\">" + Joomla.JText._('VBEDITORDERROOMSNUM') + (parseInt(v.roomsnum) > 1 ? " (" + v.roomsnum + ")" : "") + "</span><span class=\"vbo-overview-tip-cnt\">" + v.room_names + "</span></div>";
					}
					bcont += "<div class=\"vbo-overview-tip-bguests\"><span class=\"vbo-overview-tip-lbl\">" + Joomla.JText._('VBDAYS') + (v.split_stay > 0 ? ' (' + Joomla.JText._('VBO_SPLIT_STAY') + ')' : '') + "</span><span class=\"vbo-overview-tip-cnt hasTooltip\" title=\"" + Joomla.JText._('VBPICKUPAT') + " " + v.checkin + " - " + Joomla.JText._('VBRELEASEAT') + " " + v.checkout + "\">" + v.days + ", " + Joomla.JText._('VBEDITORDERADULTS') + ": " + v.tot_adults + (v.tot_children > 0 ? ", " + Joomla.JText._('VBEDITORDERCHILDREN') + ": " + v.tot_children : "") + "</span></div>";
					if (v.hasOwnProperty('rindexes')) {
						for (var rindexk in v.rindexes) {
							if (v.rindexes.hasOwnProperty(rindexk)) {
								bcont += "<div class=\"vbo-overview-tip-brindexes\"><span class=\"vbo-overview-tip-lbl\">" + rindexk + "</span><span class=\"vbo-overview-tip-cnt\">" + v.rindexes[rindexk] + "</span></div>";
							}
						}
					}
					if (v.hasOwnProperty('channelimg')) {
						bcont += "<div class=\"vbo-overview-tip-bprovenience\"><span class=\"vbo-overview-tip-lbl\">" + Joomla.JText._('VBPVIEWORDERCHANNEL') + "</span><span class=\"vbo-overview-tip-cnt\">" + v.channelimg + "</span></div>";
					}
					if (v.hasOwnProperty('optindexes') && celldata.length) {
						var subroomids = celldata[0].split('-');
						bcont += "<div class=\"vbo-overview-tip-optindexes\"><span class=\"vbo-overview-tip-lbl\"> </span><span class=\"vbo-overview-tip-cnt\"><select onchange=\"vboMoveSubunit('" + v.id + "', '" + subroomids[0] + "', '" + subroomids[1] + "', this.value, '" + celldata[1] + "');\">" + v.optindexes + "</select></span></div>";
					}
					bcont += "<div class=\"vbo-overview-tip-bookingcont-total\">";
					bcont += "<div class=\"vbo-overview-tip-btot\"><span class=\"vbo-overview-tip-lbl\">" + Joomla.JText._('VBEDITORDERNINE') + "</span><span class=\"vbo-overview-tip-cnt\">" + VBOCore.getCurrency().display(v.format_tot) + "</span></div>";
					if (v.totpaid > 0.00) {
						bcont += "<div class=\"vbo-overview-tip-btot vbo-overview-tip-btotpaid\"><span class=\"vbo-overview-tip-lbl\">" + Joomla.JText._('VBPEDITBUSYTOTPAID') + "</span><span class=\"vbo-overview-tip-cnt\">" + VBOCore.getCurrency().display(v.format_totpaid) + "</span></div>";
					}
					var getnotes = v.adminnotes;
					if (getnotes !== null && getnotes.length) {
						bcont += "<div class=\"vbo-overview-tip-notes\"><span class=\"vbo-overview-tip-lbl\"><span class=\"vbo-overview-tip-notes-inner\"><i class=\"vboicn-info hasTooltip\" title=\"" + getnotes + "\"></i></span></span></div>";
					}
					bcont += "</div>";
					bcont += "</div>";
					bcont += "</div>";
					container.append(bcont);
					jQuery('.vbo-overview-tipexpander').show();
				});

				/**
				 * @deprecated  1.16.10 (J) - 1.6.10 (WP)
				 */
				// adjust the position so that it won't go under other contents
				// adjustHoveringTooltip();

				jQuery(".hasTooltip").tooltip();
			} catch(err) {
				// restore
				hideVboTooltip();
				// display error
				console.error('could not parse JSON response', err, res);
				alert('Could not parse JSON response');
			}
		},
		(err) => {
			// restore
			hideVboTooltip();
			// display error
			console.error(err);
			alert(err.responseText);
		}
	);
}

/**
 * Set a sub-unit to a room booking. Triggered by the room-day bookings modal
 */
function vboSetSubunit(bid, rid, orkey, rindex) {
	if (!(rindex + '').length) {
		return false;
	}

	if (!confirm(Joomla.JText._('VBOFEATASSIGNUNIT') + rindex + '?')) {
		return false;
	}

	// show loading
	VBOCore.emitEvent('vbo-loading-modal-overv-rdaybookings');

	// make the request to set the room sub-unit
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.set_room_booking_subunit'); ?>",
		{
			bid: bid,
			rid: rid,
			orkey: orkey,
			rindex: rindex,
			tmpl: 'component'
		},
		(res) => {
			// dismiss the modal (no need to stop the loading)
			VBOCore.emitEvent('vbo-dismiss-modal-overv-rdaybookings');

			try {
				var obj_res = typeof res === 'string' ? JSON.parse(res) : res;
				if (!obj_res.hasOwnProperty('nights') || !Array.isArray(obj_res['nights']) || !obj_res['nights'].length) {
					// invalid response
					throw new Error('Invalid response');
				}

				// check if cells can be occupied
				var sub_rows = jQuery('tr.vboverviewtablerow-subunit[data-subroomid="' + rid + '-' + rindex + '"]');
				if (!sub_rows || !sub_rows.length) {
					console.error('Could not find any room sub-unit row');
					return false;
				}

				// loop through all sub-unit rows to match the cells for the nights affected
				sub_rows.each(function() {
					var sub_row = jQuery(this);
					obj_res['nights'].forEach((ymd, kindex) => {
						let sub_row_cell = sub_row.find('td[data-day="' + ymd + '"]');
						if (sub_row_cell.length) {
							sub_row_cell.removeClass('subnotbusy').addClass('subroom-busy');
							if (kindex == 0) {
								sub_row_cell.addClass('vbo-checkinday');
							}
							sub_row_cell.attr('data-bids', '-' + obj_res['bid'] + '-');
							if (!sub_row_cell.find('span.vbo-draggable-sp').length) {
								sub_row_cell.prepend('<span class="vbo-draggable-sp"><a class="vbo-overview-subredday">&bull;</a></span>');
							}
						}
					});
				});
			} catch(err) {
				console.error('could not parse JSON response', err, res);
				alert('Could not parse JSON response');
			}
		},
		(err) => {
			// stop loading
			VBOCore.emitEvent('vbo-loading-modal-overv-rdaybookings');

			// log and display the error
			console.error(err);
			alert(err.responseText);
		}
	);
}

/**
 * Move a subunit group of cells to another subroom row. Triggered by
 * the hovering tooltip, by the room-day bookings modal, or by DnD etc..
 */
function vboMoveSubunit(bid, rid, old_rindex, new_rindex, dday) {
	let new_rindex_name = new_rindex.replace('#', '');
	let is_tmp_subunit_row = new_rindex_name == 0;
	let is_from_tmp_row = old_rindex == 0 || old_rindex == -1;

	if (new_rindex_name == -1 || (old_rindex == -1 && is_tmp_subunit_row)) {
		// no landing on the unassigned row, or unassigned room cannot land on the holding area
		alert(<?php echo json_encode(JText::translate('VBO_UNASSIGNED_ROW_FORBID')); ?>);
		return false;
	}

	// request confirmation, if needed
	if (!is_tmp_subunit_row && !confirm(Joomla.JText._('VBOFEATASSIGNUNIT') + new_rindex_name + '?')) {
		return false;
	}

	// check if movement can be made
	var cur_tr = jQuery('tr.vboverviewtablerow-subunit[data-subroomid="' + rid + '-' + old_rindex + '"]');
	if (!cur_tr || !cur_tr.length) {
		console.error('could not find the parent row of the subunit cells');
		return false;
	}
	var maincell = cur_tr.find('td.subroom-busy[data-day="' + dday + '"]');
	if (!maincell || !maincell.length) {
		console.error('could not find the main cell of the subunit to move');
		return false;
	}
	var dest_tr = jQuery('tr.vboverviewtablerow-subunit[data-subroomid="' + rid + '-' + new_rindex + '"]');
	if (!dest_tr || !dest_tr.length) {
		console.error('could not find the destination row for the subunit cells');
		return false;
	}
	var targetbids = maincell.attr('data-bids');
	if (targetbids.indexOf('-' + bid + '-') < 0) {
		console.error('given bid does not match with cell bids', bid, targetbids);
		return false;
	}
	var firstcell = maincell;
	var loop = true;
	while (loop === true) {
		var prevsib = firstcell.prev();
		if (prevsib && prevsib.length && prevsib.attr('data-bids') && prevsib.attr('data-bids').indexOf('-' + bid + '-') >= 0) {
			firstcell = prevsib;
		} else {
			loop = false;
		}
	}
	// make sure to get the check-in day (first cell)
	dday = firstcell.attr('data-day');
	var destcell = dest_tr.find('td.subnotbusy[data-day="' + dday + '"]');
	if (!destcell || !destcell.length) {
		// first destination cell is occupied, check if swap is allowed
		if (dest_tr.find('td.subroom-busy[data-day="' + dday + '"]').length) {
			var landbids = dest_tr.find('td.subroom-busy[data-day="' + dday + '"]').attr('data-bids');
			return vboSwapRoomSubunits(bid, landbids.replaceAll('-', ''), rid, old_rindex, new_rindex, dday);
		}
		console.error('could not find the first free destination cell');
		return false;
	}
	// check if all dates are free before making movements. Redundant, but useful.
	var freedates = true;
	var copyfirstcell = firstcell;
	var copydday = dday;
	loop = true;
	while (loop === true) {
		var nextsib = copyfirstcell.next();
		if (nextsib && nextsib.length && nextsib.attr('data-bids') && nextsib.attr('data-bids').indexOf('-' + bid + '-') >= 0) {
			copyfirstcell = nextsib;
			copydday = copyfirstcell.attr('data-day');
			var copydestcell = dest_tr.find('td.subnotbusy[data-day="' + copydday + '"]');
			if (!copydestcell || !copydestcell.length) {
				console.error('could not find the next free destination cell for ' + copydday);
				alert(<?php echo json_encode(JText::translate('VBBOOKNOTMADE')); ?>);
				freedates = false;
				loop = false;
			}
		} else {
			loop = false;
		}
	}
	if (freedates === false) {
		return false;
	}

	// loading opacity
	jQuery('.vbo-overview-tipblock').css('opacity', '0.6');

	// perform the request
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=switchRoomIndex'); ?>",
		{
			tmpl: "component",
			bid: bid,
			rid: rid,
			old_rindex: old_rindex,
			new_rindex: new_rindex,
			is_tmp_row: is_tmp_subunit_row ? 1 : 0,
			is_from_tmp_row: is_from_tmp_row ? 1 : 0,
		},
		(res) => {
			if (res.indexOf('e4j.error') >= 0 ) {
				alert(res.replace("e4j.error.", ""));

				// restore loading opacity in container
				jQuery('.vbo-overview-tipblock').css('opacity', '1');

				// abort
				return;
			}

			// hide tooltip
			hideVboTooltip();

			// loop for moving cells
			loop = true;
			while (loop === true) {
				// populate destination cell attributes, classes and content
				destcell.removeClass('subnotbusy').addClass('subroom-busy').attr('data-bids', firstcell.attr('data-bids'));
				if (firstcell.hasClass('vbo-checkinday')) {
					firstcell.removeClass('vbo-checkinday');
					destcell.addClass('vbo-checkinday');
				}
				// populate inline styling
				let orig_style = firstcell.attr('style');
				destcell.attr('style', orig_style);
				firstcell.attr('style', '');
				// populate content
				firstcell.children().not('.vbo-tableaux-booking-checkout').not('.vbo-roomdaynote-trigger').appendTo(destcell);

				// re-bind hover event for tooltip
				jQuery(destcell).hover(function() {
					registerHoveringTooltip(this);
				}, unregisterHoveringTooltip);

				// clean up the previous cell from which we moved data
				firstcell.removeClass('subroom-busy').addClass('subnotbusy').attr('data-bids', '');

				// check if we have a next iteration to perform
				let nextsib = firstcell.next();
				if (nextsib && nextsib.length && nextsib.attr('data-bids') && nextsib.attr('data-bids').indexOf('-' + bid + '-') >= 0) {
					// prepare data for the next iteration
					firstcell = nextsib;
					dday = firstcell.attr('data-day');
					destcell = dest_tr.find('td.subnotbusy[data-day="' + dday + '"]');
					if (!destcell || !destcell.length) {
						console.error('could not find the next free destination cell');
						loop = false;
					}
				} else {
					// all cells were moved, break the loop
					loop = false;
					// check if we need to move the checkout snake as well
					if (nextsib && nextsib.find('.vbo-tableaux-booking-checkout').length) {
						dday = nextsib.attr('data-day');
						destcell = dest_tr.find('td.subnotbusy[data-day="' + dday + '"]');
						if (!destcell || !destcell.length) {
							// the landing checkout snake may be on an occupied date
							destcell = dest_tr.find('td.subroom-busy[data-day="' + dday + '"]');
						}
						if (destcell && destcell.length) {
							// prepend the checkout snake to the next cell
							nextsib.find('.vbo-tableaux-booking-checkout').prependTo(destcell);
						}
					}
				}
			}

			// dispatch the event for the sub-units updated
			VBOCore.emitEvent('vbo-overv-sub-units-updated');
		},
		(err) => {
			alert('Request Failed');
			// restore loading opacity in container
			jQuery('.vbo-overview-tipblock').css('opacity', '1');
		}
	);

	return true;
}

/**
 * Swap room sub-unit with another sub-unit on an occupied date. Triggered by DnD or by manual sub-unit switching.
 */
function vboSwapRoomSubunits(bid, bidtwo, rid, old_rindex, new_rindex, dday) {
	if (old_rindex == 0 || new_rindex == 0) {
		// prevent swapping with a room reservation in the holding area (temporary sub-units row)
		alert(<?php echo json_encode(JText::translate('VBO_HOLDING_AREA_NO_SWAP')); ?>);
		return false;
	}
	if (old_rindex == -1 || new_rindex == -1) {
		// prevent swapping with a room reservation in the unassigned row
		alert(<?php echo json_encode(JText::translate('VBO_UNASSIGNED_ROW_FORBID')); ?>);
		return false;
	}
	if (!confirm(Joomla.JText._('VBO_CONF_SWAP_RNUMB').replace('%s', old_rindex).replace('%s', new_rindex))) {
		return false;
	}
	if (!bid || !bidtwo || !rid || !old_rindex || !new_rindex || !dday) {
		console.error('Missing required arguments', bid, bidtwo, rid, old_rindex, new_rindex, dday);
		return false;
	}
	// check if movement can be made
	let cur_tr = jQuery('tr.vboverviewtablerow-subunit[data-subroomid="' + rid + '-' + old_rindex + '"]');
	if (!cur_tr || !cur_tr.length) {
		console.error('could not find the parent row of the subunit cells');
		return false;
	}
	let maincell = cur_tr.find('td.subroom-busy[data-day="' + dday + '"]');
	if (!maincell || !maincell.length) {
		console.error('could not find the main cell of the subunit to move');
		return false;
	}
	let dest_tr = jQuery('tr.vboverviewtablerow-subunit[data-subroomid="' + rid + '-' + new_rindex + '"]');
	if (!dest_tr || !dest_tr.length) {
		console.error('could not find the destination row for the subunit cells');
		return false;
	}
	let destcell = dest_tr.find('td.subroom-busy[data-day="' + dday + '"]');
	if (!destcell || !destcell.length) {
		console.error('could not find the destination cell of the subunit to move');
		return false;
	}

	// gather the cells for the swop
	let swap_cells_one  = [];
	let swap_cells_two  = [];
	let swap_dates_one  = [];
	let swap_dates_two  = [];
	let swap_styles_one = [];
	let swap_styles_two = [];
	let swap_cell_from  = maincell;
	let swap_cell_to    = destcell;

	// register the elements to swap of the first sub-unit
	if (!swap_cell_from.hasClass('vbo-checkinday')) {
		// attempt to select the first cell of this booking in the current month
		while (true) {
			let prev_cell_from = swap_cell_from.prev();
			if (!prev_cell_from || !prev_cell_from.length) {
				break;
			}
			let prev_bids = prev_cell_from.attr('data-bids');
			if (!prev_bids || prev_bids.indexOf('-' + bid + '-') < 0) {
				break;
			}
			swap_cell_from = prev_cell_from;
		}
	}
	while (true) {
		if (!swap_cell_from || !swap_cell_from.length) {
			// next cell not found
			break;
		}
		let swap_bids = swap_cell_from.attr('data-bids');
		if (!swap_bids || swap_bids.indexOf('-' + bid + '-') < 0) {
			// last occupied cell reached
			break;
		}
		// push cloned cell data to move
		swap_cells_one.push(swap_cell_from.children().not('.vbo-tableaux-booking-checkout').not('.vbo-roomdaynote-trigger').clone(true));
		swap_dates_one.push(swap_cell_from.attr('data-day'));
		swap_styles_one.push(swap_cell_from.attr('style'));
		// set next iteration cell
		swap_cell_from = swap_cell_from.next();
	}

	// register the elements to swap of the second sub-unit
	while (true) {
		if (!swap_cell_to || !swap_cell_to.length) {
			// next cell not found
			break;
		}
		let swap_bids = swap_cell_to.attr('data-bids');
		if (!swap_bids || swap_bids.indexOf('-' + bidtwo + '-') < 0) {
			// last occupied cell reached
			break;
		}
		// make sure we are swapping with the check-in day, or if it's a stay-date we need to unshift until the check-in
		if (!swap_cells_two.length && !swap_cell_to.hasClass('vbo-checkinday')) {
			// prepend first the cells until the check-in or until not found
			let prev_cell = swap_cell_to;
			while (true) {
				prev_cell = prev_cell.prev();
				if (!prev_cell || !prev_cell.length) {
					break;
				}
				let prev_cell_snake = prev_cell.find('.vbo-tableaux-booking').not('.vbo-tableaux-booking-checkout');
				if (prev_cell_snake && prev_cell_snake.length && (prev_cell_snake.hasClass('vbo-tableaux-booking-checkin') || prev_cell_snake.hasClass('vbo-tableaux-booking-stay'))) {
					// prepend cloned cell data to move
					swap_cells_two.unshift(prev_cell.children().not('.vbo-tableaux-booking-checkout').not('.vbo-roomdaynote-trigger').clone(true));
					swap_dates_two.unshift(prev_cell.attr('data-day'));
					swap_styles_two.unshift(prev_cell.attr('style'));
				} else {
					// no more previous cells
					break;
				}
				if (prev_cell.hasClass('vbo-checkinday')) {
					break;
				}
			}
		}
		// push cloned cell data to move
		swap_cells_two.push(swap_cell_to.children().not('.vbo-tableaux-booking-checkout').not('.vbo-roomdaynote-trigger').clone(true));
		swap_dates_two.push(swap_cell_to.attr('data-day'));
		swap_styles_two.push(swap_cell_to.attr('style'));
		// set next iteration cell
		swap_cell_to = swap_cell_to.next();
	}

	if (!swap_cells_one.length || !swap_cells_two.length) {
		console.error('could not gather the cells to swap', swap_cells_one, swap_cells_two);
		return false;
	}

	// walk over the cells of the first room to swap and check the corresponding destination
	let check_destcell = destcell;
	for (let i = 0; i < swap_cells_one.length; i++) {
		if (!check_destcell || !check_destcell.length) {
			// this cell does not exist, abort
			console.error('destination cell to #' + (i + 1) + ' was not found');
			return false;
		}
		let dest_bids = check_destcell.attr('data-bids');
		if (dest_bids && dest_bids.length && dest_bids.indexOf('-' + bidtwo + '-') < 0) {
			// this cell is already occupied, abort
			console.error('destination cell to #' + (i + 1) + ' is occupied by another reservation');
			alert(<?php echo json_encode(JText::translate('VBBOOKNOTMADE')); ?>);
			return false;
		}
		// set next destination cell
		check_destcell = check_destcell.next();
	}

	// walk over the cells of the second room to swap and check the corresponding destination
	check_destcell = maincell;
	for (let i = 0; i < swap_cells_two.length; i++) {
		if (!check_destcell || !check_destcell.length) {
			// this cell does not exist, abort
			console.error('destination cell from #' + (i + 1) + ' was not found');
			return false;
		}
		let dest_bids = check_destcell.attr('data-bids');
		if (dest_bids && dest_bids.length && dest_bids.indexOf('-' + bid + '-') < 0) {
			// this cell is already occupied, abort
			console.error('destination cell from #' + (i + 1) + ' is occupied by another reservation');
			alert(<?php echo json_encode(JText::translate('VBBOOKNOTMADE')); ?>);
			return false;
		}
		// set next destination cell
		check_destcell = check_destcell.next();
	}

	// make the AJAX request and swap cells only in case of success
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.swap_room_subunits'); ?>",
		{
			bid_one: bid,
			bid_two: bidtwo,
			rid: rid,
			index_one: old_rindex,
			index_two: new_rindex,
			checkin: dday,
			tmpl: 'component'
		},
		(res) => {
			// init values from selection
			let target_cell  = destcell;
			let current_cell = maincell;
			let different_lengths = swap_dates_one.length != swap_dates_two.length;
			let swap_diff_dates = false;

			// move the cells of the first sub-unit onto the cells of the second sub-unit
			for (let counter = 0; counter < swap_cells_one.length; counter++) {
				// remove elements from target
				target_cell.children().not('.vbo-tableaux-booking-checkout').not('.vbo-roomdaynote-trigger').remove();
				// swap cell elements
				swap_cells_one[counter].appendTo(target_cell);
				// set proper bids attribute
				target_cell.attr('data-bids', '-' + bid + '-');
				// restore initial cell styling
				if ((swap_styles_one[counter] || 0)) {
					target_cell.attr('style', swap_styles_one[counter]);
				}
				if (swap_dates_one.length > swap_dates_two.length) {
					if (!(swap_dates_two[counter] || 0) || swap_dates_two.length == counter + 1) {
						current_cell.attr('style', '');
					}
				}
				// check if we are swapping with a non check-in day sub-unit occupied cell
				if (counter === 0 && !target_cell.hasClass('vbo-checkinday')) {
					target_cell.addClass('vbo-checkinday');
					swap_diff_dates = true;
				} else if (counter > 0 && !target_cell.hasClass('subroom-busy')) {
					target_cell.removeClass('subnotbusy').addClass('subroom-busy');
				}
				// check if the reservations have different nights of stay
				let cell_will_occupy = true;
				if (counter > 0 && swap_diff_dates && !swap_dates_two.includes(swap_dates_one[counter])) {
					// the swapped unit will not occupy this cell, so clean it
					cell_will_occupy = false;
				} else if ((counter + 1) > swap_cells_two.length) {
					// this reservation is longer than the other
					cell_will_occupy = false;
				}
				if (!cell_will_occupy) {
					// remove the children elements from this longer reservation
					current_cell.children().not('.vbo-tableaux-booking-checkout').not('.vbo-roomdaynote-trigger').remove();
					// remove the occupied class as well
					current_cell.removeClass('subroom-busy').addClass('subnotbusy').attr('data-bids', '');
				}
				// set next target cell
				target_cell = target_cell.next();
				// update current cell
				current_cell = current_cell.next();
			}

			// move the checkout snake of the first sub-unit
			if (target_cell && target_cell.length && current_cell && current_cell.length && current_cell.find('.vbo-tableaux-booking-checkout').length) {
				current_cell.find('.vbo-tableaux-booking-checkout').prependTo(target_cell);
			}

			// move the cells of the second sub-unit onto the cells of the first sub-unit
			target_cell  = maincell;
			current_cell = destcell;

			// check if we are swapping with a non check-in day sub-unit occupied cell
			if (swap_diff_dates) {
				let prev_land_cell = current_cell;
				while (true) {
					prev_land_cell = prev_land_cell.prev();
					if (!prev_land_cell || !prev_land_cell.length) {
						break;
					}
					if (prev_land_cell.hasClass('subroom-busy')) {
						// overwrite first cell to move and to land
						current_cell = prev_land_cell;
						target_cell  = target_cell.prev();
						// empty this cell because no one will occupy it
						current_cell.children().not('.vbo-tableaux-booking-checkout').not('.vbo-roomdaynote-trigger').remove();
						// make the cell free
						current_cell.removeClass('subroom-busy').addClass('subnotbusy').attr('data-bids', '');
					}
					if (prev_land_cell.hasClass('vbo-checkinday')) {
						// we have reached the first cell of this booking
						break;
					}
				}
			}

			for (let counter = 0; counter < swap_cells_two.length; counter++) {
				// remove elements from target
				target_cell.children().not('.vbo-tableaux-booking-checkout').not('.vbo-roomdaynote-trigger').remove();
				// swap cell elements
				swap_cells_two[counter].appendTo(target_cell);
				// set proper bids attribute
				target_cell.attr('data-bids', '-' + bidtwo + '-');
				// restore initial cell styling
				if ((swap_styles_two[counter] || 0)) {
					target_cell.attr('style', swap_styles_two[counter]);
				}
				if (!(swap_dates_one[counter] || 0) && swap_dates_one.length > swap_dates_two.length) {
					current_cell.attr('style', '');
				}
				// check if we are swapping with a non check-in day sub-unit occupied cell
				if (counter === 0 && !target_cell.hasClass('vbo-checkinday')) {
					target_cell.addClass('vbo-checkinday');
					if (current_cell.hasClass('vbo-checkinday') && !swap_dates_one.includes(swap_dates_two[counter])) {
						current_cell.removeClass('vbo-checkinday');
					}
				} else if (counter > 0 && target_cell.hasClass('vbo-checkinday')) {
					target_cell.removeClass('vbo-checkinday');
				}
				if (!target_cell.hasClass('subroom-busy')) {
					target_cell.removeClass('subnotbusy').addClass('subroom-busy');
				}
				// check if the reservations have different nights of stay
				if (different_lengths && (swap_dates_two[counter] || 0) && !swap_dates_one.includes(swap_dates_two[counter])) {
					// moving a shorter reservation onto a longer reservation
					// remove the children elements from this longer reservation
					current_cell.children().not('.vbo-tableaux-booking-checkout').not('.vbo-roomdaynote-trigger').remove();
					// remove the occupied class as well
					current_cell.removeClass('subroom-busy').addClass('subnotbusy').attr('data-bids', '');
					// set the occupied class onto the target
					target_cell.removeClass('subnotbusy').addClass('subroom-busy');
					// check booking color tags
					let current_cell_styling = current_cell.attr('style');
					// remove custom background color with an inline styling
					current_cell.attr('style', '');
					// restore the original styling on target
					target_cell.attr('style', current_cell_styling);
				}
				// set next target cell
				target_cell = target_cell.next();
				// update current cell
				current_cell = current_cell.next();
			}

			// move the checkout snake of the second sub-unit
			if (target_cell && target_cell.length && current_cell && current_cell.length && current_cell.find('.vbo-tableaux-booking-checkout').length) {
				// make sure to move just the first check-out snake, because snakes with equal duration may have moved the check-out here already
				current_cell.find('.vbo-tableaux-booking-checkout').first().prependTo(target_cell);
			}

			// dispatch the event for the sub-units updated
			VBOCore.emitEvent('vbo-overv-sub-units-updated');
		},
		(err) => {
			alert(err.responseText);
			console.error(err.responseText);
		}
	);

	return true;
}

/**
 * Open a booking in a new tab.
 */
function vboOvervOpenBooking(bid) {
	var open_url = jQuery('.vbo-basenavuri-details').attr('href');
	open_url = open_url.replace('%d', bid);
	// navigate in a new tab
	window.open(open_url, '_blank');
}

/**
 * Resolves room assignment for a given booking.
 */
function vboOvervResolveRoomAssignment(bid, broomid) {
	// the moveset signature to apply, empty by default
	let movesetSignature = null;

	// the number of reassignment solutions found
	let solutionsFound = 0;

	// list of moveset signatures to skip
	let skipMovesets = [];

	// list of booking IDs to skip
	let skipBookings = [];

	// init modal body element
	let modalBody = null;

	// define the function to find a reassignment solution
	const findReassignmentFn = () => {
		// show loading
		VBOCore.emitEvent('vbo-loading-modal-overv-resolveroomassign');

		// make the request to get the bookings information
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.resolve_room_assignment'); ?>",
			{
				bid: bid,
				room_booking_id: broomid,
				skip_booking_ids: skipBookings,
				skip_moveset_signatures: skipMovesets,
			},
			(res) => {
				// stop loading
				VBOCore.emitEvent('vbo-loading-modal-overv-resolveroomassign');

				try {
					let obj_res = typeof res === 'string' ? JSON.parse(res) : res;

					// update moveset signature to apply
					movesetSignature = obj_res?.movesetSignature;

					// update number of solutions found so far
					solutionsFound = obj_res?.solutionsCount || solutionsFound;

					// append HTML content
					(modalBody[0] || modalBody).innerHTML = obj_res?.html;

					// show modal apply button group
					applyBtnGroup.style.display = '';
				} catch(e) {
					console.error(e);
					alert('Invalid response.');
				}
			},
			(err) => {
				// display error message
				alert(err.responseText || err);

				// dismiss modal
				VBOCore.emitEvent('vbo-dismiss-modal-overv-resolveroomassign');
			}
		);
	};

	// define the function to apply a moveset signature
	const applyReassignmentFn = (useSignature) => {
		// determine the signature to use
		useSignature = useSignature || movesetSignature;

		// show loading
		VBOCore.emitEvent('vbo-loading-modal-overv-resolveroomassign');

		// make the request to apply the moveset
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.apply_room_assignment'); ?>",
			{
				moveset: useSignature,
			},
			(res) => {
				// dismiss modal
				VBOCore.emitEvent('vbo-dismiss-modal-overv-resolveroomassign');

				// reload the page
				location.reload();
			},
			(err) => {
				// display error message
				alert(err.responseText || err);

				// stop loading
				VBOCore.emitEvent('vbo-loading-modal-overv-resolveroomassign');
			}
		);
	};

	// define the function to apply the reassignment "preview"
	const previewReassignmentFn = () => {
		if (!movesetSignature) {
			throw new Error('Empty moveset signature');
		}

		// store the moveset and its status to the admin-dock as temporary data
		VBOCore.getAdminDock().addTemporaryData(
			{
				id: '_tmp',
				persist_id: 'reassignmentmoveset',
				name: <?php echo json_encode(JText::translate('VBO_RESOLVE_ROOM_ASSIGNMENT')); ?>,
				icon: '<?php VikBookingIcons::e('random'); ?>',
				style: 'orange',
			},
			{
				moveset:      movesetSignature,
				skipMovesets: skipMovesets,
				skipBookings: skipBookings,
			}
		);

		// the "preview" will actually apply the moveset,
		// but it will be possible to undo the changes
		applyReassignmentFn();
	};

	// build modal buttons
	let cancelBtn = document.createElement('button');
	cancelBtn.setAttribute('type', 'button');
	cancelBtn.classList.add('btn');
	cancelBtn.textContent = Joomla.JText._('VBANNULLA');
	cancelBtn.addEventListener('click', () => {
		VBOCore.emitEvent('vbo-dismiss-modal-overv-resolveroomassign');
	});

	let applyBtn = document.createElement('button');
	applyBtn.setAttribute('type', 'button');
	applyBtn.classList.add('btn', 'btn-primary');
	applyBtn.textContent = <?php echo json_encode(JText::translate('VBAPPLY')); ?>;
	applyBtn.addEventListener('click', () => {
		// apply moveset to reassign room numbers
		applyReassignmentFn();
	});

	let actionsBtn = document.createElement('button');
	actionsBtn.setAttribute('type', 'button');
	actionsBtn.classList.add('btn', 'btn-primary');
	actionsBtn.innerHTML = '<?php VikBookingIcons::e('ellipsis-h'); ?>';

	// define the context menu for the actions button
	jQuery(actionsBtn).vboContextMenu({
		placement: 'top-right',
		buttons: [
			{
                class: 'btngroup',
                text: '',
                disabled: true,
                visible: function (root, event) {
                	// always overwrite title property
                	let baseText = <?php echo json_encode(JText::translate('VBO_NUM_SOLUTIONS_FOUND')); ?>;
                	this.text = baseText.replace('%d', solutionsFound);
                	return solutionsFound ? true : false;
                },
            },
			{
				class: 'vbo-context-menu-entry-secondary',
				text: <?php echo json_encode(JText::translate('VBO_SUGG_ANOTHER_SOL')); ?>,
				icon: '<?php echo VikBookingIcons::i('forward'); ?>',
				separator: false,
				action: (root, event) => {
					// suggest another solution, push current moveset to skip
					skipMovesets.push(movesetSignature);
					// find all bookings set to be excluded
					document.querySelectorAll('.vbo-room-reassignment-move-exclude[data-booking-id]').forEach((excludeEl) => {
						let excludeInput = excludeEl.querySelector('input[type="checkbox"][name*="skip_booking_id"]');
						if (excludeInput && excludeInput.checked) {
							// push booking ID to exclude
							skipBookings.push(excludeEl.getAttribute('data-booking-id'));
						}
					});
					// call function to find another reassignment solution
					findReassignmentFn();
				},
			},
			{
				class: 'vbo-context-menu-entry-secondary',
				text: <?php echo json_encode(JText::translate('VBOPREVIEW')); ?>,
				icon: '<?php echo VikBookingIcons::i('eye'); ?>',
				separator: true,
				action: (root, event) => {
					// call function to build the preview
					previewReassignmentFn();
				},
			},
			{
				class: 'vbo-context-menu-entry-secondary',
				text: <?php echo json_encode(JText::translate('VBO_RESTART')); ?>,
				icon: '<?php echo VikBookingIcons::i('play'); ?>',
				action: (root, event) => {
					// restart matrix search
					skipMovesets = [];
					skipBookings = [];
					// call function to restart finding a reassignment solution
					findReassignmentFn();
				},
			},
		],
	});

	let applyBtnGroup = document.createElement('div');
	applyBtnGroup.classList.add('btn-group', 'vbo-context-menu-btn-group', 'vbo-overv-resolveroomassign-actions');
	applyBtnGroup.style.display = 'none';
	applyBtnGroup.append(applyBtn);
	applyBtnGroup.append(actionsBtn);

	// display modal
	modalBody = VBOCore.displayModal({
		suffix:         'overv-resolveroomassign',
		extra_class:    'vbo-modal-rounded vbo-modal-tall',
		title:          <?php echo json_encode(JText::translate('VBO_RESOLVE_ROOM_ASSIGNMENT')); ?>,
		draggable:      true,
		escape_dismiss: false,
		footer_left:    cancelBtn,
		footer_right:   applyBtnGroup,
		dismiss_event:  'vbo-dismiss-modal-overv-resolveroomassign',
		loading_event:  'vbo-loading-modal-overv-resolveroomassign',
		loading_body:   '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>',
	});

	// call function to find a reassignment solution
	findReassignmentFn();
}

/**
 * DOM ready state
 */
jQuery(function() {
	// register to the event emitted when a new booking is created through an admin widget
	document.addEventListener('vbo_new_booking_created', (e) => {
		if (!e || !e.detail || !e.detail.hasOwnProperty('bid') || !e.detail['bid']) {
			// do nothing
			return;
		}
		// reload the page to display the new booking just created
		location.reload();
	});

	// register to the event dispatched when sub-units are moved
	document.addEventListener('vbo-overv-sub-units-updated', (e) => {
		// take care of the "unassigned" (missing sub-units) rows
		document
			.querySelectorAll('tr.vboverviewtablerow-subunit-unassigned')
			.forEach((row) => {
				let unassigned_cells = row.querySelectorAll('td.subroom-busy').length;
				if (!unassigned_cells) {
					try {
						// get rid of the alert element first
						let main_room_id = row.getAttribute('data-subroomid').split('-')[0];
						row
							.closest('table')
							.querySelector('tr.vboverviewtablerow[data-roomid="' + main_room_id + '"]')
							.querySelector('td.roomname')
							.querySelector('.vbo-overview-alert')
							.remove();
					} catch(e) {
						console.error(e);
					}

					// remove the unassigned row for this room-type as there are no more bookings
					row.remove();
				}
			});
	});

	/* calculate padding to increase draggable area and avoid "display: table" for the parent TD and "display: table-cell" for the draggable SPAN with 100% width and height, middle aligns */
	jQuery("td.vbo-checkinday span.vbo-draggable-sp").each(function(k, v) {
		var parentheight = jQuery(this).closest('td').height();
		var spheight = jQuery(this).height();
		var padsp = Math.floor((parentheight - spheight) / 2);
		jQuery(this).css({"padding": padsp + "px 0"});
	});
	
	/* Expand/Collapse tooltip */
	jQuery(document.body).on("click", ".vbo-overview-expandtoggle", function() {
		jQuery(this).closest('.vbo-overview-tipblock').toggleClass('vbo-overview-tipblock-expanded');
	});

	/* DnD Start */

	/* DnD Event dragstart */
	jQuery(document.body).on("dragstart", "td.vbo-checkinday span, td.vbo-checkinday .vbo-tableaux-booking-checkin", function(e) {
		// start dragging and prevent tooltip from popping up
		isdragging = true;
		if (hovtip === true) {
			hideVboTooltip();
		}

		let parentcell = jQuery(this).closest('td');
		if (parentcell.hasClass('busytmplock') || parentcell.hasClass('busy-sharedcalendar')) {
			return false;
		}

		// reset pool of booked date cells
		cellspool = [];

		let dt = e.originalEvent.dataTransfer;
		let bidstart = parentcell.attr('data-bids');
		let checkind = parentcell.attr('data-day');
		let roomid = parentcell.parent('tr').find('td').first().attr('data-roomid');
		if (!bidstart.length || !checkind.length || !roomid.length) {
			return false;
		}

		// build the drag preview to set
		let dragTarget = e.originalEvent.target;
		if (dragTarget && !dragTarget.matches('.vbo-tableaux-booking')) {
			// attempt to get the parent element
			dragTarget = dragTarget.closest('.vbo-tableaux-booking');
		}
		if (dragTarget && dragTarget.matches('.vbo-tableaux-booking')) {
			// build custom drag node
			let dragImage = dragTarget.cloneNode(true);
			dragImage.style.position = 'absolute';
			dragImage.style.top = '-9999px';
			dragImage.style.left = '-9999px';
			dragImage.style.width = getComputedStyle(dragTarget).width;
			dragImage.classList.add('vbo-booking-drag-preview');

			// temporarily append the drag node to the body
			document.body.appendChild(dragImage);

			// use the drag node as drag preview
			dt.setDragImage(dragImage, 10, 10);

			// remove drag node from body
			setTimeout(() => {
				document.body.removeChild(dragImage);
			}, 0);
		}

		// count booking nights and set booked date cells
		let totnights = countBookingCells(parentcell, bidstart, roomid);

		// add dragging class
		if (jQuery(this).find('.vbo-tableaux-guest-name').length) {
			jQuery(this).find('.vbo-tableaux-guest-name').addClass('vbo-dragging-sp');
		} else {
			if (!jQuery(this).hasClass('vbo-tableaux-booking-avatar')) {
				jQuery(this).addClass('vbo-dragging-sp');
			}
		}

		// check if the sub-unit temporary row should be displayed
		let room_id_int = Math.abs(parseInt(roomid));
		if (!isNaN(room_id_int) && room_id_int) {
			document
				.querySelectorAll('.vboverviewtablerow-subunit-tmp[data-subroomid="' + room_id_int + '-0"]')
				.forEach((room_tmp_row) => {
					// display possibly hidden row with a small delay in order to not break dragging on some browsers
					setTimeout(() => {
						room_tmp_row.style.display = 'table-row';
					}, 100);
				});
		}

		// populate dataTransfer data
		let parent_rid = '';
		let subunit_id = '';
		if (parentcell.hasClass('subroom-busy')) {
			let subdata = parentcell.parent('tr').attr('data-subroomid').split('-');
			if (!subdata[1] && subdata[2]) {
				// negative index split, probably for the unassigned row
				subdata[1] = '-' + subdata[2];
			}
			parent_rid = subdata[0];
			subunit_id = subdata[1];
		}
		dt.setData('Checkin', checkind);
		dt.setData('Roomid', roomid);
		dt.setData('Bid', bidstart);
		dt.setData('Nights', totnights);
		dt.setData('Parentrid', parent_rid);
		dt.setData('Subunit', subunit_id);
	});

	/* DnD Events dragenter dragover drop */
	jQuery(document.body).on("dragenter dragover drop", "td.notbusy, td.busy, td.subnotbusy, td.subroom-busy", function(e) {
		// always prevent default
		e.preventDefault();

		// gather dragging cell data
		let dt = e.originalEvent.dataTransfer;
		let checkind = dt.getData('Checkin');
		let roomid = dt.getData('Roomid');
		let bid = dt.getData('Bid');
		let totnights = dt.getData('Nights');
		let parent_rid = dt.getData('Parentrid');
		let subunit_id = dt.getData('Subunit');

		// check if we are hovering something that requires styling
		if (e.type === 'dragover') {
			let hover_cell = jQuery(this);
			if (hover_cell.hasClass('subroom-busy') && hover_cell.hasClass('vbo-checkinday')) {
				if (hover_cell.attr('data-day') != checkind && totnights > 1) {
					hover_cell.find('.vbo-tableaux-booking-checkin').addClass('vbo-droppable-cell');
				}
			}
			// do not proceed any further
			return;
		}

		// only look for drop
		if (e.type !== 'drop') {
			// ignore if we are not dropping the element
			return;
		}

		// always reset pool of cells
		newcellspool = [];

		/* check if drop is allowed */
		let landrow = jQuery(this).closest('tr');
		let landrid = landrow.find('td').first().attr('data-roomid');
		let landbids = jQuery(this).attr('data-bids');
		if (landrid === undefined || !landrid.length) {
			return false;
		}

		if (jQuery(this).hasClass('busy') && !jQuery(this).hasClass('vbo-partially')) {
			if (bid != landbids) {
				// landed on an occupied date but not for the same booking ID so drop is not allowed on this day
				return false;
			}
		}

		// build pool of cells
		let freenights = countCellFreeNights(jQuery(this), landrid, totnights, bid);
		if (freenights < totnights) {
			alert(Joomla.JText._('VBOVWDNDERRNOTENCELLS').replace('%s', freenights).replace('%d', totnights));
			return false;
		}

		// collect the new dates
		let nowdatefrom = jQuery(newcellspool[0]).attr('data-day');
		let nowdateto = jQuery(newcellspool[(newcellspool.length -1)]).attr('data-day');

		// check if we are just moving a sub-unit onto another for the same dates
		if (jQuery(this).hasClass('subnotbusy') || jQuery(this).hasClass('subroom-busy')) {
			// dropping on a sub-unit is only allowed onto the same room ID, for the same dates (switch room index only)
			let subdata = landrow.attr('data-subroomid').split('-');
			if (!subdata[1] && subdata[2]) {
				// negative index split, probably for the unassigned row
				subdata[1] = '-' + subdata[2];
			}
			if (!subdata || !parent_rid || !subunit_id || parent_rid != subdata[0] || !subdata[1] || subunit_id == subdata[1]) {
				// display proper error
				if (subdata[1] && subunit_id == subdata[1]) {
					// must use the same dates for sub-units
					alert(Joomla.JText._('VBO_DRAG_SUBUNITS_SAMEDATE'));
				} else {
					// only sub-units can be moved onto rows for sub-units
					alert(Joomla.JText._('VBO_ERR_SUBUN_MOVE_SUBUN'));
				}
				return false;
			}

			// sub-unit drag&drop is only allowed on the same date to change sub-unit index
			if (nowdatefrom != checkind) {
				alert(Joomla.JText._('VBO_DRAG_SUBUNITS_SAMEDATE'));
				return false;
			}

			// attempt to perform the switch-room-index request
			if (jQuery(this).hasClass('subnotbusy')) {
				// move sub-unit to a different index
				if (!vboMoveSubunit(bid.replaceAll('-', ''), parent_rid, subunit_id, subdata[1], checkind)) {
					return false;
				}
			} else {
				// dropped on an occupied sub-unit, so ask for swap confirmation
				if (!vboSwapRoomSubunits(bid.replaceAll('-', ''), landbids.replaceAll('-', ''), parent_rid, subunit_id, subdata[1], checkind)) {
					return false;
				}
			}

			// do not proceed
			return;
		}

		/* populate temporary class for the new cells */
		jQuery(cellspool).each(function(k, v) {
			v.addClass('vbo-dragging-cells-tmp');
		});

		// bookings for multiple rooms should add the same dragging class also to the booking for the other rooms
		let samebidcells = jQuery("td.vbo-checkinday[data-bids='" + bid + "']");
		if (samebidcells.length > 1) {
			jQuery("td[data-bids='" + bid + "']").each(function(k, v) {
				if (!jQuery(v).hasClass('vbo-dragging-cells-tmp')) {
					jQuery(v).addClass('vbo-dragging-cells-tmp');
				}
			});
		}
		jQuery(newcellspool).each(function(k, v) {
			v.addClass('vbo-dragged-cells-tmp');
		});

		/* populate and showing loading message */
		jQuery('.vbo-loading-dnd-head').text(Joomla.JText._('VBOVWDNDMOVINGBID').replace('%d', bid));
		let movingmess = '';
		if (roomid != landrid) {
			movingmess += Joomla.JText._('VBOVWDNDMOVINGROOM').replace('%s', jQuery("td[data-roomid='"+landrid+"']").first().find('span.vbo-overview-roomname').text() );
		}
		if (nowdatefrom != jQuery(cellspool[0]).attr('data-day') || nowdateto != jQuery(cellspool[(cellspool.length -1)]).attr('data-day')) {
			if (roomid != landrid) {
				movingmess += ', ';
			}
			movingmess += Joomla.JText._('VBOVWDNDMOVINGDATES').replace('%s', nowdatefrom + ' - ' + nowdateto);
		}

		// hide tooltip, if any
		hideVboTooltip();

		// display moving message
		jQuery('.vbo-loading-dnd-body').text(movingmess);
		jQuery('.vbo-info-overlay-block').fadeIn();
		jQuery('.vbo-loading-dnd-footer').show();

		/* fire the Ajax request after 1.5 seconds just for giving visibility to the loading message */
		setTimeout(function() {
			doAlterBooking(bid, roomid, landrid);
		}, 1500);
	});

	/* DnD Event dragend: remove class, attribute and dataTransfer Data if drag ends on the same position as dragstart or onto an invalid date */
	jQuery(document.body).on("dragend", "td.vbo-checkinday span, td.vbo-checkinday .vbo-tableaux-booking-checkin", function(e) {
		// stop dragging to restore tooltip functions
		isdragging = false;
		// we could also access the originally dragged cell
		let dt = e.originalEvent.dataTransfer;
		// restore temporary classes
		jQuery('.vbo-dragging-sp').removeClass('vbo-dragging-sp');
		jQuery('.vbo-droppable-cell').removeClass('vbo-droppable-cell');
	});
	/* DnD End */

	/* Show New Reservation Form & Modal with bookings - Start */
	jQuery('td.busy, td.notbusy').dblclick(function() {
		var curday = jQuery(this).attr('data-day');
		var roomid = jQuery(this).parent('tr').find('td.roomname').attr('data-roomid');
		roomid = roomid && roomid.length ? roomid : '';
		last_room_click = '';
		last_date_click = '';
		next_date_click = '';
		vboCallOpenJModal('vbo-new-res', 'index.php?option=com_vikbooking&task=calendar&cid[]='+roomid+'&checkin='+curday+'&overv=1&tmpl=component', {room_id: roomid, checkin: curday});
	});

	jQuery(document.body).on('click', 'td.busy, td.notbusy, td.busytmplock, td.subroom-busy', function(e) {
		if (jQuery(this).hasClass('vbo-widget-booskcal-cell-mday')) {
			return;
		}

		if (jQuery(this).hasClass('busy') || jQuery(this).hasClass('busytmplock') || jQuery(this).hasClass('subroom-busy')) {
			// make sure the clicked target is not inside the tooltip
			if (e && e.target) {
				if (jQuery(e.target).closest('.vbo-overview-tipblock').length) {
					// abort when click originated from the tooltip
					return;
				}
				if (jQuery(e.target).is('a')) {
					// abort when click is made on a link (View choosebusy)
					return;
				}
				if (jQuery(e.target).hasClass('vbo-roomdaynote-display') || jQuery(e.target).hasClass('vbo-roomdaynote-trigger')) {
					// abort when click is made on a room-day note
					return;
				}
			}

			// trigger mouseleave event to prevent tooltip from showing
			jQuery(this).trigger('mouseleave');

			// get room name, date and day-bids
			let room_name = '';
			let sub_room_data = '';
			let room_id = 0;
			if (jQuery(this).hasClass('subroom-busy')) {
				sub_room_data = jQuery(this).parent('tr').attr('data-subroomid');
				room_id = sub_room_data.split('-')[0];
				room_name = jQuery('td.roomname[data-roomid="' + room_id + '"]').not('.subroomname').first().find('.vbo-overview-roomname').text();
			} else {
				let main_room_cell = jQuery(this).parent('tr').find('td.roomname');
				room_id = main_room_cell.attr('data-roomid');
				room_name = main_room_cell.find('.vbo-overview-roomname').text();
			}
			let date_ymd  = jQuery(this).attr('data-day');
			let date_read = jQuery('.bluedays[data-ymd="' + date_ymd + '"]').attr('data-readymd');
			let date_bids = jQuery(this).attr('data-bids');
			let date_ubk  = jQuery(this).attr('data-units-booked');
			let date_ulft = jQuery(this).attr('data-units-left');
			let def_bicon = '<?php VikBookingIcons::e('user', 'vbo-dashboard-guest-activity-avatar-icon'); ?>';
			let closure_i = '<?php VikBookingIcons::e('ban', 'vbo-dashboard-guest-activity-avatar-icon'); ?>';

			let sub_room_index = sub_room_data ? (sub_room_data.split('-')[1] || '') : '';

			// build new reservation button
			let newResBtn = null;
			if (date_ubk > 1 || date_ulft > 1 || sub_room_data) {
				newResBtn = document.createElement('button');
				newResBtn.setAttribute('type', 'button');
				newResBtn.classList.add('btn', 'btn-primary');
				newResBtn.textContent = <?php echo json_encode(JText::translate('VBOSHOWQUICKRES')); ?>;
				newResBtn.addEventListener('click', () => {
					// dismiss modal
					VBOCore.emitEvent('vbo-dismiss-modal-overv-rdaybookings');
					// create new reservation
					VBOCore.handleDisplayWidgetNotification({
						widget_id: 'bookings_calendar',
					}, {
						id_room: room_id,
						offset: date_ymd,
						day: date_ymd,
						newbook: 1,
					});
				});
			}

			// display modal with booking details
			let rday_bookings_modal_body = VBOCore.displayModal({
				suffix: 	   'overv-rdaybookings',
				extra_class:   'vbo-modal-rounded vbo-modal-tall' + (!newResBtn ? ' vbo-modal-nofooter' : ''),
				title: 		   room_name + (sub_room_index ? ' #' + sub_room_index : '') + ' - ' + date_read,
				draggable:     true,
				footer_right:  newResBtn,
				dismiss_event: 'vbo-dismiss-modal-overv-rdaybookings',
				loading_event: 'vbo-loading-modal-overv-rdaybookings',
				loading_body:  '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>',
			});

			// show loading
			VBOCore.emitEvent('vbo-loading-modal-overv-rdaybookings');

			// make the request to get the bookings information
			VBOCore.doAjax(
				"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=getbookingsinfo'); ?>",
				{
					status: 'any',
					idroom: room_id,
					stay_date: date_ymd,
					idorders: date_bids,
					subroom: sub_room_data,
					sharedcal: (jQuery(this).hasClass('busy-sharedcalendar') ? 1 : 0),
					tmpl: 'component'
				},
				(res) => {
					// stop loading
					VBOCore.emitEvent('vbo-loading-modal-overv-rdaybookings');
					try {
						var obj_res = typeof res === 'string' ? JSON.parse(res) : res;

						// build the HTML response nodes
						var rday_bookings_wrap = jQuery('<div></div>').addClass('vbo-dashboard-guests-latest');
						var rday_bookings_list = jQuery('<div></div>').addClass('vbo-dashboard-guest-messages-list');

						// flag to indicate if the button to toggle the cancelled reservations was displayed
						var show_canc_res_flag = false;

						// loop through all bookings
						for (var b in obj_res) {
							if (!obj_res.hasOwnProperty(b)) {
								continue;
							}
							// nights and guests
							var nights_guests = [
								obj_res[b]['roomsnum'] + ' ' + Joomla.JText._((obj_res[b]['roomsnum'] > 1 ? 'VBPVIEWORDERSTHREE' : 'VBEDITORDERTHREE')),
								obj_res[b]['days'] + ' ' + Joomla.JText._((obj_res[b]['days'] > 1 ? 'VBDAYS' : 'VBDAY')),
								obj_res[b]['tot_adults'] + ' ' + Joomla.JText._((obj_res[b]['tot_adults'] > 1 ? 'VBMAILADULTS' : 'VBMAILADULT'))
							];
							if (obj_res[b]['tot_children'] > 0) {
								nights_guests.push(obj_res[b]['tot_children'] + ' ' + Joomla.JText._((obj_res[b]['tot_children'] > 1 ? 'VBMAILCHILDREN' : 'VBMAILCHILD')));
							}

							// OTA booking ID
							var ota_bid_info = '';
							if (obj_res[b].hasOwnProperty('idorderota') && obj_res[b].hasOwnProperty('channel') && obj_res[b]['idorderota'] && obj_res[b]['channel']) {
								if (obj_res[b]['idorderota'].length <= 16) {
									// try to display the information only for API channels
									ota_bid_info = '<span class="label label-info">' + obj_res[b]['idorderota'] + '</span> ';
								}
							}

							// the separator between confirmed and cancelled reservations
							var res_type_separator = null;

							// booking status and badge
							var badge_type = 'warning';
							if (obj_res[b]['status'] == 'confirmed') {
								badge_type = 'success';
							} else if (obj_res[b]['status'] == 'cancelled') {
								badge_type = 'danger';
								if (!show_canc_res_flag) {
									// build the separator between confirmed and cancelled reservations to toggle the latter ones
									res_type_separator = jQuery('<div></div>').addClass('vbo-bookings-status-separator');
									var btn_separator = jQuery('<button></button>').attr('type', 'button').addClass('btn btn-small btn-secondary').text(Joomla.JText._('VBO_SHOW_CANCELLATIONS'));
									btn_separator.on('click', function() {
										jQuery(this).closest('.vbo-dashboard-guest-messages-list').find('[data-type="cancelled"]').toggle();
									});
									res_type_separator.append(btn_separator);
									// turn flag on at the very first cancelled reservation found
									show_canc_res_flag = true;
								}
							}

							// build main booking node
							var rday_booking = jQuery('<div></div>').addClass('vbo-dashboard-guest-activity vbo-w-guestmessages-message');
							if (badge_type == 'danger') {
								// hide cancelled booking by default and set proper attribute
								rday_booking.attr('data-type', 'cancelled').css('display', 'none');
							}
							rday_booking.attr('data-idorder', obj_res[b]['id']);
							rday_booking.on('click', function(e) {
								var click_target = jQuery(e.target);
								if (click_target.is('a') || click_target.is('select') || click_target.is('option') || click_target.is('button')) {
									return;
								}
								if ((click_target.is('span') && click_target.hasClass('label')) || click_target.is('img') || click_target.is('i')) {
									// open booking details within the apposite admin-widget
									e.stopPropagation();
									VBOCore.handleDisplayWidgetNotification({
										widget_id: 'booking_details',
									}, {
										booking_id: jQuery(this).attr('data-idorder'),
									});
								} else {
									// open booking details on a new tab
									vboOvervOpenBooking(jQuery(this).attr('data-idorder'));
								}
							});

							// build booking structure
							var rday_booking_html = '';
							rday_booking_html += '<div class="vbo-dashboard-guest-activity-avatar">' + "\n";
							if (obj_res[b]['avatar_src']) {
								rday_booking_html += '<img class="vbo-dashboard-guest-activity-avatar-profile" src="' + obj_res[b]['avatar_src'] + '" alt="' + obj_res[b]['avatar_alt'] + '" decoding="async" loading="lazy" />' + "\n";
							} else if (obj_res[b]['closure']) {
								rday_booking_html += closure_i + "\n";
							} else {
								rday_booking_html += def_bicon + "\n";
							}
							rday_booking_html += '</div>' + "\n";
							rday_booking_html += '<div class="vbo-dashboard-guest-activity-content">' + "\n";
							rday_booking_html += '	<div class="vbo-dashboard-guest-activity-content-head">' + "\n";
							rday_booking_html += '		<div class="vbo-dashboard-guest-activity-content-info-details">' + "\n";
							rday_booking_html += '			<h4 class="vbo-w-guestmessages-message-gtitle">' + (!obj_res[b]['closure'] ? obj_res[b]['cinfo'] : obj_res[b]['closure_txt']) + '</h4>' + "\n";
							rday_booking_html += '			<div class="vbo-dashboard-guest-activity-content-info-icon">' + "\n";
							rday_booking_html += '				<span class="badge badge-info">' + obj_res[b]['id'] + '</span> ' + "\n";
							if (obj_res[b]['type'] && obj_res[b]['type'] == 'overbooking') {
								rday_booking_html += '			<span class="label label-error vbo-label-overbooking">' + Joomla.JText._('VBO_BTYPE_OVERBOOKING') + '</span>' + "\n";
							}
							rday_booking_html += '				<span class="badge badge-' + badge_type + '">' + obj_res[b]['status_lbl'] + '</span>' + "\n";
							rday_booking_html += '				<span class="vbo-w-guestmessages-message-staydates">' + "\n";
							rday_booking_html += '					<?php VikBookingIcons::e('calendar-alt'); ?>' + "\n";
							rday_booking_html += '					<span class="vbo-w-guestmessages-message-staydates-in">' + obj_res[b]['checkin_short'] + '</span>' + "\n";
							rday_booking_html += '					<span class="vbo-w-guestmessages-message-staydates-sep">-</span>' + "\n";
							rday_booking_html += '					<span class="vbo-w-guestmessages-message-staydates-out">' + obj_res[b]['checkout_short'] + '</span>' + "\n";
							rday_booking_html += '				</span>' + "\n";
							rday_booking_html += '			</div>' + "\n";
							rday_booking_html += '		</div>' + "\n";
							rday_booking_html += '		<div class="vbo-dashboard-guest-activity-content-info-date">' + "\n";
							rday_booking_html += '			<span>' + obj_res[b]['book_date'] + '</span>' + "\n";
							rday_booking_html += '			<span>' + obj_res[b]['book_time'] + '</span>' + "\n";
							if (obj_res[b].hasOwnProperty('meals_included')) {
								rday_booking_html += '		<div class="vbo-wider-badges-wrap">' + "\n";
								obj_res[b]['meals_included'].forEach((meal_enum) => {
									rday_booking_html += '		<div class="badge badge-info">' + meal_enum + '</div>' + "\n";
								});
								rday_booking_html += '		</div>' + "\n";
							}
							rday_booking_html += '		</div>' + "\n";
							rday_booking_html += '	</div>' + "\n";
							rday_booking_html += '	<div class="vbo-dashboard-guest-activity-content-info-msg">' + "\n";
							rday_booking_html += '		<div>' + ota_bid_info + '<?php VikBookingIcons::e('bed'); ?> ' + nights_guests.join(', ') + '</div>' + "\n";
							if (obj_res[b].hasOwnProperty('sub_units_data')) {
								rday_booking_html += '	<div class="vbo-rdaybooking-subunits">' + "\n";
								for (let sub_rname in obj_res[b]['sub_units_data']) {
									if (!obj_res[b]['sub_units_data'].hasOwnProperty(sub_rname)) {
										continue;
									}
									rday_booking_html += '<span class="label label-success">' + (obj_res[b]['roomsnum'] > 1 ? sub_rname + ' ' : '') + '#' + obj_res[b]['sub_units_data'][sub_rname] + '</span>' + "\n";
								}
								rday_booking_html += '	</div>' + "\n";
							} else if (obj_res[b].hasOwnProperty('missing_index') && obj_res[b]['missing_index']) {
								rday_booking_html += '	<div class="vbo-rdaybooking-subunits">' + "\n";
								rday_booking_html += '		<span class="label label-warning">' + Joomla.JText._('VBO_MISSING_SUBUNIT') + '</span>' + "\n";
								if (obj_res[b].hasOwnProperty('av_room_indexes')) {
									// build drop downs to set the sub-unit to each room with no data
									rday_booking_html += '	<div class="vbo-rdaybooking-subunits-list">' + "\n";
									for (var av_rindex in obj_res[b]['av_room_indexes']) {
										if (!obj_res[b]['av_room_indexes'].hasOwnProperty(av_rindex) || !obj_res[b]['av_room_indexes'][av_rindex].hasOwnProperty('name') || !obj_res[b]['av_room_indexes'][av_rindex].hasOwnProperty('list')) {
											continue;
										}
										var set_subunit_node = '<select onchange="vboSetSubunit(\'' + obj_res[b]['id'] + '\', \'' + obj_res[b]['av_room_indexes'][av_rindex]['rid'] + '\', \'' + av_rindex + '\', this.value);">' + "\n";
										set_subunit_node += '<option value="">' + obj_res[b]['av_room_indexes'][av_rindex]['name'] + '</option>' + "\n";
										for (var rindex_k in obj_res[b]['av_room_indexes'][av_rindex]['list']) {
											if (!obj_res[b]['av_room_indexes'][av_rindex]['list'].hasOwnProperty(rindex_k)) {
												continue;
											}
											set_subunit_node += '<option value="' + rindex_k + '">' + obj_res[b]['av_room_indexes'][av_rindex]['list'][rindex_k] + '</option>' + "\n";
										}
										set_subunit_node += '</select>' + "\n";
										// append drop down
										rday_booking_html += set_subunit_node;
									}
									rday_booking_html += '	</div>' + "\n";
								} else {
									// allow the system to relocate the room booking record
									rday_booking_html += '	<div class="vbo-rdaybooking-subunits-list vbo-rdaybooking-subunit-relocate-wrap">' + "\n";
									rday_booking_html += '		<button type="button" class="btn vbo-config-btn vbo-reassign-room-unit-btn" data-bid="' + obj_res[b]['id'] + '"><?php VikBookingIcons::e('random'); ?> ' + <?php echo json_encode(JText::translate('VBO_RESOLVE_ROOM_ASSIGNMENT')); ?> + '</button>' + "\n";
									rday_booking_html += '	</div>' + "\n";
								}
								rday_booking_html += '	</div>' + "\n";
							}
							rday_booking_html += '	</div>' + "\n";
							rday_booking_html += '</div>' + "\n";

							// set booking HTML to node
							rday_booking.append(rday_booking_html);

							if (res_type_separator !== null) {
								// append the separator to toggle the cancelled reservations
								rday_bookings_list.append(res_type_separator);
							}

							// append booking node to list
							rday_bookings_list.append(rday_booking);
						}

						// finalize response nodes
						rday_bookings_wrap.append(rday_bookings_list);

						// append the response
						rday_bookings_modal_body.append(rday_bookings_wrap);
					} catch(err) {
						console.error('could not parse JSON response', err, res);
						alert('Could not parse JSON response');
					}
				},
				(err) => {
					// stop loading and display alert message
					VBOCore.emitEvent('vbo-loading-modal-overv-rdaybookings');
					console.error(err);
					alert(err.responseText);
				}
			);
		}

		if (jQuery(this).hasClass('busy') && !jQuery(this).hasClass('vbo-partially')) {
			// abort when fully booked date
			return;
		}

		if (jQuery(this).hasClass('busytmplock') || jQuery(this).hasClass('subroom-busy')) {
			// abort when pending record or sub-room
			return;
		}

		// update clicked dates info
		var curday = jQuery(this).attr('data-day');
		var roomid = jQuery(this).parent('tr').find('td.roomname').attr('data-roomid');
		roomid = roomid && roomid.length ? roomid : '';
		last_room_click = roomid;
		if (!last_date_click) {
			last_date_click = curday;
		} else if (last_date_click && next_date_click) {
			last_date_click = curday;
			next_date_click = '';
		} else {
			var from_info = new Date(last_date_click);
			var to_info = new Date(curday);
			if (from_info.getTime() < to_info.getTime()) {
				next_date_click = curday;
			} else {
				last_date_click = curday;
				next_date_click = '';
			}
		}

		// button shake effect
		jQuery('.vbo-ovrv-flt-butn').addClass('vbo-ovrv-flt-butn-shake');
		setTimeout(function() {
			jQuery('.vbo-ovrv-flt-butn').removeClass('vbo-ovrv-flt-butn-shake');
		}, 1000);
	});
	/* Show New Reservation Form & Modal with bookings - End */

	/* Hover Tooltip Start */
	jQuery('td.busy, td.busytmplock, td.subroom-busy').not('.vbo-widget-booskcal-cell-mday').hover(function() {
		registerHoveringTooltip(this);
	}, unregisterHoveringTooltip);

	jQuery(document.body).on('click', '.vbo-overview-tip-bctag', function() {
		if (!jQuery(this).parent().find(".vbo-overview-tip-bctag-subtip").length) {
			jQuery(".vbo-overview-tip-bctag-subtip").remove();
			var cur_color = jQuery(this).attr("data-ctagcolor");
			var cur_bid = jQuery(this).attr("data-bid");
			jQuery(this).after("<div class=\"vbo-overview-tip-bctag-subtip\">"+bctags_tip+"</div>");
			jQuery(this).parent().find(".vbo-overview-tip-bctag-subtip").find(".vbo-overview-tip-bctag-subtip-circle[data-ctagcolor='"+cur_color+"']").addClass("vbo-overview-tip-bctag-activecircle").css('border-color', cur_color);
			jQuery(this).parent().find(".vbo-overview-tip-bctag-subtip").find(".vbo-overview-tip-bctag-subtip-circle").attr('data-bid', cur_bid);
			jQuery(".vbo-overview-tip-bctag-subtip .hasTooltip").tooltip();
		} else {
			jQuery(".vbo-overview-tip-bctag-subtip").remove();
		}
	});

	var applying_tag = false;

	jQuery(document.body).on('click', '.vbo-overview-tip-bctag-subtip-circle', function() {
		if (applying_tag === true) {
			return false;
		}
		applying_tag = true;
		var clickelem = jQuery(this);
		var ctagkey = clickelem.attr('data-ctagkey');
		var bid = clickelem.attr('data-bid');
		// set opacity to circles as loading
		jQuery('.vbo-overview-tip-bctag-subtip-circle').css('opacity', '0.6');

		// perform the request
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=setbookingtag'); ?>",
			{
				tmpl: "component",
				idorder: bid,
				tagkey: ctagkey
			},
			(res) => {
				applying_tag = false;
				if (res.indexOf('e4j.error') >= 0 ) {
					alert(res.replace("e4j.error.", ""));
					// restore loading opacity in circles
					jQuery('.vbo-overview-tip-bctag-subtip-circle').css('opacity', '1');
				} else {
					// decode the response, if needed
					let obj_res = typeof res === 'string' ? JSON.parse(res) : res;
					// check view mode
					if ('<?php echo $pbmode; ?>' === 'tags') {
						// apply new color to all booking cells
						document
							.querySelectorAll('.vbo-hascolortag[data-lastbid="' + bid + '"]')
							.forEach((bookingCell) => {
								bookingCell.style.backgroundColor = obj_res.color;
								let bookingCellLink = bookingCell.querySelector('a');
								if (bookingCellLink) {
									bookingCellLink.style.color = obj_res.fontcolor;
								}
							});
					}
					// hide tooltip
					hideVboTooltip();
				}
			},
			(err) => {
				applying_tag = false;
				alert("Request Failed");
				// restore loading opacity in circles
				jQuery('.vbo-overview-tip-bctag-subtip-circle').css('opacity', '1');
			}
		);
	});

	jQuery(document).keydown(function(e) {
		if ( e.keyCode == 27 ) {
			if (hovtip === true) {
				hideVboTooltip();
			}
			if (vbodialogorph_on === true) {
				hideVboDialogOverv(1);
			}
		}
	});

	jQuery(document).mouseup(function(e) {
		if (!hovtip && !vbodialogorph_on) {
			return;
		}
		if (hovtip) {
			var vbo_overlay_cont = jQuery(".vbo-overview-tipblock");
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				hideVboTooltip();
				return;
			}
			if (jQuery(".vbo-overview-tip-bctag-subtip").length) {
				var vbo_overlay_subtip_cont = jQuery(".vbo-overview-tip-bctag-wrap");
				if (!vbo_overlay_subtip_cont.is(e.target) && vbo_overlay_subtip_cont.has(e.target).length === 0) {
					jQuery(".vbo-overview-tip-bctag-subtip").remove();
					return;
				}
			}
		}
		if (vbodialogorph_on) {
			var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				hideVboDialogOverv(1);
			}
		}
	});
	/* Hover Tooltip End */

	/* Toggle Sub-units */
	jQuery('.vbo-overview-subroom-toggle').click(function() {
		var roomid = jQuery(this).closest('td').attr('data-roomid');
		if (jQuery(this).hasClass('vbo-overview-subroom-toggle-active')) {
			jQuery("td.roomname[data-roomid='" + roomid + "']").find('span.vbo-overview-subroom-toggle').removeClass('vbo-overview-subroom-toggle-active').find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
			// do not use .hide() or "display: none" may not work due to forced "display: table-row"
			jQuery("td.subroomname[data-roomid='-" + roomid + "']").parent('tr').css('display', 'none');
			// update storage object
			var storage_subunits_status = VBOCore.storageGetItem('vbo_overv_subunits_status');
			try {
				storage_subunits_status = JSON.parse(storage_subunits_status);
				storage_subunits_status = storage_subunits_status || {};
			} catch(e) {
				storage_subunits_status = {};
			}
			storage_subunits_status[roomid] = 0;
			VBOCore.storageSetItem('vbo_overv_subunits_status', storage_subunits_status);
		} else {
			jQuery("td.roomname[data-roomid='" + roomid + "']").find('span.vbo-overview-subroom-toggle').addClass('vbo-overview-subroom-toggle-active').find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
			// do not use .show() or "display: block" may be added rather than "display: table-row"
			jQuery("td.subroomname[data-roomid='-" + roomid + "']").parent('tr').not('.vboverviewtablerow-subunit-tmp').css('display', 'table-row');
			// update storage object
			var storage_subunits_status = VBOCore.storageGetItem('vbo_overv_subunits_status');
			try {
				storage_subunits_status = JSON.parse(storage_subunits_status);
				storage_subunits_status = storage_subunits_status || {};
			} catch(e) {
				storage_subunits_status = {};
			}
			storage_subunits_status[roomid] = 1;
			VBOCore.storageSetItem('vbo_overv_subunits_status', storage_subunits_status);
		}
	});

	/* Display sub-units based on storage status */
	var storage_subunits_defstatus = VBOCore.storageGetItem('vbo_overv_subunits_status');
	try {
		storage_subunits_defstatus = JSON.parse(storage_subunits_defstatus);
		storage_subunits_defstatus = storage_subunits_defstatus || {};
		for (let rid in storage_subunits_defstatus) {
			if (!storage_subunits_defstatus.hasOwnProperty(rid)) {
				continue;
			}
			if (storage_subunits_defstatus[rid]) {
				// trigger click event to display the sub-units
				jQuery('td.roomname[data-roomid="' + rid + '"]').find('.vbo-overview-subroom-toggle').trigger('click');
			}
		}
	} catch(e) {
		// do nothing
	}

	/* Default state for sticky table heads */
	vboToggleStickyTableHeaders(<?php echo $long_list && $cookie_sticky_heads != 'off' ? 1 : 0; ?>);

	/**
	 * Check orphans (only if not disabled with the cookie).
	 * 
	 * @deprecated 	1.18.0 (J) - 1.8.0 (WP) disabled with the "false" condition below.
	 */
	var hideorphans = false;
	var buiscuits = document.cookie;
	if (buiscuits.length) {
		var hideorphansck = "vboHideOrphans=1";
		if (buiscuits.indexOf(hideorphansck) >= 0) {
			hideorphans = true;
		}
	}
	if (false && !hideorphans && <?php echo $vbo_auth_pricing ? 'true' : 'false'; ?>) {
		// make the request

		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=orphanscount'); ?>",
			{
				from: '<?php echo date($df, $tsstart); ?>',
				months: <?php echo $mnum; ?>,
				tmpl: "component"
			},
			(res) => {
				var obj_res = typeof res === 'string' ? JSON.parse(res) : res;
				var orphans_list = '';
				for (var rid in obj_res) {
					if (!obj_res.hasOwnProperty(rid)) {
						continue;
					}
					orphans_list += '<div class="vbo-orphans-info-room">';
					orphans_list += '	<h4 class="vbo-orphans-roomname">'+obj_res[rid]['name']+'</h4>';
					orphans_list += '	<div class="vbo-orphans-info-dates">';
					for (var dind in obj_res[rid]['rdates']) {
						if (!obj_res[rid]['rdates'].hasOwnProperty(dind)) {
							continue;
						}
						orphans_list += '	<div class="vbo-orphans-info-date">'+obj_res[rid]['rdates'][dind]+'</div>';
					}
					orphans_list += '	</div>';
					orphans_list += '	<div class="vbo-orphans-info-btn">';
					orphans_list += '		<a href="<?php echo $file_base; ?>?option=com_vikbooking&task=ratesoverv&cid[]='+rid+'&startdate='+obj_res[rid]['linkd']+'" class="btn btn-primary" target="_blank"><?php echo addslashes(JText::translate('VBORPHANSCHECKBTN')); ?></a>';
					orphans_list += '	</div>';
					orphans_list += '</div>';
				}
				if (orphans_list.length) {
					// show the modal
					jQuery('.vbo-orphans-info-list').html(orphans_list);
					jQuery('.vbo-orphans-overlay-block').fadeIn();
					vbodialogorph_on = true;
				}
			},
			(err) => {
				console.error("orphanscount Request Failed");
			}
		);
	}

	// fests
	jQuery(document.body).on("click", "th.bluedays", function() {
		if (jQuery(this).hasClass('skip-bluedays-click')) {
			return;
		}
		var ymd = jQuery(this).attr('data-ymd');
		var daytitle = jQuery(this).attr('data-readymd');
		if (jQuery(this).hasClass('vbo-overv-festcell')) {
			// cell has fests
			if (!vboFests.hasOwnProperty(ymd)) {
				return;
			}
			vboRenderFests(ymd, daytitle);
		} else {
			// let the admin create a new fest

			// update ymd key for the selected date, useful for adding new fests
			jQuery('.vbo-overlay-fests-addnew').attr('data-ymd', ymd);

			// unset content and display modal for just adding a new fest
			jQuery('.vbo-overlay-fests-list').html('');
			var fests_modal = VBOCore.displayModal({
				suffix: 	   'overv-mng-fests',
				extra_class:   'vbo-modal-rounded vbo-modal-tall',
				title: 		   '<?php VikBookingIcons::e('star'); ?> <span>' + daytitle + '</span>',
				footer_right:  '<button type="button" class="btn btn-success" onclick="vboAddFest();">' + Joomla.JText._('VBSAVE') + '</button>',
				dismiss_event: 'vbo-dismiss-modal-overv-mng-fests',
				onDismiss: 	   () => {
					jQuery('.vbo-overv-mngfest-wrap').appendTo('.vbo-overv-mngfest-block');
				},
			});

			// set modal body
			jQuery('.vbo-overv-mngfest-wrap').appendTo(fests_modal);
		}
	});

	// room-day notes
	jQuery(document.body).on("click", ".vbo-roomdaynote-display", function() {
		if (!jQuery(this).closest('.vbo-roomdaynote-trigger').length) {
			return;
		}
		var daytitle = new Array;
		var roomday_info = jQuery(this).closest('.vbo-roomdaynote-trigger').attr('data-roomday').split('_');
		// readable day
		var readymd = roomday_info[0];
		if (jQuery('.bluedays[data-ymd="' + roomday_info[0] + '"]').length) {
			readymd = jQuery('.bluedays[data-ymd="' + roomday_info[0] + '"]').attr('data-readymd');
		}
		daytitle.push(readymd);
		// room name
		if (jQuery('.roomname[data-roomid="' + roomday_info[1] + '"]').length) {
			daytitle.push(jQuery('.roomname[data-roomid="' + roomday_info[1] + '"]').first().find('.vbo-overview-roomname').text());
		}
		// sub-unit
		if (parseInt(roomday_info[2]) > 0 && jQuery('.subroomname[data-roomid="-' + roomday_info[1] + '"]').length) {
			daytitle.push(jQuery('.subroomname[data-roomid="-' + roomday_info[1] + '"]').find('.vbo-overview-subroomname').eq((parseInt(roomday_info[2]) - 1)).text());
		}

		// display modal
		var rdaynotes_modal = VBOCore.displayModal({
			suffix: 	   'overv-mng-rdaynotes',
			extra_class:   'vbo-modal-rounded vbo-modal-tall',
			title: 		   '<?php VikBookingIcons::e('comment'); ?> <span>' + daytitle.join(', ') + '</span>',
			footer_right:  '<button type="button" class="btn btn-success" onclick="vboAddRoomDayNote();">' + Joomla.JText._('VBSAVE') + '</button>',
			dismiss_event: 'vbo-dismiss-modal-overv-mng-rdaynotes',
			onDismiss: 	   () => {
				jQuery('.vbo-overv-mngroomdaynotes-wrap').appendTo('.vbo-overv-mngroomdaynotes-block');
			},
		});

		// set modal body
		jQuery('.vbo-overv-mngroomdaynotes-wrap').appendTo(rdaynotes_modal);

		// populate current room day notes
		vboRenderRdayNotes(roomday_info[0], roomday_info[1], roomday_info[2], readymd);
	});

	/**
	 * Check if the unassigned rows, if any, should be hid because of no missing sub-units.
	 */
	document
		.querySelectorAll('tr.vboverviewtablerow-subunit-unassigned')
		.forEach((unassigned_row) => {
			let unassigned_cells = unassigned_row.querySelectorAll('td.subroom-busy').length;
			if (!unassigned_cells) {
				// remove the unassigned row for this room-type
				unassigned_row.remove();
			} else {
				// add an alert icon next to the main room row
				try {
					let main_room_id = unassigned_row.getAttribute('data-subroomid').split('-')[0];
					let alert_elem = document.createElement('span');
					alert_elem.classList.add('vbo-overview-alert');

					let alert_icon = document.createElement('span');
					alert_icon.classList.add('badge', 'badge-warning');
					alert_icon.innerHTML = '<?php VikBookingIcons::e('exclamation-triangle'); ?>';
					alert_icon.addEventListener('click', () => {
						// find the first room reservation record with missing sub-unit
						let reassignBookingEl = unassigned_row.querySelector('td.subroom-busy.vbo-checkinday[data-lastbid]');
						if (reassignBookingEl) {
							// scroll the table to make the room booking cell visible
							let mainTable = unassigned_row.closest('table');
							let tableWrapper = mainTable.closest('.vbo-table-responsive');
							let firstCellWidth = mainTable
								.querySelector('tr.vboverviewtablerow[data-roomid="' + main_room_id + '"]')
								.querySelector('td.roomname')
								.offsetWidth;
							tableWrapper.scrollTo({
								left: reassignBookingEl.offsetLeft - firstCellWidth - reassignBookingEl.offsetWidth,
								behavior: 'smooth',
							});

							// find a reassignment solution for the first booking found
							setTimeout(() => {
								vboOvervResolveRoomAssignment(reassignBookingEl.getAttribute('data-lastbid'));
							}, 200);
						}
					});

					alert_elem.append(alert_icon);

					unassigned_row
						.closest('table')
						.querySelector('tr.vboverviewtablerow[data-roomid="' + main_room_id + '"]')
						.querySelector('td.roomname')
						.querySelector('.vbo-overview-roomname')
						.insertAdjacentElement('afterend', alert_elem);
				} catch(e) {
					console.error(e);
				}

				// restore any previous temporary data for "previewing" a reassignment moveset applied
				let previousMovesetData = VBOCore.getAdminDock().loadTemporaryData(
					{
						id: '_tmp',
						persist_id: 'reassignmentmoveset',
					},
					(movesetData) => {
						// temporary data restored from dock
						let closeBtn = document.createElement('button');
						closeBtn.setAttribute('type', 'button');
						closeBtn.classList.add('btn');
						closeBtn.textContent = <?php echo json_encode(JText::translate('VBO_KEEP_CHANGES')); ?>;
						closeBtn.addEventListener('click', () => {
							// dismiss modal and keep changes
							VBOCore.emitEvent('vbo-dismiss-modal-overv-resolveroomassign-undo');
						});

						let undoBtn = document.createElement('button');
						undoBtn.setAttribute('type', 'button');
						undoBtn.classList.add('btn', 'btn-danger');
						undoBtn.textContent = <?php echo json_encode(JText::translate('VBO_UNDO_CHANGES')); ?>;
						undoBtn.addEventListener('click', () => {
							// start loading
							VBOCore.emitEvent('vbo-loading-modal-overv-resolveroomassign-undo');

							// make the request to undo the changes made by the moveset
							VBOCore.doAjax(
								"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.apply_room_assignment'); ?>",
								{
									moveset: movesetData?.moveset,
									undo:    1,
								},
								(res) => {
									// dismiss modal
									VBOCore.emitEvent('vbo-dismiss-modal-overv-resolveroomassign-undo');

									// reload the page
									location.reload();
								},
								(err) => {
									// display error message
									alert(err.responseText || err);

									// stop loading
									VBOCore.emitEvent('vbo-loading-modal-overv-resolveroomassign-undo');
								}
							);
						});

						// display prompt modal to apply or undo the changes just made
						VBOCore.displayModal({
							suffix:         'overv-resolveroomassign-undo',
							extra_class:    'vbo-modal-rounded vbo-modal-prompt',
							title:          <?php echo json_encode(JText::translate('VBO_RESOLVE_ROOM_ASSIGNMENT')); ?>,
							body:           <?php echo json_encode(sprintf('%s / %s?', JText::translate('VBO_KEEP_CHANGES'), JText::translate('VBO_UNDO_CHANGES'))); ?>,
							draggable:      true,
							escape_dismiss: false,
							footer_left:    undoBtn,
							footer_right:   closeBtn,
							dismiss_event:  'vbo-dismiss-modal-overv-resolveroomassign-undo',
							loading_event:  'vbo-loading-modal-overv-resolveroomassign-undo',
							loading_body:   '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>',
						});
					},
					(movesetData) => {
						// temporary data removed from dock
						// do nothing as moveset was already applied
					}
				);
			}
		});

	/**
	 * Add body click event delegation for elements that may be later added to the DOM.
	 */
	document.body.addEventListener('click', (e) => {

		// resolve room assignment button in modal window
		if (e.target.matches('.vbo-reassign-room-unit-btn[data-bid]') || e.target.closest('.vbo-reassign-room-unit-btn[data-bid]')) {
			const btnEl = !e.target.matches('.vbo-reassign-room-unit-btn[data-bid]') ? e.target.closest('.vbo-reassign-room-unit-btn[data-bid]') : e.target;
			const bid = btnEl.getAttribute('data-bid');

			// dismiss modal
			VBOCore.emitEvent('vbo-dismiss-modal-overv-rdaybookings');

			// call the function that handles the room assignment for a specific booking
			setTimeout(() => {
				vboOvervResolveRoomAssignment(bid);
			}, 200);

			// do not proceed
			return;
		}

		// resolve room assignment modal window include/exclude bookings checkbox
		if (e.target.matches('input[type="checkbox"][name*="skip_booking_id"]')) {
			const spanBtn = e.target.closest('.vbo-toggle-double-status');
			if (spanBtn) {
				if (e.target.checked) {
					spanBtn.setAttribute('data-tooltiptext', spanBtn.getAttribute('data-text-disabled'));
				} else {
					spanBtn.setAttribute('data-tooltiptext', spanBtn.getAttribute('data-text-enabled'));
				}
			}

			// do not proceed
			return;
		}

	});

<?php
/**
 * Handle fixed table heads when on snake-layout (scroll), which is completely
 * different than the table-layout sticky table headers (handled through jQuery).
 * 
 * @since 	1.16.9 (J) - 1.6.9 (WP)
 */
if ($cookie_sticky_heads == 'off') {
	?>
	// listen to the document scroll event to handle the fixed table heads
	document.addEventListener('scroll', vboOvervHandleVScrollTableHeads);

	// scan all tables for listening to their horizontal scrolling event
	let tables = document.querySelectorAll('table.vboverviewtable.vbo-overv-sticky-table-head-off');
	tables.forEach((table) => {
		table.parentNode.addEventListener('scroll', vboOvervHandleHScrollTableHead);
	});

	// launch the handling function
	vboOvervHandleHScrollTableHead();
	<?php
}
?>
});

/**
 * Document vertical scroll event handler for the fixed table heads.
 * 
 * @param 	object 	e 	the document scroll event object.
 * 
 * @return 	void
 * 
 * @since 	1.16.9 (J) - 1.6.9 (WP)
 */
function vboOvervHandleVScrollTableHeads(e) {
	// register throttling callback
	VBOCore.throttleTimer(() => {
		// get all eligible tables
		let tables = document.querySelectorAll('table.vboverviewtable.vbo-overv-sticky-table-head-off');
		if (!tables.length) {
			// un-register event
			document.removeEventListener('scroll', vboOvervHandleVScrollTableHeads);
			return;
		}

		// pool of fixed table heads
		let fixedTableHeadNodes = vboOvervBuildFixedTableHeads(tables);

		if (fixedTableHeadNodes.length) {
			// append to body just the last table head node for the bottom farthest table
			document.body.appendChild(fixedTableHeadNodes[fixedTableHeadNodes.length - 1]);
		}
	}, 400);
}

/**
 * Table-element horizontal scroll event handler for updating the fixed table heads.
 * 
 * @param 	object 	e 	the table-element scroll event object.
 * 
 * @return 	void
 * 
 * @since 	1.16.9 (J) - 1.6.9 (WP)
 */
function vboOvervHandleHScrollTableHead(e) {
	// register throttling callback
	VBOCore.throttleTimer(() => {
		// horizontal scrolling happens on the parent DIV node
		let tables = (e?.target || document).querySelectorAll('table.vboverviewtable.vbo-overv-sticky-table-head-off');
		if (tables.length !== 1) {
			// un-register event for missing table
			if (e?.target) {
				e.target.removeEventListener('scroll', vboOvervHandleHScrollTableHead);
			}
			return;
		}

		if (<?php echo $table_scroll_layout === 'inline' ? 'true' : 'false'; ?>) {
			// the inline-scroll layout requires to update the visible month name upon scrolling
			let firstVisibleCell = vboOvervFirstVisibleTableHeadNode(tables[0]);
			let periodEl = tables[0].querySelector('.bluedays.vbo-overview-month[data-initial-period]');
			if (firstVisibleCell && periodEl) {
				if (!firstVisibleCell.no_scroll && vboOvervMonthNames[firstVisibleCell.mon]) {
					// display current scroll position month name
					periodEl.textContent = vboOvervMonthNames[firstVisibleCell.mon] + ' ' + firstVisibleCell.year;
				} else {
					// restore the original range/month name
					periodEl.textContent = periodEl.getAttribute('data-initial-period');
				}
			}
		}

		// pool of fixed table heads
		let fixedTableHeadNodes = vboOvervBuildFixedTableHeads(tables);

		if (fixedTableHeadNodes.length) {
			// append to body the first and only table head node expected
			document.body.appendChild(fixedTableHeadNodes[0]);
		}
	}, 200);
}

/**
 * Builds the list of element nodes for the fixed table heads.
 * 
 * @param 	object 	tables 	The table elements to parse.
 * 
 * @return 	object[]
 * 
 * @since 	1.16.9 (J) - 1.6.9 (WP)
 */
function vboOvervBuildFixedTableHeads(tables) {
	// pool of fixed table heads
	let fixedTableHeadNodes = [];

	// get document body scroll top value
	let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;

	// platform fixed offset top
	let platformAdminBar = <?php echo VBOPlatformDetection::isWordPress() ? 'document.querySelector("#wpadminbar")' : '(document.querySelector(".navbar") || document.querySelector("#subhead-container"))'; ?>;
	let fixedOffsetTop = platformAdminBar ? platformAdminBar.offsetHeight : 0;
	if (fixedOffsetTop) {
		// ensure the responsive mode has got a fixed/absolute positioning
		let adminBarPosition = window.getComputedStyle(platformAdminBar).getPropertyValue('position') || platformAdminBar.style.position || '';
		if (adminBarPosition != 'fixed' && adminBarPosition != 'absolute' && adminBarPosition != 'sticky') {
			// no fixed admin bar
			fixedOffsetTop = 0;
		}
	}

	// scan all table elements
	(tables || []).forEach((table, index) => {
		// always check for existing fixed table head element
		let scrollingHeadElement = document.querySelector('.vbo-overv-sticky-scroll-month[data-table-index="' + index + '"]');
		if (scrollingHeadElement) {
			// remove fixed table head element from DOM, will be reconstructed if needed
			scrollingHeadElement.remove();
		}

		// get table head element node
		let tableHeadNode = table.querySelector('tr.vboverviewtablerowone');

		// get position of current table head
		let tableHeadHeight = tableHeadNode.offsetHeight;

		// make sure the table head is NOT visible, hence requires a fixed head
		if (scrollTop > (table.offsetTop + tableHeadHeight - (fixedOffsetTop / 2))) {
			if (scrollTop > (table.offsetTop + table.offsetHeight)) {
				// scrolling went even below the table
				return;
			}

			// build main element node
			let fixedTableHead = document.createElement('div');
			fixedTableHead.className   = 'vbo-overv-sticky-scroll-month';
			fixedTableHead.style.top   = fixedOffsetTop + 'px';
			fixedTableHead.style.left  = table.parentNode.getBoundingClientRect().x + 'px';
			fixedTableHead.setAttribute('data-table-index', index);

			// inner element node
			let fixedTableHeadWrap = document.createElement('div');
			fixedTableHeadWrap.className = 'vbo-overv-sticky-scroll-month-wrap';

			// month-name element node
			let tableHeadMonth      = tableHeadNode.querySelector('.vbo-overview-month');
			let fixedTableHeadMonth = document.createElement('div');
			fixedTableHeadMonth.className   = 'vbo-overv-sticky-scroll-month-m';
			fixedTableHeadMonth.style.width = tableHeadMonth.offsetWidth + 'px';
			fixedTableHeadMonth.appendChild(
				document.createTextNode(tableHeadMonth.innerText)
			);
			fixedTableHeadWrap.appendChild(fixedTableHeadMonth);

			// obtain the list of visible month-day cells
			vboOvervVisibleTableHeadNodes(table, index).forEach((mday, mdayIndex) => {
				// month-day cell element node
				let fixedTableHeadMday = document.createElement('div');
				fixedTableHeadMday.className = 'vbo-overv-sticky-scroll-month-d';
				if (mdayIndex && mday?.mday == 1) {
					fixedTableHeadMday.classList.add('vbo-overv-sticky-scroll-newmonth-d');
				}
				fixedTableHeadMday.style.width = mday['vwidth'] + 'px';
				fixedTableHeadMday.addEventListener('click', () => {
					try {
						// use jQuery to trigger the click event, or plain JS won't work
						/*
						document
							.querySelector('table.vboverviewtable.vbo-overv-sticky-table-head-off[data-table-index="' + index + '"]')
							.querySelector('.bluedays[data-ymd="' + mday['ymd'] + '"]')
							.dispatchEvent(new Event('click'));
						*/
						jQuery('table.vboverviewtable.vbo-overv-sticky-table-head-off[data-table-index="' + index + '"]')
							.find('.bluedays[data-ymd="' + mday['ymd'] + '"]')
							.trigger('click');
					} catch(e) {
						// do nothing
					}
				});

				// week-day element
				let wdayEl = document.createElement('span');
				wdayEl.className = 'vbo-overv-sticky-scroll-month-day';
				wdayEl.appendChild(
					document.createTextNode(mday['wday'])
				);
				fixedTableHeadMday.appendChild(wdayEl);

				// month-day element
				let mdayEl = document.createElement('span');
				mdayEl.className = 'vbo-overv-sticky-scroll-month-day-num';
				mdayEl.appendChild(
					document.createTextNode(mday['mday'])
				);
				fixedTableHeadMday.appendChild(mdayEl);

				// append month-day cell element node
				fixedTableHeadWrap.appendChild(fixedTableHeadMday);
			});

			// append wrapper
			fixedTableHead.appendChild(fixedTableHeadWrap);

			// push the final element node to the pool
			fixedTableHeadNodes.push(fixedTableHead);
		}
	});

	return fixedTableHeadNodes;
}

/**
 * Builds a list of visible month-day cells in a table head.
 * 
 * @param 	object 	table 	the NodeList element representing a table.
 * @param 	number 	index 	the NodeList element index we are parsing.
 * 
 * @return 	object[]
 * 
 * @since 	1.16.9 (J) - 1.6.9 (WP)
 */
function vboOvervVisibleTableHeadNodes(table, index) {
	// get the main table head element
	let tableHeadElement = table.querySelector('tr.vboverviewtablerowone');

	// get month element of the given table
	let monthElement = tableHeadElement.querySelector('.vbo-overview-month');
	let monthName    = monthElement.innerText;
	let monthLeft    = monthElement.offsetLeft - 2;
	let monthWidth   = monthElement.offsetWidth;

	// get horizontal scroll left and table width from parent node
	let tableScrollLeft = table.parentNode.scrollLeft;
	let tableViewWidth  = table.parentNode.offsetWidth;

	// pool of visible month-day cells
	let visibleMonthDays = [];

	// scan all visible month-day elements
	tableHeadElement.querySelectorAll('.bluedays:not(.vbo-overview-month)').forEach((cell) => {
		let monthDayLeft  = cell.offsetLeft;
		let monthDayWidth = cell.offsetWidth;

		// ensure cell is visible from left scroll
		if ((monthDayLeft + monthDayWidth) >= (monthLeft + monthWidth)) {
			// ensure cell is also visible from right scroll
			if ((monthLeft + tableViewWidth) > monthDayLeft) {
				// calculate cell exact visible width
				let cellViewWidth = monthDayWidth;
				if ((monthDayLeft + monthDayWidth) > (monthLeft + tableViewWidth)) {
					// cell is cut from right scroll, count visible width
					cellViewWidth = monthDayWidth - ((monthDayLeft + monthDayWidth) - (tableViewWidth + monthLeft));
				} else if ((monthWidth + tableScrollLeft) > monthDayLeft) {
					// cell is cut from left scroll, count visible width
					cellViewWidth = monthDayWidth - ((monthWidth + tableScrollLeft) - monthDayLeft);
				}
				cellViewWidth = cellViewWidth > monthDayWidth || cellViewWidth < 1 ? 1 : cellViewWidth;

				// push cell details
				visibleMonthDays.push({
					wday:   cell.querySelector('.vbo-overw-tablewday').innerText,
					mday:   cell.querySelector('.vbo-overw-tablemday').innerText,
					ymd: 	cell.getAttribute('data-ymd'),
					vwidth: cellViewWidth,
				});
			}
		}
	});

	return visibleMonthDays;
}

/**
 * Returns the information of the first visible month-day cell in a table head upon scrolling.
 * 
 * @param 	object 	table 	the NodeList element representing a table.
 * 
 * @return 	object|null
 * 
 * @since 	1.18.6 (J) - 1.8.6 (WP)
 */
function vboOvervFirstVisibleTableHeadNode(table) {
	// get the main table head element
	let tableHeadElement = table.querySelector('tr.vboverviewtablerowone');

	// get month element of the given table
	let monthElement = tableHeadElement.querySelector('.vbo-overview-month');
	let monthLeft    = monthElement.offsetLeft - 2;
	let monthWidth   = monthElement.offsetWidth;

	// get horizontal scroll left and table width from parent node
	let tableScrollLeft = table.parentNode.scrollLeft;
	let tableViewWidth  = table.parentNode.offsetWidth;

	// attempt to identify the first visible month-day cell
	let firstVisibleMonthDay = null;

	// scan all visible month-day elements
	tableHeadElement.querySelectorAll('.bluedays:not(.vbo-overview-month)').forEach((cell) => {
		if (firstVisibleMonthDay) {
			return;
		}

		// get cell offsets
		let monthDayLeft  = cell.offsetLeft;
		let monthDayWidth = cell.offsetWidth;

		// ensure cell is visible from left scroll
		if ((monthDayLeft + monthDayWidth) >= (monthLeft + monthWidth)) {
			// ensure cell is also visible from right scroll
			if ((monthLeft + tableViewWidth) > monthDayLeft) {
				// visible cell found, gather the information
				let ymd = cell.getAttribute('data-ymd');
				let matchData = ymd.match(/^([0-9]{4})-(0?[1-9]|1[0-2])-[0-9]{2}$/);
				if (!matchData) {
					// unexpected cell information
					return;
				}

				// set first visible cell properties
				firstVisibleMonthDay = {
					ymd: ymd,
					year: matchData[1],
					mon: Number(matchData[2]),
					no_scroll: !tableScrollLeft ? 1 : 0,
				};
			}
		}
	});

	return firstVisibleMonthDay;
}

/**
 * Fests
 */
function vboRenderFests(day, daytitle) {
	// compose fests information
	var fests_html = '';
	if (vboFests[day] && vboFests[day]['festinfo'] && vboFests[day]['festinfo'].length) {
		for (var i = 0; i < vboFests[day]['festinfo'].length; i++) {
			var fest = vboFests[day]['festinfo'][i];
			fests_html += '<div class="vbo-overlay-fest-details">';
			fests_html += '	<div class="vbo-fest-info">';
			fests_html += '		<div class="vbo-fest-name">' + fest['trans_name'] + '</div>';
			fests_html += '		<div class="vbo-fest-desc">' + fest['descr'].replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br />' + '$2') + '</div>';
			fests_html += '	</div>';
			fests_html += '	<div class="vbo-fest-cmds">';
			fests_html += '		<button type="button" class="btn btn-danger" onclick="vboRemoveFest(\'' + day + '\', \'' + i + '\', \'' + fest['type'] + '\', this);"><?php VikBookingIcons::e('trash-alt', 'no-margin'); ?></button>';
			fests_html += '	</div>';
			fests_html += '</div>';
		}
	}

	// update ymd key for the selected date, useful for adding new fests
	jQuery('.vbo-overlay-fests-addnew').attr('data-ymd', day);

	// set content and display modal
	jQuery('.vbo-overlay-fests-list').html(fests_html);

	if (typeof daytitle !== 'undefined') {
		// display modal only when we are not deleting a fest
		var fests_modal = VBOCore.displayModal({
			suffix: 	   'overv-mng-fests',
			extra_class:   'vbo-modal-rounded vbo-modal-tall',
			title: 		   '<?php VikBookingIcons::e('star'); ?> <span>' + daytitle + '</span>',
			footer_right:  '<button type="button" class="btn btn-success" onclick="vboAddFest();">' + Joomla.JText._('VBSAVE') + '</button>',
			dismiss_event: 'vbo-dismiss-modal-overv-mng-fests',
			onDismiss: 	   () => {
				jQuery('.vbo-overv-mngfest-wrap').appendTo('.vbo-overv-mngfest-block');
			},
		});

		// set modal body
		jQuery('.vbo-overv-mngfest-wrap').appendTo(fests_modal);
	}
}

function vboRemoveFest(day, index, fest_type, that) {
	if (!confirm('<?php echo addslashes(JText::translate('VBDELCONFIRM')); ?>')) {
		return false;
	}
	var elem = jQuery(that);
	// make the AJAX request to the controller to remove this fest from the DB
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=remove_fest'); ?>",
		{
			tmpl: "component",
			dt: day,
			ind: index,
			type: fest_type
		},
		(res) => {
			if (res.indexOf('e4j.ok') >= 0) {
				// delete fest also from the json-decode array of objects
				if (vboFests[day] && vboFests[day]['festinfo']) {
					// use splice to remove the desired index from array, or delete would not make the length of the array change
					vboFests[day]['festinfo'].splice(index, 1);
					// re-build indexes of delete buttons, fundamental for removing the right index at next click
					vboRenderFests(day);
					if (!vboFests[day]['festinfo'].length) {
						// delete also this date object from fests
						delete vboFests[day];
						// no more fests, remove the class for this date from all cells
						jQuery('th.bluedays[data-ymd="'+day+'"]').removeClass('vbo-overv-festcell');
						jQuery('td.notbusy[data-day="'+day+'"]').removeClass('vbo-overv-festcell');
						jQuery('td.subnotbusy[data-day="'+day+'"]').removeClass('vbo-overv-festcell');
						jQuery('td.busy[data-day="'+day+'"]').removeClass('vbo-overv-festcell');
						jQuery('td.subroom-busy[data-day="'+day+'"]').removeClass('vbo-overv-festcell');
					}
				}
				elem.closest('.vbo-overlay-fest-details').remove();
			} else {
				alert('Invalid response');
			}
		},
		(err) => {
			alert('Request failed');
		}
	);
}

function vboAddFest() {
	var ymd = jQuery('.vbo-overlay-fests-addnew').attr('data-ymd');
	var fest_name = jQuery('#vbo-newfest-name').val();
	var fest_descr = jQuery('#vbo-newfest-descr').val();
	if (!fest_name || !fest_name.length) {
		return false;
	}
	// make the AJAX request to the controller to add this fest to the DB
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=add_fest'); ?>",
		{
			tmpl: "component",
			dt: ymd,
			type: "custom",
			name: fest_name,
			descr: fest_descr
		},
		(res) => {
			// parse the JSON response that contains the fest object for the passed date
			try {
				var stored_fest = typeof res === 'string' ? JSON.parse(res) : res;
				if (!vboFests.hasOwnProperty(stored_fest['dt'])) {
					// we need to add the proper class to all cells to show that there is a fest
					jQuery('th.bluedays[data-ymd="'+stored_fest['dt']+'"]').addClass('vbo-overv-festcell');
					jQuery('td.notbusy[data-day="'+stored_fest['dt']+'"]').addClass('vbo-overv-festcell');
					jQuery('td.subnotbusy[data-day="'+stored_fest['dt']+'"]').addClass('vbo-overv-festcell');
					jQuery('td.busy[data-day="'+stored_fest['dt']+'"]').addClass('vbo-overv-festcell');
					jQuery('td.subroom-busy[data-day="'+stored_fest['dt']+'"]').addClass('vbo-overv-festcell');
				}
				vboFests[stored_fest['dt']] = stored_fest;
				// hide modal
				VBOCore.emitEvent('vbo-dismiss-modal-overv-mng-fests');
				// reset input fields
				jQuery('#vbo-newfest-name').val('');
				jQuery('#vbo-newfest-descr').val('');
			} catch (e) {
				alert('Invalid response');
				return false;
			}
		},
		(err) => {
			alert('Request failed');
		}
	);
}

/**
 * Room-day notes
 */
var rdaynote_icn_full = '<?php echo VikBookingIcons::i('sticky-note', 'vbo-roomdaynote-display'); ?>';
var rdaynote_icn_empty = '<?php echo VikBookingIcons::i('far fa-sticky-note', 'vbo-roomdaynote-display'); ?>';
function vboRenderRdayNotes(day, idroom, subunit, readymd) {
	// compose fests information
	var notes_html = '';
	var keyid = day + '_' + idroom + '_' + subunit;
	if (vboRdayNotes.hasOwnProperty(keyid) && vboRdayNotes[keyid]['info'] && vboRdayNotes[keyid]['info'].length) {
		for (var i = 0; i < vboRdayNotes[keyid]['info'].length; i++) {
			var note_data = vboRdayNotes[keyid]['info'][i];
			notes_html += '<div class="vbo-overlay-fest-details vbo-modal-roomdaynotes-note-details">';
			notes_html += '	<div class="vbo-fest-info vbo-modal-roomdaynotes-note-info">';
			notes_html += '		<div class="vbo-fest-name vbo-modal-roomdaynotes-note-name">' + note_data['name'] + '</div>';
			notes_html += '		<div class="vbo-fest-desc vbo-modal-roomdaynotes-note-desc">' + note_data['descr'].replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br />' + '$2') + '</div>';
			notes_html += '	</div>';
			notes_html += '	<div class="vbo-fest-cmds vbo-modal-roomdaynotes-note-cmds">';
			notes_html += '		<button type="button" class="btn btn-danger" onclick="vboRemoveRdayNote(\'' + i + '\', \'' + day + '\', \'' + idroom + '\', \'' + subunit + '\', \'' + note_data['type'] + '\', this);"><?php VikBookingIcons::e('trash-alt', 'no-margin'); ?></button>';
			notes_html += '	</div>';
			notes_html += '</div>';
		}
	}
	// update attributes keys for the selected date, useful for adding new notes
	jQuery('.vbo-modal-roomdaynotes-addnew').attr('data-ymd', day).attr('data-roomid', idroom).attr('data-subroomid', subunit);
	if (readymd !== null) {
		jQuery('.vbo-modal-roomdaynotes-addnew').attr('data-readymd', readymd);
		jQuery('.vbo-newrdnote-dayto-val').text(readymd);
	}
	// set content and display modal
	jQuery('.vbo-modal-roomdaynotes-list').html(notes_html);
}

function vboAddRoomDayNote(that) {
	var mainelem = jQuery('.vbo-modal-roomdaynotes-addnew');
	var ymd = mainelem.attr('data-ymd');
	var roomid = mainelem.attr('data-roomid');
	var subroomid = mainelem.attr('data-subroomid');
	var note_name = jQuery('#vbo-newrdnote-name').val();
	var note_descr = jQuery('#vbo-newrdnote-descr').val();
	var note_cdays = jQuery('#vbo-newrdnote-cdays').val();
	if (!note_name.length && !note_descr.length) {
		alert('Missing required fields');
		return false;
	}
	// make the AJAX request to the controller to add this note to the DB
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=add_roomdaynote'); ?>",
		{
			tmpl: "component",
			dt: ymd,
			idroom: roomid,
			subunit: subroomid,
			type: "custom",
			name: note_name,
			descr: note_descr,
			cdays: note_cdays
		},
		(res) => {
			// parse the JSON response that contains the note object for the passed date
			try {
				var stored_notes = typeof res === 'string' ? JSON.parse(res) : res;
				for (var keyid in stored_notes) {
					if (!stored_notes.hasOwnProperty(keyid)) {
						continue;
					}
					if (!vboRdayNotes.hasOwnProperty(keyid) && jQuery('.vbo-roomdaynote-trigger[data-roomday="' + keyid + '"]').length) {
						// we need to add the proper class to the cell for this note (if it's visible)
						jQuery('.vbo-roomdaynote-trigger[data-roomday="' + keyid + '"]').parent('td').removeClass('vbo-roomdaynote-empty').addClass('vbo-roomdaynote-full').find('i').attr('class', rdaynote_icn_full);
					}
					// update global object with the new notes in any case
					vboRdayNotes[keyid] = stored_notes[keyid];
				}
				// hide modal
				VBOCore.emitEvent('vbo-dismiss-modal-overv-mng-rdaynotes');
				// reset input fields
				jQuery('#vbo-newrdnote-name').val('');
				jQuery('#vbo-newrdnote-descr').val('');
				jQuery('#vbo-newrdnote-cdays').val('0').trigger('change');
			} catch (e) {
				alert('Invalid response');
				return false;
			}
		},
		(err) => {
			alert('Request failed');
		}
	);
}

function vboRemoveRdayNote(index, day, idroom, subunit, note_type, that) {
	if (!confirm('<?php echo addslashes(JText::translate('VBDELCONFIRM')); ?>')) {
		return false;
	}
	var elem = jQuery(that);
	// make the AJAX request to the controller to remove this note from the DB
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=remove_roomdaynote'); ?>",
		{
			tmpl: "component",
			dt: day,
			idroom: idroom,
			subunit: subunit,
			ind: index,
			type: note_type
		},
		(res) => {
			if (res.indexOf('e4j.ok') >= 0) {
				var keyid = day + '_' + idroom + '_' + subunit;
				// delete note also from the json-decode array of objects
				if (vboRdayNotes[keyid] && vboRdayNotes[keyid]['info']) {
					// use splice to remove the desired index from array, or delete would not make the length of the array change
					vboRdayNotes[keyid]['info'].splice(index, 1);
					// re-build indexes of delete buttons, fundamental for removing the right index at next click
					vboRenderRdayNotes(day, idroom, subunit, null);
					if (!vboRdayNotes[keyid]['info'].length) {
						// delete also this date object from notes
						delete vboRdayNotes[keyid];
						// no more notes, update the proper class attribute for this cell (should be visible)
						if (jQuery('.vbo-roomdaynote-trigger[data-roomday="' + keyid + '"]').length) {
							jQuery('.vbo-roomdaynote-trigger[data-roomday="' + keyid + '"]').parent('td').removeClass('vbo-roomdaynote-full').addClass('vbo-roomdaynote-empty').find('i').attr('class', rdaynote_icn_empty);
						}
					}
				}
				elem.closest('.vbo-modal-roomdaynotes-note-details').remove();
			} else {
				alert('Invalid response');
			}
		},
		(err) => {
			alert('Request failed');
		}
	);
}

function vboRdayNoteCdaysCount() {
	var cdays = parseInt(jQuery('#vbo-newrdnote-cdays').val());
	var defymd = jQuery('.vbo-modal-roomdaynotes-addnew').attr('data-ymd');
	var defreadymd = jQuery('.vbo-modal-roomdaynotes-addnew').attr('data-readymd');
	defreadymd = !defreadymd || !defreadymd.length ? defymd : defreadymd;
	if (isNaN(cdays) || cdays < 1) {
		jQuery('.vbo-newrdnote-dayto-val').text(defreadymd);
		return;
	}
	// calculate target (until) date
	var targetdate = new Date(defymd);
	targetdate.setDate(targetdate.getDate() + cdays);
	var target_y = targetdate.getFullYear();
	var target_m = targetdate.getMonth() + 1;
	target_m = target_m < 10 ? '0' + target_m : target_m;
	var target_d = targetdate.getDate();
	target_d = target_d < 10 ? '0' + target_d : target_d;
	// display target date
	var display_target = target_y + '-' + target_m + '-' + target_d;
	// check if we can get the "read ymd property"
	if (jQuery('.bluedays[data-ymd="' + display_target + '"]').length) {
		display_target = jQuery('.bluedays[data-ymd="' + display_target + '"]').attr('data-readymd');
	}
	jQuery('.vbo-newrdnote-dayto-val').text(display_target);
}
</script>

<div class="vbo-orphans-overlay-block">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content vbo-info-overlay-content-orphans">
		<h3><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBORPHANSFOUND'), 'content' => JText::translate('VBORPHANSFOUNDSHELP'), 'icon_class' => VikBookingIcons::i('exclamation-triangle'))); ?> <?php echo JText::translate('VBORPHANSFOUND'); ?></h3>
		<div class="vbo-info-overlay-scroll-content">
			<div class="vbo-orphans-info-list"></div>
		</div>
		<div class="vbo-orphans-info-cmds">
			<div class="vbo-orphans-info-cmd">
				<button type="button" class="btn btn-success" onclick="javascript: hideVboDialogOverv(1);"><?php echo JText::translate('VBOBTNKEEPREMIND'); ?></button>
			</div>
			<div class="vbo-orphans-info-cmd">
				<button type="button" class="btn btn-danger" onclick="javascript: hideVboDialogOverv(-1);"><?php echo JText::translate('VBOBTNDONTREMIND'); ?></button>
			</div>
		</div>
	</div>
</div>

<div class="vbo-overv-mngfest-block" style="display: none;">
	<div class="vbo-overv-mngfest-wrap">
		<div class="vbo-overlay-fests-list"></div>
		<div class="vbo-overlay-fests-addnew" data-ymd="">
			<h4><?php echo JText::translate('VBOADDCUSTOMFESTTODAY'); ?></h4>
			<div class="vbo-overlay-fests-addnew-elem">
				<label for="vbo-newfest-name"><?php echo JText::translate('VBPVIEWPLACESONE'); ?></label>
				<input type="text" id="vbo-newfest-name" value="" />
			</div>
			<div class="vbo-overlay-fests-addnew-elem">
				<label for="vbo-newfest-descr"><?php echo JText::translate('VBPLACEDESCR'); ?></label>
				<textarea id="vbo-newfest-descr"></textarea>
			</div>
		</div>
	</div>
</div>

<div class="vbo-overv-mngroomdaynotes-block" style="display: none;">
	<div class="vbo-overv-mngroomdaynotes-wrap">
		<div class="vbo-modal-roomdaynotes-list"></div>
		<div class="vbo-modal-roomdaynotes-addnew" data-readymd="" data-ymd="" data-roomid="" data-subroomid="">
			<h4><?php echo JText::translate('VBOADDCUSTOMFESTTODAY'); ?></h4>
			<div class="vbo-modal-roomdaynotes-addnew-elem">
				<label for="vbo-newrdnote-name"><?php echo JText::translate('VBPVIEWPLACESONE'); ?></label>
				<input type="text" id="vbo-newrdnote-name" value="" />
			</div>
			<div class="vbo-modal-roomdaynotes-addnew-elem">
				<label for="vbo-newrdnote-descr"><?php echo JText::translate('VBPLACEDESCR'); ?></label>
				<textarea id="vbo-newrdnote-descr"></textarea>
			</div>
			<div class="vbo-modal-roomdaynotes-addnew-elem">
				<label for="vbo-newrdnote-cdays"><?php echo JText::translate('VBOCONSECUTIVEDAYS'); ?></label>
				<input type="number" id="vbo-newrdnote-cdays" min="0" max="365" value="0" onchange="vboRdayNoteCdaysCount();" onkeyup="vboRdayNoteCdaysCount();" />
				<span class="vbo-newrdnote-dayto">
					<span class="vbo-newrdnote-dayto-lbl"><?php echo JText::translate('VBOUNTIL'); ?></span>
					<span class="vbo-newrdnote-dayto-val"></span>
				</span>
			</div>
		</div>
	</div>
</div>

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

$rows = $this->rows;
$allrooms = $this->allrooms;
$lim0 = $this->lim0;
$navbut = $this->navbut;
$orderby = $this->orderby;
$ordersort = $this->ordersort;

// list of IDs for which VCM should monitor the guest messages in the chat
$vcm_bids = array();

$dbo = JFactory::getDbo();
$app = JFactory::getApplication();

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadDatePicker();
$vbo_app->loadContextMenuAssets();

// JS lang def
JText::script('VBOGENINVOICES');
JText::script('VBCONFIRMGENINV');
JText::script('VBADMINNOTESTOGGLE');
JText::script('VBDASHUPRESONE');
JText::script('VBOCHANNEL');
JText::script('VBCOUPON');
JText::script('VBCUSTOMERNOMINATIVE');

$nowdf = VikBooking::getDateFormat(true);
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator(true);
$juidf = $nowdf == "%d/%m/%Y" ? 'dd/mm/yy' : ($nowdf == "%m/%d/%Y" ? 'mm/dd/yy' : 'yy/mm/dd');

$cid = VikRequest::getVar('cid', array());
$pcust_id = $app->getUserStateFromRequest("vbo.orders.cust_id", 'cust_id', 0, 'int');
$pconfirmnumber = VikRequest::getString('confirmnumber', '', 'request');
//Color tags
$colortags = VikBooking::loadBookingsColorTags();
$bctags_tip = '';
if (count($colortags) > 0) {
	$bctags_tip = '<div class=\"vbo-blist-tip-bctag-subtip-inner\">';
	foreach ($colortags as $ctagk => $ctagv) {
		$bctags_tip .= '<div class=\"vbo-blist-tip-bctag-subtip-circle hasTooltip\" data-ctagkey=\"'.$ctagk.'\" data-ctagcolor=\"'.$ctagv['color'].'\" title=\"'.addslashes(JText::translate($ctagv['name'])).'\"><div class=\"vbo-blist-tip-bctag-subtip-circlecont\" style=\"background-color: '.$ctagv['color'].';\"></div></div>';
	}
	$bctags_tip .= '</div>';
}
//

if (empty($rows)) {
	$rows = array();
	?>
	<p class="warn"><?php echo JText::translate('VBNOORDERSFOUND'); ?></p>
	<?php
	if (VBOPlatformDetection::isWordPress()) {
		/**
		 * In case of no bookings found, we check whether bookings can be imported from third party plugins.
		 * 
		 * @wponly 	we do this only on WP.
		 * 
		 * @since 	1.3.5
		 */
		$supported_plugins = VikBooking::canImportBookingsFromThirdPartyPlugins();
		if ($supported_plugins !== false) {
			$tpp_key = '';
			foreach ($supported_plugins as $spkey => $spn) {
				$tpp_key = $spkey;
				break;
			}
		?>
	<div class="notice is-dismissible notice-info">
		<div class="vbo-import-wrap">
			<p>
				<span><?php echo JText::sprintf('VBO_IMPBFROM_INTO_VBO', (isset($supported_plugins[$tpp_key]) ? $supported_plugins[$tpp_key] : '')); ?></span>
				<a class="btn btn-success" href="admin.php?option=com_vikbooking&view=importbftpp"><?php echo JText::translate('VBO_IMPBFTPP_DOIMPORT_SHORT'); ?></a>
			</p>
		</div>
	</div>
		<?php
		}
	}
}

// filter by channel
$all_channels = array();
$q = "SELECT `channel` FROM `#__vikbooking_orders` WHERE `channel` IS NOT NULL GROUP BY `channel`;";
$dbo->setQuery($q);
$ord_channels = $dbo->loadAssocList();
if ($ord_channels) {
	foreach ($ord_channels as $o_channel) {
		$channel_parts = explode('_', $o_channel['channel']);
		$channel_name = count($channel_parts) > 1 ? trim($channel_parts[1]) : trim($channel_parts[0]);
		if (in_array($channel_name, $all_channels)) {
			continue;
		}
		$all_channels[] = $channel_name;
	}
}

// prepare modal
echo $vbo_app->getJmodalScript();
echo $vbo_app->getJmodalHtml('vbo-export-csv', JText::translate('VBCSVEXPORT'), '', 'width: 80%; height: 60%; margin-left: -40%; top: 20% !important;');
echo $vbo_app->getJmodalHtml('vbo-export-ics', JText::translate('VBICSEXPORT'), '', 'width: 80%; height: 60%; margin-left: -40%; top: 20% !important;');

$filters_set = false;

?>

<script type="text/javascript">
	function vboClearFilters() {
		jQuery('#filter-bar, #vbo-search-tools-cont').find('input, select').val('');
		jQuery('#adminForm').append('<input type=\'hidden\' name=\'limitstart\' value=\'0\' />');
		document.getElementById('cust_id').value = '';
		document.adminForm.submit();
	}

	function vboKeyupSearch(event) {
		if (event && event.key && event.key == 'Enter') {
			event.preventDefault();
			document.adminForm.submit();
			return;
		}
	}

	function vboHandleSearchType(search_hint) {
		if (!search_hint) {
			search_hint = '';
		} else {
			search_hint += ': ';
		}
		jQuery('#confirmnumber').val(search_hint).focus();
	}

	jQuery(function() {

		// register context menu
		jQuery('.vbo-context-menu-searchtype').vboContextMenu({
			placement: 'bottom-left',
			buttons: [
				{
					icon: '<?php echo VikBookingIcons::i('search'); ?>',
					text: Joomla.JText._('VBDASHUPRESONE'),
					separator: false,
					action: (root, config) => {
						vboHandleSearchType('id');
					},
				},
				{
					icon: '<?php echo VikBookingIcons::i('search'); ?>',
					text: Joomla.JText._('VBDASHUPRESONE') + ' (' + Joomla.JText._('VBOCHANNEL') + ')',
					separator: false,
					action: (root, config) => {
						vboHandleSearchType('otaid');
					},
				},
				{
					icon: '<?php echo VikBookingIcons::i('user-tag'); ?>',
					text: Joomla.JText._('VBCOUPON'),
					separator: false,
					action: (root, config) => {
						vboHandleSearchType('coupon');
					},
				},
				{
					icon: '<?php echo VikBookingIcons::i('user'); ?>',
					text: Joomla.JText._('VBCUSTOMERNOMINATIVE'),
					separator: false,
					action: (root, config) => {
						vboHandleSearchType('name');
					},
				},
			],
		});

		// register to the event emitted when a booking is modified through an admin widget
		document.addEventListener('vbo_booking_modified', (e) => {
			if (!e || !e.detail || !e.detail.hasOwnProperty('bid') || !e.detail['bid']) {
				// do nothing
				return;
			}
			// reload the page to display the new information about the booking just modified
			location.reload();
		});

	});
</script>

<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm" class="vbo-allbookings-fm">

<div id="filter-bar" class="btn-toolbar vbo-btn-toolbar" style="width: 100%; display: inline-block;">
	<div class="btn-group pull-right">
		<a href="javascript: void(0);" onclick="vboOpenJModal('vbo-export-csv', 'index.php?option=com_vikbooking&task=csvexportprepare&tmpl=component');" class="vbcsvexport"><?php VikBookingIcons::e('table'); ?> <span><?php echo JText::translate('VBCSVEXPORT'); ?></span></a>
		<a href="javascript: void(0);" onclick="vboOpenJModal('vbo-export-ics', 'index.php?option=com_vikbooking&task=icsexportprepare&tmpl=component');" class="vbicsexport"><?php VikBookingIcons::e('calendar'); ?> <span><?php echo JText::translate('VBICSEXPORT'); ?></span></a>
	</div>
	<div class="btn-group pull-left input-append">
		<button type="button" class="btn btn-secondary vbo-context-menu-btn vbo-context-menu-searchtype">
			<span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
		</button>
		<input type="text" name="confirmnumber" id="confirmnumber" autocomplete="off" placeholder="<?php echo JHtml::fetch('esc_attr', JText::translate('VBOFILTCONFNUMCUST')); ?>" value="<?php echo strlen($pconfirmnumber) ? JHtml::fetch('esc_attr', $pconfirmnumber) : ''; ?>" onkeyup="vboKeyupSearch(event);" size="30" />
		<button type="submit" class="btn"><i class="icon-search"></i></button>
	</div>
	<?php
	$cust_id_filter = false;
	if (is_array($rows) && isset($rows[0]) && array_key_exists('customer_fullname', $rows[0])) {
		//customer ID filter
		$cust_id_filter = true;
	}
	?>
	<div class="btn-group pull-left input-append">
		<a style="display: none;" id="vbo-unset-cust-filter" href="index.php?option=com_vikbooking&task=orders&cust_id=0"></a>
		<div id="vbo-allbsearchcust-res" class="vbo-allbsearchcust-res" style="display: none;"></div>
		<input type="text" id="customernominative" autocomplete="off" placeholder="<?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?>" value="<?php echo $cust_id_filter ? htmlspecialchars($rows[0]['customer_fullname']) : ''; ?>" size="30" />
		<button type="button" class="btn" onclick="<?php echo $cust_id_filter ? 'document.location.href=jQuery(\'#vbo-unset-cust-filter\').attr(\'href\');' : 'document.getElementById(\'customernominative\').focus();'; ?>"><i class="<?php echo $cust_id_filter ? 'icon-remove' : 'icon-user'; ?>"></i></button>
	</div>
	<div class="btn-group pull-left">
		<button type="button" class="btn" id="vbo-search-tools-btn" onclick="if(jQuery(this).hasClass('btn-primary')){jQuery('#vbo-search-tools-cont').hide();jQuery(this).removeClass('btn-primary');}else{jQuery('#vbo-search-tools-cont').show();jQuery(this).addClass('btn-primary');}"><?php echo JText::translate('JSEARCH_TOOLS'); ?> <span class="caret"></span></button>
	</div>
	<div class="btn-group pull-left">
		<button type="button" class="btn" onclick="vboClearFilters();"><?php echo JText::translate('JSEARCH_FILTER_CLEAR'); ?></button>
	</div>

	<div id="vbo-search-tools-cont" class="js-stools-container-filters clearfix" style="display: none;">
		<div class="btn-group pull-left">
			<select name="channel">
				<option value=""><?php echo JText::translate('VBCHANNELFILTER'); ?></option>
		<?php
		$pchannel = $app->getUserStateFromRequest("vbo.orders.channel", 'channel', '', 'string');
		if (count($all_channels) > 0) {
			$filters_set = !empty($pchannel) || $filters_set;
			?>
				<option value="-1"<?php echo $pchannel == '-1' ? ' selected="selected"' : ''; ?>>- <?php echo JText::translate('VBORDFROMSITE'); ?></option>
			<?php
			foreach ($all_channels as $o_channel) {
				?>
				<option value="<?php echo $o_channel; ?>"<?php echo $pchannel == $o_channel ? ' selected="selected"' : ''; ?>>- <?php echo ucwords($o_channel); ?></option>
				<?php
			}
		}
		?>
			</select>
		</div>
		<div class="btn-group pull-left">
		<?php
		$pidroom = $app->getUserStateFromRequest("vbo.orders.idroom", 'idroom', 0, 'int');
		if (count($allrooms) > 0) {
			$filters_set = !empty($pidroom) || $filters_set;
			$rsel = '<select name="idroom"><option value="">'.JText::translate('VBROOMFILTER').'</option>';
			foreach ($allrooms as $room) {
				$rsel .= '<option value="'.$room['id'].'"'.(!empty($pidroom) && $pidroom == $room['id'] ? ' selected="selected"' : '').'>'.$room['name'].'</option>';
			}
			$rsel .= '</select>';
		}
		echo $rsel;
		?>
		</div>
	<?php
	$pcategory_id = $app->getUserStateFromRequest("vbo.orders.category_id", 'category_id', 0, 'int');
	if (count($this->categories)) {
		$filters_set = !empty($pcategory_id) || $filters_set;
		?>
		<div class="btn-group pull-left">
			<select name="category_id">
				<option value=""><?php echo JText::translate('VBOCATEGORYFILTER'); ?></option>
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
	?>
		<div class="btn-group pull-left">
			<select name="idpayment">
				<option value=""><?php echo JText::translate('VBOFILTERBYPAYMENT'); ?></option>
			<?php
			$pidpayment = $app->getUserStateFromRequest("vbo.orders.idpayment", 'idpayment', 0, 'int');
			$payment_filter = '';
			if (!empty($pidpayment)) {
				$filters_set = !empty($pidpayment) || $filters_set;
				$payment_filter = '&amp;idpayment='.$pidpayment;
			}
			$q = "SELECT `id`,`name` FROM `#__vikbooking_gpayments` ORDER BY `name` ASC;";
			$dbo->setQuery($q);
			$allpayments = $dbo->loadAssocList();
			foreach ($allpayments as $paym) {
				?>
				<option value="<?php echo $paym['id']; ?>"<?php echo $paym['id'] == $pidpayment ? ' selected="selected"' : ''; ?>><?php echo $paym['name']; ?></option>
				<?php
			}
			?>
			</select>
		</div>
		<div class="btn-group pull-left">
			<select name="status">
				<option value=""><?php echo JText::translate('VBOFILTERBYSTATUS'); ?></option>
			<?php
			$pstatus = $app->getUserStateFromRequest("vbo.orders.status", 'status', '', 'string');
			$filters_set = !empty($pstatus) || $filters_set;
			$status_filter = !empty($pstatus) ? '&amp;status='.$pstatus : '';
			?>
				<optgroup label="<?php echo JText::translate('VBSTATUS'); ?>">
					<option value="confirmed"<?php echo $pstatus == 'confirmed' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIRMED'); ?></option>
					<option value="standby"<?php echo $pstatus == 'standby' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBSTANDBY'); ?></option>
					<option value="cancelled"<?php echo $pstatus == 'cancelled' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCANCELLED'); ?></option>
					<option value="closure"<?php echo $pstatus == 'closure' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBDBTEXTROOMCLOSED'); ?></option>
					<option value="inquiry"<?php echo $pstatus == 'inquiry' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_BTYPE_INQUIRY'); ?></option>
					<option value="request"<?php echo $pstatus == 'request' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_BTYPE_REQUEST'); ?></option>
					<option value="split_stay"<?php echo $pstatus == 'split_stay' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_SPLIT_STAY'); ?></option>
					<option value="overbooking"<?php echo $pstatus == 'overbooking' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_BTYPE_OVERBOOKING'); ?></option>
				</optgroup>
				<optgroup label="<?php echo JText::translate('VBOCHECKEDSTATUS'); ?>">
					<option value="checkedin"<?php echo $pstatus == 'checkedin' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCHECKEDSTATUSIN'); ?></option>
					<option value="checkedout"<?php echo $pstatus == 'checkedout' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCHECKEDSTATUSOUT'); ?></option>
					<option value="noshow"<?php echo $pstatus == 'noshow' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCHECKEDSTATUSNOS'); ?></option>
					<option value="none"<?php echo $pstatus == 'none' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCHECKEDSTATUSZERO'); ?></option>
				</optgroup>
			</select>
		</div>
		<div class="btn-group pull-left">
		<?php
		$dates_filter = '';
		$pdatefilt = $app->getUserStateFromRequest("vbo.orders.datefilt", 'datefilt', 0, 'int');
		$pdatefiltfrom = $app->getUserStateFromRequest("vbo.orders.datefiltfrom", 'datefiltfrom', '', 'string');
		$pdatefiltto = $app->getUserStateFromRequest("vbo.orders.datefiltto", 'datefiltto', '', 'string');
		if (!empty($pdatefilt) && (!empty($pdatefiltfrom) || !empty($pdatefiltto))) {
			$filters_set = true;
			$dates_filter = '&amp;datefilt='.$pdatefilt.(!empty($pdatefiltfrom) ? '&amp;datefiltfrom='.$pdatefiltfrom : '').(!empty($pdatefiltto) ? '&amp;datefiltto='.$pdatefiltto : '');
		}
		$datesel = '<select name="datefilt" onchange="vboToggleDateFilt(this.value);"><option value="">'.JText::translate('VBOFILTERBYDATES').'</option>';
		$datesel .= '<option value="1"'.(!empty($pdatefilt) && $pdatefilt == 1 ? ' selected="selected"' : '').'>'.JText::translate('VBOFILTERDATEBOOK').'</option>';
		$datesel .= '<option value="2"'.(!empty($pdatefilt) && $pdatefilt == 2 ? ' selected="selected"' : '').'>'.JText::translate('VBOFILTERDATEIN').'</option>';
		$datesel .= '<option value="3"'.(!empty($pdatefilt) && $pdatefilt == 3 ? ' selected="selected"' : '').'>'.JText::translate('VBOFILTERDATEOUT').'</option>';
		$datesel .= '</select>';
		echo $datesel;
		?>
		</div>
		<div class="btn-group pull-left" id="vbo-dates-cont" style="display: <?php echo (!empty($pdatefilt) && (!empty($pdatefiltfrom) || !empty($pdatefiltto)) ? 'inline-block' : 'none'); ?>;">
			<input type="text" id="vbo-date-from" placeholder="<?php echo JText::translate('VBNEWSEASONONE'); ?>" value="<?php echo $pdatefiltfrom; ?>" size="10" name="datefiltfrom" autocomplete="off" />&nbsp;-&nbsp;<input type="text" id="vbo-date-to" placeholder="<?php echo JText::translate('VBNEWSEASONTWO'); ?>" value="<?php echo $pdatefiltto; ?>" size="10" name="datefiltto" autocomplete="off" />
		</div>
		<div class="btn-group pull-left">
			<button type="submit" class="btn btn-primary"><i class="icon-search"></i> <?php echo JText::translate('VBPVIEWORDERSSEARCHSUBM'); ?></button>
		</div>
	</div>
</div>

<div class="table-responsive">
<table cellpadding="4" cellspacing="0" border="0" width="100%" class="table table-striped vbo-bookingslist-table">
	<thead>
		<tr>
			<th width="20">
				<input type="checkbox" onclick="Joomla.checkAll(this)" value="" name="checkall-toggle">
			</th>
			<th class="title center" width="20" align="center">
				<a href="index.php?option=com_vikbooking&amp;task=orders<?php echo ($cust_id_filter ? '&amp;cust_id='.$pcust_id : '').$dates_filter.$status_filter.$payment_filter; ?>&amp;vborderby=id&amp;vbordersort=<?php echo ($orderby == "id" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "id" && $ordersort == "ASC" ? "vbo-bookingslist-activesort" : ($orderby == "id" ? "vbo-bookingslist-activesort" : "")); ?>">
					<?php echo 'ID'.($orderby == "id" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "id" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title left" width="110">
				<a href="index.php?option=com_vikbooking&amp;task=orders<?php echo ($cust_id_filter ? '&amp;cust_id='.$pcust_id : '').$dates_filter.$status_filter.$payment_filter; ?>&amp;vborderby=ts&amp;vbordersort=<?php echo ($orderby == "ts" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "ts" && $ordersort == "ASC" ? "vbo-bookingslist-activesort" : ($orderby == "ts" ? "vbo-bookingslist-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWORDERSONE').($orderby == "ts" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "ts" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title left" width="200"><span><?php echo JText::translate('VBPVIEWORDERSTWO'); ?></span></th>
			<th class="title center" width="100" align="center"><span><?php echo JText::translate('VBPVIEWORDERSTHREE'); ?></span></th>
			<th class="title left" width="120"><span><?php echo JText::translate('VBPVIEWORDERSPEOPLE'); ?></span></th>
			<th class="title left" width="110">
				<a href="index.php?option=com_vikbooking&amp;task=orders<?php echo ($cust_id_filter ? '&amp;cust_id='.$pcust_id : '').$dates_filter.$status_filter.$payment_filter; ?>&amp;vborderby=checkin&amp;vbordersort=<?php echo ($orderby == "checkin" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "checkin" && $ordersort == "ASC" ? "vbo-bookingslist-activesort" : ($orderby == "checkin" ? "vbo-bookingslist-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWORDERSFOUR').($orderby == "checkin" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "checkin" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
				</th>
			<th class="title left" width="110">
				<a href="index.php?option=com_vikbooking&amp;task=orders<?php echo ($cust_id_filter ? '&amp;cust_id='.$pcust_id : '').$dates_filter.$status_filter.$payment_filter; ?>&amp;vborderby=checkout&amp;vbordersort=<?php echo ($orderby == "checkout" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "checkout" && $ordersort == "ASC" ? "vbo-bookingslist-activesort" : ($orderby == "checkout" ? "vbo-bookingslist-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWORDERSFIVE').($orderby == "checkout" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "checkout" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title center" width="60" align="center">
				<a href="index.php?option=com_vikbooking&amp;task=orders<?php echo ($cust_id_filter ? '&amp;cust_id='.$pcust_id : '').$dates_filter.$status_filter.$payment_filter; ?>&amp;vborderby=days&amp;vbordersort=<?php echo ($orderby == "days" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "days" && $ordersort == "ASC" ? "vbo-bookingslist-activesort" : ($orderby == "days" ? "vbo-bookingslist-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWORDERSSIX').($orderby == "days" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "days" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title center" width="110" align="center">
				<a href="index.php?option=com_vikbooking&amp;task=orders<?php echo ($cust_id_filter ? '&amp;cust_id='.$pcust_id : '').$dates_filter.$status_filter.$payment_filter; ?>&amp;vborderby=total&amp;vbordersort=<?php echo ($orderby == "total" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "total" && $ordersort == "ASC" ? "vbo-bookingslist-activesort" : ($orderby == "total" ? "vbo-bookingslist-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWORDERSSEVEN').($orderby == "total" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "total" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title center" width="30" align="center"><span>&nbsp;</span></th>
			<th class="title center" width="50" align="center"><span>&nbsp;</span></th>
			<th class="title center" width="100" align="center"><span><?php echo JText::translate('VBPVIEWORDERSEIGHT'); ?></span></th>
			<th class="title center" width="100" align="center"><span><?php echo JText::translate('VBPVIEWORDERCHANNEL'); ?></span></th>
		</tr>
	</thead>
	<?php
	$currencysymb = VikBooking::getCurrencySymb(true);
	$monsmap = array(
		JText::translate('VBSHORTMONTHONE'),
		JText::translate('VBSHORTMONTHTWO'),
		JText::translate('VBSHORTMONTHTHREE'),
		JText::translate('VBSHORTMONTHFOUR'),
		JText::translate('VBSHORTMONTHFIVE'),
		JText::translate('VBSHORTMONTHSIX'),
		JText::translate('VBSHORTMONTHSEVEN'),
		JText::translate('VBSHORTMONTHEIGHT'),
		JText::translate('VBSHORTMONTHNINE'),
		JText::translate('VBSHORTMONTHTEN'),
		JText::translate('VBSHORTMONTHELEVEN'),
		JText::translate('VBSHORTMONTHTWELVE')
	);
	$vcm_logos = VikBooking::getVcmChannelsLogo('', true);
	$kk = 0;
	$i = 0;
	for ($i = 0, $n = count($rows); $i < $n; $i++) {
		$row = $rows[$i];
		if (!$row['closure']) {
			array_push($vcm_bids, $row['id']);
		}
		$rooms = VikBooking::loadOrdersRoomsData($row['id']);
		$peoplestr = '';
		$guest_nums = [];
		$room_names = array();
		if (is_array($rooms)) {
			$totadults = 0;
			$totchildren = 0;
			$totpets = 0;
			foreach ($rooms as $rr) {
				$totadults += $rr['adults'];
				$totchildren += $rr['children'];
				$totpets += $rr['pets'];
				$room_names[] = $rr['room_name'];
				if ($row['split_stay']) {
					// do not sum the guests in case of split stay rooms
					$totadults 	 = $rr['adults'];
					$totchildren = $rr['children'];
				}
			}
			$guest_nums[] = $totadults . " " . ($totadults > 1 ? JText::translate('VBMAILADULTS') : JText::translate('VBMAILADULT'));
			if ($totchildren > 0) {
				$guest_nums[] = $totchildren . " " . ($totchildren > 1 ? JText::translate('VBMAILCHILDREN') : JText::translate('VBMAILCHILD'));
			}
			if ($totpets > 0) {
				$guest_nums[] = $totpets . " " . ($totpets > 1 ? JText::translate('VBO_PETS') : JText::translate('VBO_PET'));
			}
			$peoplestr = implode(', ', $guest_nums);
		}
		$isdue = $row['total'];
		$otachannel = '';
		$otacurrency = '';
		if (!empty($row['channel'])) {
			$channelparts = explode('_', $row['channel']);
			$otachannel = array_key_exists(1, $channelparts) && strlen($channelparts[1]) > 0 ? $channelparts[1] : ucwords($channelparts[0]);
			$otachannelclass = $otachannel;
			if (strstr($otachannelclass, '.') !== false) {
				$otaccparts = explode('.', $otachannelclass);
				$otachannelclass = $otaccparts[0];
			}
			$otacurrency = strlen((string)$row['chcurrency']) ? $row['chcurrency'] : '';
		}
		//Customer Details
		$custdata = $row['custdata'];
		$custdata_parts = explode("\n", $row['custdata']);
		if (count($custdata_parts) > 2 && strpos($custdata_parts[0], ':') !== false && strpos($custdata_parts[1], ':') !== false) {
			//get the first two fields
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
				$custdata = implode(' ', $custvalues);
			}
		}
		if (strlen($custdata) > 45) {
			if (function_exists('mb_substr')) {
				$custdata = mb_substr($custdata, 0, 45, 'UTF-8') . " ...";
			} else {
				$custdata = substr($custdata, 0, 45) . " ...";
			}
		}

		// customer profile picture
		$customer_avatar = null;

		$q = "SELECT `c`.*,`co`.`idorder` FROM `#__vikbooking_customers` AS `c` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `c`.`id`=`co`.`idcustomer` WHERE `co`.`idorder`=".$row['id'];
		$dbo->setQuery($q, 0, 1);
		$cust_country = $dbo->loadAssoc();
		if ($cust_country) {
			if (!empty($cust_country['first_name'])) {
				$custdata = $cust_country['first_name'].' '.$cust_country['last_name'];
				if (!empty($cust_country['country'])) {
					if (file_exists(VBO_ADMIN_PATH.DS.'resources'.DS.'countries'.DS.$cust_country['country'].'.png')) {
						$custdata .= '<img src="'.VBO_ADMIN_URI.'resources/countries/'.$cust_country['country'].'.png'.'" title="'.$cust_country['country'].'" class="vbo-country-flag vbo-country-flag-left"/>';
					}
				}
			}
			if (!empty($cust_country['pic'])) {
				$customer_avatar = $cust_country['pic'];
			}
		}
		$custdata = $row['closure'] > 0 || JText::translate('VBDBTEXTROOMCLOSED') == $row['custdata'] ? '<span class="vbordersroomclosed"><i class="'.VikBookingIcons::i('ban').'"></i> '.JText::translate('VBDBTEXTROOMCLOSED').'</span>' : $custdata;

		if ($row['status'] == "confirmed") {
			$saystaus = '<span class="label label-success vbo-status-label">' . JText::translate('VBCONFIRMED') . '</span>';
			if (!empty($row['type']) && !strcasecmp($row['type'], 'overbooking')) {
				$saystaus .= '<div class="vbo-orders-substatus"><span class="label label-error">' . JText::translate('VBO_BTYPE_' . strtoupper($row['type'])) . '</span></div>';
			}
		} elseif ($row['status'] == "standby") {
			$status_type = !empty($row['type']) ? JText::translate('VBO_BTYPE_' . strtoupper($row['type'])) : JText::translate('VBSTANDBY');
			$saystaus = '<span class="label label-warning vbo-status-label">' . $status_type . '</span>';
		} else {
			$saystaus = '<span class="label label-error vbo-status-label">' . JText::translate('VBCANCELLED') . '</span>';
		}
		$ts_info = getdate($row['ts']);
		$ts_wday = JText::translate('VB'.strtoupper(substr($ts_info['weekday'], 0, 3)));
		$checkin_info = getdate($row['checkin']);
		$checkin_wday = JText::translate('VB'.strtoupper(substr($checkin_info['weekday'], 0, 3)));
		$checkout_info = getdate($row['checkout']);
		$checkout_wday = JText::translate('VB'.strtoupper(substr($checkout_info['weekday'], 0, 3)));
		?>
	
		<tr class="row<?php echo $kk; ?>">
			<td class="skip">
				<input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row['id']; ?>" onclick="Joomla.isChecked(this.checked);">
			</td>
			<td class="center">
				<a class="vbo-bookingid" href="index.php?option=com_vikbooking&amp;task=editorder&amp;cid[]=<?php echo $row['id']; ?>"><?php echo $row['id']; ?></a>
			</td>
			<td>
				<a class="vbo-bookingslist-viewdet-link" href="index.php?option=com_vikbooking&amp;task=editorder&amp;cid[]=<?php echo $row['id']; ?>">
					<div class="vbo-bookingslist-viewdet">
						<div class="vbo-bookingslist-viewdet-open">
							<?php VikBookingIcons::e('external-link'); ?>
						</div>
						<div class="vbo-bookingslist-viewdet-fulldate">
							<div class="vbo-bookingslist-viewdet-date">
							<?php
							if (strpos($df, 'd') < strpos($df, 'm')) {
								//assuming d/m/Y or similar
								?>
								<span><?php echo $ts_info['mday']; ?></span>
								<span><?php echo $monsmap[($ts_info['mon'] - 1)]; ?></span>
								<?php
							} else {
								//assuming m/d/Y or similar
								?>
								<span><?php echo $monsmap[($ts_info['mon'] - 1)]; ?></span>
								<span><?php echo $ts_info['mday']; ?></span>
								<?php
							}
							?>
								<span><?php echo $ts_info['year']; ?></span>
							</div>
							<div class="vbo-bookingslist-viewdet-time">
								<span class="vbo-bookingslist-viewdet-wday"><?php echo $ts_wday; ?></span>
								<span class="vbo-bookingslist-viewdet-hour"><?php echo date('H:i', $row['ts']); ?></span>
							</div>
						</div>
					</div>
				</a>
			</td>
			<td>
			<?php
			if (!empty($customer_avatar)) {
				// build caption name
				$avatar_caption = '';
				if (isset($cust_country) && !empty($cust_country) && !empty($cust_country['first_name'])) {
					$avatar_caption = $cust_country['first_name'] . ' ' . $cust_country['last_name'];
				}
				?>
				<div class="vbo-customer-info-box">
					<div class="vbo-customer-info-box-name">
						<?php echo $custdata; ?>
					</div>
					<div class="vbo-customer-info-box-avatar vbo-customer-avatar-small">
						<span>
							<img src="<?php echo strpos($customer_avatar, 'http') === 0 ? $customer_avatar : VBO_SITE_URI . 'resources/uploads/' . $customer_avatar; ?>" data-caption="<?php echo htmlspecialchars($avatar_caption); ?>" />
						</span>
					</div>
				</div>
				<?php
			} else {
				echo $custdata;
			}
			?>
				<span class="vbo-orders-review-link" style="display: none;" title="<?php echo addslashes(JText::translate('VBOSEEGUESTREVIEW')); ?>" data-bid="<?php echo $row['id']; ?>"></span>
			</td>
			<td class="center">
				<?php
				if (count($room_names) > 1) {
					if ($row['split_stay']) {
						?>
					<div class="vbo-booking-with-splitstay">
						<span class="hasTooltip vbo-tip-small vbo-room-sharedcalendar vbo-booking-has-splitstay" title="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_SPLIT_STAY')); ?>"><?php VikBookingIcons::e('random'); ?></span>
						<span class="hasTooltip vbo-tip-small vbo-bookingslist-numrooms" title="<?php echo implode(', ', $room_names); ?>"><?php echo $row['roomsnum']; ?></span>
					</div>
						<?php
					} else {
					?>
					<span class="hasTooltip vbo-tip-small vbo-bookingslist-numrooms" title="<?php echo implode(', ', $room_names); ?>"><?php echo $row['roomsnum']; ?></span>
					<?php
					}
				} else {
					?>
					<span class="vbo-bookingslist-roomname"><?php echo $row['roomsnum'] == 1 && count($room_names) > 0 ? $room_names[0] : $row['roomsnum']; ?></span>
					<?php
					$room_number = '';
					if ($rooms && !empty($rooms[0]) && !empty($rooms[0]['roomindex']) && !empty($rooms[0]['params'])) {
						// print the room number when it's just one room booked
						$room_params = json_decode($rooms[0]['params'], true);
						if (is_array($room_params) && !empty($room_params['features']) && is_array($room_params['features'])) {
							// parse distinctive features
							foreach ($room_params['features'] as $rind => $rfeatures) {
								if ($rind != $rooms[0]['roomindex']) {
									continue;
								}
								foreach ($rfeatures as $fname => $fval) {
									if (strlen($fval)) {
										$room_number = '#' . $fval;
										break 2;
									}
								}
							}
						}
					}
					if ($room_number) {
						?>
					<span class="vbo-bookingslist-roomunit"><?php echo $room_number; ?></span>
						<?php
					}
				}
				?>
			</td>
			<td>
				<span class="vbo-tooltip vbo-tooltip-top badge badge-info vbo-bookslist-snapshot" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', JText::translate('VBOPREVIEW')); ?>" data-bid="<?php echo $row['id']; ?>"><?php VikBookingIcons::e('eye'); ?></span>
				<span><?php echo $peoplestr; ?></span>
			</td>
			<td>
				<div class="vbo-bookingslist-booktime vbo-bookingslist-booktime-checkin">
					<div class="vbo-bookingslist-booktime-fulldate">
						<div class="vbo-bookingslist-booktime-date">
							<span><?php echo date(str_replace("/", $datesep, $df), $row['checkin']); ?></span>
						</div>
						<div class="vbo-bookingslist-booktime-time">
							<span class="vbo-bookingslist-booktime-twrap">
								<span class="vbo-bookingslist-booktime-wday"><?php echo $checkin_wday; ?></span>
								<span class="vbo-bookingslist-booktime-hour"><?php echo date('H:i', $row['checkin']); ?></span>
							</span>
						<?php
						if ($row['checked'] == 1) {
							//checked in
							?>
							<span class="vbo-bookingslist-booktime-checkedin"><i class="<?php echo VikBookingIcons::i('circle', 'hasTooltip'); ?>" title="<?php echo JText::translate('VBOCHECKEDSTATUSIN'); ?>"></i></span>
							<?php
						} elseif ($row['checked'] < 0) {
							//no show
							?>
							<span class="vbo-bookingslist-booktime-noshow"><i class="<?php echo VikBookingIcons::i('circle', 'hasTooltip'); ?>" title="<?php echo JText::translate('VBOCHECKEDSTATUSNOS'); ?>"></i></span>
							<?php
						}
						?>
						</div>
					</div>
				</div>
			</td>
			<td>
				<div class="vbo-bookingslist-booktime vbo-bookingslist-booktime-checkin">
					<div class="vbo-bookingslist-booktime-fulldate">
						<div class="vbo-bookingslist-booktime-date">
							<span><?php echo date(str_replace("/", $datesep, $df), $row['checkout']); ?></span>
						</div>
						<div class="vbo-bookingslist-booktime-time">
							<span class="vbo-bookingslist-booktime-twrap">
								<span class="vbo-bookingslist-booktime-wday"><?php echo $checkout_wday; ?></span>
								<span class="vbo-bookingslist-booktime-hour"><?php echo date('H:i', $row['checkout']); ?></span>
							</span>
						<?php
						if ($row['checked'] == 2) {
							//checked out
							?>
							<span class="vbo-bookingslist-booktime-checkedout"><i class="<?php echo VikBookingIcons::i('circle', 'hasTooltip'); ?>" title="<?php echo JText::translate('VBOCHECKEDSTATUSOUT'); ?>"></i></span>
							<?php
						}
						?>
						</div>
					</div>
				</div>
			</td>
			<td class="center">
				<span class="vbo-bookingslist-numnights"><?php echo $row['days']; ?></span>
			</td>
			<td class="center">
				<div class="vbo-bookingslist-total-wrap">
					<div class="vbo-bookingslist-total-amount">
						<span><?php echo strlen($otacurrency) > 0 ? $otacurrency : $currencysymb; ?></span>
						<span><?php echo VikBooking::numberFormat($isdue); ?></span>
					</div>
				<?php
				if (!is_null($row['totpaid'])) {
					?>
					<div class="vbo-bookingslist-total-totpaid">
						<span><?php echo $currencysymb; ?></span>
						<span><?php echo VikBooking::numberFormat($row['totpaid']); ?></span>
					<?php
					if ($row['refund'] > 0) {
						?>
						<span class="vbo-bookingslist-total-refunded">
							<span><?php echo $currencysymb; ?></span>
							<span><?php echo VikBooking::numberFormat($row['refund']); ?></span>
						</span>
						<?php
					}
					?>
					</div>
					<?php
				} elseif ($row['refund'] > 0) {
					?>
					<div class="vbo-bookingslist-total-totpaid">
						<span class="vbo-bookingslist-total-refunded">
							<span><?php echo $currencysymb; ?></span>
							<span><?php echo VikBooking::numberFormat($row['refund']); ?></span>
						</span>
					</div>
					<?php
				}
				?>
				</div>
			</td>
			<td class="center">
			<?php
			$bcolortag = VikBooking::applyBookingColorTag($row, $colortags);
			if (count($bcolortag) > 0) {
				$bcolortag['name'] = JText::translate($bcolortag['name']);
				?>
				<div class="vbo-colortag-circle hasTooltip" style="background-color: <?php echo $bcolortag['color']; ?>;" title="<?php echo $bcolortag['name']; ?>" data-ctagcolor="<?php echo $bcolortag['color']; ?>" data-bid="<?php echo $row['id']; ?>"></div>
				<?php
			}
			?>
			</td>
			<td class="center">
				<?php
				if (!empty($row['adminnotes'])) {
					?>
				<span class="vbo-admin-tipsicon vbo-admin-notes-icn" data-bid="<?php echo $row['id']; ?>"><?php VikBookingIcons::e('sticky-note'); ?></span>&nbsp;
				<div class="vbo-order-admin-notes-cnt" data-bid="<?php echo $row['id']; ?>" style="display: none;"><?php echo nl2br($row['adminnotes']); ?></div>
					<?php
				}
				$invoice_path = implode(DIRECTORY_SEPARATOR, [VBO_SITE_PATH, 'helpers', 'invoices', 'generated', "{$row['id']}_{$row['sid']}.pdf"]);
				echo (is_file($invoice_path) ? '<a class="hasTooltip vbo-admin-invoiceicon" href="'.VBO_SITE_URI.'helpers/invoices/generated/'.$row['id'].'_'.$row['sid'].'.pdf" target="_blank" title="' . htmlspecialchars(JText::translate('VBOINVDOWNLOAD')) . '"><i class="' . VikBookingIcons::i('invoice') . '"></i></a>' : '');
				?>
				<span class="vbo-bookings-guestmessages-bubble-cont vbo-admin-tipsicon" style="display: none;" data-bid="<?php echo $row['id']; ?>">
					<a href="index.php?option=com_vikbooking&task=editorder&cid[]=<?php echo $row['id']; ?>#messaging">
						<i class="<?php echo VikBookingIcons::i('commenting'); ?>" data-message-count="0"></i>
					</a>
				</span>
			</td>
			<td class="center">
				<?php echo $saystaus; ?>
			</td>
			<td class="center">
			<?php
			if (!empty($row['channel'])) {
				//VBO 1.10: use of strtolower($otachannelclass) next to the class 'vbotasp' is deprecated. Fetch the OTA logo from VCM
				$ota_logo_img = is_object($vcm_logos) ? $vcm_logos->setProvenience($otachannel, $row['channel'])->getLogoURL() : false;
				if ($ota_logo_img !== false) {
					?>
				<img src="<?php echo $ota_logo_img; ?>" class="vbo-channelimg-medium"/>
					<?php
				} else {
					?>
				<span class="vbo-provenience"><?php echo $otachannel; ?></span>
					<?php
				}
			} else {
				?>
				<span class="vbo-provenience"><?php echo JText::translate('VBORDFROMSITE'); ?></span>
				<?php
			}
			?>
			</td>
		</tr>
		<?php
		$kk = 1 - $kk;
		
	}
	?>
	
</table>
</div>
<input type="hidden" name="option" value="com_vikbooking" />
<input type="hidden" name="cust_id" id="cust_id" value="<?php echo !empty($pcust_id) ? $pcust_id : ''; ?>" />
<input type="hidden" name="task" value="orders" />
<input type="hidden" name="boxchecked" value="0" />
<?php echo JHtml::fetch('form.token'); ?>
<?php echo $navbut; ?>
</form>

<a style="display: none;" id="vcm-review-details-base" href="index.php?option=com_vikchannelmanager&task=reviews&revid="></a>

<script type="text/javascript">
if (jQuery.isFunction(jQuery.fn.tooltip)) {
	jQuery(".hasTooltip").tooltip();
} else {
	jQuery.fn.tooltip = function(){};
}
function vboToggleDateFilt(dtype) {
	if (!(dtype.length > 0)) {
		document.getElementById('vbo-dates-cont').style.display = 'none';
		document.getElementById('vbo-date-from').value = '';
		document.getElementById('vbo-date-to').value = '';
		return true;
	}
	document.getElementById('vbo-dates-cont').style.display = 'inline-block';
	return true;
}
var bctags_tip = "<?php echo $bctags_tip; ?>";
var applying_tag = false;
var bctags_tip_on = false;

jQuery(function() {

	jQuery(document.body).on('click', '.vbo-colortag-circle', function() {
		if (!jQuery(this).parent().find(".vbo-blist-tip-bctag-subtip").length) {
			jQuery(".vbo-blist-tip-bctag-subtip").remove();
			var cur_color = jQuery(this).attr("data-ctagcolor");
			var cur_bid = jQuery(this).attr("data-bid");
			jQuery(this).after("<div class=\"vbo-blist-tip-bctag-subtip\">"+bctags_tip+"</div>");
			jQuery(this).parent().find(".vbo-blist-tip-bctag-subtip").find(".vbo-blist-tip-bctag-subtip-circle[data-ctagcolor='"+cur_color+"']").addClass("vbo-blist-tip-bctag-activecircle").css('border-color', cur_color);
			jQuery(this).parent().find(".vbo-blist-tip-bctag-subtip").find(".vbo-blist-tip-bctag-subtip-circle").attr('data-bid', cur_bid);
			jQuery(".vbo-blist-tip-bctag-subtip .hasTooltip").tooltip();
			bctags_tip_on = true;
		} else {
			jQuery(".vbo-blist-tip-bctag-subtip").remove();
			bctags_tip_on = false;
		}
	});

	jQuery(document.body).on('click', '.vbo-blist-tip-bctag-subtip-circle', function() {
		if (applying_tag === true) {
			return false;
		}
		applying_tag = true;
		var clickelem = jQuery(this);
		var ctagkey = clickelem.attr('data-ctagkey');
		var bid = clickelem.attr('data-bid');
		// set opacity to circles as loading
		jQuery('.vbo-blist-tip-bctag-subtip-circle').css('opacity', '0.6');

		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=setbookingtag'); ?>",
			{
				tmpl: "component",
				idorder: bid,
				tagkey: ctagkey
			},
			(res) => {
				applying_tag = false;
				if (res.indexOf('e4j.error') >= 0) {
					console.log(res);
					alert(res.replace("e4j.error.", ""));
					//restore loading opacity in circles
					jQuery('.vbo-blist-tip-bctag-subtip-circle').css('opacity', '1');
				} else {
					var obj_res = JSON.parse(res);
					jQuery(clickelem).closest(".vbo-blist-tip-bctag-subtip").parent().find(".vbo-colortag-circle").css("background-color", obj_res.color).attr('data-ctagcolor', obj_res.color).attr('data-original-title', obj_res.name);
					jQuery(".vbo-blist-tip-bctag-subtip").remove();
					bctags_tip_on = false;
				}
			},
			(err) => {
				applying_tag = false;
				alert("Request Failed");
				//restore loading opacity in circles
				jQuery('.vbo-blist-tip-bctag-subtip-circle').css('opacity', '1');
			}
		);
	});

	jQuery('.vbo-bookingslist-viewdet-link').click(function(e) {
		if (e && e.target.tagName.toUpperCase() == 'I') {
			//open the link in a new window
			e.preventDefault();
			window.open(jQuery(this).attr('href'), '_blank');
		}
	});

	jQuery('#vbo-date-from').datepicker({
		showOn: 'focus',
		dateFormat: '<?php echo $juidf; ?>',
		onSelect: function( selectedDate ) {
			jQuery('#vbo-date-to').datepicker('option', 'minDate', selectedDate);
		}
	});

	jQuery('#vbo-date-to').datepicker({
		showOn: 'focus',
		dateFormat: '<?php echo $juidf; ?>',
		onSelect: function( selectedDate ) {
			jQuery('#vbo-date-from').datepicker('option', 'maxDate', selectedDate);
		}
	});

	//Search customer - Start
	var vbocustsdelay = (function() {
		var timer = 0;
		return function(callback, ms) {
			clearTimeout (timer);
			timer = setTimeout(callback, ms);
		};
	})();

	function vboCustomerSearch(words) {
		jQuery("#vbo-allbsearchcust-res").hide().html("");
		jQuery("#customernominative").addClass('vbo-allbsearchcust-loading-inp');

		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=searchcustomer'); ?>",
			{
				kw: words,
				nopin: 1,
				tmpl: "component"
			},
			(cont) => {
				if (cont) {
					var obj_res = typeof cont === 'string' ? JSON.parse(cont) : cont;
					jQuery("#vbo-allbsearchcust-res").html(obj_res[1]);
				} else {
					jQuery("#vbo-allbsearchcust-res").html("");
				}
				jQuery("#vbo-allbsearchcust-res").show();
				jQuery("#customernominative").removeClass('vbo-allbsearchcust-loading-inp');
			},
			(err) => {
				jQuery("#customernominative").removeClass('vbo-allbsearchcust-loading-inp');
				alert("Error Searching.");
			}
		);
	}

	jQuery("#customernominative").keyup(function(event) {
		vbocustsdelay(function() {
			var keywords = jQuery("#customernominative").val();
			if (keywords.length > 1) {
				if ((event.which > 96 && event.which < 123) || (event.which > 64 && event.which < 91) || event.which == 13) {
					vboCustomerSearch(keywords);
				}
			} else {
				if (jQuery("#vbo-allbsearchcust-res").is(":visible")) {
					jQuery("#vbo-allbsearchcust-res").hide();
				}
			}
		}, 600);
	});

	jQuery(document).on('click', '.vbo-custsearchres-entry', function() {
		var customer_id = jQuery(this).attr('data-custid');
		if (customer_id.length) {
			document.location.href = 'index.php?option=com_vikbooking&task=orders&cust_id='+customer_id;
		}
	});
	//Search customer - End

	jQuery(document).keydown(function(e) {
		if ( e.keyCode == 27 && bctags_tip_on === true ) {
			jQuery(".vbo-blist-tip-bctag-subtip").remove();
			bctags_tip_on = false;
		}
		if (e.keyCode == 13 && !e.altKey && !e.ctrlKey && !e.shiftKey) {
			//prevent form-submit by hitting enter
			e.preventDefault();
			return false;
		}
	});

	jQuery(document).mouseup(function(e) {
		if (!bctags_tip_on) {
			return false;
		}
		if (jQuery(".vbo-blist-tip-bctag-subtip").length) {
			var vbo_overlay_subtip_cont = jQuery(".vbo-blist-tip-bctag-subtip-inner");
			if (!vbo_overlay_subtip_cont.is(e.target) && vbo_overlay_subtip_cont.has(e.target).length === 0) {
				jQuery(".vbo-blist-tip-bctag-subtip").remove();
				bctags_tip_on = false;
				return true;
			}
		}
	});

	jQuery(".vbo-bookingslist-table tr td").not(".skip").click(function() {
		//the checkbox for the booking is on the first TD of the row
		var trcbox = jQuery(this).parent("tr").find("td").first().find("input[type='checkbox']");
		if (!trcbox || !trcbox.length) {
			return;
		}
		trcbox.prop('checked', !(trcbox.prop('checked')));
		if (typeof Joomla !== 'undefined' && Joomla != null) {
			Joomla.isChecked(trcbox.prop('checked'));
		}
	});

	jQuery(".vbo-bookingslist-table tr").dblclick(function() {
		if (document.selection && document.selection.empty) {
			document.selection.empty();
		} else if (window.getSelection) {
			var sel = window.getSelection();
			sel.removeAllRanges();
		}
		//the link to the booking details page is on the third TD of the row
		var olink = jQuery(this).find("td").first().next().next().find("a");
		if (!olink || !olink.length) {
			return;
		}
		document.location.href = olink.attr("href");
	});

	<?php
	if ($filters_set) {
		?>
	jQuery("#vbo-search-tools-btn").trigger("click");
		<?php
	}
	?>

	// count unread guest messages
	vcmCountGuestMessages();

	// load guest reviews
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings_have_reviews'); ?>",
		{
			tmpl: "component",
			bids: <?php echo json_encode($vcm_bids); ?>
		},
		(res) => {
			try {
				var obj = JSON.parse(res);
				var base_link = jQuery('#vcm-review-details-base').attr('href');
				for (var bid in obj) {
					if (!obj.hasOwnProperty(bid)) {
						continue;
					}
					jQuery('.vbo-orders-review-link[data-bid="' + bid + '"]').html('<a href="' + base_link + obj[bid] + '" target="_blank"><?php VikBookingIcons::e('star'); ?></a>').fadeIn();
				}
			} catch(err) {
				console.error(err);
			}
		},
		(err) => {
			console.log('request failed for loading booking reviews');
		}
	);

	// zoom-able avatars
	jQuery('.vbo-customer-info-box-avatar').each(function() {
		var img = jQuery(this).find('img');
		if (!img.length) {
			return;
		}
		// register click listener
		img.on('click', function(e) {
			// stop events propagation
			e.preventDefault();
			e.stopPropagation();

			// check for caption
			var caption = jQuery(this).attr('data-caption');

			// build modal content
			var zoom_modal = jQuery('<div></div>').addClass('vbo-modal-overlay-block vbo-modal-overlay-zoom-image').css('display', 'block');
			var zoom_dismiss = jQuery('<a></a>').addClass('vbo-modal-overlay-close');
			zoom_dismiss.on('click', function() {
				jQuery('.vbo-modal-overlay-zoom-image').fadeOut();
			});
			zoom_modal.append(zoom_dismiss);
			var zoom_content = jQuery('<div></div>').addClass('vbo-modal-overlay-content vbo-modal-overlay-content-zoom-image');
			var zoom_head = jQuery('<div></div>').addClass('vbo-modal-overlay-content-head');
			var zoom_head_title = jQuery('<span></span>');
			if (caption) {
				zoom_head_title.text(caption);
			}
			var zoom_head_close = jQuery('<span></span>').addClass('vbo-modal-overlay-close-times').html('&times;');
			zoom_head_close.on('click', function() {
				jQuery('.vbo-modal-overlay-zoom-image').fadeOut();
			});
			zoom_head.append(zoom_head_title).append(zoom_head_close);
			var zoom_body = jQuery('<div></div>').addClass('vbo-modal-overlay-content-body vbo-modal-overlay-content-body-scroll');
			var zoom_image = jQuery('<div></div>').addClass('vbo-modal-zoom-image-wrap');
			zoom_image.append(jQuery(this).clone());
			zoom_body.append(zoom_image);
			zoom_content.append(zoom_head).append(zoom_body);
			zoom_modal.append(zoom_content);
			// append modal to body
			if (jQuery('.vbo-modal-overlay-zoom-image').length) {
				jQuery('.vbo-modal-overlay-zoom-image').remove();
			}
			jQuery('body').append(zoom_modal);
		});
	});

	// open admin notes for a booking
	jQuery('.vbo-admin-notes-icn').click(function(e) {
		e.stopPropagation();
		var now_bid = jQuery(this).attr('data-bid');
		var hid_elm = jQuery('.vbo-order-admin-notes-cnt[data-bid="' + now_bid + '"]');
		if (!hid_elm || !hid_elm.length) {
			return;
		}
		VBOCore.displayModal({
			extra_class: 'vbo-modal-rounded',
			title: '<span class="label label-info">' + now_bid + '</span> ' + Joomla.JText._('VBADMINNOTESTOGGLE'),
			body: hid_elm.html(),
		});
	});

	// view booking snapshot
	jQuery('.vbo-bookslist-snapshot').on('click', function(e) {
		e.stopPropagation();
		VBOCore.handleDisplayWidgetNotification({widget_id: 'booking_details'}, {booking_id: jQuery(this).attr('data-bid')});
	});

});

function vcmCountGuestMessages() {
	<?php
	// VCM Chat Guest Messages (if VCM is installed)
	if (count($vcm_bids) && is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
		?>
	var all_bids = <?php echo json_encode($vcm_bids); ?>;
	VBOCore.doAjax(
		"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=chat.count_unread_messages'); ?>",
		{
			tmpl: "component",
			id_order: all_bids
		},
		(res) => {
			var counters = JSON.parse(res);
			for (var bid in all_bids) {
				if (!all_bids.hasOwnProperty(bid)) {
					continue;
				}
				var booking_bubble = jQuery('.vbo-bookings-guestmessages-bubble-cont[data-bid="'+all_bids[bid]+'"]');
				if (!booking_bubble || !booking_bubble.length) {
					continue;
				}
				if (!counters.hasOwnProperty(all_bids[bid])) {
					// 0 messages to be read
					booking_bubble.find('i').attr('data-message-count', '0');
					booking_bubble.hide();
					continue;
				}
				booking_bubble.find('i').attr('data-message-count', counters[all_bids[bid]]);
				if (parseInt(counters[all_bids[bid]]) > 0) {
					booking_bubble.fadeIn();
				} else {
					booking_bubble.hide();
				}
			}
		},
		(err) => {
			console.error('AJAX request failed for counting guest messages from VCM');
		}
	);
		<?php
	}
	?>
}
</script>
<?php

//Invoices
$pconfirmgen = VikRequest::getInt('confirmgen', '', 'request');
if (count($cid) > 0 && !empty($cid[0])) {
	$oldinvdate = '';
	$nextinvnum = VikBooking::getNextInvoiceNumber();
	$invsuff = VikBooking::getInvoiceNumberSuffix();
	$companyinfo = VikBooking::getInvoiceCompanyInfo();
	//if editing an invoice (re-creating an existing invoice for a booking), do not increment the invoice number
	if (count($cid) == 1) {
		//the generate invoice btn of the booking details page can pass the invoice notes field via hidden field. If not empty, update invoice notes for this id
		$pinvnotes = VikRequest::getString('invnotes', '', 'request', VIKREQUEST_ALLOWHTML);
		if (!empty($pinvnotes)) {
			$pinvnotes = strpos($pinvnotes, '<br') !== false ? $pinvnotes : nl2br($pinvnotes);
			$q = "UPDATE `#__vikbooking_orders` SET `inv_notes`=".$dbo->quote($pinvnotes)." WHERE `id`=".(int)$cid[0].";";
			$dbo->setQuery($q);
			$dbo->execute();
		}
		//
		$q = "SELECT `number`,`for_date` FROM `#__vikbooking_invoices` WHERE `idorder`=" . (int)$cid[0];
		$dbo->setQuery($q, 0, 1);
		$prev_data = $dbo->loadAssoc();
		if ($prev_data) {
			$oldinvdate = $prev_data['for_date'];
			$prev_inv_number = intval(str_replace($invsuff, '', $prev_data['number']));
			if ($prev_inv_number > 0) {
				$nextinvnum = $prev_inv_number;
			}
		}
	}
	//
	?>
<div class="vbo-gen-invoices-helper" style="display: none;">
	<form action="index.php?option=com_vikbooking" method="post" id="vbo-geninv-form">
		<div class="vbo-calendar-cfield-entry">
			<label for="invoice_num"><?php echo JText::translate('VBINVSTARTNUM'); ?></label>
			<span><input type="number" min="1" size="4" value="<?php echo $nextinvnum; ?>" id="invoice_num" name="invoice_num" /></span>
		</div>
		<div class="vbo-calendar-cfield-entry">
			<label for="invoice_suff"><?php echo JText::translate('VBINVNUMSUFFIX'); ?></label>
			<span><input type="text" size="7" value="<?php echo $invsuff; ?>" id="invoice_suff" name="invoice_suff" /></span>
		</div>
		<div class="vbo-calendar-cfield-entry">
			<label for="invoice_date"><?php echo JText::translate('VBINVUSEDATE'); ?></label>
			<span>
				<select id="invoice_date" name="invoice_date">
				<?php
				if (!empty($oldinvdate)) {
					?>
					<option value="<?php echo date($df, $oldinvdate); ?>"><?php echo date($df, $oldinvdate); ?></option>
					<?php
				}
				?>
					<option value="<?php echo date($df, time()); ?>"><?php echo date($df, time()); ?></option>
					<option value="0"><?php echo JText::translate('VBINVUSEDATEBOOKING'); ?></option>
				</select>
			</span>
		</div>
		<div class="vbo-calendar-cfield-entry">
			<label for="company_info"><?php echo JText::translate('VBINVCOMPANYINFO'); ?></label>
			<span><textarea name="company_info" id="company_info" style="width: 98%; min-width: 98%; max-width: 98%; height: 70px;"><?php echo $companyinfo; ?></textarea></span>
		</div>
		<div class="vbo-calendar-cfield-entry">
			<label for="invoice_send"><i class="vboicn-envelop"></i><?php echo JText::translate('VBINVSENDVIAMAIL'); ?></label>
			<span><select id="invoice_send" name="invoice_send"><option value=""><?php echo JText::translate('VBNO'); ?></option><option value="1"><?php echo JText::translate('VBYES'); ?></option></select></span>
		</div>
		<div class="vbo-calendar-cfields-bottom">
			
		</div>
	<?php
	foreach ($cid as $invid) {
		echo '<input type="hidden" name="cid[]" value="'.$invid.'" />';
	}
	if ($pconfirmgen > 0) {
		echo '<input type="hidden" name="confirmgen" value="'.$cid[0].'" />';
	}
	?>
		<input type="hidden" name="option" value="com_vikbooking" />
		<input type="hidden" name="task" value="geninvoices" />
	</form>
</div>

<script type="text/javascript">
	jQuery(function() {
		
		// render modal to generate the invoice(s)
		var gen_invoices_modal_body = VBOCore.displayModal({
			suffix: 'invoices',
			extra_class: 'vbo-modal-rounded vbo-modal-tall',
			title: Joomla.JText._('VBOGENINVOICES') + ' (<?php echo count($cid); ?>)',
			footer_right: '<button type="button" class="btn btn-success" onclick="document.getElementById(\'vbo-geninv-form\').submit();"><i class="vboicn-file-text2"></i><?php echo addslashes(JText::translate('VBOGENINVOICES')); ?></button>',
		<?php
		if (count($cid) === 1) {
			?>
			footer_left: '<a href="index.php?option=com_vikbooking&task=editbusy&cid[]=<?php echo $cid[0]; ?>&frominv=1" class="btn vbo-config-btn"><?php echo addslashes(JText::translate('VBINVEDITBINFO')); ?></a>',
			<?php
		}
		?>
			onDismiss: () => {
				jQuery('#vbo-geninv-form').appendTo('.vbo-gen-invoices-helper');
			},
		});

		// set modal content
		jQuery('#vbo-geninv-form').appendTo(gen_invoices_modal_body);

		<?php
		if ($pconfirmgen > 0) {
			?>
		// prompt for immediate generation
		if (confirm(Joomla.JText._('VBCONFIRMGENINV'))) {
			document.getElementById('vbo-geninv-form').submit();
		}
			<?php
		}
		?>
	});
</script>
<?php
}

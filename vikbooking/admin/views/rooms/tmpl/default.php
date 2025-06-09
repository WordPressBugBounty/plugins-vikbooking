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

$dbo = JFactory::getDbo();
$app = JFactory::getApplication();

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();

$config = VBOFactory::getConfig();

JText::script('VBO_CREATE_LISTING_ON_OTA');
JText::script('VBOCHECKINSTATUSUPDATED');
JText::script('VBO_OTA_ACCOUNT_ID');
JText::script('VBO_OTA_LISTING_ID');
JText::script('VBANNULLA');
JText::script('VBSAVE');

$rows = $this->rows;
$lim0 = $this->lim0;
$navbut = $this->navbut;
$orderby = $this->orderby;
$ordersort = $this->ordersort;

$prname = $app->getUserStateFromRequest("vbo.rooms.rname", 'rname', '', 'string');
$pidcat = $app->getUserStateFromRequest("vbo.rooms.idcat", 'idcat', 0, 'int');
?>
<div class="vbo-list-form-filters vbo-btn-toolbar">
	<form action="index.php?option=com_vikbooking&amp;task=rooms" method="post" name="roomsform">
		<div style="width: 100%; display: inline-block;" class="btn-toolbar" id="filter-bar">
			<div class="btn-group pull-left">
				<select name="idcat" id="idcat" onchange="document.roomsform.submit();">
					<option value=""><?php echo JText::translate('VBOCATEGORYFILTER'); ?></option>
				<?php
				foreach ($this->allcats as $cat) {
					?>
					<option value="<?php echo $cat['id']; ?>"<?php echo $cat['id'] == $pidcat ? ' selected="selected"' : ''; ?>><?php echo $cat['name']; ?></option>
					<?php
				}
				?>
				</select>
			</div>
			<div class="btn-group pull-left input-append btn-group-right-el">
				<input type="text" name="rname" id="rname" value="<?php echo $prname; ?>" size="40" placeholder="<?php echo JText::translate('VBPVIEWROOMONE'); ?>"/>
				<button type="button" class="btn btn-secondary" onclick="document.roomsform.submit();"><i class="icon-search"></i></button>
			</div>
			<div class="btn-group pull-left">
				<button type="button" class="btn btn-secondary" onclick="document.getElementById('rname').value='';document.getElementById('idcat').value='';document.roomsform.submit();"><?php echo JText::translate('JSEARCH_FILTER_CLEAR'); ?></button>
			</div>
		</div>
		<input type="hidden" name="task" value="rooms" />
		<input type="hidden" name="option" value="com_vikbooking" />
	</form>
</div>
<script type="text/javascript">
jQuery(function() {
	jQuery('#idcat').select2();
});
</script>
<?php
if (empty($rows)) {
	?>
	<p class="warn"><?php echo JText::translate('VBNOROOMSFOUND'); ?></p>
	<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="option" value="com_vikbooking" />
	</form>
	<?php
} else {
	?>
<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm" class="vbo-list-form">
<div class="table-responsive">
	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="table table-striped vbo-list-table">
		<thead>
		<tr>
			<th width="20">
				<input type="checkbox" onclick="Joomla.checkAll(this)" value="" name="checkall-toggle">
			</th>
			<th class="title left" width="150">
				<a href="index.php?option=com_vikbooking&amp;task=rooms&amp;vborderby=name&amp;vbordersort=<?php echo ($orderby == "name" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "name" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "name" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWROOMONE').($orderby == "name" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "name" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title center" align="center" width="75">
				<a href="index.php?option=com_vikbooking&amp;task=rooms&amp;vborderby=toadult&amp;vbordersort=<?php echo ($orderby == "toadult" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "toadult" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "toadult" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWROOMADULTS').($orderby == "toadult" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "toadult" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title center" align="center" width="75">
				<a href="index.php?option=com_vikbooking&amp;task=rooms&amp;vborderby=tochild&amp;vbordersort=<?php echo ($orderby == "tochild" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "tochild" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "tochild" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWROOMCHILDREN').($orderby == "tochild" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "tochild" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title center" align="center" width="75">
				<a href="index.php?option=com_vikbooking&amp;task=rooms&amp;vborderby=totpeople&amp;vbordersort=<?php echo ($orderby == "totpeople" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "totpeople" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "totpeople" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWROOMTOTPEOPLE').($orderby == "totpeople" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "totpeople" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title center" width="75"><?php echo JText::translate( 'VBPVIEWROOMTWO' ); ?></th>
			<th class="title center" align="center" width="75"><?php echo JText::translate( 'VBPVIEWROOMTHREE' ); ?></th>
			<th class="title center" align="center" width="75"><?php echo JText::translate( 'VBPVIEWROOMFOUR' ); ?></th>
			<th class="title center" align="center" width="100"><?php echo JText::translate( 'VBOCHANNELS' ); ?></th>
			<th class="title center" align="center" width="75">
				<a href="index.php?option=com_vikbooking&amp;task=rooms&amp;vborderby=units&amp;vbordersort=<?php echo ($orderby == "units" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "units" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "units" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWROOMSEVEN').($orderby == "units" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "units" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title center" align="center" width="100">
				<a href="index.php?option=com_vikbooking&amp;task=rooms&amp;vborderby=avail&amp;vbordersort=<?php echo ($orderby == "avail" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "avail" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "avail" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::translate('VBPVIEWROOMSIX').($orderby == "avail" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "avail" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
		</tr>
		</thead>
	<?php
	$vcm_logos = VikBooking::getVcmChannelsLogo('', true);
	$website_source_lbl = JText::translate('VBORDFROMSITE');
	$kk = 0;
	$i = 0;
	for ($i = 0, $n = count($rows); $i < $n; $i++) {
		$row = $rows[$i];
		$categories = [];
		if (strlen(trim(str_replace(";", "", (string)$row['idcat']))) > 0) {
			$cat = explode(";", $row['idcat']);
			$catsfound = false;
			$q = "SELECT `name` FROM `#__vikbooking_categories` WHERE ";
			foreach ($cat as $k => $cc) {
				if (!empty($cc)) {
					$q .= "`id`=".$dbo->quote($cc)." ";
					if ($cc != end($cat) && !empty($cat[($k + 1)])) {
						$q .= "OR ";
					}
					$catsfound = true;
				}
			}
			$q .= ";";
			if ($catsfound) {
				$dbo->setQuery($q);
				$lines = $dbo->loadAssocList();
				foreach ($lines as $ll) {
					$categories[] = $ll['name'];
				}
			}
		}
		
		$caratteristiche = '<span class="label">0</span>';
		if (!empty($row['idcarat'])) {
			$tmpcarat = explode(";", $row['idcarat']);
			$caratteristiche = '<span class="label">' . VikBooking::totElements($tmpcarat) . '</span>';
		}
		
		$optionals = '<span class="label">0</span>';
		if (!empty($row['idopt'])) {
			$tmpopt = explode(";", $row['idopt']);
			$optionals = '<span class="label">' . VikBooking::totElements($tmpopt) . '</span>';
		}

		if ($row['fromadult'] == $row['toadult']) {
			$stradult = $row['fromadult'];
		} else {
			$stradult = $row['fromadult'].' - '.$row['toadult'];
		}
		if ($row['fromchild'] == $row['tochild']) {
			$strchild = $row['fromchild'];
		} else {
			$strchild = $row['fromchild'].' - '.$row['tochild'];
		}

		// shared calendar icon
		$sharedcal = '';
		if (!empty($row['sharedcals'])) {
			$sharedcal = '<span class="vbo-room-sharedcalendar" title="' . $this->escape(JText::translate('VBOROOMCALENDARSHARED')) . '"><i class="' . VikBookingIcons::i('calendar-check') . '"></i></span> ';
		}

		// VCM room's channels mapped
		$website_source_lbl_short = substr($website_source_lbl, 0, 1);
		if (function_exists('mb_substr')) {
			$website_source_lbl_short = mb_substr($website_source_lbl, 0, 1, 'UTF-8');
		}
		$roomchannels = array($website_source_lbl => strtoupper($website_source_lbl_short));
		$otachannels  = is_object($vcm_logos) && method_exists($vcm_logos, 'getVboRoomLogosMapped') ? $vcm_logos->getVboRoomLogosMapped($row['id']) : array();
		$roomchannels = count($otachannels) ? array() : $roomchannels;
		$roomchannels = array_merge($roomchannels, $otachannels);

		// VCM room onboardable OTAs
		$onboardable_otas  = [];
		$room_ota_accounts = [];
		if (is_object($vcm_logos) && method_exists($vcm_logos, 'getRoomOnboardableChannels')) {
			$onboardable_otas = $vcm_logos->getRoomOnboardableChannels($row['id']);
			$room_ota_accounts = $vcm_logos->getRoomOtaAccounts();
			$room_ota_accounts = $room_ota_accounts[$row['id']] ?? [];
		}

		// room upgrade option
		$room_upgrade_options = $config->getArray('room_upgrade_options_' . $row['id'], []);
		$room_upgrade_enabled = (!empty($room_upgrade_options['rooms']) && $room_upgrade_options['rooms']);
		?>
		<tr class="row<?php echo $kk; ?>">
			<td><input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row['id']; ?>" onclick="Joomla.isChecked(this.checked);"></td>
			<td class="vbo-highlighted-td"><a href="index.php?option=com_vikbooking&amp;task=editroom&amp;cid[]=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a></td>
			<td class="center"><?php echo $stradult; ?></td>
			<td class="center"><?php echo $strchild; ?></td>
			<td class="center"><?php echo $row['mintotpeople'].' - '.$row['totpeople']; ?></td>
			<td class="center"><?php
			if ($categories && count($categories) < 4) {
				echo implode(', ', $categories);
			} else if ($categories) {
				?>
				<span class="label" title="<?php echo $this->escape(implode(', ', $categories)); ?>"><?php echo count($categories); ?></span>
				<?php
			} else {
				echo '----' ;
			}
			?></td>
			<td class="center">
				<?php
				if (strpos((string)$row['params'], 'geo":{"enabled":1') !== false) {
					?>
					<span class="vbo-room-sharedcalendar" title="<?php echo $this->escape(JText::translate('VBO_GEO_INFO')); ?>"><?php VikBookingIcons::e('map-marked-alt'); ?></span> 
					<?php
				}
				if ($room_upgrade_enabled) {
					?>
					<span class="vbo-room-sharedcalendar" title="<?php echo $this->escape(JText::translate('VBO_ROOM_UPGRADE')); ?>"><?php VikBookingIcons::e('gem'); ?></span> 
					<?php
				}
				echo $caratteristiche;
				?>
			</td>
			<td class="center"><?php echo $optionals; ?></td>
			<td class="center">
				<div class="vbo-room-channels-mapped-wrap">
				<?php
				$ch_counter = 0;
				foreach ($roomchannels as $source => $churi) {
					$is_img = (strpos($churi, 'http') !== false);
					// build readable channel name
					$raw_ch_name  = $source;
					$lower_name   = strtolower($raw_ch_name);
					$lower_name   = preg_replace("/(hotel|vr)$/", ' $1', $lower_name);
					$channel_name = ucwords(preg_replace("/api$/", '', $lower_name));
					// build OTA account attributes
					$ota_account_attributes = [];
					if (!strcasecmp(($room_ota_accounts[$ch_counter]['channel'] ?? ''), $source)) {
						$ota_account_attributes = [
							'data-ota-name="' . $this->escape($channel_name) . '"',
							'data-ota-idroom="' . $this->escape(($room_ota_accounts[$ch_counter]['idroomota'] ?? '')) . '"',
							'data-ota-account-name="' . $this->escape(($room_ota_accounts[$ch_counter]['account_name'] ?? '')) . '"',
							'data-ota-account-id="' . $this->escape(($room_ota_accounts[$ch_counter]['host_main_id'] ?? '')) . '"',
						];
					}
					?>
					<div class="vbo-tooltip vbo-tooltip-top vbo-room-channels-mapped-ch" data-tooltiptext="<?php echo $this->escape($channel_name); ?>"<?php echo $ota_account_attributes ? ' ' . implode(' ', $ota_account_attributes) : ''; ?>>
						<span class="vbo-room-channels-mapped-ch-lbl">
						<?php
						if ($is_img) {
							?>
							<img src="<?php echo $churi; ?>" alt="<?php echo $source; ?>" />
							<?php
						} else {
							?>
							<span><?php echo $churi; ?></span>
							<?php
						}
						?>
						</span>
					</div>
					<?php
					$ch_counter++;
				}
				foreach ($onboardable_otas as $ch_id => $ch_name) {
					$ch_logo_url = $vcm_logos ? $vcm_logos->setProvenience($ch_name, $ch_name)->getTinyLogoURL() : '';
					if (!$ch_logo_url || strpos($ch_logo_url, 'http') === false) {
						continue;
					}
					?>
					<div
						class="vbo-tooltip vbo-tooltip-top vbo-room-channels-onboard-ch"
						data-chid="<?php echo $ch_id; ?>"
						data-chname="<?php echo $this->escape($ch_name); ?>"
						data-roomid="<?php echo $row['id']; ?>"
						data-tooltiptext="<?php echo $this->escape(JText::sprintf('VBO_CREATE_LISTING_ON_OTA', $ch_name)); ?>"
					>
						<span class="vbo-room-channels-onboard-ch-lbl">
							<?php VikBookingIcons::e('plus'); ?>
							<img src="<?php echo $ch_logo_url; ?>" alt="<?php echo $ch_name; ?>" />
						</span>
					</div>
					<?php
				}
				?>
				</div>
			</td>
			<td class="center"><?php echo $sharedcal . '<span class="label label-info">' . $row['units'] . '</span>'; ?></td>
			<td class="center">
				<a href="<?php echo VBOFactory::getPlatform()->getUri()->addCSRF('index.php?option=com_vikbooking&task=modavail&cid[]=' . $row['id'], true); ?>"><?php echo (intval($row['avail'])=="1" ? "<i class=\"".VikBookingIcons::i('check', 'vbo-icn-img')."\" style=\"color: #099909;\" title=\"".JText::translate('VBMAKENOTAVAIL')."\"></i>" : "<i class=\"".VikBookingIcons::i('times-circle', 'vbo-icn-img')."\" style=\"color: #ff0000;\" title=\"".JText::translate('VBMAKEAVAIL')."\"></i>"); ?></a>
			</td>
		 </tr>
		  <?php
		$kk = 1 - $kk;
	}
	?>
	</table>
</div>
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="rooms" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="rname" value="<?php echo $prname; ?>" />
	<input type="hidden" name="idcat" value="<?php echo $pidcat; ?>" />
	<?php echo JHtml::fetch('form.token'); ?>
	<?php echo $navbut; ?>
</form>

<script type="text/javascript">
	jQuery(function() {

		jQuery('.vbo-room-channels-onboard-ch').on('click', function() {
			let channel_id   = jQuery(this).attr('data-chid');
			let channel_name = jQuery(this).attr('data-chname');
			let room_id      = jQuery(this).attr('data-roomid');
			let room_name    = jQuery(this).closest('tr').find('td.vbo-highlighted-td').text();

			let cancel_btn = jQuery('<button></button>')
				.attr('type', 'button')
				.addClass('btn')
				.text(Joomla.JText._('VBANNULLA'))
				.on('click', function() {
					VBOCore.emitEvent('vbo-ota-onboard-listing-dismiss');
				});

			let save_btn = jQuery('<button></button>')
				.attr('type', 'button')
				.addClass('btn btn-success')
				.text(Joomla.JText._('VBSAVE'))
				.on('click', function() {
					// start loading
					VBOCore.emitEvent('vbo-ota-onboard-listing-loading');

					try {
						// prepare toast message container
						VBOToast.create(VBOToast.POSITION_TOP_CENTER);
						VBOToast.changePosition(VBOToast.POSITION_TOP_CENTER);
					} catch(err) {
						// do nothing
					}

					// get form values
					let formValues = jQuery('#vbo-vcm-onboard-listing-form').serialize();

					// perform the request
					VBOCore.doAjax(
						"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=otaonboarding.create'); ?>",
						formValues,
						(resp) => {
							// dispatch toast success message
							VBOToast.enqueue(new VBOToastMessage({
								body:   Joomla.JText._('VBOCHECKINSTATUSUPDATED'),
								icon:   'fas fa-check-circle',
								status: VBOToast.SUCCESS_STATUS,
								action: () => {
									VBOToast.dispose(true);
								},
							}));

							// dismiss the modal on success
							VBOCore.emitEvent('vbo-ota-onboard-listing-dismiss');

							// reload the current page
							window.location.reload();
						},
						(error) => {
							// stop loading
							VBOCore.emitEvent('vbo-ota-onboard-listing-loading');

							// attempt to decode the possible JSON erroneous response
							try {
								let errorData = JSON.parse(error.responseText);

								// dispatch the event to display the current progress
								VBOCore.emitEvent('vbo-vcm-onboard-progress-updated', errorData);

								// display a delayed error
								setTimeout(() => {
									alert(errorData.error);
								}, 100);
							} catch(err) {
								// display the raw error as it was not a JSON-encoded string
								alert(error.responseText);
							}
						}
					);
				});

			let modal_body = VBOCore.displayModal({
				suffix: 'ota-onboard-listing',
				extra_class: 'vbo-modal-rounded vbo-modal-tall',
				title: Joomla.JText._('VBO_CREATE_LISTING_ON_OTA').replace('%s', channel_name) + ' - ' + room_name,
				footer_left: cancel_btn,
				footer_right: save_btn,
				dismiss_event: 'vbo-ota-onboard-listing-dismiss',
				loading_event: 'vbo-ota-onboard-listing-loading',
			});

			// start loading
			VBOCore.emitEvent('vbo-ota-onboard-listing-loading');

			// perform the request
			VBOCore.doAjax(
				"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=otaonboarding.new'); ?>",
				{
					channel_id: channel_id,
					channel_name: channel_name,
					room_id: room_id,
				},
				(resp) => {
					// stop loading
					VBOCore.emitEvent('vbo-ota-onboard-listing-loading');
					try {
						let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;
						modal_body.append(obj_res['html']);
					} catch (err) {
						console.error('Error decoding the response', err, resp);
					}
				},
				(error) => {
					alert(error.responseText);
					// dismiss the modal
					VBOCore.emitEvent('vbo-ota-onboard-listing-dismiss');
				}
			);
		});

		jQuery('.vbo-room-channels-mapped-ch[data-ota-idroom]').on('click', function() {
			let room_name = jQuery(this).closest('tr').find('td.vbo-highlighted-td').text();
			let channel_img = jQuery(this).find('img').attr('src');
			let ota_name = jQuery(this).attr('data-ota-name');
			let ota_idroom = jQuery(this).attr('data-ota-idroom');
			let ota_account_name = jQuery(this).attr('data-ota-account-name');
			let ota_account_id = jQuery(this).attr('data-ota-account-id');

			if (!ota_name || !ota_idroom || !ota_account_id) {
				return;
			}

			let listing_details_html = '';
			listing_details_html += '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
			listing_details_html += '	<div class="vbo-params-wrap">';
			listing_details_html += '		<div class="vbo-params-container">';
			listing_details_html += '			<div class="vbo-params-block">';

			listing_details_html += '				<div class="vbo-param-container">';
			listing_details_html += '					<div class="vbo-param-label"><img class="vbo-room-ota-listing-details" src="' + channel_img + '" title="' + ota_name + '" /></div>';
			listing_details_html += '					<div class="vbo-param-setting"><strong>' + ota_account_name + '</strong></div>';
			listing_details_html += '				</div>';

			listing_details_html += '				<div class="vbo-param-container">';
			listing_details_html += '					<div class="vbo-param-label"><strong>' + Joomla.JText._('VBO_OTA_ACCOUNT_ID') + '</strong></div>';
			listing_details_html += '					<div class="vbo-param-setting"><span class="label label-info">' + ota_account_id + '</span></div>';
			listing_details_html += '				</div>';

			listing_details_html += '				<div class="vbo-param-container">';
			listing_details_html += '					<div class="vbo-param-label"><strong>' + Joomla.JText._('VBO_OTA_LISTING_ID') + '</strong></div>';
			listing_details_html += '					<div class="vbo-param-setting"><span class="label label-info">' + ota_idroom + '</span></div>';
			listing_details_html += '				</div>';

			listing_details_html += '			</div>';
			listing_details_html += '		</div>';
			listing_details_html += '	</div>';
			listing_details_html += '</div>';

			VBOCore.displayModal({
				suffix: 'ota-listing-details',
				extra_class: 'vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter',
				title: room_name,
				body: listing_details_html,
			});
		});

		/**
		 * Mini thumbnails background generation.
		 */
		setTimeout(() => {
			VBOCore.doAjax(
				"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=listings.check_mini_thumbnails'); ?>",
				{},
				(result) => {
					if (typeof result === 'object') {
						console.info('Mini thumbnails processed (' + result.processed + ') and generated (' + result.generated + ').');
					}
				},
				(error) => {
					console.error(error);
				}
			);
		}, 1000);

	});
</script>
<?php
}

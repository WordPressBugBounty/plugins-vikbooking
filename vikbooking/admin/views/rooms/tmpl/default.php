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
			<div class="btn-group pull-left input-append">
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
			<th class="title center" align="center" width="100"><?php echo JText::translate( 'VBPVIEWROOMSIX' ); ?></th>
		</tr>
		</thead>
	<?php
	$vcm_logos = VikBooking::getVcmChannelsLogo('', true);
	$website_source_lbl = JText::translate('VBORDFROMSITE');
	$kk = 0;
	$i = 0;
	for ($i = 0, $n = count($rows); $i < $n; $i++) {
		$row = $rows[$i];
		$categories = "";
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
				if ($lines) {
					$categories = array();
					foreach($lines as $ll) {
						$categories[] = $ll['name'];
					}
					$categories = implode(", ", $categories);
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
			<td class="center"><?php echo $categories ?: '----' ; ?></td>
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
				foreach ($roomchannels as $source => $churi) {
					$is_img = (strpos($churi, 'http') !== false);
					// build readable channel name
					$raw_ch_name  = $source;
					$lower_name   = strtolower($raw_ch_name);
					$lower_name   = preg_replace("/hotel$/", ' hotel', $lower_name);
					$channel_name = ucwords(preg_replace("/api$/", '', $lower_name));
					?>
					<div class="vbo-tooltip vbo-tooltip-top vbo-room-channels-mapped-ch" data-tooltiptext="<?php echo $this->escape($channel_name); ?>">
						<span class="vbo-room-channels-mapped-ch-lbl">
						<?php
						if ($is_img) {
							?>
							<img src="<?php echo $churi ?>" alt="<?php echo $source; ?>" />
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
		unset($categories);
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
<?php
}

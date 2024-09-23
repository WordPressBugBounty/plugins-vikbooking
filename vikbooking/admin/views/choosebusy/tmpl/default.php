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

$reservs = $this->reservs;
$totres = $this->totres;
$pts = $this->pts;
$lim0 = $this->lim0;
$navbut = $this->navbut;

$dbo = JFactory::getDBO();
if (is_file(VBO_SITE_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$reservs[0]['img']) && getimagesize(VBO_SITE_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$reservs[0]['img'])) {
	$img = '<img align="middle" class="maxninety" style="border-radius: 5px;" alt="Room Image" src="' . VBO_SITE_URI . 'resources/uploads/'.$reservs[0]['img'].'" />';
} else {
	$img = '<img align="middle" alt="vikbooking logo" src="' . VBO_ADMIN_URI . 'vikbooking.png' . '" />';
}
$unitsdisp = $reservs[0]['units'] - $totres;
$unitsdisp = ($unitsdisp < 0 ? "0" : $unitsdisp);
$pvcm = VikRequest::getInt('vcm', '', 'request');
$pgoto = VikRequest::getString('goto', '', 'request');
?>
<table class="vbo-choosebusy-table">
	<tr class="vbo-choosebusy-tr1">
		<td><div class="vbadminfaresctitle-chbusy"><?php echo JText::translate('VBMAINCHOOSEBUSY'); ?> <?php echo $reservs[0]['name']; ?></div></td>
	</tr>
	<tr class="vbo-choosebusy-tr2">
		<td><?php echo $img; ?></td>
	</tr>
	<tr class="vbo-choosebusy-tr3">
		<td>
			<div class="vbadminfaresctitle-chbusy">
				<span class="label label-success"><?php echo JText::translate('VBPCHOOSEBUSYCAVAIL'); ?>:</span>
				<span class="badge badge-warning"><?php echo $unitsdisp; ?> / <?php echo $reservs[0]['units']; ?></span>
			</div>
		</td>
	</tr>
</table>

<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm" class="vbo-list-form">
<div class="table-responsive">
	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="table table-striped vbo-list-table">
		<thead>
			<tr>
				<th class="title left" width="50">ID</th>
				<th class="title left" width="150"><?php echo JText::translate( 'VBPVIEWORDERSFOUR' ); ?></th>
				<th class="title left" width="150"><?php echo JText::translate( 'VBPVIEWORDERSFIVE' ); ?></th>
				<th class="title left" width="250"><?php echo JText::translate( 'VBPVIEWORDERSTWO' ); ?></th>
				<th class="title center" width="150"><?php echo JText::translate( 'VBOFEATUNITASSIGNED' ); ?></th>
				<th class="title left" width="150"><?php echo JText::translate( 'VBPCHOOSEBUSYORDATE' ); ?></th>
			</tr>
		</thead>
	<?php
	$nowdf = VikBooking::getDateFormat(true);
	if ($nowdf == "%d/%m/%Y") {
		$df = 'd/m/Y';
	} elseif ($nowdf == "%m/%d/%Y") {
		$df = 'm/d/Y';
	} else {
		$df = 'Y/m/d';
	}
	$datesep = VikBooking::getDateSeparator(true);
	$k = 0;
	$i = 0;
	$room_params = json_decode($reservs[0]['params'], true);
	$or_map = array();
	for ($i = 0, $n = count($reservs); $i < $n; $i++) {
		$row = $reservs[$i];
		//Room specific unit
		$room_first_feature = '----';
		$q = "SELECT `id`,`roomindex` FROM `#__vikbooking_ordersrooms` WHERE `idorder`=".(int)$row['idorder']." AND `idroom`=".(int)$row['idroom'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$roomindexes = $dbo->loadAssocList();
			$usekey = 0;
			if (array_key_exists($row['idorder'], $or_map)) {
				$usekey = count($or_map[$row['idorder']]);
				$or_map[$row['idorder']][] = $row['id'];
			} else {
				$or_map[$row['idorder']] = array($row['id']);
			}
			if (array_key_exists($usekey, $roomindexes) && is_array($room_params) && array_key_exists('features', $room_params) && count($room_params['features']) > 0) {
				foreach ($room_params['features'] as $rind => $rfeatures) {
					if ($rind != $roomindexes[$usekey]['roomindex']) {
						continue;
					}
					foreach ($rfeatures as $fname => $fval) {
						if (strlen($fval)) {
							$room_first_feature = '#'.$rind.' - '.JText::translate($fname).': '.$fval;
							break 2;
						}
					}
				}
			}
		}
		//
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
			$custdata = substr($custdata, 0, 45)." ...";
		}
		$q = "SELECT `c`.*,`co`.`idorder` FROM `#__vikbooking_customers` AS `c` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `c`.`id`=`co`.`idcustomer` WHERE `co`.`idorder`=".$row['idorder'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cust_country = $dbo->loadAssocList();
			$cust_country = $cust_country[0];
			if (!empty($cust_country['first_name'])) {
				$custdata = $cust_country['first_name'].' '.$cust_country['last_name'];
				if (!empty($cust_country['country'])) {
					if (file_exists(VBO_ADMIN_PATH.DS.'resources'.DS.'countries'.DS.$cust_country['country'].'.png')) {
						$custdata .= '<img src="'.VBO_ADMIN_URI.'resources/countries/'.$cust_country['country'].'.png'.'" title="'.$cust_country['country'].'" class="vbo-country-flag vbo-country-flag-left"/>';
					}
				}
			}
		}
		$custdata = $row['closure'] > 0 || JText::translate('VBDBTEXTROOMCLOSED') == $row['custdata'] ? '<span class="vbordersroomclosed">'.JText::translate('VBDBTEXTROOMCLOSED').'</span>' : $custdata;
		?>
		<tr class="row<?php echo $k; ?>">
			<td>
				<a class="vbo-bookingid" href="index.php?option=com_vikbooking&amp;task=editbusy<?php echo ($pvcm == 1 ? '&amp;vcm=1' : '').(!empty($pgoto) ? '&amp;goto='.$pgoto : ''); ?>&amp;cid[]=<?php echo $row['idorder']; ?>"><?php echo $row['idorder']; ?></a>
			</td>
			<td>
				<a href="index.php?option=com_vikbooking&amp;task=editbusy<?php echo ($pvcm == 1 ? '&amp;vcm=1' : '').(!empty($pgoto) ? '&amp;goto='.$pgoto : ''); ?>&amp;cid[]=<?php echo $row['idorder']; ?>"><?php echo date(str_replace("/", $datesep, $df).' H:i', $row['checkin']); ?></a>
			</td>
			<td><?php echo date(str_replace("/", $datesep, $df).' H:i', $row['checkout']); ?></td>
			<td><?php echo $custdata; ?></td>
			<td style="text-align: center;"><?php echo $room_first_feature; ?></td>
			<td><?php echo date(str_replace("/", $datesep, $df).' H:i', $row['ts']); ?></td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</table>
</div>
	<input type="hidden" name="idroom" value="<?php echo $reservs[0]['idroom']; ?>" />
	<input type="hidden" name="ts" value="<?php echo $pts; ?>" />
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="choosebusy" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHtml::fetch('form.token'); ?>
	<?php echo $navbut; ?>
</form>
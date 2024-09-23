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

$prices = $this->prices;
$rooms = $this->rooms;
$days = $this->days;
$checkin = $this->checkin;
$checkout = $this->checkout;
$selopt = $this->selopt;
$roomsnum = $this->roomsnum;
$adults = $this->adults;
$children = $this->children;
$arrpeople = $this->arrpeople;
$ppkg_id = VikRequest::getInt('pkg_id', '', 'request');

$strpriceid = "";
foreach ($prices as $num => $pid) {
	$strpriceid .= "&priceid".$num."=".$pid;
}
$stroptid = "";
for ($ir = 1; $ir <= $roomsnum; $ir++) {
	if (isset($selopt[$ir]) && is_array($selopt[$ir])) {
		foreach ($selopt[$ir] as $opt) {
			$stroptid .= "&optid".$ir.$opt['id']."=".$opt['quan'];
		}
	}
}
$strroomid = "";
foreach ($rooms as $num => $r) {
	$strroomid .= "&roomid[]=".$r['id'];
}
$straduchild = "";
foreach ($arrpeople as $indroom => $aduch) {
	$straduchild .= "&adults[]=".$aduch['adults'];
	$straduchild .= "&children[]=".$aduch['children'];
}

$action = 'index.php?option=com_user&amp;task=login';

$pitemid = VikRequest::getString('Itemid', '', 'request');

if (count($rooms) > 0 && !empty($checkin) && !empty($checkout)) {
	$goto = "index.php?option=com_vikbooking&task=oconfirm".$strpriceid.$stroptid.$strroomid.$straduchild."&roomsnum=".$roomsnum."&days=".$days."&checkin=".$checkin."&checkout=".$checkout.($ppkg_id > 0 ? '&pkg_id='.$ppkg_id : '').(!empty($pitemid) ? "&Itemid=".$pitemid : "");
	$goto = JRoute::rewrite($goto, false);
} else {
	// The Joomla! home page
	$menu = JSite::getMenu();
	$default = $menu->getDefault();
	$uri = JFactory::getURI($default->link . '&Itemid=' . $default->id);
	$goto = $uri->toString(array (
		'path',
		'query',
		'fragment'
	));
}
$return_url = base64_encode($goto);

?>

<script language="JavaScript" type="text/javascript">
function checkVrcReg() {
	var vbvar = document.vbreg;
	if (!vbvar.name.value.match(/\S/)) {
		document.getElementById('vbfname').style.color='#ff0000';
		return false;
	} else {
		document.getElementById('vbfname').style.color='';
	}
	if (!vbvar.lname.value.match(/\S/)) {
		document.getElementById('vbflname').style.color='#ff0000';
		return false;
	} else {
		document.getElementById('vbflname').style.color='';
	}
	if (!vbvar.email.value.match(/\S/)) {
		document.getElementById('vbfemail').style.color='#ff0000';
		return false;
	} else {
		document.getElementById('vbfemail').style.color='';
	}
	if (!vbvar.username.value.match(/\S/)) {
		document.getElementById('vbfusername').style.color='#ff0000';
		return false;
	} else {
		document.getElementById('vbfusername').style.color='';
	}
	if (!vbvar.password.value.match(/\S/)) {
		document.getElementById('vbfpassword').style.color='#ff0000';
		return false;
	} else {
		document.getElementById('vbfpassword').style.color='';
	}
	if (!vbvar.confpassword.value.match(/\S/)) {
		document.getElementById('vbfconfpassword').style.color='#ff0000';
		return false;
	} else {
		document.getElementById('vbfconfpassword').style.color='';
	}
	return true;
}
</script>

<div class="loginregistercont">
		
	<div class="loginregister-block registerblock">
		<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post" name="vbreg" onsubmit="return checkVrcReg();">
			<h3><?php echo JText::translate('VBREGSIGNUP'); ?></h3>
			<div class="loginregister-inner-block">
				<div class="loginregister-row">
					<div class="loginregister-lbl"><span id="vbfname"><?php echo JText::translate('VBREGNAME'); ?></span></div>
					<div class="loginregister-val"><input type="text" name="fname" value="" size="20" class="vbinput"/></div>
				</div>
				<div class="loginregister-row">
					<div class="loginregister-lbl"><span id="vbflname"><?php echo JText::translate('VBREGLNAME'); ?></span></div>
					<div class="loginregister-val"><input type="text" name="lname" value="" size="20" class="vbinput"/></div>
				</div>
				<div class="loginregister-row">
					<div class="loginregister-lbl"><span id="vbfemail"><?php echo JText::translate('VBREGEMAIL'); ?></span></div>
					<div class="loginregister-val"><input type="text" name="email" value="" size="20" class="vbinput"/></div>
				</div>
				<div class="loginregister-row">
					<div class="loginregister-lbl"><span id="vbfusername"><?php echo JText::translate('VBREGUNAME'); ?></span></div>
					<div class="loginregister-val"><input type="text" name="username" value="" size="20" class="vbinput"/></div>
				</div>
				<div class="loginregister-row">
					<div class="loginregister-lbl"><span id="vbfpassword"><?php echo JText::translate('VBREGPWD'); ?></span></div>
					<div class="loginregister-val"><input type="password" name="password" value="" size="20" class="vbinput"/></div>
				</div>
				<div class="loginregister-row">
					<div class="loginregister-lbl"><span id="vbfconfpassword"><?php echo JText::translate('VBREGCONFIRMPWD'); ?></span></div>
					<div class="loginregister-val"><input type="password" name="confpassword" value="" size="20" class="vbinput"/></div>
				</div>
				<div class="loginregister-row loginregister-submit">
					<input type="submit" value="<?php echo JText::translate('VBREGSIGNUPBTN'); ?>" class="btn booknow vbo-pref-color-btn" name="submit" />
				</div>
			</div>
		<?php
		foreach ($prices as $num => $pid) {
			?>
			<input type="hidden" name="priceid<?php echo $num; ?>" value="<?php echo $pid; ?>" />
			<?php
		}
		for ($ir = 1; $ir <= $roomsnum; $ir++) {
			if (isset($selopt[$ir]) && is_array($selopt[$ir])) {
				foreach ($selopt[$ir] as $opt) {
					?>
					<input type="hidden" name="optid<?php echo $ir.$opt['id']; ?>" value="<?php echo $opt['quan']; ?>" />
					<?php
				}
			}
		}
		foreach ($rooms as $num => $r) {
			?>
			<input type="hidden" name="roomid[]" value="<?php echo $r['id']; ?>" />
			<?php
		}
		foreach ($arrpeople as $indroom => $aduch) {
			?>
			<input type="hidden" name="adults[]" value="<?php echo $aduch['adults']; ?>" />
			<input type="hidden" name="children[]" value="<?php echo $aduch['children']; ?>" />
			<?php
		}
		for ($ir = 1; $ir <= $roomsnum; $ir++) {
			if (isset($selopt[$ir]) && is_array($selopt[$ir])) {
				foreach ($selopt[$ir] as $opt) {
					?>
					<input type="hidden" name="optid<?php echo $ir.$opt['id']; ?>" value="<?php echo $opt['quan']; ?>" />
					<?php
				}
			}
		}
		?>
		<input type="hidden" name="roomsnum" value="<?php echo $roomsnum; ?>" />
		<input type="hidden" name="days" value="<?php echo $days; ?>" />
		<input type="hidden" name="checkin" value="<?php echo $checkin; ?>" />
		<input type="hidden" name="checkout" value="<?php echo $checkout; ?>" />
		<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />
		<input type="hidden" name="option" value="com_vikbooking" />
		<input type="hidden" name="task" value="register" />
		</form>
	</div>
<?php

/**
 * @wponly 	use WP login form (change login and password names: "log" and "pwd")
 */
$url = wp_login_url(base64_decode($return_url));
$url .= (strpos($url, '?') !== false ? '&' : '?') . 'action=login';

?>

	<div class="loginregister-block loginblock">
		<form action="<?php echo $url; ?>" method="post">
			<h3><?php echo JText::translate('VBREGSIGNIN'); ?></h3>
			<div class="loginregister-inner-block">
				<div class="loginregister-row">
					<div class="loginregister-lbl"><?php echo JText::translate('VBREGUNAME'); ?></div>
					<div class="loginregister-val"><input type="text" name="log" value="" size="20" class="vbinput"/></div>
				</div>
				<div class="loginregister-row">
					<div class="loginregister-lbl"><?php echo JText::translate('VBREGPWD'); ?></div>
					<div class="loginregister-val"><input type="password" name="pwd" value="" size="20" class="vbinput"/></div>
				</div>
				<div class="loginregister-row loginregister-submit">
					<input type="submit" value="<?php echo JText::translate('VBREGSIGNINBTN'); ?>" class="btn booknow vbo-pref-color-btn" name="Login" />
				</div>
			</div>
			<input type="hidden" name="remember" id="remember" value="yes" />
			<?php echo JHtml::fetch('form.token'); ?>
		</form>
	</div>
	
</div>

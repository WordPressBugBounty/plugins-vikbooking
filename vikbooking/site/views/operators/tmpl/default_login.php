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

$pitemid = VikRequest::getInt('Itemid', '', 'request');

$goto = "index.php?option=com_vikbooking&view=operators".(!empty($pitemid) ? "&Itemid=".$pitemid : "");
$goto = JRoute::rewrite($goto, false);
/**
 * @wponly 	JRoute is already adding the scheme, the host and the port, so we cannot concatenate it again to the variable $goto
 */
// $goto = JURI::getInstance()->toString(array("scheme","host","port")) . $goto;

$return_url = base64_encode($goto);

?>

<div class="vbo-operators-login-wrap">

	<div class="vbo-operators-authcode">
		<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=operatorlogin&Itemid=' . $pitemid); ?>" method="post">
			<label for="authcode"><?php echo JText::translate('VBOOPERAUTHCODE'); ?></label>
			<input type="text" name="authcode" id="authcode" value="" />
			<input type="submit" value="<?php echo JText::translate('VBREGSIGNINBTN'); ?>" class="btn booknow vbo-pref-color-btn" name="Login" />
			<input type="hidden" name="option" value="com_vikbooking" />
			<input type="hidden" name="task" value="operatorlogin" />
			<?php
			if (!empty($pitemid)) {
				?>
			<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />
				<?php
			}
			echo JHtml::fetch('form.token');
			?>
		</form>
	</div>
<?php

/**
 * @wponly 	use WP login form (change login and password names: "log" and "pwd")
 */
$url = wp_login_url(base64_decode($return_url));
$url .= (strpos($url, '?') !== false ? '&' : '?') . 'action=login';

?>
	<div class="loginblock">
		<form action="<?php echo $url; ?>" method="post">
			<h3><?php echo JText::translate('VBREGSIGNIN'); ?></h3>
			<div class="loginblock-cnt" valign="top">
				<div>
					<div class="loginblock-lbl"><label for="vbo-username"><?php echo JText::translate('VBREGUNAME'); ?></label></div>
					<div class="loginblock-value"><input type="text" name="log" id="vbo-username" value="" size="20" class="vbinput"/></div>
				</div>
				<div>
					<div class="loginblock-lbl"><label for="vbo-password"><?php echo JText::translate('VBREGPWD'); ?></label></div>
					<div class="loginblock-value"><input type="password" name="pwd" id="vbo-password" value="" size="20" class="vbinput"/></div>
				</div>
				<div>
					<div class="loginblock-subbtn"><input type="submit" value="<?php echo JText::translate('VBREGSIGNINBTN'); ?>" class="btn booknow vbo-pref-color-btn" name="Login" /></div>
				</div>
			</div>
			<input type="hidden" name="remember" id="remember" value="yes" />
			<?php echo JHtml::fetch('form.token'); ?>
		</form>
	</div>

</div>

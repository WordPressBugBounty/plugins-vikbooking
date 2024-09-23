<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

class VikBookingHelper
{
	public static function printHeader($highlight = "")
	{
		$app = JFactory::getApplication();
		$cookie = $app->input->cookie;
		$tmpl = VikRequest::getVar('tmpl');
		$view = VikRequest::getVar('view');

		if ($tmpl == 'component') {
			return;
		}

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly Hide menu for Pro-update views
			 */
			if (in_array($view, ['getpro'])) {
				return;
			}
		}

		// JS lang def
		JText::script('VBOGUESTREVSVCMREQ');
		JText::script('VBO_QUICK_ACTIONS');

		$session 	= JFactory::getSession();
		$admin_user = JFactory::getUser();

		$has_vcm = is_file(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php');
		$backlogo = VikBooking::getBackendLogo();
		$vbo_auth_global = $admin_user->authorise('core.vbo.global', 'com_vikbooking');
		$vbo_auth_rateplans = $admin_user->authorise('core.vbo.rateplans', 'com_vikbooking');
		$vbo_auth_rooms = $admin_user->authorise('core.vbo.rooms', 'com_vikbooking');
		$vbo_auth_pricing = $admin_user->authorise('core.vbo.pricing', 'com_vikbooking');
		$vbo_auth_bookings = $admin_user->authorise('core.vbo.bookings', 'com_vikbooking');
		$vbo_auth_availability = $admin_user->authorise('core.vbo.availability', 'com_vikbooking');
		$vbo_auth_management = $admin_user->authorise('core.vbo.management', 'com_vikbooking');
		$vbo_auth_pms = $admin_user->authorise('core.vbo.pms', 'com_vikbooking');
		$reviews_dld = 0;

		// check for stored quick actions only once
		$admin_menu_actions_checked = $session->get('admin_menu.actions.check', null, 'vikbooking');
		if (!$admin_menu_actions_checked) {
			$session->set('admin_menu.actions.check', 1, 'vikbooking');
		}

		/**
		 * Check VCM subscription status.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$vcm_expiration_reminder = VikBooking::getVCMSubscriptionStatus();

		/**
		 * New back-end menu structure would support sub-titles for menu entries.
		 * 
		 * <span class="vbo-submenu-item">
		 * 	<span class="vbo-submenu-item-txt vbo-submenu-item-title">Entry Title</span>
		 * 	<span class="vbo-submenu-item-help">I am the entry sub-text.</span>
		 * </span>
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		?>
		<div class="vbo-menu-container<?php echo $view == 'dashboard' ? ' vbo-menu-container-closer' : ''; ?>">
			<div class="vbo-menu-left">
				<a href="index.php?option=com_vikbooking"><img src="<?php echo VBO_ADMIN_URI.(!empty($backlogo) ? 'resources/'.$backlogo : 'vikbooking.png'); ?>" alt="VikBooking Logo" /></a>
			</div>
			<div class="vbo-menu-right">
				<ul class="vbo-menu-ul">
					<?php
					if ($vbo_auth_global || $vbo_auth_management) {
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('cogs'); ?><a><?php echo JText::translate('VBMENUFOUR'); ?> <?php VikBookingIcons::e('chevron-down', 'vbo-submenu-chevron'); ?></a></span>
						<div class="vbo-submenu-wrap">
							<ul class="vbo-submenu-ul" data-menu-scope="global">
							<?php if ($vbo_auth_global) : ?>
								<li>
									<div class="<?php echo ($highlight == "14" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=payments">
											<?php VikBookingIcons::e('credit-card'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTENEIGHT'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							<?php if ($vbo_auth_global) : ?>
								<li>
									<div class="<?php echo ($highlight == "16" || $highlight == 'states' || $highlight == 'managestate' ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=customf">
											<?php VikBookingIcons::e('address-card'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTENTEN'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							<?php if ($vbo_auth_management) : ?>
								<li>
									<div class="<?php echo ($highlight == "21" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=translations">
											<?php VikBookingIcons::e('language'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTRANSLATIONS'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							<?php if ($vbo_auth_global) : ?>
								<li>
									<div class="<?php echo ($highlight == "11" || $highlight == 'backups' || $highlight == 'overrides' ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=config">
											<?php VikBookingIcons::e('cogs'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTWELVE'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							</ul>
						</div>
					</li><?php
					}
					if ($vbo_auth_rateplans) {
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('briefcase'); ?><a><?php echo JText::translate('VBMENURATEPLANS'); ?> <?php VikBookingIcons::e('chevron-down', 'vbo-submenu-chevron'); ?></a></span>
						<div class="vbo-submenu-wrap">
							<ul class="vbo-submenu-ul" data-menu-scope="rateplans">
								<li>
									<div class="<?php echo ($highlight == "2" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=iva">
											<?php VikBookingIcons::e('percent'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUNINE'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "1" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=prices">
											<?php VikBookingIcons::e('tags'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUFIVE'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "17" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=coupons">
											<?php VikBookingIcons::e('user-tag'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUCOUPONS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "packages" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=packages">
											<?php VikBookingIcons::e('box'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUPACKAGES'); ?></span>
											</span>
										</a>
									</div>
								</li>
							</ul>
						</div>
					</li><?php
					}
					if ($vbo_auth_rooms || $vbo_auth_pricing) {
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('bed'); ?><a><?php echo JText::translate('VBMENUTWO'); ?> <?php VikBookingIcons::e('chevron-down', 'vbo-submenu-chevron'); ?></a></span>
						<div class="vbo-submenu-wrap">
							<ul class="vbo-submenu-ul" data-menu-scope="rooms">
							<?php if ($vbo_auth_rooms) : ?>
								<li>
									<div class="<?php echo ($highlight == "4" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=categories">
											<?php VikBookingIcons::e('filter'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUSIX'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							<?php if ($vbo_auth_rooms) : ?>
								<li>
									<div class="<?php echo ($highlight == "5" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=carat">
											<?php VikBookingIcons::e('icons'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTENFOUR'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							<?php if ($vbo_auth_pricing) : ?>
								<li>
									<div class="<?php echo ($highlight == "6" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=optionals">
											<?php VikBookingIcons::e('couch'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTENFIVE'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							<?php if ($vbo_auth_rooms) : ?>
								<li>
									<div class="<?php echo ($highlight == "7" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=rooms">
											<?php VikBookingIcons::e('bed'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTEN'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							</ul>
						</div>
					</li><?php
					}
					if ($vbo_auth_pricing) {
					?><li class="vbo-menu-parent-li">
						<span><i class="vboicn-calculator"></i><a><?php echo JText::translate('VBMENUFARES'); ?> <?php VikBookingIcons::e('chevron-down', 'vbo-submenu-chevron'); ?></a></span>
						<div class="vbo-submenu-wrap">
							<ul class="vbo-submenu-ul" data-menu-scope="pricing">
								<li>
									<div class="<?php echo ($highlight == "fares" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=tariffs">
											<?php VikBookingIcons::e('toolbox'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUPRICESTABLE'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "13" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=seasons">
											<?php VikBookingIcons::e('seedling'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTENSEVEN'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "restrictions" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=restrictions">
											<?php VikBookingIcons::e('hand-paper'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENURESTRICTIONS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "20" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=ratesoverv">
											<?php VikBookingIcons::e('calculator'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENURATESOVERVIEW'); ?></span>
											</span>
										</a>
									</div>
								</li>
							</ul>
						</div>
					</li><?php
					}
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('calendar-check'); ?><a><?php echo JText::translate('VBMENUTHREE'); ?> <?php VikBookingIcons::e('chevron-down', 'vbo-submenu-chevron'); ?></a></span>
						<div class="vbo-submenu-wrap">
							<ul class="vbo-submenu-ul" data-menu-scope="bookings">
								<li>
									<div class="<?php echo (in_array($highlight, ['18', 'shortcodes', 'acl', 'gotopro']) ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking">
											<?php VikBookingIcons::e('concierge-bell'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUDASHBOARD'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php if ($vbo_auth_availability || $vbo_auth_bookings) : ?>
								<li>
									<div class="<?php echo ($highlight == "19" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=calendar">
											<?php VikBookingIcons::e('calendar'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUQUICKRES'); ?></span>
											</span>
										</a>
										</div>
									</li>
							<?php endif; ?>
							<?php if ($vbo_auth_availability) : ?>
								<li>
									<div class="<?php echo ($highlight == "15" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=overv">
											<?php VikBookingIcons::e('calendar-check'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTENNINE'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							<?php if ($vbo_auth_bookings) : ?>
								<li>
									<div class="<?php echo ($highlight == "8" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=orders">
											<?php VikBookingIcons::e('clipboard-list'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUSEVEN'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							<?php if ($has_vcm && ($vbo_auth_availability || $vbo_auth_bookings)) : ?>
								<li>
									<div class="vmenulink">
										<a href="index.php?option=com_vikchannelmanager" class="vbo-menu-vcmlink">
											<?php VikBookingIcons::e('cloud'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUCHANNELMANAGER'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							</ul>
						</div>
					</li><?php
					if ($vbo_auth_management) {
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('chart-pie'); ?><a><?php echo JText::translate('VBMENUMANAGEMENT'); ?> <?php VikBookingIcons::e('chevron-down', 'vbo-submenu-chevron'); ?></a></span>
						<div class="vbo-submenu-wrap">
							<ul class="vbo-submenu-ul" data-menu-scope="management">
								<li>
									<div class="<?php echo ($highlight == "22" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=customers">
											<?php VikBookingIcons::e('users'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUCUSTOMERS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "invoices" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=invoices">
											<?php VikBookingIcons::e('file-invoice'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUINVOICES'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "stats" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=stats">
											<?php VikBookingIcons::e('chart-line'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUSTATS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "trackings" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=trackings">
											<?php VikBookingIcons::e('compass'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTRACKINGS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo (in_array($highlight, ["crons", "managecron"]) ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;view=crons">
											<?php VikBookingIcons::e('stopwatch'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUCRONS'); ?></span>
											</span>
										</a>
									</div>
								</li>
							</ul>
						</div>
					</li><?php
					}
					if ($vbo_auth_pms) {
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('tasks'); ?><a><?php echo JText::translate('VBMENUPMS'); ?> <?php VikBookingIcons::e('chevron-down', 'vbo-submenu-chevron'); ?></a></span>
						<div class="vbo-submenu-wrap">
							<ul class="vbo-submenu-ul" data-menu-scope="pms">
								<li>
									<div class="<?php echo ($highlight == "operators" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=operators">
											<?php VikBookingIcons::e('user-tie'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUOPERATORS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "tableaux" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=tableaux">
											<?php VikBookingIcons::e('stream'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUTABLEAUX'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "pmsreports" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=pmsreports">
											<?php VikBookingIcons::e('cash-register'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUPMSREPORTS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "einvoicing" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikbooking&amp;task=einvoicing">
											<?php VikBookingIcons::e('laptop-code'); ?>
											<span class="vbo-submenu-item">
												<span class="vbo-submenu-item-txt"><?php echo JText::translate('VBMENUEINVOICING'); ?></span>
											</span>
										</a>
									</div>
								</li>
							</ul>
						</div>
					</li><?php
					}
					?>
				</ul>
				<div class="vbo-menu-updates">
			<?php
			if (VBOPlatformDetection::isWordPress()) {
				/**
				 * @wponly PRO Version
				 */
				VikBookingLoader::import('update.license');
				if (!VikBookingLicense::isPro()) {
					?>
						<button type="button" class="vbo-gotopro" title="<?php echo addslashes(JText::translate('VBOGOTOPROBTN')); ?>" onclick="document.location.href='admin.php?option=com_vikbooking&view=gotopro';">
							<?php VikBookingIcons::e('rocket'); ?>
							<span><?php echo JText::translate('VBOGOTOPROBTN'); ?></span>
						</button>
					<?php
				} else {
					?>
						<button type="button" class="vbo-alreadypro" title="<?php echo addslashes(JText::translate('VBOISPROBTN')); ?>" onclick="document.location.href='admin.php?option=com_vikbooking&view=gotopro';">
							<?php VikBookingIcons::e('trophy'); ?>
							<span><?php echo JText::translate('VBOISPROBTN'); ?></span>
						</button>
					<?php
				}
			} else {
				/**
				 * @joomlaonly
				 */
				if (($highlight == '18' || $highlight == '11') && method_exists($app, 'triggerEvent')) {
					// VikUpdater
					JPluginHelper::importPlugin('e4j');
					$callable = $app->triggerEvent('onUpdaterSupported');
					if (count($callable) && $callable[0]) {
						//Plugin enabled
						$params = new stdClass;
						$params->version 	= E4J_SOFTWARE_VERSION;
						$params->alias 		= 'com_vikbooking';
						
						$upd_btn_text = strrev('setadpU kcehC');
						$ready_jsfun = '';
						$result = $app->triggerEvent('onGetVersionContents', array(&$params));
						if (count($result) && $result[0]) {
							$upd_btn_text = $result[0]->response->shortTitle;
						} else {
							$ready_jsfun = 'jQuery("#vik-update-btn").trigger("click");';
						}
						?>
						<button type="button" id="vik-update-btn" onclick="<?php echo count($result) && $result[0] && $result[0]->response->compare == 1 ? 'document.location.href=\'index.php?option=com_vikbooking&task=updateprogram\'' : 'checkVersion(this);'; ?>">
							<i class="vboicn-cloud"></i> 
							<span><?php echo $upd_btn_text; ?></span>
						</button>
						<script type="text/javascript">
						function checkVersion(button) {
							jQuery(button).find('span').text('Checking...');
							jQuery.ajax({
								type: 'POST',
								url: 'index.php?option=com_vikbooking&task=checkversion&tmpl=component',
								data: {}
							}).done(function(resp) {
								var obj = typeof resp === 'string' ? JSON.parse(resp) : resp;
								console.log(obj);
								if (obj.status == 1 && obj.response.status == 1) {
									jQuery(button).find('span').text(obj.response.shortTitle);
									if (obj.response.compare == 1) {
										jQuery(button).attr('onclick', 'document.location.href="index.php?option=com_vikbooking&task=updateprogram"');
									}
								}
							}).fail(function(resp) {
								console.log(resp);
							});
						}
						jQuery(function() {
							<?php echo $ready_jsfun; ?>
						});
						</script>
						<?php
					} else {
						/**
						 * When Vik Updater is not available or disabled, we now
						 * render a modal for the automated installation of the plugin.
						 * 
						 * @since 	1.15.1
						 */

						$data = [
							'hn'  => getenv('HTTP_HOST'),
							'sn'  => getenv('SERVER_NAME'),
							'app' => CREATIVIKAPP,
							'ver' => VIKBOOKING_SOFTWARE_VERSION,
						];

						$vikupdater_url = 'https://extensionsforjoomla.com/vikcheck/vikupdater.php?' . http_build_query($data);

						echo JHtml::fetch(
							'bootstrap.renderModal',
							'jmodal-version-check',
							array(
								'title'       => 'Install VikUpdater',
								'closeButton' => true,
								'keyboard'    => true,
								'bodyHeight'  => 80,
								'url'         => $vikupdater_url,
								'footer'      => '<button type="button" class="btn btn-success" id="version-check-install">' . JText::translate('JTOOLBAR_INSTALL') . '</button>',
							)
						);
						?>
						<button type="button" id="vik-update-btn">
							<i class="vboicn-cloud"></i> 
							<span></span>
						</button>

						<?php echo VikBooking::getVboApplication()->getJmodalScript(); ?>

						<script>
							(function($) {
								'use strict';

								$(function() {
									$('#vik-update-btn').on('click', () => {
										vboOpenJModal('version-check');
									});

									$('#version-check-install').on('click', () => {
										const form = $('<form action="index.php?option=com_installer&task=install.install" method="post"></form>');

										form.append('<input type="hidden" name="installtype" value="url" />');
										form.append('<input type="hidden" name="install_url" value="https://extensionsforjoomla.com/vikapi/?task=products.freedownload&sku=vup" />');
										form.append('<input type="hidden" name="return" value="<?php echo base64_encode(JUri::getInstance()); ?>" />');
										form.append('<?php echo JHtml::fetch('form.token'); ?>');

										$('body').append(form);

										form.submit();
									});
								});
							})(jQuery);
						</script>
						<?php
					}
				}
			}

			if ($vbo_auth_management && in_array($highlight, array('18', '11'))) {
				// VCM Opportunities
				$opp = VikBooking::getVcmOpportunityInstance();
				if (!is_null($opp)) {
					// download opportunities if it's time to do it
					if ($opp->shouldRequestOpportunities()) {
						$opp->downloadOpportunities();
					}
					// count opportunities
					$opp_filters = array(
						'status' => 0,
						'action' => 0,
					);
					$new_opp_count = count($opp->loadOpportunities($opp_filters, null, null));
					if ($new_opp_count > 0) {
						?>
					<!-- <button type="button" class="vbo-opportunities-btnbadge" data-opportunity-count="<?php echo $new_opp_count; ?>" title="<?php echo htmlspecialchars(JText::translate('VBOGOTOOPPORTUNITIES')); ?>" onclick="document.location.href='<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikchannelmanager&task=opportunities', false); ?>';">
						<?php VikBookingIcons::e('crown'); ?>
						<span><?php echo JText::translate('VBOGOTOOPPORTUNITIES'); ?></span>
					</button> -->
						<?php
					}
				}

				// Guest Reviews
				$reviews_dld = VikBooking::shouldDownloadReviews();
				$base_lnk = VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikchannelmanager&task=reviews', false);
				if ($highlight == '18') {
					?>
					<button type="button" class="vbo-reviews-btnbadge" data-reviews-count="" title="<?php echo htmlspecialchars(JText::translate('VBOPANELREVIEWS')); ?>" onclick="<?php echo $reviews_dld >= 0 ? "window.open('{$base_lnk}', '_blank'); jQuery(this).removeClass('vbo-reviews-btnbadge-alert').attr('data-reviews-count', '');" : "alert(Joomla.JText._('VBOGUESTREVSVCMREQ'));"; ?>">
						<?php VikBookingIcons::e('star'); ?>
						<span><?php echo JText::translate('VBOPANELREVIEWS'); ?></span>
					</button>
					<?php
				}
			}

			/**
			 * Admin widgets multitasking side panel.
			 * 
			 * @since 	1.15.0 (J) - 1.5.0 (WP)
			 */
			if ($view != 'dashboard') {
				?>
					<button type="button" class="vbo-multitasking-apps" title="<?php echo htmlspecialchars(JText::translate('VBO_MULTITASK_PANEL'), ENT_QUOTES, 'UTF-8'); ?>">
						<?php VikBookingIcons::e('th'); ?>
					</button>

					<?php
					// prepare the layout data array
					$layout_data = [
						'vbo_page' 	  => (empty($view) ? VikRequest::getString('task', '') : $view),
						'btn_trigger' => '.vbo-multitasking-apps',
					];
					// render the multitasking layout
					echo JLayoutHelper::render('sidepanel.multitasking', $layout_data, null, [
						'component' => 'com_vikbooking',
						'client' 	=> 'administrator',
					]);
					?>
				<?php
			}

			/**
			 * Notifications Center.
			 * 
			 * @since 	1.16.8 (J) - 1.6.8 (WP)
			 */
			$ncInstance = new VBONotificationCenter;

			// read "old" notifications
			$ncInstance->readOldNotifications();

			// check if notifications should be downloaded
			$ncInstance->downloadNotifications();

			// count unread notifications
			$unreadNotifs = $ncInstance->countUnread();
			$unreadNotifsRead = $unreadNotifs > 99 ? '99+' : $unreadNotifs;
			$unreadNotifsRead = !$unreadNotifsRead ? '' : $unreadNotifsRead;
			?>
					<div class="vbo-notifications-center-container">
						<button type="button" class="vbo-notifications-center-handler" data-badge-count="<?php echo $unreadNotifs; ?>" data-badge-read="<?php echo $unreadNotifsRead; ?>" title="<?php echo htmlspecialchars(JText::translate('VBO_W_NOTIFSCENTER_TITLE'), ENT_QUOTES, 'UTF-8'); ?>">
							<?php VikBookingIcons::e('bell', 'can-shake'); ?>
						</button>
					<?php
					// prepare the layout data array
					$layout_data = [
						'vbo_page' 	   => (empty($view) ? VikRequest::getString('task', '') : $view),
						'btn_trigger'  => '.vbo-notifications-center-handler',
						'badge_c_attr' => 'data-badge-count',
						'badge_r_attr' => 'data-badge-read',
						'badge_count'  => $unreadNotifs,
					];
					// render the notificationscenter layout
					echo JLayoutHelper::render('sidepanel.notificationscenter', $layout_data, null, [
						'component' => 'com_vikbooking',
						'client' 	=> 'administrator',
					]);
					?>
					</div>

				</div>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(function() {
			jQuery('.vbo-menu-parent-li').hover(
				function() {
					jQuery(this).addClass('vbo-menu-parent-li-opened');
					jQuery(this).find('.vbo-submenu-wrap').addClass('vbo-submenu-wrap-active');
				},function() {
					jQuery(this).removeClass('vbo-menu-parent-li-opened');
					jQuery(this).find('.vbo-submenu-wrap').removeClass('vbo-submenu-wrap-active');
				}
			);

			if (jQuery('.vmenulinkactive').length) {
				// set active class to current menu block
				jQuery('.vmenulinkactive').closest('.vbo-submenu-wrap').parent('li').addClass('vbo-menu-parent-li-active');
			}

			if (<?php echo $reviews_dld; ?> > 0) {
				// download new reviews
				VBOCore.doAjax(
					"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=reviews_download'); ?>",
					{
						return: 1,
						everywhere: 1,
						tmpl: 'component'
					},
					(resp) => {
						try {
							if (resp.indexOf('e4j.ok') >= 0) {
								var tot_reviews = resp.replace('e4j.ok.', '');
								tot_reviews = parseInt(tot_reviews);
								if (!isNaN(tot_reviews) && tot_reviews > 0) {
									// update button badge
									jQuery('.vbo-reviews-btnbadge').attr('data-reviews-count', tot_reviews).addClass('vbo-reviews-btnbadge-alert');
								}
							}
						} catch(err) {
							console.error('Error in decoding response', err, resp);
						}
					},
					(err) => {
						console.error('Could not download new reviews from Channel Manager', err);
					}
				);
			}

			// handle quick actions storage
			jQuery('.vbo-menu-right').find('.vbo-submenu-ul').find('a').on('click', function(e) {
				if (!jQuery(this).find('.vbo-submenu-item-txt').length) {
					// nothing to do
					return true;
				}

				// handle the clicked menu entry
				e.preventDefault();

				try {
					// register clicked page
					VBOCore.registerAdminMenuAction({
						name: jQuery(this).find('.vbo-submenu-item-txt').text(),
						href: jQuery(this).attr('href'),
					});
				} catch(e) {
					console.error(e);
				}

				if (e.metaKey) {
					// open link on new window
					window.open(jQuery(this).attr('href'), '_blank').focus();
				} else {
					// proceed with the navigation
					window.location.href = jQuery(this).attr('href');
				}

				return true;
			});

			// register event to populate quick actions sub-menu helpers
			document.addEventListener('vbo-adminmenu-quickactions-create', () => {
				// load the action from the "widgets" scope to be used as fallback
				let menu_widgets_actions = VBOCore.getAdminMenuActions('widgets');

				// scan all menu scopes
				jQuery('.vbo-submenu-ul[data-menu-scope]').each(function() {
					let menu_ul = jQuery(this);
					let scope = menu_ul.attr('data-menu-scope');
					if (!scope) {
						return;
					}

					let orig_scope = scope;
					let wrapper = menu_ul.closest('.vbo-submenu-wrap');
					if (!wrapper || !wrapper.length || wrapper.hasClass('vbo-submenu-wrap-multi') || wrapper.find('.vbo-submenu-helper-ul').length) {
						return;
					}

					let menu_scope_actions = VBOCore.getAdminMenuActions(scope);
					if (!Array.isArray(menu_scope_actions) || !menu_scope_actions.length) {
						// check if we can fallback on the "widgets" scope
						if (Array.isArray(menu_widgets_actions) && menu_widgets_actions.length) {
							// overwrite scope values
							scope 			   = 'widgets';
							menu_scope_actions = menu_widgets_actions;
						} else {
							// do not proceed with this scope
							return;
						}
					}

					// check if menu actions should be merged with the fallback scope
					if (scope != 'widgets' && menu_scope_actions[0].hasOwnProperty('fallback')) {
						menu_scope_actions = VBOCore.fillAdminMenuActions(menu_scope_actions, menu_widgets_actions, scope);
					}

					wrapper.addClass('vbo-submenu-wrap-multi');
					let quick_actions = jQuery('<ul></ul>').addClass('vbo-submenu-helper-ul');
					quick_actions.append('<li class="vbo-submenu-helper-lbl-li"><span class="vbo-submenu-helper-lbl-txt">' + Joomla.JText._('VBO_QUICK_ACTIONS') + '</span></li>');

					// scan all menu actions
					menu_scope_actions.forEach((action, index) => {
						let is_pinned = action.hasOwnProperty('pinned') && action['pinned'];
						let quick_actions_entry = jQuery('<li></li>').addClass((is_pinned ? 'vbo-submenu-item-helper-pinned' : 'vbo-submenu-item-helper-unpinned'));
						let quick_actions_div = jQuery('<div></div>').addClass('vmenulink');
						let quick_action_link = jQuery('<a></a>').attr('href', action['href']).addClass('vbo-submenu-item-helper-link');

						if (action.hasOwnProperty('target') && action['target']) {
							quick_action_link.attr('target', action['target']);
						}

						if (action.hasOwnProperty('widget') && action['widget']) {
							quick_action_link.on('click', () => {
								VBOCore.handleDisplayWidgetNotification({widget_id: action['widget']});
							});
						}

						if (action.hasOwnProperty('img') && action['img']) {
							let quick_action_img = jQuery('<span></span>').addClass('vbo-submenu-item-helper-avatar');
							quick_action_img.append('<img src="' + action['img'] + '" />');
							quick_action_link.append(quick_action_img);
						} else if (action.hasOwnProperty('icon') && action['icon']) {
							let quick_action_icon = jQuery('<span></span>').addClass('vbo-submenu-item-helper-avatar');
							quick_action_icon.append(action['icon']);
							quick_action_link.append(quick_action_icon);
						}

						let quick_action_name = jQuery('<span></span>').addClass('vbo-submenu-item-helper-txt').text(action['name']);
						quick_action_link.append(quick_action_name);
						quick_actions_div.append(quick_action_link);

						let quick_action_pin = jQuery('<span></span>').addClass('vbo-submenu-item-helper-setpin').on('click', function() {
							// toggle pinned status and update admin menu action
							if (!action.hasOwnProperty('pinned')) {
								action['pinned'] = !is_pinned;
							} else {
								action['pinned'] = !action['pinned'];
							}
							try {
								// register/unregister pin
								if (orig_scope != scope && !VBOCore.getAdminMenuActions(orig_scope).length) {
									// set fallbacked scope
									action['fallback'] = scope;

									// register scope on local storage with the fallbacked scope
									VBOCore.registerAdminMenuAction(action, orig_scope);
								} else {
									// update local storage regularly on this scope
									VBOCore.updateAdminMenuAction(action, scope);
								}
								
								// trigger event
								VBOCore.emitEvent('vbo-adminmenu-quickactions-update');
							} catch(e) {
								console.error(e);
							}
							// update action status
							if (action['pinned']) {
								jQuery(this).closest('li').removeClass('vbo-submenu-item-helper-unpinned').addClass('vbo-submenu-item-helper-pinned');
							} else {
								jQuery(this).closest('li').removeClass('vbo-submenu-item-helper-pinned').addClass('vbo-submenu-item-helper-unpinned');
							}
						});

						quick_action_pin.html('<?php VikBookingIcons::e('thumbtack'); ?>');
						quick_actions_div.append(quick_action_pin);
						quick_actions_entry.append(quick_actions_div);
						quick_actions.append(quick_actions_entry);
					});

					// append to DOM element
					wrapper.append(quick_actions);
				});
			});

			// register event to update the pinned quick actions
			document.addEventListener('vbo-adminmenu-quickactions-update', VBOCore.debounceEvent(() => {
				let menu_scopes = [];
				jQuery('.vbo-submenu-ul[data-menu-scope]').each(function() {
					let menu_ul = jQuery(this);
					let scope = menu_ul.attr('data-menu-scope');
					if (!scope) {
						scope = '';
					}
					if (menu_scopes.indexOf(scope) < 0) {
						menu_scopes.push(scope);
					}
				});
				let admin_menu_actions = [];
				menu_scopes.forEach((scope) => {
					let menu_actions = VBOCore.getAdminMenuActions(scope);
					admin_menu_actions.push({
						scope: scope,
						actions: menu_actions,
					});
				});
				VBOCore.doAjax(
					"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=menuactions.update'); ?>",
					{
						actions: admin_menu_actions,
					},
					(resp) => {
						// do nothing
					},
					(err) => {
						// log the error
						console.error(err.responseText);
					}
				);
			}, 300));

			// register shortcuts
			var vbo_menu_last_keydown = null;
			window.addEventListener('keydown', (e) => {
				e = e || window.event;
				if (!e.key) {
					return;
				}
				if (e.key === 'Enter' && e.metaKey) {
					// toggle multitask panel
					VBOCore.emitEvent(VBOCore.multitask_shortcut_event);
					return;
				}
				if (VBOCore.side_panel_on && e.key === 'f' && e.metaKey) {
					// focus search admin widget in multitask panel
					VBOCore.emitEvent(VBOCore.multitask_searchfs_event);
					e.preventDefault();
					return;
				}
				if (e.key === 'k' && e.metaKey) {
					// set special key listener for sequences of combos
					e.preventDefault();
					vbo_menu_last_keydown = 'k';
					return;
				}
				if (vbo_menu_last_keydown === 'k' && e.metaKey && ['a', 'd', 'l'].includes(e.key)) {
					// trigger event to change color scheme preferences
					e.preventDefault();
					// unset special key
					vbo_menu_last_keydown = '';
					// activate requested color scheme preference
					let color_schemes = {
						a: 'auto',
						d: 'dark',
						l: 'light',
					};
					jQuery('.vbo-sidepanel-colorscheme-option[data-scheme="' + color_schemes[e.key] + '"]').trigger('click');
					return;
				}
				// always unset last key if this point gets reached
				vbo_menu_last_keydown = '';
			}, true);

			// populate quick actions sub-menu helpers on page load
			VBOCore.emitEvent('vbo-adminmenu-quickactions-create');

			// check (only once) if the quick actions should be imported from the db
			if (<?php echo !$admin_menu_actions_checked ? 'true' : 'false'; ?>) {
				setTimeout(() => {
					// count admin menu actions populated from local storage
					let tot_admin_menu_actions = 0;
					jQuery('.vbo-submenu-ul[data-menu-scope]').each(function() {
						let menu_ul = jQuery(this);
						let scope = menu_ul.attr('data-menu-scope');
						if (!scope) {
							return;
						}
						let menu_scope_actions = VBOCore.getAdminMenuActions(scope);
						if (!Array.isArray(menu_scope_actions) || !menu_scope_actions.length) {
							return;
						}
						tot_admin_menu_actions++;
						return false;
					});

					if (tot_admin_menu_actions) {
						return;
					}

					// request for any previously stored quick actions for this admin
					VBOCore.doAjax(
						"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=menuactions.retrieve'); ?>",
						{},
						(resp) => {
							// store to local storage the quick actions just retrieved
							let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;
							if (Array.isArray(obj_res) && obj_res.length) {
								obj_res.forEach((menu_actions) => {
									let storage_scope_name = VBOCore.options.admin_menu_actions_nm;
									if (menu_actions['scope']) {
										storage_scope_name += '.' + menu_actions['scope'];
									}
									VBOCore.storageSetItem(storage_scope_name, menu_actions['actions']);
								});
							}
							// trigger the event to populate the quick actions
							VBOCore.emitEvent('vbo-adminmenu-quickactions-create');
						},
						(err) => {
							// log the error
							console.error(err.responseText);
						}
					);
				}, 300);
			}
		});
		</script>
		<?php

		// handle subscription expiration reminder modal
		if (is_array($vcm_expiration_reminder) && $vcm_expiration_reminder['days_to_exp'] >= 0) {
			// subscription is expiring, but it's not expired yet, display a modal-reminder
			?>
		<div class="vbo-info-overlay-block vbo-info-overlay-expiration-reminder">
			<div class="vbo-info-overlay-content">
				<h3 style="color: var(--vbo-red-color);"><i class="vboicn-warning"></i> <?php echo JText::translate('VCM_EXPIRATION_REMINDERS'); ?></h3>
				<div>
					<h4><?php echo JText::sprintf('VCM_EXPIRATION_REMINDER_DAYS', $vcm_expiration_reminder['days_to_exp'], $vcm_expiration_reminder['expiration_ymd']); ?></h4>
				</div>
				<div class="vbo-info-overlay-footer">
					<div class="vbo-info-overlay-footer-right">
						<button type="button" class="btn btn-danger" onclick="jQuery('.vbo-info-overlay-expiration-reminder').fadeOut();"><?php echo JText::translate('VBOBTNKEEPREMIND'); ?></button>
					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(function() {
				jQuery('.vbo-info-overlay-expiration-reminder').fadeIn();
			});
		</script>
			<?php
		}
	}

	public static function printFooter()
	{
		$tmpl = VikRequest::getVar('tmpl');
		if ($tmpl == 'component') return;
		/**
		 * @wponly "Powered by" is VikWP.com
		 */
		echo '<br clear="all" />' . '<div id="hmfooter">' . JText::sprintf('VBFOOTER', VIKBOOKING_SOFTWARE_VERSION) . ' <a href="https://vikwp.com/" target="_blank">VikWP - vikwp.com</a></div>';
	}

	public static function pUpdateProgram($version)
	{
		/**
		 * @wponly 	do nothing
		 */
	}

	/**
	 * Method to add parameters to the update extra query.
	 * 
	 * @joomlaonly 	this class is automatically loaded by Joomla
	 * 				to invoke this method when updating the component.
	 *
	 * @param   Update  &$update  An update definition
	 * @param   JTable  &$table   The update instance from the database
	 *
	 * @return  void
	 *
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public static function prepareUpdate(&$update, &$table)
	{
		// get current domain
		$server = JFactory::getApplication()->input->server;

		// build query array
		$query = [
			'domain' => base64_encode($server->getString('HTTP_HOST')),
			'ip' 	 => $server->getString('REMOTE_ADDR'),
		];

		// get license key
		$license_key = VBOFactory::getConfig()->get('licensekey');
		if ($license_key)
		{
			$query['key'] = $license_key;
		}

		// always refresh the extra query before an update
		$update->set('extra_query', http_build_query($query, '', '&amp;'));
	}

	/**
	 * Returns the information about the first setup metrics and minimum requirements.
	 * 
	 * @return 	array 	associative list of metrics.
	 * 
	 * @since 	1.16.4 (J) - 1.6.4 (WP)
	 */
	public static function getFirstSetupMetrics()
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT COUNT(*) FROM `#__vikbooking_prices`;";
		$dbo->setQuery($q);
		$totprices = $dbo->loadResult();

		$q = "SELECT COUNT(*) FROM `#__vikbooking_rooms`;";
		$dbo->setQuery($q);
		$totrooms = $dbo->loadResult();

		$q = "SELECT COUNT(*) FROM `#__vikbooking_dispcost`;";
		$dbo->setQuery($q);
		$totdailyfares = $dbo->loadResult();

		$metrics = [
			'totprices' 	=> $totprices,
			'totrooms' 		=> $totrooms,
			'totdailyfares' => $totdailyfares,
			'completed' 	=> ($totprices && $totrooms && $totdailyfares),
		];

		// shortcodes (if platform-supported)
		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly - check if some shortcodes have been defined before showing the Dashboard
			 */
			$model = JModel::getInstance('vikbooking', 'shortcodes');
			$metrics['shortcodes'] = $model->all('post_id');

			// calculate again the completed status
			$metrics['completed'] = ($metrics['completed'] && $metrics['shortcodes']);
		}

		return $metrics;
	}
}

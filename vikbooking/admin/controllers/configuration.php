<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking configuration controller.
 *
 * @since 	1.16.5 (J) - 1.6.5 (WP)
 */
class VikBookingControllerConfiguration extends JControllerAdmin
{
	/**
	 * AJAX endpoint to update configuration settings.
	 * 
	 * @return 	void
	 */
	public function update()
	{
		if (!JSession::checkToken()) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JINVALID_TOKEN'));
		}

		$settings = JFactory::getApplication()->input->get('settings', [], 'array');
		if (!$settings) {
			VBOHttpDocument::getInstance()->close(500, 'Missing data to update');
		}

		// VikBooking configuration object
		$config = VBOFactory::getConfig();

		// prepare settings to be mirrored on VikChannelManager, if available
		$vcm_settings = [
			'appearance_pref' => null,
		];

		foreach ($settings as $param => $value) {
			// update setting on VBO
			$config->set($param, $value);

			// check if setting exists in VCM
			if (array_key_exists($param, $vcm_settings)) {
				// set value
				$vcm_settings[$param] = $value;
			}
		}

		// check if any settings should be updated on VCM
		if (class_exists('VCMFactory')) {
			$vcm_config = VCMFactory::getConfig();
			foreach ($vcm_settings as $param => $value) {
				if ($value !== null) {
					// update mirrored setting on VCM
					$vcm_config->set($param, $value);
				}
			}
		}

		VBOHttpDocument::getInstance()->json([]);
	}
}

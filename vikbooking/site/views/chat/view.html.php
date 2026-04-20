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

jimport('joomla.application.component.view');

/**
 * VikBooking chat view.
 * 
 * @since 1.18.8 (J) - 1.8.8 (WP)
 */
class VikbookingViewChat extends JViewVikBooking
{
	/**
	 * @inheritDoc
	 */
	public function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$sessionModel = new VBOChatSessionModel;

		// get token from request to resume a session
		$token = $app->input->getAlnum('token');

		// in case the user never started a chat from this browser and the URL includes a token, resume the session
		if (!$sessionModel->getCookieToken() && $token) {
			$sessionModel->setCookieToken($token);
		}

		$this->session = $sessionModel->getFromCookie();

		if (!$this->session) {
			$this->setLayout('error');
		}

		parent::display($tpl);
	}
}

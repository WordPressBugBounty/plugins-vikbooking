<?php
/** 
 * @package   	VikBooking
 * @subpackage 	core
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2019 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.mvc.controllers.admin');

/**
 * VikBooking plugin ACL controller.
 *
 * @since 	1.0
 * @see 	JControllerAdmin
 */
class VikBookingControllerAcl extends JControllerAdmin
{
	public function saveclose()
	{
		$this->save(1);
	}

	public function save($close = 0)
	{
		$app 	= JFactory::getApplication();
		$input 	= $app->input;
		$dbo 	= JFactory::getDbo();

		// get return URL
		$encoded = $input->getBase64('return', '');
		$active  = $input->get('activerole', '');

		if ($encoded)
		{
			$return = base64_decode($encoded);
		}
		else
		{
			$return = '';
		}

		/**
		 * Added token validation.
		 *
		 * @since 1.7.3
		 */
		if (!JSession::checkToken())
		{
			// back to main list, missing CSRF-proof token
			$app->enqueueMessage(JText::translate('JINVALID_TOKEN'), 'error');
			$this->cancel();

			return false;
		}

		// make sure the user is authorised to change ACL
		if (!JFactory::getUser()->authorise('core.admin', 'com_vikbooking'))
		{
			$app->enqueueMessage(JText::translate('JERROR_ALERTNOAUTHOR'), 'error');
			$this->cancel();

			return false;
		}

		$data = $input->get('acl', array(), 'array');

		if ($this->model->save($data))
		{
			$app->enqueueMessage(JText::translate('ACL_SAVE_SUCCESS'));
		}
		else
		{
			$app->enqueueMessage(JText::translate('ACL_SAVE_ERROR'), 'error');
		}

		if (!$close)
		{
			$return = 'admin.php?option=com_vikbooking&view=acl&activerole=' . $active . '&return=' . $encoded;
		}

		$this->setRedirect($return);
	}

	public function cancel()
	{
		$app = JFactory::getApplication();

		$return = $app->input->getBase64('return', '');

		if ($return)
		{
			$return = base64_decode($return);
		}

		$this->setRedirect($return);
	}
}

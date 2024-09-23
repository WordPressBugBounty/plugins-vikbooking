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
 * VikBooking plugin License controller.
 *
 * @since 	1.3.12
 * @see 	JControllerAdmin
 */
class VikBookingControllerLicense extends JControllerAdmin
{
	/**
	 * License Key validation through ajax request.
	 * This task takes also the change-log for the current version.
	 *
	 * @return 	void
	 */
	public function validate()
	{
		if (!JFactory::getUser()->authorise('core.admin', 'com_vikbooking'))
		{
			// not authorised to view this resource
			VBOHttpDocument::getInstance()->close(403, JText::translate('RESOURCE_AUTH_ERROR'));
		}

		$input = JFactory::getApplication()->input;

		// get input key
		$key = $input->getString('key');

		// get license model
		$model = $this->getModel();

		// dispatch license key validation
		$response = $model->validate($key);

		// make sure the validation went fine
		if ($response === false)
		{
			// nope, retrieve the error
			$error = $model->getError(null, $toString = false);

			// an error will be always an exception
			VBOHttpDocument::getInstance()->close($error->getCode(), $error->getMessage());
		}

		VBOHttpDocument::getInstance()->json($response);
	}

	/**
	 * Downloads the PRO version from VikWP servers.
	 *
	 * @return 	void
	 */
	public function downloadpro()
	{
		if (!JFactory::getUser()->authorise('core.admin', 'com_vikbooking'))
		{
			// not authorised to view this resource
			VBOHttpDocument::getInstance()->close(403, JText::translate('RESOURCE_AUTH_ERROR'));
		}

		$input = JFactory::getApplication()->input;

		// get input key
		$key = $input->getString('key');

		// get license model
		$model = $this->getModel();

		// dispatch pro version download
		$response = $model->download($key);

		// make sure the download went fine
		if ($response === false)
		{
			// nope, retrieve the error
			$error = $model->getError(null, $toString = false);

			if (!$error instanceof Exception)
			{
				$error = new Exception($error, 500);
			}

			// terminate the response with a proper error code
			VBOHttpDocument::getInstance()->close($error->getCode(), $error->getMessage());
		}

		// downloaded successfully
		VBOHttpDocument::getInstance()->close(200, 'e4j.OK');
	}
}

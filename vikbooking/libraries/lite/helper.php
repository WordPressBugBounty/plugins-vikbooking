<?php
/** 
 * @package     VikBooking - Libraries
 * @subpackage  lite
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Helper implementor used to apply the restrictions of the LITE version.
 *
 * @since 1.5.11
 */
class VikBookingLiteHelper
{
	/**
	 * The platform application instance.
	 * 
	 * @var JApplication
	 */
	private $app;

	/**
	 * The platform database instance.
	 * 
	 * @var JDatabase
	 */
	private $db;

	/**
	 * Class constructor.
	 */
	public function __construct()
	{
		$this->app = JFactory::getApplication();
		$this->db  = JFactory::getDbo();
	}

	/**
	 * Helper method used to disable the capabilities according
	 * to the restrictions applied by the LITE version.
	 * 
	 * @param   array   $capabilities  Array of key/value pairs where keys represent a capability name and boolean values
	 *                                 represent whether the role has that capability.
	 * 
	 * @return  array   The resulting capabilities lookup.
	 */
	public function restrictCapabilities(array $capabilities)
	{
		switch ($this->app->input->get('view'))
		{
			case 'ratesoverv':
				// disable CREATE capability
				$capabilities['com_vikbooking_create'] = false;
				break;

			case 'editbusy':
			case 'editorder':
				// disable capability to access the MANAGEMENT section
				$capabilities['com_vikbooking_vbo_management'] = false;
				break;

			case 'customf':
				// disable CREATE, EDIT and DELETE capabilities
				$capabilities['com_vikbooking_create'] = false;
				$capabilities['com_vikbooking_edit'] = false;
				$capabilities['com_vikbooking_delete'] = false;
				break;
		}

		return $capabilities;
	}

	/**
	 * Intercepts the request to return a custom error message when the current
	 * task is equals to "pricing.setnewrates".
	 * 
	 * @return  void
	 */
	public function disableSetNewRatesTask()
	{
		if ($this->app->input->get('task') === 'pricing.setnewrates')
		{
			$error = 'e4j.error.' . __('This Pricing Model is only supported in the Pro version.', 'vikbooking');
			VBOHttpDocument::getInstance($this->app)->close(200, $error);
		}
	}

	/**
	 * Helper method used to display an advertsing banner while trying
	 * to reach a page available only in the PRO version.
	 * 
	 * @return  void
	 */
	public function displayBanners()
	{
		if (!$this->app->isAdmin())
		{
			return;
		}

		$input = $this->app->input;

		// get current view
		$view = $input->get('view', $input->get('task'));

		// define list of pages not supported by the LITE version
		$lookup = array(
			'coupons'      => '17',
			'crons'        => 'crons',
			'customers'    => '22',
			'einvoicing'   => 'einvoicing',
			'invoices'     => 'invoices',
			'operators'    => 'operators',
			'optionals'    => '6',
			'packages'     => 'packages',
			'payments'     => '14',
			'pmsreports'   => 'pmsreports',
			'restrictions' => 'restrictions',
			'seasons'      => '13',
			'stats'        => 'stats',
		);

		// check whether the view is supported
		if (!$view || !isset($lookup[$view]))
		{
			return;
		}

		// use a missing view to display blank contents
		$input->set('view', 'liteview');
		$input->set('task', '');
		$input->set('hide_menu', true);

		// display menu before unsetting the view
		VikBookingHelper::printHeader($lookup[$view]);

		// display LITE banner
		echo JLayoutHelper::render('html.license.lite', array('view' => $view));

		if (VikBooking::showFooter())
		{
			VikBookingHelper::printFooter();
		}
	}

	/**
	 * Hides all the elements that owns a class equal to "pro-feature".
	 * 
	 * @return  void
	 */
	public function hideProFeatures()
	{
		JFactory::getDocument()->addStyleDeclaration('.pro-feature { display: none !important; }');
	}

	/**
	 * Disable all the features linked to the rooms management provided by
	 * the "editbusy" page.
	 * 
	 * @return  void
	 */
	public function disableEditBusyRoomFeatures()
	{
		if ($this->app->input->get('task') !== 'updatebusy')
		{
			return;
		}

		// scan all the elements set in request
		foreach ($this->app->input->getArray() as $k => $v)
		{
			// check whether the element name follows the switch_[ID] pattern
			if (preg_match("/^switch_[0-9]+$/", $k))
			{
				// unset switch element
				$this->app->input->set($k, '');
			}
		}

		// disable room cancellation
		$this->app->input->set('rm_room_oid', 0);
		// cannot add new rooms from the back-end
		$this->app->input->set('add_room_id', 0);
	}

	/**
	 * Helper method used to display the scripts and the HTML needed to
	 * allow the management of the terms-of-service custom field.
	 * 
	 * @param   JView  $view  The view instance.
	 * 
	 * @return  void
	 */
	public function displayTosFieldManagementForm($view)
	{
		// iterate all custom fields
		foreach ($view->rows as $cf)
		{
			$cf = (array) $cf;

			// check if we have a checkbox field
			if ($cf['type'] == 'checkbox')
			{
				// use scripts to manage ToS
				echo JLayoutHelper::render('html.managetos.script', array('field' => $cf));
			}
		}
	}

	/**
	 * Helper method used to intercept the custom request used to update
	 * the terms-of-service custom field.
	 * 
	 * @return  void
	 */
	public function listenTosFieldSavingTask()
	{
		// check if we should save the TOS field
		if ($this->app->input->get('task') == 'customf.savetosajax')
		{
			if (!JSession::checkToken())
			{
				VBOHttpDocument::getInstance($this->app)->close(403, JText::translate('JINVALID_TOKEN'));
			}

			$db = JFactory::getDbo();

			$data = new stdClass;
			$data->name    = $this->app->input->get('name', '', 'string');
			$data->poplink = $this->app->input->get('poplink', '', 'string');
			$data->id      = $this->app->input->get('id', 0, 'uint');

			$db->updateObject('#__vikbooking_custfields', $data, 'id');

			// return saved object to caller
			VBOHttpDocument::getInstance($this->app)->json($data);
		}
	}
}

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
 * VikBooking plugin Shortcodes controller.
 *
 * @since 	1.0
 * @see 	JControllerAdmin
 */
class VikBookingControllerShortcodes extends JControllerAdmin
{
	public function create()
	{
		if (!JFactory::getUser()->authorise('core.admin', 'com_vikbooking'))
		{
			wp_die(
				'<h1>' . JText::translate('FATAL_ERROR') . '</h1>' .
				'<p>' . JText::translate('RESOURCE_AUTH_ERROR') . '</p>',
				403
			);
		}

		$input = JFactory::getApplication()->input;

		$input->set('type', 'new');
		$input->set('view', 'shortcode');

		parent::display();
	}

	public function edit()
	{
		if (!JFactory::getUser()->authorise('core.admin', 'com_vikbooking'))
		{
			wp_die(
				'<h1>' . JText::translate('FATAL_ERROR') . '</h1>' .
				'<p>' . JText::translate('RESOURCE_AUTH_ERROR') . '</p>',
				403
			);
		}

		$input = JFactory::getApplication()->input;

		$input->set('type', 'edit');
		$input->set('view', 'shortcode');

		parent::display();
	}

	public function delete()
	{
		$app 	= JFactory::getApplication();
		$input 	= $app->input;

		$cid 	 = $input->getUint('cid', array());
		$encoded = $input->getBase64('return', '');

		$this->model->delete($cid);

		$app->redirect('admin.php?option=com_vikbooking&view=shortcodes&return=' . $encoded);
	}

	public function cancel()
	{
		$app = JFactory::getApplication();

		$encoded = $app->input->getBase64('return', '');

		$app->redirect('admin.php?option=com_vikbooking&view=shortcodes&return=' . $encoded);
	}

	public function back()
	{
		$app = JFactory::getApplication();

		$return = $app->input->getBase64('return', '');

		if ($return)
		{
			$return = base64_decode($return);
		}
		else
		{
			$return = 'admin.php?option=com_vikbooking';
		}

		$app->redirect($return);
	}
}

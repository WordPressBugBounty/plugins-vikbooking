<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

jimport('joomla.application.component.view');

class VikbookingViewTinyurl extends JViewVikBooking
{
	public function display($tpl = null)
	{
		$app = JFactory::getApplication();

		$sequence = $app->input->getAlnum('to');

		// access the shorten URL model
		$model = VBOModelShortenurl::getInstance();

		try {
			// route the tiny URL's sequence code to the original URL
			$original_url = $model->routeToOriginal((string) $sequence);
		} catch (Exception $e) {
			// terminate the execution by propagating the error
			VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
		}

		$this->sequence     = $sequence;
		$this->model        = $model;
		$this->original_url = $original_url;

		parent::display($tpl);
	}
}

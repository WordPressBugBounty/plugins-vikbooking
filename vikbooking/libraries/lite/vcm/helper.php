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
 * @since 1.8
 */
class VikChannelManagerLiteHelper
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
	 * Intercepts any calls to the channel manager.
	 * 
	 * @return  void
	 */
	public function hijackChannelManager()
	{
		if (!$this->app->isAdmin())
		{
			return;
		}

		$input = $this->app->input;

		// get current component
		$option = preg_replace("/^com_/i", '', (string) $input->get('page', $input->get('option')));

		if ($option === 'vikchannelmanager')
		{
			// route to VikBooking controller
			$this->app->redirect('index.php?option=com_vikbooking&view=vikchannelmanager');
			$this->app->close();
		}
	}

	/**
	 * Helper method used to display an advertsing banner while trying
	 * to reach a page available only with VCM installed.
	 * 
	 * @return  void
	 */
	public function displayViewBanners()
	{
		if (!$this->app->isAdmin())
		{
			return;
		}

		$input = $this->app->input;

		// get current view
		$view = $input->get('view', $input->get('task'));

		// define list of pages not supported without VikChannelManager
		$lookup = [
			'taskmanager' => 'taskmanager',
			'vikchannelmanager' => 'vikchannelmanager',
		];

		// check whether a banner should be displayed
		if (!isset($lookup[$view]))
		{
			return;
		}

		// use a missing view to display blank contents
		$input->set('view', 'liteview');
		$input->set('task', '');
		$input->set('hide_menu', true);

		// display menu before unsetting the view
		VikBookingHelper::printHeader($lookup[$view]);

		// display adv banner
		echo JLayoutHelper::render('html.license.adv.' . $view);

		if (VikBooking::showFooter())
		{
			VikBookingHelper::printFooter();
		}
	}

	/**
	 * Helper method used to display an advertsing banner while trying
	 * to render a widget available only with VCM installed.
	 * 
	 * @return  void
	 */
	public function displayWidgetBanners()
	{
		if (!$this->app->isAdmin())
		{
			return;
		}

		$input = $this->app->input;

		// make sure we are rendering an admin widget
		if ($input->get('task') !== 'exec_admin_widget' || $input->get('call') !== 'render')
		{
			return;
		}

		$widget = $input->get('widget_id');

		// define list of widgets not supported without VikChannelManager
		$lookup = [
			'aitools' => 'ai',
			'guest_messages' => 'guestmessages',
			'guest_reviews' => 'guestreviews',
			'latest_from_guests' => 'guestnews',
			'operators_chat' => 'operatorschat',
		];

		// check whether a banner should be displayed
		if (!isset($lookup[$widget]))
		{
			return;
		}

		// output the JSON encoded response and exit
		VBOHttpDocument::getInstance()->json([
			'render' => JLayoutHelper::render('html.license.adv.' . $lookup[$widget])
		]);
	}

	/**
	 * Prevents certain widgets from being added to the multitask panel.
	 * 
	 * @return  void
	 */
	public function hijackWidgetMultitask()
	{
		if (!$this->app->isAdmin())
		{
			return;
		}

		$input = $this->app->input;

		// make sure we are updating a widget an admin widget
		if ($input->get('task') !== 'exec_multitask_widgets' || $input->get('call') !== 'updateMultitaskingMap')
		{
			return;
		}

		$args = $input->get('call_args', [], 'array');
		$widgets = (array) (!empty($args[1]) ? $args[1] : []);

		// define list of widgets not supported without VikChannelManager
		$lookup = [
			'aitools',
			'guest_messages',
			'guest_reviews',
			'latest_from_guests',
			'operators_chat',
		];

		// remove all the widgets that should not be saved within the multitask panel
		$filtered = array_filter($widgets, function($widget) use ($lookup) {
			return !in_array($widget, $lookup);
		});

		// in case something has changed, update the filtered widgets array into the request
		if (count($widgets) !== count($filtered)) {
			$args[1] = array_values($filtered);
			$input->set('call_args', $args);
		}
	}
}

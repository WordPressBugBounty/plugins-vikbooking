<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	dashboard
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2020 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Widget used to display the latest RSS feeds within the
 * admin dashboard of WordPress.
 *
 * @since 1.3.9
 */
class JDashboardWidgetVikBookingRss extends JDashboardWidget
{
	/**
	 * The RSS reader class handler.
	 *
	 * @var JRssReader
	 */
	protected $rss;

	/**
	 * Class constructor.
	 */
	public function __construct()
	{
		// set up RSS reader
		$this->rss = VikBookingBuilder::setupRssReader();
	}

	/**
	 * Returns the name of the widget.
	 *
	 * @return 	string
	 */
	public function getName()
	{
		return __('VikBooking - RSS Feeds', 'vikbooking');
	}

	/**
	 * Checks whether the specified user is able to access
	 * this widget. Allow only super users.
	 *
	 * @param 	mixed 	 $user  The user to check.
	 *
	 * @return 	boolean
	 */
	public function canAccess($user = null)
	{
		if (!$user instanceof JUser)
		{
			// get user
			$user = JFactory::getUser($user);
		}

		// allow super users only
		return $user->authorise('core.admin', 'com_vikbooking');
	}

	/**
	 * Returns the configuration of the widget.
	 *
	 * @return 	JRegistry  A registry of the configuration.
	 */
	public function getConfig()
	{
		// obtain dashboard configuration
		$config = parent::getConfig();

		try
		{
			// inject opt-in date within configuration
			$config->set('optin', $this->rss->optedIn($date = true));
		}
		catch (Exception $e)
		{
			// missing opt-in
			$config->set('optin', false);
		}

		// inject supported channels within configuration
		$config->set('channels', $this->rss->getChannels());
	  
		return $config;
	}

	/**
	 * Renders the HTML to display within the contents of the widget.
	 * 
	 * @param 	mixed 	$args  A registry of settings.
	 *
	 * @return 	string  The HTML to display.
	 */
	protected function renderWidget($args)
	{
		try
		{
			$options = array(
				// take a number of feeds equals to the value
				// specified from the widget config (5 by default)
				'limit' => $args->get('limit', 5),
			);

			// try to download feeds
			$feeds = $this->rss->download($options);
		}
		catch (Exception $e)
		{
			// go ahead silently
			$feeds = array();
		}

		// prepare display data
		$data = array(
			'config' => $args,
			'widget' => $this,
			'feeds'  => $feeds,
		);

		// create layout file
		$layout = new JLayoutFile('html.wpdash.rss.widget', null, array('component' => 'com_vikbooking'));

		// render widget content
		return $layout->render($data);
	}

	/**
	 * Returns an instance of the form, if supported.
	 * Implement this method in order to avoid forcing
	 * its declaration in subclasses.
	 *
	 * @return 	null|JForm
	 */
	public function getForm()
	{
		static $form = null;

		if (!$form)
		{
			// build XML path
			$xml = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'rss.xml';

			// prepare form options
			$options = array(
				'control' => 'jform',
				'client'  => 'vikbooking',
			);

			// create form only once
			$form = JForm::getInstance($this->getID(), $xml, $options);
		}

		return $form;
	}

	/**
	 * Renders the HTML to display within the configuration of the widget.
	 * Implement this method in order to avoid forcing its declaration in subclasses.
	 * 
	 * @param 	mixed 	$args  A registry of settings.
	 *
	 * @return 	string  The HTML to display.
	 */
	protected function renderForm($args)
	{
		$document = JFactory::getDocument();

		// force 100% width for configuration fieldsets
		$document->addStyleDeclaration('#vik_booking_rss .postbox-container { width: 100% !important; }');

		// let parent renders the configuration starting
		// from the specified XML form
		return parent::renderForm($args);
	}
}

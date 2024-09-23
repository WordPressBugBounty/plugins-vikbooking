<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	system
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2020 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Helper class used to manage settings related to RSS feeds.
 *
 * @since 1.3.9
 */
class VikBookingRssFeeds
{
	/**
	 * Hook used to manipulate the RSS channels.
	 *
	 * @param 	array    $channels   A list of RSS permalinks.
	 * @param 	boolean  $published  True to return only the published channels.
	 *
	 * @return 	array    The channels to use for RSS subscription.
	 */
	public static function getChannels(array $channels = array(), $published = true)
	{
		// subscribe reader to the following channels
		$default = array(
			'https://vikwp.com/rss/news/',
			'https://vikwp.com/rss/promo/',
			'https://vikwp.com/rss/tips/',
		);

		// allow channels manipulation only to PRO users
		if (VikBookingLicense::isPro() && $published)
		{
			$user = JFactory::getUser();

			// get channels configuration
			$config = get_user_meta($user->id, 'vikbooking_rss_urls', true);

			// make sure we have a configuration
			if (is_array($config))
			{
				// take only the active channels
				$default = array_intersect($default, $config);
			}
		}

		// apply filters only in case we need the final URI
		if ($published)
		{
			// build query string for each channel
			$default = array_map(function($url)
			{
				// create URI
				$url = new JUri($url);

				// append format and type
				$url->setVar('format', 'feed');
				$url->setVar('type', 'rss');

				// apply tag filter (8: vikbooking, 12: vbo-lite, 16: vbo-pro)
				$tags = array(8);

				if (VikBookingLicense::isPro())
				{
					// PRO tag
					$tags[] = 16;
				}
				else
				{
					// LITE tag
					$tags[] = 12;
				}
				
				$url->setVar('filter_tag', $tags);

				// take language from WP locale
				$langtag = JFactory::getLanguage()->getTag();

				// look for language part
				if (preg_match("/^([a-z]{2,})[\-_][a-z]{2,}$/i", $langtag, $match))
				{
					// Append language to URI.
					// In case the language is not supported, the system
					// will fallback to the default one.
					$url->setVar('lang', end($match));
				}

				// take only the channel string
				return (string) $url;
			}, $default);
		}

		// merge default channels with existing ones
		return array_merge($channels, $default);
	}

	/**
	 * Downloads the latest feed and display it, if any.
	 *
	 * @return 	void
	 */
	public static function download()
	{
		$app  = JFactory::getApplication();
		$user = JFactory::getUser();

		// make sure that all the following conditions are verified
		$conditions = (
			// we are not doing AJAX
			!wp_doing_ajax()
			// we are in the back-end
			&& $app->isClient('administrator')
			// the user is an administrator
			&& $user->authorise('core.admin', 'com_vikbooking')
			// the dashboard should display the RSS feeds
			&& static::isDashboard()
			// the initial setup was at least started
			&& static::hasMinimumSetup()
		);

		// validate conditions flag
		if ($conditions)
		{
			// instantiate RSS reader
			$rss = VikBookingBuilder::setupRssReader();

			try
			{
				// prepare download options
				$options = array(
					// take only one item
					'limit' => 1,
					// take only visible feeds
					'new' => true,
					// take oldest feed
					'order' => 'asc',
				);

				// try to download the feed
				$feed = $rss->download($options);

				if ($feed)
				{
					// opt-in missing, ask the user to agree to our terms
					echo JLayoutHelper::render('html.rss.feed', array('feed' => $feed));
				}
			}
			catch (JRssOptInException $e)
			{
				// opt-in missing, ask the user to agree to our terms
				echo JLayoutHelper::render('html.rss.optin');
			}
			catch (Exception $e)
			{
				// service declined, go ahead silently	
			}
		}
	}

	/**
	 * Displays the configuration fieldset to manage the opt-in.
	 *
	 * @param 	mixed   $forms  The HTML to display.
	 * @param 	mixed   $view 	The current view instance.
	 *
	 * @return 	mixed   The HTML to display.
	 *
	 * @return 	mixed 	The HTML to display.
	 */
	public static function config($forms, $view)
	{
		$app  = JFactory::getApplication();
		$user = JFactory::getUser();

		if (!is_array($forms))
		{
			$forms = array();
		}

		// make sure we are under VikBooking
		if ($app->input->get('option') != 'com_vikbooking')
		{
			// do not go ahead
			return $forms;
		}

		// instantiate RSS reader
		$rss = VikBookingBuilder::setupRssReader();

		// set up configuration array
		$config = array();

		try
		{
			$config['optin'] = $rss->optedIn();
		}
		catch (Exception $e)
		{
			$config['optin'] = false;
		}

		// get published channels
		$config['channels'] = $rss->getChannels();

		// take only the host and path because the query string might vary
		$config['channels'] = array_map(function($url)
		{
			$url = new JUri($url);
			$url->setQuery('');
			return (string) $url;
		}, $config['channels']);

		// load all supported channels
		$list = apply_filters('vikbooking_fetch_rss_channels', array(), false);

		$channels = array();

		// iterate channels to fetch a readable label
		foreach ($list as $url)
		{
			$url = new JUri($url);

			// get path without trailing slash
			$key = trim($url->toString(array('host', 'path')), '/');
			// explode paths
			$chunks = explode('/', $key);
			// take only the last
			$key = array_pop($chunks);

			// prepend path recursively in case of non-unique path
			while (isset($channels[$key]) && $chunks)
			{
				$key = array_pop($chunks) . ' ' . $key;
			}

			// remove query from URL
			$url->setQuery('');

			// register channel
			$channels[$key] = (string) $url;
		}

		// get display dashboard from user meta
		$config['dashboard'] = static::isDashboard();

		// prepare layout data
		$data = array(
			'rss'      => $rss,
			'config'   => $config,
			'channels' => $channels,
		);

		// include sub-fieldset to enable RSS configuration
		$layout = new JLayoutFile('html.rss.config');
		// render layout
		$html = $layout->render($data);

		// create an apposite fieldset for RSS
		$forms['RSS'] = $html;

		return $forms;
	}

	/**
	 * Saves the opt-in choice made by the user.
	 *
	 * @return 	void
	 */
	public static function save()
	{
		$app  = JFactory::getApplication();
		$user = JFactory::getUser();

		// make sure we are under VikBooking
		if ($app->input->get('option') != 'com_vikbooking')
		{
			// do not go ahead
			return;
		}

		// instantiate RSS reader
		$rss = VikBookingBuilder::setupRssReader();

		try
		{
			$status = $rss->optedIn();
		}
		catch (Exception $e)
		{
			$status = false;
		}

		$input = JFactory::getApplication()->input;

		// get user choice
		$choice = $input->getBool('rss_optin_status', false);

		// check whether the choice changed
		if ($choice != $status)
		{
			// update choice
			$rss->optIn($choice);
		}

		// recover display dashboard from request
		$dashboard = $input->get('rss_display_dashboard', 0, 'uint');

		// update dashboard visibility
		update_user_meta($user->id, 'vikbooking_rss_display_dashboard', $dashboard);

		// allow channels manipulation only to PRO users
		if (VikBookingLicense::isPro())
		{
			// recover specified channels from request
			$channels = $input->get('rss_channel_url', array(), 'string');

			// update channels configuration
			update_user_meta($user->id, 'vikbooking_rss_urls', $channels);
		}
	}

	/**
	 * Adjusts the RSS class to the plugin needs.
	 * Executes before using it.
	 * 
	 * @param 	JRssReader  $rss  The RSS reader handler.
	 *
	 * @return 	void
	 */
	public static function ready(&$rss)
	{
		/**
		 * Filters the HTML that is allowed for a given context.
		 *
		 * @since 3.5.0
		 *
		 * @param array   $tags     An associative array containing the supported tags
		 * 						    and all the related attributes.
		 * @param string  $context  Context name.
		 */
		add_filter('wp_kses_allowed_html', function($tags, $context)
		{
			// make sure we are filtering a POST context
			if ($context == 'post')
			{
				// add support for input field
				$tags['input'] = [
					'type'     => true,
					'name'     => true,
					'id'       => true,
					'class'    => true,
					'value'    => true,
					'style'    => true,
					'disabled' => true,
					'readonly' => true,
				];

				// add support for textarea field
				$tags['textarea'] = [
					'name'     => true,
					'id'       => true,
					'class'    => true,
					'style'    => true,
					'rows'     => true,
					'cols'     => true,
					'disabled' => true,
					'readonly' => true,
				];

				// add support for button
				if (isset($tags['button']))
				{
					// just include the use of onclick attribute
					$tags['button']['onclick'] = true;
				}
				else
				{
					// define all supported attributes
					$tags['button'] = [
						'type'     => true,
						'id'       => true,
						'class'    => true,
						'style'    => true,
						'onclick'  => true,
						'disabled' => true,
					];
				}

				// add support for source under a <video> tag
				$tags['source'] = [
					'src' => true,
				];
			}

			return $tags;
		}, 10, 2);
	}

	/**
	 * Returns true in case the RSS feeds should be displayed
	 * within the dashboard of VikBooking.
	 *
	 * @return 	boolean
	 */
	public static function isDashboard()
	{
		$user = JFactory::getUser();

		// get display dashboard from user meta
		$dashboard = get_user_meta($user->id, 'vikbooking_rss_display_dashboard', true);

		// make sure we have a number
		if (preg_match("/^[01]$/", (string) $dashboard))
		{
			// cast value to boolean
			$dashboard = (bool) $dashboard;
		}
		else
		{
			// missing configuration, always show by default
			$dashboard = true;
		}

		return $dashboard;
	}

	/**
	 * Returns true in case a minimum setup has been started.
	 *
	 * @return 	boolean
	 */
	public static function hasMinimumSetup()
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `id` FROM `#__vikbooking_rooms`";

		$dbo->setQuery($q);
		$dbo->execute();

		return ($dbo->getNumRows() > 0);
	}
}

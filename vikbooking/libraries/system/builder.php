<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	system
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Helper class to setup the plugin.
 *
 * @since 1.0
 */
class VikBookingBuilder
{
	/**
	 * Loads the .mo language related to the current locale.
	 *
	 * @return 	void
	 */
	public static function loadLanguage()
	{
		$app = JFactory::getApplication();

		/**
		 * @since 	1.0.2 	All the language files have been merged 
		 * 					within a single file to be compliant with
		 * 					the Worpdress Translation Standards.
		 *					The language file is located in /languages folder.
		 */
		$path 	 = VIKBOOKING_LANG;

		$handler = VIKBOOKING_LIBRARIES . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR;
		$domain  = 'vikbooking';

		// init language
		$lang = JFactory::getLanguage();
		
		$lang->attachHandler($handler . 'system.php', $domain);
		
		if ($app->isAdmin())
		{
			$lang->attachHandler($handler . 'adminsys.php', $domain);
			$lang->attachHandler($handler . 'admin.php', $domain);
		}
		else
		{
			$lang->attachHandler($handler . 'site.php', $domain);
		}

		$lang->load($domain, $path);
	}

	/**
	 * Setup the pagination layout to use.
	 *
	 * @return 	void
	 */
	public static function setupPaginationLayout()
	{
		$layout = new JLayoutFile('html.system.pagination', null, array('component' => 'com_vikbooking'));

		JLoader::import('adapter.pagination.pagination');
		JPagination::setLayout($layout);
	}

	/**
	 * Pushes the plugin pages into the WP admin menu.
	 *
	 * @return 	void
	 *
	 * @link 	https://developer.wordpress.org/resource/dashicons/#star-filled
	 */
	public static function setupAdminMenu()
	{
		JLoader::import('adapter.acl.access');
		$capability = JAccess::adjustCapability('core.manage', 'com_vikbooking');

		add_menu_page(
			JText::translate('COM_VIKBOOKING'), 	// page title
			JText::translate('COM_VIKBOOKING_MENU'), 	// menu title
			$capability,						// capability
			'vikbooking', 						// slug
			array('VikBookingBody', 'getHtml'),	// callback
			'dashicons-building',				// icon
			71									// ordering
		);
	}

	/**
	 * Setup HTML helper classes.
	 * This method should be used to register custom function
	 * for example to render own layouts.
	 *
	 * @return 	void
	 */
	public static function setupHtmlHelpers()
	{
		// helper method to render calendars layout
		JHtml::register('renderCalendar', function($data)
		{
			JHtml::fetch('script', VBO_SITE_URI . 'resources/jquery-ui.min.js');
			JHtml::fetch('stylesheet', VBO_SITE_URI . 'resources/jquery-ui.min.css');

			$layout = new JLayoutFile('html.plugins.calendar', null, array('component' => 'com_vikbooking'));
			
			return $layout->render($data);
		});

		// helper method to get the plugin layout file handler
		JHtml::register('layoutfile', function($layoutId, $basePath = null, $options = array())
		{
			$input = JFactory::getApplication()->input;

			if (!isset($options['component']) && !$input->getBool('option'))
			{
				// force layout file in case there is no active plugin
				$options['component'] = 'com_vikbooking';
			}

			return new JLayoutFile($layoutId, $basePath, $options);
		});

		// helper method to include the system JS file
		JHtml::register('system.js', function()
		{
			static $loaded = 0;

			if (!$loaded)
			{
				// include only once
				$loaded = 1;

				$internalFilesOptions = array('version' => VIKBOOKING_SOFTWARE_VERSION);

				JHtml::fetch('script', VBO_ADMIN_URI . 'resources/js/system.js', $internalFilesOptions, array('id' => 'vbo-sys-script'));
				JHtml::fetch('stylesheet', VBO_ADMIN_URI . 'resources/css/system.css', $internalFilesOptions, array('id' => 'vbo-sys-style'));

				/**
				 * The CSS/JS files of Bootstrap may disturb the styles of the Theme, and so
				 * we load it only within the back-end, or if the configuration setting is on.
				 * 
				 * @since 	1.3.0
				 */
				if (JFactory::getApplication()->isAdmin() || (class_exists('VikBooking') && VikBooking::loadBootstrap()))
				{
					/**
					 * Prior the version 1.3.5 the file bootstrap.min.js was always loaded above and outside
					 * this IF statement. We now wrap all Bootstrap assets within the admin or setting enabled.
					 * 
					 * @since 	1.3.5
					 */
					JHtml::fetch('script', VBO_ADMIN_URI . 'resources/js/bootstrap.min.js', $internalFilesOptions, array('id' => 'bootstrap-script'));

					JHtml::fetch('stylesheet', VBO_ADMIN_URI . 'resources/css/bootstrap.lite.css', $internalFilesOptions, array('id' => 'bootstrap-lite-style'));
				}
			}
		});

		// helper method to include the select2 JS file
		JHtml::register('select2', function()
		{
			/**
			 * Select2 is now loaded only when requested.
			 *
			 * @since 1.2.5
			 */
			JHtml::fetch('script', VBO_ADMIN_URI . 'resources/select2.min.js');
			JHtml::fetch('stylesheet', VBO_ADMIN_URI . 'resources/select2.min.css');
		});

		/**
		 * Register helper methods to sanitize attributes, html, JS and other elements.
		 */
		JHtml::register('esc_attr', function($str)
		{
			return esc_attr($str);
		});

		JHtml::register('esc_html', function($str)
		{
			return esc_html($str);
		});

		JHtml::register('esc_js', function($str)
		{
			return esc_js($str);
		});

		JHtml::register('esc_textarea', function($str)
		{
			return esc_textarea($str);
		});

		/**
		 * Attempt to turn on the SQL_BIG_SELECTS setting at runtime, to avoid
		 * SQL errors like "The SELECT would examine more than MAX_JOIN_SIZE rows;".
		 * This has affected several clients with the Channel Manager for the Guest Messages
		 * downloaded by OTAs like Booking.com or Airbnb. Since we do this operation at runtime,
		 * we attempt to suppress the DB errors in case the user does not have enough permissions
		 * to run queries of type "SET". Once executed, we restore the original value for DB errors.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		add_action('plugins_loaded', function()
		{
			$dbo = JFactory::getDbo();

			// suppress temporarily any database error
			$dbo->suppress_errors(true);

			// turn on the required SQL setting
			$dbo->setQuery('SET SQL_BIG_SELECTS=1');
			$dbo->execute();

			// restore the default SQL display errors setting
			$dbo->suppress_errors(false);
		});
	}

	/**
	 * This method is used to configure teh payments framework.
	 * Here should be registered all the default gateways supported
	 * by the plugin.
	 *
	 * @return 	void
	 *
	 * @since 	1.0.5
	 */
	public static function configurePaymentFramework()
	{
		// push the pre-installed gateways within the payment drivers list
		add_filter('get_supported_payments_vikbooking', function($drivers)
		{
			$list = glob(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR . '*.php');

			return array_merge($drivers, $list);
		});

		// load payment handlers when dispatched
		add_action('load_payment_gateway_vikbooking', function(&$drivers, $payment)
		{
			$classname = null;
			
			VikBookingLoader::import('admin.payments.' . $payment, VIKBOOKING_BASE);

			switch ($payment)
			{
				case 'paypal':
					$classname = 'VikBookingPayPalPayment';
					break;

				case 'paypal_checkout':
					$classname = 'VikBookingPayPalCheckoutPayment';
					break;

				case 'offline_credit_card':
					$classname = 'VikBookingOfflineCreditCardPayment';
					break;

				case 'bank_transfer':
					$classname = 'VikBookingBankTransferPayment';
					break;
			}

			if ($classname)
			{
				$drivers[] = $classname;
			}
		}, 10, 2);

		// manipulate response to be compliant with notifypayment task
		add_action('payment_after_validate_transaction_vikbooking', function(&$payment, &$status, &$response)
		{
			/**
			 * Transaction property can be used to perform refunds, and it's collected
			 * and returned during charge/capture transactions only.
			 * 
			 * @since 	1.4.0
			 * @since 	1.6.9  added support for "tot_fees".
			 */

			// manipulate the response to be compliant with the old payment system
			$response = array(
				'verified' 	  => (int) $status->isVerified(),
				'tot_paid' 	  => $status->amount,
				'tot_fees' 	  => $status->fees,
				'log'	   	  => $status->log,
				'transaction' => $status->transaction,
			);

			if ($status->skip_email)
			{
				$response['skip_email'] = $status->skip_email;
			}
		}, 10, 3);

		// manipulate response to be compliant with dorefund task
		add_action('payment_after_refund_transaction_vikbooking', function(&$payment, &$status, &$response)
		{
			/**
			 * Transactions of type refund need to unify the response
			 * to be compliant with all platforms.
			 * 
			 * @since 	1.4.0
			 */

			// manipulate the response to be compliant with the old payment system
			$response = array(
				'verified' 		=> (int) $status->isVerified(),
				'tot_refunded' 	=> $status->amount,
				'log'	   		=> $status->log,
			);
		}, 10, 3);

		// manipulate response to be compliant with direct charge transaction
		add_action('payment_after_direct_charge_vikbooking', function($payment, $status, &$response)
		{
			/**
			 * Transactions of type direct charge need to unify the response
			 * to be compliant with all platforms.
			 * 
			 * @since 	1.6.4
			 */

			// manipulate the response to be compliant with any platform
			$response = array(
				'verified' 	  => (int) $status->isVerified(),
				'tot_paid' 	  => $status->amount,
				'log'	   	  => $status->log,
				'transaction' => $status->transaction,
			);
		}, 10, 3);
	}

	/**
	 * Registers all the widget contained within the modules folder.
	 *
	 * @return 	void
	 */
	public static function setupWidgets()
	{
		JLoader::import('adapter.module.factory');

		// load all the modules
		JModuleFactory::load(VIKBOOKING_BASE . DIRECTORY_SEPARATOR . 'modules');

		/**
		 * Loads also the widgets to display within the
		 * admin dashboard of WordPress.
		 *
		 * @since 1.3.9
		 */
		add_action('wp_dashboard_setup', function()
		{
			JLoader::import('adapter.dashboard.admin');

			// set up folder containing the widget to load
			$path = VIKBOOKING_LIBRARIES . DIRECTORY_SEPARATOR . 'dashboard';
			// define the classname prefix
			$prefix = 'JDashboardWidgetVikBooking';

			try
			{
				// load and register widgets
				JDashboardAdmin::load($path, $prefix);
			}
			catch (Exception $e)
			{
				// silently suppress exception to avoid breaking the website

				if (VIKBOOKING_DEBUG)
				{
					// propagate error in case of debug enabled
					throw $e;
				}
			}
		});
	}

	/**
	 * Configures the RSS feeds reader.
	 *
	 * @return 	JRssReader
	 *
	 * @since 	1.3.9
	 */
	public static function setupRssReader()
	{
		// autoload RSS handler class
		JLoader::import('adapter.rss.reader');

		/**
		 * Hook used to manipulate the RSS channels to which the plugin is subscribed.
		 *
		 * @param 	array    $channels  A list of RSS permalinks.
		 * @param 	boolean  $status    True to return only the published channels.
		 *
		 * @return 	array    A list of supported channels.
		 *
		 * @since 	1.3.9
		 */
		$channels = apply_filters('vikbooking_fetch_rss_channels', array(), true);

		if (VIKBOOKING_DEBUG)
		{
			/**
			 * Filters the transient lifetime of the feed cache.
			 *
			 * @since 2.8.0
			 *
			 * @param 	integer  $lifetime  Cache duration in seconds. Default is 43200 seconds (12 hours).
			 * @param 	string   $filename  Unique identifier for the cache object.
			 */
			add_filter('wp_feed_cache_transient_lifetime', function($time, $url) use ($channels)
			{
				// in case of debug enabled, cache the feeds only for 60 seconds
				if ($url == $channels || in_array($url, $channels))
				{
					$time = 60;
				}

				return $time;
			}, 10, 2);
		}

		// instantiate RSS reader
		$rss = JRssReader::getInstance($channels, 'vikbooking');

		/**
		 * Hook used to apply some stuff before returning the RSS reader.
		 *
		 * @param 	JRssReader  &$rss  The RSS reader handler.
		 *
		 * @return 	void
		 *
		 * @since 	1.3.9
		 */
		do_action_ref_array('vikbooking_before_use_rss', array(&$rss));

		return $rss;
	}

	/**
	 * Extends the backup framework.
	 *
	 * @return 	void
	 *
	 * @since 	1.5
	 */
	public static function setupBackupSystem()
	{
		/**
		 * Anonymous function used to check whether the manifest includes the shortcodes import.
		 * 
		 * @param 	object  $manifest  The backup manifest.
		 * 
		 * @return 	boolean
		 */
		$hasShortcodes = function($manifest)
		{
			// look for the uninstall directive inside the manifest, which is mainly
			// used during the sample data installation
			if (isset($manifest->uninstall))
			{
				// iterate all uninstall queries
				foreach ((array) $manifest->uninstall as $query)
				{
					// look for a table that uninstall the shortcodes
					if (preg_match("/#__vikbooking_wpshortcodes\b/", $query))
					{
						return true;
					}
				}
			}

			// look for the directive containing the installation rules
			if (isset($manifest->installers))
			{
				// iterate all install rules
				foreach ((array) $manifest->installers as $rule)
				{
					// detect SQL File role
					if ($rule->role === 'sqlfile')
					{
						// check shortcodes into the file path
						$target = [$rule->data->path];
					}
					else if ($rule->role === 'sql')
					{
						// search into all the provided queries
						$target = (array) $rule->data;
					}
					else
					{
						// nothing to check
						$target = [];
					}

					foreach ($target as $tmp)
					{
						// check whether the current target mentions the shortcodes database table
						if (preg_match("/#__vikbooking_wpshortcodes\b/", $tmp))
						{
							return true;
						}
					}
				}
			}

			return false;
		};

		/**
		 * Trigger event to allow third party plugins to extend the backup import.
		 * This hook triggers before processing the import of an existing backup.
		 * 
		 * It is possible to throw an exception to prevent the import process.
		 * 
		 * Uninstalls all the pages that have been assigned to the existing shortcodes.
		 * 
		 * @param 	object  $manifest  The backup manifest.
		 * @param 	string  $path      The path of the backup archive (uncompressed).
		 * 
		 * @since 	1.5
		 * 
		 * @throws 	Exception
		 */
		add_action('vikbooking_before_import_backup', function($manifest, $path) use ($hasShortcodes)
		{
			// check whether the manifest includes the shortcodes installation
			$manifest->shortcodes = $hasShortcodes($manifest);

			if (empty($manifest->shortcodes))
			{
				// shortcodes not included within the backup, do not uninstall
				return;
			}

			// get shortcode admin model
			$model = JModel::getInstance('vikbooking', 'shortcodes', 'admin');

			// get all existing shortcodes
			$shortcodes = $model->all(array('createdon', 'post_id'));

			// iterate all shortcodes found
			foreach ($shortcodes as $shortcode)
			{
				// make sure the shortcode has been assigned to a post
				if ($shortcode->post_id)
				{
					// get post details
					$post = get_post((int) $shortcode->post_id);

					if (!$post)
					{
						continue;
					}

					// convert shortcode creation date
					$shortcode->createdon = new JDate($shortcode->createdon);
					// convert post creation date
					$post->post_date_gmt = new JDate($post->post_date_gmt);

					// compare ephocs and make sure the post was not created before the shortcode
					if ((int) $shortcode->createdon->format('U') <= (int) $post->post_date_gmt->format('U'))
					{
						// permanently delete post
						wp_delete_post($post->ID, $force_delete = true);
					}
				}
			}
		}, 10, 2);

		/**
		 * Trigger event to allow third party plugins to extend the backup import.
		 * This hook triggers after processing the import of an existing backup.
		 * 
		 * It is possible to throw an exception to prevent the import process.
		 * 
		 * Assigns all the newly created shortcodes to new pages.
		 * 
		 * @param 	object  $manifest  The backup manifest.
		 * @param 	string  $path      The path of the backup archive (uncompressed).
		 * 
		 * @since 	1.5
		 * 
		 * @throws 	Exception
		 */
		add_action('vikbooking_after_import_backup', function($manifest, $path)
		{
			if (!empty($manifest->shortcodes))
			{
				// get shortcodes admin model
				$listModel = JModel::getInstance('vikbooking', 'shortcodes', 'admin');

				// get all existing shortcodes
				$shortcodes = $listModel->all('id');

				// get shortcode admin model
				$model = JModel::getInstance('vikbooking', 'shortcode', 'admin');

				// iterate all shortcodes found
				foreach ($shortcodes as $shortcode)
				{
					// assign the shortcode to a new page
					$model->addPage($shortcode->id);
				}
			}

			// trigger full files mirroring
			VikBookingUpdateManager::triggerUploadFullMirroring();
		}, 10, 2);

		/**
		 * Trigger event to allow third party plugins to choose what are the columns to dump
		 * and whether the table should be skipped or not.
		 * 
		 * Fires while attaching a rule to dump some SQL statements.
		 * 
		 * Used to avoid dumping the post ID to which the shortcodes are attached
		 * 
		 * @param 	boolean  $include   False to avoid including the table into the backup.
		 * @param 	array    &$columns  An associative array of supported database table columns,
		 *                              where the key is the column name and the value is a nested
		 *                              array holding the column information.
		 * @param 	string   $table     The name of the database table.
		 * 
		 * @since 	1.5
		 */
		add_filter('vikbooking_before_backup_dump_sql', function($include, &$columns, $table)
		{
			if (is_null($include))
			{
				$include = true;
			}

			// check if we are exporting the shortcodes
			if ($table === '#__vikbooking_wpshortcodes')
			{
				// avoid dumping the post ID column
				unset($columns['post_id'], $columns['tmp_post_id']);
			}

			return $include;
		}, 10, 3);
	}

	/**
	 * Implements the tools needed to manage the overrides
	 * without having to use a FTP client.
	 *
	 * @return 	void
	 *
	 * @since 	1.6.5
	 */
	public static function setupOverridesManager()
	{
		/**
		 * Trigger event to allow the plugins to include custom HTML within the view. 
		 * It is possible to return an associative array to group the HTML strings
		 * under different fieldsets. Plain/html string will be always pushed within
		 * the "custom" fieldset instead.
		 *
		 * Displays the overrides configuration.
		 *
		 * @param  mixed  $forms  The HTML to display.
		 * @param  mixed  $view   The current view instance.
		 *
		 * @since  1.6.5
		 */
		add_filter('vikbooking_display_view_config_global', function($forms, $view)
		{
			if (!$forms)
			{
				// init forms array
				$forms = [];
			}

			// render configuration layout
			$html = JLayoutHelper::render('html.overrides.config', [
				'view' => $view,
			]);

			// add fieldset to forms
			$forms[__('Page Overrides', 'vikbooking')] = $html;

			return $forms;
		}, 8, 2);

		/**
		 * Hook used to display a list of breaking changes after completing an update of the plugin.
		 * The message will be displayed only once within the dashboard of VikBooking.
		 *
		 * @since 1.6.5
		 */
		add_action('vikbooking_before_display_dashboard', array('VikBookingInstaller', 'showBreakingChanges'));
	}
}

<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.mvc.models.form');

/**
 * VikBooking plugin Overrides model.
 * @wponly
 *
 * @since 1.6.5
 * @see   JModel
 */
class VikBookingModelOverrides extends JModel
{
	/**
	 * A list containing all the back-end views to exclude.
	 *
	 * @var array
	 */
	public $_excludedAdminViews = ['getpro', 'gotopro', 'overrides'];

	/**
	 * A list containing all the front-end views to exclude.
	 *
	 * @var array
	 */
	public $_excludedSiteViews = [];

	/**
	 * A list containing all the widgets to exclude.
	 *
	 * @var array
	 */
	public $_excludedModules = [];

	/**
	 * A list containing all the layouts to exclude.
	 *
	 * @var array
	 */
	public $_excludedLayouts = [];

	/**
	 * Checks whether the specified file supports overrides.
	 *
	 * @param   mixed   $tree  Either the tree array or the client string.
	 * @param   string  $file  The file to look for.
	 *
	 * @return  mixed   The node found on success, false otherwise.
	 */
	public function isSupported($tree, string $file)
	{
		if (!is_array($tree))
		{
			// client given, generate tree
			$tree = $this->getTree($tree);
		}

		// look for a leaf
		if (isset($tree['folder']) && !$tree['folder'])
		{
			// leaf found, look for a match with the file path
			if ($file == $tree['path'] || $file == $tree['override'])
			{
				// match found, override supported
				return $tree;
			}
			else
			{
				// match not found, go ahead
				return false;
			}
		}

		// scan files if we are inside a node
		if (isset($tree['files']))
		{
			$tree = $tree['files'];
		}

		// iterate nodes
		foreach ($tree as $node)
		{
			// recursively check whether the node contains
			// a file matching the specified path
			if ($leaf = $this->isSupported($node, $file))
			{
				// leaf found, override supported
				return $leaf;
			}
		}

		// file not supported
		return false;
	}

	/**
	 * Creates the tree containing the available overrides for the specified client.
	 *
	 * @param   string  $client  The client to look for. Supports the following options:
	 *                           - administrator  back-end views;
	 *                           - site           front-end views;
	 *                           - layouts        admin, site and core layouts;
	 *                           - modules        plugin widgets.
	 *
	 * @return  array   The resulting tree.
	 */
	public function getTree(string $client)
	{
		// look for administrator/site views
		if (preg_match("/^(admin(?:istrator)?|site)$/i", $client))
		{
			// scan views overrides
			$tree = $this->getViewsTree($client);
		}
		else if (preg_match("/^layouts?$/i", $client))
		{
			// scan layouts overrides
			$tree = $this->getLayoutsTree();
		}
		else if (preg_match("/^(modules?|widgets?)$/i", $client))
		{
			// scan modules overrides
			$tree = $this->getModulesTree();
		}
		else
		{
			// client not supported, throw exception
			throw new Exception(sprintf('Override [%s] client not supported', $client), 500);
		}

		return $tree;
	}

	/**
	 * Creates the tree containing the available overrides for the admin/site views.
	 *
	 * @param   string  $client  The client to look for.
	 *
	 * @return  array   The resulting tree.
	 */
	protected function getViewsTree(string $client)
	{
		$tree = [];

		// build path according to the specified client
		if (preg_match("/^admin(?:istrator)?$/i", $client))
		{
			// admin path
			$path = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'views';

			// get list of excluded views
			$excluded = $this->_excludedAdminViews;

			// define client folder
			$clientFolder = 'admin';
		}
		else
		{
			// site path
			$path = VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'views';

			// get list of excluded views
			$excluded = $this->_excludedSiteViews;

			// define client folder
			$clientFolder = 'site';
		}

		// retrieve temporary uploads path
		$upload = wp_upload_dir();

		// create base path of overrides
		$overridesPath = JPath::clean($upload['basedir'] . '/vikbooking/overrides/' . $clientFolder . '/');

		// get all folders at the specified path
		$views = JFolder::folders($path, '.', $recursive = false, $fullPath = true);

		// check whether the plugin supports specific views only for the WordPress platform
		if (JFolder::exists(VIKBOOKING_LIBRARIES . '/mvc/' . $clientFolder . '/views'))
		{
			$views = array_merge($views, JFolder::folders(VIKBOOKING_LIBRARIES . '/mvc/' . $clientFolder . '/views', '.', $recursive = false, $fullPath = true));
		}

		// iterate views and extract all the supported templates
		foreach ($views as $view)
		{
			$viewName = basename($view);

			// make sure the view should not be excluded
			if (!$this->isExcluded($viewName, $excluded))
			{
				// create node to inject within the tree
				$node = [
					'name'   => $viewName,
					'path'   => $view,
					'folder' => true,
					'files'  => [],
				];

				// build path containing the view templates
				$tmpl = JPath::clean($view . '/tmpl');

				// scan files within the view
				$files = JFolder::files($tmpl, '\.php$', $recursive = false, $fullPath = true);

				// iterate files
				foreach ($files as $file)
				{
					$filename = basename($file);

					// create override path
					$fileOverridePath = $overridesPath . $viewName . DIRECTORY_SEPARATOR . $filename;

					$published = false;

					// look for an existing override
					if (JFile::exists($fileOverridePath))
					{
						// the file owns an override
						$override = $published = true;
					}
					else
					{
						// look for an unpublished override
						$unpublishedPath = $overridesPath . $viewName . DIRECTORY_SEPARATOR . '__' . $filename;

						// check whether the file exists
						if (JFile::exists($unpublishedPath))
						{
							// the file owns an unpublished override
							$override = true;
						}
						else
						{
							// missing override
							$override = false;
						}
					}

					// register node within the parent
					$node['files'][] = [
						'name'      => $filename,
						'path'      => $file,
						'override'  => $fileOverridePath,
						'folder'    => false,
						'has'       => $override,
						'published' => $published,
					];
				}

				// append node to the tree
				$tree[] = $node;
			}
		}

		// sort folders by ascending name
		usort($tree, function($a, $b)
		{
			return strcasecmp($a['name'], $b['name']);
		});

		return $tree;
	}

	/**
	 * Creates the tree containing the available overrides for the layouts.
	 * The first level will always contain these nodes: admin, site, core.
	 *
	 * @return  array  The resulting tree.
	 */
	protected function getLayoutsTree()
	{
		$tree = [];

		// administrator layouts
		$tree[] = [
			'name'   => 'admin',
			'path'   => VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'layouts',
			'folder' => true,
			'files'  => [],
		];

		// site layouts
		// $tree[] = [
		// 	'name'   => 'site',
		// 	'path'   => VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'layouts',
		// 	'folder' => true,
		// 	'files'  => [],
		// ];

		// system layouts
		$tree[] = [
			'name'   => 'core',
			'path'   => VIKBOOKING_LIBRARIES . DIRECTORY_SEPARATOR . 'html',
			'folder' => true,
			'files'  => [],
		];

		// retrieve temporary uploads path
		$upload = wp_upload_dir();

		// create base path of overrides
		$overridesPath = JPath::clean($upload['basedir'] . '/vikbooking/layouts/');

		// iterate main nodes
		foreach ($tree as &$node)
		{
			if ($node['name'] == 'site')
			{
				$op = $overridesPath . 'site' . DIRECTORY_SEPARATOR;
			}
			else
			{
				$op = $overridesPath . 'admin' . DIRECTORY_SEPARATOR;	

				if ($node['name'] == 'core')
				{
					// append HTML folder in case of core client
					$op .= 'html' . DIRECTORY_SEPARATOR;
				}
			}

			// scan and construct node recursively
			$this->_scanNode($node, $op);
		}

		return $tree;
	}

	/**
	 * Creates the tree containing the available overrides for the plugin widgets.
	 *
	 * @return 	array   The resulting tree.
	 */
	protected function getModulesTree()
	{
		$tree = [];

		// retrieve temporary uploads path
		$upload = wp_upload_dir();

		// create base path of overrides
		$overridesPath = JPath::clean($upload['basedir'] . '/vikbooking/overrides/modules/');

		// get all folders at the specified path
		$modules = JFolder::folders(VIKBOOKING_BASE . '/modules', '.', $recursive = false, $fullPath = true);

		// iterate modules and extract all the supported templates
		foreach ($modules as $module)
		{
			$modName = basename($module);

			// make sure the module should not be excluded
			if (!$this->isExcluded($modName, $this->_excludedModules))
			{
				// create node to inject within the tree
				$node = [
					'name'   => preg_replace("/^mod_vikbooking_/i", '', $modName),
					'path'   => $module,
					'folder' => true,
					'files'  => [],
				];

				// build path containing the module templates
				$tmpl = JPath::clean($module . '/tmpl');

				// scan files within the module
				$files = JFolder::files($tmpl, '\.php$', $recursive = false, $fullPath = true);

				// iterate files
				foreach ($files as $file)
				{
					$filename = basename($file);

					// create override path
					$fileOverridePath = $overridesPath . $modName . DIRECTORY_SEPARATOR . $filename;

					$published = false;

					// look for an existing override
					if (JFile::exists($fileOverridePath))
					{
						// the file owns an override
						$override = $published = true;
					}
					else
					{
						// look for an unpublished override
						$unpublishedPath = $overridesPath . $modName . DIRECTORY_SEPARATOR . '__' . $filename;

						// check whether the file exists
						if (JFile::exists($unpublishedPath))
						{
							// the file owns an unpublished override
							$override = true;
						}
						else
						{
							// missing override
							$override = false;
						}
					}

					// register node within the parent
					$node['files'][] = [
						'name'      => $filename,
						'path'      => $file,
						'override'  => $fileOverridePath,
						'folder'    => false,
						'has'       => $override,
						'published' => $published,
					];
				}

				// append node to the tree
				$tree[] = $node;
			}
		}

		return $tree;
	}

	/**
	 * Recursive function used to scan the folder and files within the specified node.
	 *
	 * @param   array   &$node  The node to scan.
	 * @param   string  $base   The base path in which the overrides should locate.
	 *
	 * @return  void
	 */
	protected function _scanNode(array &$node, string $base)
	{
		// get all folders at the node path
		$folders = JFolder::folders($node['path'], '.', $recursive = false, $fullPath = true);

		// get all files at the node path
		$files = JFolder::files($node['path'], '\.php$', $recursive = false, $fullPath = true);

		// iterate folders
		foreach ($folders as $folder)
		{
			$folderName = basename($folder);

			// create folder node
			$tmp = [
				'name'   => basename($folder),
				'path'   => $folder,
				'folder' => true,
				'files'  => [],
			];

			// folder recursive scan 
			$this->_scanNode($tmp, $base . $folderName . DIRECTORY_SEPARATOR);

			// append folder to main node
			$node['files'][] = $tmp;
		}

		// iterate files
		foreach ($files as $file)
		{
			// make sure the file should not be excluded
			if (!$this->isExcluded($file, $this->_excludedLayouts))
			{
				$filename = basename($file);

				// create override path
				$fileOverridePath = $base . $filename;

				$published = false;

				// look for an existing override
				if (JFile::exists($fileOverridePath))
				{
					// the file owns an override
					$override = $published = true;
				}
				else
				{
					// look for an unpublished override
					$unpublishedPath = $base . DIRECTORY_SEPARATOR . '__' . $filename;

					// check whether the file exists
					if (JFile::exists($unpublishedPath))
					{
						// the file owns an unpublished override
						$override = true;
					}
					else
					{
						// missing override
						$override = false;
					}
				}

				// create file node
				$tmp = [
					'name'      => $filename,
					'path'      => $file,
					'override'  => $fileOverridePath,
					'folder'    => false,
					'has'       => $override,
					'published' => $published,
				];

				// append folder to main node
				$node['files'][] = $tmp;
			}
		}
	}

	/**
	 * Check whether the specified path matches one of
	 * the specified regex.
	 *
	 * @param   string   $path  The path to look for.
	 * @param   array    $pool  An array of regex.
	 *
	 * @return  bool     True if excluded, false otherwise.
	 */
	protected function isExcluded(string $path, array $pool)
	{
		$path = preg_replace("/[\\\\]/", '/', $path);

		// iterate pool
		foreach ($pool as $match)
		{
			// exec regex
			if (preg_match("/$match/i", $path))
			{
				// inside the pool, exclude file
				return true;
			}
		}

		// can include file
		return false;
	}

	/**
	 * Returns a lookup containing all the existing (and published) overrides,
	 * categorized by override group (admin, site, layouts, modules).
	 * 
	 * Only the files that are an actual override of the core files will be taken here.
	 * In example, default_foo_bar.php is not part of VikBooking and therefore it
	 * will be discarded.
	 * 
	 * @return  array
	 */
	public function getAllOverrides()
	{
		// retrieve temporary uploads path
		$upload = wp_upload_dir();

		// fetch all the files created on each section
		$lookup = [
			'admin'   => JFolder::files($upload['basedir'] . '/vikbooking/overrides/admin/', '\.php$', true, true),
			'site'    => JFolder::files($upload['basedir'] . '/vikbooking/overrides/site/', '\.php$', true, true),
			'layouts' => JFolder::files($upload['basedir'] . '/vikbooking/layouts/', '\.php$', true, true),
			'modules' => JFolder::files($upload['basedir'] . '/vikbooking/overrides/modules/', '\.php$', true, true),
		];

		// exclude all the overrides that are actually unpublished
		foreach ($lookup as &$files)
		{
			if (!is_array($files))
			{
				$files = [];
			}

			$files = array_values(array_filter($files, function($file)
			{
				// the override file name must not start with 2 underscores
				return strpos(basename($file), '__') !== 0;
			}));
		}

		// get rid of the sections without files
		return array_filter($lookup);
	}
}

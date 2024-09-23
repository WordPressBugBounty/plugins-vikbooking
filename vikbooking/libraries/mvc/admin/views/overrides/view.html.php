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

/**
 * VikBooking Overrides view.
 * @wponly
 *
 * @since 1.6.5
 */
class VikBookingViewOverrides extends JView
{
	/**
	 * @override
	 * View display method.
	 *
	 * @return 	void
	 */
	public function display($tpl = null)
	{
		$input = JFactory::getApplication()->input;
		$user  = JFactory::getUser();

		if (!$user->authorise('core.admin', 'com_vikbooking'))
		{
			wp_die(
				'<h1>' . JText::translate('FATAL_ERROR') . '</h1>' .
				'<p>' . JText::translate('RESOURCE_AUTH_ERROR') . '</p>',
				403
			);
		}

		// load filters from request
		$filters = [];
		$filters['client']   = $input->get('client', 'site', 'string');
		$filters['status']   = $input->get('status', '', 'string');
		$filters['file']     = $input->get('selectedfile', '', 'base64');
		$filters['override'] = $input->get('overridefile', '', 'base64');

		$filters['hasoverride'] = false;
		$filters['published']   = false;

		// get view model
		$model = $this->getModel();

		// scan overrides tree
		$this->tree = $model->getTree($filters['client']);

		// check whether a file was specified
		if ($filters['file'])
		{
			// decode file path
			$filters['path']     = base64_decode($filters['file']);
			$filters['override'] = base64_decode($filters['override']);
		}
		else if ($filters['override'])
		{
			// override provided, attempt to fetch the path of the original file
			$filters['override'] = base64_decode($filters['override']);

			// search node with matching override file
			$node = $model->isSupported($this->tree, $filters['override']);

			if ($node)
			{
				// node found
				$filters['file'] = base64_encode($node['path']);
				$filters['path'] = $node['path'];
			}
			else
			{
				// node not found
				$filters['path']     = false;
				$filters['override'] = '';
			}
		}
		else
		{
			// no selected file
			$filters['path'] = false;
		}

		// check if we should filter the tree according to the specified status
		if ($filters['status'] !== '')
		{
			// apply filters
			foreach ($this->tree as $i => &$node)
			{
				// apply filters to the first level nodes
				if (!$this->filterOverrides($this->tree[$i], (int) $filters['status']))
				{
					// detach node as there are no matching overrides
					unset($this->tree[$i]);
				}
			}
		}

		// in case of selected file, make sure it supports overrides
		if ($filters['path'])
		{
			// invoke model check
			$supported = $model->isSupported($this->tree, $filters['path']);

			if ($supported)
			{
				// read default file contents
				$filters['defaultcode'] = file_get_contents($filters['path']);

				// override supported, check if already exists
				if (JFile::exists($filters['override']))
				{
					// existing override, read contents
					$filters['code'] = file_get_contents($filters['override']);

					$filters['hasoverride'] = true;

					$filters['published'] = true;
				}
				else
				{
					// override is missing, look for an unpublished override
					$_path = JPath::clean(dirname($filters['override']) . '/__' . basename($filters['override']));

					if (JFile::exists($_path))
					{
						// existing unpublished override, read contents
						$filters['code'] = file_get_contents($_path);

						$filters['hasoverride'] = true;
					}
					else
					{
						// no override, use default code
						$filters['code'] = $filters['defaultcode'];
					}
				}

				/**
				 * Adjust the comments describing the layout location
				 * to the wordpress structure.
				 */
				$filters['code'] = preg_replace_callback(
					"/\*\s*(\/administrator)?\/?components\/com_vikbooking(.*?)\R/i",
					function($match)
					{
						return '* /wp-content/plugins/vikbooking/'
							. ($match[1] ? 'admin' : 'site')
							. $match[2] . "\n";
					},
					$filters['code']
				);
			}
			else
			{
				// the selected file doesn't support overrides, unset selection
				$filters['file']     = '';
				$filters['override'] = '';
				$filters['path']     = false;
			}
		}

		// register filters
		$this->filters = $filters;

		// set up toolbar
		$this->addToolbar();
		
		// display parent
		parent::display($tpl);
	}

	/**
	 * Helper method to setup the toolbar.
	 *
	 * @return 	void
	 */
	public function addToolbar()
	{
		JToolbarHelper::title(__('VikBooking - Page Overrides', 'vikbooking'));

		// back to the configuration
		JToolbarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_vikbooking&view=config');

		// check if we have a selected file
		if ($this->filters['path'])
		{
			// add save button
			JToolbarHelper::apply('override.save');
		}
	}

	/**
	 * Helper function used to build a navigator node.
	 *
	 * @param 	array   $node  The tree node.
	 *
	 * @return 	string  The HTML to display.
	 */
	protected function buildNode($node)
	{
		// in case the selected path contains the current node, mark the node as selected
		if ($this->filters['path'] && strpos($this->filters['path'], $node['path'] . '/') === 0)
		{
			$node['selected'] = true;
		}
		else
		{
			$node['selected'] = false;
		}

		// set up current node
		$this->currentNode = $node;

		if ($node['folder'])
		{
			// build folder link
			$html = $this->loadTemplate('folder');
		}
		else
		{
			// build file link
			$html = $this->loadTemplate('file');
		}

		// check whether the node is a leaf
		if (!empty($node['files']))
		{
			// fetch node children visibility
			$style = $node['selected'] ? '' : ' style="display:none;"';

			// create children list
			$html .= '<ul' . $style . '>';

			// iterate children
			foreach ($node['files'] as $file)
			{
				// create child node
				$html .= '<li>';
				// build child HTML
				$html .= $this->buildNode($file);
				// close child node
				$html .= '</li>';
			}

			// close children list
			$html .= '</ul>';
		}

		return $html;
	}

	/**
	 * Filter the available pages/widgets/layouts according to the specified status.
	 * 
	 * @param   array  &$node   The node holding the available files.
	 * @param   int    $status  The filter status (1: active only, 0: inactive only).
	 * 
	 * @return  void
	 */
	protected function filterOverrides(array &$node, int $status)
	{
		// scan all the files of the node
		foreach ($node['files'] as $k => &$file)
		{
			// check whether we have a sub-level node
			if (isset($file['files']))
			{
				// recursively filter the overrides under this sub-level
				if (!$this->filterOverrides($file, $status))
				{
					// retach the sub-level in case it has no overrides
					unset($node['files'][$k]);
				}
			}
			// or if we have a leaf (file override)
			else
			{
				// make sure the file has an override and it matches the provided status
				if (!$file['has'] || $file['published'] != $status)
				{
					// not compliant, detach file override
					unset($node['files'][$k]);
				}
			}
		}

		if (count($node['files']) === 0)
		{
			// return false in case the node should be detached from the tree
			return false;
		}

		// the node contains at least a matching override
		return true;
	}
}

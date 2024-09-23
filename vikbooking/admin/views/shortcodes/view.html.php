<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2023 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

jimport('joomla.application.component.view');
jimport('adapter.acl.access');

/**
 * VikBooking Shortcodes view.
 * @wponly
 *
 * @since 1.0
 */
class VikBookingViewShortcodes extends JView
{
	/**
	 * @override
	 * View display method.
	 *
	 * @return 	void
	 */
	public function display($tpl = null)
	{
		$app   = JFactory::getApplication();
		$user  = JFactory::getUser();

		if (!$user->authorise('core.admin', 'com_vikbooking'))
		{
			wp_die(
				'<h1>' . JText::translate('FATAL_ERROR') . '</h1>' .
				'<p>' . JText::translate('RESOURCE_AUTH_ERROR') . '</p>',
				403
			);
		}

		$this->returnLink = $app->input->getBase64('return', '');

		// get filters
		$filters = array();
		$filters['search'] = $app->getUserStateFromRequest('shortcode.filters.search', 'filter_search', '', 'string');
		$filters['lang']   = $app->getUserStateFromRequest('shortcode.filters.lang', 'filter_lang', '*', 'string');
		$filters['type']   = $app->getUserStateFromRequest('shortcode.filters.type', 'filter_type', '', 'string');

		$this->filters = $filters;

		// get shortcodes

		$this->limit  = $app->getUserStateFromRequest('shortcodes.limit', 'limit', $app->get('list_limit'), 'uint');
		$this->offset = $app->input->getUint('limitstart', 0);
		$this->navbut = '';

		$this->shortcodes = $this->hierarchicalShortcodes();

		JLoader::import('adapter.filesystem.folder');

		$this->views = array();

		// get all the views that contain a default.xml file
		// [0] : base path
		// [1] : query
		// [2] : true for recursive search
		// [3] : true to return full paths
		$files = JFolder::files(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'views', 'default.xml', true, true);

		foreach ($files as $f)
		{
			// retrieve the view ID from the path: /views/[ID]/tmpl/default.xml
			if (preg_match("/[\/\\\\]views[\/\\\\](.*?)[\/\\\\]tmpl[\/\\\\]default\.xml$/i", $f, $matches))
			{
				$id = $matches[1];
				// load the XML form
				$form = JForm::getInstance($id, $f);
				// get the view title
				$this->views[$id] = (string) $form->getXml()->layout->attributes()->title;
			}
		}

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
		JToolbarHelper::title(JText::translate('VBOSHORTCDSMENUTITLE'));

		JToolbarHelper::addNew('shortcodes.create');
		JToolbarHelper::editList('shortcodes.edit');
		JToolbarHelper::deleteList(JText::translate('VBDELCONFIRM'), 'shortcodes.delete');
		JToolbarHelper::cancel('shortcodes.back');
	}

	/**
	 * Retrieves the shortcodes by using a hierarchical ordering.
	 * 
	 * @return 	array  An array of shortcodes.
	 * 
	 * @since 	1.5
	 */
	protected function hierarchicalShortcodes()
	{
		$dbo = JFactory::getDbo();

		// loads all the existing shortcodes
		$q = $dbo->getQuery(true)
			->select('SQL_CALC_FOUND_ROWS *')
			->from($dbo->qn('#__vikbooking_wpshortcodes'));

		/**
		 * Filters the shortcodes by using the requested values.
		 *
		 * @since 1.1.5
		 */

		if ($this->filters['search'])
		{
			$q->where($dbo->qn('name') . ' LIKE ' . $dbo->q("%{$this->filters['search']}%"));
		}

		if ($this->filters['lang'] != '*')
		{
			$q->where($dbo->qn('lang') . ' = ' . $dbo->q($this->filters['lang']));
		}

		if ($this->filters['type'])
		{
			$q->where($dbo->qn('type') . ' = ' . $dbo->q($this->filters['type']));
		}

		$dbo->setQuery($q);
		$dbo->execute();

		if (!$dbo->getNumRows())
		{
			return [];
		}

		$model = JModel::getInstance('vikbooking', 'shortcode', 'admin');

		$shortcodes = [];

		foreach ($dbo->loadObjectList() as $shortcode)
		{
			// load shortcode ancestors
			$shortcode->ancestors = $model->getAncestors($shortcode);

			// create ordering leverage, based on version comparison
			$tmp = array_merge([$shortcode->id], $shortcode->ancestors);
			$shortcode->leverage = implode('.', array_reverse($tmp));

			$shortcodes[] = $shortcode;
		}

		// sort shortcodes by comparing the evaluated leverage
		usort($shortcodes, function($a, $b)
		{
			return version_compare($a->leverage, $b->leverage);
		});

		// create pagination
		jimport('joomla.html.pagination');
		$pageNav = new JPagination(count($shortcodes), $this->offset, $this->limit);
		$this->navbut = '<table align="center"><tr><td>' . $pageNav->getListFooter() . '</td></tr></table>';

		// take only the records that metch the pagination query
		$shortcodes = array_splice($shortcodes, $this->offset, $this->limit);

		return $shortcodes;
	}
}

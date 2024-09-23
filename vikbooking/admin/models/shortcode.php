<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      E4J srl
 * @copyright   Copyright (C) e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.mvc.models.form');

/**
 * VikBooking plugin Shortcode model.
 *
 * @since 	1.0
 * @see 	JModelForm
 */
class VikBookingModelShortcode extends JModelForm
{
	/**
	 * @override
	 * This method should be used to pre-load an item considering
	 * the data set in the request.
	 * 
	 * For example, if the request owns an ID, this method may try 
	 * to retrieve the item from the database.
	 * Otherwise it may return an empty object.
	 *
	 * @return 	object  The object found.
	 */
	public function loadFormData()
	{
		$input = JFactory::getApplication()->input;

		$id = $input->getUint('cid', array(0));

		return $this->getItem(array_shift($id));
	}

	/**
	 * This method should be used to retrieve the posted data
	 * after the form submission.
	 *
	 * @return 	object  The data object.
	 */
	public function getFormData()
	{
		$input = JFactory::getApplication()->input;

		// get data from request
		$data = new stdClass;

		$data->id 		 = $input->getInt('id', 0);
		$data->title 	 = '';
		$data->name  	 = $input->getString('name');
		$data->type 	 = $input->getString('type');
		$data->lang 	 = $input->getString('lang');
		$data->parent_id = $input->getUint('parent_id', 0);
		$data->json  	 = array();
		$data->shortcode = '';

		// only if we are creating the shortcode, set the creation date and user
		if ($data->id <= 0)
		{
			$data->createdby = JFactory::getUser()->id;
			$data->createdon = JFactory::getDate()->toSql();
		}

		// get layout path
		$path = implode(DIRECTORY_SEPARATOR, array(VBO_SITE_PATH, 'views', $data->type, 'tmpl', 'default.xml'));

		// if the file doesn't exist, raise an exception
		if (!is_file($path))
		{
			throw new Exception("Missing XML [{$data->type}] view type.", 404);
		}

		// load XML form
		$form = JForm::getInstance($data->type, $path);

		// obtain view title
		$data->title = (string) $form->getXml()->layout->attributes()->title;

		// get form data
		$formData = $input->get('jform', [], 'array');

		// get input filter
		$inputFilter = JInputFilter::getInstance();

		// iterate the layout fields
		foreach ($form->getFields() as $field)
		{
			$attrs = $field->attributes();

			$name 	= (string) $attrs->name;
			$filter = (string) $attrs->filter;
			$req 	= (string) $attrs->required;

			// use string if no filter
			if (empty($filter))
			{
				$filter = 'string';
			}

			if (isset($formData[$name]))
			{
				// clean filter in request
				$value = $inputFilter->clean($formData[$name], $filter);
			}
			else
			{
				// use NULL
				$value = null;
			}

			// raise an exception if a mandatory field is empty
			if (empty($value) && $req == 'true')
			{
				throw new Exception("Missing required [$name] field.", 400);
			}

			// save value only if not NULL
			if ($value !== null)
			{
				$data->json[$name] = $value;
			}
		}

		$viewData = array();
		$viewData['view'] = $data->type;
		$viewData['lang'] = $data->lang;

		// merge VIEW name and LANG TAG with JSON params 
		$args = array_merge($data->json, $viewData);
		// generate shortcode string
		$data->shortcode = JFilterOutput::shortcode('vikbooking', $args);

		// finally encode the params in JSON
		$data->json = json_encode($data->json);

		return $data;
	}

	/**
	 * @override
	 * Retrieves the specified item.
	 *
	 * @param 	mixed 	 $pk 	  The primary key value or a list of keys.
	 * @param 	boolean  $create  True to create an empty object.
	 *
	 * @return 	object 	 The item found if exists, otherwise an empty object.
	 */
	public function getItem($pk, $create = true)
	{
		$item = parent::getItem($pk, $create);

		if ($item && !$item->id)
		{
			$item->name = '';
			$item->type = '';
			$item->json = '{}';
		}

		return $item;
	}

	/**
	 * @override
	 * Creates or updates the specified record.
	 *
	 * @param 	object 	 &$data  The record to insert.
	 *
	 * @return 	boolean  True if the record has been inserted/updated, otherwise false.
	 */
	public function save(&$data)
	{
		// get old item to get previous shortcode
		$old = $this->getItem($data->id);

		// save shortcode
		$res = parent::save($data);

		if ($res && $old)
		{
			// get saved item to access post ID property
			$item = $this->getItem($data->id);

			/**
			 * Get the post object.
			 * Obtain post only in case the shortcode is assigned
			 * to a real ID, otherwise get_post() could retrieve
			 * the last post used.
			 *
			 * @since 1.2.7
			 */
			$post = $item->post_id ? get_post($item->post_id) : null;

			/**
			 * Proceed only in case the post exists.
			 *
			 * @since 1.1.7
			 */
			if ($post)
			{
				/**
				 * Do not proceed in case the post already contains the shortcode.
				 * Otherwise we would fall in a loop as wp_update_post() triggers
				 * the action that invoked the current method.
				 *
				 * @see 	vikbooking.php @ action:save_post
				 *
				 * @since 	1.0.17
				 */
				if (strpos($post->post_content, $item->shortcode) === false)
				{
					// replace old shortcode with the new one from the post contents
					$post->post_content = str_replace($old->shortcode, $item->shortcode, $post->post_content);

					// finalize the update
					wp_update_post($post);
				}
			}
		}

		return $res;
	}

	/**
	 * Obtains the JForm object related to the model view.
	 *
	 * @param 	object 	$item 	The data to bind.
	 *
	 * @return 	JForm 	The form object.
	 */
	public function getTypeForm($item)
	{
		$item = (object) $item;

		if (!$item->type)
		{
			return null;
		}

		// inject custom vars to access the layout
		// file of a site view (change model name and client)
		$this->_name   = $item->type;
		$this->_client = 'site';

		// get the form
		$form = parent::getForm();

		$form->setFormControl('jform');

		// reset the vars (it will be taken later if needed)
		$this->_name   = null;
		$this->_client = null;

		return $form;
	}

	/**
	 * Retrieves the IDs of the ancestors of a shortcode.
	 *
	 * @param 	mixed  $shortcode  Either a shortcode ID or an object.
	 * 
	 * @return 	array  Array of ancestor IDs or empty array if there are none.
	 * 
	 * @since 	1.5
	 */
	public function getAncestors($shortcode)
	{
		if (is_array($shortcode))
		{
			// treat associative array as object
			$shortcode = (object) $shortcode;
		}
		else if (!is_object($shortcode))
		{
			// fetch shortcode details from ID
			$shortcode = $this->getItem($shortcode);
		}

		$ancestors = [];

		if (!$shortcode || empty($shortcode->parent_id) || $shortcode->parent_id == $shortcode->id)
		{
			return $ancestors;
		}

		$id          = $shortcode->parent_id;
		$ancestors[] = $id;

		while ($ancestor = $this->getItem($id))
		{
			// Loop detection: If the ancestor has been seen before, break
			if (empty($ancestor->parent_id) || ($ancestor->parent_id == $shortcode->id ) || in_array($ancestor->parent_id, $ancestors, true))
			{
				break;
			}

			$id          = $ancestor->parent_id;
			$ancestors[] = $id;
		}

		return $ancestors;
	}

	/**
	 * Creates a page on WordPress with for each requested shortcode.
	 * This is useful to automatically link Shortcodes in pages with no manual actions.
	 * 
	 * @param 	mixed    $ids  Either an array or a shortcode ID.
	 * 
	 * @return 	boolean  True on success, false otherwise.
	 * 
	 * @since 	1.5
	 */
	public function addPage($ids)
	{
		$ids = (array) $ids;

		$success = false;

		foreach ($ids as $id)
		{
			// get shortcode record
			$item = $this->getItem($id);

			if (empty($item->id))
			{
				// missing shortcode
				$this->setError(sprintf('Shortcode [%d] not found', $id));

				continue;
			}

			// make sure the shortcode hasn't been assigned to any pages
			if (empty($item->post_id))
			{
				$post_parent = 0;

				if ($item->parent_id)
				{
					// get parent shortcode
					$parent = $this->getItem($item->parent_id);

					if ($parent && $parent->post_id)
					{
						// in case the parent shortcode is assigned to a post, use it as parent
						$post_parent = $parent->post_id;
					}
				}

				// Add a new page (we allow a WP_ERROR to be thrown in case of failure).
				// This should automatically trigger the hook that we use to link the shortcode 
				// to the new page/post ID, and so there's no need to update the item.
				$post_id = wp_insert_post(array(
					'post_title'   => (!empty($item->name) ? $item->name : JText::translate($item->title)),
					'post_content' => $item->shortcode,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_parent'  => $post_parent,
				), $wp_error = false, $fire_after_hooks = true);

				// check if we received an error
				if ($post_id instanceof WP_Error)
				{
					// propagate error message
					$this->setError($post_id);

					continue;
				}

				// check whether the page has been created
				$success = $success || (is_int($post_id) && $post_id > 0);
			}
		}

		return $success;
	}

	/**
	 * Method to get a table object.
	 *
	 * @param   string  $name     The table name.
	 * @param   string  $prefix   The class prefix.
	 * @param   array   $options  Configuration array for table.
	 *
	 * @return  JTable  A table object.
	 *
	 * @since   1.4.7
	 */
	public function getTable($name = '', $prefix = 'JTable', $options = array())
	{
		if (!$name)
		{
			$name 	= 'shortcode';
			$prefix = 'VBOTable';
		}

		return parent::getTable($name, $prefix, $options);
	}
}

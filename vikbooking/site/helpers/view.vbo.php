<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * This class implements helpful methods for view instances.
 * JViewBaseVikBooking is a placeholder used to support both JView and JViewLegacy.
 *
 * @since 1.2.0
 */
class JViewVikBooking extends JViewBaseVikBooking
{
	/**
	 * The current signature of the filters.
	 *
	 * @var array
	 */
	protected $signatureId = '';

	/**
	 * This method returns the correct limit start to use.
	 * In case the filters changes, the limit is always reset.
	 *
	 * @param 	array 	 $args 	The filters associative array.
	 * @param 	mixed 	 $id 	An optional value used to restrict
	 * 							the states only to a specific ID/page.
	 *
	 * @return 	integer  The list start limit.
	 *
	 * @uses 	getPoolName()
	 * @uses 	registerSignature()
	 * @uses 	checkSignature()
	 * @uses 	resetLimit()
	 */
	protected function getListLimitStart(array $args, $id = null)
	{
		$app = JFactory::getApplication();

		// calculate pool name
		$name = $this->getPoolName($id);

		// get list limit
		$start = $app->getUserStateFromRequest($name . '.limitstart', 'limitstart', 0, 'uint');

		// register new filters signature
		$this->registerSignature($args, $id);

		if ($start > 0 && !$this->checkSignature($id))
		{
			// filters are changed, reset limit
			$this->resetLimit($start, $id);
		}

		return $start;
	}

	/**
	 * Calculates the signature of the given filters and register it in the user state.
	 *
	 * @param 	array 	$args 	The filters associative array.
	 * @param 	mixed 	$id 	An optional value used to restrict
	 * 							the states only to a specific ID/page.
	 *
	 * @return 	string 	The old signature.
	 *
	 * @uses 	getPoolName()
	 */
	protected function registerSignature(array $args, $id = null)
	{
		$app = JFactory::getApplication();

		// calculate new signature
		$sign = array();
		
		foreach ($args as $k => $v)
		{
			if (strlen($v))
			{
				$sign[$k] = $v;
			}
		}

		$sign = $sign ? serialize($sign) : '';

		// calculate signature name
		$name = $this->getPoolName($id);

		// get old signature because `setUserState` owns a bug for returning the old state
		$this->signatureId = $app->getUserState($name . '.signature', '');

		// register new signature
		$app->setUserState($name . '.signature', $sign);

		// return old signature
		return $this->signatureId;
	}

	/**
	 * Checks if the new signature matches the previous one.
	 *
	 * @param 	mixed 	 $id 	 An optional value used to restrict
	 * 					 		 the states only to a specific ID/page.
	 * @param 	string 	 $token  The token to check against the new one.
	 * 					  		 If not provided, the internal one will be used.
	 *
	 * @return 	boolean  True if the tokens are equal.
	 *
	 * @uses 	getPoolName()
	 */
	protected function checkSignature($id = null, $token = null)
	{
		if (!$token)
		{
			// use property in case the argument is empty
			$token = $this->signatureId;
		}

		// calculate signature name
		$name = $this->getPoolName($id);

		// get current signature
		$sign = JFactory::getApplication()->getUserState($name . '.signature', '');

		// check if the 2 signatures are equal
		return !strcasecmp($sign, $token);
	}
	
	/**
	 * Resets the list limit and save it in the user state.
	 *
	 * @param 	integer  &$start  The start list limit.
	 * @param 	mixed 	 $id 	  An optional value used to restrict
	 * 					 		  the states only to a specific ID/page.
	 *
	 * @return 	void
	 *
	 * @uses 	getPoolName();
	 */
	protected function resetLimit(&$start, $id = null)
	{
		// limit start passed by reference, reset it
		$start = 0;

		// calculate limit name
		$name = $this->getPoolName($id);

		// register the new limit within the user state
		JFactory::getApplication()->setUserState($name . '.limitstart', $start);
	}

	/**
	 * Returns the pool base name in which is stored the user state.
	 *
	 * @param 	mixed 	$id  An optional value used to restrict
	 * 						 the states only to a specific ID/page.
	 *
	 * @return 	string 	The pool name.
	 */
	protected function getPoolName($id = null)
	{
		// calculate pool name
		$name = $this->getName();

		if (!is_null($id))
		{
			// access the user state of a specific ID/page
			$name .= "[$id]";
		}

		return $name;
	}

	/**
	 * Validates the list query to ensure that the specified limit
	 * doesn't exceed the total number of records. This might happen
	 * while erasing all the records from the last page.
	 *
	 * The query is always retrieved from the database object and
	 * must be invoked only once it has been set and executed.
	 *
	 * @param 	mixed 	&$offset  The offset to use.
	 * @param 	mixed 	&$limit   The limit to use.
	 * @param 	mixed 	$id       An optional value used to restrict
	 * 						      the states only to a specific ID/page.
	 *
	 * @return 	void
	 *
	 * @uses 	getPoolName()
	 */
	protected function assertListQuery(&$offset, &$limit, $id = null)
	{
		$dbo = JFactory::getDbo();

		// retrieve current query
		$query = $dbo->getQuery();

		if (!$offset || $dbo->getNumRows())
		{
			// we don't need to proceed as we are already fetching the first page
			// or we found at least one record
			return;
		}

		// No record found on the page we are (not the first one)!
		// Try shifting by the offset found.
		$limit  = $limit ? $limit : 20;
		$offset = max(array(0, $offset - (int) $limit));

		// execute query again with updated limit
		$dbo->setQuery($query, $offset, $limit);
		$dbo->execute();

		if (!$dbo->getNumRows())
		{
			$offset = 0;

			// check if we are handling a limitable query object
			if (interface_exists('JDatabaseQueryLimitable') && $query instanceof JDatabaseQueryLimitable)
			{
				// Update limit on query builder too because database might ignore it when offset 
				// is equals to 0. Note that offset and limit are specified in the opposite way.
				$query->setLimit($limit, $offset);
			}

			// Still no rows found! Reset to the first page.
			$dbo->setQuery($query, $offset, $limit);
			$dbo->execute();
		}

		// calculate limit name
		$name = $this->getPoolName($id);

		// register the new limit within the user state
		JFactory::getApplication()->setUserState($name . '.limitstart', $offset);
	}

	/**
	 * Creates an event that triggers when displaying a view.
	 * This is useful to include custom HTML in specific positions
	 * of the current view.
	 *
	 * Any specified arguments will be used when triggering the event.
	 *
	 * @param 	string  $suffix  An optional suffix to use for the event.
	 *
	 * @return 	array 	An array of forms.
	 *
	 * @since 	1.5.10
	 */
	protected function onDisplayView()
	{
		$events = array();

		// get all specified arguments
		$args = func_get_args();
		// extract suffix from arguments
		$suffix = array_shift($args);

		// create event name based on the view name (e.g. onDisplayViewVikBookingManagereservation)
		$events[] = 'onDisplayViewVikBooking' . ucfirst($this->getName()) . (string) $suffix;
		// use also a different alias by trimming the initial "manage", "edit", "new" strings
		// from the view name, such as "onDisplayViewVikBookingReservation"
		$events[] = 'onDisplayViewVikBooking' . ucfirst(preg_replace("/^(manage|new|edit)/i", '', $this->getName())) . (string) $suffix;

		$app = JFactory::getApplication();

		JPluginHelper::importPlugin('e4j');

		// merge default arguments with the given ones
		$args = array_merge(
			array($this),
			$args
		);

		$forms = array();

		// iterate events and make sure the same event name is not going to be used twice
		foreach (array_unique($events) as $event)
		{
			/**
			 * Trigger event to allow the plugins to include custom HTML within the view. 
			 * It is possible to return an associative array to group the HTML strings
			 * under different fieldsets. Plain/html string will be always pushed within
			 * the "custom" fieldset instead.
			 *
			 * @param 	mixed   $view 	The current view instance.
			 *
			 * @return 	mixed   The HTML to display.
			 *
			 * @since   1.5.10
			 */
			$values = $app->triggerEvent($event, $args);

			// iterate all the returned values
			foreach ($values as $value)
			{
				if (!is_array($value))
				{
					// use "custom" group in case the returned value is a string
					$value = array('VBO_CUSTOM_FIELDSET' => $value);
				}

				// iterate groups
				foreach ($value as $key => $html)
				{
					// check if the fieldset already exists
					if (!isset($forms[$key]))
					{
						$forms[$key] = '';
					}

					// push form within the specified fieldset
					$forms[$key] .= $html;
				}
			}
		}

		// return array of forms
		return $forms;
	}
}

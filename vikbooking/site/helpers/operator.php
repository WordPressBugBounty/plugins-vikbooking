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
 * Operator and tool permission types class handler.
 * 
 * @since 	1.11 (J) - 1.1 (WP)
 */
final class VikBookingOperator
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var VikBookingOperator
	 */
	protected static $instance = null;

	/**
	 * The various tool permission types.
	 *
	 * @var 	array
	 * 
	 * @since 	1.16.9 (J) - 1.6.9 (WP)
	 */
	protected $toolPermissionTypes = [];

	/**
	 * Class constructor is protected.
	 *
	 * @see 	getInstance()
	 */
	protected function __construct()
	{
		// load all the operator tools and related permission types
		$this->loadToolPermissionTypes();
	}

	/**
	 * Returns the global VikBookingOperator object, either
	 * a new instance or the existing instance
	 * if the class was already instantiated.
	 *
	 * @return 	self 	A new instance of the class.
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Returns the list of all the operators.
	 *
	 * @return 	array 	The list of operators
	 */
	public function getAll()
	{
		$dbo = JFactory::getDbo();

		$operators = [];
		
		$q = "SELECT * FROM `#__vikbooking_operators` ORDER BY `first_name` ASC, `last_name` ASC;";
		$dbo->setQuery($q);
		$all = $dbo->loadAssocList();
		foreach ($all as $o) {
			$operators[$o['id']] = $o;
		}

		return $operators;
	}

	/**
	 * Fetches the information and permissions of one operator by ID.
	 * 
	 * @param 	int 	$id 	The operator ID.
	 * 
	 * @return 	array
	 */
	public function getOne($id)
	{
		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select('*')
				->from($dbo->qn('#__vikbooking_operators'))
				->where($dbo->qn('id') . ' = ' . (int) $id)
		);

		$operator = $dbo->loadAssoc();

		if (!$operator) {
			return [];
		}

		$operator['perms'] = !empty($operator['perms']) ? (array) json_decode($operator['perms'], true) : [];

		return $operator;
	}

	/**
	 * Returns the list of all the operators filtered by a specific
	 * permission type and with an array of their own permissions.
	 * 
	 * @param 	string 	$permtype 	The type of permission to look for.
	 * @param 	array 	$operators	The list of operators.
	 *
	 * @return 	array 				The list of operators.
	 */
	public function getOperatorsFromPermissions($permtype = '', $operators = [])
	{
		if (!$operators) {
			$operators = $this->getAll();
		}

		foreach ($operators as $k => $v) {
			$perms = !empty($v['perms']) ? json_decode($v['perms'], true) : [];
			$perms = !is_array($perms) ? [] : $perms;
			$operators[$k]['perms'] = $perms;
			$enabled = false;
			foreach ($perms as $perm) {
				if ($permtype && ($perm['type'] ?? '') == $permtype) {
					// turn the permissions into an associative array for just the requested tool
					$operators[$k]['perms'] = $perm['perms'];
					$enabled = true;
					break;
				}
			}
			if (!$enabled) {
				unset($operators[$k]);
			}
		}

		return array_values($operators);
	}

	/**
	 * Tells whether the operator is logged in by renewing
	 * the session cookie if the auth method is through code.
	 * Returns the information of the logged in operator or false.
	 *
	 * @return 	mixed 	Array if the operator is logged in, false otherwise.
	 */
	public function getOperatorAccount()
	{
		$dbo  = JFactory::getDbo();
		$user = JFactory::getUser();

		/**
		 * Beware of a possible magic method __get() in JUser.
		 * Assign the id property to a variable for evaluation.
		 */
		$ujid = $user->id;

		if (!$user->guest && !empty($ujid)) {
			$q = "SELECT * FROM `#__vikbooking_operators` WHERE `ujid`=".(int)$user->id." LIMIT 1;";
			$dbo->setQuery($q);
			$record = $dbo->loadAssoc();
			if ($record) {
				// user is logged in to the site account
				return $record;
			}
		}

		$session = JFactory::getSession();
		$sessval = $session->get('vboOpFngpt', '');
		$cookie  = JFactory::getApplication()->input->cookie;
		$cksess  = $cookie->get('vboOpFngpt', '', 'string');

		if (!empty($sessval)) {
			// look up for this fingerprint in the session
			$q = "SELECT * FROM `#__vikbooking_operators` WHERE `fingpt`=" . $dbo->q($sessval);
			$dbo->setQuery($q, 0, 1);
			$record = $dbo->loadAssoc();
			if ($record) {
				// user is logged in through the session
				return $record;
			}
		}

		if (!empty($cksess)) {
			// look up for this fingerprint in the cookie
			$q = "SELECT * FROM `#__vikbooking_operators` WHERE `fingpt`=" . $dbo->q($cksess);
			$dbo->setQuery($q, 0, 1);
			$record = $dbo->loadAssoc();
			if ($record) {
				// user is logged in through the cookie, renew it for 2 weeks before returning the array
				VikRequest::setCookie('vboOpFngpt', $cksess, (time() + (86400 * 14)), '/', '', false, true);
				return $record;
			}
		}

		// user is not logged in
		return false;
	}

	/**
	 * Login request through the authentication code.
	 * If code is valid, the session and cookie are set.
	 *
	 * @param 	string 		$code 	the authentication code to check
	 *
	 * @return 	boolean 	true if the code exists, false otherwise
	 */
	public function authOperator($code)
	{
		if (empty($code)) {
			return false;
		}

		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();
		$cookie  = JFactory::getApplication()->input->cookie;

		$q = "SELECT * FROM `#__vikbooking_operators` WHERE `code`=" . $dbo->q($code);
		$dbo->setQuery($q, 0, 1);
		$operator = $dbo->loadAssoc();

		// ensure a valid code was provided
		if ($operator) {
			// send cookie
			VikRequest::setCookie('vboOpFngpt', $operator['fingpt'], (time() + (86400 * 14)), '/', '', false, true);

			// update session value
			$session->set('vboOpFngpt', $operator['fingpt']);

			return true;
		}

		// invalid code provided
		return false;
	}

	/**
	 * Unsets the session value and sends an expired cookie.
	 * This action completely logs out an operator that should log back in.
	 *
	 * @return 	void
	 */
	public function logoutOperator()
	{
		$app 	 = JFactory::getApplication();
		$session = JFactory::getSession();
		$cookie  = $app->input->cookie;

		$session->set('vboOpFngpt', '');
		VikRequest::setCookie('vboOpFngpt', $operator['fingpt'], (time() - (86400 * 14)), '/', '', false, true);

		// logout from the site
		$app->logout(JFactory::getUser()->id);
	}

	/**
	 * Checks whether the given operator has the permissions to access
	 * the given view type (permission type). Permissions are converted
	 * into an array if still JSON encoded, and updated via reference.
	 * 
	 * @param 	array 		$operator 		the operator record
	 * @param 	string 		$permtype 		the name of the view to access
	 *
	 * @return 	boolean
	 */
	public function checkPermissions(&$operator, $permtype)
	{
		if (!is_array($operator) || !$operator || empty($operator['perms'])) {
			return false;
		}

		if (is_scalar($operator['perms'])) {
			$operator['perms'] = json_decode($operator['perms'], true);
			$operator['perms'] = is_array($operator['perms']) ? $operator['perms'] : [];
		}

		foreach ($operator['perms'] as $k => $perm) {
			if (isset($perm['type']) && $perm['type'] == $permtype) {
				// has permission for this View, take only these perms
				$operator['perms'] = $operator['perms'][$k]['perms'];
				return true;
			}
		}

		// permission not found
		return false;
	}

	/**
	 * Returns the data for the given tool identifier.
	 * 
	 * @param 	string 	$tool 	The tool identifier.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.9 (J) - 1.6.9 (WP)
	 */
	public function getToolData($tool)
	{
		return $this->toolPermissionTypes[$tool] ?? [];
	}

	/**
	 * Returns the name for the given tool identifier.
	 * If a raw language constant is detected, the raw
	 * tool identifier value is returned (front-end).
	 * 
	 * @param 	string 	$tool 	The tool identifier.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.16.9 (J) - 1.6.9 (WP)
	 */
	public function getToolName($tool)
	{
		$tool_name = $this->toolPermissionTypes[$tool]['name'] ?? $tool;

		return preg_match("/^VB[A-Z0-9_]+$/", $tool_name) ? ucfirst(str_replace('_', ' ', $tool)) : $tool_name;
	}

	/**
	 * Returns a list of permission types for a specific tool (i.e. "tableaux") or all tools.
	 * Allows third-party plugins to define their own tool permission types.
	 * 
	 * @param 	string 	$tool 	the tool identifier.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.9 (J) - 1.6.9 (WP)
	 */
	public function getToolPermissionTypes($tool = null)
	{
		$default_tool_perms = $this->loadToolPermissionTypes();

		if (!$tool) {
			return $default_tool_perms;
		}

		return $default_tool_perms[$tool] ?? [];
	}

	/**
	 * Renders a native operator tool through a callback (i.e. "finance").
	 * Alternatively, tools could be rendered through a View (i.e. "tableaux").
	 * Lastly, third-party plugins could load their own operator tools.
	 * 
	 * @param 	string 	$tool 		  the tool identifier.
	 * @param 	array 	$operator 	  the operator record calling the tool.
	 * @param 	object 	$permissinos  the operator-tool permissions registry.
	 * @param 	string 	$tool_uri 	  the base URI to the given tool.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.9 (J) - 1.6.9 (WP)
	 */
	public function renderNativeToolLayout($tool, $operator, $permissions, $tool_uri)
	{
		// prepare the layout data
		$layout_data = [
			'tool' 		  => $tool,
			'operator'    => $operator,
			'permissions' => $permissions,
			'tool_uri' 	  => $tool_uri,
		];

		// render the requested tool layout
		echo JLayoutHelper::render('tools.' . $tool, $layout_data, null, [
			'component' => 'com_vikbooking',
			'client' 	=> 'site',
		]);
	}

	/**
	 * Loads a list of default permission types for all the available tools.
	 * Allows third-party plugins to define their own tools and permission types.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.9 (J) - 1.6.9 (WP)
	 */
	protected function loadToolPermissionTypes()
	{
		if ($this->toolPermissionTypes) {
			// return the cached list
			return $this->toolPermissionTypes;
		}

		$dbo = JFactory::getDbo();

		// fetch all rooms
		$dbo->setQuery(
			$dbo->getQuery(true)
				->select([$dbo->qn('id'), $dbo->qn('name')])
				->from($dbo->qn('#__vikbooking_rooms'))
				->order($dbo->qn('avail') . ' DESC')
				->order($dbo->qn('name') . ' ASC')
		);

		$rooms = [];

		foreach ($dbo->loadAssocList() as $room) {
			$rooms[$room['id']] = $room['name'];
		}

		// build the default tool permission types
		$this->toolPermissionTypes = [
			'tableaux' => [
				'name'        => JText::translate('VBOOPERPERMTABLEAUX'),
				'permissions' => [
					'days'  => [
						'type'    => 'number',
						'label'   => JText::translate('VBOPERMTBLXDAYS'),
						'help'    => JText::translate('VBO_PERM_NEGATIVE_DAYS'),
						'step'    => 1,
						'min'     => -365,
						'max'     => 365,
						'default' => 14,
					],
					'rooms' => [
						'type'     => 'select',
						'label'    => JText::translate('VBOPERMTBLXROOMS'),
						'multiple' => true,
						'assets'   => true,
						'asset_options' => [
                            'placeholder' => JText::translate('VBCOUPONALLVEHICLES'),
                            'allowClear'  => true,
                        ],
						'options' => $rooms,
					],
					'guestname' => [
						'type'    => 'select',
						'label'   => JText::translate('VBOCUSTOMERDETAILS'),
						'options' => [
							1 => JText::translate('VBYES'),
							0 => JText::translate('VBNO'),
						],
					],
					'roomextras' => [
						'type'    => 'select',
						'label'   => JText::translate('VBPEDITBUSYEXTRACOSTS'),
						'options' => [
							1 => JText::translate('VBYES'),
							0 => JText::translate('VBNO'),
						],
					],
				],
				// this native tool is rendered through a View
				'rendering_type' => 'view',
			],
			'finance' => [
				'name'        => JText::translate('VBO_W_FINANCE_TITLE'),
				'permissions' => [
					'rooms' => [
						'type'     => 'select',
						'label'    => JText::translate('VBOPERMTBLXROOMS'),
						'multiple' => true,
						'assets'   => true,
						'asset_options' => [
                            'placeholder' => JText::translate('VBCOUPONALLVEHICLES'),
                            'allowClear'  => true,
                        ],
						'options' => $rooms,
					],
				],
				// this native tool is rendered through a layout thanks to a callback
				'rendering_type' => 'layout',
				'rendering_callback' => function ($tool, $operator, $permissions, $tool_uri) {
					VikBooking::getOperatorInstance()->renderNativeToolLayout($tool, $operator, $permissions, $tool_uri);
				},
			],
		];

		/**
		 * Trigger event to let other plugins register additional tools and permissions.
		 * Custom operator tools do NOT need to define a rendering type or callback.
		 *
		 * @return 	array 	A list of custom tools and related permission types.
		 */
		$custom_tools = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadOperatorToolPermissionTypes');

		foreach ($custom_tools as $tool) {
			if (!is_array($tool)) {
				continue;
			}
			foreach ($tool as $tool_name => $tool_perms) {
				if (!is_numeric($tool_name) && is_array($tool_perms)) {
					// set valid tool, by also allowing to overwrite the default ones
					$this->toolPermissionTypes[$tool_name] = $tool_perms;
				}
			}
		}

		return $this->toolPermissionTypes;
	}
}

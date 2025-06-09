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
	 * A cache array holding all the existing operators.
	 * 
	 * @var array[]
	 * @since 1.18.0 (J) - 1.8.0 (WP)
	 */
	protected $operators = null;

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
	 * @param 	array 	$ids 	Optional list of IDs to filter.
	 *
	 * @return 	array 	The list of operators
	 * 
	 * @since   1.18.0 (J) - 1.8.0 (WP) The method now caches all the operators to prevent duplicate queries.
	 */
	public function getAll(array $ids = [])
	{
		$dbo = JFactory::getDbo();

		if (is_null($this->operators)) {
			$this->operators = [];

			$q = $dbo->getQuery(true)
				->select('*')
				->from($dbo->qn('#__vikbooking_operators'))
				->order($dbo->qn('first_name') . ' ASC')
				->order($dbo->qn('last_name') . ' ASC');

			$dbo->setQuery($q);

			foreach ($dbo->loadAssocList() as $o) {
				$this->operators[$o['id']] = $o;
			}
		}

		// take only the operators matching the specified query
		return array_filter($this->operators, function($o) use ($ids) {
			return !$ids || in_array($o['id'], $ids);
		});
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
		$operator['work_days_week'] = !empty($operator['work_days_week']) ? (array) json_decode($operator['work_days_week'], true) : [];
		$operator['work_days_exceptions'] = !empty($operator['work_days_exceptions']) ? (array) json_decode($operator['work_days_exceptions'], true) : [];

		return $operator;
	}

	/**
	 * Returns a list of operators compatible for rendering them as elements.
	 * 
	 * @param 	array 	$ids 	Optional list of IDs to filter.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public function getElements(array $ids = [])
	{
		$elements = [];

		foreach ($this->getAll($ids) as $operator) {
			// check for operator's avatar picture
			$operator_pic = $operator['pic'] ?: '';
			if ($operator_pic) {
				$operator_pic = strpos($operator_pic, 'http') === 0 ? $operator_pic : VBO_SITE_URI . 'resources/uploads/' . $operator_pic;
			}

			// push operator element
			$elements[] = [
				'id'      => $operator['id'],
				'name'    => ltrim($operator['first_name'] . ' ' . $operator['last_name']),
				'img_uri' => $operator_pic,
			];
		}

		return $elements;
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
			$perms = !empty($v['perms']) && is_string($v['perms']) ? (array) json_decode($v['perms'], true) : [];
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

		/**
		 * The operators authentication process is now case sensitive.
		 * 
		 * @since 1.18 (J) - 1.8 (WP)
		 */
		$q = "SELECT * FROM `#__vikbooking_operators` WHERE BINARY `code` = " . $dbo->q($code);
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
	 * Builds the URI to render the provided operator tool.
	 * 
	 * @param 	string 	$tool 		The operator tool identifier.
	 * @param 	int 	$itemid 	The optional page/item ID.
	 * 
	 * @return 	string 				The routed operator tool URI.
	 * 
	 * @since 	1.17.6 (WP) - 1.7.6 (WP)
	 */
	public function getToolUri(string $tool, int $itemid = 0)
	{
		// build URI to current tool
		return JRoute::rewrite(
			sprintf(
				'index.php?option=com_vikbooking&view=operators&tool=%s&Itemid=%d',
				$tool,
				($itemid ?: JFactory::getApplication()->input->getInt('Itemid', 0))
			)
		);
	}

	/**
	 * Authorizes the currently logged operator to access the given tool.
	 * 
	 * @param 	string 	$tool 	The operator tool identifier.
	 * 
	 * @return 	array 			List of operator-tool data including:
	 * 							- The operator record accessing the tool.
	 * 							- The operator-tool permissions registry.
	 * 							- The base URI for rendering the tool.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.6 (WP) - 1.7.6 (WP)
	 */
	public function authOperatorToolData(string $tool)
	{
		// attempt to get the current operator
		$operator = $this->getOperatorAccount();

		if (!$operator) {
			throw new Exception('Operator authentication required.', 401);
		}

		// get tool data
		$tool_data = $this->getToolData($tool);

		if (!$tool_data) {
			// tool is unknown
			throw new Exception(sprintf('Operator tool not found (%s)', $tool), 404);
		}

		if (!$this->checkPermissions($operator, $tool)) {
			// no permission to access this tool
			throw new Exception(sprintf('Not enough permissions to access the tool (%s)', $tool), 403);
		}

		return [
			$operator,
			new JObject(($operator['perms'] ?: [])),
			$this->getToolUri($tool),
		];
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

		// build the default tool permission types
		$this->toolPermissionTypes = [
			'tableaux' => [
				'name'        => JText::translate('VBOOPERPERMTABLEAUX'),
				'icon'        => '<i class="' . VikBookingIcons::i('stream') . '"></i>',
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
						'type'     => 'listings',
						'label'    => JText::translate('VBOPERMTBLXROOMS'),
						'multiple' => true,
						'asset_options' => [
							'placeholder' => JText::translate('VBCOUPONALLVEHICLES'),
							'allowClear'  => true,
						],
					],
					'guestname' => [
						'type'    => 'select',
						'label'   => JText::translate('VBOCUSTOMERDETAILS'),
						'options' => [
							1 => JText::translate('VBYES'),
							0 => JText::translate('VBNO'),
						],
					],
					'guestphone' => [
						'type'    => 'select',
						'label'   => JText::translate('ORDER_PHONE'),
						'default' => 0,
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
				'icon'        => '<i class="' . VikBookingIcons::i('piggy-bank') . '"></i>',
				'permissions' => [
					'rooms' => [
						'type'     => 'listings',
						'label'    => JText::translate('VBOPERMTBLXROOMS'),
						'multiple' => true,
						'asset_options' => [
							'placeholder' => JText::translate('VBCOUPONALLVEHICLES'),
							'allowClear'  => true,
						],
					],
				],
				// this native tool is rendered through a layout thanks to a callback
				'rendering_type' => 'layout',
				'rendering_callback' => function ($tool, $operator, $permissions, $tool_uri) {
					VikBooking::getOperatorInstance()->renderNativeToolLayout($tool, $operator, $permissions, $tool_uri);
				},
			],
			'guest_messaging' => [
				'name'        => JText::translate('VBO_W_GUESTMESSAGES_TITLE'),
				'icon'        => '<i class="' . VikBookingIcons::i('comment-dots') . '"></i>',
				'permissions' => [
					'rooms' => [
						'type'     => 'listings',
						'label'    => JText::translate('VBOPERMTBLXROOMS'),
						'multiple' => true,
						'asset_options' => [
							'placeholder' => JText::translate('VBCOUPONALLVEHICLES'),
							'allowClear'  => true,
						],
					],
				],
				// this native tool is rendered through a layout thanks to a callback
				'rendering_type' => 'layout',
				'rendering_callback' => function ($tool, $operator, $permissions, $tool_uri) {
					VikBooking::getOperatorInstance()->renderNativeToolLayout($tool, $operator, $permissions, $tool_uri);
				},
			],
			'task_manager' => [
				'name'        => JText::translate('VBO_TASK_MANAGER'),
				'icon'        => '<i class="' . VikBookingIcons::i('tasks') . '"></i>',
				'permissions' => [
					'accept_tasks' => [
						'type'    => 'select',
						'label'   => JText::translate('VBO_ACCEPT_TASKS'),
						'options' => [
							1 => JText::translate('VBYES'),
							0 => JText::translate('VBNO'),
						],
					],
				],
				// this native tool is rendered through a layout thanks to a callback
				'rendering_type' => 'layout',
				'rendering_callback' => function ($tool, $operator, $permissions, $tool_uri) {
					VikBooking::getOperatorInstance()->renderNativeToolLayout($tool, $operator, $permissions, $tool_uri);
				},
			],
			'work_days' => [
				'name'        => JText::translate('VBO_WORK_DAYS'),
				'icon'        => '<i class="' . VikBookingIcons::i('toolbox') . '"></i>',
				'permissions' => [
					'work_days_exceptions' => [
						'type'    => 'select',
						'label'   => JText::translate('VBO_EXCEPTIONS'),
						'help'    => implode(' - ', [JText::translate('VBO_WORK_DAYS_OFF'), JText::translate('VBO_WORK_DAYS_ON')]),
						'options' => [
							1 => JText::translate('VBYES'),
							0 => JText::translate('VBNO'),
						],
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

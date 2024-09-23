<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * MydataAade child Class of VikBookingEInvoicing
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
class VikBookingEInvoicingMydataAade extends VikBookingEInvoicing
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the driver.
	 *
	 * @var 	string
	 */
	public $defaultKeySort = 'ts';

	/**
	 * Property 'defaultKeyOrder' is used by the View that renders the driver.
	 *
	 * @var 	string
	 */
	public $defaultKeyOrder = 'ASC';

	/**
	 * The path to this driver helper directory. Used only by this driver.
	 *
	 * @var 	string
	 */
	protected $driverHelperPath = '';

	/**
	 * An array of session filters.
	 *
	 * @var 	array
	 */
	protected $sessionFilters;

	/**
	 * An array of bookings.
	 *
	 * @var 	array
	 */
	protected $bookings;

	/**
	 * @var 	array
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	protected $environmental_fee_details = [];

	/**
	 * Class constructor should define the name of the driver and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->driverFile = basename(__FILE__, '.php');
		$this->driverName = "myDATA - ΑΑΔΕ Greece";
		$this->driverFilters = [];
		$this->driverButtons = [];

		// driver helper dir path
		$this->driverHelperPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . str_replace(' ', '', ucwords(str_replace('_', ' ', $this->driverFile))) . DIRECTORY_SEPARATOR;
		
		// this driver has settings
		$this->hasSettings = true;

		// reset session filters
		$this->sessionFilters = [];

		// reset bookings array
		$this->bookings = [];

		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

		// require class constants
		$this->importHelper($this->driverHelperPath . 'constants.php');

		parent::__construct();
	}

	/**
	 * Returns the name of this file without .php.
	 *
	 * @return 	string
	 */
	public function getFileName()
	{
		return $this->driverFile;
	}

	/**
	 * Returns the name of this driver.
	 *
	 * @return 	string
	 */
	public function getName()
	{
		return $this->driverName;
	}

	/**
	 * Returns the filters of this driver.
	 *
	 * @return 	array
	 */
	public function getFilters()
	{
		if (count($this->driverFilters)) {
			// do not run this method twice, as it could load JS and CSS files.
			return $this->driverFilters;
		}

		// session filters
		$sessfilters = $this->loadSessionFilters();

		// get VBO Application Object
		$vbo_app = VikBooking::getVboApplication();

		// load the jQuery UI Datepicker
		$this->loadDatePicker();

		// date format
		$df = $this->getDateFormat();

		// request variables
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$peinvtype = VikRequest::getInt('einvtype', 0, 'request');
		$peinvkword = VikRequest::getString('einvkword', '', 'request');
		$pdatetype = VikRequest::getString('datetype', $this->getSessionFilter('datetype', ''), 'request');

		// js lang vars
		JText::script('VBDELCONFIRM');

		// From Date Filter
		$filter_opt = array(
			'label' => '<label for="fromdate">'.JText::translate('VBOREPORTSDATEFROM').'</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-einvoicing-datepicker vbo-einvoicing-datepicker-from" size="12" autocomplete="off" />',
			'type' => 'calendar',
			'name' => 'fromdate'
		);
		array_push($this->driverFilters, $filter_opt);

		// To Date Filter
		$filter_opt = array(
			'label' => '<label for="todate">'.JText::translate('VBOREPORTSDATETO').'</label>',
			'html' => '<input type="text" id="todate" name="todate" value="" class="vbo-einvoicing-datepicker vbo-einvoicing-datepicker-to" size="12" autocomplete="off" />',
			'type' => 'calendar',
			'name' => 'todate'
		);
		array_push($this->driverFilters, $filter_opt);

		// jQuery code for the datepicker calendars and other events
		if (empty($pfromdate) && empty($ptodate)) {
			// if both request values are empty, take them from the session
			$pfromdate = $this->getSessionFilter('fromdate');
			$ptodate = $this->getSessionFilter('todate');
		}
		$js = '
		jQuery(function() {
			jQuery(".vbo-einvoicing-datepicker:input").datepicker({
				maxDate: "+1y",
				dateFormat: "'.$this->getDateFormat('jui').'",
				onSelect: vboEInvoicingCheckDates
			});
			'.(!empty($pfromdate) && empty($peinvkword) ? 'jQuery(".vbo-einvoicing-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			'.(!empty($ptodate) && empty($peinvkword) ? 'jQuery(".vbo-einvoicing-datepicker-to").datepicker("setDate", "'.$ptodate.'");' : '').'
			jQuery("#monyear").change(function() {
				var monopt = jQuery(this).find("option:selected");
				if (monopt && monopt.length && monopt.val().length) {
					var from = monopt.attr("data-from");
					var to = monopt.attr("data-to");
					jQuery(".vbo-einvoicing-datepicker-from").datepicker("setDate", from);
					jQuery(".vbo-einvoicing-datepicker-to").datepicker("setDate", to);
					jQuery("#einvkword").val("");
				}
			});
			jQuery(".vbo-einvoicing-selaction").change(function() {
				var prop = "excludebid"+jQuery(this).attr("data-bid");
				var pobj = {};
				var actval = parseInt(jQuery(this).val());
				pobj[prop] = actval;
				vboSetFilters(pobj, false);
				if (actval > 0) {
					// update cell data attribute for CSS to not-generate
					jQuery(this).closest("td").attr("data-einvaction", 0);
				} else {
					// update cell data attribute for CSS to generate
					jQuery(this).closest("td").attr("data-einvaction", 1);
				}
			});
			jQuery(".vbo-einvoicing-existaction").change(function() {
				var prop = "regeneratebid"+jQuery(this).attr("data-bid");
				var propexcl = "excludesendbid"+jQuery(this).attr("data-bid");
				var einvid = parseInt(jQuery(this).val());
				var pobj = {};
				if (einvid > 0) {
					// update cell data attribute for CSS to generate
					jQuery(this).closest("td").attr("data-einvaction", 1);
					// set re-generate and exclude send
					pobj[prop] = einvid;
					pobj[propexcl] = 1;
				} else {
					if (einvid < 0) {
						// update cell data attribute for CSS to not-transmit
						jQuery(this).closest("td").attr("data-einvaction", 0);
						// set exclude send and not re-generate
						pobj[prop] = 0;
						pobj[propexcl] = 1;
					} else {
						// update cell data attribute for CSS to transmit (value = 0)
						jQuery(this).closest("td").attr("data-einvaction", -2);
						// set send and not re-generate
						pobj[prop] = 0;
						pobj[propexcl] = 0;
					}
				}
				vboSetFilters(pobj, false);
			});
			jQuery(".vbo-einvoicing-sentaction").change(function() {
				var propregen = "regeneratebid"+jQuery(this).attr("data-bid");
				var propresend = "resendbid"+jQuery(this).attr("data-bid");
				var curval = jQuery(this).val();
				var splitval = curval.split("-");
				var einvid = parseInt(splitval[0]);
				var pobj = {};
				if (einvid === 0) {
					// update cell data attribute for CSS to transmitted
					jQuery(this).closest("td").attr("data-einvaction", -1);
					pobj[propregen] = einvid;
					pobj[propresend] = einvid;
				} else {
					if (splitval[1] == "regen") {
						// update cell data attribute for CSS to generate
						jQuery(this).closest("td").attr("data-einvaction", 1);
						pobj[propregen] = einvid;
						pobj[propresend] = 0;
					} else if (splitval[1] == "resend") {
						// update cell data attribute for CSS to transmitted
						jQuery(this).closest("td").attr("data-einvaction", -1);
						pobj[propregen] = 0;
						pobj[propresend] = einvid;
					}
				}
				vboSetFilters(pobj, false);
			});
			jQuery(".vbo-driver-output-vieweinv").click(function() {
				var id = jQuery(this).attr("data-einvid");
				vboSetFilters({einvid: id}, false);
				vboDriverDoAction("viewEInvoice", true);
			});
			jQuery(".vbo-driver-output-editeinv").click(function() {
				var id  = jQuery(this).attr("data-einvid");
				var bid = jQuery(this).attr("data-envfeebid");
				vboSetFilters({drivercontent: "editEInvoice", einvid: id, envfeebid: (bid || null)}, true);
			});
			jQuery(".vbo-driver-output-rmeinv").click(function() {
				var id = jQuery(this).attr("data-einvid");
				if (confirm(Joomla.JText._("VBDELCONFIRM"))) {
					vboSetFilters({einvid: id}, false);
					vboDriverDoAction("removeEInvoice", false);
				}
			});
		});
		function vboEInvoicingCheckDates(selectedDate, inst) {
			if (selectedDate === null || inst === null) {
				return;
			}
			jQuery("#monyear").val("");
			jQuery("#einvkword").val("");
			var cur_from_date = jQuery(this).val();
			if (jQuery(this).hasClass("vbo-einvoicing-datepicker-from") && cur_from_date.length) {
				var nowstart = jQuery(this).datepicker("getDate");
				var nowstartdate = new Date(nowstart.getTime());
				jQuery(".vbo-einvoicing-datepicker-to").datepicker("option", {minDate: nowstartdate});
			}
		}';
		$this->setScript($js);

		// month-year filter
		$q = "SELECT MIN(`for_date`) AS `mindate`, MAX(`for_date`) AS `maxdate` FROM `#__vikbooking_einvoicing_data`;";
		$this->dbo->setQuery($q);
		$minmax = $this->dbo->loadAssoc();
		if ($minmax) {
			if (!empty($minmax['mindate']) && !empty($minmax['maxdate'])) {
				$infomin = getdate(strtotime($minmax['mindate']));
				$infomax = getdate(strtotime($minmax['maxdate']));
				$startts = mktime(0, 0, 0, $infomin['mon'], 1, $infomin['year']);
				$lastts  = mktime(23, 59, 59, $infomax['mon'], date('t', $infomax[0]), $infomax['year']);
				$monthys = [];
				while ($startts < $lastts) {
					array_push($monthys, array(
						'mon' => $infomin['mon'],
						'year' => $infomin['year'],
						'from' => $startts,
						'to' => mktime(0, 0, 0, $infomin['mon'], date('t', $infomin[0]), $infomin['year'])
					));
					$startts = mktime(0, 0, 0, ($infomin['mon'] + 1), 1, $infomin['year']);
					$infomin = getdate($startts);
				}
				$opts = '';
				foreach ($monthys as $my) {
					$dfrom = date($df, $my['from']);
					$dto = date($df, $my['to']);
					$selectedstat = $pfromdate == $dfrom && $ptodate == $dto ? ' selected="selected"' : '';
					$opts .= '<option value="'.$my['from'].'" data-from="'.$dfrom.'" data-to="'.$dto.'"'.$selectedstat.'>'.$this->getMonthString($my['mon']).' '.$my['year'].'</option>';
				}
				$filter_opt = array(
					'label' => '<label for="monyear">' . JText::translate('VBPVIEWRESTRICTIONSTWO') . '</label>',
					'html' => '<select name="monyear" id="monyear"><option value=""></option>'.$opts.'</select>',
					'type' => 'select',
					'name' => 'monyear'
				);
				array_push($this->driverFilters, $filter_opt);
			}
		}

		// date type filter
		$filter_opt = array(
			'label' => '<label for="datetype">' . JText::translate('VBPVIEWORDERSONE') . '</label>',
			'html' => '<select name="datetype" id="datetype">
							<option value="ts"'.($pdatetype == 'ts' ? ' selected="selected"' : '').'>' . JText::translate('VBRENTALORD') . '</option>
							<option value="checkin"'.($pdatetype == 'checkin' ? ' selected="selected"' : '').'>' . JText::translate('VBPICKUPAT') . '</option>
							<option value="checkout"'.($pdatetype == 'checkout' ? ' selected="selected"' : '').'>' . JText::translate('VBRELEASEAT') . '</option>
						</select>',
			'type' => 'select',
			'name' => 'datetype'
		);
		array_push($this->driverFilters, $filter_opt);

		// invoice type filter
		$filter_opt = array(
			'label' => '<label for="einvtype">Show</label>',
			'html' => '<select name="einvtype" id="einvtype">
							<option value="0">All reservations</option>
							<option value="1"'.($peinvtype == 1 ? ' selected="selected"' : '').'>- To be invoiced</option>
							<option value="-1"'.($peinvtype == -1 ? ' selected="selected"' : '').'>- To be transmitted</option>
							<option value="-2"'.($peinvtype == -2 ? ' selected="selected"' : '').'>- Trasmitted</option>
						</select>',
			'type' => 'select',
			'name' => 'einvtype'
		);
		array_push($this->driverFilters, $filter_opt);

		// search invoice filter
		$filter_opt = array(
			'label' => '<label for="einvkword">' . JText::translate('VBODASHSEARCHKEYS') . '</label>',
			'html' => '<div class="input-append"><input type="text" id="einvkword" name="einvkword" value="'.htmlspecialchars($peinvkword).'" size="15" /><button type="button" class="btn btn-secondary" onclick="document.getElementById(\'einvkword\').value = \'\';"><i class="icon-remove"></i></button></div>',
			'type' => 'text',
			'name' => 'einvkword'
		);
		array_push($this->driverFilters, $filter_opt);

		return $this->driverFilters;
	}

	/**
	 * Whether there are enough filters in the session to render data when the page loads.
	 *
	 * @return 	boolean
	 */
	public function hasFiltersSet()
	{
		return (bool)(count($this->loadSessionFilters()) > 0);
	}

	/**
	 * Returns the current filters saved in the session.
	 * This protected method is only used by this class.
	 *
	 * @return 	array
	 */
	protected function loadSessionFilters()
	{
		if ($this->sessionFilters) {
			return $this->sessionFilters;
		}

		$session  	 = JFactory::getSession();
		$sessfilters = $session->get($this->getFileName().'Filt', '');
		$sessfilters = empty($sessfilters) || !is_array($sessfilters) ? array() : $sessfilters;

		$this->sessionFilters = $sessfilters;

		return $this->sessionFilters;
	}

	/**
	 * Returns the current session filter for the given name.
	 * This protected method is only used by this class.
	 * 
	 * @param 	string 	the name of the filter to fetch
	 * @param 	mixed 	the default filter value if empty
	 * 
	 * @return 	mixed 	the current session filter requested, or a default empty value
	 */
	protected function getSessionFilter($name, $def = '')
	{
		if (isset($this->sessionFilters[$name])) {
			return $this->sessionFilters[$name];
		}

		return $def;
	}

	/**
	 * Sets and updates the session filters.
	 * 
	 * @param 	string 	the name of the filter to set
	 * @param 	mixed 	the value to set for the filter
	 * 
	 * @return 	void
	 */
	protected function setSessionFilter($name, $val)
	{
		$this->sessionFilters[$name] = $val;

		// update session
		$session  	 = JFactory::getSession();
		$sessfilters = $session->set($this->getFileName().'Filt', $this->sessionFilters);

		return;
	}

	/**
	 * Returns the buttons for the driver actions.
	 *
	 * @return 	array
	 */
	public function getButtons()
	{
		// generate invoices button
		array_push($this->driverButtons, '
			<a href="JavaScript: void(0);" onclick="vboDriverDoAction(\'generateEInvoices\', false);" class="vbcsvexport"><i class="vboicn-file-text2 icn-nomargin"></i> <span>'.JText::translate('VBODRIVERGENERATEINVS').'</span></a>
		');

		// transmit invoices button
		array_push($this->driverButtons, '
			<a href="JavaScript: void(0);" onclick="vboDriverDoAction(\'transmitEInvoices\', false);" class="vbo-perms-operators"><i class="vboicn-truck icn-nomargin"></i> <span>Transmit to myDATA</span></a>
		');

		// download invoices button
		array_push($this->driverButtons, '
			<a href="JavaScript: void(0);" onclick="vboDriverDoAction(\'downloadEInvoices\', true);" class="vbo-perms-operators"><i class="vboicn-download icn-nomargin"></i> <span>Download XML files</span></a>
		');

		return $this->driverButtons;
	}

	/**
	 * Prepares the data for saving the driver settings.
	 * Validate post vars to make sure they are correct.
	 *
	 * @return 	stdClass
	 */
	protected function prepareSavingSettings()
	{
		$data 	= new stdClass;
		$params = new stdClass;

		// settings vars
		$automatic = VikRequest::getInt('automatic', 0, 'request');
		$progcount = VikRequest::getInt('progcount', 1, 'request');
		$invoiceinum = VikRequest::getInt('invoiceinum', 1, 'request');
		$invoiceinum = $invoiceinum < 1 ? 1 : $invoiceinum;
		// we lower the next invoice num because VikBooking::getNextInvoiceNumber() returns increased by 1
		$invoiceinum--;

		$einvdttype = VikRequest::getString('einvdttype', 'today', 'request');
		$einvexnumdt = VikRequest::getString('einvexnumdt', 'new', 'request');
		$einvtypecode = VikRequest::getString('einvtypecode', '1.1', 'request');
		$vat_exempt_cat = VikRequest::getString('vat_exempt_cat', '1', 'request');
		$einv_paymethod = VikRequest::getString('einv_paymethod', '1', 'request');
		$einv_inc_class_type = VikRequest::getString('einv_inc_class_type', '', 'request');
		$einv_inc_class_cat = VikRequest::getString('einv_inc_class_cat', '', 'request');
		$schema_validate = VikRequest::getInt('schema_validate', 0, 'request');

		$aade_user_id = VikRequest::getString('aade_user_id', '', 'request');
		$aade_subscription_key = VikRequest::getString('aade_subscription_key', '', 'request');
		$test_mode = VikRequest::getInt('test_mode', 0, 'request');
		$mydata_endpoint_url = VikRequest::getString('mydata_endpoint_url', '', 'request');

		$companyname = VikRequest::getString('companyname', '', 'request');
		$vatid = VikRequest::getString('vatid', '', 'request');
		$country = VikRequest::getString('country', '', 'request');
		$address = VikRequest::getString('address', '', 'request');
		$streetnumber = VikRequest::getString('streetnumber', '', 'request');
		$zip = VikRequest::getString('zip', '', 'request');
		$city = VikRequest::getString('city', '', 'request');

		// fields validation
		$mandatory = [
			$companyname,
			$vatid,
			$country,
			$address,
			$streetnumber,
			$zip,
			$city,
			$aade_user_id,
			$aade_subscription_key,
		];
		foreach ($mandatory as $field) {
			if (empty($field)) {
				$this->setError(JText::translate('VBO_PLEASE_FILL_FIELDS'));
				return false;
			}
		}

		// update the global configuration setting 'invoiceinum'
		$q = "UPDATE `#__vikbooking_config` SET `setting`=".$this->dbo->quote((string)$invoiceinum)." WHERE `param`='invoiceinum';";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		// build data for saving
		$params->einvdttype	  = $einvdttype;
		$params->einvexnumdt  = $einvexnumdt;
		$params->einvtypecode = $einvtypecode;
		$params->vat_exempt_cat = $vat_exempt_cat;
		$params->einv_paymethod = $einv_paymethod;
		$params->einv_inc_class_type = $einv_inc_class_type;
		$params->einv_inc_class_cat = $einv_inc_class_cat;
		$params->schema_validate = $schema_validate;

		$params->aade_user_id = $aade_user_id;
		$params->aade_subscription_key = $aade_subscription_key;
		$params->test_mode 	 = $test_mode;
		$params->mydata_endpoint_url = $mydata_endpoint_url;

		$params->companyname = $companyname;
		$params->vatid 	 	 = $vatid;
		$params->country	 = $country;
		$params->address 	 = $address;
		$params->streetnumber = $streetnumber;
		$params->zip 	 	 = $zip;
		$params->city 	 	 = $city;

		/**
		 * Environmental Fee settings
		 * 
		 * @since 	1.16.7 (J) - 1.6.7 (WP)
		 */
		$params->environmental_invoice = VikRequest::getInt('environmental_invoice', 0, 'request');
		$params->envfeeinvoiceinum 	   = VikRequest::getInt('envfeeinvoiceinum', 1, 'request');
		$params->envfeevboid 		   = VikRequest::getInt('envfeevboid', 0, 'request');

		$data->driver 		 = $this->getFileName();
		$data->params 		 = json_encode($params);
		$data->automatic 	 = $automatic;
		$data->progcount 	 = $progcount;

		return $data;
	}

	/**
	 * Gets an array with the default settings.
	 *
	 * @return 	array
	 */
	protected function getDefaultSettings()
	{
		return [
			'id' => -1,
			'driver' => $this->getFileName(),
			'params' => array(),
			'automatic' => 0
		];
	}

	/**
	 * Echoes the HTML required for the driver settings form.
	 *
	 * @return 	void
	 */
	public function printSettings()
	{
		// load current driver settings
		$settings = $this->loadSettings();
		if ($settings === false) {
			$settings = $this->getDefaultSettings();
			/**
			 * it's the first time we run the driver, so we print a warning message
			 * with some instructions for generating the invoices and to transmit them.
			 */
			$this->displayInstructions();
		}

		/**
		 * Load and inject all the configured fees within VikBooking to support the environmental fee.
		 * 
		 * @since 	1.16.7 (J) - 1.6.7 (WP)
		 */
		$this->dbo->setQuery(
			$this->dbo->getQuery(true)
				->select('*')
				->from($this->dbo->qn('#__vikbooking_optionals'))
				->where(1)
				->andWhere([
					$this->dbo->qn('forcesel') . ' = 1',
					$this->dbo->qn('is_fee') . ' = 1',
				], 'OR')
				->order($this->dbo->qn('name') . ' ASC')
		);
		$settings['mandatory_fees'] = $this->dbo->loadAssocList();

		// settings layout file
		$fpath = $this->driverHelperPath . 'settings.php';

		// load helper file and echo its content
		echo $this->loadHelperFile($fpath, $settings);
	}

	/**
	 * Sets some warning messages.
	 *
	 * @return 	array
	 */
	protected function displayInstructions()
	{
		$this->setWarning('Driver settings not available. Make sure to save your personal myDATA information, or the data transmission will not work.');
		$this->setWarning('Fill in all the required information related to your company and to your myDATA profile in order to be able to start generating electronic invoices for AADE.');
	}

	/**
	 * This method converts each booking array into a matrix with one room-booking per index.
	 * It also adds information about the customer and the invoices generated for each booking.
	 * 
	 * @param 	array 	$records 	the array containing the bookings before nesting
	 * 
	 * @return 	array
	 */
	protected function nestBookingsData($records)
	{
		// to avoid heavy and extra joins, we load all customers for the returned booking ids
		$allids = [];
		foreach ($records as $b) {
			if (!isset($b['customer']) && !in_array($b['id'], $allids)) {
				array_push($allids, $b['id']);
			}
		}
		$customers_books = [];
		if (count($allids)) {
			$q = "SELECT `c`.*,`co`.`idorder`,`cy`.`country_name`,`cy`.`country_2_code` FROM `#__vikbooking_customers` AS `c` 
				LEFT JOIN `#__vikbooking_customers_orders` `co` ON `c`.`id`=`co`.`idcustomer` 
				LEFT JOIN `#__vikbooking_countries` AS `cy` ON `c`.`country`=`cy`.`country_3_code` 
				WHERE `co`.`idorder`".(count($allids) === 1 ? "=".(int)$allids[0] : " IN (".implode(', ', $allids).")").";";
			$this->dbo->setQuery($q);
			$allcustomers = $this->dbo->loadAssocList();
			if ($allcustomers) {
				foreach ($allcustomers as $customer) {
					$customers_books[$customer['idorder']] = $customer;
				}
			}
		}

		// nest records with multiple rooms booked inside sub-array
		$bookings = [];
		foreach ($records as $v) {
			if (!isset($bookings[$v['id']])) {
				$bookings[$v['id']] = [];
			}
			// to avoid heavy joins, we put the customer record onto the first nested room booked
			if (!isset($v['customer']) && !$bookings[$v['id']]) {
				$v['customer'] = isset($customers_books[$v['id']]) ? $customers_books[$v['id']] : [];
			}

			// push room sub-array
			array_push($bookings[$v['id']], $v);
		}

		return $bookings;
	}

	/**
	 * Loads the bookings from the DB according to the filters set.
	 * Gathers the information for the electronic invoices generation.
	 * Sets the columns and rows for the page and commands to be displayed.
	 * Updates the internal bookings array for any custom action.
	 *
	 * @return 	boolean
	 */
	public function getBookingsData()
	{
		if (strlen($this->getError())) {
			// other methods may set errors rather than exiting the process, and the View may continue the execution to attempt to render the page.
			return false;
		}

		if (count($this->bookings)) {
			// this method may be called by other generation methods, so it's useless to run it twice
			return true;
		}

		$cpin = VikBooking::getCPinIstance();
		$customsq = '';
		// input fields and other vars
		$pdatetype = VikRequest::getString('datetype', $this->getSessionFilter('datetype', 'ts'), 'request');
		$peinvtype = VikRequest::getInt('einvtype', 0, 'request');
		$peinvkword = VikRequest::getString('einvkword', '', 'request');
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		if (empty($pfromdate) && empty($ptodate)) {
			// if both request values are empty, take them from the session
			$pfromdate = $this->getSessionFilter('fromdate');
			$ptodate = $this->getSessionFilter('todate');
		}
		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';
		$currency_symb = VikBooking::getCurrencySymb();
		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();
		if (empty($ptodate)) {
			$ptodate = $pfromdate;
		}
		// get dates timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		if (empty($peinvkword) && (empty($pfromdate) || empty($from_ts) || empty($to_ts) || $from_ts > $to_ts)) {
			$this->setError('Please select the dates to filter invoices and reservations.');
			return false;
		}

		// update session filters
		$this->setSessionFilter('fromdate', $pfromdate);
		$this->setSessionFilter('todate', $ptodate);
		$this->setSessionFilter('datetype', $pdatetype);

		// query to obtain the records
		$records = [];
		if (!empty($peinvkword)) {
			// search invoice requires a different query
			$seekclauses = [];
			$maybenum = $this->getOnlyNumbers($peinvkword, true);
			$maybevat = $this->getOnlyNumbers($peinvkword);
			if (!empty($maybenum)) {
				// try to seek for this invoice number
				array_push($seekclauses, '`ei`.`number`='.(int)$maybenum);
			}
			if (!empty($maybevat)) {
				// customer vat number
				array_push($seekclauses, "`cust`.`vat` LIKE ".$this->dbo->quote("%".$maybevat."%"));
			}
			// customer company name
			array_push($seekclauses, "`cust`.`company` LIKE ".$this->dbo->quote("%".$peinvkword."%"));
			// customer full name
			array_push($seekclauses, "CONCAT_WS(' ', `cust`.`first_name`, `cust`.`last_name`) LIKE ".$this->dbo->quote("%".$peinvkword."%"));
			// customer email
			if (strpos($peinvkword, '@') !== false) {
				// customer email
				array_push($seekclauses, "`cust`.`email`=".$this->dbo->quote($peinvkword));
			}
			// customer fiscal code
			array_push($seekclauses, "`cust`.`fisccode`=".$this->dbo->quote($peinvkword));

			// find first the booking IDs with a specific query given the filters
			$oidsfound = [];
			$q = "SELECT `ei`.`id`,`ei`.`idorder` FROM `#__vikbooking_einvoicing_data` AS `ei` ".
				"LEFT JOIN `#__vikbooking_customers` AS `cust` ON `ei`.`idcustomer` = `cust`.`id` ".
				"WHERE `ei`.`obliterated`=0 AND (".implode(' OR ', $seekclauses).") ".
				"GROUP BY `ei`.`driverid`,`ei`.`number`;";
			$this->dbo->setQuery($q);
			$results = $this->dbo->loadAssocList();
			if (!$results) {
				$this->setError('No invoice found with the specified filters');
				return false;
			}

			$mergecustoms = false;
			$customsids = [];
			foreach ($results as $res) {
				if ($res['idorder'] < 0) {
					$mergecustoms = true;
					array_push($customsids, $res['id']);
				}
				array_push($oidsfound, $res['idorder']);
			}
			// we make the same query but by passing the IDs of the bookings found according to the filters
			$q = "SELECT `o`.`id`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`idpayment`,`o`.`coupon`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`chcurrency`,`o`.`country`,`o`.`tot_taxes`,".
				"`o`.`tot_city_taxes`,`o`.`tot_fees`,`o`.`cmms`,`o`.`pkg`,`o`.`refund`,`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`idtar`,`or`.`optionals`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`c`.`country_name`,`c`.`country_2_code`,`r`.`name` AS `room_name`,`r`.`fromadult`,`r`.`toadult`,`ei`.`id` AS `einvid`,`ei`.`driverid` AS `einvdriver`,`ei`.`for_date` AS `einvdate`,`ei`.`number` AS `einvnum`,`ei`.`transmitted` AS `einvsent` ".
				"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` ".
				"LEFT JOIN `#__vikbooking_rooms` AS `r` ON `or`.`idroom`=`r`.`id` ".
				"LEFT JOIN `#__vikbooking_countries` AS `c` ON `o`.`country`=`c`.`country_3_code` ".
				"LEFT JOIN `#__vikbooking_einvoicing_data` AS `ei` ON `o`.`id`=`ei`.`idorder` AND `ei`.`obliterated`=0 ".
				"WHERE `o`.`id` IN (".implode(', ', array_unique($oidsfound)).") ".
				"ORDER BY `o`.`ts` ASC, `o`.`id` ASC;";
			// check if we need to merge custom (manual) invoices
			if ($mergecustoms) {
				$customsq = "SELECT `ei`.`id` AS `einvid`,`ei`.`driverid` AS `einvdriver`,`ei`.`created_on`,`ei`.`for_date` AS `einvdate`,`ei`.`number` AS `einvnum`,`ei`.`transmitted` AS `einvsent`,`ei`.`idorder`,`ei`.`idcustomer`,`inv`.`id` AS `invid`,`inv`.`rawcont`,`inv`.`for_date` AS `inv_fordate_ts` ".
					"FROM `#__vikbooking_einvoicing_data` AS `ei` ".
					"LEFT JOIN `#__vikbooking_invoices` AS `inv` ON `ei`.`idorder`=`inv`.`idorder` ".
					"WHERE `ei`.`idorder` < 0 AND `ei`.`obliterated`=0 AND `ei`.`id` IN (".implode(', ', $customsids).");";
			}
		} else {
			// use date filters for the regular query
			$mergecustoms = false;
			$typeclause = '';
			// filter by type
			switch ($peinvtype) {
				case 1:
					$typeclause = '`ei`.`id` IS NULL AND ';
					break;
				case -1:
					$mergecustoms = true;
					$typeclause = '`ei`.`id` IS NOT NULL AND `ei`.`transmitted`=0 AND ';
					break;
				case -2:
					$mergecustoms = true;
					$typeclause = '`ei`.`id` IS NOT NULL AND `ei`.`transmitted`=1 AND ';
					break;
				default:
					// when no e-invoice type filter set, try to merge custom (manual) invoices
					$mergecustoms = true;
					break;
			}
			$q = "SELECT `o`.`id`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`idpayment`,`o`.`coupon`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`chcurrency`,`o`.`country`,`o`.`tot_taxes`,".
				"`o`.`tot_city_taxes`,`o`.`tot_fees`,`o`.`cmms`,`o`.`pkg`,`o`.`refund`,`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`idtar`,`or`.`optionals`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`c`.`country_name`,`c`.`country_2_code`,`r`.`name` AS `room_name`,`r`.`fromadult`,`r`.`toadult`,`ei`.`id` AS `einvid`,`ei`.`driverid` AS `einvdriver`,`ei`.`for_date` AS `einvdate`,`ei`.`number` AS `einvnum`,`ei`.`transmitted` AS `einvsent` ".
				"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` ".
				"LEFT JOIN `#__vikbooking_rooms` AS `r` ON `or`.`idroom`=`r`.`id` ".
				"LEFT JOIN `#__vikbooking_countries` AS `c` ON `o`.`country`=`c`.`country_3_code` ".
				"LEFT JOIN `#__vikbooking_einvoicing_data` AS `ei` ON `o`.`id`=`ei`.`idorder` AND `ei`.`obliterated`=0 ".
				"WHERE ".$typeclause.
				"(`o`.`status`='confirmed' OR (`o`.`status`='cancelled' AND `o`.`totpaid`>0)) AND `o`.`closure`=0 AND `o`.`{$pdatetype}`>=".$from_ts." AND `o`.`{$pdatetype}`<=".$to_ts." ".
				"ORDER BY `o`.`ts` ASC, `o`.`id` ASC;";
			// check if we need to merge custom (manual) invoices (they should be searched with the apposite dates filters for matching `created_on` or `for_date`)
			if ($mergecustoms) {
				$customsq = "SELECT `ei`.`id` AS `einvid`,`ei`.`driverid` AS `einvdriver`,`ei`.`created_on`,`ei`.`for_date` AS `einvdate`,`ei`.`number` AS `einvnum`,`ei`.`transmitted` AS `einvsent`,`ei`.`idorder`,`ei`.`idcustomer`,`inv`.`id` AS `invid`,`inv`.`rawcont`,`inv`.`for_date` AS `inv_fordate_ts` ".
					"FROM `#__vikbooking_einvoicing_data` AS `ei` ".
					"LEFT JOIN `#__vikbooking_invoices` AS `inv` ON `ei`.`idorder`=`inv`.`idorder` ".
					"WHERE `ei`.`idorder` < 0 AND `ei`.`obliterated`=0 AND {$typeclause}".
					"( (`ei`.`created_on`>=".$this->dbo->quote(date('Y-m-d H:i:s', $from_ts))." AND `ei`.`created_on`<=".$this->dbo->quote(date('Y-m-d H:i:s', $to_ts)).") OR ".
					"(`ei`.`for_date`>=".$this->dbo->quote(date('Y-m-d', $from_ts))." AND `ei`.`for_date`<=".$this->dbo->quote(date('Y-m-d', $to_ts)).") ) AND ".
					/**
					 * We need to add also the following clause in order to not get multiple records with equal invoice numbers for manual bookings.
					 */
					"( (`inv`.`created_on`>={$from_ts} AND `inv`.`created_on`<={$to_ts}) OR ".
					"(`inv`.`for_date`>={$from_ts} AND `inv`.`for_date`<={$to_ts}) );";
			}
		}
		$this->dbo->setQuery($q);
		$records = $this->dbo->loadAssocList();

		if (!empty($customsq)) {
			// we make a query to fetch the custom (manual) invoices to merge them with the real bookings
			$this->dbo->setQuery($customsq);
			$custom_records = $this->dbo->loadAssocList();

			foreach ($custom_records as $customrec) {
				$custom_data = $this->prepareCustomInvoiceData($customrec, $cpin->getCustomerByID($customrec['idcustomer']));
				// push the prepared custom invoice array to the global records array
				array_push($records, $custom_data[0]);
			}
		}

		if (!$records) {
			$this->setError('No reservation or invoice found with the specified filters.');
			return false;
		}

		// nest records with multiple rooms booked inside sub-array
		$bookings = $this->nestBookingsData($records);

		// define the columns of the page
		$this->cols = array(
			// id
			array(
				'key' => 'id',
				'sortable' => 1,
				'label' => 'ID'
			),
			// date
			array(
				'key' => 'ts',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBPVIEWORDERSONE')
			),
			// checkin
			array(
				'key' => 'checkin',
				'sortable' => 1,
				'label' => JText::translate('VBPICKUPAT')
			),
			// checkout
			array(
				'key' => 'checkout',
				'sortable' => 1,
				'label' => JText::translate('VBRELEASEAT')
			),
			// customer
			array(
				'key' => 'customer',
				'sortable' => 1,
				'label' => JText::translate('VBOCUSTOMER')
			),
			// country
			array(
				'key' => 'country',
				'sortable' => 1,
				'label' => JText::translate('ORDER_STATE')
			),
			// city
			array(
				'key' => 'city',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('ORDER_CITY')
			),
			// vat
			array(
				'key' => 'vat',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBCUSTOMERCOMPANYVAT')
			),
			// counterpart company name
			array(
				'key' => 'company',
				'sortable' => 1,
				'label' => JText::translate('VBCUSTOMERCOMPANY')
			),
			// total
			array(
				'key' => 'tot',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBPVIEWORDERSSEVEN')
			),
			// commands
			array(
				'key' => 'commands',
				'attr' => array(
					'class="center"'
				),
				'label' => ''
			),
			// action
			array(
				'key' => 'action',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBO_BACKUP_ACTION_LABEL')
			),
		);

		// build the rows of the page
		foreach ($bookings as $bk => $gbook) {
			$bid = $gbook[0]['id'];
			$analog_id = isset($gbook[0]['invid']) && !empty($gbook[0]['invid']) ? $gbook[0]['invid'] : null;
			/**
			 * Manual invoices could have the same number and so negative id order across multiple years.
			 * For this reason, searching for an invoice by number may display invalid links to the manual
			 * invoices, and so we build a list of invoice IDs with related dates to be displayed.
			 */
			$multi_analog_ids = [];
			if (!empty($analog_id) && count($gbook) > 1) {
				$all_analog_ids = [];
				foreach ($gbook as $subinv) {
					if (!isset($subinv['invid']) || !isset($subinv['inv_fordate_ts'])) {
						continue;
					}
					$inv_key_identifier = $subinv['invid'] . $subinv['inv_fordate_ts'];
					if (in_array($inv_key_identifier, $all_analog_ids)) {
						continue;
					}
					array_push($all_analog_ids, $inv_key_identifier);
					array_push($multi_analog_ids, array(
						'invid' => $subinv['invid'],
						'for_date' => date(str_replace("/", $datesep, $df), $subinv['inv_fordate_ts']),
					));
				}
			}
			//
			$tsinfo = getdate($gbook[0]['ts']);
			$tswday = $this->getWdayString($tsinfo['wday'], 'short');
			$ininfo = getdate($gbook[0]['checkin']);
			$inwday = $this->getWdayString($ininfo['wday'], 'short');
			$outinfo = getdate($gbook[0]['checkout']);
			$outwday = $this->getWdayString($outinfo['wday'], 'short');
			$customer = $gbook[0]['customer'];
			$country3 = $gbook[0]['country'];
			$country2 = $gbook[0]['country_2_code'];
			$countryfull = $gbook[0]['country_name'];
			if (empty($country3) && count($customer) && !empty($customer['country'])) {
				$country3 = $customer['country'];
				$gbook[0]['country'] = $country3;
			}
			if (empty($country2) && count($customer) && !empty($customer['country_2_code'])) {
				$country2 = $customer['country_2_code'];
				$gbook[0]['country_2_code'] = $country2;
			}
			if (empty($countryfull) && count($customer) && !empty($customer['country_name'])) {
				$countryfull = $customer['country_name'];
				$gbook[0]['country_name'] = $countryfull;
			}
			$totguests = 0;
			$rooms_map = [];
			$rooms_str = [];
			foreach ($gbook as $book) {
				$totguests += $book['adults'] + $book['children'];
				if (!isset($book['room_name'])) {
					// custom (manual) invoice records may be missing this property
					continue;
				}
				if (!isset($rooms_map[$book['room_name']])) {
					$rooms_map[$book['room_name']] = 0;
				}
				$rooms_map[$book['room_name']]++;
			}
			foreach ($rooms_map as $rname => $rcount) {
				array_push($rooms_str, $rname . ($rcount > 1 ? ' x'.$rcount : ''));
			}
			$rooms_str = implode(', ', $rooms_str);

			// einvnum (if exists)
			$einvnum = !empty($gbook[0]['einvnum']) ? $gbook[0]['einvnum'] : 0;

			// always update the main array reference
			$bookings[$bk] = $gbook;

			// check whether the invoice can be issued
			list($canbeinvoiced, $noinvoicereason) = $this->canBookingBeInvoiced($bookings[$bk]);

			// push fields in the rows array as a new row
			array_push($this->rows, array(
				array(
					'key' => 'id',
					'callback' => function ($val) use ($analog_id, $multi_analog_ids) {
						if ($val < 0 && !empty($analog_id)) {
							// custom (manual) invoices have a negative idorder (-number)
							$returi = base64_encode('index.php?option=com_vikbooking&task=einvoicing');
							if (count($multi_analog_ids) < 2) {
								// just one manual invoice found
								return '<a href="index.php?option=com_vikbooking&task=editmaninvoice&cid[]='.$analog_id.'&goto='.$returi.'"><i class="'.VikBookingIcons::i('external-link').'"></i> '.JText::translate('VBOMANUALINVOICE').'</a>';
							}
							/**
							 * There can be conflictual manual invoices with the same number and negative order
							 * across multiple years, so we print a link to display them all with an alert.
							 * @since 	1.13.5
							 */
							$all_links = [];
							foreach ($multi_analog_ids as $analog_info) {
								array_push($all_links, '<a href="index.php?option=com_vikbooking&task=editmaninvoice&cid[]='.$analog_info['invid'].'&goto='.$returi.'" onclick="alert(\'Use date filters to not list manual invoices with the same number\'); return true;"><i class="'.VikBookingIcons::i('external-link').'"></i> '.JText::translate('VBOMANUALINVOICE').' (' . $analog_info['for_date'] . ')</a>');
							}
							return implode('<br/>', $all_links);
						}
						return '<a href="index.php?option=com_vikbooking&task=editorder&cid[]='.$val.'" target="_blank"><i class="'.VikBookingIcons::i('external-link').'"></i> '.$val.'</a>';
					},
					'value' => $bid
				),
				array(
					'key' => 'ts',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($df, $datesep, $tswday) {
						return $tswday.', '.date(str_replace("/", $datesep, $df), $val);
					},
					'value' => $gbook[0]['ts']
				),
				array(
					'key' => 'checkin',
					'callback' => function ($val) use ($df, $datesep, $inwday) {
						if (empty($val)) {
							// custom (manual) invoices have an empty timestamp
							return '-----';
						}
						return $inwday.', '.date(str_replace("/", $datesep, $df), $val);
					},
					'value' => $gbook[0]['checkin']
				),
				array(
					'key' => 'checkout',
					'callback' => function ($val) use ($df, $datesep, $outwday) {
						if (empty($val)) {
							// custom (manual) invoices have an empty timestamp
							return '-----';
						}
						return $outwday.', '.date(str_replace("/", $datesep, $df), $val);
					},
					'value' => $gbook[0]['checkout']
				),
				array(
					'key' => 'customer',
					'callback' => function ($val) use ($customer, $bid) {
						$goto = base64_encode('index.php?option=com_vikbooking&task=einvoicing');
						if (!empty($val)) {
							$cont = count($customer) ? '<a href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">'.$val.'</a>' : $val;
							if (count($customer) && !empty($customer['country'])) {
								if (is_file(VBO_ADMIN_PATH.DS.'resources'.DS.'countries'.DS.$customer['country'].'.png')) {
									$cont .= '<img src="'.VBO_ADMIN_URI.'resources/countries/'.$customer['country'].'.png'.'" title="'.$customer['country'].'" class="vbo-country-flag vbo-country-flag-left"/>';
								}
							}
						} else {
							// if empty customer ($val) print danger button to assign a customer to this booking ID
							$cont = '<a class="btn btn-danger" href="index.php?option=com_vikbooking&task=newcustomer&bid='.$bid.'&goto='.$goto.'">' . JText::translate('VBOCREATENEWCUST') . '</a>';
						}
						return $cont;
					},
					'value' => (count($customer) ? $customer['first_name'].' '.$customer['last_name'] : '')
				),
				array(
					'key' => 'country',
					'callback' => function ($val) {
						return !empty($val) ? $val : '-----';
					},
					'value' => $countryfull
				),
				array(
					'key' => 'city',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($customer) {
						$goto = base64_encode('index.php?option=com_vikbooking&task=einvoicing');
						if (empty($val)) {
							if (count($customer) && !empty($customer['id'])) {
								// just an empty City, edit the customer
								$cont = '<a class="btn btn-danger" href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">' . JText::translate('VBCONFIGCLOSINGDATEADD') . '</a>';
							} else {
								$cont = '-----';
							}
							return $cont;
						}
						if (count($customer) && empty($customer['zip'])) {
							// postal code is mandatory
							return '<a class="btn btn-danger" href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">No Postal Code</a>';
						}
						if (count($customer) && empty($customer['address'])) {
							// address is mandatory
							return '<a class="btn btn-secondary" href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">No Address</a>';
						}
						return $val;
					},
					'value' => (count($customer) && !empty($customer['city']) ? $customer['city'] : '')
				),
				array(
					'key' => 'vat',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($customer, $bid) {
						if (!empty($val)) {
							$cont = $val;
						} else {
							$goto = base64_encode('index.php?option=com_vikbooking&task=einvoicing');
							if (count($customer) && !empty($customer['id'])) {
								// empty VAT Number, which is mandatory for both issuer and counterpart
								$cont = '<a class="btn btn-danger" href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">' . JText::translate('VBCONFIGCLOSINGDATEADD') . '</a>';
							} else {
								// if empty customer ($val) print danger button to assign a customer to this booking ID
								$cont = '<a class="btn btn-danger" href="index.php?option=com_vikbooking&task=newcustomer&bid='.$bid.'&goto='.$goto.'">' . JText::translate('VBCONFIGCLOSINGDATEADD') . '</a>';
							}
						}
						return $cont;
					},
					'value' => (count($customer) && !empty($customer['vat']) ? $customer['vat'] : '')
				),
				array(
					'key' => 'company',
					'callback' => function ($val) use ($customer) {
						$cont = !empty($val) ? $val : '-----';
						if (count($customer)) {
							$goto = base64_encode('index.php?option=com_vikbooking&task=einvoicing');
							$cont = '<a href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">'.$cont.'</a>';
						}
						return $cont;
					},
					'value' => (count($customer) && !empty($customer['company']) ? $customer['company'] : '')
				),
				array(
					'key' => 'tot',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb.' '.VikBooking::numberFormat($val);
					},
					'value' => $gbook[0]['total']
				),
				array(
					'key' => 'commands',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($bid, $noinvoicereason) {
						if ($val === 0 || $val === 1) {
							// invoice cannot be issued or is about to be issued
							return '';
						}
						$buttons = [];
						if ($val === -1 || $val === -2) {
							// invoice generated or generated and transmitted
							array_push($buttons, '<i class="vboicn-eye icn-nomargin vbo-driver-customoutput vbo-driver-output-vieweinv" title="View invoice" data-einvid="' . $noinvoicereason . '"></i>');
							array_push($buttons, '<i class="vboicn-pencil2 icn-nomargin vbo-driver-customoutput vbo-driver-output-editeinv" title="Edit invoice" data-einvid="' . $noinvoicereason . '"></i>');
							$correlated_inv_numb = $this->getPreviousCorrelatedInvoiceData($noinvoicereason, $bid);
							if ($correlated_inv_numb) {
								array_push($buttons, '<i class="vboicn-pencil2 icn-nomargin vbo-driver-customoutput vbo-driver-output-editeinv" title="Edit environmental fee invoice" data-einvid="' . $noinvoicereason . '" data-envfeebid="' . $bid . '"></i>');
							}
							array_push($buttons, '<i class="vboicn-bin icn-nomargin vbo-driver-customoutput vbo-driver-output-rmeinv" title="Delete invoice" data-einvid="' . $noinvoicereason . '"></i>');
						}
						return implode("\n", $buttons);
					},
					'value' => $canbeinvoiced
				),
				array(
					'key' => 'action',
					'attr' => array(
						'class="center vbo-einvoicing-cellaction"',
						'data-einvaction="'.$canbeinvoiced.'"'
					),
					'callback' => function ($val) use ($bid, $noinvoicereason, $einvnum) {
						if ($val === 0) {
							// invoice cannot be issued
							$noinvoicereason = empty($noinvoicereason) ? 'Missing data to generate the invoice' : $noinvoicereason;
							return '<button type="button" class="btn btn-secondary" onclick="alert(\''.addslashes($noinvoicereason).'\');"><i class="vboicn-blocked icn-nomargin"></i> Not billable</button>';
						}
						if ($val === -1) {
							// e-invoice already issued and transmitted: print drop down to let the customer regenerate this invoice and obliterate the other or to re-send
							return '<select class="vbo-einvoicing-sentaction" data-bid="'.$bid.'"><option value="0-none">Invoice #'.$einvnum.' transmitted</option><option value="'.$noinvoicereason.'-regen">- Regenerate invoice</option><option value="'.$noinvoicereason.'-resend">- Retransmit invoice</option></select>';
						}
						if ($val === -2) {
							// e-invoice already issued but NOT transmitted: print drop down to let the customer regenerate this invoice and obliterate the other
							return '<select class="vbo-einvoicing-existaction" data-bid="'.$bid.'"><option value="0">Transmit invoice #'.$einvnum.'</option><option value="-1">- Do NOT transmit invoice</option><option value="'.$noinvoicereason.'">- Regenerate invoice</option></select>';
						}
						// invoice can be issued: print drop down to let the customer skip this generation
						return '<select class="vbo-einvoicing-selaction" data-bid="'.$bid.'"><option value="0">Generate invoice</option><option value="1">- Do NOT generate invoice</option></select>';
					},
					'value' => $canbeinvoiced
				),
			));
		}

		// sort rows
		$this->sortRows($pkrsort, $pkrorder);

		// build footer rows
		$totcols = count($this->cols);
		$footerstats = [];
		foreach ($this->rows as $k => $row) {
			foreach ($row as $col) {
				if ($col['key'] != 'action') {
					continue;
				}
				if (!isset($footerstats[$col['value']])) {
					$footerstats[$col['value']] = 0;
				}
				$footerstats[$col['value']]++;
			}
		}
		$avgcolspan = floor($totcols / count($footerstats));
		$footercells = [];
		foreach ($footerstats as $canbeinvoiced => $tot) {
			switch ($canbeinvoiced) {
				case 1:
					$descr = 'To be invoiced';
					break;
				case -1:
					$descr = 'Transmitted invoices';
					break;
				case -2:
					$descr = 'Generated invoices';
					break;
				default:
					$descr = 'Not billable';
					break;
			}
			array_push($footercells, array(
				'attr' => array(
					'class="vbo-report-total vbo-driver-total"',
					'colspan="'.$avgcolspan.'"'
				),
				'value' => '<h3>'.$descr.': '.$tot.'</h3>'
			));
		}
		$this->footerRow[0] = $footercells;
		$missingcols = $totcols - ($avgcolspan * count($footerstats));
		if ($missingcols > 0) {
			array_push($this->footerRow[0], array(
				'attr' => array(
					'class="vbo-report-total vbo-driver-total"',
					'colspan="'.$missingcols.'"'
				),
				'value' => ''
			));
		}

		// update bookings array for the other methods to avoid double executions
		$this->bookings = $bookings;

		return true;
	}

	/**
	 * Checks whether an e-invoice can be issued for this booking.
	 *
	 * @param 	array 	the booking array with one array-room per array value
	 *
	 * @return 	array 	to be used with list(): 0 => (int) can be invoiced, 1 => (string) reason message
	 * 
	 * @see 	https://www.aade.gr/sites/default/files/2020-04/myDATA%20API%20Documentation%20v0%206b_eng.pdf
	 */
	protected function canBookingBeInvoiced($booking)
	{
		if (empty($booking[0]['customer']) || empty($booking[0]['customer']['vat'])) {
			// the VAT number is a mandatory field for both issuer and counterpart
			return array(0, 'Missing VAT Number');
		}

		if (empty($booking[0]['customer']) || empty($booking[0]['customer']['country']) || empty($booking[0]['customer']['country_2_code'])) {
			return array(0, 'Missing country');
		}

		if (empty($booking[0]['customer']) || empty($booking[0]['customer']['city'])) {
			return array(0, 'Missing City');
		}

		if (empty($booking[0]['customer']) || empty($booking[0]['customer']['zip'])) {
			return array(0, 'Missing Postal Code');
		}

		// check if an electronic invoice was already issued for this booking ID by this driver
		if (!empty($booking[0]['einvid']) && $booking[0]['einvdriver'] == $this->getDriverId()) {
			if ($booking[0]['einvsent'] > 0) {
				// in this case we return -1 because an e-invoice was already issued and transmitted. We use the second key for the ID of the e-invoice
				return array(-1, $booking[0]['einvid']);
			}
			// in this case we return -2 because an e-invoice was already issued but NOT transmitted. We use the second key for the ID of the e-invoice
			return array(-2, $booking[0]['einvid']);
		}

		return array(1, '');
	}

	/**
	 * @inheritDoc
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public function elaborateBookingDetails(array &$booking, array &$rooms = [])
	{
		// load driver settings
		$settings = $this->loadSettings();
		if (!$settings || !$settings['params']) {
			return;
		}

		// make sure the environmental fee invoice generation setting is enabled
		if (empty($settings['params']['environmental_invoice']) || empty($settings['params']['envfeevboid'])) {
			return;
		}

		// look for the environmental fee details
		$this->environmental_fee_details = [];

		// scan the list of room reservation options to find the environmental fee
		foreach ($rooms as $k => $or) {
			if (empty($or['optionals'])) {
				continue;
			}
			$stepo = explode(";", $or['optionals']);
			foreach ($stepo as $roptkey => $oo) {
				if (empty($oo)) {
					continue;
				}
				$stept = explode(":", $oo);
				if ((int)$stept[0] != (int)$settings['params']['envfeevboid']) {
					continue;
				}

				$this->dbo->setQuery(
					$this->dbo->getQuery(true)
						->select('*')
						->from($this->dbo->qn('#__vikbooking_optionals'))
						->where($this->dbo->qn('id') . ' = ' . (int)$settings['params']['envfeevboid'])
				, 0, 1);
				$environmental_fee = $this->dbo->loadAssoc();

				if ($environmental_fee && !$environmental_fee['pcentroom'] && $environmental_fee['cost']) {
					// we've found what we needed, calculate the fee cost
					$fee_cost = $environmental_fee['perday'] ? ($environmental_fee['cost'] * $booking['days']) : $environmental_fee['cost'];
					if ($environmental_fee['perperson']) {
						$fee_cost = $fee_cost * $or['adults'];
					}

					if (!$this->environmental_fee_details) {
						$this->environmental_fee_details = $environmental_fee;
						$this->environmental_fee_details['fee_cost'] = 0;
					}

					// set final cost
					$this->environmental_fee_details['fee_cost'] += $fee_cost;

					// unset the extra service from the room reservation record
					unset($stepo[$roptkey]);
					$rooms[$k]['optionals'] = implode(';', $stepo);

					// just one fee per room is supported
					break;
				}
			}
		}

		if (!$this->environmental_fee_details && !empty($booking['idorderota']) && !empty($booking['channel'])) {
			// in case of OTA reservation, scan the list of custom extra services and their type
			foreach ($rooms as $k => $or) {
				if (empty($or['extracosts'])) {
					continue;
				}
				$extra_costs = json_decode($or['extracosts'], true);
				if (!is_array($extra_costs) || !$extra_costs) {
					continue;
				}
				foreach ($extra_costs as $ec_key => $ec_data) {
					if (!$ec_data || empty($ec_data['type']) || empty($ec_data['name']) || empty($ec_data['cost'])) {
						continue;
					}
					if (!strcasecmp($ec_data['type'], 'env_fee')) {
						// environmental fee found from OTA reservation
						if (!$this->environmental_fee_details) {
							$this->environmental_fee_details = $ec_data;
							$this->environmental_fee_details['fee_cost'] = 0;
						}

						// set final cost
						$this->environmental_fee_details['fee_cost'] += (float)$ec_data['cost'];

						// unset the custom extra service from the room reservation record
						unset($extra_costs[$ec_key]);
						if (!$extra_costs) {
							$rooms[$k]['extracosts'] = null;
						} else {
							$rooms[$k]['extracosts'] = json_encode($extra_costs);
						}

						// just one fee per room is supported
						break;
					}
				}
			}
		}

		if ($this->environmental_fee_details) {
			// lower booking total value
			$booking['total'] -= $this->environmental_fee_details['fee_cost'];

			if (isset($booking['optionals'])) {
				$booking['optionals'] = $rooms[0]['optionals'];
			}
		}

		return;
	}

	/**
	 * @inheritDoc
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public function getBookingExtraInvoices($bid)
	{
		$this->dbo->setQuery(
			$this->dbo->getQuery(true)
				->select('*')
				->from($this->dbo->qn('#__vikbooking_einvoicing_data'))
				->where($this->dbo->qn('driverid') . ' = ' . $this->dbo->q($this->getDriverId()))
				->where($this->dbo->qn('idorder') . ' = ' . (int)$bid)
				->where($this->dbo->qn('transmitted') . ' = 1')
				->where($this->dbo->qn('obliterated') . ' = 0')
				->order($this->dbo->qn('id') . ' DESC')
		, 0, 1);

		$last_einvoice = $this->dbo->loadAssoc();

		if (!$last_einvoice) {
			return [];
		}

		$booking = VikBooking::getBookingInfoFromID($bid);

		// check if a correlated invoice was generated
		$correlated_inv_raw_data = VBOFactory::getConfig()->getArray($this->getCorrelatedInvoiceParamName($last_einvoice['id'], $bid), []);

		if (!$correlated_inv_raw_data || empty($correlated_inv_raw_data['transmission']) || empty($correlated_inv_raw_data['transmission']['pdf'])) {
			return [];
		}

		// build the PDF locations
		$pdffname = implode('_', ['envfee', $booking['id'], ($booking['sid'] ?: $booking['ts'])]) . '.pdf';
		$pathpdf  = VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "invoices" . DIRECTORY_SEPARATOR . "generated" . DIRECTORY_SEPARATOR . $pdffname;
		$urlpdf   = VBO_SITE_URI . 'helpers/invoices/generated/' . $pdffname;

		return [
			[
				'label' => 'Correlated Invoice',
				'path' 	=> $pathpdf,
				'uri' 	=> $urlpdf,
			]
		];
	}

	/**
	 * Generates the electronic invoices according to the input parameters.
	 * This is a 'driver action', and so it's called before getBookingsData()
	 * in the view. This method will save/update records in the DB so that when
	 * the view re-calls getBookingsData(), the information will be up to date.
	 *
	 * @return 	boolean 	True if at least one e-invoice was generated
	 */
	public function generateEInvoices()
	{
		// call the main method to generate rows, cols and bookings array
		$this->getBookingsData();

		if (strlen($this->getError()) || !$this->bookings) {
			return false;
		}

		$generated = 0;

		foreach ($this->bookings as $gbook) {
			// check whether this booking ID was set to be skipped
			$exclude = VikRequest::getInt('excludebid'.$gbook[0]['id'], 0, 'request');
			if ($exclude > 0) {
				// skipping this invoice
				continue;
			}

			// check if an electronic invoice was already issued for this booking ID by this driver
			if (!empty($gbook[0]['einvid']) && $gbook[0]['einvdriver'] == $this->getDriverId()) {
				$regenerate = VikRequest::getInt('regeneratebid'.$gbook[0]['id'], 0, 'request');
				if (!($regenerate > 0)) {
					// we do not re-generate an invoice for this booking ID
					continue;
				}
			}

			// generate invoice
			if ($this->generateEInvoice($gbook)) {
				$generated++;
			}
		}

		// we need to unset the bookings var so that the later call to getBookingsData() made by the View will reload the information
		$this->bookings = [];
		// unset also cols, rows and footer row to not merge data
		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

		// set info message
		$this->setInfo('Invoices generated: '.$generated);

		return ($generated > 0);
	}

	/**
	 * Given two arguments, the current analogic invoice record and the customer record, this
	 * method should prepare and return an array that can be later passed onto generateEInvoice().
	 * This originally abstract method must be implemented for the generation of the custom (manual) invoices
	 * that are not related to any bookings (idorder = -number), that were manually created for certain customers.
	 * 
	 * @param 	array 	$invoice 	the analogic invoice record
	 * @param 	array 	$customer 	the customer record obtained through VikBookingCustomersPin::getCustomerByID()
	 *
	 * @return 	array 	the data array compatible with generateEInvoice()
	 * 
	 * @see 	generateEInvoice()
	 */
	public function prepareCustomInvoiceData($invoice, $customer)
	{
		if (!isset($invoice['number']) && !empty($invoice['einvnum'])) {
			// getBookingsData() may call this method by knowing only the electronic invoice number
			$invoice['number'] = $invoice['einvnum'];
		}

		// make sure to get an integer value from the invoice number, which is a string with a probable suffix
		$numnumber = intval(preg_replace("/[^\d]+/", '', $invoice['number']));

		// make sure the key rawcont is an array
		if (!is_array($invoice['rawcont'])) {
			$rawcont = !empty($invoice['rawcont']) ? json_decode($invoice['rawcont'], true) : [];
			$rawcont = is_array($rawcont) ? $rawcont : [];
			$invoice['rawcont'] = $rawcont;
		}

		// build necessary data array compatible with generateEInvoice()
		$data = array(
			'id' => ($numnumber - ($numnumber * 2)),
			'ts' => (isset($invoice['created_on']) ? strtotime($invoice['created_on']) : time()),
			'checkin' => 0,
			'checkout' => 0,
			'adults' => 0,
			'children' => 0,
			'total' => $invoice['rawcont']['totaltot'],
			'country' => $customer['country'],
			'country_name' => $customer['country_name'],
			'country_2_code' => (isset($customer['country_2_code']) ? $customer['country_2_code'] : null),
			'tot_taxes' => $invoice['rawcont']['totaltax'],
			'tot_city_taxes' => 0,
			'tot_fees' => 0,
			'customer' => $customer,
			'pkg' => null,
			'einvid' => (isset($invoice['einvid']) ? $invoice['einvid'] : null),
			'einvdriver' => (isset($invoice['einvdriver']) ? $invoice['einvdriver'] : null),
			'einvdate' => (isset($invoice['einvdate']) ? $invoice['einvdate'] : null),
			'einvnum' => (isset($invoice['einvnum']) ? $invoice['einvnum'] : null),
			'einvsent' => (isset($invoice['einvsent']) ? $invoice['einvsent'] : null),
			// this could be the ID of the analogic invoice
			'invid' => (isset($invoice['invid']) ? $invoice['invid'] : null),
			// this could be the for date timestamp of the analogic invoice
			'inv_fordate_ts' => (isset($invoice['inv_fordate_ts']) ? $invoice['inv_fordate_ts'] : null),
		);

		// make sure to inject the raw content of the custom invoice
		$this->externalData['einvrawcont'] = $invoice['rawcont'];

		// original data array contains nested rooms booked so we need to return it as the 0th value
		return array($data);
	}

	/**
	 * Checks whether an active electronic invoice already exists from the given details.
	 *
	 * @param 	mixed 	$data 	array or StdClass object with properties to identify the e-invoice
	 *
	 * @return 	mixed 	False if the invoice does not exist, its ID otherwise.
	 */
	public function eInvoiceExists($data)
	{
		if (is_object($data)) {
			// cast to array
			$data = (array)$data;
		}

		// allowed properties to check
		$properties = array(
			'id' 	  => 'einvid',
			'idorder' => 'idorder',
			'number'  => 'number',
		);

		$filters = [];
		foreach ($properties as $k => $v) {
			if (isset($data[$v]) && !empty($data[$v])) {
				$filters[$k] = $data[$v];
			} elseif (isset($data[$k]) && !empty($data[$k])) {
				$filters[$k] = $data[$k];
			}
		}

		if (empty($filters)) {
			return false;
		}

		$clauses = [];
		foreach ($filters as $col => $val) {
			array_push($clauses, "`{$col}`=".$this->dbo->quote($val));
		}

		$q = "SELECT `id` FROM `#__vikbooking_einvoicing_data` WHERE `driverid`=".(int)$this->getDriverId()." AND `obliterated`=0 AND ".implode(' AND ', $clauses)." ORDER BY `id` DESC LIMIT 1;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if (!$this->dbo->getNumRows()) {
			return false;
		}
		
		return $this->dbo->loadResult();
	}

	/**
	 * Attempts to set one e-invoice to obliterated.
	 *
	 * @param 	mixed 	$data 	array or StdClass object with properties to identify the e-invoice
	 *
	 * @return 	void
	 */
	public function obliterateEInvoice($data)
	{
		if (is_object($data)) {
			// cast to array
			$data = (array)$data;
		}

		// allowed properties to check
		$properties = array(
			'id' 	  => 'einvid',
			'idorder' => 'idorder',
			'number'  => 'number',
		);

		$filters = [];
		foreach ($properties as $k => $v) {
			if (isset($data[$v]) && !empty($data[$v])) {
				$filters[$k] = $data[$v];
			} elseif (isset($data[$k]) && !empty($data[$k])) {
				$filters[$k] = $data[$k];
			}
		}

		if (empty($filters)) {
			return;
		}

		$clauses = [];
		foreach ($filters as $col => $val) {
			array_push($clauses, "`{$col}`=".$this->dbo->quote($val));
		}

		$q = "UPDATE `#__vikbooking_einvoicing_data` SET `obliterated`=1 WHERE `driverid`=".(int)$this->getDriverId()." AND ".implode(' AND ', $clauses).";";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
	}

	/**
	 * Generates one single electronic invoice. If no array data provided, the booking ID should
	 * be passed as argument. In this case the method would fetch and nest the booking data.
	 *
	 * @param 	mixed 	$data 			either the booking ID or the booking array (one room info per index)
	 * @param 	bool 	$correlated 	true if the invoice should only contain the environmental fee details.
	 *
	 * @return 	string|bool 			true if the e-invoice was generated, or string if it was the correlated one.
	 */
	public function generateEInvoice($data, $correlated = false)
	{
		// load driver settings
		$settings = $this->loadSettings();
		if ($settings === false || !$settings['params']) {
			$this->setError('Missing driver settings. Please set up the driver first.');
			return false;
		}

		if ($correlated && !$this->environmental_fee_details) {
			$this->setError('Could not generate the correlated invoice');
			return false;
		}

		if (is_int($data)) {
			// query to obtain the booking records
			$q = "SELECT `o`.`id`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`idpayment`,`o`.`coupon`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`chcurrency`,`o`.`country`,`o`.`tot_taxes`,".
				"`o`.`tot_city_taxes`,`o`.`tot_fees`,`o`.`cmms`,`o`.`pkg`,`o`.`refund`,`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`idtar`,`or`.`optionals`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`c`.`country_name`,`r`.`name` AS `room_name`,`r`.`fromadult`,`r`.`toadult`,`ei`.`id` AS `einvid`,`ei`.`driverid` AS `einvdriver`,`ei`.`for_date` AS `einvdate`,`ei`.`number` AS `einvnum`,`ei`.`transmitted` AS `einvsent` ".
				"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` ".
				"LEFT JOIN `#__vikbooking_rooms` AS `r` ON `or`.`idroom`=`r`.`id` ".
				"LEFT JOIN `#__vikbooking_countries` AS `c` ON `o`.`country`=`c`.`country_3_code` ".
				"LEFT JOIN `#__vikbooking_einvoicing_data` AS `ei` ON `o`.`id`=`ei`.`idorder` AND `ei`.`obliterated`=0 ".
				"WHERE ".
				"(`o`.`status`='confirmed' OR (`o`.`status`='cancelled' AND `o`.`totpaid`>0)) AND `o`.`closure`=0 AND `o`.`id`=".$this->dbo->quote($data)." ".
				"ORDER BY `o`.`ts` ASC, `o`.`id` ASC;";
			$this->dbo->setQuery($q);
			$record = $this->dbo->loadAssocList();
			if (!$record) {
				$this->setError('Could not find the booking information');
				return false;
			}
			
			// nest records with multiple rooms booked inside sub-array
			$record = $this->nestBookingsData($record);
			$data = $record[$data];
		}

		if (!is_array($data) || empty($data)) {
			$this->setError('No bookings found');
			return false;
		}

		/**
		 * Elaborate the booking details in case of environmental fee available.
		 * 
		 * @since 	1.16.7 (J) - 1.6.7 (WP)
		 */
		if (!$correlated) {
			// build the booking record
			$elaborate_booking = $data[0];

			// build the room reservation records
			$elaborate_rooms   = $data;

			// elaborate data for the environmental fee
			$this->elaborateBookingDetails($elaborate_booking, $elaborate_rooms);

			// replace values, all room reservation records first, then the main booking
			$data    = $elaborate_rooms;
			$data[0] = $elaborate_booking;
		}

		// check whether the invoice can be issued
		list($canbeinvoiced, $noinvoicereason) = $this->canBookingBeInvoiced($data);
		if ($canbeinvoiced === 0) {
			/**
			 * IMPORTANT: if this method is not called by generateEInvoices(), then the script should
			 * make sure that an e-invoice is not already available for this booking ID because
			 * here we skip only if $canbeinvoiced=0 and when e-invoices exist, the code is -1 or -2.
			 */

			// do not raise any errors unless called externally, we just skip this booking because it cannot be invoiced
			if ($this->externalCall) {
				if ($data[0]['id'] < 0) {
					$message = "Could not generate electronic invoice from custom invoice: {$noinvoicereason}";
				} else {
					$message = "Could not generate electronic invoice for booking ID {$data[0]['id']} ({$noinvoicereason})";
				}
				$this->setError($message);
			}

			return false;
		}

		// counterpart branch number
		$branch = '0';

		// invoice/correlated invoice number
		$invnum = '';
		$correlated_invnum = '';

		// counterpart name must not be submitted if entity is from Greece
		$client_name = '';
		if ((!empty($data[0]['customer']['first_name']) || !empty($data[0]['customer']['last_name'])) && $data[0]['customer']['country'] != 'GRC') {
			$client_name = $data[0]['customer']['first_name'] . ' ' . $data[0]['customer']['last_name'];
		}

		// invoice date and number (suffix not supported for AA serial number)
		if (!empty($data[0]['einvnum']) && $settings['params']['einvexnumdt'] == 'old') {
			// if an invoice already exists, we re-use the same number also because the setting said so
			$invnum  = $data[0]['einvnum'];
			$invdate = $data[0]['einvdate'];
			if ($correlated) {
				// get the previous correlated invoice number
				$correlated_invnum = $this->getPreviousCorrelatedInvoiceData($data[0]['einvid'], $data[0]['id']);
			}
		} else {
			// get a new invoice number
			if ($correlated) {
				$correlated_invnum = (int)($settings['params']['envfeeinvoiceinum'] ?: 0) + 1;
			} else {
				$invnum = VikBooking::getNextInvoiceNumber();
			}
			$invdate = $settings['params']['einvdttype'] == 'ts' ? date('Y-m-d', $data[0]['ts']) : date('Y-m-d');
		}
		if (isset($this->externalData['einvnum']) && intval($this->externalData['einvnum']) > 0) {
			// external calls may inject the invoice number to use
			$invnum = (int)$this->externalData['einvnum'];
		}
		if (isset($this->externalData['einvdate']) && !empty($this->externalData['einvdate'])) {
			// external calls may inject the invoice date to use
			$invdate = is_int($this->externalData['einvdate']) ? date('Y-m-d', $this->externalData['einvdate']) : $this->externalData['einvdate'];
		}

		// invoice series ("in case of non-issuance of series of an invoice, the series field must have a value of 0")
		$series = $correlated ? 'C' : '0';
		// invoice serial number "aa" (we use the e-invoice number in VBO with no suffix as it must be a positive number, or it could be just '0')
		$aa_serial_number = $correlated && $correlated_invnum ? $correlated_invnum : $invnum;
		// invoice type
		$invtype = !empty($settings['params']['einvtypecode']) ? $settings['params']['einvtypecode'] : VikBookingMydataAadeConstants::DEFAULT_INVOICE_TYPE;
		$invtype = $correlated ? '8.2' : $invtype;

		// invoice total paid amount
		$inv_tot_paid = empty($data[0]['totpaid']) ? $data[0]['total'] : $data[0]['totpaid'];
		if ($correlated) {
			$inv_tot_paid = $this->environmental_fee_details['fee_cost'];
		}

		// invoice payment method
		$inv_pay_method = '';
		if (!empty($data[0]['idpayment'])) {
			$pay_info_parts = explode('=', $data[0]['idpayment']);
			$inv_pay_method = !empty($pay_info_parts[1]) ? $pay_info_parts[1] : $inv_pay_method;
		}

		// compose the invoice UID
		$invoice_uid_parts = [
			$settings['params']['vatid'],
			$invdate,
			$branch,
			$invtype,
			$series,
			$aa_serial_number,
		];
		$invoice_uid = sha1(implode('', $invoice_uid_parts));

		// invoice details and summaries
		$invoice_details = [];
		$summaries 		 = [];
		$summariesvat 	 = [];
		$rounded_nets 	 = [];

		// whether to include "incomeClassification" nodes
		$use_income_classf = (!empty($settings['params']['einv_inc_class_type']) && !empty($settings['params']['einv_inc_class_cat']));

		$is_package = (!empty($data[0]['pkg']));
		$isdue = 0;
		$extralinenum = 0;
		$discountval = 0;
		if ($data[0]['id'] < 0 && isset($this->externalData['einvrawcont'])) {
			// custom (manual) invoice, get the raw content of the invoice
			foreach ($this->externalData['einvrawcont']['rows'] as $ind => $row) {
				if (!isset($summariesvat[$row['aliq']])) {
					$summariesvat[$row['aliq']] = array('net' => 0, 'tax' => 0);
					$rounded_nets[$row['aliq']] = 0;
				}
				$summariesvat[$row['aliq']]['net'] += $row['net'];
				$summariesvat[$row['aliq']]['tax'] += $row['tax'];
				$rounded_nets[$row['aliq']] += (float)number_format($row['net'], 2, '.', '');

				// income classification
				$inc_classf_nodes = '';
				if ($use_income_classf) {
					$inc_classf_nodes = '<incomeClassification>
						<N1:classificationType>' . $settings['params']['einv_inc_class_type'] . '</N1:classificationType>
						<N1:classificationCategory>' . $settings['params']['einv_inc_class_cat'] . '</N1:classificationCategory>
						<N1:amount>' . number_format($row['net'], 2, '.', '') . '</N1:amount>
					</incomeClassification>';
				}

				// push invoice details node
				$vat_category = VikBookingMydataAadeConstants::getVatCategory($row['aliq']);
				array_push($invoice_details, '
				<invoiceDetails>
					<lineNumber>' . ($ind + 1) . '</lineNumber>
					<netValue>' . number_format($row['net'], 2, '.', '') . '</netValue>
					<vatCategory>' . $vat_category . '</vatCategory>
					<vatAmount>' . number_format($row['tax'], 2, '.', '') . '</vatAmount>
					' . ((int)$row['aliq'] === 0 && !empty($settings['params']['vat_exempt_cat']) ? '<vatExemptionCategory>' . $settings['params']['vat_exempt_cat'] . '</vatExemptionCategory>' : '') . '
					<lineComments>'.$this->convertSpecials($row['service']).'</lineComments>
					' . $inc_classf_nodes . '
				</invoiceDetails>');

			}
		} else {
			// invoice for a regular booking
			$tars = $this->getBookingTariffs($data);

			// check discount (coupon and/or refund)
			$discount_nodes = '';
			if (isset($data[0]['coupon']) && strlen($data[0]['coupon']) > 0) {
				$expcoupon = explode(";", $data[0]['coupon']);
				$discountval += (float)$expcoupon[1];
			}
			if (isset($data[0]['refund']) && $data[0]['refund'] > 0) {
				$discountval += $data[0]['refund'];
			}
			if ($discountval > 0) {
				$discount_nodes = '
				<discountOption>true</discountOption>
				<deductionsAmount>' . number_format($discountval, 2, '.', '') . '</deductionsAmount>';
			}

			foreach ($data as $kor => $or) {
				$num = $kor + 1;

				if ($correlated) {
					// push invoice details node
					array_push($invoice_details, '
				<invoiceDetails>
					<lineNumber>1</lineNumber>
					<netValue>0.00</netValue>
					<vatCategory>8</vatCategory>
					<vatAmount>0.00</vatAmount>
					<incomeClassification>
						<N1:classificationCategory>category1_95</N1:classificationCategory>
						<N1:amount>0</N1:amount>
					</incomeClassification>
				</invoiceDetails>
				<taxesTotals>
					<taxes>
						<taxType>3</taxType>
						<taxCategory>10</taxCategory>
						<underlyingValue>' . number_format($or['total'], 2, '.', '') . '</underlyingValue>
						<taxAmount>' . number_format($this->environmental_fee_details['fee_cost'], 2, '.', '') . '</taxAmount>
					</taxes>
				</taxesTotals>');
					// break the loop for the single environmental fee
					break;
				}

				if ($is_package || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
					// package cost or cust_cost may not be inclusive of taxes if prices tax included is off
					$descr = $is_package ? sprintf(VikBookingMydataAadeConstants::DESCRPACKAGENIGHTS, $or['days']) : sprintf(VikBookingMydataAadeConstants::DESCRSTAYROOMNIGHTS, $or['days'], strtoupper($or['room_name']));
					$cost_minus_tax = VikBooking::sayPackageMinusIva($or['cust_cost'], $or['cust_idiva']);
					$cost_tax_amount = (VikBooking::sayPackagePlusIva($or['cust_cost'], $or['cust_idiva']) - $cost_minus_tax);
					$aliq = $this->getAliquoteById($or['cust_idiva']);
					if (!isset($summariesvat[$aliq])) {
						$summariesvat[$aliq] = array('net' => 0, 'tax' => 0);
						$rounded_nets[$aliq] = 0;
					}
					$summariesvat[$aliq]['net'] += $cost_minus_tax;
					$summariesvat[$aliq]['tax'] += $cost_tax_amount;
					$rounded_nets[$aliq] += (float)number_format($cost_minus_tax, 2, '.', '');

					// income classification
					$inc_classf_nodes = '';
					if ($use_income_classf) {
						$inc_classf_nodes = '<incomeClassification>
							<N1:classificationType>' . $settings['params']['einv_inc_class_type'] . '</N1:classificationType>
							<N1:classificationCategory>' . $settings['params']['einv_inc_class_cat'] . '</N1:classificationCategory>
							<N1:amount>' . number_format($cost_minus_tax, 2, '.', '') . '</N1:amount>
						</incomeClassification>';
					}

					// push invoice details node
					array_push($invoice_details, '
				<invoiceDetails>
					<lineNumber>' . ($num + $extralinenum) . '</lineNumber>
					<netValue>' . number_format($cost_minus_tax, 2, '.', '') . '</netValue>
					<vatCategory>' . VikBookingMydataAadeConstants::getVatCategory($aliq) . '</vatCategory>
					<vatAmount>' . number_format($cost_tax_amount, 2, '.', '') . '</vatAmount>
					' . ((int)$aliq === 0 && !empty($settings['params']['vat_exempt_cat']) ? '<vatExemptionCategory>' . $settings['params']['vat_exempt_cat'] . '</vatExemptionCategory>' : '') . '
					' . (($num + $extralinenum) == 1 ? $discount_nodes : '') . '
					<lineComments>' . $this->convertSpecials($descr) . '</lineComments>
					' . $inc_classf_nodes . '
				</invoiceDetails>');
				} elseif (isset($tars[$num]) && is_array($tars[$num])) {
					// regular tariff
					$descr = sprintf(VikBookingMydataAadeConstants::DESCRSTAYROOMNIGHTS, $or['days'], strtoupper($or['room_name']));
					$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
					$calctar = VikBooking::sayCostPlusIva($display_rate, $tars[$num]['idprice']);
					$aliq = $this->getAliquoteFromPriceId($tars[$num]['idprice']);
					$isdue += $calctar;
					if ($calctar == $display_rate) {
						$cost_minus_tax = VikBooking::sayCostMinusIva($display_rate, $tars[$num]['idprice']);
						$tax = ($display_rate - $cost_minus_tax);
					} else {
						$cost_minus_tax = $display_rate;
						$tax = ($calctar - $display_rate);
					}
					if (!isset($summariesvat[$aliq])) {
						$summariesvat[$aliq] = array('net' => 0, 'tax' => 0);
						$rounded_nets[$aliq] = 0;
					}
					$summariesvat[$aliq]['net'] += $cost_minus_tax;
					$summariesvat[$aliq]['tax'] += $tax;
					$rounded_nets[$aliq] += (float)number_format($cost_minus_tax, 2, '.', '');

					// income classification
					$inc_classf_nodes = '';
					if ($use_income_classf) {
						$inc_classf_nodes = '<incomeClassification>
							<N1:classificationType>' . $settings['params']['einv_inc_class_type'] . '</N1:classificationType>
							<N1:classificationCategory>' . $settings['params']['einv_inc_class_cat'] . '</N1:classificationCategory>
							<N1:amount>' . number_format($cost_minus_tax, 2, '.', '') . '</N1:amount>
						</incomeClassification>';
					}

					// push invoice details node
					array_push($invoice_details, '
				<invoiceDetails>
					<lineNumber>' . ($num + $extralinenum) . '</lineNumber>
					<netValue>' . number_format($cost_minus_tax, 2, '.', '') . '</netValue>
					<vatCategory>' . VikBookingMydataAadeConstants::getVatCategory($aliq) . '</vatCategory>
					<vatAmount>' . number_format($tax, 2, '.', '') . '</vatAmount>
					' . ((int)$aliq === 0 && !empty($settings['params']['vat_exempt_cat']) ? '<vatExemptionCategory>' . $settings['params']['vat_exempt_cat'] . '</vatExemptionCategory>' : '') . '
					' . (($num + $extralinenum) == 1 ? $discount_nodes : '') . '
					<lineComments>' . $this->convertSpecials($descr) . '</lineComments>
					' . $inc_classf_nodes . '
				</invoiceDetails>');
				}

				// room options
				if (!empty($or['optionals']) && !$correlated) {
					$stepo = explode(";", $or['optionals']);
					foreach ($stepo as $roptkey => $oo) {
						if (empty($oo)) {
							continue;
						}
						$stept = explode(":", $oo);
						$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=" . $this->dbo->quote($stept[0]) . ";";
						$this->dbo->setQuery($q);
						$actopt = $this->dbo->loadAssocList();
						if (!$actopt) {
							continue;
						}
						$chvar = '';
						if (!empty($actopt[0]['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
							$optagenames = VikBooking::getOptionIntervalsAges($actopt[0]['ageintervals']);
							$optagepcent = VikBooking::getOptionIntervalsPercentage($actopt[0]['ageintervals']);
							$optageovrct = VikBooking::getOptionIntervalChildOverrides($actopt[0], $or['adults'], $or['children']);
							$child_num 	 = VikBooking::getRoomOptionChildNumber($or['optionals'], $actopt[0]['id'], $roptkey, $or['children']);
							$optagecosts = VikBooking::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt[0]['ageintervals']);
							$agestept = explode('-', $stept[1]);
							$stept[1] = $agestept[0];
							$chvar = $agestept[1];
							if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
								// percentage value of the adults tariff
								if ($is_package || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
								} else {
									$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
									$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
								}
							} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
								// percentage value of room base cost
								if ($is_package || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
								} else {
									$display_rate = isset($tars[$num]['room_base_cost']) ? $tars[$num]['room_base_cost'] : (!empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost']);
									$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
								}
							}
							$actopt[0]['chageintv'] = $chvar;
							$actopt[0]['name'] .= ' ('.$optagenames[($chvar - 1)].')';
							$actopt[0]['quan'] = $stept[1];
							$realcost = (intval($actopt[0]['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $or['days'] * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
						} else {
							$actopt[0]['quan'] = $stept[1];
							// VBO 1.11 - options percentage cost of the room total fee
							if ($is_package || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
								$deftar_basecosts = $or['cust_cost'];
							} else {
								$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
							}
							$actopt[0]['cost'] = (int)$actopt[0]['pcentroom'] ? ($deftar_basecosts * $actopt[0]['cost'] / 100) : $actopt[0]['cost'];
							//
							$realcost = (intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $or['days'] * $stept[1]) : ($actopt[0]['cost'] * $stept[1]));
						}
						if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $realcost > $actopt[0]['maxprice']) {
							$realcost = $actopt[0]['maxprice'];
							if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
								$realcost = $actopt[0]['maxprice'] * $stept[1];
							}
						}
						if ($actopt[0]['perperson'] == 1) {
							$realcost = $realcost * $or['adults'];
						}
						$tmpopr = VikBooking::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
						$isdue += $tmpopr;
						// increase line number
						$extralinenum++;
						//
						$aliq = $this->getAliquoteById($actopt[0]['idiva']);
						if ($tmpopr == $realcost) {
							$opt_minus_tax = VikBooking::sayOptionalsMinusIva($realcost, $actopt[0]['idiva']);
							$tax = ($realcost - $opt_minus_tax);
						} else {
							$opt_minus_tax = $realcost;
							$tax = ($tmpopr - $realcost);
						}
						$descr = $actopt[0]['is_citytax'] == 1 ? VikBookingMydataAadeConstants::DESCRTOURISTTAX : sprintf(VikBookingMydataAadeConstants::DESCRROOMOPTION, strtoupper($actopt[0]['name']));
						if (!isset($summariesvat[$aliq])) {
							$summariesvat[$aliq] = array('net' => 0, 'tax' => 0);
							$rounded_nets[$aliq] = 0;
						}

						$summariesvat[$aliq]['net'] += $opt_minus_tax;
						$summariesvat[$aliq]['tax'] += $tax;
						$rounded_nets[$aliq] += (float)number_format($opt_minus_tax, 2, '.', '');

						// income classification
						$inc_classf_nodes = '';
						if ($use_income_classf) {
							$inc_classf_nodes = '<incomeClassification>
								<N1:classificationType>' . $settings['params']['einv_inc_class_type'] . '</N1:classificationType>
								<N1:classificationCategory>' . $settings['params']['einv_inc_class_cat'] . '</N1:classificationCategory>
								<N1:amount>' . number_format($opt_minus_tax, 2, '.', '') . '</N1:amount>
							</incomeClassification>';
						}

						// push invoice details node
						array_push($invoice_details, '
					<invoiceDetails>
						<lineNumber>' . ($num + $extralinenum) . '</lineNumber>
						<netValue>' . number_format($opt_minus_tax, 2, '.', '') . '</netValue>
						<vatCategory>' . VikBookingMydataAadeConstants::getVatCategory($aliq) . '</vatCategory>
						<vatAmount>' . number_format($tax, 2, '.', '') . '</vatAmount>
						' . ((int)$aliq === 0 && !empty($settings['params']['vat_exempt_cat']) ? '<vatExemptionCategory>' . $settings['params']['vat_exempt_cat'] . '</vatExemptionCategory>' : '') . '
						<lineComments>' . $this->convertSpecials($descr) . '</lineComments>
						' . $inc_classf_nodes . '
					</invoiceDetails>');
					}
				}

				// custom extra costs
				if (!empty($or['extracosts']) && !$correlated) {
					$cur_extra_costs = json_decode($or['extracosts'], true);
					foreach ($cur_extra_costs as $eck => $ecv) {
						// increase line number
						$extralinenum++;
						//
						$ecplustax = !empty($ecv['idtax']) ? VikBooking::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
						$isdue += $ecplustax;
						$descr = sprintf(VikBookingMydataAadeConstants::DESCRROOMEXTRACOST, strtoupper($ecv['name']));
						if ($ecplustax == $ecv['cost']) {
							$ec_minus_tax = !empty($ecv['idtax']) ? VikBooking::sayOptionalsMinusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
							$tax = ($ecv['cost'] - $ec_minus_tax);
						} else {
							$ec_minus_tax = ($ecplustax - $ecv['cost']);
							$tax = ($ecplustax - $ecv['cost']);
						}
						$aliq = $this->getAliquoteById($ecv['idtax']);
						if (!isset($summariesvat[$aliq])) {
							$summariesvat[$aliq] = array('net' => 0, 'tax' => 0);
							$rounded_nets[$aliq] = 0;
						}

						$summariesvat[$aliq]['net'] += $ec_minus_tax;
						$summariesvat[$aliq]['tax'] += $tax;
						$rounded_nets[$aliq] += (float)number_format($ec_minus_tax, 2, '.', '');

						// income classification
						$inc_classf_nodes = '';
						if ($use_income_classf) {
							$inc_classf_nodes = '<incomeClassification>
								<N1:classificationType>' . $settings['params']['einv_inc_class_type'] . '</N1:classificationType>
								<N1:classificationCategory>' . $settings['params']['einv_inc_class_cat'] . '</N1:classificationCategory>
								<N1:amount>' . number_format($ec_minus_tax, 2, '.', '') . '</N1:amount>
							</incomeClassification>';
						}

						/**
						 * Removed "quantity" and "measurementUnit" nodes from every "invoiceDetails" node.
						 * 
						 * <quantity>1.00</quantity>
						 * <measurementUnit>' . VikBookingMydataAadeConstants::DEFAULT_MEAS_UNIT . '</measurementUnit>
						 * 
						 * @since 	1.16.7 (J) - 1.6.7 (WP)
						 */

						// push invoice details node
						array_push($invoice_details, '
					<invoiceDetails>
						<lineNumber>' . ($num + $extralinenum) . '</lineNumber>
						<netValue>' . number_format($ec_minus_tax, 2, '.', '') . '</netValue>
						<vatCategory>' . VikBookingMydataAadeConstants::getVatCategory($aliq) . '</vatCategory>
						<vatAmount>' . number_format($tax, 2, '.', '') . '</vatAmount>
						' . ((int)$aliq === 0 && !empty($settings['params']['vat_exempt_cat']) ? '<vatExemptionCategory>' . $settings['params']['vat_exempt_cat'] . '</vatExemptionCategory>' : '') . '
						<lineComments>' . $this->convertSpecials($descr) . '</lineComments>
						' . $inc_classf_nodes . '
					</invoiceDetails>');
					}
				}
			}
		}

		// build riepiloghi IVA
		$grand_total_net 		  = 0;
		$grand_total_vat 		  = 0;
		$grand_total_tax_no_rate  = 0;
		$environmental_fee_amount = $correlated ? $this->environmental_fee_details['fee_cost'] : 0;
		foreach ($summariesvat as $aliq => $vat_summary) {
			$totnet = number_format($vat_summary['net'], 2, '.', '');
			$tottax = number_format($vat_summary['tax'], 2, '.', '');
			if (isset($rounded_nets[$aliq]) && (float)$totnet != $rounded_nets[$aliq]) {
				/**
				 * In case of several rows in the invoice, maybe a lot of Extra Services,
				 * there can be a discrepancy between the sum of the <netValue> nodes
				 * in the <invoiceDetails> nodes, and the <taxAmount> in <taxesTotals>.
				 * We need to prevent the amounts to be different because of number_format and
				 * adjust the amounts and obtain the same value as the sum of the nets in the lines.
				 * The issue was reproduced with 9 Extra Services, one Room, one Tourist Tax (Option).
				 * 
				 * @see 	sandbox booking ID 1140
				 */
				if ($rounded_nets[$aliq] > (float)$totnet) {
					$diff = $rounded_nets[$aliq] - (float)$totnet;
					$totnet = number_format($rounded_nets[$aliq], 2, '.', '');
					$tottax = number_format(((float)$tottax - $diff), 2, '.', '');
				} else {
					$diff = (float)$totnet - $rounded_nets[$aliq];
					$totnet = number_format($rounded_nets[$aliq], 2, '.', '');
					$tottax = number_format(((float)$tottax + $diff), 2, '.', '');
				}
			}

			// sum grand total values
			$grand_total_net += $totnet;
			if ((int)$aliq > 0) {
				$grand_total_vat += $tottax;
			} else {
				/**
				 * @todo  are we doing good by summing this kind of tax, which is not VAT because
				 * 		  the tax rate is 0%, to the "total withheld amount"? Or should we use the
				 *  	  node <totalOtherTaxesAmount> instead?
				 */
				$grand_total_tax_no_rate += $tottax;
			}

			/**
			 * For the moment we ingore completely the <taxesTotals> node and sub-nodes. Docs say:
			 * "Field taxesTotals contains all taxes except VAT. If user users this element,
			 * taxes will not exist in invoiceDetails".
			 * However, here we have a sum of tax amounts for any aliquote (tax rate) involved.
			 * 
			 * @todo  check if these nodes should be somehow composed even if they are optional.
			 */
		}

		/**
		 * Address element is forbidden for issuer from Greece.
		 */
		$issuer_address_nodes = '';
		if (strcasecmp($settings['params']['country'], 'GR')) {
			// issuer not from Greece, compose address
			$issuer_address_nodes = '<address>
				<street>' . $this->convertSpecials($settings['params']['address']) . '</street>
				<number>' . $settings['params']['streetnumber'] . '</number>
				<postalCode>' . $settings['params']['zip'] . '</postalCode>
				<city>' . $this->convertSpecials($settings['params']['city']) . '</city>
			</address>';
		}

		// total income classification
		$inc_classf_nodes = '';
		if ($use_income_classf) {
			if ($correlated) {
				$inc_classf_nodes = '<incomeClassification>
				<N1:classificationCategory>category1_95</N1:classificationCategory>
				<N1:amount>' . number_format(0, 2, '.', '') . '</N1:amount>
			</incomeClassification>';
			} else {
				$inc_classf_nodes = '<incomeClassification>
				<N1:classificationType>' . $settings['params']['einv_inc_class_type'] . '</N1:classificationType>
				<N1:classificationCategory>' . $settings['params']['einv_inc_class_cat'] . '</N1:classificationCategory>
				<N1:amount>' . number_format($grand_total_net, 2, '.', '') . '</N1:amount>
			</incomeClassification>';
			}
		}

		// build XML
		$root_namespaces = VikBookingMydataAadeConstants::getInvoiceNamespaceAttributes();
		$xml = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<InvoicesDoc ' . $root_namespaces . '>
	<invoice>
		<uid>' . $invoice_uid . '</uid>
		<mark>' . ($correlated ? $correlated_invnum : $settings['progcount']) . '</mark>
		<issuer>
			<vatNumber>' . $settings['params']['vatid'] . '</vatNumber>
			<country>' . $settings['params']['country'] . '</country>
			<branch>0</branch>
			' . (strcasecmp($settings['params']['country'], 'GR') ? '<name>' . $this->convertSpecials($settings['params']['companyname']) . '</name>' : '') . '
			' . (!empty($issuer_address_nodes) ? $issuer_address_nodes : '') . '
		</issuer>
		<counterpart>
			' . (!empty($data[0]['customer']['vat']) ? '<vatNumber>' . $this->convertSpecials($data[0]['customer']['vat']) . '</vatNumber>' : '') . '
			' . (!empty($data[0]['customer']['country_2_code']) ? '<country>' . $this->convertSpecials($data[0]['customer']['country_2_code']) . '</country>' : '') . '
			<branch>' . $branch . '</branch>
			' . (!empty($client_name) ? '<name>' . $this->convertSpecials($client_name) . '</name>' : '') . '
			<address>
				<street>' . $this->convertSpecials(preg_replace("/[0-9]/", '', $data[0]['customer']['address'])) . '</street>
				<number>' . preg_replace("/[^0-9]/", '', $data[0]['customer']['address']) . '</number>
				<postalCode>' . $this->convertSpecials($data[0]['customer']['zip']) . '</postalCode>
				<city>' . $this->convertSpecials($data[0]['customer']['city']) . '</city>
			</address>
		</counterpart>
		<invoiceHeader>
			<series>' . $series . '</series>
			<aa>' . $aa_serial_number . '</aa>
			<issueDate>' . $invdate . '</issueDate>
			<invoiceType>' . $invtype . '</invoiceType>
			<currency>' . VikBooking::getCurrencyName() . '</currency>
			' . ($correlated ? '<correlatedInvoices>{main_invoice_mark}</correlatedInvoices>' : '') . '
		</invoiceHeader>
		<paymentMethods>
			<paymentMethodDetails>
				<type>' . $settings['params']['einv_paymethod'] . '</type>
				<amount>' . number_format($inv_tot_paid, 2, '.', '') . '</amount>
				' . (!empty($inv_pay_method) ? '<paymentMethodInfo>' . $this->convertSpecials($inv_pay_method) . '</paymentMethodInfo>' : '') . '
			</paymentMethodDetails>
		</paymentMethods>
		' . implode("\n", $invoice_details) . '
		<invoiceSummary>
			<totalNetValue>' . number_format($grand_total_net, 2, '.', '') . '</totalNetValue>
			<totalVatAmount>' . number_format($grand_total_vat, 2, '.', '') . '</totalVatAmount>
			<totalWithheldAmount>' . number_format($grand_total_tax_no_rate, 2, '.', '') . '</totalWithheldAmount>
			<totalFeesAmount>0.00</totalFeesAmount>
			<totalStampDutyAmount>0.00</totalStampDutyAmount>
			<totalOtherTaxesAmount>' . number_format($environmental_fee_amount, 2, '.', '') . '</totalOtherTaxesAmount>
			<totalDeductionsAmount>' . number_format(($correlated ? 0 : $discountval), 2, '.', '') . '</totalDeductionsAmount>
			<totalGrossValue>' . number_format(($correlated ? $this->environmental_fee_details['fee_cost'] : $data[0]['total']), 2, '.', '') . '</totalGrossValue>
			' . $inc_classf_nodes . '
		</invoiceSummary>
	</invoice>
</InvoicesDoc>';

		// attempt to properly format the XML string
		$this->formatXmlString($xml);

		if ($correlated) {
			// update driver setting
			$this->updateDriverSetting('envfeeinvoiceinum', $correlated_invnum);

			// return the raw XML for the correlated invoice just built
			return $xml;
		}

		// check if we need to validate the XML against the official schema
		if (!empty($settings['params']['schema_validate'])) {
			/**
			 * It may not be possible to validate the XML against the schema, as on
			 * some environments this process may run out of execution time.
			 */
			try {
				$schema_validation = $this->validateXmlAgainstSchema($xml);
				if ($schema_validation === null) {
					// display warning
					$this->setWarning('Missing PHP libraries for DOMDocument to validate the XML invoice against the official schema.');
				}
			} catch (Exception $e) {
				// display warning
				$this->setWarning('Could not validate the XML invoice against the official Schema - process failed with no response.');
			}
		}

		if ($this->debugging()) {
			$this->setWarning('<pre>'.htmlentities($xml).'</pre><br/>');
			// break the process when in debug mode
			return false;
		}

		// we proceed with the generation

		// invoice name (transmission date-time string + auto-increment registration value just for our internal purpose)
		$einvname = date('YmdHis') . '_' . $settings['progcount'] . '.xml';

		// get current datetime object in local format
		$date_obj = JFactory::getDate();
		$date_obj->setTimezone(new DateTimeZone(date_default_timezone_get()));

		// prepare object for storing the invoice
		$einvobj = new stdClass;
		$einvobj->driverid = $settings['id'];
		$einvobj->created_on = $date_obj->toSql($local = true);
		$einvobj->for_date = $invdate;
		$einvobj->filename = $einvname;
		$einvobj->number = $invnum;
		$einvobj->idorder = $data[0]['id'];
		$einvobj->idcustomer = !empty($data[0]['customer']['id']) ? $data[0]['customer']['id'] : 0;
		$einvobj->country = !empty($data[0]['customer']['country']) ? $data[0]['customer']['country'] : null;
		// this column is not needed in this driver, but we give it a default value
		$einvobj->recipientcode = '';
		$einvobj->xml = $xml;
		// always reset transmitted and obliterated values for new e-invoices
		$einvobj->transmitted = 0;
		$einvobj->obliterated = 0;

		$newinvid = $this->storeEInvoice($einvobj);
		if ($newinvid === false) {
			$this->setError('Error storing the electronic invoice for the reservation ID '.$data[0]['id']);
			return false;
		}

		if ($canbeinvoiced < 0) {
			// log event history when regenerating an e-invoice
			VikBooking::getBookingHistoryInstance()->setBid($data[0]['id'])->store('BI', ($this->getName() . ' #' . $invnum));
		}

		// update settings before generating the analogic invoice in PDF format to prevent exceptions to be thrown or exit/die calls.
		// update configuration setting for VikBooking::getNextInvoiceNumber()
		if ($data[0]['id'] > 0) {
			// we exclude custom (manual) invoices which would have a booking ID set to -number
			$this->updateInvoiceNumber($invnum);
		}
		// update auto-increment driver setting by increasing it for the next run
		$this->updateProgressiveNumber(++$settings['progcount']);

		/**
		 * Check if we should generate another, correlated, invoice.
		 */
		if (!$correlated && $this->environmental_fee_details) {
			// re-call the same method to generate the invoice for the environmental fee
			$correlated_inv_xml = $this->generateEInvoice($data, true);
			if ($correlated_inv_xml) {
				// let the method store the raw XML for the correlated invoice
				$this->prepareCorrelatedInvoice($einvobj, $data, $correlated_inv_xml);
			}
		}

		if (!$correlated && !$this->hasAnalogicInvoice($data[0]['id'])) {
			// no analogic invoice in PDF available, so we create it
			if (!$this->generateAnalogicInvoice($data[0]['id'], $invnum, $invdate)) {
				// raise warning in case of error
				$this->setWarning('It was not possible to generate the courtesy PDF version of the invoice for the reservation ID '.$data[0]['id']);
			}
		}

		return true;
	}

	/**
	 * Builds the param name to read the correlated e-invoice data from the db settings.
	 * 
	 * @param 	int 	$einv_id 	 the e-invoice record ID.
	 * @param 	int 	$booking_id  the reservation record ID.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public function getCorrelatedInvoiceParamName($einv_id, $booking_id)
	{
		$driver_id = $this->getDriverId();

		return "envfee_invoice_{$driver_id}_{$einv_id}_{$booking_id}";
	}

	/**
	 * Stores a record with the information to create the environmental fee invoice (correlated invoice).
	 * The actual invoice will be created upon transmitting the main one for the reservation because
	 * the environmental fee invoice requires the correlated number to be the Mark of the main invoice.
	 * 
	 * @param 	object 	$einvobj 	the main e-invoice record.
	 * @param 	array 	$data 		the nested booking record.
	 * @param 	string 	$xml 		the raw XML generated.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public function prepareCorrelatedInvoice($einvobj, array $data, $xml)
	{
		if (!is_object($einvobj) || empty($einvobj->id) || !$data) {
			return false;
		}

		$booking_id = $data[0]['id'];
		$einv_id 	= $einvobj->id;
		$driver_id  = $this->getDriverId();

		$config_param_name = $this->getCorrelatedInvoiceParamName($einv_id, $booking_id);

		$config_param_value = [
			'bid' 	 => $booking_id,
			'einvid' => $einv_id,
			'envfee' => $this->environmental_fee_details,
			'xml' 	 => $xml,
		];

		VBOFactory::getConfig()->set($config_param_name, $config_param_value);

		return true;
	}

	/**
	 * Attempts to get the previous correlated invoice number.
	 * Useful when re-generating an invoice already transmitted.
	 * 
	 * @param 	int 	$einv_id 	the main invoice ID.
	 * @param 	int 	$bid 		the VBO booking ID.
	 * @param 	string 	$type 		the type of data to fetch.
	 * 
	 * @return 	string|array
	 */
	public function getPreviousCorrelatedInvoiceData($einv_id, $bid, $type = '')
	{
		$driver_id = $this->getDriverId();

		$correlated_inv_raw_data = VBOFactory::getConfig()->getArray($this->getCorrelatedInvoiceParamName($einv_id, $bid), []);

		if (!$correlated_inv_raw_data || empty($correlated_inv_raw_data['xml'])) {
			return '';
		}

		$xml_inv = simplexml_load_string($correlated_inv_raw_data['xml']);

		if (!$xml_inv) {
			return '';
		}

		if (!strcasecmp($type, 'date')) {
			// previous invoice date
			return (string)$xml_inv->invoice->invoiceHeader->issueDate;
		}

		if (!strcasecmp($type, 'xml')) {
			// return the plain XML
			return $correlated_inv_raw_data['xml'];
		}

		if (!strcasecmp($type, 'record')) {
			// return the whole array record
			return $correlated_inv_raw_data;
		}

		if (!strcasecmp($type, 'transmission')) {
			// return the whole transmission array, if available
			return $correlated_inv_raw_data['transmission'] ?? [];
		}

		// default to previous invoice number
		return (string)$xml_inv->invoice->mark;
	}

	/**
	 * Manipulates the previously created correlated invoice by adding the proper invoice mark,
	 * then transmits the second e-invoice to myDATA.
	 * 
	 * @param 	object 	$main_invoice_data 	the main e-invoice transaction information object.
	 * @param 	array 	$env_fee_data 		the prepared data for the correlated invoice and fee.
	 * @param 	array 	$extras 			associative array to pass extra information.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public function transmitCorrelatedInvoice($main_invoice_data, array $env_fee_data, array $extras)
	{
		// first off, set the proper correlated invoice number by using the mark

		// we use a regex that will capture two groups to avoid problems with the number-value for replacement
		$correlated_einv_final_xml = preg_replace("/<(correlatedInvoices)>(\{[a-z_]*\})?<\/correlatedInvoices>/i", '<$1>' . $main_invoice_data->invoice_mark . '</$1>', $env_fee_data['xml']);

		// transmit the XML correlated e-invoice to myDATA
		$response = $this->myDATARequestPOST('SendInvoices', $correlated_einv_final_xml);
		if ($response->code != 200) {
			// the request was not successful, and the XML invoices were not parsed at all by myDATA
			$this->setError(sprintf('Correlated e-invoice - Invalid response (code %s): %s', $response->code, htmlspecialchars($response->body)));
			$this->setError('Correlated e-invoice - Could not send the invoice to myDATA.');
			return false;
		}

		// process the transmission response
		$res_obj = simplexml_load_string($response->body);
		if (!is_object($res_obj) || !isset($res_obj->response)) {
			$this->setError('Could not parse XML response');
			$this->setError('<pre>' . htmlentities($response->body) . '</pre>');
			return false;
		}

		if (!isset($res_obj->response->statusCode)) {
			$this->setError('Unexpected nodes in XML response (missing statusCode)');
			$this->setError('<pre>' . htmlentities($response->body) . '</pre>');
			return false;
		}

		// check if we have a successful status code for this invoice
		if (!strcasecmp((string)$res_obj->response->statusCode, 'Success')) {
			// get the invoice UID
			$invoice_uid = isset($res_obj->response->invoiceUid) ? (string)$res_obj->response->invoiceUid : null;

			// get the invoice mark (needed for a later cancellation)
			$invoice_mark = isset($res_obj->response->invoiceMark) ? (string)$res_obj->response->invoiceMark : null;

			// get the invoice QRCode URL
			$invoice_qrcode = isset($res_obj->response->qrUrl) ? (string)$res_obj->response->qrUrl : null;
			$invoice_qrcode = !isset($res_obj->response->qrUrl) && isset($res_obj->response->qrCodeUrl) ? (string)$res_obj->response->qrCodeUrl : $invoice_qrcode;

			// prepare data transmission values to be updated
			$config_param_name   = $this->getCorrelatedInvoiceParamName($extras['einvid'], $extras['bid']);
			$env_fee_data['xml'] = $correlated_einv_final_xml;
			$env_fee_data['transmission'] = [
				'ts'    	 => time(),
				'uid'   	 => $invoice_uid,
				'mark'  	 => $invoice_mark,
				'qrurl' 	 => $invoice_qrcode,
				'qrcode_img' => '',
				'pdf'   	 => '',
			];

			if ($invoice_qrcode) {
				// generate the QR Code for the environmental fee invoice as well

				// the QR Code PNG file name
				$filename = "aade_qrcode_env_{$extras['bid']}_{$extras['einvid']}.png";

				if ($this->generateQRCodeImage($invoice_qrcode, VikBookingMydataAadeConstants::getQRCodeBase('path', $filename))) {
					// set the QR Code image property
					$env_fee_data['transmission']['qrcode_img'] = $filename;
				}
			}

			// immediately update data transmission values before generating the PDF invoice
			VBOFactory::getConfig()->set($config_param_name, $env_fee_data);

			// generate the PDF (courtesy) for the correlated invoice (will set the PDF path in case of success)
			if ($this->generateAnalogicEnvFeeInvoice($env_fee_data)) {
				// update data transmission values again, as they will contain the path to the PDF file
				VBOFactory::getConfig()->set($config_param_name, $env_fee_data);
			}

			return true;
		}

		// at this point we expect an error
		if (!isset($res_obj->response->errors) || !isset($res_obj->response->errors->error)) {
			// errors should be set, but if they aren't, this is unexpected
			$this->setError('Unexpected nodes in XML response (missing errors or error)');
			$this->setError('<pre>' . htmlentities($response->body) . '</pre>');
			return false;
		}

		// loop through the errors
		foreach ($res_obj->response->errors->error as $resp_err) {
			$err_code = isset($resp_err->code) ? (string)$resp_err->code : '0';
			$err_mess = isset($resp_err->message) ? (string)$resp_err->message : '???';
			$this->setError(sprintf('Error (%s): %s', $err_code, $err_mess));
		}

		return false;
	}

	/**
	 * Generates a PDF file for the correlated invoice for the environmental fee.
	 * 
	 * @param 	array 	&$env_fee_data 	the raw environmental fee information data.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public function generateAnalogicEnvFeeInvoice(&$env_fee_data)
	{
		// get the customer information
		$customer = VikBooking::getCPinInstance()->getCustomerFromBooking($env_fee_data['bid']);

		// build a dummy invoice associative array with the information required
		$invoice = [
			'id' 	   => -1,
			'number'   => $this->getPreviousCorrelatedInvoiceData($env_fee_data['einvid'], $env_fee_data['bid']),
			'for_date' => strtotime($this->getPreviousCorrelatedInvoiceData($env_fee_data['einvid'], $env_fee_data['bid'], 'date')),
			'rawcont'  => [
				'totalnet' => 0,
				'totaltax' => $env_fee_data['envfee']['fee_cost'],
				'totaltot' => $env_fee_data['envfee']['fee_cost'],
				'rows' 	   => [
					[
						'service' => $env_fee_data['envfee']['name'],
						'net' 	  => 0,
						'tax' 	  => $env_fee_data['envfee']['fee_cost'],
						'tot' 	  => $env_fee_data['envfee']['fee_cost'],
					],
				],
			],
			'env_fee_data' => $env_fee_data,
			'feeseries'    => 'C',
		];

		// load the custom invoice template file
		list($invoice_tmpl, $pdfparams) = VikBooking::loadCustomInvoiceTmpl($invoice, $customer);

		// trigger an event to allow third-party plugins to manipulate the content of the custom invoice
		VBOFactory::getPlatform()->getDispatcher()->trigger('onMydataBeforeGenerateEnvFeeCourtesyInvoice', [$env_fee_data, $invoice, $invoice_tmpl]);

		// parse the content of the template file
		$invoice_body = VikBooking::parseCustomInvoiceTemplate($invoice_tmpl, $invoice, $customer);

		// reload booking details
		$booking_details = VikBooking::getBookingInfoFromID($env_fee_data['bid']);

		// force the execution of the conditional text rules
		VikBooking::getConditionalRulesInstance()
			->set(
				[
					'booking',
					'rooms',
				], 
				[
					$booking_details,
					VikBooking::loadOrdersRoomsData($env_fee_data['bid']),
				]
			)
			->parseTokens($invoice_body);

		// load dependencies
		if (!class_exists('TCPDF')) {
			require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . 'tcpdf.php');
		}
		$usepdffont = is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . "fonts" . DIRECTORY_SEPARATOR . "dejavusans.php") ? 'dejavusans' : 'helvetica';

		/**
		 * Trigger event to allow third party plugins to return a specific font name.
		 */
		$custom_pdf_font = VBOFactory::getPlatform()->getDispatcher()->filter('onGetPdfFontNameVikBooking', [$usepdffont]);
		if (is_array($custom_pdf_font) && !empty($custom_pdf_font[0])) {
			$usepdffont = $custom_pdf_font[0];
		}

		// write the PDF on file
		$pdffname = implode('_', ['envfee', $booking_details['id'], ($booking_details['sid'] ?: $booking_details['ts'])]) . '.pdf';
		$pathpdf = VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "invoices" . DIRECTORY_SEPARATOR . "generated" . DIRECTORY_SEPARATOR . $pdffname;

		if (is_file($pathpdf)) {
			@unlink($pathpdf);
		}

		$pdf_page_format = is_array($pdfparams['pdf_page_format']) ? $pdfparams['pdf_page_format'] : constant($pdfparams['pdf_page_format']);

		$pdf = new TCPDF(constant($pdfparams['pdf_page_orientation']), constant($pdfparams['pdf_unit']), $pdf_page_format, true, 'UTF-8', false);
		$pdf->SetTitle(JText::translate('VBOINVNUM') . ' ' . $invoice['number']);

		// header for each page of the pdf
		if ($pdfparams['show_header'] == 1 && count($pdfparams['header_data']) > 0) {
			$pdf->SetHeaderData($pdfparams['header_data'][0], $pdfparams['header_data'][1], $pdfparams['header_data'][2], $pdfparams['header_data'][3], $pdfparams['header_data'][4], $pdfparams['header_data'][5]);
		}

		// change some currencies to their unicode (decimal) value
		$currencyname = VikBooking::getCurrencyName();
		$unichr_map = array('EUR' => 8364, 'USD' => 36, 'AUD' => 36, 'CAD' => 36, 'GBP' => 163);
		if (array_key_exists($currencyname, $unichr_map)) {
			$invoice_body = str_replace($currencyname, TCPDF_FONTS::unichr($unichr_map[$currencyname]), $invoice_body);
		}

		// header and footer fonts
		$pdf->setHeaderFont(array($usepdffont, '', $pdfparams['header_font_size']));
		$pdf->setFooterFont(array($usepdffont, '', $pdfparams['footer_font_size']));

		// margins
		$pdf->SetMargins(constant($pdfparams['pdf_margin_left']), constant($pdfparams['pdf_margin_top']), constant($pdfparams['pdf_margin_right']));
		$pdf->SetHeaderMargin(constant($pdfparams['pdf_margin_header']));
		$pdf->SetFooterMargin(constant($pdfparams['pdf_margin_footer']));

		$pdf->SetAutoPageBreak(true, constant($pdfparams['pdf_margin_bottom']));
		$pdf->setImageScale(constant($pdfparams['pdf_image_scale_ratio']));
		$pdf->SetFont($usepdffont, '', (int)$pdfparams['body_font_size']);

		if ($pdfparams['show_header'] == 0 || !$pdfparams['header_data']) {
			$pdf->SetPrintHeader(false);
		}
		if ($pdfparams['show_footer'] == 0) {
			$pdf->SetPrintFooter(false);
		}

		$pdf->AddPage();
		$pdf->writeHTML($invoice_body, true, false, true, false, '');
		$pdf->lastPage();
		$pdf->Output($pathpdf, 'F');

		if (!is_file($pathpdf)) {
			return false;
		}

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly - trigger files mirroring
			 */
			VikBookingLoader::import('update.manager');
			VikBookingUpdateManager::triggerUploadBackup($pathpdf);
		}

		// set the PDF file name at last
		$env_fee_data['transmission']['pdf'] = $pdffname;

		return true;
	}

	/**
	 * Returns the calculated tariffs given their IDs per room booked.
	 *
	 * @param 	array 	$booking 	the booking array with one array-room per array value
	 *
	 * @return 	array 				associative array of tariffs for each room booked
	 */
	protected function getBookingTariffs($booking)
	{
		$tars = [];

		$is_package = (!empty($booking[0]['pkg']));

		foreach ($booking as $kor => $or) {
			$num = $kor + 1;
			if ($is_package || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
				// package or custom cost set from the back-end does not need calculation
				continue;
			}
			$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `id`=".(int)$or['idtar'].";";
			$this->dbo->setQuery($q);
			$tar = $this->dbo->loadAssocList();
			if ($tar) {
				$tar = VikBooking::applySeasonsRoom($tar, $or['checkin'], $or['checkout']);

				// apply OBP rules
				$tar = VBORoomHelper::getInstance()->applyOBPRules($tar, $or, $or['adults']);

				$tars[$num] = $tar[0];
			}
		}

		return $tars;
	}

	/**
	 * Transmits the electronic invoices to myDATA according to the input parameters.
	 * This is a 'driver action', and so it's called before getBookingsData()
	 * in the view. This method will save/update records in the DB so that when
	 * the view re-calls getBookingsData(), the information will be up to date.
	 *
	 * @return 	boolean 	True if at least one e-invoice was transmitted
	 */
	public function transmitEInvoices()
	{
		// make sure the transmission settings are not empty
		$settings = $this->loadSettings();
		if ($settings === false || !$settings['params']) {
			$this->setError('Missing settings to transmit the invoices. Please set up the driver settings first.');
			return false;
		}
		// make sure the settings we need are not empty
		$required = [
			$settings['params']['aade_user_id'],
			$settings['params']['aade_subscription_key'],
		];
		foreach ($required as $reqset) {
			if (empty($reqset)) {
				$this->setError('Invalid settings to transmit the invoices. Please make sure to provide all the information from the driver settings.');
				return false;
			}
		}

		// call the main method to generate rows, cols and bookings array
		$this->getBookingsData();

		if ($this->getError() || !$this->bookings) {
			return false;
		}

		// get the driver ID
		$driver_id = $this->getDriverId();

		// pool of e-invoice IDs to transmit
		$einvspool = [];
		$einvnumbs = [];

		// electronic invoices IDs referenced to booking IDs
		$einvs_bids_ref = [];

		foreach ($this->bookings as $gbook) {
			// check whether this booking ID was set to be skipped from transmission
			$exclude = VikRequest::getInt('excludesendbid'.$gbook[0]['id'], 0, 'request');
			if ($exclude > 0) {
				// skipping this invoice from transmission
				continue;
			}

			// make sure an electronic invoice was already issued for this booking ID by this driver
			if (empty($gbook[0]['einvid']) || $gbook[0]['einvdriver'] != $this->getDriverId()) {
				// no e-invoices available for this booking, skipping
				continue;
			}

			// check if an e-invoice was already sent for this booking
			if ($gbook[0]['einvsent'] > 0) {
				$resend = VikRequest::getInt('resendbid'.$gbook[0]['id'], 0, 'request');
				if (!($resend > 0)) {
					// we do not re-send the invoice for this booking ID
					continue;
				}
			}

			// push e-invoice ID to the pool
			array_push($einvspool, $gbook[0]['einvid']);

			// push also the corresponding invoice number
			array_push($einvnumbs, $gbook[0]['einvnum']);

			// set the e-invoice ID/booking ID relation
			$einvs_bids_ref[$gbook[0]['einvid']] = $gbook[0]['id'];
		}

		if (!$einvspool) {
			// no e-invoices generated or ready to be transmitted
			$this->setWarning('No e-invoices generated or ready to be transmitted to myDATA. Please generate first the XML invoices or select some for the re-transmission.');
			return false;
		}

		// build one XML file for all XML e-invoices (if more than one)
		$einv_xml_body = $this->buildTransmissionXMLBody($einvspool, $settings);
		if ($einv_xml_body === false) {
			// something went wrong with the creation of the XML file
			$this->setError('Error creating the XML file for the request. Unable to proceed.');
			return false;
		}

		if ($this->debugging()) {
			// when in debug mode, the raw XML request is sent to output
			$this->setWarning('Raw XML request for Debug Mode');
			$this->setWarning('<pre> ' . htmlentities($einv_xml_body) . ' </pre>');
		}

		// transmit e-invoices to myDATA
		$response = $this->myDATARequestPOST('SendInvoices', $einv_xml_body, $settings);
		if ($response->code != 200) {
			// the request was not successful, and the XML invoices were not parsed at all by myDATA
			$this->setError(sprintf('Invalid response (code %s): %s', $response->code, htmlspecialchars($response->body)));
			$this->setError('Could not send the invoice(s) to myDATA.');
			return false;
		}

		if ($this->debugging()) {
			// when in debug mode, the raw XML response is sent to output
			$this->setWarning('Raw XML response for Debug Mode');
			$this->setWarning('<pre> ' . htmlentities($response->body) . ' </pre>');
		}

		// check if the XML response contains errors, and adjust the e-invoices that succeeded
		list($success, $valid_einv_marks, $valid_einv_uids, $valid_qrcode_urls) = $this->myDATAParseXMLResponse($response->body, $einvspool, $einvnumbs);

		if (!$success) {
			// some errors occurred
			if (!is_array($valid_einv_marks) || !$valid_einv_marks) {
				$this->setError('Could not send the invoice(s) to myDATA.');
				return false;
			} else {
				// some e-invoices were transmitted successfully
				$einvspool = array_keys($valid_einv_marks);
			}
		}

		// update ProgressivoInvio driver setting by increasing it for the next run
		$this->updateProgressiveNumber(++$settings['progcount']);

		// set to transmitted=1 all e-invoice IDs that were transmitted with success
		foreach ($einvspool as $einvid) {
			// find the corresponding booking ID
			$einv_bid = $einvs_bids_ref[$einvid] ?? 0;

			// flag to check if the PDF invoice should be refreshed
			$qrcode_fname = null;

			// prepare "transmission data" object
			$trans_data = new stdClass;
			$trans_data->invoice_uid    = (isset($valid_einv_uids[$einvid]) && $einvid != $valid_einv_uids[$einvid] ? $valid_einv_uids[$einvid] : null);
			$trans_data->invoice_mark   = (isset($valid_einv_marks[$einvid]) && $einvid != $valid_einv_marks[$einvid] ? $valid_einv_marks[$einvid] : null);
			$trans_data->invoice_qrcode = (isset($valid_qrcode_urls[$einvid]) && $einvid != $valid_qrcode_urls[$einvid] ? $valid_qrcode_urls[$einvid] : null);
			$trans_data->qrcode_img 	= null;
			$trans_data->trans_dtime    = date('Y-m-d H:i:s');

			if ($trans_data->invoice_qrcode) {
				/**
				 * Attempt to generate the QR Code image file for the current invoice correctly transmitted.
				 * 
				 * @since 	1.16.7 (J) - 1.6.7 (WP)
				 */
				$qrcode_fname = $this->generateInvoiceQRCode($einvid, $einv_bid, $trans_data);
				if ($qrcode_fname) {
					$trans_data->qrcode_img = $qrcode_fname;
				}
			}

			// build e-invoice object for update (with "transmission data")
			$data = new stdClass;
			$data->id = $einvid;
			$data->transmitted = 1;
			$data->trans_data = json_encode($trans_data);

			// update e-invoice record
			$this->updateEInvoice($data);

			// check if the PDF invoice requires a refresh to let the conditional text rules run after having updated the e-invoice
			if ($qrcode_fname) {
				// attempt to refresh the PDF invoice, if available, in case it uses the Conditional Text Rules
				if ($this->refreshPdfInvoice($einv_bid)) {
					$qrcode_url = VikBookingMydataAadeConstants::getQRCodeBase('uri', $qrcode_fname);
					// trigger an event to allow third-party plugins to do something, like sending the invoice via email
					VBOFactory::getPlatform()->getDispatcher()->trigger('onMydataAfterQrcodeInvoiceSubmitted', [$einv_bid, $einvid, $trans_data, $qrcode_url]);
				}
			}

			/**
			 * Check if an e-invoice for the environmental fee was prepared to be generated and transmitted as well.
			 * 
			 * @since 	1.16.7 (J) - 1.6.7 (WP)
			 */
			$env_fee_inv_details = VBOFactory::getConfig()->getArray($this->getCorrelatedInvoiceParamName($einvid, $einv_bid), []);
			if ($env_fee_inv_details && $trans_data->invoice_mark) {
				/**
				 * It is recommended to sleep at least one second after the main invoice has been
				 * transmitted and before the correlated invoice gets transmitted to ensure the
				 * main invoice mark is registered on the myDATA platform.
				 */
				sleep(1);

				// use the obtained invoice mark to adjust and transmit the environmental fee invoice
				$this->transmitCorrelatedInvoice(
					$trans_data,
					$env_fee_inv_details,
					[
						'einvid' => $einvid,
						'bid' 	 => $einv_bid,
					]
				);
			}
		}

		// display info message
		$this->setInfo('Electronic invoices transmitted: ' . count($einvspool));

		// we need to unset the bookings var so that the later call to getBookingsData() made by the View will reload the information
		$this->bookings = [];
		// unset also cols, rows and footer row to not merge data
		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

		return true;
	}

	/**
	 * Generates one XML string for the request body to myDATA. If more
	 * than one e-invoice ID passed, attempts to parse all XML files for
	 * the already generated e-invoices in order to compose one single
	 * XML request body that contains all invoices. Every e-invoice has
	 * got an XML file compliant for the transmission, but when we need
	 * to transmit in mass multiple e-invoices, we try to use just one
	 * HTTP request by merging all e-invoices into one single XML file.
	 *
	 * @param 	array 	$einvspool 	an array of e-invoice IDs
	 * @param 	array 	$settings 	the driver settings
	 *
	 * @return 	bool|string 		false on failure or XML request body string.
	 */
	protected function buildTransmissionXMLBody($einvspool, $settings)
	{
		if (!is_array($einvspool) || !$einvspool) {
			return false;
		}

		// the list of XML strings
		$xml_strings = [];

		// generate XML files for the requested e-invoice IDs
		foreach ($einvspool as $einvid) {
			// load e-invoice details
			$einv_data = $this->loadEInvoiceDetails($einvid);
			if (!$einv_data || !is_array($einv_data) || empty($einv_data['xml'])) {
				// all e-invoices must exist as they will be set to transmitted=1 so we break the process
				$this->setError('Unable to load data for the electronic invoice ID ' . $einvid);
				return false;
			}

			// push e-invoice content
			$xml_strings[] = $einv_data['xml'];
		}

		if (!$xml_strings) {
			// no XML files created, break the process
			return false;
		}

		if (count($xml_strings) === 1) {
			// just one XML file, no need to build an XML container
			return $xml_strings[0];
		}

		// return one whole XML request body for all e-invoices
		return $this->mergeXMLInvoices($xml_strings);
	}

	/**
	 * Given a list of XML e-invoice strings, attempts to merge them
	 * into one single XML to avoid making one HTTP request per e-invoice.
	 * 
	 * @param 	array 	$xml_strings 	list of XML strings for each e-invoice.
	 * 
	 * @return 	bool|string 	false or whole XML string for all e-invoices.
	 */
	protected function mergeXMLInvoices($xml_strings)
	{
		if (!is_array($xml_strings) || !$xml_strings) {
			return false;
		}

		if (count($xml_strings) === 1) {
			return $xml_strings[0];
		}

		if (!class_exists('SimpleXMLElement')) {
			/**
			 * We cannot afford to do a string manipulation only because SimpleXMLElement
			 * is missing on the server. It has to be available, it's a native library.
			 */
			$this->setError('SimpleXMLElement is missing on your server.');
			$this->setError('This is unusual, and you should contact your hosting company to enable this native PHP library.');
			$this->setError('You can only transmit single e-invoices, not more than one because SimpleXMLElement is missing');

			return false;
		}

		/**
		 * Define the XML root element for the InvoicesDoc message.
		 * Namespace attributes will affect the incomeClassification sub nodes.
		 * 
		 * @see 	VikBookingMydataAadeConstants::getInvoiceNamespaceAttributes();
		 * @see 	https://mydata-dev.portal.azure-api.net/issues/5f3c411ac75730207831ead4
		 */
		$root_namespaces = VikBookingMydataAadeConstants::getInvoiceNamespaceAttributes();
		$xml_root = <<<XML
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<InvoicesDoc $root_namespaces>
</InvoicesDoc>
XML;
		// get the SimpleXMLElement object
		$xml = new SimpleXMLElement($xml_root);

		// define the namespace rules for the children elements of <incomeClassification>
		$child_nmspaces = [
			'incomeClassification' => VikBookingMydataAadeConstants::getInvoiceChildrenNamespace()
		];

		// parse all e-invoices
		$parsed = 0;
		foreach ($xml_strings as $k => $einvoice) {
			$xml_einvoice = simplexml_load_string($einvoice);
			if (!is_object($xml_einvoice)) {
				$this->setWarning('Unable to parse the XML of the e-invoice index ' . ($k + 1));
				$this->setWarning($this->libxml_display_errors());
				continue;
			}
			// append XML tree to a new <invoice> node
			$invoice_node = $xml->addChild('invoice');
			$this->simpleXmlAppendTree($invoice_node, $xml_einvoice->invoice, $child_nmspaces);
			// increase parsed invoices
			$parsed++;
		}

		if (!$parsed) {
			$this->setError('No e-invoices could be parsed to merge the XML trees and related nodes into one single XML body.');
			return false;
		}

		// get the whole XML request body just built from all e-invoices
		$full_xml = $xml->asXML();

		/**
		 * When appending child nodes with namespaces to "<incomeClassification>", these may be added as
		 * "<N1:classificationCategory xmlns:N1="N1">category1_3</N1:classificationCategory>" so with both
		 * the proper namespace in the node name, but also with the attribute 'xmlns:N1="N1"' which is making
		 * the whole XML failing according to the schema. Therefore, we manipulate the string to remove such attributes.
		 */
		if (!empty($child_nmspaces['incomeClassification'])) {
			$seek_pattern = $child_nmspaces['incomeClassification'];
			$full_xml = str_replace('xmlns:' . $seek_pattern . '="' . $seek_pattern . '"', '', $full_xml);
		}

		// attempt to properly format the XML string
		$this->formatXmlString($full_xml);

		// return the whole XML request body containing all the e-invoices
		return $full_xml;
	}

	/**
	 * Recursive method to append a SimpleXMLElement tree node, and
	 * related children nodes, to another SimpleXMLElement. Used to
	 * dinamically add an entire tree of a single e-invoice XML file
	 * under a single node <invoice> of the whole XML request body.
	 * 
	 * @param 	SimpleXMLElement 	$xml_to 		 the node where the tree will be appended.
	 * @param 	SimpleXMLElement 	$xml_from 		 the element to append with all its children.
	 * @param 	array 				$child_nmspaces  associative list of children namespaces.
	 * 
	 * @return 	void
	 */
	protected function simpleXmlAppendTree(&$xml_to, &$xml_from, $child_nmspaces = [])
	{
		$child_nmspace  = null;
		$child_isprefix = false;

		$node_name = $xml_to->getName();
		if (!empty($child_nmspaces[$node_name])) {
			$child_nmspace  = $child_nmspaces[$node_name];
			$child_isprefix = true;
		}

		foreach ($xml_from->children($child_nmspace, $child_isprefix) as $xml_child) {
			$add_node_name = $xml_child->getName();
			if (!empty($child_nmspace)) {
				$add_node_name = "$child_nmspace:$add_node_name";
			}
			$xml_temp = $xml_to->addChild($add_node_name, (string)$xml_child, $child_nmspace);
			foreach ($xml_child->attributes() as $attr_key => $attr_value) {
				$xml_temp->addAttribute($attr_key, $attr_value);
			}
			$this->simpleXmlAppendTree($xml_temp, $xml_child, $child_nmspaces);
		}
	}

	/**
	 * Loads the details of the given e-invoice ID. The given ID should not be obliterated.
	 *
	 * @param 	int 	$einvid 	the ID of the e-invoice
	 *
	 * @return 	mixed 	array if the e-invoice exists and is not obliterated, false otherwise.
	 */
	protected function loadEInvoiceDetails($einvid)
	{
		if (empty($einvid)) {
			return false;
		}

		$q = "SELECT * FROM `#__vikbooking_einvoicing_data` WHERE `id`=" . (int)$einvid . " AND `obliterated`=0;";
		$this->dbo->setQuery($q);
		$einv = $this->dbo->loadAssoc();

		return $einv ? $einv : false;
	}

	/**
	 * Performs a POST request to the myDATA infrastructure.
	 * 
	 * @param 	string 	$url_path 	the path to append to the base endpoint URI.
	 * @param 	mixed 	$body 		the request body.
	 * @param 	array 	$settings 	driver settings or any other option to inject.
	 * 
	 * @return 	JHttpResponse object with code and body properties
	 */
	protected function myDATARequestPOST($url_path = '', $body = null, $settings = [])
	{
		if (empty($settings)) {
			$settings = $this->loadSettings();
		}

		$aade_user_id = $settings['params']['aade_user_id'];
		$aade_subscription_key = $settings['params']['aade_subscription_key'];
		$aade_endp_url = $settings['params']['mydata_endpoint_url'];
		if (!empty($settings['params']['test_mode'])) {
			$aade_endp_url = VikBookingMydataAadeConstants::getDevEndpointBaseUrl();
		}

		if (!empty($url_path)) {
			$aade_endp_url .= ltrim($url_path, '/');
		}

		// build request headers
		$headers = [
			'Content-Type' 				=> 'application/xml',
			'aade-user-id' 				=> $aade_user_id,
			'Ocp-Apim-Subscription-Key' => $aade_subscription_key,
		];

		// invoke CMS native transporter
		$transporter = new JHttp;
		$response = $transporter->post($aade_endp_url, $body, $headers);

		if ($response->code != 200) {
			$this->setError('Erroneous response with HTTP code ' . $response->code);
			$this->setError(htmlentities($response->body));
		}

		return $response;
	}

	/**
	 * Checks if the XML response string from myDATA contains errors.
	 * Returns an array with boolean "success" and array with "einv_id => einv_mark".
	 * 
	 * @param 	string 	$body 		the raw response body from the request.
	 * @param 	array 	$einvspool 	list of e-invoice ids in VBO just transmitted.
	 * @param 	array 	$einvnumbs 	list of e-invoice numbers in VBO just transmitted.
	 * 
	 * @return 	array 				to be used with list($success, $valid_einv_marks, $valid_einv_uids, $valid_qrcode_urls).
	 */
	protected function myDATAParseXMLResponse($body, $einvspool = [], $einvnumbs = [])
	{
		// the default information to return
		$success = false;
		$valid_einv_marks  = [];
		$valid_einv_uids   = [];
		$valid_qrcode_urls = [];

		$res_obj = $body;
		if (!is_object($res_obj)) {
			$res_obj = simplexml_load_string($body);
		}

		if (!is_object($res_obj) || !isset($res_obj->response)) {
			$this->setError('Could not parse XML response');
			$this->setError('<pre>' . htmlentities($body) . '</pre>');
			return [$success, $valid_einv_marks, $valid_einv_uids, $valid_qrcode_urls];
		}

		// errors counter
		$errors_found = 0;

		// loop through each response node
		foreach ($res_obj->response as $invoice_resp) {
			if (!isset($invoice_resp->statusCode)) {
				$this->setError('Unexpected nodes in XML response (missing statusCode)');
				$this->setError('<pre>' . htmlentities($body) . '</pre>');
				return [$success, $valid_einv_marks, $valid_einv_uids, $valid_qrcode_urls];
			}

			/**
			 * Endpoint POST /SendInvoices only (/CancelInvoice would not return this data).
			 * Get the index of the current invoice response (line-number starts from 1).
			 */
			$invoice_index = isset($invoice_resp->index) ? (int)$invoice_resp->index : 0;

			// check if we have a successful status code for this invoice
			if (!strcasecmp((string)$invoice_resp->statusCode, 'Success')) {
				// success!
				if ($invoice_index > 0 && isset($einvspool[($invoice_index - 1)])) {
					// push successful invoice
					$einv_id = $einvspool[($invoice_index - 1)];

					// get the invoice UID
					$invoice_uid = isset($invoice_resp->invoiceUid) ? (string)$invoice_resp->invoiceUid : $einv_id;
					$valid_einv_uids[$einv_id] = $invoice_uid;

					// get the invoice mark (needed for a later cancellation)
					$invoice_mark = isset($invoice_resp->invoiceMark) ? (string)$invoice_resp->invoiceMark : $einv_id;
					$valid_einv_marks[$einv_id] = $invoice_mark;

					/**
					 * Check if a QRCode URL is available for the electronic invoice.
					 * Valid property name should be "qrUrl".
					 * 
					 * @since 	1.16.7 (J) - 1.6.7 (WP)
					 */
					$invoice_qrcode = isset($invoice_resp->qrUrl) ? (string)$invoice_resp->qrUrl : $einv_id;
					$invoice_qrcode = !isset($invoice_resp->qrUrl) && isset($invoice_resp->qrCodeUrl) ? (string)$invoice_resp->qrCodeUrl : $invoice_qrcode;
					$valid_qrcode_urls[$einv_id] = $invoice_qrcode;
				}
				continue;
			}

			// at this point we expect an error
			if (!isset($invoice_resp->errors) || !isset($invoice_resp->errors->error)) {
				// errors should be set, but if they aren't, this is unexpected
				$this->setError('Unexpected nodes in XML response (missing errors or error)');
				$this->setError('<pre>' . htmlentities($body) . '</pre>');
				return [$success, $valid_einv_marks, $valid_einv_uids, $valid_qrcode_urls];
			}

			// loop through the errors
			foreach ($invoice_resp->errors->error as $resp_err) {
				$errors_found++;
				$err_code = isset($resp_err->code) ? (string)$resp_err->code : '0';
				$err_mess = isset($resp_err->message) ? (string)$resp_err->message : '???';
				$inv_numb = isset($einvnumbs[($invoice_index - 1)]) ? $einvnumbs[($invoice_index - 1)] : '???';
				$this->setError(sprintf('Error (%s) in invoice index %d (#%s): %s', $err_code, $invoice_index, $inv_numb, $err_mess));
			}
		}

		// if we had no errors at all, the response was successful
		$success = (!$errors_found);

		return [$success, $valid_einv_marks, $valid_einv_uids, $valid_qrcode_urls];
	}

	/**
	 * Downloads the electronic invoices by storing temporary files.
	 * This is a 'driver action', and so it's called before getBookingsData()
	 * in the view. This method will not save/update records in the DB.
	 *
	 * @return 	void
	 */
	public function downloadEInvoices()
	{
		// make sure the transmission settings are not empty
		$settings = $this->loadSettings();
		if ($settings === false || !$settings['params']) {
			$this->setError('Missing settings. Please set up the driver first.');
			return false;
		}

		// call the main method to generate rows, cols and bookings array
		$this->getBookingsData();

		if (strlen($this->getError()) || !$this->bookings) {
			return false;
		}

		// pool of e-invoice IDs to download
		$einvspool = [];

		foreach ($this->bookings as $gbook) {
			// make sure an electronic invoice was already issued for this booking ID by this driver
			if (empty($gbook[0]['einvid']) || $gbook[0]['einvdriver'] != $this->getDriverId()) {
				// no e-invoices available for this booking, skipping
				continue;
			}

			// push e-invoice ID to the pool
			array_push($einvspool, $gbook[0]['einvid']);
		}

		if (!$einvspool) {
			// no e-invoices generated
			$this->setWarning('No electronic invoices can be downloaded. Please generate them first.');
			return false;
		}

		// build one whole XML file
		$einv_xml_body = $this->buildTransmissionXMLBody($einvspool, $settings);
		if ($einv_xml_body === false) {
			// something went wrong with the creation of the file to download
			$this->setError('Could not generate the XML file containing all the electronic invoices.');
			return false;
		}

		// force the download of the XML string
		header('Content-Disposition: attachment; filename="mydata-aade-einvoices' . date('Y-m-d') . '.xml"');
		header("Content-Type: text/xml");
		header("Content-Length:" . strlen($einv_xml_body));
		header('Connection: close');
		echo $einv_xml_body;

		exit;
	}

	/**
	 * Forces the display of an electronic invoice. This is a 'driver action', and so it's called
	 * before getBookingsData() in the view. This method will not save/update records in the DB.
	 * This method truncates the execution of the script to read the XML data.
	 *
	 * @return 	void
	 */
	public function viewEInvoice()
	{
		$einvid = VikRequest::getInt('einvid', 0, 'request');
		$einv_data = $this->loadEInvoiceDetails($einvid);
		if (!$einv_data) {
			die('Missing e-invoice ID');
		}

		// force the output
		header("Content-type:text/xml");
		echo $einv_data['xml'];

		exit;
	}

	/**
	 * Removes an electonic invoice. This is a 'driver action',
	 * and so it's called before getBookingsData() in the view.
	 * It also removes the analogic version in PDF of the invoice.
	 *
	 * @return 	void
	 */
	public function removeEInvoice()
	{
		$einvid = VikRequest::getInt('einvid', '', 'request');
		$einv_data = $this->loadEInvoiceDetails($einvid);
		if (!$einv_data) {
			$this->setError('Missing e-invoice ID. Unable to delete the e-invoice.');
			return false;
		}

		// get "transmission data" (if any)
		$trans_data = !empty($einv_data['trans_data']) ? json_decode($einv_data['trans_data']) : null;
		if (is_object($trans_data) && !empty($trans_data->invoice_mark)) {
			/**
			 * This invoice was transmitted before, make sure to cancel it also from myDATA.
			 * However, the endpoint requires a "mark" value for the invoice, which could be the
			 * invoiceMark property upon a successful submission or the number we pass to compose
			 * the XML of the electronic invoice (our progressive number). There are two "mark"
			 * values, but we got errors for both, hence we don't know which one to use. We always
			 * check if $trans_data->invoice_mark is not empty so that we know the invoice was
			 * already transmitted before to myDATA and accepted.
			 * 
			 * @todo 	what's the right invoice mark? the "number" is inside the XML that we generate
			 * 			even before the transmission, while "invoice_mark" is returned in the myDATA response.
			 */
			$mydata_invoice_mark = $einv_data['number'];
			$mydata_invoice_mark = $trans_data->invoice_mark;

			/**
			 * Check if a correlated invoice was transmitted, because it should be removed first.
			 * 
			 * @since 	1.16.7 (J) - 1.6.7 (WP)
			 */
			$correlated_inv_data_tn = $this->getPreviousCorrelatedInvoiceData($einv_data['id'], $einv_data['idorder'], 'transmission');
			if ($correlated_inv_data_tn && !empty($correlated_inv_data_tn['mark'])) {
				// delete the correlated invoice first, by making the POST request
				$response = $this->myDATARequestPOST('CancelInvoice?mark=' . $correlated_inv_data_tn['mark']);
				if ($response->code != 200) {
					// the request was not successful
					$this->setWarning(sprintf('Invalid response (code %s): %s', $response->code, htmlspecialchars($response->body)));
					$this->setWarning('Could not cancel the correlated invoice from myDATA with mark ' . $correlated_inv_data_tn['mark']);
				} else {
					// check the XML response
					$xml_result = $this->myDATAParseXMLResponse($response->body);
					if ($xml_result[0]) {
						// success! the correlated invoice was cancelled from myDATA
						$this->setInfo(sprintf('Correlated invoice mark %s successfully cancelled from myDATA', $correlated_inv_data_tn['mark']));

						/**
						 * IMPORTANT: sleep for 2 seconds, or deleting immediately the main invoice below may
						 * result into an error like "Error (255) in invoice index 0 (#???): Invoice with MARK 400001924172498
						 * cannot be cancelled because it is connected with active invoice with MARK 400001924172499"
						 */
						sleep(2);
					}
				}

				// always delete the record for the correlated invoice
				VBOFactory::getConfig()->remove($this->getCorrelatedInvoiceParamName($einv_data['id'], $einv_data['idorder']));

				// check if the PDF version of the environmental fee exists
				if (!empty($correlated_inv_data_tn['pdf'])) {
					$envfee_pathpdf = VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "invoices" . DIRECTORY_SEPARATOR . "generated" . DIRECTORY_SEPARATOR . $correlated_inv_data_tn['pdf'];
					if (is_file($envfee_pathpdf)) {
						@unlink($envfee_pathpdf);
					}
				}
			}

			// make the POST request
			$response = $this->myDATARequestPOST('CancelInvoice?mark=' . $mydata_invoice_mark);
			if ($response->code != 200) {
				// the request was not successful, and the XML invoices were not parsed at all by myDATA
				$this->setWarning(sprintf('Invalid response (code %s): %s', $response->code, htmlspecialchars($response->body)));
				$this->setWarning('Could not cancel the invoice from myDATA.');
			} else {
				// check the XML response
				$xml_result = $this->myDATAParseXMLResponse($response->body);
				if ($xml_result[0]) {
					// success! the invoice was cancelled from myDATA
					$this->setInfo(sprintf('Invoice mark %s (#%s) successfully cancelled from myDATA', $trans_data->invoice_mark, $einv_data['number']));
				}
			}
		}

		// remove e-invoice
		$q = "DELETE FROM `#__vikbooking_einvoicing_data` WHERE `id`=".$einv_data['id'].";";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		// remove analogic invoice for this booking
		$pdfremoved = false;
		if (!empty($einv_data['idorder'])) {
			$pdfname = '';
			$q = "SELECT * FROM `#__vikbooking_invoices` WHERE `idorder`=".(int)$einv_data['idorder'].";";
			$this->dbo->setQuery($q);
			$analogic = $this->dbo->loadAssoc();
			if ($analogic) {
				$pdfname = $analogic['file_name'];
				$q = "DELETE FROM `#__vikbooking_invoices` WHERE `idorder`=".(int)$einv_data['idorder'].";";
				$this->dbo->setQuery($q);
				$this->dbo->execute();

			}
			$pdfpath = VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR . $pdfname;
			if (!empty($pdfname) && is_file($pdfpath)) {
				$pdfremoved = true;
				@unlink($pdfpath);
			}
		}

		$this->setInfo(($pdfremoved ? 'Electronic and PDF invoices deleted' : 'Electronic invoice deleted'));
	}

	/**
	 * Attempts to generate a QR Code PNG image file with the e-invoice URL.
	 * 
	 * @param 	int 	$einv_id 	the generated e-invoice record ID.
	 * @param 	int 	$bid 		the reservation record ID.
	 * @param 	object 	$data 		transaction data object with myDATA values.
	 * 
	 * @return 	string 				empty string in case of failure, or generated QR Code file name.
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	protected function generateInvoiceQRCode($einv_id, $bid, $data)
	{
		if (!is_object($data) || empty($data->invoice_qrcode)) {
			return '';
		}

		// the QR Code PNG file name
		$filename = "aade_qrcode_{$bid}_{$einv_id}.png";

		// generate the image
		if ($this->generateQRCodeImage($data->invoice_qrcode, VikBookingMydataAadeConstants::getQRCodeBase('path', $filename))) {
			// file was written successfully
			return $filename;
		}

		// an error has occurred
		return '';
	}

	/**
	 * Generates a QR Code image with the given URL in the given path.
	 * 
	 * @param 	string 	$url 	the URL to be assigned (content) to the QR Code.
	 * @param 	string 	$path 	the full path where the file should be saved.
	 * 
	 * @return 	bool
	 */
	protected function generateQRCodeImage($url, $path)
	{
		try {
			// require the TCPDF 2D Barcode library
			require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . 'tcpdf_barcodes_2d.php';

			// set the barcode content and type
			$barCode = new TCPDF2DBarcode($url, 'QRCODE,H');

			// generate the QR code as PNG image
			$qr = $barCode->getBarcodePngData(
				VikBookingMydataAadeConstants::QRCODE_PNG_WIDTH,
				VikBookingMydataAadeConstants::QRCODE_PNG_HEIGHT,
				explode(',', preg_replace("/[^0-9\.\,]/", '', VikBookingMydataAadeConstants::QRCODE_PNG_COLOR_RGB))
			);

			// write the image on disk
			return (bool)JFile::write($path, $qr);
		} catch (Throwable $t) {
			// do nothing
		}

		return false;
	}

	/**
	 * Right after obtaining a QR Code for an electronic invoice, the driver
	 * calls this method to refresh the PDF (courtesy) invoice so that any
	 * conditional text rule used on the invoice template file will run correctly.
	 * 
	 * @param 	int 	$bid 	the reservation record ID for which the invoice should be refreshed.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	protected function refreshPdfInvoice($bid)
	{
		return (bool)VikBooking::generateBookingInvoice(
			// load booking details
			VikBooking::getBookingInfoFromID($bid),
			// do not set an invoice number, because the previous one must be used
			$invoice_num = 0,
			// do not set an invoice suffix, because the previous one must be used
			$invoice_suff = '',
			// do not set an invoice date, because the previous one must be used
			$invoice_date = '',
			// company information will be re-fetched
			$company_info = '',
			// translation is not needed
			$translate = false,
			// set the argument to request a re-generation (refresh) of the existing invoice
			$refresh_pdf = true
		);
	}

	/**
	 * Validates the XML against the Schema.
	 *
	 * @param 	string 		$xml 	the xml string to validate
	 *
	 * @return 	null|boolean
	 */
	protected function validateXmlAgainstSchema($xml) {
		if (!class_exists('DOMDocument')) {
			// we cannot validate the XML because DOMDocument is missing
			return null;
		}

		$schema_path = VikBookingMydataAadeConstants::getSchemaPath();

		libxml_use_internal_errors(true);
		
		$dom = new DOMDocument();
		$dom->load($xml);
		if (!$dom->schemaValidate($schema_path)) {
			$this->setWarning('The schema validation of the electronic XML invoice returned errors, but they may be related to an unreadable schema.');
			$this->setWarning($this->libxml_display_errors());
			return false;
		}

		return true;
	}

	/**
	 * Formats the XML errors occurred
	 *
	 * @return 	string 	the error string
	 */
	protected function libxml_display_errors() {
		$errorstr = "";
		$errors = libxml_get_errors();
		foreach ($errors as $error) {
			$errorstr .= $this->libxml_display_error($error);
		}
		libxml_clear_errors();

		return $errorstr;
	}

	/**
	 * Explanation of the XML error
	 * 
	 * @param 	object 	$error 	the libxml error object
	 *
	 * @return 	string 	the explained error occurred
	 */
	protected function libxml_display_error($error) {
		$return = "\n";
		switch ($error->level) {
			case LIBXML_ERR_WARNING :
				$return .= "Warning ".$error->code.": ";
				break;
			case LIBXML_ERR_ERROR :
				$return .= "Error ".$error->code.": ";
				break;
			case LIBXML_ERR_FATAL :
				$return .= "Fatal Error ".$error->code.": ";
				break;
		}
		$return .= trim($error->message);
		if ($error->file) {
			$return .= " in " . $error->file;
		}
		$return .= " on line " . $error->line . "\n";

		return $return;
	}

	/**
	 * Override method to show the overlay content.
	 * Used to display the edit form of the raw XML.
	 * This method echoes the string to be displayed.
	 *
	 * @return 	void
	 */
	public function printOverlayContent()
	{
		$content   = VikRequest::getString('drivercontent', '', 'request');
		$einvid    = VikRequest::getInt('einvid', 0, 'request');
		$envfeebid = VikRequest::getInt('envfeebid', 0, 'request');

		if ($content == 'editEInvoice' && !empty($einvid)) {
			$einv_data = $this->loadEInvoiceDetails($einvid);
			if (!$einv_data) {
				return;
			}

			if ($envfeebid) {
				$correlated_invoice = $this->getPreviousCorrelatedInvoiceData($einvid, $envfeebid, $type = 'record');
				if ($correlated_invoice) {
					$einv_data['correlated_invoice'] = $correlated_invoice;
				}
			}

			// path to edit invoice layout file
			$fpath = $this->driverHelperPath . 'editeinvoice.php';
			
			// load helper file and echo its content
			echo $this->loadHelperFile($fpath, $einv_data);

			return;
		}
	}

	/**
	 * Updates the XML of an electonic invoice. This is a 'driver action',
	 * and so it's called before getBookingsData() in the view.
	 *
	 * @return 	bool
	 */
	public function updateXmlEInvoice()
	{
		$einvid = VikRequest::getInt('einvid', '', 'request');
		$newxml = VikRequest::getString('newxml', '', 'request', VIKREQUEST_ALLOWRAW);
		$einv_data = $this->loadEInvoiceDetails($einvid);
		if (!$einv_data) {
			$this->setError('Invoice not found');
			return false;
		}
		if (empty($newxml)) {
			$this->setError('Empty XML content');
			return false;
		}
		
		$jdate = new JDate;
		$data = new stdClass;
		$data->id = $einv_data['id'];
		$data->created_on = $jdate->toSql();
		$data->xml = $newxml;

		return $this->updateEInvoice($data);
	}

	/**
	 * Updates the XML of a correlated electonic invoice. This is a 'driver action',
	 * and so it's called before getBookingsData() in the view.
	 *
	 * @return 	bool
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public function updateCorrelatedXmlEInvoice()
	{
		$einvid    = VikRequest::getInt('einvid', 0, 'request');
		$envfeebid = VikRequest::getInt('envfeebid', 0, 'request');
		$newxml    = VikRequest::getString('newxml', '', 'request', VIKREQUEST_ALLOWRAW);

		// get the invoice record
		$correlated_inv_raw_data = VBOFactory::getConfig()->getArray($this->getCorrelatedInvoiceParamName($einvid, $envfeebid), []);

		if (!$correlated_inv_raw_data) {
			return false;
		}

		// update the XML source code
		$correlated_inv_raw_data['xml'] = $newxml;

		// update record
		VBOFactory::getConfig()->set($this->getCorrelatedInvoiceParamName($einvid, $envfeebid), $correlated_inv_raw_data);

		return true;
	}

	/**
	 * Extracts only numbers from a given string, by optionally
	 * stripping the current year. Useful to find an invoice number.
	 *
	 * @param 	string 		$str 		the string to look for numbers
	 * @param 	boolean 	$stripy 	whether to strip the current year
	 *
	 * @return 	string 	either an empty string or all numbers as a concatenated string
	 */
	protected function getOnlyNumbers($str, $stripy = false)
	{
		if ($stripy) {
			$str = str_replace(date('Y'), '', $str);
		}

		preg_match_all('/\d+/', $str, $matches);

		return implode('', $matches[0]);
	}
}

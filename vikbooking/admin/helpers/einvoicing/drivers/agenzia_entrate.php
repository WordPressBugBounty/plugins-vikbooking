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
 * AgenziaEntrate child Class of VikBookingEInvoicing
 */
class VikBookingEInvoicingAgenziaEntrate extends VikBookingEInvoicing
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
	private $driverHelperPath = '';

	/**
	 * An array of session filters.
	 *
	 * @var 	array
	 */
	private $sessionFilters;

	/**
	 * An array of bookings.
	 *
	 * @var 	array
	 */
	private $bookings;

	/**
	 * Class constructor should define the name of the driver and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->driverFile = basename(__FILE__, '.php');
		$this->driverName = "Agenzia dell'Entrate - Italia";
		$this->driverFilters = array();
		$this->driverButtons = array();

		// driver helper dir path
		$this->driverHelperPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . str_replace(' ', '', ucwords(str_replace('_', ' ', $this->driverFile))) . DIRECTORY_SEPARATOR;
		
		// this driver has settings
		$this->hasSettings = true;

		// reset session filters
		$this->sessionFilters = array();

		// reset bookings array
		$this->bookings = array();

		$this->cols = array();
		$this->rows = array();
		$this->footerRow = array();

		// require Class constants
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
		jQuery(document).ready(function() {
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
				var id = jQuery(this).attr("data-einvid");
				vboSetFilters({drivercontent: "editEInvoice", einvid: id}, true);
			});
			jQuery(".vbo-driver-output-rmeinv").click(function() {
				var id = jQuery(this).attr("data-einvid");
				if (confirm("Vuoi rimuovere questa fattura?")) {
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
				$monthys = array();
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
					'label' => '<label for="monyear">Mese</label>',
					'html' => '<select name="monyear" id="monyear"><option value=""></option>'.$opts.'</select>',
					'type' => 'select',
					'name' => 'monyear'
				);
				array_push($this->driverFilters, $filter_opt);
			}
		}

		// date type filter
		$filter_opt = array(
			'label' => '<label for="datetype">Data</label>',
			'html' => '<select name="datetype" id="datetype">
							<option value="ts"'.($pdatetype == 'ts' ? ' selected="selected"' : '').'>Prenotazione</option>
							<option value="checkin"'.($pdatetype == 'checkin' ? ' selected="selected"' : '').'>Check-in</option>
							<option value="checkout"'.($pdatetype == 'checkout' ? ' selected="selected"' : '').'>Check-out</option>
						</select>',
			'type' => 'select',
			'name' => 'datetype'
		);
		array_push($this->driverFilters, $filter_opt);

		// invoice type filter
		$filter_opt = array(
			'label' => '<label for="einvtype">Mostra</label>',
			'html' => '<select name="einvtype" id="einvtype">
							<option value="0">Tutte le prenotazioni</option>
							<option value="1"'.($peinvtype == 1 ? ' selected="selected"' : '').'>- Prenotazioni da fatturare</option>
							<option value="-1"'.($peinvtype == -1 ? ' selected="selected"' : '').'>- Fatture da trasmettere</option>
							<option value="-2"'.($peinvtype == -2 ? ' selected="selected"' : '').'>- Fatture trasmesse</option>
						</select>',
			'type' => 'select',
			'name' => 'einvtype'
		);
		array_push($this->driverFilters, $filter_opt);

		// search invoice filter
		$filter_opt = array(
			'label' => '<label for="einvkword">Ricerca Fattura</label>',
			'html' => '<div class="input-append"><input type="text" id="einvkword" name="einvkword" placeholder="Numero o dati cliente" value="'.htmlspecialchars($peinvkword).'" size="20" /><button type="button" class="btn btn-secondary" onclick="document.getElementById(\'einvkword\').value = \'\';"><i class="icon-remove"></i></button></div>',
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
		return count($this->loadSessionFilters());
	}

	/**
	 * Returns the current filters saved in the session.
	 * This private method is only used by this class.
	 *
	 * @return 	array
	 */
	private function loadSessionFilters()
	{
		if (count($this->sessionFilters)) {
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
	 * This private method is only used by this class.
	 * 
	 * @param 	string 	the name of the filter to fetch
	 * @param 	mixed 	the default filter value if empty
	 * 
	 * @return 	mixed 	the current session filter requested, or a default empty value
	 */
	private function getSessionFilter($name, $def = '')
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
	private function setSessionFilter($name, $val)
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
			<a href="JavaScript: void(0);" onclick="vboDriverDoAction(\'transmitEInvoices\', false);" class="vbo-perms-operators"><i class="vboicn-truck icn-nomargin"></i> <span>Trasmetti fatture al SdI</span></a>
		');

		// download invoices button
		array_push($this->driverButtons, '
			<a href="JavaScript: void(0);" onclick="vboDriverDoAction(\'downloadEInvoices\', true);" class="vbo-perms-operators"><i class="vboicn-download icn-nomargin"></i> <span>Scarica fatture XML</span></a>
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
		//
		$einvsuffix = VikRequest::getString('einvsuffix', '', 'request');
		$einvdttype = VikRequest::getString('einvdttype', 'today', 'request');
		$einvexnumdt = VikRequest::getString('einvexnumdt', 'new', 'request');
		$vatid = VikRequest::getString('vatid', '', 'request');
		$fisccode = VikRequest::getString('fisccode', '', 'request');
		$pecsdi = VikRequest::getString('pecsdi', '', 'request');
		$pec = VikRequest::getString('pec', '', 'request');
		$hostpec = VikRequest::getString('hostpec', '', 'request');
		$portpec = VikRequest::getInt('portpec', 587, 'request');
		$pwdpec = VikRequest::getString('pwdpec', '', 'request');
		$companyname = VikRequest::getString('companyname', '', 'request');
		$name = VikRequest::getString('name', '', 'request');
		$lname = VikRequest::getString('lname', '', 'request');
		$regimfisc = VikRequest::getString('regimfisc', '', 'request');
		$address = VikRequest::getString('address', '', 'request');
		$nciv = VikRequest::getString('nciv', '', 'request');
		$zip = VikRequest::getString('zip', '', 'request');
		$city = VikRequest::getString('city', '', 'request');
		$province = VikRequest::getString('province', '', 'request');
		$phone = VikRequest::getString('phone', '', 'request');
		// make sure the phone doesn't contain white spaces or the invoice will be rejected
		$phone = str_replace(' ', '', $phone);

		// fields validation
		$mandatory = array(
			$progcount,
			$vatid,
			$fisccode,
			$pec,
			$address,
			$nciv,
			$zip,
			$city,
			$province,
		);
		foreach ($mandatory as $field) {
			if (empty($field)) {
				$this->setError('Completare tutti i campi obbligatori.');
				return false;
			}
		}

		// update the global configuration setting 'invoiceinum'
		$q = "UPDATE `#__vikbooking_config` SET `setting`=".$this->dbo->quote((string)$invoiceinum)." WHERE `param`='invoiceinum';";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		// build data for saving
		$params->einvsuffix	 = $einvsuffix;
		$params->einvdttype	 = $einvdttype;
		$params->einvexnumdt = $einvexnumdt;
		$params->vatid 	 	 = $vatid;
		$params->fisccode 	 = $fisccode;
		$params->pecsdi 	 = $pecsdi;
		$params->pec 	 	 = $pec;
		$params->hostpec 	 = $hostpec;
		$params->portpec 	 = $portpec;
		$params->pwdpec 	 = $pwdpec;
		$params->companyname = $companyname;
		$params->name 		 = $name;
		$params->lname 		 = $lname;
		$params->regimfisc   = $regimfisc;
		$params->address 	 = $address;
		$params->nciv 	 	 = $nciv;
		$params->zip 	 	 = $zip;
		$params->city 	 	 = $city;
		$params->province 	 = $province;
		$params->phone 	 	 = $phone;

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
	private function getDefaultSettings()
	{
		return array(
			'id' => -1,
			'driver' => $this->getFileName(),
			'params' => array(),
			'automatic' => 0
		);
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
	private function displayInstructions()
	{
		$this->setWarning('Le impostazioni del driver non sono state configurate. Molti parametri presentano delle istruzioni importanti, tipo l\'indirizzo PEC del Sistema di Interscambio.');
		$this->setWarning('Assicurati di aver compilato le informazioni dei clienti prima di generare le fatture. Una volta generate le fatture, le potrai trasmettere al Sistema di Interscambio (SdI).');
		$this->setWarning('Ricordati per la prima volta di trasmettere <strong>una sola fattura</strong> al Sistema di Interscambio al loro indirizzo PEC predefinito.');
		$this->setWarning('Dopo la prima trasmissione riceverai via PEC il nuovo indirizzo PEC del Sistema di Interscambio che ti è stato assegnato. Cambialo dalle impostazioni e poi potrai trasmettere anche più fatture insieme.');
	}

	/**
	 * This method converts each booking array into a matrix with one room-booking per index.
	 * It also adds information about the customer and the invoices generated for each booking.
	 * 
	 * @param 	array 	$records 	the array containing the bookings before nesting
	 * 
	 * @return 	array
	 */
	private function nestBookingsData($records)
	{
		// to avoid heavy and extra joins, we load all customers for the returned booking ids
		$allids = array();
		foreach ($records as $b) {
			if (!isset($b['customer']) && !in_array($b['id'], $allids)) {
				array_push($allids, $b['id']);
			}
		}
		$customers_books = array();
		if (count($allids)) {
			$q = "SELECT `c`.*,`co`.`idorder`,`cy`.`country_name`,`cy`.`country_2_code` FROM `#__vikbooking_customers` AS `c` 
				LEFT JOIN `#__vikbooking_customers_orders` `co` ON `c`.`id`=`co`.`idcustomer` 
				LEFT JOIN `#__vikbooking_countries` AS `cy` ON `c`.`country`=`cy`.`country_3_code` 
				WHERE `co`.`idorder`".(count($allids) === 1 ? "=".(int)$allids[0] : " IN (".implode(', ', $allids).")").";";
			$this->dbo->setQuery($q);
			$allcustomers = $this->dbo->loadAssocList();
			foreach ($allcustomers as $customer) {
				$customers_books[$customer['idorder']] = $customer;
			}
		}

		// nest records with multiple rooms booked inside sub-array
		$bookings = array();
		foreach ($records as $v) {
			if (!isset($bookings[$v['id']])) {
				$bookings[$v['id']] = array();
			}
			// to avoid heavy joins, we put the customer record onto the first nested room booked
			if (!isset($v['customer']) && !count($bookings[$v['id']])) {
				$v['customer'] = isset($customers_books[$v['id']]) ? $customers_books[$v['id']] : array();
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
			$this->setError('Selezionare le date per filtrare le fatture e le prenotazioni.');
			return false;
		}

		// update session filters
		$this->setSessionFilter('fromdate', $pfromdate);
		$this->setSessionFilter('todate', $ptodate);
		$this->setSessionFilter('datetype', $pdatetype);

		// query to obtain the records
		$records = array();
		if (!empty($peinvkword)) {
			// search invoice requires a different query
			$seekclauses = array();
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
			// customer email or PEC
			if (strpos($peinvkword, '@') !== false) {
				// customer email
				array_push($seekclauses, "`cust`.`email`=".$this->dbo->quote($peinvkword));
				// customer pec
				array_push($seekclauses, "`cust`.`pec`=".$this->dbo->quote($peinvkword));
			}
			// customer fiscal code
			array_push($seekclauses, "`cust`.`fisccode`=".$this->dbo->quote($peinvkword));
			// customer recipeint code
			array_push($seekclauses, "`cust`.`recipcode`=".$this->dbo->quote($peinvkword));

			// find first the booking IDs with a specific query given the filters
			$oidsfound = array();
			/*
			$q = "SELECT `o`.`id` FROM `#__vikbooking_orders` AS `o` ".
				"LEFT JOIN `#__vikbooking_customers_orders` AS `custo` ON `o`.`id`=`custo`.`idorder` ".
				"LEFT JOIN `#__vikbooking_customers` AS `cust` ON `custo`.`idcustomer`=`cust`.`id` ".
				"LEFT JOIN `#__vikbooking_einvoicing_data` AS `ei` ON `o`.`id`=`ei`.`idorder` ".
				"WHERE `ei`.`id` IS NOT NULL AND `ei`.`obliterated`=0 AND (".implode(' OR ', $seekclauses).") ".
				"GROUP BY `o`.`id`;";
			*/
			$q = "SELECT `ei`.`id`,`ei`.`idorder` FROM `#__vikbooking_einvoicing_data` AS `ei` ".
				"LEFT JOIN `#__vikbooking_customers` AS `cust` ON `ei`.`idcustomer` = `cust`.`id` ".
				"WHERE `ei`.`obliterated`=0 AND (".implode(' OR ', $seekclauses).") ".
				"GROUP BY `ei`.`driverid`,`ei`.`number`;";
			$this->dbo->setQuery($q);
			$results = $this->dbo->loadAssocList();
			if (!$results) {
				$this->setError('Nessuna fattura trovata con i filtri specificati');
				return false;
			}

			$mergecustoms = false;
			$customsids = array();
			foreach ($results as $res) {
				if ($res['idorder'] < 0) {
					$mergecustoms = true;
					array_push($customsids, $res['id']);
				}
				array_push($oidsfound, $res['idorder']);
			}
			// we make the same query but by passing the IDs of the bookings found according to the filters
			$q = "SELECT `o`.`id`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`coupon`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`chcurrency`,`o`.`country`,`o`.`tot_taxes`,".
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
			$q = "SELECT `o`.`id`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`coupon`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`chcurrency`,`o`.`country`,`o`.`tot_taxes`,".
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
					 * 
					 * @since 	1.13.5
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
			$this->setError('Nessuna prenotazione o fattura trovata con i filtri specificati.');
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
				'label' => 'Data'
			),
			/*
			// rooms
			array(
				'key' => 'rooms',
				'sortable' => 1,
				'label' => 'Camere'
			),
			// guests
			array(
				'key' => 'guests',
				'sortable' => 1,
				'label' => 'Ospiti'
			),
			// nights
			array(
				'key' => 'nights',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Notti'
			),
			*/
			// checkin
			array(
				'key' => 'checkin',
				'sortable' => 1,
				'label' => 'Check-in'
			),
			// checkout
			array(
				'key' => 'checkout',
				'sortable' => 1,
				'label' => 'Check-out'
			),
			// customer
			array(
				'key' => 'customer',
				'sortable' => 1,
				'label' => 'Cliente'
			),
			// country
			array(
				'key' => 'country',
				'sortable' => 1,
				'label' => 'Nazione'
			),
			// city
			array(
				'key' => 'city',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Città'
			),
			// vat
			array(
				'key' => 'vat',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Partita IVA'
			),
			// pec
			array(
				'key' => 'pec',
				'sortable' => 1,
				'label' => 'PEC'
			),
			// total
			array(
				'key' => 'tot',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Totale'
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
				'label' => 'Azione'
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
			 * 
			 * @since 	1.13.5
			 */
			$multi_analog_ids = array();
			if (!empty($analog_id) && count($gbook) > 1) {
				$all_analog_ids = array();
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
			$province = '';
			if (count($customer) && !empty($customer['city']) && $customer['country'] == 'ITA') {
				// require the class VikBookingAgenziaEntrateComuni and seek for the province
				if (!class_exists('VikBookingAgenziaEntrateComuni')) {
					$this->importHelper($this->driverHelperPath . 'comuni.php');
				}
				$comuniobj = VikBookingAgenziaEntrateComuni::getInstance();
				$province = $comuniobj::findProvince($customer['city']);
				// update references
				$customer['provincia'] = $province;
				$gbook[0]['customer'] = $customer;
				//
			}
			$totguests = 0;
			$rooms_map = array();
			$rooms_str = array();
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
							$all_links = array();
							foreach ($multi_analog_ids as $analog_info) {
								array_push($all_links, '<a href="index.php?option=com_vikbooking&task=editmaninvoice&cid[]='.$analog_info['invid'].'&goto='.$returi.'" onclick="alert(\'Usa i filtri per data per non listare fatture manuali con numero identico\'); return true;"><i class="'.VikBookingIcons::i('external-link').'"></i> '.JText::translate('VBOMANUALINVOICE').' (' . $analog_info['for_date'] . ')</a>');
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
				/*
				array(
					'key' => 'rooms',
					'value' => $rooms_str
				),
				array(
					'key' => 'guests',
					'callback' => function ($val) use ($gbook) {
						$adults = 0;
						$children = 0;
						foreach ($gbook as $book) {
							$adults += $book['adults'];
							$children += $book['children'];
						}
						return $adults . ' ' . ($adults > 1 ? 'Adulti' : 'Adulto') . ($children > 0 ? ', ' . $children . ($children > 1 ? ' Bambini' : ' Bambino') : '');
					},
					'value' => $totguests
				),
				array(
					'key' => 'nights',
					'attr' => array(
						'class="center"'
					),
					'value' => $gbook[0]['days']
				),
				*/
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
								if (file_exists(VBO_ADMIN_PATH.DS.'resources'.DS.'countries'.DS.$customer['country'].'.png')) {
									$cont .= '<img src="'.VBO_ADMIN_URI.'resources/countries/'.$customer['country'].'.png'.'" title="'.$customer['country'].'" class="vbo-country-flag vbo-country-flag-left"/>';
								}
							}
						} else {
							// if empty customer ($val) print danger button to assign a customer to this booking ID
							$cont = '<a class="btn btn-danger" href="index.php?option=com_vikbooking&task=newcustomer&bid='.$bid.'&goto='.$goto.'">Crea Cliente</a>';
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
					'callback' => function ($val) use ($customer, $province) {
						$goto = base64_encode('index.php?option=com_vikbooking&task=einvoicing');
						if (empty($val)) {
							if (count($customer) && !empty($customer['id'])) {
								// just an empty City, edit the customer
								$cont = '<a class="btn btn-danger" href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">Inserisci</a>';
							} else {
								$cont = '-----';
							}
							return $cont;
						}
						if (count($customer) && empty($customer['zip'])) {
							// CAP is mandatory
							return '<a class="btn btn-danger" href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">Inserisci CAP</a>';
						}
						if (count($customer) && empty($customer['address'])) {
							// address is mandatory
							return '<a class="btn btn-danger" href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">Indirizzo ?</a>';
						}
						if (!empty($province)) {
							return $val . " ({$province})";
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
								// just an empty VAT Number, edit the customer (VAT is no longer mandatory to create an e-invoice)
								$cont = '<a class="btn btn-primary" href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">Inserisci</a>';
							} else {
								// if empty customer ($val) print danger button to assign a customer to this booking ID
								$cont = '<a class="btn btn-danger" href="index.php?option=com_vikbooking&task=newcustomer&bid='.$bid.'&goto='.$goto.'">Crea Cliente</a>';
							}
						}
						return $cont;
					},
					'value' => (count($customer) && !empty($customer['vat']) ? $customer['vat'] : '')
				),
				array(
					'key' => 'pec',
					'callback' => function ($val) use ($customer) {
						$cont = !empty($val) ? $val : '-----';
						if (count($customer) && $customer['country'] == 'ITA') {
							$goto = base64_encode('index.php?option=com_vikbooking&task=einvoicing');
							$cont = '<a href="index.php?option=com_vikbooking&task=editcustomer&cid[]='.$customer['id'].'&goto='.$goto.'">'.$cont.'</a>';
						}
						return $cont;
					},
					'value' => (count($customer) && !empty($customer['pec']) ? $customer['pec'] : '')
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
						$buttons = array();
						if ($val === -1 || $val === -2) {
							// invoice generated or generated and transmitted
							array_push($buttons, '<i class="vboicn-eye icn-nomargin vbo-driver-customoutput vbo-driver-output-vieweinv" title="Visualizza fattura" data-einvid="'.$noinvoicereason.'"></i>');
							array_push($buttons, '<i class="vboicn-pencil2 icn-nomargin vbo-driver-customoutput vbo-driver-output-editeinv" title="Modifica fattura" data-einvid="'.$noinvoicereason.'"></i>');
							array_push($buttons, '<i class="vboicn-bin icn-nomargin vbo-driver-customoutput vbo-driver-output-rmeinv" title="Elimina fattura" data-einvid="'.$noinvoicereason.'"></i>');
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
							$noinvoicereason = empty($noinvoicereason) ? 'Dati mancanti per emettere fattura' : $noinvoicereason;
							return '<button type="button" class="btn btn-secondary" onclick="alert(\''.addslashes($noinvoicereason).'\');"><i class="vboicn-blocked icn-nomargin"></i> Non fatturabile</button>';
						}
						if ($val === -1) {
							// e-invoice already issued and transmitted: print drop down to let the customer regenerate this invoice and obliterate the other or to re-send
							return '<select class="vbo-einvoicing-sentaction" data-bid="'.$bid.'"><option value="0-none">Fattura #'.$einvnum.' trasmessa</option><option value="'.$noinvoicereason.'-regen">- Rigenera fattura</option><option value="'.$noinvoicereason.'-resend">- Ritrasmetti fattura</option></select>';
						}
						if ($val === -2) {
							// e-invoice already issued but NOT transmitted: print drop down to let the customer regenerate this invoice and obliterate the other
							return '<select class="vbo-einvoicing-existaction" data-bid="'.$bid.'"><option value="0">Trasmetti fattura #'.$einvnum.'</option><option value="-1">- Non trasmettere fattura</option><option value="'.$noinvoicereason.'">- Rigenera Fattura</option></select>';
						}
						// invoice can be issued: print drop down to let the customer skip this generation
						return '<select class="vbo-einvoicing-selaction" data-bid="'.$bid.'"><option value="0">Genera fattura</option><option value="1">- Non generare fattura</option></select>';
					},
					'value' => $canbeinvoiced
				),
			));
		}

		// sort rows
		$this->sortRows($pkrsort, $pkrorder);

		// build footer rows
		$totcols = count($this->cols);
		$footerstats = array();
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
		$footercells = array();
		foreach ($footerstats as $canbeinvoiced => $tot) {
			switch ($canbeinvoiced) {
				case 1:
					$descr = 'Da fatturare';
					break;
				case -1:
					$descr = 'Fatture trasmesse';
					break;
				case -2:
					$descr = 'Fatture generate';
					break;
				default:
					$descr = 'Non fatturabili';
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
	 */
	private function canBookingBeInvoiced($booking)
	{
		if (!isset($booking[0]['customer']) || !count($booking[0]['customer']) || empty($booking[0]['customer']['vat'])) {
			/**
			 * Invoices can also be emitted to privates, not only to companies, and so the VAT is no longer mandatory.
			 * 
			 * @since 	1.13
			 */
			// return array(0, 'Partita IVA mancante');
		}

		if (!isset($booking[0]['customer']) || !count($booking[0]['customer']) || empty($booking[0]['customer']['country']) || empty($booking[0]['customer']['country_2_code'])) {
			return array(0, 'Nazione mancante');
		}

		if (!isset($booking[0]['customer']) || !count($booking[0]['customer']) || empty($booking[0]['customer']['city'])) {
			return array(0, 'Città mancante');
		}

		if (!isset($booking[0]['customer']) || !count($booking[0]['customer']) || empty($booking[0]['customer']['zip'])) {
			return array(0, 'CAP mancante');
		}

		if (!isset($booking[0]['customer']) || !count($booking[0]['customer']) || empty($booking[0]['customer']['address'])) {
			return array(0, 'Indirizzo mancante');
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

		if (strlen($this->getError()) || !count($this->bookings)) {
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
		$this->bookings = array();
		// unset also cols, rows and footer row to not merge data
		$this->cols = array();
		$this->rows = array();
		$this->footerRow = array();

		// set info message
		$this->setInfo('Fatture generate: '.$generated);

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
			$rawcont = !empty($invoice['rawcont']) ? json_decode($invoice['rawcont'], true) : array();
			$rawcont = is_array($rawcont) ? $rawcont : array();
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
			'country' => (isset($customer['country']) ? $customer['country'] : ''),
			'country_name' => (isset($customer['country_name']) ? $customer['country_name'] : ''),
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
			'id' => 'einvid',
			'idorder' => 'idorder',
			'number' => 'number',
		);

		$filters = array();
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

		$clauses = array();
		foreach ($filters as $col => $val) {
			array_push($clauses, "`{$col}`=".$this->dbo->quote($val));
		}

		$q = "SELECT `id` FROM `#__vikbooking_einvoicing_data` WHERE `driverid`=".(int)$this->getDriverId()." AND `obliterated`=0 AND ".implode(' AND ', $clauses)." ORDER BY `id` DESC LIMIT 1;";
		$this->dbo->setQuery($q);
		$einv_id = $this->dbo->loadResult();

		return $einv_id ?: false;
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
			'id' => 'einvid',
			'idorder' => 'idorder',
			'number' => 'number',
		);

		$filters = array();
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

		$clauses = array();
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
	 * @param 	mixed 		$data 		either the booking ID or the booking array (one room info per index)
	 *
	 * @return 	boolean 	True if the e-invoice was generated
	 */
	public function generateEInvoice($data)
	{
		// load driver settings
		$settings = $this->loadSettings();
		if ($settings === false || !count($settings['params'])) {
			$this->setError('Impostazioni driver mancanti. Configuare prima il driver.');
			return false;
		}

		if (is_int($data)) {
			// query to obtain the booking records
			$q = "SELECT `o`.`id`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`coupon`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`chcurrency`,`o`.`country`,`o`.`tot_taxes`,".
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
				$this->setError('Informazioni prenotazione non trovate');
				return false;
			}

			// nest records with multiple rooms booked inside sub-array
			$record = $this->nestBookingsData($record);
			$data = $record[$data];
		}

		if (!is_array($data) || empty($data)) {
			$this->setError('Nessuna prenotazione trovata');
			return false;
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
				$message = $data[0]['id'] < 0 ? 'Impossibile generare fattura elettronica manuale: '.$noinvoicereason : 'Impossibile generare fattura elettronica per prenotazione ID '.$data[0]['id'].' ('.$noinvoicereason.')';
				$this->setError($message);
			}

			return false;
		}

		// Codice Destinatario
		$coddest = '';
		$pecdest = '';
		if (!empty($data[0]['customer']['recipcode']) && $data[0]['customer']['country'] == 'ITA') {
			$coddest = $data[0]['customer']['recipcode'];
		} elseif ($data[0]['customer']['country'] == 'ITA') {
			$coddest = '0000000';
			if (!empty($data[0]['customer']['pec'])) {
				$pecdest = $data[0]['customer']['pec'];
			}
		} else {
			$coddest = 'XXXXXXX';
		}

		// provincia (solo per clienti Italiani)
		$province = '';
		if (!empty($data[0]['customer']['city']) && $data[0]['customer']['country'] == 'ITA') {
			// require the class VikBookingAgenziaEntrateComuni and seek for the province
			if (!class_exists('VikBookingAgenziaEntrateComuni')) {
				$this->importHelper($this->driverHelperPath . 'comuni.php');
			}
			$comuniobj = VikBookingAgenziaEntrateComuni::getInstance();
			$province = $comuniobj::findProvince($data[0]['customer']['city']);
		}

		// invoice date, number and suffix
		if (!empty($data[0]['einvnum']) && $settings['params']['einvexnumdt'] == 'old') {
			// if an invoice already exists, we re-use the same number also because the setting said so
			$invnum = $data[0]['einvnum'];
			$invdate = $data[0]['einvdate'];
		} else {
			// get a new invoice number
			$invnum = VikBooking::getNextInvoiceNumber();
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
		$invsuf = $settings['params']['einvsuffix'];

		// linee e riepiloghi IVA/Nature IVA
		$linee = array();
		$riepiloghi = array();
		$riepiloghivat = array();
		$riepiloghinat = array();
		$rounded_nets = array();
		$is_package = (!empty($data[0]['pkg']));
		$isdue = 0;
		$extralinenum = 0;
		if ($data[0]['id'] < 0 && isset($this->externalData['einvrawcont'])) {
			// custom (manual) invoice, get the raw content of the invoice
			foreach ($this->externalData['einvrawcont']['rows'] as $ind => $row) {
				if (!isset($riepiloghivat[$row['aliq']])) {
					$riepiloghivat[$row['aliq']] = array('net' => 0, 'tax' => 0);
					$rounded_nets[$row['aliq']] = 0;
				}
				if (intval($row['aliq']) === 0 && !isset($riepiloghinat['N2.2'])) {
					// rate plans with no tax rates assigned will default to N2.2 = Non soggette
					$riepiloghinat['N2.2'] = array('net' => 0, 'tax' => 0);
				}
				$riepiloghivat[$row['aliq']]['net'] += $row['net'];
				$riepiloghivat[$row['aliq']]['tax'] += $row['tax'];
				$rounded_nets[$row['aliq']] += (float)number_format($row['net'], 2, '.', '');
				// push linea
				array_push($linee, '
				<DettaglioLinee>
					<NumeroLinea>'.($ind + 1).'</NumeroLinea>
					<Descrizione>'.$this->convertSpecials($row['service']).'</Descrizione>
					<Quantita>1.00</Quantita>
					<PrezzoUnitario>'.number_format($row['net'], 2, '.', '').'</PrezzoUnitario>
					<PrezzoTotale>'.number_format($row['net'], 2, '.', '').'</PrezzoTotale>
					<AliquotaIVA>'.number_format($row['aliq'], 2, '.', '').'</AliquotaIVA>
					' . (intval($row['aliq']) === 0 ? '<Natura>N2.2</Natura>' : '') . '
				</DettaglioLinee>');

			}
		} else {
			// invoice for a regular booking
			$tars = $this->getBookingTariffs($data);
			foreach ($data as $kor => $or) {
				$num = $kor + 1;
				if ($is_package || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
					// package cost or cust_cost may not be inclusive of taxes if prices tax included is off
					$descr = $is_package ? sprintf(VikBookingAgenziaEntrateConstants::DESCRPACKAGENIGHTS, $or['days']) : sprintf(VikBookingAgenziaEntrateConstants::DESCRSTAYROOMNIGHTS, $or['days'], strtoupper($or['room_name']));
					$cost_minus_tax = VikBooking::sayPackageMinusIva($or['cust_cost'], $or['cust_idiva']);
					$aliq = $this->getAliquoteById($or['cust_idiva']);
					if (!isset($riepiloghivat[$aliq])) {
						$riepiloghivat[$aliq] = array('net' => 0, 'tax' => 0);
						$rounded_nets[$aliq] = 0;
					}
					if (intval($aliq) === 0 && !isset($riepiloghinat['N2.2'])) {
						// rate plans with no tax rates assigned will default to N2.2 = Non soggette
						$riepiloghinat['N2.2'] = array('net' => 0, 'tax' => 0);
					}
					$riepiloghivat[$aliq]['net'] += $cost_minus_tax;
					$riepiloghivat[$aliq]['tax'] += (VikBooking::sayPackagePlusIva($or['cust_cost'], $or['cust_idiva']) - $cost_minus_tax);
					$rounded_nets[$aliq] += (float)number_format($cost_minus_tax, 2, '.', '');
					// push linea
					array_push($linee, '
				<DettaglioLinee>
					<NumeroLinea>'.($num + $extralinenum).'</NumeroLinea>
					<Descrizione>'.$this->convertSpecials($descr).'</Descrizione>
					<Quantita>1.00</Quantita>
					<PrezzoUnitario>'.number_format($cost_minus_tax, 2, '.', '').'</PrezzoUnitario>
					<PrezzoTotale>'.number_format($cost_minus_tax, 2, '.', '').'</PrezzoTotale>
					<AliquotaIVA>'.number_format($aliq, 2, '.', '').'</AliquotaIVA>
					' . (intval($aliq) === 0 ? '<Natura>N2.2</Natura>' : '') . '
				</DettaglioLinee>');
				} elseif (isset($tars[$num]) && is_array($tars[$num])) {
					// regular tariff
					$descr = sprintf(VikBookingAgenziaEntrateConstants::DESCRSTAYROOMNIGHTS, $or['days'], strtoupper($or['room_name']));
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
					if (!isset($riepiloghivat[$aliq])) {
						$riepiloghivat[$aliq] = array('net' => 0, 'tax' => 0);
						$rounded_nets[$aliq] = 0;
					}
					if (intval($aliq) === 0 && !isset($riepiloghinat['N2.2'])) {
						// rate plans with no tax rates assigned will default to N2.2 = Non soggette
						$riepiloghinat['N2.2'] = array('net' => 0, 'tax' => 0);
					}
					$riepiloghivat[$aliq]['net'] += $cost_minus_tax;
					$riepiloghivat[$aliq]['tax'] += $tax;
					$rounded_nets[$aliq] += (float)number_format($cost_minus_tax, 2, '.', '');
					// push linea
					array_push($linee, '
				<DettaglioLinee>
					<NumeroLinea>'.($num + $extralinenum).'</NumeroLinea>
					<Descrizione>'.$this->convertSpecials($descr).'</Descrizione>
					<Quantita>1.00</Quantita>
					<PrezzoUnitario>'.number_format($cost_minus_tax, 2, '.', '').'</PrezzoUnitario>
					<PrezzoTotale>'.number_format($cost_minus_tax, 2, '.', '').'</PrezzoTotale>
					<AliquotaIVA>'.number_format($aliq, 2, '.', '').'</AliquotaIVA>
					' . (intval($aliq) === 0 ? '<Natura>N2.2</Natura>' : '') . '
				</DettaglioLinee>');
				}
				// room options
				if (!empty($or['optionals'])) {
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
						$descr = $actopt[0]['is_citytax'] == 1 ? VikBookingAgenziaEntrateConstants::DESCRTOURISTTAX : sprintf(VikBookingAgenziaEntrateConstants::DESCRROOMOPTION, strtoupper($actopt[0]['name']));
						if (!isset($riepiloghivat[$aliq])) {
							$riepiloghivat[$aliq] = array('net' => 0, 'tax' => 0);
							$rounded_nets[$aliq] = 0;
						}
						
						/**
						 * City taxes or other fees should use N1 = Escluse ex. art. 15.
						 * While other options with no tax will default to N2.2 = Non soggette.
						 * We now also detect the "imposta di bollo".
						 * 
						 * @since 	November 3rd 2020, updated on January 12th 2021
						 */
						$option_natura = $actopt[0]['is_citytax'] == 1 || $actopt[0]['is_fee'] == 1 ? 'N1' : 'N2.2';
						if ($actopt[0]['is_fee'] == 1 && stripos($actopt[0]['name'], 'imposta') !== false && stripos($actopt[0]['name'], 'bollo') !== false) {
							// imposta di bollo
							$option_natura = 'N1';
							$imposta_bollo_cost = $opt_minus_tax;
						}
						if (intval($aliq) === 0 && !isset($riepiloghinat[$option_natura])) {
							// options can support two types of Natura
							$riepiloghinat[$option_natura] = array('net' => 0, 'tax' => 0);
						}
						if (intval($aliq) === 0) {
							$riepiloghinat[$option_natura]['net'] += $opt_minus_tax;
							$riepiloghinat[$option_natura]['tax'] += $tax;
						}
						//

						if (count($riepiloghinat) < 2 || $option_natura == 'N2.2') {
							/**
							 * We should increase the net and taxes for this aliquote only if the types
							 * of Natura is one or zero, as rooms, options or extra costs of no particular type
							 * (imposta bollo/tassa soggiorno) will always share the same natura even if the
							 * aliquote is zero, meaning that has no tax rates assigned.
							 */
							$riepiloghivat[$aliq]['net'] += $opt_minus_tax;
							$riepiloghivat[$aliq]['tax'] += $tax;
							$rounded_nets[$aliq] += (float)number_format($opt_minus_tax, 2, '.', '');
						}

						// push linea
						array_push($linee, '
					<DettaglioLinee>
						<NumeroLinea>'.($num + $extralinenum).'</NumeroLinea>
						<Descrizione>'.$this->convertSpecials($descr).'</Descrizione>
						<Quantita>1.00</Quantita>
						<PrezzoUnitario>'.number_format($opt_minus_tax, 2, '.', '').'</PrezzoUnitario>
						<PrezzoTotale>'.number_format($opt_minus_tax, 2, '.', '').'</PrezzoTotale>
						<AliquotaIVA>'.number_format($aliq, 2, '.', '').'</AliquotaIVA>
						' . (intval($aliq) === 0 ? '<Natura>' . $option_natura . '</Natura>' : '') . '
					</DettaglioLinee>');
					}
				}
				// custom extra costs
				if (!empty($or['extracosts'])) {
					$cur_extra_costs = json_decode($or['extracosts'], true);
					foreach ($cur_extra_costs as $eck => $ecv) {
						// increase line number
						$extralinenum++;
						//
						$ecplustax = !empty($ecv['idtax']) ? VikBooking::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
						$isdue += $ecplustax;
						$descr = sprintf(VikBookingAgenziaEntrateConstants::DESCRROOMEXTRACOST, strtoupper($ecv['name']));
						if ($ecplustax == $ecv['cost']) {
							$ec_minus_tax = !empty($ecv['idtax']) ? VikBooking::sayOptionalsMinusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
							$tax = ($ecv['cost'] - $ec_minus_tax);
						} else {
							$ec_minus_tax = ($ecplustax - $ecv['cost']);
							$tax = ($ecplustax - $ecv['cost']);
						}
						$aliq = $this->getAliquoteById($ecv['idtax']);
						if (!isset($riepiloghivat[$aliq])) {
							$riepiloghivat[$aliq] = array('net' => 0, 'tax' => 0);
							$rounded_nets[$aliq] = 0;
						}
						
						/**
						 * The Channel Manager can store custom extra services for tourist taxes,
						 * and so we need to be able to detect the type of Natura to use.
						 * We now also detect the "imposta di bollo".
						 * 
						 * @since 	January 12th 2021
						 */
						$extracost_natura = 'N2.2';
						if (stripos($ecv['name'], 'soggiorno') !== false || stripos($ecv['name'], 'city') !== false || stripos($ecv['name'], 'touris') !== false) {
							// it's a city tax ("tassa di soggiorno", "city tax", "tourism levy", "tourist tax")
							$extracost_natura = 'N1';
							$descr = VikBookingAgenziaEntrateConstants::DESCRTOURISTTAX;
						} elseif (stripos($ecv['name'], 'imposta') !== false && stripos($ecv['name'], 'bollo') !== false) {
							// imposta di bollo
							$extracost_natura = 'N1';
							$imposta_bollo_cost = $ec_minus_tax;
						}
						if (intval($aliq) === 0 && !isset($riepiloghinat[$extracost_natura])) {
							// custom extra costs can support two types of Natura, but default to N2.2 = Non soggette
							$riepiloghinat[$extracost_natura] = array('net' => 0, 'tax' => 0);
						}
						if (intval($aliq) === 0) {
							$riepiloghinat[$extracost_natura]['net'] += $ec_minus_tax;
							$riepiloghinat[$extracost_natura]['tax'] += $tax;
						}
						//

						if (count($riepiloghinat) < 2 || $extracost_natura == 'N2.2') {
							/**
							 * We should increase the net and taxes for this aliquote only if the types
							 * of Natura is one or zero, as rooms, options or extra costs of no particular type
							 * (imposta bollo/tassa soggiorno) will always share the same natura even if the
							 * aliquote is zero, meaning that has no tax rates assigned.
							 */
							$riepiloghivat[$aliq]['net'] += $ec_minus_tax;
							$riepiloghivat[$aliq]['tax'] += $tax;
							$rounded_nets[$aliq] += (float)number_format($ec_minus_tax, 2, '.', '');
						}
						
						// push linea
						array_push($linee, '
					<DettaglioLinee>
						<NumeroLinea>'.($num + $extralinenum).'</NumeroLinea>
						<Descrizione>'.$this->convertSpecials($descr).'</Descrizione>
						<Quantita>1.00</Quantita>
						<PrezzoUnitario>'.number_format($ec_minus_tax, 2, '.', '').'</PrezzoUnitario>
						<PrezzoTotale>'.number_format($ec_minus_tax, 2, '.', '').'</PrezzoTotale>
						<AliquotaIVA>'.number_format($aliq, 2, '.', '').'</AliquotaIVA>
						' . (intval($aliq) === 0 ? '<Natura>' . $extracost_natura . '</Natura>' : '') . '
					</DettaglioLinee>');
					}
				}
				//
			}
			//
		}
		
		// check discount (coupon and/or refund)
		$scontonode = '';
		$discountval = 0;
		if (isset($data[0]['coupon']) && strlen($data[0]['coupon']) > 0) {
			$expcoupon = explode(";", $data[0]['coupon']);
			$discountval += (float)$expcoupon[1];
		}
		if (isset($data[0]['refund']) && $data[0]['refund'] > 0) {
			$discountval += $data[0]['refund'];
		}
		if ($discountval > 0) {
			$scontonode = '
			<ScontoMaggiorazione>
				<Tipo>'.VikBookingAgenziaEntrateConstants::SCONTO.'</Tipo>
				<Importo>'.number_format($discountval, 2, '.', '').'</Importo>
			</ScontoMaggiorazione>';
		}

		/**
		 * Check whether the "Imposta di Bollo" should be added to "DatiGeneraliDocumento".
		 * 
		 * @since 	January 12th 2021
		 */
		$imposta_bollo_node = '';
		if (isset($imposta_bollo_cost) && $imposta_bollo_cost > 0) {
			$imposta_bollo_node = '
			<DatiBollo>
				<BolloVirtuale>SI</BolloVirtuale>
				<ImportoBollo>' . number_format($imposta_bollo_cost, 2, '.', '') . '</ImportoBollo>
			</DatiBollo>';
		}

		// build riepiloghi IVA
		foreach ($riepiloghivat as $aliq => $riepilogo) {
			$totnet = number_format($riepilogo['net'], 2, '.', '');
			$tottax = number_format($riepilogo['tax'], 2, '.', '');
			if (isset($rounded_nets[$aliq]) && (float)$totnet != $rounded_nets[$aliq]) {
				/**
				 * In case of several rows in the invoice, maybe a lot of Extra Services,
				 * there can be a discrepancy between the sum of the <PrezzoUnitario> nodes
				 * in the <DettaglioLinee> nodes, and the <ImponibileImporto> in <DatiRiepilogo>.
				 * We need to prevent the amounts to be different because of number_format and
				 * adjust the amounts and obtain the same value as the sum of the nets in the lines.
				 * The issue was tested with 9 Extra Services, one Room, one Tourist Tax (Option).
				 * 
				 * @see 	sandbox booking ID 1140
				 * 
				 * @since 	June 30th 2019
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
			/**
			 * Depending on the type of service, when the tax aliquote is 0/empty,
			 * we support different types of Natura.
			 * 
			 * @since 	November 3rd 2020
			 * @since 	January 11th 2021 - Natura N2 was replaced with N2.2
			 */
			if (intval($aliq) === 0) {
				// default to "N2.2 = Non soggette"
				$use_natura = 'N2.2';
				foreach ($riepiloghinat as $natura_code => $natdet) {
					// grab the first Natura code and unset it to make sure we use them all
					$use_natura = $natura_code;
					unset($riepiloghinat[$natura_code]);
					break;
				}
			}
			//
			array_push($riepiloghi, '
			<DatiRiepilogo>
				<AliquotaIVA>'.number_format($aliq, 2, '.', '').'</AliquotaIVA>
				' . (intval($aliq) === 0 ? '<Natura>' . $use_natura . '</Natura>' : '') . '
				<ImponibileImporto>'.$totnet.'</ImponibileImporto>
				<Imposta>'.$tottax.'</Imposta>
			</DatiRiepilogo>');
		}

		/**
		 * We may need to build another "riepilogo IVA" for other types of Natura.
		 * 
		 * @since 	November 3rd 2020
		 */
		if (count($riepiloghinat)) {
			// we still got some Natura codes left to include
			foreach ($riepiloghinat as $natura_code => $natdet) {
				array_push($riepiloghi, '
				<DatiRiepilogo>
					<AliquotaIVA>'.number_format(0, 2, '.', '').'</AliquotaIVA>
					<Natura>' . $natura_code . '</Natura>
					<ImponibileImporto>'.number_format($natdet['net'], 2, '.', '').'</ImponibileImporto>
					<Imposta>'.number_format($natdet['tax'], 2, '.', '').'</Imposta>
				</DatiRiepilogo>');
			}
		}
		//

		// build XML
		$xml = VikBookingAgenziaEntrateConstants::XMLOPENINGTAG.'
<p:FatturaElettronica xmlns:ds="'.VikBookingAgenziaEntrateConstants::XMLNS_DS.'" xmlns:p="'.VikBookingAgenziaEntrateConstants::XMLNS_P.'" versione="'.VikBookingAgenziaEntrateConstants::XMLNS_V.'">
	<FatturaElettronicaHeader>
		<DatiTrasmissione>
			<IdTrasmittente>
				<IdPaese>'.VikBookingAgenziaEntrateConstants::TRASMITTENTEIDPAESE.'</IdPaese>
				<IdCodice>'.$settings['params']['fisccode'].'</IdCodice>
			</IdTrasmittente>
			<ProgressivoInvio>'.$settings['progcount'].'</ProgressivoInvio>
			<FormatoTrasmissione>'.VikBookingAgenziaEntrateConstants::FORMATOTRASMISSIONE.'</FormatoTrasmissione>
			<CodiceDestinatario>'.$coddest.'</CodiceDestinatario>
			'.(!empty($pecdest) ? '<PECDestinatario>'.$pecdest.'</PECDestinatario>' : '').'
		</DatiTrasmissione>
		<CedentePrestatore>
			<DatiAnagrafici>
				<IdFiscaleIVA>
					<IdPaese>'.VikBookingAgenziaEntrateConstants::TRASMITTENTEIDPAESE.'</IdPaese>
					<IdCodice>'.$settings['params']['vatid'].'</IdCodice>
				</IdFiscaleIVA>
				<CodiceFiscale>'.$settings['params']['fisccode'].'</CodiceFiscale>
				<Anagrafica>
					'.(!empty($settings['params']['companyname']) ? '<Denominazione>'.$this->convertSpecials($settings['params']['companyname']).'</Denominazione>' : '' ).'
					'.(empty($settings['params']['companyname']) ? '<Nome>'.$this->convertSpecials($settings['params']['name']).'</Nome>'."\n".'<Cognome>'.$this->convertSpecials($settings['params']['lname']).'</Cognome>' : '' ).'
				</Anagrafica>
				<RegimeFiscale>'.$settings['params']['regimfisc'].'</RegimeFiscale>
			</DatiAnagrafici>
			<Sede>
				<Indirizzo>'.$this->convertSpecials($settings['params']['address']).'</Indirizzo>
				<NumeroCivico>'.$settings['params']['nciv'].'</NumeroCivico>
				<CAP>'.$settings['params']['zip'].'</CAP>
				<Comune>'.$this->convertSpecials($settings['params']['city']).'</Comune>
				<Provincia>'.$settings['params']['province'].'</Provincia>
				<Nazione>'.VikBookingAgenziaEntrateConstants::TRASMITTENTEIDPAESE.'</Nazione>
			</Sede>
			<Contatti>
				<Telefono>'.$this->convertSpecials($settings['params']['phone']).'</Telefono>
				<Email>'.$settings['params']['pec'].'</Email>
			</Contatti>
		</CedentePrestatore>
		<CessionarioCommittente>
			<DatiAnagrafici>
				'.(!empty($data[0]['customer']['country_2_code']) && !empty($data[0]['customer']['vat']) ? '
				<IdFiscaleIVA>
					<IdPaese>'.$data[0]['customer']['country_2_code'].'</IdPaese>
					<IdCodice>'.$data[0]['customer']['vat'].'</IdCodice>
				</IdFiscaleIVA>
				' : '').'
				'.(!empty($data[0]['customer']['fisccode']) && !empty($data[0]['customer']['country_2_code']) && $data[0]['customer']['country_2_code'] == 'IT' ? '
				<CodiceFiscale>'.$data[0]['customer']['fisccode'].'</CodiceFiscale>
				' : '').'
				<Anagrafica>
					'.(!empty($data[0]['customer']['company']) ? '<Denominazione>'.$this->convertSpecials($data[0]['customer']['company']).'</Denominazione>' : '' ).'
					'.(empty($data[0]['customer']['company']) ? '<Nome>'.$this->convertSpecials($data[0]['customer']['first_name']).'</Nome>'."\n".'<Cognome>'.$this->convertSpecials($data[0]['customer']['last_name']).'</Cognome>' : '' ).'
				</Anagrafica>
			</DatiAnagrafici>
			<Sede>
				<Indirizzo>'.$this->convertSpecials($data[0]['customer']['address']).'</Indirizzo>
				<CAP>'.$data[0]['customer']['zip'].'</CAP>
				<Comune>'.$this->convertSpecials($data[0]['customer']['city']).'</Comune>
				'.(!empty($province) ? '
				<Provincia>'.$province.'</Provincia>
				' : '').'
				<Nazione>'.$data[0]['customer']['country_2_code'].'</Nazione>
			</Sede>
		</CessionarioCommittente>
	</FatturaElettronicaHeader>
	<FatturaElettronicaBody>
		<DatiGenerali>
			<DatiGeneraliDocumento>
				<TipoDocumento>' . VikBookingAgenziaEntrateConstants::TIPODOCUMENTO_DEFAULT . '</TipoDocumento>
				<Divisa>' . (isset($data[0]['chcurrency']) && strlen($data[0]['chcurrency']) == 3 ? strtoupper($data[0]['chcurrency']) : VikBookingAgenziaEntrateConstants::DIVISA) . '</Divisa>
				<Data>' . $invdate . '</Data>
				<Numero>' . $invnum . $invsuf . '</Numero>
				' . $imposta_bollo_node . '
				' . $scontonode . '
				<ImportoTotaleDocumento>' . number_format($data[0]['total'], 2, '.', '') . '</ImportoTotaleDocumento>
			</DatiGeneraliDocumento>
		</DatiGenerali>
		<DatiBeniServizi>
			' . implode("\n", $linee) . '
			' . implode("\n", $riepiloghi) . '
		</DatiBeniServizi>
	</FatturaElettronicaBody>
</p:FatturaElettronica>';

		$this->formatXmlString($xml);

		// it doesn't look like we can validate the XML against the schema because the process runs out of execution time
		// $this->validateXmlAgainstSchema($xml);

		if ($this->debugging()) {
			$this->setWarning('<pre>'.htmlentities($xml).'</pre><br/><br/>');
			// break the process when in debug mode
			return false;
		}

		// at this point we are no longer in debug mode so we proceed with the generation

		// invoice name (codice paese + codice fiscale + progressivo univoco file che per noi è il nodo ProgressivoInvio)
		$einvname = VikBookingAgenziaEntrateConstants::TRASMITTENTEIDPAESE . $settings['params']['fisccode'] . '_' . $settings['progcount'] . '.xml';

		// prepare object for storing the invoice
		$jdate = new JDate;
		$einvobj = new stdClass;
		$einvobj->driverid = $settings['id'];
		$einvobj->created_on = $jdate->toSql();
		$einvobj->for_date = $invdate;
		$einvobj->filename = $einvname;
		$einvobj->number = $invnum;
		$einvobj->idorder = $data[0]['id'];
		$einvobj->idcustomer = !empty($data[0]['customer']['id']) ? $data[0]['customer']['id'] : 0;
		$einvobj->country = !empty($data[0]['customer']['country']) ? $data[0]['customer']['country'] : null;
		$einvobj->recipientcode = !empty($data[0]['customer']['recipcode']) && $data[0]['customer']['country'] == 'ITA' ? $data[0]['customer']['recipcode'] : '';
		$einvobj->xml = $xml;
		// always reset transmitted and obliterated values for new e-invoices
		$einvobj->transmitted = 0;
		$einvobj->obliterated = 0;

		$newinvid = $this->storeEInvoice($einvobj);
		if ($newinvid === false) {
			$this->setError('Errore nella creazione della fattura elettronica per la prenotazione ID '.$data[0]['id']);
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
		// update ProgressivoInvio driver setting by increasing it for the next run
		$this->updateProgressiveNumber(++$settings['progcount']);

		if (!$this->hasAnalogicInvoice($data[0]['id'])) {
			// no analogic invoice in PDF available, so we create it
			if (!$this->generateAnalogicInvoice($data[0]['id'], $invnum, $invdate)) {
				// raise warning in case of error
				$this->setWarning('Non è stato possibile creare la fattura analogica in PDF per la prenotazione ID '.$data[0]['id']);
			}
		}

		return true;
	}

	/**
	 * Returns the calculated tariffs given their IDs per room booked.
	 *
	 * @param 	array 	the booking array with one array-room per array value
	 *
	 * @return 	array 	associative array of tariffs for each room booked
	 */
	private function getBookingTariffs($booking)
	{
		$tars = array();

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
	 * Transmits the electronic invoices to the SdI according to the input parameters.
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
		if ($settings === false || !count($settings['params'])) {
			$this->setError('Impostazioni mancanti per la trasmissione delle fatture. Configuare prima il driver.');
			return false;
		}
		// make sure the settings we need are not empty
		$required = array(
			$settings['params']['pecsdi'],
			$settings['params']['pec'],
			$settings['params']['hostpec'],
			$settings['params']['portpec'],
			$settings['params']['pwdpec'],
		);
		foreach ($required as $reqset) {
			if (empty($reqset)) {
				$this->setError('Impostazioni mancanti per la trasmissione delle fatture. Inserire tutte le informazioni dalle impostazioni del driver.');
				return false;
			}
		}

		// call the main method to generate rows, cols and bookings array
		$this->getBookingsData();

		if (strlen($this->getError()) || !count($this->bookings)) {
			return false;
		}

		// pool of e-invoice IDs to transmit
		$einvspool = array();

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
		}

		if (!count($einvspool)) {
			// no e-invoices generated or ready to be transmitted
			$this->setWarning('Nessuna fattura elettronica pronta per essere trasmessa al SdI. Generare prima le fatture elettroniche o selezionarne alcuna per la ritrasmissione.');
			return false;
		}

		// build attachment
		$einvattachpath = $this->buildTransmissionAttachment($einvspool, $settings);
		if ($einvattachpath === false) {
			// something went wrong with the creation of the file to attach via PEC
			$this->setError('Errore nella creazione del file da allegare via PEC al SdI. Impossibile procedere.');
			return false;
		}

		// send attachment to SdI
		if (!$this->sendAttachmentToSdI($einvattachpath, $settings)) {
			$this->setError('Impossibile trasmettere dati via PEC al SdI. Controllare le impostazioni della PEC.');
			return false;
		}

		// clean up temporary files
		$this->cleanTemporaryFiles();

		// update ProgressivoInvio driver setting by increasing it for the next run
		$this->updateProgressiveNumber(++$settings['progcount']);
		
		// set to transmitted=1 all e-invoice IDs that were transmitted
		foreach ($einvspool as $einvid) {
			$data = new stdClass;
			$data->id = $einvid;
			$data->transmitted = 1;
			$this->updateEInvoice($data);
		}

		// display info message
		$this->setInfo('Fatture elettroniche trasmesse: '.count($einvspool));

		// we need to unset the bookings var so that the later call to getBookingsData() made by the View will reload the information
		$this->bookings = array();
		// unset also cols, rows and footer row to not merge data
		$this->cols = array();
		$this->rows = array();
		$this->footerRow = array();

		return true;
	}

	/**
	 * Generates one XML temporary file for each e-invoice ID passed. If more than one, the XML files
	 * are placed inside an archive zip file. The path of the generated file is returned so that it
	 * can be attached via certified email to be sent to the Sistema di Interscambio for verification.
	 *
	 * @param 	array 	$einvspool 	an array of e-invoice IDs
	 * @param 	array 	$settings 	the driver settings
	 *
	 * @return 	mixed 	string with the path of the generated attachment file, false on failure
	 */
	private function buildTransmissionAttachment($einvspool, $settings)
	{
		// clean up temporary files
		$this->cleanTemporaryFiles();

		if (!is_array($einvspool) || !count($einvspool)) {
			return false;
		}

		// the base path for storing the XML files
		$xmlbase = $this->driverHelperPath . 'xml' . DIRECTORY_SEPARATOR;

		// the paths of the generated XML files
		$xmlpaths = array();

		// generate XML files for the requested e-invoice IDs
		foreach ($einvspool as $einvid) {
			// load e-invoice details
			$einv_data = $this->loadEInvoiceDetails($einvid);
			if (!$einv_data || !is_array($einv_data)) {
				// all e-invoices must exist as they will be set to transmitted=1 so we break the process
				$this->setError('Impossibile caricare dati per la fattura elettronica ID '.$einvid);
				return false;
			}
			$fp = fopen($xmlbase.$einv_data['filename'], 'w+');
			$bytes = fwrite($fp, $einv_data['xml']);
			fclose($fp);
			if (!$bytes || !is_file($xmlbase . $einv_data['filename'])) {
				// all e-invoices must exist as they will be set to transmitted=1 so we break the process
				$this->setError('Impossibile creare il file '.$xmlbase.$einv_data['filename'].' - controllare i permessi di scrittura sul server.');
				return false;
			}
			// push path
			array_push($xmlpaths, $xmlbase.$einv_data['filename']);
		}

		if (!count($xmlpaths)) {
			// no XML files created, break the process
			return false;
		}

		if (count($xmlpaths) === 1) {
			// just one XML file, no need to create an archive
			return $xmlpaths[0];
		}

		// compress all the XML files into a zip archive (if ZipArchive exists)
		if (!class_exists('ZipArchive')) {
			// unable to proceed, only one e-invoice per time should be requested for transmission
			$this->setError('La Classe PHP ZipArchive non è disponibile sul tuo server e quindi non è possibile creare un archivio ZIP per le fatture XML. Richiedi alla tua hosting company di abilitare questa funzione nativa di PHP sul tuo server, altrimenti devi trasmettere una fattura elettronica alla volta in XML piuttosto che nel formato ZIP.');
			return false;
		}

		$to_zip = array();
		foreach ($xmlpaths as $k => $xmlpath) {
			$to_zip[$k]['name'] = basename($xmlpath);
			$to_zip[$k]['path'] = $xmlpath;
		}

		// zip name (codice paese + codice fiscale + progressivo univoco file che per noi è il nodo ProgressivoInvio)
		$zip_name = VikBookingAgenziaEntrateConstants::TRASMITTENTEIDPAESE . $settings['params']['fisccode'] . '_' . $settings['progcount'] . '.zip';

		$zip_path = $xmlbase . $zip_name;
		$zip = new ZipArchive;
		$zip->open($zip_path, ZipArchive::CREATE);
		foreach ($to_zip as $zipv) {
			$zip->addFile($zipv['path'], $zipv['name']);
		}
		$zip->close();

		if (!is_file($zip_path)) {
			// zip file does not exist for some reason, so output the error
			$this->setError('Errore durante la creazione di un archivio ZIP con le fatture XML da inviare. Riprova.');
			return false;
		}

		// return the path of the generated ZIP file
		return $zip_path;
	}

	/**
	 * Loads the details of the given e-invoice ID. The given ID should not be obliterated.
	 *
	 * @param 	int 	$einvid 	the ID of the e-invoice
	 *
	 * @return 	mixed 	array if the e-invoice exists and is not obliterated, false otherwise.
	 */
	private function loadEInvoiceDetails($einvid)
	{
		if (empty($einvid)) {
			return false;
		}

		$q = "SELECT * FROM `#__vikbooking_einvoicing_data` WHERE `id`=" . $einvid . " AND `obliterated`=0;";
		$this->dbo->setQuery($q);
		$record = $this->dbo->loadAssoc();

		if ($record) {
			return $record;
		}

		return false;
	}

	/**
	 * Removes any temporary ZIP and XML file created for the transmission.
	 *
	 * @return 	void
	 */
	private function cleanTemporaryFiles()
	{
		if (!function_exists('jimport')) {
			// prevent Fatal Errors that could break the whole process
			return;
		}

		// import libraries
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		if (!class_exists('JFolder') || !class_exists('JFile')) {
			// prevent Fatal Errors that could break the whole process
			return;
		}

		$tmpfiles = JFolder::files($this->driverHelperPath.'xml', '.xml|.zip');
		foreach ($tmpfiles as $tmpfile) {
			JFile::delete($tmpfile);
		}
	}

	/**
	 * Sends via PEC the attachment to the SdI for verification.
	 * Forces an SMTP connection through PHPMailer by using the driver settings.
	 *
	 * @param 	string 	$file 		the path to the file to attach
	 * @param 	array 	$settings 	the driver settings
	 *
	 * @return 	boolean
	 */
	private function sendAttachmentToSdI($file, $settings)
	{
		if (empty($file) || empty($settings)) {
			return false;
		}

		// build sender details
		$sender = array($settings['params']['pec'], (!empty($settings['params']['companyname']) ? $settings['params']['companyname'] : $settings['params']['name'].' '.$settings['params']['lname']));

		// get JMail instance
		$mailer = JFactory::getMailer();

		// force the use of the SMTP through PHPMailer
		$mailer->useSmtp(true, gethostbyname($settings['params']['hostpec']), $settings['params']['pec'], $settings['params']['pwdpec'], 'tls', $settings['params']['portpec']);
		
		// PHPMailer setup
		$mailer->setSender($sender);
		$mailer->addRecipient($settings['params']['pecsdi']);
		$mailer->addReplyTo($settings['params']['pec']);
		// set attachment
		$mailer->addAttachment($file);
		// set SMTP Options for signature
		$mailer->SMTPOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		);
		// compose body and subject
		$mailer->setSubject(VikBookingAgenziaEntrateConstants::getTransmissionMailSubject($settings));
		$mailer->setBody(VikBookingAgenziaEntrateConstants::getTransmissionMailBody($settings));
		// enable debug mode if requested
		if ($this->debugging()) {
			$mailer->SMTPDebug = 4;
    		$mailer->Debugoutput = 'html';
		}

		// send the message
		if (!$mailer->Send()) {
			$this->setError($mailer->ErrorInfo);
			return false;
		}

		return true;
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
		if ($settings === false || !count($settings['params'])) {
			$this->setError('Impostazioni mancanti. Configuare prima il driver.');
			return false;
		}

		// call the main method to generate rows, cols and bookings array
		$this->getBookingsData();

		if (strlen($this->getError()) || !count($this->bookings)) {
			return false;
		}

		// pool of e-invoice IDs to transmit
		$einvspool = array();

		foreach ($this->bookings as $gbook) {
			// make sure an electronic invoice was already issued for this booking ID by this driver
			if (empty($gbook[0]['einvid']) || $gbook[0]['einvdriver'] != $this->getDriverId()) {
				// no e-invoices available for this booking, skipping
				continue;
			}

			// push e-invoice ID to the pool
			array_push($einvspool, $gbook[0]['einvid']);
		}

		if (!count($einvspool)) {
			// no e-invoices generated
			$this->setWarning('Nessuna fattura elettronica scaricabile. Generare prima le fatture elettroniche.');
			return false;
		}

		// build attachment
		$einvattachpath = $this->buildTransmissionAttachment($einvspool, $settings);
		if ($einvattachpath === false) {
			// something went wrong with the creation of the file to download
			$this->setError('Errore nella creazione del file da scaricare. Impossibile procedere.');
			return false;
		}

		$ext = substr($einvattachpath, strrpos($einvattachpath, '.') + 1);
		$conttype = 'application/zip';
		if ($ext == 'xml') {
			$conttype = 'text/xml';
		}

		// force the download
		header("Content-type:{$conttype}");
		header("Content-Disposition:attachment;filename=".'fatture-elettroniche_'.date('Y-m-d').'.'.$ext);
		header("Content-Length:".filesize($einvattachpath));
		readfile($einvattachpath);

		// clean up temporary files
		$this->cleanTemporaryFiles();

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
		$einvid = VikRequest::getInt('einvid', '', 'request');
		$einv_data = $this->loadEInvoiceDetails($einvid);
		if (!$einv_data) {
			die('ID fattura mancante');
		}

		// inject the stylesheet node as second line of the XML
		$einv_data['xml'] = str_replace(
			VikBookingAgenziaEntrateConstants::XMLOPENINGTAG, 
			VikBookingAgenziaEntrateConstants::XMLOPENINGTAG."\n".VikBookingAgenziaEntrateConstants::getXlsNode(), 
			$einv_data['xml']
		);

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
			$this->setError('ID fattura mancante, impossibile eliminare la fattura.');
			return false;
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

		$this->setInfo(($pdfremoved ? 'Rimossa fattura elettronica ed in versione PDF' : 'Fattura elettronica rimossa'));
	}

	/**
	 * Validates the XML against the Schema.
	 *
	 * @param 	string 		$xml 	the xml string to validate
	 *
	 * @return 	boolean
	 */
	private function validateXmlAgainstSchema($xml) {
		if (!class_exists('DOMDocument')) {
			// we cannot validate the XML because DOMDocument is missing
			return true;
		}

		$schemaPath = VikBookingAgenziaEntrateConstants::getSchemaPath();

		libxml_use_internal_errors(true);
		
		$dom = new DOMDocument();
		$dom->load($xml);
		if (!$dom->schemaValidate($schemaPath)) {
			$this->setWarning('Lo Schema di validazione per la fattura elettronica in formato XML ha ritornato degli errori.');
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
	private function libxml_display_errors() {
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
	private function libxml_display_error($error) {
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
			$return .= " in ".$error->file;
		}
		$return .= " alla riga ".$error->line."\n";

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
		$content = VikRequest::getString('drivercontent', '', 'request');
		$einvid = VikRequest::getInt('einvid', '', 'request');
		if ($content == 'editEInvoice' && !empty($einvid)) {
			$einv_data = $this->loadEInvoiceDetails($einvid);
			if (!$einv_data) {
				return;
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
	 * @return 	boolean
	 */
	public function updateXmlEInvoice()
	{
		$einvid = VikRequest::getInt('einvid', '', 'request');
		$newxml = VikRequest::getString('newxml', '', 'request', VIKREQUEST_ALLOWRAW);
		$einv_data = $this->loadEInvoiceDetails($einvid);
		if (!$einv_data) {
			$this->setError('Fattura non trovata');
			return false;
		}
		if (empty($newxml)) {
			$this->setError('Contenuto XML vuoto');
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
	 * Extracts only numbers from a given string, by optionally
	 * stripping the current year. Useful to find an invoice number.
	 *
	 * @param 	string 		$str 		the string to look for numbers
	 * @param 	boolean 	$stripy 	whether to strip the current year
	 *
	 * @return 	string 	either an empty string or all numbers as a concatenated string
	 */
	private function getOnlyNumbers($str, $stripy = false)
	{
		if ($stripy) {
			$str = str_replace(date('Y'), '', $str);
		}

		preg_match_all('/\d+/', $str, $matches);

		return implode('', $matches[0]);
	}
}

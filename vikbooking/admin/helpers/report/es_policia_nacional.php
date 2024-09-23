<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Lorenzo Monsani - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2020 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

class VikBookingReportEsPoliciaNacional extends VikBookingReport
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 */
	public $defaultKeySort = 'idbooking';
	/**
	 * Property 'defaultKeyOrder' is used by the View that renders the report.
	 */
	public $defaultKeyOrder = 'ASC';
	/**
	 * Property 'customExport' is used by the View to display custom export buttons.
	 */
	public $customExport = '';
	/**
	 * Debug mode is activated by passing the value 'e4j_debug' > 0
	 */
	private $debug;

	/**
	 * Other private vars of this sub-class.
	 */
	private $nazioni;
	private $documenti;

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = "Policia Nacional";
		$this->reportFilters = array();

		$this->cols = array();
		$this->rows = array();
		$this->footerRow = array();

		$this->debug = (VikRequest::getInt('e4j_debug', 0, 'request') > 0);

		$this->registerExportFileName();

		$this->nazioni = array();

		parent::__construct();
	}

	/**
	 * Returns the name of this report.
	 *
	 * @return 	string
	 */
	public function getName()
	{
		return $this->reportName;
	}

	/**
	 * Returns the name of this file without .php.
	 *
	 * @return 	string
	 */
	public function getFileName()
	{
		return $this->reportFile;
	}

	/**
	 * Returns the filters of this report.
	 *
	 * @return 	array
	 */
	public function getFilters()
	{
		if (count($this->reportFilters)) {
			//do not run this method twice, as it could load JS and CSS files.
			return $this->reportFilters;
		}

		//get VBO Application Object
		$vbo_app = VikBooking::getVboApplication();

		//load the jQuery UI Datepicker
		$this->loadDatePicker();

		//custom export button
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadSchedaPoliciaNacional();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download File</span></a>';

		//build the hidden values for the selection of Comuni & Province.
		$this->nazioni = $this->loadNazioni();
		$this->documenti = $this->loadDocumenti();
		$hidden_vals = '<div id="vbo-report-policianacional-hidden" style="display: none;">';
		//
		//Nazioni
		$hidden_vals .= '	<div id="vbo-report-policianacional-nazione" class="vbo-report-policianacional-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-nazione" onchange="vboReportChosenNazione(this);"><option value=""></option>';
		if (count($this->nazioni)) {
			foreach ($this->nazioni as $code => $nazione) {
				$hidden_vals .= '	<option value="'.$nazione['name'].'">'.$nazione['name'].'</option>'."\n";
			}
		}
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';
		//
		//Documenti
		$hidden_vals .= '	<div id="vbo-report-policianacional-doctype" class="vbo-report-policianacional-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-documento" onchange="vboReportChosenDocumento(this);"><option value=""></option>';
		if (count($this->documenti)) {
			foreach ($this->documenti as $code => $documento) {
				$hidden_vals .= '	<option value="'.$code.'">'.$documento.'</option>'."\n";
			}
		}
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';
		//
		//Sesso
		$hidden_vals .= '	<div id="vbo-report-policianacional-sesso" class="vbo-report-policianacional-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-sesso" onchange="vboReportChosenSesso(this);"><option value=""></option>';
		$sessos = array(
			1 => 'M',
			2 => 'F'
		);
		foreach ($sessos as $code => $ses) {
			$hidden_vals .= '	<option value="'.$code.'">'.$ses.'</option>'."\n";
		}
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';
		//
		//Numero Documento
		$hidden_vals .= '	<div id="vbo-report-policianacional-docnum" class="vbo-report-policianacional-selcont" style="display: none;">';
		$hidden_vals .= '		<input type="text" size="40" id="choose-docnum" placeholder="Número del Documento..." value="" /><br/>';
		$hidden_vals .= '		<button type="button" class="btn" onclick="vboReportChosenDocnum(document.getElementById(\'choose-docnum\').value);">'.JText::translate('VBAPPLY').'</button>';
		$hidden_vals .= '	</div>';
		//
		//Data di Nascita
		$hidden_vals .= '	<div id="vbo-report-policianacional-dbirth" class="vbo-report-policianacional-selcont" style="display: none;">';
		$hidden_vals .= '		<input type="text" size="40" id="choose-dbirth" placeholder="'.JText::translate('VBCUSTOMERBDATE').'" value="" /><br/>';
		$hidden_vals .= '		<button type="button" class="btn" onclick="vboReportChosenDbirth(document.getElementById(\'choose-dbirth\').value);">'.JText::translate('VBAPPLY').'</button>';
		$hidden_vals .= '	</div>';

		//Data di Emissione documento
		$hidden_vals .= '	<div id="vbo-report-policianacional-docissue" class="vbo-report-policianacional-selcont" style="display: none;">';
		$hidden_vals .= '		<input type="text" size="40" id="choose-docissue" placeholder="Fecha de expedición del documento" value="" /><br/>';
		$hidden_vals .= '		<button type="button" class="btn" onclick="vboReportChosenDocissue(document.getElementById(\'choose-docissue\').value);">'.JText::translate('VBAPPLY').'</button>';
		$hidden_vals .= '	</div>';
		//
		$hidden_vals .= '</div>';

		//From Date Filter (with hidden values for the dropdown menus of Comuni, Province, Stati etc..)
		$filter_opt = array(
			'label' => '<label for="fromdate">'.JText::translate('VBOREPORTSDATEFROM').'</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />'.$hidden_vals,
			'type' => 'calendar',
			'name' => 'fromdate'
		);
		array_push($this->reportFilters, $filter_opt);

		//To Date Filter
		$filter_opt = array(
			'label' => '<label for="todate">'.JText::translate('VBOREPORTSDATETO').'</label>',
			'html' => '<input type="text" id="todate" name="todate" value="" class="vbo-report-datepicker vbo-report-datepicker-to" />',
			'type' => 'calendar',
			'name' => 'todate'
		);
		array_push($this->reportFilters, $filter_opt);


		// Filtro Nome Hotel
		$photelname = VikRequest::getString('hotelname', '', 'request');
		$filter_opt = array(
			'label' => '<label for="hotelname">Nombre del hotel</label>',
			'html' => '<input type="text" id="hotelname" name="hotelname" value="'.$photelname.'" size="10" />',
			'type' => 'text',
			'name' => 'hotelname'
		);
		array_push($this->reportFilters, $filter_opt);

		// Filtro Codice Hotel
		$photelcode = VikRequest::getString('hotelcode', '', 'request');
		$filter_opt = array(
			'label' => '<label for="hotelcode">Progressive document code</label>',
			'html' => '<input type="text" id="hotelcode" name="hotelcode" value="'.$photelcode.'" min="1" max="999" />',
			'type' => 'text',
			'name' => 'hotelcode'
		);
		array_push($this->reportFilters, $filter_opt);

		$progressivecode = VikRequest::getString('progressivecode', '', 'request');
		$filter_opt = array(
			'label' => '<label for="progressivecode">Código del hotel</label>',
			'html' => '<input type="number" id="progressivecode" name="progressivecode" value="'.$progressivecode.'" size="10" />',
			'type' => 'text',
			'name' => 'progressivecode'
		);
		array_push($this->reportFilters, $filter_opt);

		//jQuery code for the datepicker calendars, select2 and triggers for the dropdown menus
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$js = 'var reportActiveCell = null, reportObj = {};
		jQuery(document).ready(function() {
			//prepare main filters
			jQuery(".vbo-report-datepicker:input").datepicker({
				maxDate: 0,
				dateFormat: "'.$this->getDateFormat('jui').'",
				onSelect: vboReportCheckDates
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			'.(!empty($ptodate) ? 'jQuery(".vbo-report-datepicker-to").datepicker("setDate", "'.$ptodate.'");' : '').'
			//prepare filler helpers
			jQuery("#vbo-report-policianacional-hidden").children().detach().appendTo(".vbo-info-overlay-report");
			jQuery("#choose-nazione").select2({placeholder: "- Selecciona una Nacion -", width: "200px"});
			jQuery("#choose-documento").select2({placeholder: "- Selecciona un Documento -", width: "200px"});
			jQuery("#choose-sesso").select2({placeholder: "- Selecciona Sexo -", width: "200px"});
			jQuery("#choose-dbirth, #choose-docissue").datepicker({
				maxDate: 0,
				dateFormat: "dd/mm/yy",
				changeMonth: true,
				changeYear: true,
				yearRange: "'.(date('Y') - 100).':'.date('Y').'"
			});
			// click events
			jQuery(".vbo-report-load-nazione, .vbo-report-load-cittadinanza").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-policianacional-selcont").hide();
				jQuery("#vbo-report-policianacional-nazione").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-doctype").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-policianacional-selcont").hide();
				jQuery("#vbo-report-policianacional-doctype").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-docplace").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-policianacional-selcont").hide();
				jQuery("#vbo-report-policianacional-nazione").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-sesso").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-policianacional-selcont").hide();
				jQuery("#vbo-report-policianacional-sesso").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-docnum").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-policianacional-selcont").hide();
				jQuery("#vbo-report-policianacional-docnum").show();
				vboShowOverlay();
				setTimeout(function(){jQuery("#choose-docnum").focus();}, 500);
			});
			jQuery(".vbo-report-load-dbirth").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-policianacional-selcont").hide();
				jQuery("#vbo-report-policianacional-dbirth").show();
				vboShowOverlay();
				//pretend the overlay is off, or navigating in the datepicker will close the modal.
				setTimeout(function(){vbo_overlay_on = false;}, 800);
				//
			});
			jQuery(".vbo-report-load-docissue").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-policianacional-selcont").hide();
				jQuery("#vbo-report-policianacional-docissue").show();
				vboShowOverlay();
				//pretend the overlay is off, or navigating in the datepicker will close the modal.
				setTimeout(function(){vbo_overlay_on = false;}, 800);
				//
			});
		});
		function vboReportCheckDates(selectedDate, inst) {
			if (selectedDate === null || inst === null) {
				return;
			}
			var cur_from_date = jQuery(this).val();
			if (jQuery(this).hasClass("vbo-report-datepicker-from") && cur_from_date.length) {
				var nowstart = jQuery(this).datepicker("getDate");
				var nowstartdate = new Date(nowstart.getTime());
				jQuery(".vbo-report-datepicker-to").datepicker("option", {minDate: nowstartdate});
			}
		}
		function vboReportChosenNazione(naz) {
			var c_code = naz.value;
			var c_val = naz.options[naz.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-nazione")) {
						reportObj[nowindex].stabirth = c_code;
					} else if (jQuery(reportActiveCell).hasClass("vbo-report-load-docplace")) {
						reportObj[nowindex].docplace = c_code;
					} else {
						reportObj[nowindex].citizen = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-nazione").val("").select2("data", null, false);
		}
		function vboReportChosenDocumento(doctype) {
			var c_code = doctype.value;
			var c_val = doctype.options[doctype.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					reportObj[nowindex].doctype = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-documento").val("").select2("data", null, false);
		}
		function vboReportChosenSesso(sesso) {
			var c_code = sesso.value;
			var c_val = sesso.options[sesso.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					reportObj[nowindex].gender = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-sesso").val("").select2("data", null, false);
		}
		function vboReportChosenDocnum(val) {
			var c_code = val, c_val = val;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					reportObj[nowindex].docnum = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-docnum").val("");
		}
		function vboReportChosenDbirth(val) {
			var c_code = val, c_val = val;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					reportObj[nowindex].dbirth = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-dbirth").val("");
		}
		function vboReportChosenDocissue(val) {
			var c_code = val, c_val = val;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					reportObj[nowindex].docissue = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-docissue").val("");
		}
		//download function
		function vboDownloadSchedaPoliciaNacional() {
			if (!confirm("¿Está seguro de haber ingresado todos los datos requeridos por la Guardia Civil?")) {
				return false;
			}
			document.adminForm.target = "_blank";
			document.adminForm.action += "&tmpl=component";
			vboSetFilters({exportreport: "1", filler: JSON.stringify(reportObj)}, true);
			setTimeout(function() {
				document.adminForm.target = "";
				document.adminForm.action = document.adminForm.action.replace("&tmpl=component", "");
				vboSetFilters({exportreport: "0", filler: ""}, false);
			}, 1000);
		}
		';
		$this->setScript($js);

		return $this->reportFilters;
	}

	/**
	 * Loads the report data from the DB.
	 * Returns true in case of success, false otherwise.
	 * Sets the columns and rows for the report to be displayed.
	 *
	 * @return 	boolean
	 */
	public function getReportData()
	{
		if (strlen($this->getError())) {
			//Export functions may set errors rather than exiting the process, and the View may continue the execution to attempt to render the report.
			return false;
		}
		$all_countries = VikBooking::getCountriesArray();
		//Input fields and other vars
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$progressivecode = VikRequest::getString('progressivecode', '', 'request');		
		$photelname = VikRequest::getString('hotelname', '', 'request');
		$photelcode = VikRequest::getString('hotelcode', '', 'request');
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

		if (empty($photelcode) || empty($photelname)) {
			$this->setError(JText::translate('VBOREPORTERRNODATA'));
			return false;
		}
		//Get dates timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts) || empty($to_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}

		//Query to obtain the records (all check-ins within the dates filter)
		$records = array();
		$q = "SELECT `o`.`id`,`o`.`custdata`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,".
			"`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`t_first_name`,`or`.`t_last_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,".
			"`co`.`idcustomer`,`co`.`pax_data`,`c`.`first_name`,`c`.`last_name`,`c`.`country` AS `customer_country`,`c`.`doctype`,`c`.`docnum`,`c`.`gender`,`c`.`bdate`,`c`.`pbirth` ".
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` ".
			"LEFT JOIN `#__vikbooking_customers_orders` AS `co` ON `co`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_customers` AS `c` ON `c`.`id`=`co`.`idcustomer` ".
			"WHERE `o`.`status`='confirmed' AND `o`.`closure`=0 AND `o`.`checkin`>=".$from_ts." AND `o`.`checkin`<=".$to_ts." ".
			"ORDER BY `o`.`checkin` ASC, `o`.`id` ASC;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows() > 0) {
			$records = $this->dbo->loadAssocList();
		}
		if (!count($records)) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			$this->setError('No llegan clientes en las fechas seleccionadas.');
			return false;
		}

		//nest records with multiple rooms booked inside sub-array
		$bookings = array();
		foreach ($records as $v) {
			if (!isset($bookings[$v['id']])) {
				$bookings[$v['id']] = array();
			}
			array_push($bookings[$v['id']], $v);
		}

		//define the columns of the report
		$this->cols = array(
			//check-in
			array(
				'key' => 'checkin',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBPICKUPAT')
			),
			//cognome
			array(
				'key' => 'cognome',
				'label' => JText::translate('VBTRAVELERLNAME')
			),
			//nome
			array(
				'key' => 'nome',
				'label' => JText::translate('VBTRAVELERNAME')
			),
			//sesso
			array(
				'key' => 'gender',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERGENDER')
			),
			//data di nascita
			array(
				'key' => 'dbirth',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERBDATE')
			),
			//cittadinanza
			array(
				'key' => 'citizen',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERNATION')
			),
			//tipo documento
			array(
				'key' => 'doctype',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERDOCTYPE')
			),
			//numero documento
			array(
				'key' => 'docnum',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERDOCNUM')
			),
			//numero documento
			array(
				'key' => 'docissue',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERDOCISSUE')
			),
			//id booking
			array(
				'key' => 'idbooking',
				'attr' => array(
					'class="center"'
				),
				'label' => 'ID'
			)
		);

		//loop over the bookings to build the rows of the report
		$from_info = getdate($from_ts);
		foreach ($bookings as $gbook) {
			$guests_rows = array($gbook[0]);
			$tot_guests_rows = 1;
			$tipo = 16;
			//Codici Tipo Alloggiato
			// 16 = Ospite Singolo
			// 17 = Capofamiglia
			// 18 = Capogruppo
			// 19 = Familiare
			// 20 = Membro Gruppo
			//
			if (!empty($gbook[0]['pax_data'])) {
				$pax_data = json_decode($gbook[0]['pax_data'], true);
				if (count($pax_data)) {
					$guests_rows[0]['pax_data'] = $pax_data;
					$tot_guests_rows = 0;
					foreach ($pax_data as $roomguests) {
						$tot_guests_rows += count($roomguests);
					}
					for ($i = 1; $i < $tot_guests_rows; $i++) {
						array_push($guests_rows, $guests_rows[0]);
					}
					$tipo = count($guests_rows) > 1 ? 17 : $tipo;
				}
			}
			//create one row for each guest
			$guest_ind = 1;
			foreach ($guests_rows as $ind => $guests) {
				$insert_row = array();
				//echo print_r($guests);
				//Data Arrivo
				array_push($insert_row, array(
					'key' => 'checkin',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) {
						return str_replace("/", "", $val);
					},
					'value' => date('Y/m/d', $guests['checkin'])
				));
				//Cognome
				$cognome = !empty($guests['t_last_name']) ? $guests['t_last_name'] : $guests['last_name'];
				if (is_array($guests['pax_data']) && count($guests['pax_data']) > 0) {
					$j = 0;
					foreach ($guests['pax_data'] as $rnum => $rguests) {
						foreach ($rguests as $rguest) {
							$j++;
							if ($j == $guest_ind) {
								$cognome = !empty($rguest['last_name']) ? $rguest['last_name'] : $cognome;
								break 2;
							}
						}
					}
				}
				array_push($insert_row, array(
					'key' => 'cognome',
					'value' => $cognome
				));
				//Nome
				$nome = !empty($guests['t_first_name']) ? $guests['t_first_name'] : $guests['first_name'];
				if (is_array($guests['pax_data']) && count($guests['pax_data']) > 0) {
					$j = 0;
					foreach ($guests['pax_data'] as $rnum => $rguests) {
						foreach ($rguests as $rguest) {
							$j++;
							if ($j == $guest_ind) {
								$nome = !empty($rguest['first_name']) ? $rguest['first_name'] : $nome;
								break 2;
							}
						}
					}
				}
				array_push($insert_row, array(
					'key' => 'nome',
					'value' => $nome
				));
				//Sesso
				$gender = !empty($guests['gender']) && $guest_ind < 2 ? strtoupper($guests['gender']) : '';
				if (is_array($guests['pax_data']) && count($guests['pax_data']) > 0) {
					$j = 0;
					foreach ($guests['pax_data'] as $rnum => $rguests) {
						foreach ($rguests as $rguest) {
							$j++;
							if ($j == $guest_ind) {
								$gender = !empty($rguest['gender']) ? $rguest['gender'] : $gender;
								$gender = $gender == 'Female' ? 'F' : ($gender == 'Male' ? 'M' : $gender);
								break 2;
							}
						}
					}
				}
				array_push($insert_row, array(
					'key' => 'gender',
					'attr' => array(
						'class="center'.(empty($gender) ? ' vbo-report-load-sesso' : '').'"'
					),
					'callback' => function ($val) {
						return !empty($val) ? $val : '?';
					},
					'no_export_callback' => 1,
					'value' => $gender
				));
				//Data di nascita
				$dbirth = !empty($guests['bdate']) ? VikBooking::getDateTimestamp($guests['bdate'], 0, 0) : '';
				if (is_array($guests['pax_data']) && count($guests['pax_data']) > 0) {
					$j = 0;
					foreach ($guests['pax_data'] as $rnum => $rguests) {
						foreach ($rguests as $rguest) {
							$j++;
							if ($j == $guest_ind) {
								$dbirth = !empty($rguest['date_birth']) ? $rguest['date_birth'] : $dbirth;
								break 2;
							}
						}
					}
				}

				$dbirth = VikBooking::getDateTimestamp($dbirth, 0, 0);
				array_push($insert_row, array(
					'key' => 'dbirth',
					'attr' => array(
						'class="center'.(empty($dbirth) ? ' vbo-report-load-dbirth' : '').'"'
					),
					'callback' => function ($val) {
						return !empty($val) ? $val : '?';
					},
					'callback_export' => function ($val) {
						return (strpos($val, '/') === false && strpos($val, VikBooking::getDateSeparator()) === false) ? date('Ymd', $val) : date('Ymd', VikBooking::getDateTimestamp($val, 0, 0));
					},
					'value' => $dbirth > 0 ? date('Y/m/d', $dbirth) : ''
				));

				//Cittadinanza
				$citizen = !empty($guests['country']) && $guest_ind < 2 ? $guests['country'] : '';
				$citizenres = array();
				$citizenval = '';
				if (!empty($citizen) && $guest_ind < 2) {
					$citizenval = $this->checkCountry($citizen);
				}
				if (is_array($guests['pax_data']) && count($guests['pax_data']) > 0) {
					$j = 0;
					foreach ($guests['pax_data'] as $rnum => $rguests) {
						foreach ($rguests as $rguest) {
							$j++;
							if ($j == $guest_ind) {
								$citizen = !empty($rguest['nationality']) ? $rguest['nationality'] : $citizen;
								$citizenval = $this->checkCountry($citizen);
								break 2;
							}
						}
					}
				}

				array_push($insert_row, array(
					'key' => 'citizen',
					'attr' => array(
						'class="center'.(empty($citizen) ? ' vbo-report-load-cittadinanza' : '').'"'
					),
					'callback' => function ($val) {
						return !empty($val) ? strtoupper($val) : '?';
					},
					'no_export_callback' => 1,
					'value' => !empty($citizenval) ? $this->nazioni[$citizenval]['name'] : ''
				));

				//Tipo documento
				$doctype = '?';
				array_push($insert_row, array(
					'key' => 'doctype',
					'attr' => array(
						'class="center'.(' vbo-report-load-doctype' ).'"'
					),
					'value' => $doctype
				));

				//Numero documento
				$docnum = $guests['docnum'];
				if (is_array($guests['pax_data']) && count($guests['pax_data']) > 0) {
					$j = 0;
					foreach ($guests['pax_data'] as $rnum => $rguests) {
						foreach ($rguests as $rguest) {
							$j++;
							if ($j == $guest_ind) {
								$docnum = !empty($rguest['docnum']) ? $rguest['docnum'] : $docnum;
								break 2;
							}
						}
					}
				}

				array_push($insert_row, array(
					'key' => 'docnum',
					'attr' => array(
						'class="center'.(empty($docnum) ? ' vbo-report-load-docnum' : '').'"'
					),
					'callback' => function ($val) {
						return empty($val) ? '?' : $val;
					},
					'value' => $docnum
				));

				array_push($insert_row, array(
					'key' => 'docissue',
					'attr' => array(
						'class="center'.(empty($val) ? ' vbo-report-load-docissue' : '').'"'
					),
					'callback' => function ($val) {
						return !empty($val) ? $val : '?';
					},
					'callback_export' => function ($val) {
						return (strpos($val, '/') === false && strpos($val, VikBooking::getDateSeparator()) === false) ? date('Ymd', $val) : date('Ymd', VikBooking::getDateTimestamp($val, 0, 0));
					},
					'value' => ''
				));

				//id booking
				array_push($insert_row, array(
					'key' => 'idbooking',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) {
						return '<a href="index.php?option=com_vikbooking&task=editorder&cid[]='.$val.'" target="_blank"><i class="'.VikBookingIcons::i('external-link').'"></i> '.$val.'</a>';
					},
					'ignore_export' => 1,
					'value' => $guests['id']
				));
				//push fields in the rows array as a new row
				array_push($this->rows, $insert_row);
				//increment guest index
				$guest_ind++;
				
			}
		}
		
		//do not sort the rows for this report because the lines of the guests of the same booking must be consecutive
		//$this->sortRows($pkrsort, $pkrorder);

		//the footer row will just print the amount of records to export
		array_push($this->footerRow, array(
			array(
				'attr' => array(
					'class="vbo-report-total"'
				),
				'value' => '<h3>'.JText::translate('VBOREPORTSTOTALROW').'</h3>'
			),
			array(
				'attr' => array(
					'colspan="'.(count($this->cols) - 1).'"'
				),
				'value' => count($this->rows)
			)
		));

		//Debug
		if ($this->debug) {
			$this->setWarning('path to report file = '.urlencode(dirname(__FILE__)).'<br/>');
			$this->setWarning('$total_rooms_units = '.$total_rooms_units.'<br/>');
			$this->setWarning('$bookings:<pre>'.print_r($bookings, true).'</pre><br/>');
		}
		//

		return true;
	}

	/**
	 * Generates the text file for the Italian Police, 
	 * then it sends it to output for download.
	 * In case of errors, the process is not terminated (exit)
	 * to let the View display the error message.
	 *
	 * @param 	int 	$export_type 	the view will pass this argument to the method to call different types of export.
	 *
	 * @return 	mixed 	void on success with script termination, false otherwise.
	 */
	public function customExport($export_type = 0)
	{
		if (!$this->getReportData()) {
			return false;
		}
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$photelname = VikRequest::getString('hotelname', '', 'request');
		$photelcode = VikRequest::getString('hotelcode', '', 'request');
		$progressivecode = VikRequest::getString('progressivecode', '', 'request');
		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? json_decode($pfiller, true) : array();
		$pfiller = !is_array($pfiller) ? array() : $pfiller;

		$progressivecode = (int)$progressivecode < 10 ? '00'.(string)$progressivecode : ((int)$progressivecode < 100 ? '0'.(string)$progressivecode : (string)$progressivecode);
		//debug
		//$this->setError('<pre>'.print_r($pfiller, true).'</pre><br/>');
		//return false;
		//

		//pool of booking IDs to update their history
		$booking_ids = array();

		//array of lines (one line for each guest)
		$lines = array();
		$customers = array();
		$customerCount = 0;
		$keyorder = array(
			"docnum", // Doc id for DNI 
			"doctype",
			"docissue",
			"cognome1", 
			"cognome2",
			"nome",
			"gender",
			"dbirth",
			"citizen",
			"checkin"
		);

		//Push the lines of the Text file
		foreach ($this->rows as $ind => $row) {

			$line_cont = '';
			foreach ($row as $field) {
				if ($field['key'] == 'idbooking' && !in_array($field['value'], $booking_ids)) {
					array_push($booking_ids, $field['value']);
				}
				if (isset($field['ignore_export'])) {
					continue;
				}
				//report value
				if (is_array($pfiller) && isset($pfiller[$ind]) && isset($pfiller[$ind][$field['key']])) {
					if (strlen($pfiller[$ind][$field['key']])) {
						$field['value'] = $pfiller[$ind][$field['key']];
					}
				}
				
				// values set to -1 are usually empty and should have been filled in manually
				if ($field['value'] === -1) {
					// we raise an error in this case without stopping the process
					$field['value'] = 0;
					VikError::raiseWarning('', 'La riga #'.$ind.' ha un valore vuoto che doveva essere riempito manualmente cliccando sul blocco in rosso. Il file potrebbe contenere valori invalidi per questa riga.');
				}

				if (isset($field['callback_export'])) {
					$field['callback'] = $field['callback_export'];
				}
				$value = !isset($field['no_export_callback']) && isset($field['callback']) && is_callable($field['callback']) ? $field['callback']($field['value']) : $field['value'];
				//0 or '---' should be changed to an empty string (case of "-- Estero --" or field to be filled with Blank)
				$value = empty($value) || $value == '---' ? '' : $value;

				//this is necessary since the customer may have multiple last names.
				if ($field['key'] == 'cognome') {
					$cognomi = explode(" ", $value);
					$customers[$customerCount]['cognome1'] = $cognomi[0];
					$customers[$customerCount]['cognome2'] = isset($cognomi[1]) ? $cognomi[1] : "";
					continue;
				} 
				//concatenate the field to the customer record
				$customers[$customerCount][$field['key']] = $this->valueFiller($value, '');
			}
			$customerCount++;
		}

		$separator = '|';
		$line_cont = '1' . $separator . strtoupper($photelcode) . $separator . strtoupper($photelname) . $separator . date('Ymd') . $separator . date('Hi') . $separator . count($customers);
		array_push($lines, $line_cont);
		foreach ($customers as $customer) {
			$line_cont = '2|';
			foreach ($keyorder as $key) {
				if ($key == 'docnum') {
					$line_cont .= strtoupper($customer['doctype']) == 'D' || strtoupper($customer['doctype']) == 'P' || strtoupper($customer['doctype']) == 'C' ? (strtoupper($customer[$key]) . $separator . $separator) : ($separator . strtoupper($customer[$key]) . $separator);
					continue;
				}
				$line_cont .= strtoupper($customer[$key]) . $separator;
			}
			$line_cont = rtrim($line_cont, '|');

			//push the line in the array of lines
			array_push($lines, $line_cont);
		}
		

		//update the history for all bookings affected
		foreach ($booking_ids as $bid) {
			VikBooking::getBookingHistoryInstance()->setBid($bid)->store('RP', $this->reportName);
		}

		/**
		 * Custom export method supports a custom export handler, if previously set.
		 * 
		 * @since 	1.16.1 (J) - 1.6.1 (WP)
		 */
		if ($this->hasExportHandler()) {
			// write data onto the custom file handler
			$fp = $this->getExportCSVHandler();
			fwrite($fp, implode("\r\n", $lines));
			fclose($fp);

			return true;
		}

		// force text file download
		header("Content-type: text/plain");
		header("Cache-Control: no-store, no-cache");
		header('Content-Disposition: attachment; filename="' . $this->getExportCSVFileName() . '"');
		echo implode("\r\n", $lines);
		exit;
	}

	/**
	 * Registers the name to give to the file being exported.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	private function registerExportFileName()
	{
		$photelcode = VikRequest::getString('hotelcode', '', 'request');
		$progressivecode = VikRequest::getString('progressivecode', '', 'request');

		$this->setExportCSVFileName(strtoupper($photelcode) . '.' . $progressivecode . '.txt');
	}

	/**
	 * This method adds blank spaces to the string
	 * until the passed length of string is reached.
	 *
	 * @param 	string 		$val
	 * @param 	int 		$len
	 *
	 * @return 	string
	 */
	private function valueFiller($val, $len)
	{
		$len = empty($len) || (int)$len <= 0 ? strlen($val) : (int)$len;

		//clean up $val in case there is still a CR or LF
		$val = str_replace(array("\r\n", "\r", "\n"), '', $val);
		//
		
		if (strlen($val) < $len) {
			while (strlen($val) < $len) {
				$val .= ' ';
			}
		} elseif (strlen($val) > $len) {
			$val = substr($val, 0, $len);
		}

		return $val;
	}


	/**
	 * Loads the country names from DB
	 *
	 * @return 	array
	 */
	private function loadNazioni()
	{
		$nazioni = array();
		$records = array();

		$db = JFactory::getDbo();
		$q = "SELECT `country_3_code`, `country_name`,`country_2_code`   FROM `#__vikbooking_countries`;";
		$db->setQuery($q);
		$db->execute();
		if ($db->getNumRows()>0) {
			$records = $db->loadAssocList();
		}

		foreach ($records as $key => $value) {
			$nazioni[$value['country_2_code']]['name'] = $value['country_name'];
			$nazioni[$value['country_2_code']]['three_code'] = $value['country_3_code'];
		}
		return $nazioni;
	}

	/**
	 * Parses the file Documenti.csv and returns an associative
	 * array with the code and name of the Documento.
	 * Every line of the CSV is composed of: Codice, Documento.
	 *
	 * @return 	array
	 */
	private function loadDocumenti()
	{
		$documenti = array(
			"D" => "Documento Nacional de Identidad",
			"P" => "Pasaporte",
			"C" => "Permiso de Conducir",
			"I" => "Carta o Documento de Identidad",
			"N" => "Permiso de Residencia Español",
			"X" => "Permiso de Residencia de otro Estado Miembro de la Unión Europea",
		);
		return $documenti;
	}

	/**
	 *
	 * Returns the key of the state selected by the user.
	 *
 	 * @return string
 	 *
	 */
	private function checkCountry($country) {
		$found = false;
		$staval = '';
		if (!count($this->nazioni)) {
			$this->nazioni = $this->loadNazioni();
		}
		foreach ($this->nazioni as $key => $value) {
			if (trim($value['three_code']) == trim($country)) {
				$staval = $key;
				$found = true;
				break;
			}
		}
		if ($found !== true) {
			$staval = '';
		}
		return $staval;
	}
}

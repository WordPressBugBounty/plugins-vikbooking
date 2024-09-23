<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2019 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * ISTAT Ricestat child Class of VikBookingReport.
 */
class VikBookingReportIstatRicestat extends VikBookingReport
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

	// username and password for the structure
	private $providerusername;
	private $providerpassword;

	/**
	 * Other private vars of this sub-class.
	 */
	private $comuniProvince;
	private $nazioni;
	private $documenti;
	public $tipoTurismo; 
	public $mezzoDiTrasporto;

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = JText::translate('ISTAT Ricestat');
		$this->reportFilters = array();

		$this->providerusername = 'Prova';
		$this->providerpassword = 'Prova';

		$this->cols = array();
		$this->rows = array();
		$this->footerRow = array();

		$this->debug = (VikRequest::getInt('e4j_debug', 0, 'request') > 0);

		$this->comuniProvince = array();
		$this->nazioni = array();
		$this->mezzoDiTrasporto = array(
			'0' => 'Auto',
			'1' => 'Aereo', 
			'2' => 'Aereo+Pullman',
			'3' => 'Aereo+Navetta/Taxi/Auto',
			'4' => 'Aereo+Treno',
			'5' => 'Treno',
			'6' => 'Pullman',
			'7' => 'Caravan/Autocaravan',
			'8' => 'Barca/Nave/Traghetto',
			'9' => 'Moto',
			'10' => 'Bicicletta',
			'11' => 'A piedi',
			'12' => 'Altro motivo',
			'13' => 'Non specificato'
		);
		$this->tipoTurismo = array(
			'0' => 'Culturale',
			'1' => 'Balneare', 
			'2' => 'Congressuale/Affari',
			'3' => 'Fieristico',
			'4' => 'Sportivo/Fitness',
			'5' => 'Scolastico',
			'6' => 'Religioso',
			'7' => 'Sociale',
			'8' => 'Parchi Tematici',
			'9' => 'Termale/Trattamenti salute',
			'10' => 'Enogastronomico',
			'11' => 'Cicloturismo',
			'12' => 'Escursionistico/Naturalistico',
			'13' => 'Altro motivo',
			'14' => 'Non specificato'
		);

		$this->registerExportFileName();

		parent::__construct();
	}

	/**
	 * Returns the name of this report.
	 *
	 * @return     string
	 */
	public function getName()
	{
		return $this->reportName;
	}

	/**
	 * Returns the name of this file without .php.
	 *
	 * @return     string
	 */
	public function getFileName()
	{
		return $this->reportFile;
	}

	/**
	 * Returns the filters of this report.
	 *
	 * @return     array
	 */
	public function getFilters()
	{
		if ($this->reportFilters) {
			//do not run this method twice, as it could load JS and CSS files.
			return $this->reportFilters;
		}

		$doc = JFactory::getDocument();
		$css = '.vbo-report-load-trasporto span, .vbo-report-load-turismo span {
			display: inline-block;
			border: 1px solid #dd0000;
			cursor: pointer;
			color: #dd0000;
			padding: 0px 7px;
		}';
		$doc->addStyleDeclaration($css);

		//load the jQuery UI Datepicker
		$this->loadDatePicker();

		//custom export button
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadSchedaIstat();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download File</span></a>';

		//build the hidden values for the selection of Comuni & Province.
		$this->comuniProvince = $this->loadComuniProvince();
		$this->nazioni = $this->loadNazioni();
		$hidden_vals = '<div id="vbo-report-alloggiati-hidden" style="display: none;">';
		//Comuni
		$hidden_vals .= '   <div id="vbo-report-alloggiati-comune" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '        <select id="choose-comune" onchange="vboReportChosenComune(this);"><option value=""></option>';
		if (isset($this->comuniProvince['comuni']) && count($this->comuniProvince['comuni'])) {
			foreach ($this->comuniProvince['comuni'] as $code => $comune) {
				$hidden_vals .= '	<option value="' . $code . '">' . (is_array($comune) ? $comune['name'] : '') . '</option>'."\n";
			}
		}
		$hidden_vals .= '        </select>';
		$hidden_vals .= '   </div>';
		//
		//Mezzi di Trasporto 
		$hidden_vals .= '   <div id="vbo-report-trasporto" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '        <select id="choose-trasporto" onchange="vboReportChosenTrasporto(this);"><option value=""></option>';
		foreach ($this->mezzoDiTrasporto as $key => $value) {
			$hidden_vals .= '   <option value="'.$value.'">'.$value.'</option>'."\n";
		}
		$hidden_vals .= '        </select>';
		$hidden_vals .= '   </div>';
		//
		//Tipo di turismo
		$hidden_vals .= '   <div id="vbo-report-turismo" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '        <select id="choose-turismo" onchange="vboReportChosenTurismo(this);"><option value=""></option>';
		foreach ($this->tipoTurismo as $key => $value) {
			$hidden_vals .= '   <option value="'.$value.'">'.$value.'</option>'."\n";
		}
		$hidden_vals .= '        </select>';
		$hidden_vals .= '   </div>';
		//
		//Province
		$hidden_vals .= '   <div id="vbo-report-alloggiati-provincia" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '        <select id="choose-provincia" onchange="vboReportChosenProvincia(this);"><option value=""></option>';
		if (isset($this->comuniProvince['province']) && count($this->comuniProvince['province'])) {
			foreach ($this->comuniProvince['province'] as $code => $provincia) {
				$hidden_vals .= '   <option value="'.$code.'">'.$provincia.'</option>'."\n";
			}
		}
		$hidden_vals .= '        </select>';
		$hidden_vals .= '   </div>';
		//
		//Nazioni
		$hidden_vals .= '   <div id="vbo-report-alloggiati-nazione" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '        <select id="choose-nazione" onchange="vboReportChosenNazione(this);"><option value=""></option>';
		if (count($this->nazioni)) {
			foreach ($this->nazioni as $code => $nazione) {
				$hidden_vals .= '		<option value="'.$code.'">'.$nazione['name'].'</option>';
			}
		}
		$hidden_vals .= '        </select>';
		$hidden_vals .= '   </div>';
		//
		//Sesso
		$hidden_vals .= '   <div id="vbo-report-alloggiati-sesso" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '        <select id="choose-sesso" onchange="vboReportChosenSesso(this);"><option value=""></option>';
		$sessos = array(
			1 => 'M',
			2 => 'F'
		);
		foreach ($sessos as $code => $ses) {
			$hidden_vals .= '   <option value="'.$code.'">'.$ses.'</option>'."\n";
		}
		$hidden_vals .= '        </select>';
		$hidden_vals .= '   </div>';
		//
		//Numero Documento
		$hidden_vals .= '   <div id="vbo-report-alloggiati-docnum" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '        <input type="text" size="40" id="choose-docnum" placeholder="Numero Documento..." value="" /><br/>';
		$hidden_vals .= '        <button type="button" class="btn" onclick="vboReportChosenDocnum(document.getElementById(\'choose-docnum\').value);">'.JText::translate('VBAPPLY').'</button>';
		$hidden_vals .= '   </div>';
		//
		//Data di Nascita
		$hidden_vals .= '   <div id="vbo-report-alloggiati-dbirth" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '        <input type="text" size="40" id="choose-dbirth" placeholder="Data di Nascita" value="" /><br/>';
		$hidden_vals .= '        <button type="button" class="btn" onclick="vboReportChosenDbirth(document.getElementById(\'choose-dbirth\').value);">'.JText::translate('VBAPPLY').'</button>';
		$hidden_vals .= '   </div>';
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

		// Azione da eseguire: 1 per inserire/modificare, 2 per cancellare.
		$actiontodo = VikRequest::getString('actiontodo', '', 'request');
		$filter_opt = array(
			'label' => '<label for="actiontodo">Azione da Eseguire</label>',
			'html' => '<select id="choose-actiontodo" name="actiontodo"> <option value="" '.((empty($actiontodo) || ($actiontodo != '1' && $actiontodo != '2')) ? 'selected="selected"' : '' ).'></option><option value="1" ' .((!empty($actiontodo) && $actiontodo == '1') ? 'selected="selected"' : '') .'> Inserire/Modificare una prenotazione. </option> <option value="NO"' .((!empty($actiontodo) && $actiontodo == '2') ? 'selected="selected"' : '') .'> Eliminare una prenotazione. </option> </select>',
			'type' => 'text',
			'name' => 'actiontodo'
		);
		array_push($this->reportFilters, $filter_opt);

		$loginvals = $this->initConfiguration();
		$updatevals = false; //if false, do not trigger update for login vals
		// Username
		$username = VikRequest::getString('riceusername', '', 'request');

		if (empty($username) && !empty($loginvals) && !empty($loginvals['username'])) {
			$username = $loginvals['username'];
		} else if (!empty($username) && (empty($loginvals) || empty($loginvals['username'])) ){
			$loginvals['username'] = $username;
			$updatevals = true;
		}

		$filter_opt = array(
			'label' => '<label for="riceusername">Codice Struttura</label>',
			'html' => '<input type="text" id="riceusername" name="riceusername" value="'.$username.'" size="10" />',
			'type' => 'text',
			'name' => 'riceusername'
		);
		array_push($this->reportFilters, $filter_opt);

		// Password
		$password = VikRequest::getString('ricepassword', '', 'request');

		if (empty($password)  && !empty($loginvals) && !empty($loginvals['password'])) {
			$password = $loginvals['password'];
		} else if (!empty($password) && (empty($loginvals) || empty($loginvals['password'])) ){
			$loginvals['password'] = $password;
			$updatevals = true;
		}
		$filter_opt = array(
			'label' => '<label for="ricepassword">Password Struttura</label>',
			'html' => '<input type="password" id="ricepassword" name="ricepassword" value="'.$password.'" size="10" />',
			'type' => 'text',
			'name' => 'ricepassword'
		);
		array_push($this->reportFilters, $filter_opt);

		if ($updatevals) {
			$this->updateLogin($loginvals);
		}
		//jQuery code for the datepicker calendars, select2 and triggers for the dropdown menus
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$js = 'var reportActiveCell = null, reportObj = {};
		jQuery(function() {
			//prepare main filters
			jQuery(".vbo-report-datepicker:input").datepicker({
				maxDate: 0,
				dateFormat: "'.$this->getDateFormat('jui').'",
				onSelect: vboReportCheckDates
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			'.(!empty($ptodate) ? 'jQuery(".vbo-report-datepicker-to").datepicker("setDate", "'.$ptodate.'");' : '').'
			//prepare filler helpers
			jQuery("#vbo-report-alloggiati-hidden").children().detach().appendTo(".vbo-info-overlay-report");
			jQuery("#choose-comune").select2({placeholder: "- Seleziona un Comune -", width: "200px"});
			jQuery("#choose-provincia").select2({placeholder: "- Seleziona una Provincia -", width: "200px"});
			jQuery("#choose-nazione").select2({placeholder: "- Seleziona una Nazione -", width: "200px"});
			jQuery("#choose-sesso").select2({placeholder: "- Seleziona Sesso -", width: "200px"});
			jQuery("#choose-turismo").select2({placeholder: "- Seleziona Tipo di Turismo -", width: "300px"});
			jQuery("#choose-trasporto").select2({placeholder: "- Seleziona Mezzo di Trasporto -", width: "300px"});

			jQuery("#choose-dbirth").datepicker({
				maxDate: 0,
				dateFormat: "dd/mm/yy",
				changeMonth: true,
				changeYear: true,
				yearRange: "'.(date('Y') - 100).':'.date('Y').'"
			});
			//click events
			jQuery(".vbo-report-load-comune").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-comune").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-provincia").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-provincia").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-nazione, .vbo-report-load-cittadinanza").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-nazione").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-sesso").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-sesso").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-turismo").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-turismo").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-trasporto").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-trasporto").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-dbirth").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-dbirth").show();
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
		function vboReportChosenComune(comune) {
			var c_code = comune.value;
			var c_val = comune.options[comune.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-docplace")) {
						reportObj[nowindex].docplace = c_code;
					} else {
						reportObj[nowindex].comres = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-comune").val("").select2("data", null, false);
		}
		function vboReportChosenProvincia(prov) {
			var c_code = prov.value;
			var c_val = prov.options[prov.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					reportObj[nowindex].prores = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-provincia").val("").select2("data", null, false);
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
						reportObj[nowindex].stares = c_code;
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
		function vboReportChosenTrasporto(trasporto) {
			var c_code = trasporto.value;
			var c_val = trasporto.options[trasporto.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					reportObj[nowindex].mezzo = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-trasporto").val("").select2("data", null, false);
		}
		function vboReportChosenTurismo(turismo) {
			var c_code = turismo.value;
			var c_val = turismo.options[turismo.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					reportObj[nowindex].turismo = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-turismo").val("").select2("data", null, false);
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
		//download function
		function vboDownloadSchedaIstat() {
			if (!confirm("Sei sicuro di aver compilato tutti i dati?")) {
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
		}';
		$this->setScript($js);

		return $this->reportFilters;
	}

	/**
	 * Loads the report data from the DB.
	 * Returns true in case of success, false otherwise.
	 * Sets the columns and rows for the report to be displayed.
	 *
	 * @return     boolean
	 */
	public function getReportData()
	{
		if (strlen($this->getError())) {
			//Export functions may set errors rather than exiting the process, and the View may continue the execution to attempt to render the report.
			return false;
		}
		//Input fields and other vars
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';
		$username = VikRequest::getString('riceusername', '', 'request');
		$password = VikRequest::getString('ricepassword', '', 'request');
		$actiontodo = VikRequest::getString('actiontodo', '', 'request');
		$records = array();
		$q = "SELECT SUM(`units`) AS `sommaunita`, SUM(`totpeople`) AS `numeropersone`, COUNT(*) AS `numerocamere`  FROM `#__vikbooking_rooms` WHERE `avail`= '1';";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows() > 0) {
			$records = $this->dbo->loadAssocList();
		}
		$totalBeds =(int) ($records[0]['sommaunita'] * ($records[0]['numeropersone']/$records[0]['numerocamere'])); 
		$pletti = VikRequest::getString('numletti', $totalBeds, 'request');
		$currency_symb = VikBooking::getCurrencySymb();
		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();
		if (empty($ptodate)) {
			$ptodate = $pfromdate;
		}
		//Get dates timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts) || empty($to_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}
		if (empty($username)) {
			$this->setError('Inserisci il codice della tua Struttura.<br/>Si tratta di un codice univoco di identificazione che ti viene assegnato dall\'Amministrazione competente.');
			return false;
		}
		if (empty($actiontodo)) {
			$this->setError('Devi specificare l\'azione da eseguire attraverso il menù a tendina qui sopra.');
			return false;
		}
		if (empty($password)) {
			$this->setError('Devi specificare la password della tua struttura attraverso il menù a tendina qui sopra.');
			return false;
		}

		//Query to obtain the records (all check-ins within the dates filter)
		$records = array();
		if($actiontodo == 1){
			$q = "SELECT `o`.`id`,`o`.`custdata`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,".
			"`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`t_first_name`,`or`.`t_last_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,".
			"`co`.`idcustomer`,`co`.`pax_data`,`c`.`first_name`,`c`.`last_name`,`c`.`country` AS `customer_country`,`c`.`doctype`,`c`.`docnum`,`c`.`gender`,`c`.`bdate`,`c`.`pbirth` ".
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` ".
			"LEFT JOIN `#__vikbooking_customers_orders` AS `co` ON `co`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_customers` AS `c` ON `c`.`id`=`co`.`idcustomer` ".
			"WHERE `o`.`status`='confirmed' AND `o`.`closure`=0 AND ((`o`.`checkin`>=".$from_ts." AND `o`.`checkin`<=".$to_ts.") OR (`o`.`checkout`>=".$from_ts." AND `o`.`checkout`<=".$to_ts.") "."OR (`o`.`ts`>=".$from_ts." AND `o`.`ts`<=".$to_ts.")) ".
			"ORDER BY `o`.`checkin` ASC, `o`.`id` ASC;";
		} else {
			$q = "SELECT `o`.`id`,`o`.`custdata`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,".
			"`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`t_first_name`,`or`.`t_last_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,".
			"`co`.`idcustomer`,`co`.`pax_data`,`c`.`first_name`,`c`.`last_name`,`c`.`country` AS `customer_country`,`c`.`doctype`,`c`.`docnum`,`c`.`gender`,`c`.`bdate`,`c`.`pbirth` ".
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` ".
			"LEFT JOIN `#__vikbooking_customers_orders` AS `co` ON `co`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_customers` AS `c` ON `c`.`id`=`co`.`idcustomer` ".
			"WHERE `o`.`status`='cancelled' AND `o`.`closure`=0 AND ((`o`.`checkin`>=".$from_ts." AND `o`.`checkin`<=".$to_ts.") OR (`o`.`checkout`>=".$from_ts." AND `o`.`checkout`<=".$to_ts.") "."OR (`o`.`ts`>=".$from_ts." AND `o`.`ts`<=".$to_ts.")) ".
			"ORDER BY `o`.`checkin` ASC, `o`.`id` ASC;";
		}
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows() > 0) {
			$records = $this->dbo->loadAssocList();
		}
		if (!count($records)) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			$this->setError('Nessun check-in nelle date selezionate.');
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
			//id booking
			array(
				'key' => 'idbooking',
				'attr' => array(
					'class="center"'
				),
				'label' => 'ID',
				'sortable' => 1,
			),
			//check-in
			array(
				'key' => 'checkin',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBPICKUPAT')
			),
			//checkout
			array(
				'key' => 'checkout',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Partenza'
			),
			//cognome
			array(
				'key' => 'cognome',
				'label' => JText::translate('VBTRAVELERLNAME'),
				'sortable' => 1,
			),
			//nome
			array(
				'key' => 'nome',
				'label' => JText::translate('VBTRAVELERNAME'),
				'sortable' => 1,
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
				'sortable' => 1,
				'label' => 'Cittadinanza'
			),
			//cittadinanza
			array(
				'key' => 'stares',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Stato di Residenza'
			),
			//comune di residenza
			array(
				'key' => 'comres',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Comune Residenza',
				'tip' => 'Inserire il comune di residenza solo se il cittadino è di nazionalità italiana.'
			),
			//tipo
			array(
				'key' => 'tipo',
				'attr' => array(
					'class="vbo-report-longlbl"'
				),
				'label' => 'Tipo Alloggiato'
			),
			//quantità (numero di ospiti)
			array(
				'key' => 'guestsnum',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Numero Ospiti',
				'tip' => 'Il numero di ospiti totali (adulti + bambini) per ogni prenotazione è un dato che verrà comunicato all\'ISTAT.',
			),
			array(
				'key' => 'roomsbooked',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Occupazione',
				'tip' => 'Questo valore indica il numero di camere occupate da ogni prenotazione, ed è un dato che verrà comunicato all\'ISTAT.',
			),
			
			// tipo di trasporto 
			array(
				'key' => 'mezzo',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Mezzo di Trasporto'
			),
			// tipo di turismo 
			array(
				'key' => 'turismo',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Tipo di Turismo'
			),
		);

		// loop over the bookings to build the rows of the report
		$from_info = getdate($from_ts);
		foreach ($bookings as $gbook) {
			$guestsnum = 0;
			$guests_rows = array($gbook[0]);
			$room_guests = array();
			$tot_guests_rows = 1;

			$tipo = 16;
			// Codici Tipo Alloggiato
			// 16 = Ospite Singolo
			// 17 = Capofamiglia
			// 18 = Capogruppo
			// 19 = Familiare
			// 20 = Membro Gruppo

			foreach ($gbook as $book) {
				$guestsnum += $book['adults'] + $book['children'];
				$room_guests[] = ($book['adults'] + $book['children']);
			}
			$pax_data = null;
			if (!empty($gbook[0]['pax_data'])) {
				$pax_data = json_decode($gbook[0]['pax_data'], true);
				if (is_array($pax_data) && count($pax_data)) {
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
				// prepare row record
				$insert_row = array();

				// find the actual guest-room-index
				$guest_room_ind = $this->calcGuestRoomIndex($room_guests, $guest_ind);

				// booking ID
				array_push($insert_row, array(
					'key' => 'idbooking',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) {
						return '<a href="index.php?option=com_vikbooking&task=editorder&cid[]='.$val.'" target="_blank"><i class="'.VikBookingIcons::i('external-link').'"></i> '.$val.'</a>';
					},
					'callback_export' => function ($val) {
						return $val;
					},
					'value' => $guests['id']
				));

				// checkin date
				array_push($insert_row, array(
					'key' => 'checkin',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) {
						return date('Y-m-d', $val);
					},
					'callback_export' => function ($val) {
						return date('Y-m-d', $val);
					},
					'value' => $guests['checkin']
				));

				// checkout date
				array_push($insert_row, array(
					'key' => 'checkout',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) {
						return date('Y-m-d', $val);
					},
					'callback_export' => function ($val) {
						return date('Y-m-d', $val);
					},
					'value' => $gbook[0]['checkout']
				));

				// cognome
				$cognome = !empty($guests['t_last_name']) ? $guests['t_last_name'] : $guests['last_name'];
				$pax_cognome = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'last_name');
				$cognome = !empty($pax_cognome) ? $pax_cognome : $cognome;
				array_push($insert_row, array(
					'key' => 'cognome',
					'value' => $cognome
				));

				// nome
				$nome = !empty($guests['t_first_name']) ? $guests['t_first_name'] : $guests['first_name'];
				$pax_nome = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'first_name');
				$nome = !empty($pax_nome) ? $pax_nome : $nome;
				array_push($insert_row, array(
					'key' => 'nome',
					'value' => $nome
				));

				// sesso
				$gender = !empty($guests['gender']) && $guest_ind < 2 ? strtoupper($guests['gender']) : '';
				$gender = $gender == 'F' ? 2 : ($gender == 'M' ? 1 : $gender);
				$pax_gender = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'gender');
				$gender = !empty($pax_gender) ? $pax_gender : $gender;
				if (is_numeric($gender)) {
					$gender = (int)$gender;
				} elseif (!strcasecmp($gender, 'F')) {
					$gender = 2;
				} elseif (!strcasecmp($gender, 'M')) {
					$gender = 1;
				}
				array_push($insert_row, array(
					'key' => 'gender',
					'attr' => array(
						'class="center'.(empty($gender) ? ' vbo-report-load-sesso' : '').'"'
					),
					'callback' => function ($val) {
						return $val == 2 ? 'F' : ($val == 1 ? 'M' : '?');
					},
					'callback_export' => function ($val) {
						return $val == 2 ? 'F' : ($val == 1 ? 'M' : '?');
					},
					'value' => $gender
				));

				// data di nascita
				$dbirth = !empty($guests['bdate']) && $guest_ind < 2 ? VikBooking::getDateTimestamp($guests['bdate'], 0, 0) : '';
				$pax_dbirth = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'date_birth');
				$dbirth = !empty($pax_dbirth) ? $pax_dbirth : $dbirth;
				$dbirth = (strpos($dbirth, '/') === false && strpos($dbirth, VikBooking::getDateSeparator()) === false) ? $dbirth : VikBooking::getDateTimestamp($dbirth, 0, 0);
				array_push($insert_row, array(
					'key' => 'dbirth',
					'attr' => array(
						'class="center'.(empty($dbirth) ? ' vbo-report-load-dbirth' : '').'"'
					),
					'callback' => function ($val) {
						if (empty($val)) {
							return '?';
						}
						if (strpos($val, '/') === false && strpos($val, VikBooking::getDateSeparator()) === false) {
							return date('Y-m-d', $val);
						}
						if (strpos($val, '/') !== false) {
							return date('Y-m-d', VikBooking::getDateTimestamp($val, 0, 0));
						}
						return $val;
					},
					'value' => $dbirth
				));

				// cittadinanza (compatible with pax data field of driver "Italy")
				$pax_country_c = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'country_c');
				$citizen = !empty($guests['country']) && $guest_ind < 2 ? $guests['country'] : '';
				$citizenval = '';
				if (!empty($citizen) && $guest_ind < 2) {
					$citizenval = $this->checkCountry($citizen);
				}
				// check nationality field from pre-checkin
				$pax_citizen = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'nationality');
				$citizen = !empty($pax_citizen) ? $pax_citizen : $citizen;
				$citizen = !empty($pax_country_c) ? $pax_country_c : $citizen;
				$citizenval = !empty($pax_country_c) ? $pax_country_c : $this->checkCountry($citizen);
				array_push($insert_row, array(
					'key' => 'citizen',
					'attr' => array(
						'class="center'.(empty($citizen) ? ' vbo-report-load-cittadinanza' : '').'"'
					),
					'callback' => function ($val) {
						return !empty($val) ? $this->nazioni[$val]['name'] : '?';
					},
					'no_export_callback' => 1,
					'value' => !empty($citizenval) ? $citizenval : ''
				));

				// stato di residenza
				$provstay = '';
				$pax_provstay = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'country_s');
				$provstay = !empty($pax_provstay) ? $pax_provstay : $provstay;
				array_push($insert_row, array(
					'key' => 'staprov',
					'attr' => array(
						'class="center'.(empty($provstay) ? ' vbo-report-load-nazione' : '').'"'
					),
					'callback' => function($val) {
						if (!empty($val) && isset($this->nazioni[$val])) {
							return $this->nazioni[$val]['name'];
						}
						// information is missing and should be provided
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $provstay
				));

				// comune di residenza
				$comstay = '';
				$pax_comstay = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'comune_s');
				$comstay = !empty($pax_comstay) ? $pax_comstay : $comstay;
				array_push($insert_row, array(
					'key' => 'comprov',
					'attr' => array(
						'class="center'.(empty($comstay) ? ' vbo-report-load-comune' : '').'"'
					),
					'callback' => function($val) {
						if (!empty($val) && isset($this->comuniProvince['comuni'][$val])) {
							return $this->comuniProvince['comuni'][$val]['name'];
						}
						// information is missing and should be provided
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $comstay
				));

				// tipo alloggiato
				$use_tipo = $ind > 0 && $tipo == 17 ? 19 : $tipo;
				$pax_guest_type = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'guest_type');
				$use_tipo = !empty($pax_guest_type) ? $pax_guest_type : $use_tipo;
				array_push($insert_row, array(
					'key' => 'tipo',
					'callback' => function ($val) {
						switch ($val) {
							case 16:
								return 'Ospite Singolo';
							case 17:
								return 'Capofamiglia';
							case 18:
								return 'Capogruppo';
							case 19:
								return 'Familiare';
							case 20:
								return 'Membro Gruppo';
							default:
								return '?';
						}
					},
					'no_export_callback' => 1,
					'value' => $use_tipo
				));

				// numero persone in prenotazione
				array_push($insert_row, array(
					'key' => 'guestsnum',
					'attr' => array(
						'class="center"'
					),
					'value' => $guestsnum
				));

				// camere prenotate 
				array_push($insert_row, array(
					'key' => 'roomsbooked',
					'attr' => array(
						'class="center"'
					),
					'value' => count($gbook)
				));

				// mezzo di trasporto
				array_push($insert_row, array(
					'key' => 'mezzo',
					'attr' => array(
						'class="center vbo-report-load-trasporto"'
					),
					'value' => '?'
				));

				// tipo di turismo
				array_push($insert_row, array(
					'key' => 'turismo',
					'attr' => array(
						'class="center vbo-report-load-turismo"'
					),
					'value' => '?'
				));

				// push fields in the rows array as a new row
				array_push($this->rows, $insert_row);

				// increment guest index
				$guest_ind++;
			}
		}

		// do not sort the rows for this report because the lines of the guests of the same booking must be consecutive
		// $this->sortRows($pkrsort, $pkrorder);

		// the footer row will just print the amount of records to export
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

		// Debug
		if ($this->debug) {
			$this->setWarning('path to report file = '.urlencode(dirname(__FILE__)).'<br/>');
			$this->setWarning('$total_rooms_units = '.$total_rooms_units.'<br/>');
			$this->setWarning('$bookings:<pre>'.print_r($bookings, true).'</pre><br/>');
		}

		return true;
	}

	public function formatXML(&$xml)
	{
		if (!class_exists('DOMDocument')) {
			// we cannot format the XML because DOMDocument is missing
			return $xml;
		}

		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml);
		$dom->formatOutput = true;
		$xml = $dom->saveXML();

		return $xml;
	}

	/**
	 * Generates the report columns and rows, then it outputs a CSV file
	 * for download. In case of errors, the process is not terminated (exit)
	 * to let the View display the error message.
	 * We use customExport() rather than exportCSV() only because we need a
	 * different download button rather than the classic "Export as CSV".
	 * 
	 * @param      int  $export_type   the view will pass this argument to the method to call different types of export.
	 *
	 * @return     mixed     void on success with script termination, false otherwise.
	 */
	public function customExport($export_type = 0)
	{
		if (!$this->getReportData()) {
			return false;
		}

		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$pcodstru = VikRequest::getString('codstru', '', 'request');
		$actiontodo = VikRequest::getString('actiontodo', '', 'request');
		$username = VikRequest::getString('riceusername', '', 'request');
		$password = VikRequest::getString('ricepassword', '', 'request');
		$records = array();

		// manual values in filler
		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? json_decode($pfiller, true) : array();
		$pfiller = !is_array($pfiller) ? array() : $pfiller;

		//Debug
		if ($this->debug) {
			$this->setError('<pre>'.print_r($pfiller, true).'</pre><br/>');
			return false;
		}
		// pool of booking IDs to update their history
		$booking_ids = array();
		// update the history for all bookings affected
		foreach ($booking_ids as $bid) {
			VikBooking::getBookingHistoryInstance()->setBid($bid)->store('RP', $this->reportName);
		}
		$insertedReservations = $this->initReservations(); 

		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<MTO_SchedineRQ xmlns:xs="http://www.w3.org/2001/XMLSchema" xsi:schemaLocation="http://ws.webci.it/webci.xsd" Version="1.0" PrimaryLangID="it">
				<POS>
					<Source>
						<RequestorID Type = "4" ID = "' . $this->providerusername . '" MessagePassword = "' . $this->providerpassword . '"/>
					</Source>
					<Source>
						<RequestorID Type = "10" ID = "' . $username . '" MessagePassword = "' . $password . '"/>
					</Source>
				</POS>';

		if ($actiontodo == '1') {
			$xml .= $this->editInsert($insertedReservations, $pfiller);
		} else {
			$xml .= $this->deleteRes($insertedReservations);
		}
		$xml .= '</MTO_SchedineRQ>';

		// format XML document
		$this->formatXML($xml);

		/**
		 * Custom export method supports a custom export handler, if previously set.
		 * 
		 * @since 	1.16.1 (J) - 1.6.1 (WP)
		 */
		if ($this->hasExportHandler()) {
			// write data onto the custom file handler
			$fp = $this->getExportCSVHandler();
			fwrite($fp, $xml);
			fclose($fp);

			return true;
		}

		header('Content-Disposition: attachment; filename=' . $this->getExportCSVFileName());
		header('Content-type: text/xml');
		echo $xml;

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
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$this->setExportCSVFileName(str_replace(' ', '_', $this->reportName) . '-' . str_replace('/', '_', $pfromdate) . '.xml');
	}

	private function editInsert($toEdit, $pfiller) 
	{
		$xml = '';
		$insertedids = array();
		$insertedids = $toEdit;

		foreach ($this->rows as $ind => $row) {
			$idswh = -1;
			foreach ($row as $field) {
				if (isset($field['ignore_export'])) {
					continue;
				}
				// check if a value for this field was filled in manually
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
				$export_value = !isset($field['no_export_callback']) && isset($field['callback']) && is_callable($field['callback']) ? $field['callback']($field['value']) : $field['value'];

				if ($field['key'] == 'idbooking') { 
					$idswh = $export_value;
					//retrieve customer ID
					$q = "SELECT `idcustomer` FROM `#__vikbooking_customers_orders` WHERE `idorder` = " . $idswh . ";";
					$this->dbo->setQuery($q);
					$this->dbo->execute();
					if ($this->dbo->getNumRows() > 0) {
						$clienti[$idswh]['idcustomer'] = $this->dbo->loadResult();
					}
				}
				if ($field['key'] == 'checkin') {
					$clienti[$idswh]['checkin'] = $export_value;
				}
				if ($field['key'] == 'guestsnum') {
					$clienti[$idswh]['guestsnum'] = $export_value;
				}
				if ($field['key'] == 'checkout') {
					$clienti[$idswh]['checkout'] = $export_value;
				}
				$clienti[$idswh]['idswh'] = $idswh;
				if ($field['key'] == 'tipo') {
					$clienti[$idswh]['tipoalloggiato'] = (string)$export_value;
				}
				if ($field['key'] == 'gender') {
					$clienti[$idswh]['sesso'] = $export_value;
				}
				if ($field['key'] == 'turismo') {
					$clienti[$idswh]['tipoturismo'] = $export_value;
				}
				if ($field['key'] == 'mezzo') {
					$clienti[$idswh]['mezzotrasporto'] = $export_value;
				}
				if ($field['key'] == 'citizen') {
					$clienti[$idswh]['cittadinanza'] = (string)$export_value; 
				}
				if ($field['key'] == 'stares') {
					$clienti[$idswh]['statoresidenza'] = (string)$export_value; 
				}
				if ($field['key'] == 'dbirth') {
					$date = str_replace('/', '-', $export_value);
					//$datanascita = date('Ymd', strtotime($date));
					$clienti[$idswh]['datanascita'] = (string)$date; 
				}
				if (isset($clienti[$idswh]['cittadinanza']) && $clienti[$idswh]['cittadinanza'] == '100000100' && $field['key'] == 'comres') {
					$clienti[$idswh]['luogoresidenza'] = (string)$export_value;
				}
				if (isset($clienti[$idswh]['tipoalloggiato']) && ($clienti[$idswh]['tipoalloggiato'] == '19' || $clienti[$idswh]['tipoalloggiato'] == '20') && $field['key'] == 'tipo') {
					$clienti[$idswh]['idcapo'] = (string)$idswh; 
				}
			}  
		}
		if (in_array($clienti['idswh'], $toEdit)) {
			$xml .= '<Aggiornamento>';
		} else {
			$xml .= '<InserimentoAlloggiati>';
		}

		foreach ($clienti as $cliente) {
			if ($cliente['guestsnum'] > 1) {
				$rooms = VikBooking::loadOrdersRoomsData($cliente['idswh']);
				foreach ($rooms as $room) {
					$totalaval = $room['adults'] + $room['children'];
					$xml .= '<Gruppo id="' . $cliente['idswh'] . '" >';
					for ($i = 0; $i < $totalaval; $i++) {
						$provenienza = '';
						if ($cliente['cittadinanza'] == '100000100') {
							$provincia = substr($cliente['luogoresidenza'], 0, 6);
							$provenienza = '<Italia CodiceComune="' . $cliente['luogoresidenza'] . '" Provincia="' . $provincia . '" Stato="' . $cliente['cittadinanza'] . '" />';
						} else {
							$provenienza = '<Estero Stato="' . $cliente['cittadinanza'] . '" />';
						}
						$tipoalloggiato = $i == 0 ? '17' : '19';
						$xml .= '<Ospite id="' . $cliente['idcustomer'] . '_' . $room['idroom'] . '_' . $i . '" IdCamera="' . $room['idroom'] . '" TipoAlloggiato="' . $tipoalloggiato . '" >' .
									'<DataDiArrivo>' . $cliente['checkin'] . '</DataDiArrivo>' .
									'<DataDiPartenza>' . $cliente['checkout'] . '</DataDiPartenza>' .
									'<DataDiNascita>' . $cliente['datanascita'] . '</DataDiNascita>' .
									'<Sesso>' . $cliente['sesso'] . '</Sesso>' .
									'<Provenienza>' . $provenienza . '</Provenienza>' .
									'<Cittadinanza>' . $cliente['cittadinanza'] . '</Cittadinanza>' .
								'</Ospite>';
					}
					$xml .= '</Gruppo>';
				}
			} else {
				$provenienza = '';
				if ($cliente['cittadinanza'] == '100000100') {
					$provincia = substr($cliente['luogoresidenza'], 0, 6);
					$provenienza = '<Italia CodiceComune="' . $cliente['luogoresidenza'] . '" Provincia="' . $provincia . '" Stato="' . $cliente['cittadinanza'] . '" />';
				} else {
					$provenienza = '<Estero Stato="' . $cliente['cittadinanza'] . '" />';
				}
				$rooms = VikBooking::loadOrdersRoomsData($cliente['idswh']);
				$xml .= '<OspiteSingolo id="' . $cliente['idswh'] . '" >' .
							'<Ospite id="' . $cliente['idcustomer'] . '" IdCamera="' . $rooms['0']['idroom'] . '" TipoAlloggiato="' . $cliente['tipoalloggiato'] . '" >' .
								'<DataDiArrivo>' . $cliente['checkin'] . '</DataDiArrivo>' .
								'<DataDiPartenza>' . $cliente['checkout'] . '</DataDiPartenza>' .
								'<DataDiNascita>' . $cliente['datanascita'] . '</DataDiNascita>' .
								'<Sesso>' . $cliente['sesso'] . '</Sesso>' .
								'<Provenienza>' . $provenienza . '</Provenienza>' .
								'<Cittadinanza>' . $cliente['cittadinanza'] . '</Cittadinanza>' .
							'</Ospite>' .
						'</OspiteSingolo>';
			}
		}

		if (in_array($cliente['idswh'], $toEdit)) {
			$xml .= '</Aggiornamento>';
		} else {
			array_push($insertedids, $cliente['idswh']);
			$xml .= '</InserimentoAlloggiati>';
		}

		$this->updateReservations($insertedids);

		return $xml;
	}

	private function deleteRes($toEdit) {
		$xml = '<Eliminazione >';
		foreach ($this->rows as $ind => $row) {
			foreach ($row as $field) {
				if ($field['key'] == 'idbooking') { 
					$idswh = $export_value;
					for ($i = 0; $i < count($toEdit); $i++) {
						if ($toEdit[$i] == $idswh) {
							unset($toEdit[$i]);
							$xml .= '<Alloggiato id="' . $idswh .'" />';
						}
					}
				}
			}
		}
		$xml .= '</Eliminazione >';

		return $xml;
	}

	/**
	 * Parses the file Comuni.csv and returns two associative
	 * arrays: one for the Comuni and one for the Province.
	 * Every line of the CSV is composed of: Codice, Comune, Provincia.
	 *
	 * @return 	array
	 */
	private function loadComuniProvince()
	{
		$vals = array(
			'comuni' => array(
				0 => '-- Estero --'
			),
			'province' => array(
				0 => '-- Estero --'
			)
		);

		$csv = dirname(__FILE__).DIRECTORY_SEPARATOR.'Comuni.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}
			$v = explode(';', $row);
			if (count($v) != 3) {
				continue;
			}
			$vals['comuni'][$v[0]]['name'] = $v[1];
			$vals['comuni'][$v[0]]['province'] = $v[2];
			$vals['province'][$v[2]] = $v[2];
		}

		return $vals;
	}

	/**
	 * Parses the file Nazioni.csv and returns an associative
	 * array with the code and name of the Nazione.
	 * Every line of the CSV is composed of: Codice, Nazione.
	 *
	 * @return 	array
	 */
	protected function loadNazioni()
	{
		$nazioni = array();

		$csv = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Nazioni.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}

			$v = explode(';', $row);
			if (count($v) != 3) {
				continue;
			}

			$nazioni[$v[0]]['name'] = $v[1];
			$nazioni[$v[0]]['three_code'] = $v[2];
		}

		return $nazioni;
	}

	private function initReservations()
	{
		$init_res = VBOFactory::getConfig()->get('ricestat_insertedres');
		if (empty($init_res)) {
			return [];
		}

		return (array)json_decode($init_res, true);
	}

	private function initConfiguration()
	{
		$init_res = VBOFactory::getConfig()->get('ricestat_loginvalsricestat');
		if (empty($init_res)) {
			return [];
		}

		return (array)json_decode($init_res, true);
	}

	private function updateLogin($updatevals)
	{
		$vals = json_encode($updatevals);

		VBOFactory::getConfig()->set('ricestat_loginvalsricestat', $vals);
	}

	private function updateReservations($updatevals)
	{
		$vals = json_encode($updatevals);

		VBOFactory::getConfig()->set('ricestat_insertedres', $vals);
	}

	/**
	 * Helper method to quickly get a pax_data property for the guest.
	 * 
	 * @param 	array 	$pax_data 	the current pax_data stored.
	 * @param 	array 	$guests 	list of total guests per room.
	 * @param 	int 	$guest_ind 	the guest index.
	 * @param 	string 	$key 		the pax_data key to look for.
	 * 
	 * @return 	mixed 				null on failure or value fetched.
	 */
	private function getGuestPaxDataValue($pax_data, $guests, $guest_ind, $key)
	{
		if (!is_array($pax_data) || !count($pax_data) || empty($key)) {
			return null;
		}

		// find room index for this guest number
		$room_num = 0;
		$use_guest_ind = $guest_ind;
		foreach ($guests as $room_index => $room_tot_guests) {
			// find the proper guest index for the room to which this belongs
			if ($use_guest_ind <= $room_tot_guests) {
				// proper room index found for this guest
				$room_num = $room_index;
				break;
			} else {
				// it's probably in a next room
				$use_guest_ind -= $room_tot_guests;
			}
		}

		// check if a value exists for the requested key in the found room and guest indexes
		if (isset($pax_data[$room_num]) && isset($pax_data[$room_num][$use_guest_ind])) {
			if (isset($pax_data[$room_num][$use_guest_ind][$key])) {
				// we've got a value previously stored
				return $pax_data[$room_num][$use_guest_ind][$key];
			}
		}

		// nothing was found
		return null;
	}

	/**
	 * Helper method to determine the exact number for this guest in the room booked.
	 * 
	 * @param 	array 	$guests 	list of total guests per room.
	 * @param 	int 	$guest_ind 	the guest index.
	 * 
	 * @return 	int 				the actual guest room index starting from 1.
	 */
	private function calcGuestRoomIndex($guests, $guest_ind)
	{
		// find room index for this guest number
		$room_num = 0;
		$use_guest_ind = $guest_ind;
		foreach ($guests as $room_index => $room_tot_guests) {
			// find the proper guest index for the room to which this belongs
			if ($use_guest_ind <= $room_tot_guests) {
				// proper room index found for this guest
				$room_num = $room_index;
				break;
			} else {
				// it's probably in a next room
				$use_guest_ind -= $room_tot_guests;
			}
		}

		return $use_guest_ind;
	}
}

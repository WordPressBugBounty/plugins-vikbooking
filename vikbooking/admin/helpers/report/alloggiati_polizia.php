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
 * AlloggiatiPolizia child Class of VikBookingReport.
 * The Class was designed to export the customers details for the Italian Police.
 * 
 * @link https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/TechSupp.aspx
 * @link https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/CREAFILE.pdf (da pagina 4)
 * @link https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/MANUALEALBERGHI.pdf
 * @link https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/TABELLE.zip
 */
class VikBookingReportAlloggiatiPolizia extends VikBookingReport
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 * 
	 * @var  string
	 */
	public $defaultKeySort = 'idbooking';

	/**
	 * Property 'defaultKeyOrder' is used by the View that renders the report.
	 * 
	 * @var  string
	 */
	public $defaultKeyOrder = 'ASC';

	/**
	 * Property 'customExport' is used by the View to display custom export buttons.
	 * 
	 * @var  string
	 */
	public $customExport = '';

	/**
	 * Debug mode is activated by passing the value 'e4j_debug' > 0
	 * 
	 * @var  bool
	 */
	protected $debug;

	/**
	 * List of "comuni" and "province" codes.
	 * 
	 * @var  array
	 */
	protected $comuniProvince = array();

	/**
	 * List of "nazioni" codes.
	 * 
	 * @var  array
	 */
	protected $nazioni = array();

	/**
	 * List of "documenti" codes.
	 * 
	 * @var  array
	 */
	protected $documenti = array();

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = JText::translate('VBOREPORT'.strtoupper(str_replace('_', '', $this->reportFile)));
		$this->reportFilters = array();

		$this->cols = array();
		$this->rows = array();
		$this->footerRow = array();

		$this->comuniProvince = $this->loadComuniProvince();
		$this->nazioni = $this->loadNazioni();
		$this->documenti = $this->loadDocumenti();

		$this->debug = (VikRequest::getInt('e4j_debug', 0, 'request') > 0);

		$this->registerExportFileName();

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
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadSchedaPolizia();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download File</span></a>';

		//build the hidden values for the selection of Comuni & Province.
		$hidden_vals = '<div id="vbo-report-alloggiati-hidden" style="display: none;">';
		//Comuni
		$hidden_vals .= '	<div id="vbo-report-alloggiati-comune" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-comune" onchange="vboReportChosenComune(this);"><option value=""></option>';
		if (isset($this->comuniProvince['comuni']) && count($this->comuniProvince['comuni'])) {
			foreach ($this->comuniProvince['comuni'] as $code => $comune) {
				if (!is_array($comune)) {
					continue;
				}
				$hidden_vals .= '	<option value="'.$code.'">'.$comune['name'].'</option>'."\n";
			}
		}
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';

		//Province
		$hidden_vals .= '	<div id="vbo-report-alloggiati-provincia" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-provincia" onchange="vboReportChosenProvincia(this);"><option value=""></option>';
		if (isset($this->comuniProvince['province']) && count($this->comuniProvince['province'])) {
			foreach ($this->comuniProvince['province'] as $code => $provincia) {
				// sanitize code from line breaks
				$code = str_replace("\r\n", '', $code);
				$code = str_replace("\r", '', $code);
				$code = str_replace("\n", '', $code);
				//
				$hidden_vals .= '	<option value="'.$code.'">'.$provincia.'</option>'."\n";
			}
		}
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';

		//Nazioni
		$hidden_vals .= '	<div id="vbo-report-alloggiati-nazione" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-nazione" onchange="vboReportChosenNazione(this);"><option value=""></option>';
		if (count($this->nazioni)) {
			foreach ($this->nazioni as $code => $nazione) {
				$hidden_vals .= '	<option value="'.$code.'">'.$nazione['name'].'</option>'."\n";
			}
		}
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';

		//Documenti
		$hidden_vals .= '	<div id="vbo-report-alloggiati-doctype" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-documento" onchange="vboReportChosenDocumento(this);"><option value=""></option>';
		if (count($this->documenti)) {
			foreach ($this->documenti as $code => $documento) {
				$hidden_vals .= '	<option value="'.$code.'">'.$documento.'</option>'."\n";
			}
		}
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';

		//Sesso
		$hidden_vals .= '	<div id="vbo-report-alloggiati-sesso" class="vbo-report-alloggiati-selcont" style="display: none;">';
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

		//Numero Documento
		$hidden_vals .= '	<div id="vbo-report-alloggiati-docnum" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '		<input type="text" size="40" id="choose-docnum" placeholder="Numero Documento..." value="" /><br /><br />';
		$hidden_vals .= '		<button type="button" class="btn vbo-config-btn" onclick="vboReportChosenDocnum(document.getElementById(\'choose-docnum\').value);">'.JText::translate('VBAPPLY').'</button>';
		$hidden_vals .= '	</div>';

		//Data di Nascita
		$hidden_vals .= '	<div id="vbo-report-alloggiati-dbirth" class="vbo-report-alloggiati-selcont" style="display: none;">';
		$hidden_vals .= '		<input type="text" size="40" id="choose-dbirth" placeholder="Data di Nascita" value="" /><br /><br />';
		$hidden_vals .= '		<button type="button" class="btn vbo-config-btn" onclick="vboReportChosenDbirth(document.getElementById(\'choose-dbirth\').value);">'.JText::translate('VBAPPLY').'</button>';
		$hidden_vals .= '	</div>';

		$hidden_vals .= '</div>';

		// From Date Filter (with hidden values for the dropdown menus of Comuni, Province, Stati etc..)
		$filter_opt = array(
			'label' => '<label for="fromdate">'.JText::translate('VBOREPORTSDATEFROM').'</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />'.$hidden_vals,
			'type' => 'calendar',
			'name' => 'fromdate'
		);
		array_push($this->reportFilters, $filter_opt);

		// To Date Filter
		$filter_opt = array(
			'label' => '<label for="todate">'.JText::translate('VBOREPORTSDATETO').'</label>',
			'html' => '<input type="text" id="todate" name="todate" value="" class="vbo-report-datepicker vbo-report-datepicker-to" />',
			'type' => 'calendar',
			'name' => 'todate'
		);
		array_push($this->reportFilters, $filter_opt);

		// append button to save the data when creating manual values
		$filter_opt = array(
			'label' => '<label class="vbo-report-alloggiati-manualsave" style="display: none;">Dati inseriti</label>',
			'html' => '<button type="button" class="btn vbo-config-btn vbo-report-alloggiati-manualsave" style="display: none;" onclick="vboAlloggiatiSaveData();"><i class="' . VikBookingIcons::i('save') . '"></i> ' . JText::translate('VBSAVE') . '</button>',
		);
		array_push($this->reportFilters, $filter_opt);

		//jQuery code for the datepicker calendars, select2 and triggers for the dropdown menus
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$js = 'var reportActiveCell = null, reportObj = {};
		var vbo_alloggiati_ajax_uri = "' . VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=invoke_report&report=' . $this->reportFile) . '";
		var vbo_alloggiati_save_icn = "' . VikBookingIcons::i('save') . '";
		var vbo_alloggiati_saving_icn = "' . VikBookingIcons::i('circle-notch', 'fa-spin fa-fw') . '";
		var vbo_alloggiati_saved_icn = "' . VikBookingIcons::i('check-circle') . '";
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
			jQuery("#choose-documento").select2({placeholder: "- Seleziona un Documento -", width: "200px"});
			jQuery("#choose-sesso").select2({placeholder: "- Seleziona Sesso -", width: "200px"});
			jQuery("#choose-dbirth").datepicker({
				maxDate: 0,
				dateFormat: "dd/mm/yy",
				changeMonth: true,
				changeYear: true,
				yearRange: "'.(date('Y') - 100).':'.date('Y').'"
			});
			//click events
			jQuery(".vbo-report-load-comune, .vbo-report-load-comune-stay").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-comune").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-provincia, .vbo-report-load-provincia-stay").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-provincia").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-nazione, .vbo-report-load-nazione-stay, .vbo-report-load-cittadinanza").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-nazione").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-doctype").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-doctype").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-docplace").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-comune").show();
				jQuery("#vbo-report-alloggiati-nazione").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-sesso").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-sesso").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-docnum").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-docnum").show();
				vboShowOverlay();
				setTimeout(function(){jQuery("#choose-docnum").focus();}, 500);
			});
			jQuery(".vbo-report-load-dbirth").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-dbirth").show();
				vboShowOverlay();
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
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-docplace")) {
						reportObj[nowindex]["docplace"] = c_code;
					} else if (jQuery(reportActiveCell).hasClass("vbo-report-load-comune-stay")) {
						reportObj[nowindex]["comune_s"] = c_code;
					} else {
						reportObj[nowindex]["comune_b"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-comune").val("").select2("data", null, false);
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		function vboReportChosenProvincia(prov) {
			var c_code = prov.value;
			var c_val = prov.options[prov.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-provincia-stay")) {
						reportObj[nowindex]["province_s"] = c_code;
					} else {
						reportObj[nowindex]["province_b"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-provincia").val("").select2("data", null, false);
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		function vboReportChosenNazione(naz) {
			var c_code = naz.value;
			var c_val = naz.options[naz.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-nazione")) {
						reportObj[nowindex]["country_b"] = c_code;
					} else if (jQuery(reportActiveCell).hasClass("vbo-report-load-nazione-stay")) {
						reportObj[nowindex]["country_s"] = c_code;
					} else if (jQuery(reportActiveCell).hasClass("vbo-report-load-docplace")) {
						reportObj[nowindex]["docplace"] = c_code;
					} else {
						reportObj[nowindex]["country_c"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-nazione").val("").select2("data", null, false);
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		function vboReportChosenDocumento(doctype) {
			var c_code = doctype.value;
			var c_val = doctype.options[doctype.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					reportObj[nowindex]["doctype"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-documento").val("").select2("data", null, false);
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		function vboReportChosenSesso(sesso) {
			var c_code = sesso.value;
			var c_val = sesso.options[sesso.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					reportObj[nowindex]["gender"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-sesso").val("").select2("data", null, false);
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		function vboReportChosenDocnum(val) {
			var c_code = val, c_val = val;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					reportObj[nowindex]["docnum"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-docnum").val("");
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		function vboReportChosenDbirth(val) {
			var c_code = val, c_val = val;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					reportObj[nowindex]["date_birth"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-dbirth").val("");
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		//download function
		function vboDownloadSchedaPolizia() {
			if (!confirm("Sei sicuro di aver compilato tutti i dati della Scheda Alloggiati?")) {
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
		// save data function
		function vboAlloggiatiSaveData() {
			jQuery("button.vbo-report-alloggiati-manualsave").find("i").attr("class", vbo_alloggiati_saving_icn);
			VBOCore.doAjax(
				vbo_alloggiati_ajax_uri,
				{
					call: "updatePaxData",
					params: reportObj,
					tmpl: "component"
				},
				function(response) {
					if (!response || !response[0]) {
						alert("An error occurred.");
						return false;
					}
					jQuery("button.vbo-report-alloggiati-manualsave").addClass("btn-success").find("i").attr("class", vbo_alloggiati_saved_icn);
				},
				function(error) {
					alert(error.responseText);
					jQuery("button.vbo-report-alloggiati-manualsave").removeClass("btn-success").find("i").attr("class", vbo_alloggiati_save_icn);
				}
			);
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
			"`co`.`idcustomer`,`co`.`pax_data`,`c`.`first_name`,`c`.`last_name`,`c`.`country` AS `customer_country`,`c`.`address`,`c`.`doctype`,`c`.`docnum`,`c`.`gender`,`c`.`bdate`,`c`.`pbirth` ".
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

		// free some memory up
		unset($records);

		// define the columns of the report
		$this->cols = array(
			// tipo
			array(
				'key' => 'tipo',
				'attr' => array(
					'class="vbo-report-longlbl"'
				),
				'label' => 'Tipo Alloggiato'
			),
			// check-in
			array(
				'key' => 'checkin',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBPICKUPAT')
			),
			// nights
			array(
				'key' => 'nights',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBDAYS')
			),
			// cognome
			array(
				'key' => 'last_name',
				'label' => JText::translate('VBTRAVELERLNAME')
			),
			// nome
			array(
				'key' => 'first_name',
				'label' => JText::translate('VBTRAVELERNAME')
			),
			// sesso
			array(
				'key' => 'gender',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERGENDER')
			),
			// data di nascita
			array(
				'key' => 'date_birth',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERBDATE')
			),
			// comune di nascita
			array(
				'key' => 'comune_b',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Comune Nascita'
			),
			// provincia di nascita
			array(
				'key' => 'province_b',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Provincia Nascita'
			),
			// stato di nascita
			array(
				'key' => 'country_b',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Stato Nascita'
			),
			// cittadinanza
			array(
				'key' => 'country_c',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERNATION')
			),
			/**
			 * This is not needed!
			 * 
			 * @see 	https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/Manuali/MANUALEALBERGHI.pdf
			 */
			/*
			// comune di residenza
			array(
				'key' => 'comune_s',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Comune Residenza'
			),
			// provincia di residenza
			array(
				'key' => 'province_s',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Provincia Residenza'
			),
			// stato di residenza
			array(
				'key' => 'country_s',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Stato Residenza'
			),
			// indirizzo
			array(
				'key' => 'address',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('ORDER_ADDRESS'),
				'tip' => 'Campo facoltativo che può essere lasciato vuoto. Viene letto dalle informazioni del cliente associato alla prenotazione.',
			),
			*/
			// tipo documento
			array(
				'key' => 'doctype',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERDOCTYPE')
			),
			// numero documento
			array(
				'key' => 'docnum',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERDOCNUM')
			),
			// luogo rilascio documento
			array(
				'key' => 'docplace',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Luogo Rilascio'
			),
			// id booking
			array(
				'key' => 'idbooking',
				'attr' => array(
					'class="center"'
				),
				'label' => 'ID / #'
			),
		);

		// line number (to facilitate identifying a specific guest in case of errors with the file submission)
		$line_number = 0;

		// loop over the bookings to build the rows of the report
		$from_info = getdate($from_ts);
		foreach ($bookings as $gbook) {
			// count the total number of guests for all rooms of this booking
			$tot_booking_guests = 0;
			$room_guests = array();
			foreach ($gbook as $rbook) {
				$tot_booking_guests += ($rbook['adults'] + $rbook['children']);
				$room_guests[] = ($rbook['adults'] + $rbook['children']);
			}

			// make sure to decode the current pax data
			if (!empty($gbook[0]['pax_data'])) {
				$gbook[0]['pax_data'] = json_decode($gbook[0]['pax_data'], true);
				$gbook[0]['pax_data'] = !is_array($gbook[0]['pax_data']) ? array() : $gbook[0]['pax_data'];
			}

			// push a copy of the booking for each guest
			$guests_rows = array();
			for ($i = 1; $i <= $tot_booking_guests; $i++) {
				array_push($guests_rows, $gbook[0]);
			}

			/**
			 * Codici Tipo Alloggiato
			 * 
			 * 16 = Ospite Singolo
			 * 17 = Capofamiglia
			 * 18 = Capogruppo
			 * 19 = Familiare
			 * 20 = Membro Gruppo
			 */
			$tipo = 16;
			$tipo = count($guests_rows) > 1 ? 17 : $tipo;

			// create one row for each guest
			$guest_ind = 1;
			foreach ($guests_rows as $ind => $guests) {
				// prepare row record for this room-guest
				$insert_row = array();

				// find the actual guest-room-index
				$guest_room_ind = $this->calcGuestRoomIndex($room_guests, $guest_ind);

				// determine the type of guest, either automatically or from the check-in pax data
				$use_tipo = $ind > 0 && $tipo == 17 ? 19 : $tipo;
				$pax_guest_type = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'guest_type');
				$use_tipo = !empty($pax_guest_type) ? $pax_guest_type : $use_tipo;

				/**
				 * Il calcolo dello stato di nascita è fatto qua per evitare che poi succeda casino col comune. (Belgio -> Belgioioso, vedi cliente)
				 * In questo modo, il calcolo dello stato di nascita permette di escludere poi la provincia di nascita in caso il cliente sia straniero.
				 * 
				 * @since 	1.4.1
				 * 
				 * @see 	the updated integration of this report relies on drivers, and so such controls are no longer
				 * 			needed, if the pax data is collected through the back-end before launching the report.
				 */
				$stabirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country');
				$stabirth = $stabirth || '';
				$staval = $this->checkCountry($stabirth);

				// Tipo Alloggiato
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
						}
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $use_tipo
				));

				// Data Arrivo
				array_push($insert_row, array(
					'key' => 'checkin',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) {
						return date('d/m/Y', $val);
					},
					'value' => $guests['checkin']
				));

				// Notti di Permanenza
				array_push($insert_row, array(
					'key' => 'nights',
					'attr' => array(
						'class="center"'
					),
					'value' => $guests['days']
				));

				// Cognome
				$cognome = !empty($guests['t_last_name']) ? $guests['t_last_name'] : $guests['last_name'];
				$pax_cognome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'last_name');
				$cognome = !empty($pax_cognome) ? $pax_cognome : $cognome;
				array_push($insert_row, array(
					'key' => 'last_name',
					'value' => $cognome
				));

				// Nome
				$nome = !empty($guests['t_first_name']) ? $guests['t_first_name'] : $guests['first_name'];
				$pax_nome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'first_name');
				$nome = !empty($pax_nome) ? $pax_nome : $nome;
				array_push($insert_row, array(
					'key' => 'first_name',
					'value' => $nome
				));

				// Sesso
				$gender = !empty($guests['gender']) && $guest_ind < 2 ? strtoupper($guests['gender']) : '';
				$pax_gender = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'gender');
				$gender = !empty($pax_gender) ? $pax_gender : $gender;
				/**
				 * We make sure the gender will be compatible with both back-end and front-end
				 * check-in/registration data collection driver and processes.
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
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
						return $val == 2 ? 'F' : ($val === 1 ? 'M' : '?');
					},
					'no_export_callback' => 1,
					'value' => $gender
				));

				// Data di nascita
				$dbirth = !empty($guests['bdate']) && $guest_ind < 2 ? VikBooking::getDateTimestamp($guests['bdate'], 0, 0) : '';
				$pax_dbirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'date_birth');
				$dbirth = !empty($pax_dbirth) ? $pax_dbirth : $dbirth;
				$dbirth = (strpos($dbirth, '/') === false && strpos($dbirth, VikBooking::getDateSeparator()) === false) ? $dbirth : VikBooking::getDateTimestamp($dbirth, 0, 0);
				array_push($insert_row, array(
					'key' => 'date_birth',
					'attr' => array(
						'class="center'.(empty($dbirth) ? ' vbo-report-load-dbirth' : '').'"'
					),
					'callback' => function ($val) {
						if (!empty($val) && strpos($val, '/') === false && strpos($val, VikBooking::getDateSeparator()) === false) {
							return is_numeric($val) ? date('d/m/Y', $val) : $val;
						}
						if (!empty($val) && strpos($val, '/') !== false) {
							return $val;
						}
						return '?';
					},
					'value' => $dbirth
				));

				/**
				 * Comune di nascita.
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_comval = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'comune_b');

				//if the country has been set and it's not "Italy", then don't let the user select Comune and Provincia.
				$is_foreign = (empty($staval) || !isset($this->nazioni[$staval]) || strcasecmp($this->nazioni[$staval]['name'], 'ITALIA'));

				// default value for "comune di nascita"
				$combirth = !empty($guests['pbirth']) && $guest_ind < 2 && !$is_foreign ? strtoupper($guests['pbirth']) : '';

				// assign the default value found from pax_data registration
				$comval = $pax_comval;

				$result = array();
				if (empty($pax_comval)) {
					$result = $this->sanitizeComune($combirth);
					if (!empty($combirth) && $guest_ind < 2 && (!$is_foreign && !empty($staval)) ) {
						//if $combirth has been sanitized, then you should just check if the province is the right one
						if (isset($result['combirth'])) {
							$result = $this->checkComune($result['combirth'], true, $result['province']);
						} else {
							$result = $this->checkComune($combirth, false, '');
						}	
						$combirth = $result['combirth'];
					}

					if (!$is_foreign && !empty($staval)) {
						$pax_combirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'place_birth');
						$combirth = !empty($pax_combirth) ? strtoupper($pax_combirth) : $combirth;
						$result = $this->sanitizeComune($combirth);
						//If $combirth have been sanitized, then you should just check if the province is the right one
						if (isset($result['combirth'])) {
							$result = $this->checkComune($result['combirth'], true, $result['province']);
						} else {
							$result = $this->checkComune($combirth, false, '');
						}
						$comval = isset($result['comval']) ? $result['comval'] : $combirth;
					}
				}

				// se il comune di nascita è vuoto o è stato indovinato, allora aggiungi la classe. Altrimenti non rendere selezionabile il comune.
				$comune = empty($combirth);
				$comguessed = (isset($result['similar']) && $result['similar']);
				$addclass = false;
				if (empty($pax_comval) && ($comune || $comguessed)) {
					if (empty($staval)) {
						//se lo stato manca, comunque rendilo selezionabile, sia che sia vuoto, sia che sia stato "indovinato"
						$addclass = true;
					} elseif (!$is_foreign && !empty($staval)) {
						//se lo stato esiste, ed è italiano, allora è selezionabile. Se è straniero, non farlo scegliere in quanto non necessario.
						$addclass = true;	
					}
				}
				array_push($insert_row, array(
					'key' => 'comune_b',
					'attr' => array(
						'class="center'.($addclass ? ' vbo-report-load-comune' : '').'"'
					),
					'callback' => function($val) use ($addclass) {
						return !empty($val) && isset($this->comuniProvince['comuni'][$val]) ? $this->comuniProvince['comuni'][$val]['name'] : ($addclass ? '?' : "---");
					},
					'no_export_callback' => 1,
					'value' => $comval
				));

				/**
				 * Provincia di nascita.
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_province = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'province_b');

				$comune = empty($combirth);
				$comguessed = (isset($result['similar']) && $result['similar']);
				$province = (isset($result['provinceok']) && !$result['provinceok']);
				$addclass = false; 
				if (empty($pax_province) && ($comune || $comguessed || $province)) {
					if (empty($staval)) {
						$addclass = true;
					} elseif (!$is_foreign && !empty($staval)) {
						$addclass = true;	
					}
				}

				// check if we have an exact value
				$use_province = $pax_province;
				$use_province = empty($use_province) && !empty($result['province']) ? $result['province'] : $use_province;
				$use_province = empty($use_province) ? ($addclass ? '?' : "---") : $use_province;

				array_push($insert_row, array(
					'key' => 'province_b',
					'attr' => array(
						'class="center'.($addclass ? ' vbo-report-load-provincia' : '').'"'
					),
					'value' => $use_province
				));

				/**
				 * Stato di nascita.
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_country = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_b');

				$staval   = !empty($pax_country) ? $pax_country : $staval;
				$stabirth = !empty($pax_country) ? $pax_country : $stabirth;

				array_push($insert_row, array(
					'key' => 'country_b',
					'attr' => array(
						'class="center'.(empty($stabirth) ? ' vbo-report-load-nazione' : '').'"'
					),
					'callback' => function ($val) {
						return (!empty($val) ? $this->nazioni[$val]['name'] : '?');
					},
					'no_export_callback' => 1,
					'value' => $staval
				));

				/**
				 * Cittadinanza.
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_country_c = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_c');

				$citizen = !empty($guests['country']) && $guest_ind < 2 ? $guests['country'] : '';
				$citizenval = '';
				if (!empty($citizen) && $guest_ind < 2) {
					$citizenval = $this->checkCountry($citizen);
				}

				// check nationality field from pre-checkin
				$pax_citizen = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'nationality');
				$citizen = !empty($pax_citizen) ? $pax_citizen : $citizen;

				$citizen = !empty($pax_country_c) ? $pax_country_c : $citizen;
				$citizenval = !empty($pax_country_c) ? $pax_country_c : $this->checkCountry($citizen);

				array_push($insert_row, array(
					'key' => 'country_c',
					'attr' => array(
						'class="center'.(empty($citizen) ? ' vbo-report-load-cittadinanza' : '').'"'
					),
					'callback' => function ($val) {
						return !empty($val) ? $this->nazioni[$val]['name'] : '?';
					},
					'no_export_callback' => 1,
					'value' => !empty($citizenval) ? $citizenval : ''
				));

				/**
				 * Comune di residenza.
				 * Check compatibility with pax_data field of driver for "Italy".
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
				$pax_comstay = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'comune_s');
				/**
				 * This is not needed!
				 * 
				 * @see 	https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/Manuali/MANUALEALBERGHI.pdf
				 */
				/*
				array_push($insert_row, array(
					'key' => 'comune_s',
					'attr' => array(
						'class="center'.(empty($pax_comstay) && $guest_ind < 2 ? ' vbo-report-load-field vbo-report-load-comune-stay' : '').'"'
					),
					'callback' => function($val) use ($guest_ind) {
						if ($guest_ind > 1) {
							// not needed for the Nth guest
							return "---";
						}
						if (!empty($val) && isset($this->comuniProvince['comuni'][$val])) {
							return $this->comuniProvince['comuni'][$val]['name'];
						}
						// information is missing and should be provided
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $pax_comstay,
				));
				*/

				/**
				 * Provincia di residenza.
				 * Check compatibility with pax_data field of driver for "Italy".
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
				$pax_provstay = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'province_s');
				/**
				 * This is not needed!
				 * 
				 * @see 	https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/Manuali/MANUALEALBERGHI.pdf
				 */
				/*
				array_push($insert_row, array(
					'key' => 'province_s',
					'attr' => array(
						'class="center'.(empty($pax_provstay) && $guest_ind < 2 ? ' vbo-report-load-field vbo-report-load-provincia-stay' : '').'"'
					),
					'callback' => function($val) use ($guest_ind) {
						if ($guest_ind > 1) {
							// not needed for the Nth guest
							return "---";
						}
						if (!empty($val) && isset($this->comuniProvince['province'][$val])) {
							return $this->comuniProvince['province'][$val];
						}
						// information is missing and should be provided
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $pax_provstay,
				));
				*/

				/**
				 * Stato di residenza.
				 * Check compatibility with pax_data field of driver for "Italy".
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
				$pax_provstay = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_s');
				/**
				 * This is not needed!
				 * 
				 * @see 	https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/Manuali/MANUALEALBERGHI.pdf
				 */
				/*
				array_push($insert_row, array(
					'key' => 'country_s',
					'attr' => array(
						'class="center'.(empty($pax_provstay) && $guest_ind < 2 ? ' vbo-report-load-field vbo-report-load-nazione-stay' : '').'"'
					),
					'callback' => function($val) use ($guest_ind) {
						if ($guest_ind > 1) {
							// not needed for the Nth guest
							return "---";
						}
						if (!empty($val) && isset($this->nazioni[$val])) {
							return $this->nazioni[$val]['name'];
						}
						// information is missing and should be provided
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $pax_provstay,
				));
				*/

				/**
				 * Indirizzo.
				 * Optional information that is not collected through any pax_data field.
				 * We try to fill in with the customer information, if available.
				 */
				$address = !empty($guests['address']) ? $guests['address'] : '';
				/**
				 * This is not needed!
				 * 
				 * @see 	https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/Manuali/MANUALEALBERGHI.pdf
				 */
				/*
				array_push($insert_row, array(
					'key' => 'address',
					'attr' => array(
						'class="center"'
					),
					'value' => $address
				));
				*/

				/**
				 * Tipo documento.
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_doctype = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'doctype');

				$doctype = '---';
				$doctype_cur_val = '';
				if ($guest_room_ind < 2 && !empty($pax_doctype)) {
					$doctype = $pax_doctype;
					$doctype_cur_val = $pax_doctype;
				}

				array_push($insert_row, array(
					'key' => 'doctype',
					'attr' => array(
						// we always allow to rectify this field, but if guessed, we style it with the class "vbo-report-load-elem-filled"
						'class="center'.($guest_room_ind < 2 ? ' vbo-report-load-doctype' : '').(!empty($doctype_cur_val) ? ' vbo-report-load-elem-filled' : '').'"'
					),
					'callback' => function ($val) use ($doctype_cur_val) {
						return !empty($doctype_cur_val) ? $doctype_cur_val : $val;
					},
					'no_export_callback' => 1,
					'value' => $doctype
				));

				//Numero documento

				/**
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_docnum = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'docnum');

				$docnum = !empty($pax_docnum) ? $pax_docnum : '---';

				if (empty($pax_docnum) && $guest_room_ind < 2) {
					if (is_array($guests['pax_data']) && !empty($guests['pax_data'][0][1]['docnum'])) {
						$docnum = $guests['pax_data'][0][1]['docnum'];
					} elseif (!empty($guests['docnum'])) {
						$docnum = $guests['docnum'];
					}
				}

				array_push($insert_row, array(
					'key' => 'docnum',
					'attr' => array(
						'class="center'.($guest_room_ind < 2 && empty($docnum) ? ' vbo-report-load-docnum' : '').'"'
					),
					'callback' => function ($val) {
						return empty($val) ? '?' : $val;
					},
					'value' => $docnum
				));

				//Luogo rilascio documento

				/**
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_docplace = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'docplace');

				$docplace = '';
				if ($guest_room_ind < 2 && !empty($pax_docplace)) {
					$docplace = $pax_docplace;
				}
				array_push($insert_row, array(
					'key' => 'docplace',
					'attr' => array(
						'class="center'.($guest_room_ind < 2 && empty($docplace) ? ' vbo-report-load-docplace' : '').'"'
					),
					'callback' => function ($val) use ($guest_room_ind) {
						if ($guest_room_ind > 1) {
							return '---';
						}
						return !empty($val) && isset($this->nazioni[$val]) ? $this->nazioni[$val]['name'] : '?';
					},
					'no_export_callback' => 1,
					'value' => $docplace
				));

				//id booking
				array_push($insert_row, array(
					'key' => 'idbooking',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($line_number) {
						// make sure to keep the data-bid attribute as it's used by JS to identify the booking ID
						return '<a data-bid="' . $val . '" href="index.php?option=com_vikbooking&task=editorder&cid[]=' . $val . '" target="_blank"><i class="' . VikBookingIcons::i('external-link') . '"></i> ' . $val . '</a> / <span>#' . $line_number . '</span>';
					},
					'ignore_export' => 1,
					'value' => $guests['id']
				));

				// push fields in the rows array as a new row
				array_push($this->rows, $insert_row);

				// increment guest index
				$guest_ind++;

				// increment line number
				$line_number++;
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

		//Debug
		if ($this->debug) {
			$this->setWarning('path to report file = '.urlencode(dirname(__FILE__)).'<br/>');
			$this->setWarning('$total_rooms_units = '.$total_rooms_units.'<br/>');
			$this->setWarning('$bookings:<pre>'.print_r($bookings, true).'</pre><br/>');
		}

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
		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? json_decode($pfiller, true) : array();
		$pfiller = !is_array($pfiller) ? array() : $pfiller;

		//map of the rows keys with their related length
		$keys_length_map = array(
			'tipo' 		 => 2,
			'checkin' 	 => 10,
			'nights' 	 => 2,
			'last_name'  => 50,
			'first_name' => 30,
			'gender' 	 => 1,
			'date_birth' => 10,
			'comune_b' 	 => 9,
			'province_b' => 2,
			'country_b'  => 9,
			'country_c'  => 9,
			// 'comune_s' 	 => 9,
			// 'province_s' => 2,
			// 'country_s'  => 9,
			// the field "address" is optional
			// 'address' 	 => 50,
			//
			'doctype' 	 => 5,
			'docnum' 	 => 20,
			'docplace' 	 => 9,
		);

		//pool of booking IDs to update their history
		$booking_ids = array();

		//array of lines (one line for each guest)
		$lines = array();

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
				// 0 or '---' should be changed to an empty string (case of "-- Estero --" or field to be filled with Blank)
				$value = empty($value) || $value == '---' ? '' : $value;
				// concatenate the field to the current line
				$line_cont .= $this->valueFiller($value, $keys_length_map[$field['key']]);
			}

			//push the line in the array of lines
			array_push($lines, $line_cont);
		}

		// update the history for all bookings affected
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
	 * Helper method invoked via AJAX by the controller.
	 * Needed to save the manual entries for the pax data.
	 * 
	 * @param 	array 	$manual_data 	the object representation of the manual entries.
	 * 
	 * @return 	array 					one boolean value array with the operation result.
	 */
	public function updatePaxData($manual_data = array())
	{
		if (!is_array($manual_data) || !$manual_data) {
			VBOHttpDocument::getInstance()->close(400, 'Nothing to save!');
		}

		// re-build manual entries object representation
		$bids_guests = array();
		foreach ($manual_data as $guest_ind => $guest_data) {
			if (!is_numeric($guest_ind) || !is_array($guest_data) || empty($guest_data['bid']) || !isset($guest_data['bid_index']) || count($guest_data) < 2) {
				// empty or invalid manual entries array
				continue;
			}
			// the guest index in the reportObj starts from 0
			$use_guest_ind = ($guest_ind + 1 - (int)$guest_data['bid_index']);
			if (!isset($bids_guests[$guest_data['bid']])) {
				$bids_guests[$guest_data['bid']] = array();
			}
			// set manual entries for this guest number
			$bids_guests[$guest_data['bid']][$use_guest_ind] = $guest_data;
			// remove the "bid" and "bid_index" keys
			unset($bids_guests[$guest_data['bid']][$use_guest_ind]['bid'], $bids_guests[$guest_data['bid']][$use_guest_ind]['bid_index']);
		}

		if (!count($bids_guests)) {
			VBOHttpDocument::getInstance()->close(400, 'No manual entries to save found');
		}

		// loop through all bookings to update the data for the various rooms and guests
		$bids_updated = 0;
		foreach ($bids_guests as $bid => $entries) {
			$b_rooms = VikBooking::loadOrdersRoomsData($bid);
			if (empty($b_rooms)) {
				continue;
			}
			// count guests per room
			$room_guests = array();
			foreach ($b_rooms as $b_room) {
				$room_guests[] = $b_room['adults'] + $b_room['children'];
			}
			// get current booking pax data
			$pax_data = VBOCheckinPax::getBookingPaxData($bid);
			$pax_data = empty($pax_data) ? array() : $pax_data;
			foreach ($entries as $guest_ind => $guest_data) {
				// find room index for this guest
				$room_num = 0;
				$use_guest_ind = $guest_ind;
				foreach ($room_guests as $room_index => $tot_guests) {
					// find the proper guest index for the room to which this belongs
					if ($use_guest_ind <= $tot_guests) {
						// proper room index found for this guest
						$room_num = $room_index;
						break;
					} else {
						// it's probably in a next room
						$use_guest_ind -= $tot_guests;
					}
				}
				// push new pax data for this room and guest
				if (!isset($pax_data[$room_num])) {
					$pax_data[$room_num] = array();
				}
				if (!isset($pax_data[$room_num][$use_guest_ind])) {
					$pax_data[$room_num][$use_guest_ind] = $guest_data;
				} else {
					$pax_data[$room_num][$use_guest_ind] = array_merge($pax_data[$room_num][$use_guest_ind], $guest_data);
				}
			}
			// update booking pax data
			if (VBOCheckinPax::setBookingPaxData($bid, $pax_data)) {
				$bids_updated++;
			}
		}

		return $bids_updated ? [true] : [false];
	}

	/**
	 * Registers the name to give to the file being exported.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	protected function registerExportFileName()
	{
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$this->setExportCSVFileName($this->reportName . '-' . str_replace('/', '_', $pfromdate) . '-' . str_replace('/', '_', $ptodate) . '.txt');
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
	protected function valueFiller($val, $len)
	{
		$len = empty($len) || (int)$len <= 0 ? strlen($val) : (int)$len;

		//clean up $val in case there is still a CR or LF
		$val = str_replace(array("\r\n", "\r", "\n"), '', $val);
		
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
	 * Parses the file Comuni.csv and returns two associative
	 * arrays: one for the Comuni and one for the Province.
	 * Every line of the CSV is composed of: Codice, Comune, Provincia.
	 *
	 * @return 	array
	 */
	protected function loadComuniProvince()
	{
		$comprov_codes = array(
			'comuni' => array(
				0 => '-- Estero --'
			),
			'province' => array(
				0 => '-- Estero --'
			)
		);

		$csv = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Comuni.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}

			$v = explode(';', $row);
			if (count($v) != 3) {
				continue;
			}

			// trim values
			$v[0] = trim($v[0]);
			$v[1] = trim($v[1]);
			$v[2] = trim($v[2]);

			if (!isset($comprov_codes['comuni'][$v[0]])) {
				$comprov_codes['comuni'][$v[0]] = array();
			}

			$comprov_codes['comuni'][$v[0]]['name'] = $v[1];
			$comprov_codes['comuni'][$v[0]]['province'] = $v[2];
			$comprov_codes['province'][$v[2]] = $v[2];
		}

		return $comprov_codes;
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

	/**
	 * Parses the file Documenti.csv and returns an associative
	 * array with the code and name of the Documento.
	 * Every line of the CSV is composed of: Codice, Documento.
	 *
	 * @return 	array
	 */
	protected function loadDocumenti()
	{
		$documenti = array();

		$csv = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Documenti.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}

			$v = explode(';', $row);
			if (count($v) != 2) {
				continue;
			}

			// trim values
			$v[0] = trim($v[0]);
			$v[1] = trim($v[1]);

			$documenti[$v[0]] = $v[1];
		}

		return $documenti;
	}

	/**
	 * Returns an array that contains both name and key of the comune
	 * selected, plus the associated province.
	 * 
	 * @deprecated 	1.5.0 there is no need to rely on this method.
	 *
	 * @return 		array
	 */
	protected function checkComune($combirth, $checked, $province)
	{
		$result = array();
		$first_found = '';

		if (empty($combirth)) {
			return $result;
		}

		foreach ($this->comuniProvince['comuni'] as $key => $value) {
			if (!isset($value['name'])) {
				continue;
			}
			if ($value['name'] == $combirth) {
				$result['found'] = true;
				$result['combirth'] = $value['name'];
				$result['province'] = substr($value['province'], 0, 2);
				$result['comval'] = $key;
				$result['similar'] = false;
				break;
			} elseif (strpos($value['name'], trim($combirth)) !== false && empty($first_found)) {
				$result['found'] = true;
				$result['combirth'] = $value['name'];
				$first_found = $key;
				$result['similar'] = true;
				$result['province'] = substr($value['province'], 0, 2);
			}
		}
		if (!$result['found']) {
			$result['combirth'] = '';
		} 

		if ($checked === true && strlen($province) > 0  && $result['found']) {
			$result['province'] = $province;
			if ($province == $value['province']) {
				$result['provinceok'] = true;
				$result['province'] = substr($province, 0, 2);
			} else {
				$result['provinceok'] = false;
			}
		}
		if ($result['similar'] && $result['found']) {
			$result['comval'] = $first_found;
		}

		return $result;
	}

	/**
	 * Returns the key of the state selected by the user.
	 * 
	 * @deprecated 	1.5.0 there is no need to rely on this method.
	 *
 	 * @return string
	 */
	protected function checkCountry($country)
	{
		$found = false;
		$staval = '';

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

	/**
	 * Sanitizes the "Comune" if value also contains also the province. Example "PRATO (PO)".
	 * 
	 * @deprecated 	1.5.0 there is no need to rely on this method.
	 * 
	 * @param 	string 	$combirth 	the current value to check.
	 *
	 * @return 	array 				empty array or comune/province info.
	 */
	protected function sanitizeComune($combirth)
	{
		if (empty($combirth)) {
			return array();
		}

		$result = array();

		if (strlen($combirth) > 2) {
			if (strpos($combirth, "(") !== false) {
				$comnas = explode("(", $combirth);
				$result['combirth'] = trim($comnas[0]);
				$result['province'] = $comnas[1];
				$result['province'] = str_replace(')', '', $result['province']);
			}
		} elseif(strlen($combirth) > 0) {
			$result['province'] = trim($combirth);
			$result['similar'] = true;
		}

		return $result; 
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
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected function getGuestPaxDataValue($pax_data, $guests, $guest_ind, $key)
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
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected function calcGuestRoomIndex($guests, $guest_ind)
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

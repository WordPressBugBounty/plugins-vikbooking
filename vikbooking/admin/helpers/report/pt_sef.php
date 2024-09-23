<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2023 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * PtSef child Class of VikBookingReport.
 * Portugal: Servico de Estrangeiros e Fronteiras
 * 
 * @link 	https://siba.sef.pt/ajuda/modos-de-envio/#upload
 * 
 * @since 	1.16.2 (J) - 1.6.2 (WP)
 */
class VikBookingReportPtSef extends VikBookingReport
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
	 * List of countries.
	 * 
	 * @var  array
	 */
	protected $pt_countries = [];

	/**
	 * List of ID types.
	 * 
	 * @var  array
	 */
	protected $pt_idtypes = [];

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = 'SEF - Servico de Estrangeiros e Fronteiras';
		$this->reportFilters = [];

		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

		$this->pt_countries = $this->loadCountries();
		$this->pt_idtypes 	= $this->loadIdTypes();

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

		// JS lang defs
		JText::script('VBOADMINLEGENDSETTINGS');
		JText::script('VBANNULLA');
		JText::script('VBAPPLY');

		// get VBO Application Object
		$vbo_app = VikBooking::getVboApplication();

		// load the jQuery UI Datepicker
		$this->loadDatePicker();

		// custom export button
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadPtSefReport();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download Ficheiros</span></a>';

		// From Date Filter
		$filter_opt = array(
			'label' => '<label for="fromdate">'.JText::translate('VBOREPORTREVENUEDAY').'</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />',
			'type' => 'calendar',
			'name' => 'fromdate'
		);
		array_push($this->reportFilters, $filter_opt);

		// build JS helpers (fillers)
		$hidden_vals = '';
		$hidden_vals .= '<div id="vbo-report-ptsef-hidden" style="display: none;">';

		// countries
		$hidden_vals .= '	<div id="vbo-report-ptsef-nazione" class="vbo-report-ptsef-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-country" onchange="vboReportChosenCountry(this);"><option value=""></option>';
		foreach ($this->pt_countries as $code => $nat) {
			$hidden_vals .= '	<option value="' . $code . '">' . $nat['country_name'] . '</option>' . "\n";
		}
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';

		// ID types
		$hidden_vals .= '	<div id="vbo-report-ptsef-doctype" class="vbo-report-ptsef-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-doctype" onchange="vboReportChosenDoctype(this);"><option value=""></option>';
		foreach ($this->pt_idtypes as $code => $documento) {
			$hidden_vals .= '	<option value="' . $code . '">' . $documento . '</option>'."\n";
		}
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';

		// ID number
		$hidden_vals .= '	<div id="vbo-report-ptsef-docnum" class="vbo-report-ptsef-selcont" style="display: none;">';
		$hidden_vals .= '		<input type="text" size="40" id="choose-docnum" placeholder="Número Documento..." value="" /><br /><br />';
		$hidden_vals .= '		<button type="button" class="btn vbo-config-btn" onclick="vboReportChosenDocnum(document.getElementById(\'choose-docnum\').value);">' . JText::translate('VBAPPLY') . '</button>';
		$hidden_vals .= '	</div>';

		// date of birth
		$hidden_vals .= '	<div id="vbo-report-ptsef-dbirth" class="vbo-report-ptsef-selcont" style="display: none;">';
		$hidden_vals .= '		<input type="text" size="40" id="choose-dbirth" placeholder="Data Nascimento" value="" /><br /><br />';
		$hidden_vals .= '		<button type="button" class="btn vbo-config-btn" onclick="vboReportChosenDbirth(document.getElementById(\'choose-dbirth\').value);">' . JText::translate('VBAPPLY') . '</button>';
		$hidden_vals .= '	</div>';

		$hidden_vals .= '</div>';


		// build HTML helper
		$html_helper = 
<<<HTML
<div class="vbo-report-pt-sef-settings-helper" style="display: none;">
	<div class="vbo-report-pt-sef-settings-filler">
		<div class="vbo-calendar-cfields-filler">
			<div class="vbo-calendar-cfields-inner">
				<div class="vbo-calendar-cfield-entry">
					<label for="nif">NIF</label>
					<span>
						<input type="text" id="nif" value="" placeholder="Identificação fiscal" />
					</span>
				</div>
				<div class="vbo-calendar-cfield-entry">
					<label for="estabelecimento">Estabelecimento</label>
					<span>
						<input type="number" id="estabelecimento" min="0" max="9999" value="" placeholder="Número do Estabelecimento" />
					</span>
				</div>
				<div class="vbo-calendar-cfield-entry">
					<label for="nome">Nome</label>
					<span>
						<input type="text" id="nome" value="" placeholder="Nome" />
					</span>
				</div>
				<div class="vbo-calendar-cfield-entry">
					<label for="morada">Morada</label>
					<span>
						<input type="text" id="morada" value="" placeholder="Morada" />
					</span>
				</div>
				<div class="vbo-calendar-cfield-entry">
					<label for="localidade">Localidade</label>
					<span>
						<input type="text" id="localidade" value="" placeholder="Localidade" />
					</span>
				</div>
				<div class="vbo-calendar-cfield-entry">
					<label for="postalcode">Codigo Postal</label>
					<span>
						<input type="text" id="postalcode" value="" />
					</span>
				</div>
				<div class="vbo-calendar-cfield-entry">
					<label for="zonapostal">Zona Postal</label>
					<span>
						<input type="text" id="zonapostal" value="" />
					</span>
				</div>
				<div class="vbo-calendar-cfield-entry">
					<label for="telefone">Telefone</label>
					<span>
						<input type="text" id="telefone" value="" />
					</span>
				</div>
				<div class="vbo-calendar-cfield-entry">
					<label for="fax">Fax</label>
					<span>
						<input type="text" id="fax" value="" />
					</span>
				</div>
				<div class="vbo-calendar-cfield-entry">
					<label for="contacto">Nome Contacto</label>
					<span>
						<input type="text" id="contacto" value="" />
					</span>
				</div>
				<div class="vbo-calendar-cfield-entry">
					<label for="email">Email Contacto</label>
					<span>
						<input type="email" id="email" value="" />
					</span>
				</div>
			</div>
		</div>
	</div>
</div>
HTML;

		// append button to manage the property information (Registo de Cabeçalho, which is the "header", so the first line of the DAT file) and HTML helper
		$filter_opt = array(
			'label' => '<label>Registo de Cabeçalho</label>',
			'html' => '<button type="button" class="btn vbo-config-btn vbo-report-ptsef-mngsettings" onclick="vboPtSefManageSettings();"><i class="' . VikBookingIcons::i('cogs') . '"></i> ' . JText::translate('VBOADMINLEGENDSETTINGS') . '</button>' . $hidden_vals . $html_helper,
		);
		array_push($this->reportFilters, $filter_opt);

		// append button to save the data when creating manual values
		$filter_opt = array(
			'label' => '<label class="vbo-report-ptsef-manualsave" style="display: none;">' . JText::translate('VBOGUESTSDETAILS') . '</label>',
			'html' => '<button type="button" class="btn vbo-config-btn vbo-report-ptsef-manualsave" style="display: none;" onclick="vboPtSefSaveData();"><i class="' . VikBookingIcons::i('save') . '"></i> ' . JText::translate('VBSAVE') . '</button>',
		);
		array_push($this->reportFilters, $filter_opt);

		// get minimum check-in and maximum check-out for dates filters
		$df = $this->getDateFormat();
		$mincheckin = 0;
		$maxcheckout = 0;
		$q = "SELECT MIN(`checkin`) AS `mincheckin`, MAX(`checkout`) AS `maxcheckout` FROM `#__vikbooking_orders` WHERE `status`='confirmed' AND `closure`=0;";
		$this->dbo->setQuery($q);
		$data = $this->dbo->loadAssoc();
		if ($data) {
			if (!empty($data['mincheckin']) && !empty($data['maxcheckout'])) {
				$mincheckin = $data['mincheckin'];
				$maxcheckout = $data['maxcheckout'];
			}
		}

		// jQuery code for the datepicker calendars and select2
		$pfromdate = VikRequest::getString('fromdate', date($df), 'request');
		$js = 'jQuery(function() {
			jQuery(".vbo-report-datepicker:input").datepicker({
				'.(!empty($mincheckin) ? 'minDate: "'.date($df, $mincheckin).'", ' : '').'
				'.(!empty($maxcheckout) ? 'maxDate: "'.date($df, $maxcheckout).'", ' : '').'
				dateFormat: "'.$this->getDateFormat('jui').'"
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
		});';
		$this->setScript($js);

		// js for managing the property information and for using the fillers
		$report_settings = VBOFactory::getConfig()->getArray("report_{$this->reportFile}_settings", []);
		$report_settings_js = json_encode($report_settings);
		$js_ajax_base = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=invoke_report&report=' . $this->reportFile);
		$js_save_icn = VikBookingIcons::i('save');
		$js_saving_icn = VikBookingIcons::i('circle-notch', 'fa-spin fa-fw');
		$js_saved_icn = VikBookingIcons::i('check-circle');
		$js_birth_min = (date('Y') - 100);
		$js_birth_max = date('Y');

		$this->setScript(
<<<JS
var reportActiveCell = null, reportObj = {};
var vbo_report_js_ajax_base = "$js_ajax_base";
var vbo_report_settings = $report_settings_js;
var vbo_report_settings_def = [
	'nif',
	'estabelecimento',
	'nome',
	'morada',
	'localidade',
	'postalcode',
	'zonapostal',
	'telefone',
	'fax',
	'contacto',
	'email',
];
var vbo_ptsef_save_icn = "$js_save_icn";
var vbo_ptsef_saving_icn = "$js_saving_icn";
var vbo_ptsef_saved_icn = "$js_saved_icn";

// manage property settings/information
function vboPtSefManageSettings() {
	let modal_body = VBOCore.displayModal({
		suffix: 		'report-pt-sef',
		extra_class: 	'vbo-modal-tall',
		title: 			Joomla.JText._('VBOADMINLEGENDSETTINGS'),
		body_prepend: 	true,
		footer_left: 	'<button type="button" class="btn" onclick="vboPtSefCancelSettings();">' + Joomla.JText._('VBANNULLA') + '</button>',
		footer_right: 	'<button type="button" class="btn btn-success" onclick="vboPtSefSaveSettings();"><i class="icon-edit"></i> ' + Joomla.JText._('VBAPPLY') + '</button>',
		dismiss_event: 	'vbo-report-pt-sef-settings-dismiss',
		onDismiss: 		() => {
			jQuery('.vbo-report-pt-sef-settings-filler').appendTo('.vbo-report-pt-sef-settings-helper');
		},
	});

	jQuery('.vbo-report-pt-sef-settings-filler').appendTo(modal_body);

	// populate current settings
	vbo_report_settings_def.forEach((sett_name) => {
		if (vbo_report_settings.hasOwnProperty(sett_name)) {
			jQuery('#' + sett_name).val(vbo_report_settings[sett_name]);
		}
	});
}

// save settings
function vboPtSefSaveSettings() {
	let vbo_pt_sef_save_settings = {
		nif: jQuery('#nif').val(),
		estabelecimento: jQuery('#estabelecimento').val(),
		nome: jQuery('#nome').val(),
		morada: jQuery('#morada').val(),
		localidade: jQuery('#localidade').val(),
		postalcode: jQuery('#postalcode').val(),
		zonapostal: jQuery('#zonapostal').val(),
		telefone: jQuery('#telefone').val(),
		fax: jQuery('#fax').val(),
		contacto: jQuery('#contacto').val(),
		email: jQuery('#email').val(),
	};

	VBOCore.doAjax(
		vbo_report_js_ajax_base,
		{
			call: "savePtSefSettings",
			params: vbo_pt_sef_save_settings,
			tmpl: "component"
		},
		(success) => {
			// overwrite current settings object
			vbo_report_settings = Object.assign(vbo_report_settings, vbo_pt_sef_save_settings);
			// dismiss modal
			VBOCore.emitEvent('vbo-report-pt-sef-settings-dismiss');
		},
		(err) => {
			alert(err.responseText);
		},
	);
}

// cancel settings
function vboPtSefCancelSettings() {
	// dismiss modal
	VBOCore.emitEvent('vbo-report-pt-sef-settings-dismiss');
}

// download function
function vboDownloadPtSefReport() {
	if (!confirm("Certifique-se de ter preenchido todas as informações. Continuar com o download?")) {
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

// save data after manual fillers
function vboPtSefSaveData() {
	jQuery("button.vbo-report-ptsef-manualsave").find("i").attr("class", vbo_ptsef_saving_icn);
	VBOCore.doAjax(
		vbo_report_js_ajax_base,
		{
			call: "updatePaxData",
			params: reportObj,
			tmpl: "component"
		},
		(response) => {
			if (!response || !response[0]) {
				alert("An error occurred.");
				return false;
			}
			jQuery("button.vbo-report-ptsef-manualsave").addClass("btn-success").find("i").attr("class", vbo_ptsef_saved_icn);
		},
		(error) => {
			alert(error.responseText);
			jQuery("button.vbo-report-ptsef-manualsave").removeClass("btn-success").find("i").attr("class", vbo_ptsef_save_icn);
		}
	);
}

// DOM ready state
jQuery(function() {
	// prepare filler helpers
	jQuery("#vbo-report-ptsef-hidden").children().detach().appendTo(".vbo-info-overlay-report");
	jQuery("#choose-country").select2({placeholder: "- País -", width: "200px"});
	jQuery("#choose-doctype").select2({placeholder: "- Tipo Documento -", width: "200px"});
	jQuery("#choose-dbirth").datepicker({
		maxDate: 0,
		dateFormat: "dd/mm/yy",
		changeMonth: true,
		changeYear: true,
		yearRange: "$js_birth_min:$js_birth_max"
	});

	// click events
	jQuery(".vbo-report-load-nazione, .vbo-report-load-nazione-stay, .vbo-report-load-cittadinanza").click(function() {
		reportActiveCell = this;
		jQuery(".vbo-report-ptsef-selcont").hide();
		jQuery("#vbo-report-ptsef-nazione").show();
		vboShowOverlay();
	});
	jQuery(".vbo-report-load-doctype").click(function() {
		reportActiveCell = this;
		jQuery(".vbo-report-ptsef-selcont").hide();
		jQuery("#vbo-report-ptsef-doctype").show();
		vboShowOverlay();
	});
	jQuery(".vbo-report-load-docplace").click(function() {
		reportActiveCell = this;
		jQuery(".vbo-report-ptsef-selcont").hide();
		jQuery("#vbo-report-ptsef-nazione").show();
		vboShowOverlay();
	});
	jQuery(".vbo-report-load-docnum").click(function() {
		reportActiveCell = this;
		jQuery(".vbo-report-ptsef-selcont").hide();
		jQuery("#vbo-report-ptsef-docnum").show();
		vboShowOverlay();
		setTimeout(function(){jQuery("#choose-docnum").focus();}, 500);
	});
	jQuery(".vbo-report-load-dbirth").click(function() {
		reportActiveCell = this;
		jQuery(".vbo-report-ptsef-selcont").hide();
		jQuery("#vbo-report-ptsef-dbirth").show();
		vboShowOverlay();
	});
});

function vboReportChosenCountry(country_el) {
	var c_code = country_el.value;
	var c_val = country_el.options[country_el.selectedIndex].text;
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
	jQuery(".vbo-report-ptsef-manualsave").show();
}

function vboReportChosenDoctype(doctype) {
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
	jQuery("#choose-doctype").val("").select2("data", null, false);
	jQuery(".vbo-report-ptsef-manualsave").show();
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
	jQuery(".vbo-report-ptsef-manualsave").show();
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
	jQuery(".vbo-report-ptsef-manualsave").show();
}
JS
		);

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
			// Export functions may set errors rather than exiting the process, and the View may continue the execution to attempt to render the report.
			return false;
		}

		// input fields and other vars
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';
		$currency_symb = VikBooking::getCurrencySymb();
		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();

		// Get dates timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($pfromdate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}

		// Query to obtain the records (arrivals, departures and stayovers for the selected date)
		$q = "SELECT `o`.`id`,`o`.`custdata`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,".
			"`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`t_first_name`,`or`.`t_last_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,".
			"`co`.`idcustomer`,`co`.`pax_data`,`c`.`first_name`,`c`.`last_name`,`c`.`country` AS `customer_country`,`c`.`address`,`c`.`doctype`,`c`.`docnum`,`c`.`gender`,`c`.`bdate`,`c`.`pbirth` ".
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` ".
			"LEFT JOIN `#__vikbooking_customers_orders` AS `co` ON `co`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_customers` AS `c` ON `c`.`id`=`co`.`idcustomer` ".
			"WHERE `o`.`status`='confirmed' AND `o`.`closure`=0 AND (
				(`o`.`checkin` >= $from_ts AND `o`.`checkin` <= $to_ts) OR (`o`.`checkout` >= $from_ts AND `o`.`checkout` <= $to_ts) OR (`o`.`checkin` < $from_ts AND `o`.`checkout` > $to_ts)
			) ".
			"ORDER BY `o`.`checkin` ASC, `o`.`id` ASC;";
		$this->dbo->setQuery($q);
		$records = $this->dbo->loadAssocList();

		if (!$records) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			return false;
		}

		// nest records with multiple rooms booked inside sub-array
		$bookings = [];
		foreach ($records as $v) {
			if (!isset($bookings[$v['id']])) {
				$bookings[$v['id']] = [];
			}
			array_push($bookings[$v['id']], $v);
		}

		// free some memory up
		unset($records);

		// define the columns of the report
		$this->cols = array(
			// type
			array(
				'key' => 'type',
				'sortable' => 1,
				'label' => JText::translate('VBPSHOWSEASONSTHREE')
			),
			// last name
			array(
				'key' => 'lastname',
				'sortable' => 1,
				'label' => JText::translate('ORDER_LNAME')
			),
			// name
			array(
				'key' => 'name',
				'sortable' => 1,
				'label' => JText::translate('ORDER_NAME')
			),
			// nationality
			array(
				'key' => 'nationality',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERNATION')
			),
			// birthplace
			array(
				'key' => 'place_birth',
				'attr' => array(
					'class="center"'
				),
				'tip' => ucwords(JText::translate('VBOFILTEISROPTIONAL')),
				'label' => JText::translate('VBOCUSTPLACEBIRTH')
			),
			// birth date
			array(
				'key' => 'date_birth',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERBDATE')
			),
			// docnum
			array(
				'key' => 'docnum',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBOCUSTDOCNUM')
			),
			// doctype
			array(
				'key' => 'doctype',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBOCUSTDOCTYPE')
			),
			// docplace
			array(
				'key' => 'docplace',
				'attr' => array(
					'class="center"'
				),
				'label' => 'País Emissor'
			),
			// country_s
			array(
				'key' => 'country_s',
				'attr' => array(
					'class="center"'
				),
				'label' => 'País Residência'
			),
			// place_s
			array(
				'key' => 'place_s',
				'tip' => ucwords(JText::translate('VBOFILTEISROPTIONAL')),
				'label' => 'Local Residência'
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
			// id booking
			array(
				'key' => 'idbooking',
				'sortable' => 1,
				'attr' => array(
					'class="center"'
				),
				'label' => 'ID'
			),
		);

		// loop over the bookings to build the rows of the report
		$from_info = getdate($from_ts);
		foreach ($bookings as $gbook) {
			// count the total number of guests for all rooms of this booking
			$tot_booking_guests = 0;
			$room_guests = [];
			foreach ($gbook as $rbook) {
				$tot_booking_guests += ($rbook['adults'] + $rbook['children']);
				$room_guests[] = ($rbook['adults'] + $rbook['children']);
			}

			// make sure to decode the current pax data
			if (!empty($gbook[0]['pax_data'])) {
				$gbook[0]['pax_data'] = json_decode($gbook[0]['pax_data'], true);
				$gbook[0]['pax_data'] = !is_array($gbook[0]['pax_data']) ? [] : $gbook[0]['pax_data'];
			}

			// push a copy of the booking for each guest
			$guests_rows = [];
			for ($i = 1; $i <= $tot_booking_guests; $i++) {
				array_push($guests_rows, $gbook[0]);
			}

			// create one row for each guest
			$guest_ind = 1;
			foreach ($guests_rows as $ind => $guests) {
				// prepare row record for this room-guest
				$insert_row = [];

				// find the actual guest-room-index
				$guest_room_ind = $this->calcGuestRoomIndex($room_guests, $guest_ind);

				// stay type
				if (date('Y-m-d', $guests['checkin']) == date('Y-m-d', $from_ts)) {
					$stay_type = JText::translate('VBOTYPEARRIVAL');
				} elseif (date('Y-m-d', $guests['checkout']) == date('Y-m-d', $from_ts)) {
					$stay_type = JText::translate('VBOTYPEDEPARTURE');
				} else {
					$stay_type = JText::translate('VBOTYPESTAYOVER');
				}
				array_push($insert_row, array(
					'key' => 'type',
					'ignore_export' => 1,
					'value' => $stay_type
				));

				// last name
				$cognome = !empty($guests['t_last_name']) ? $guests['t_last_name'] : $guests['last_name'];
				$pax_cognome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'last_name');
				$cognome = !empty($pax_cognome) ? $pax_cognome : $cognome;
				array_push($insert_row, array(
					'key' => 'lastname',
					'value' => $cognome
				));

				// name
				$nome = !empty($guests['t_first_name']) ? $guests['t_first_name'] : $guests['first_name'];
				$pax_nome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'first_name');
				$nome = !empty($pax_nome) ? $pax_nome : $nome;
				array_push($insert_row, array(
					'key' => 'name',
					'value' => $nome
				));

				/**
				 * Nationality.
				 * Check compatibility with pax_data field of driver for "Portugal".
				 */
				$citizen = !empty($guests['country']) && $guest_ind < 2 ? $guests['country'] : '';
				$pax_country_c = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_c');
				$citizen = !empty($pax_country_c) ? $pax_country_c : $citizen;

				// check nationality field from pre-checkin
				$pax_citizen = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'nationality');
				$citizen = empty($citizen) && !empty($pax_citizen) ? $pax_citizen : $citizen;

				array_push($insert_row, array(
					'key' => 'nationality',
					'attr' => array(
						'class="center' . (empty($citizen) ? ' vbo-report-load-cittadinanza' : '') . '"'
					),
					'callback' => function ($val) {
						return !empty($val) && isset($this->pt_countries[$val]) ? $this->pt_countries[$val]['country_name'] : '?';
					},
					'no_export_callback' => 1,
					'value' => $citizen
				));

				// birth place
				$pax_pbirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'place_birth');
				array_push($insert_row, array(
					'key' => 'place_birth',
					'attr' => array(
						'class="center"'
					),
					'value' => ($pax_pbirth ? $pax_pbirth : '')
				));

				// birth date
				$dbirth = !empty($guests['bdate']) && $guest_ind < 2 ? VikBooking::getDateTimestamp($guests['bdate'], 0, 0) : '';
				$pax_dbirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'date_birth');
				$dbirth = !empty($pax_dbirth) ? $pax_dbirth : $dbirth;
				$dbirth = (strpos($dbirth, '/') === false && strpos($dbirth, VikBooking::getDateSeparator()) === false) ? $dbirth : VikBooking::getDateTimestamp($dbirth, 0, 0);
				array_push($insert_row, array(
					'key' => 'date_birth',
					'attr' => array(
						'class="center' . (empty($dbirth) ? ' vbo-report-load-dbirth' : '') . '"'
					),
					'callback' => function ($val) {
						if (!empty($val) && strpos($val, '/') === false && strpos($val, VikBooking::getDateSeparator()) === false) {
							return date('d/m/Y', $val);
						}
						if (!empty($val) && strpos($val, '/') !== false) {
							return $val;
						}
						return '?';
					},
					'export_callback' => function ($val) {
						if (!empty($val) && strpos($val, '/') === false && strpos($val, VikBooking::getDateSeparator()) === false) {
							return date('Ymd', $val);
						}
						if (!empty($val) && strpos($val, '/') !== false) {
							return date('Ymd', VikBooking::getDateTimestamp($val, 0, 0));
						}
						return '?';
					},
					'value' => $dbirth
				));

				/**
				 * ID Number
				 * Check compatibility with pax_data field of driver for "Portugal".
				 */
				$docnum = !empty($guests['docnum']) && $guest_ind < 2 ? $guests['docnum'] : '';
				$pax_docnum = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'docnum');
				$docnum = !empty($pax_docnum) ? $pax_docnum : $docnum;

				array_push($insert_row, array(
					'key' => 'docnum',
					'attr' => array(
						'class="center' . (empty($docnum) ? ' vbo-report-load-docnum' : '') . '"'
					),
					'callback' => function ($val) {
						return empty($val) ? '?' : $val;
					},
					'value' => $docnum
				));

				/**
				 * ID Type
				 * Check compatibility with pax_data field of driver for "Portugal".
				 */
				$pax_doctype = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'doctype');
				$doctype = 'O';
				$doctype_cur_val = '';
				if (!empty($pax_doctype)) {
					$doctype = $pax_doctype;
					$doctype_cur_val = $pax_doctype;
				}

				array_push($insert_row, array(
					'key' => 'doctype',
					'attr' => array(
						// we always allow to rectify this field, but if guessed, we style it with the class "vbo-report-load-elem-filled"
						'class="center vbo-report-load-doctype' . (!empty($doctype_cur_val) ? ' vbo-report-load-elem-filled' : '') . '"'
					),
					'callback' => function ($val) use ($doctype_cur_val) {
						return !empty($doctype_cur_val) ? $doctype_cur_val : $val;
					},
					'no_export_callback' => 1,
					'value' => $doctype
				));

				/**
				 * ID Issuing Country
				 * Check compatibility with pax_data field of driver for "Portugal".
				 */
				$pax_docplace = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'docplace');
				$docplace = $pax_docplace;

				array_push($insert_row, array(
					'key' => 'docplace',
					'attr' => array(
						'class="center' . (empty($docplace) ? ' vbo-report-load-docplace' : '') . '"'
					),
					'callback' => function ($val) {
						return !empty($val) && isset($this->pt_countries[$val]) ? $this->pt_countries[$val]['country_name'] : '?';
					},
					'no_export_callback' => 1,
					'value' => $docplace
				));

				/**
				 * Country of residence.
				 * Check compatibility with pax_data field of driver for "Portugal".
				 */
				$pax_countrystay = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_s');
				array_push($insert_row, array(
					'key' => 'country_s',
					'attr' => array(
						'class="center' . (empty($pax_countrystay) ? ' vbo-report-load-field vbo-report-load-nazione-stay' : '') . '"'
					),
					'callback' => function($val) {
						if (!empty($val) && isset($this->pt_countries[$val])) {
							return $this->pt_countries[$val]['country_name'];
						}
						// information is missing and should be provided
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $pax_countrystay,
				));

				/**
				 * Place of residence.
				 * Check compatibility with pax_data field of driver for "Portugal".
				 */
				$pax_placestay = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'place_s');
				array_push($insert_row, array(
					'key' => 'place_s',
					'value' => $pax_placestay,
				));

				// checkin
				array_push($insert_row, array(
					'key' => 'checkin',
					'callback' => function ($val) {
						return date('d/m/Y', $val);
					},
					'export_callback' => function ($val) {
						return date('Ymd', $val);
					},
					'value' => $guests['checkin']
				));

				// checkout
				array_push($insert_row, array(
					'key' => 'checkout',
					'callback' => function ($val) {
						return date('d/m/Y', $val);
					},
					'export_callback' => function ($val) {
						return date('Ymd', $val);
					},
					'value' => $guests['checkout']
				));

				// id booking
				array_push($insert_row, array(
					'key' => 'idbooking',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) {
						// make sure to keep the data-bid attribute as it's used by JS to identify the booking ID
						return '<a data-bid="' . $val . '" href="index.php?option=com_vikbooking&task=editorder&cid[]=' . $val . '" target="_blank"><i class="' . VikBookingIcons::i('external-link') . '"></i> ' . $val . '</a>';
					},
					'ignore_export' => 1,
					'value' => $guests['id']
				));

				// push fields in the rows array as a new row
				array_push($this->rows, $insert_row);

				// increment guest index
				$guest_ind++;
			}
		}
		
		// sort the rows
		$this->sortRows($pkrsort, $pkrorder);

		// the footer row will just print the amount of records to export
		array_push($this->footerRow, array(
			array(
				'attr' => array(
					'class="vbo-report-total"'
				),
				'value' => '<h3>' . JText::translate('VBOREPORTSTOTALROW') . '</h3>'
			),
			array(
				'attr' => array(
					'colspan="' . (count($this->cols) - 1) . '"'
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

		// load settings from DB to populate the first line of the DAT file
		$report_settings = VBOFactory::getConfig()->getArray("report_{$this->reportFile}_settings", []);
		if (!$report_settings) {
			$this->setError('Mandatory report settings are missing');
			return false;
		}

		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? json_decode($pfiller, true) : [];
		$pfiller = !is_array($pfiller) ? [] : $pfiller;

		// map of the rows keys with their related length
		$keys_length_map = [
			'lastname' 	  => 40,
			'name' 		  => 40,
			'nationality' => 3,
			'place_birth' => 40,
			'date_birth'  => 8,
			'docnum' 	  => 16,
			'doctype' 	  => 3,
			'docplace' 	  => 3,
			'country_s'   => 3,
			'place_s' 	  => 30,
			'checkin' 	  => 8,
			'checkout' 	  => 8,
		];

		// the below field keys are optional and can be empty
		$optional_fields = ['place_birth', 'place_s'];

		// pool of booking IDs to update their history
		$booking_ids = [];

		// array of lines (header, one line for each guest and closure)
		$lines = [];

		// build header line by reading the parameters
		$header_line_parts = [
			'0',
			'BA03',
			str_pad((!empty($report_settings['nif']) ? $report_settings['nif'] : ''), 9),
			str_pad((!empty($report_settings['estabelecimento']) ? $report_settings['estabelecimento'] : ''), 4),
			str_pad((!empty($report_settings['nome']) ? $report_settings['nome'] : ''), 40),
			str_pad((!empty($report_settings['morada']) ? $report_settings['morada'] : ''), 40),
			str_pad((!empty($report_settings['localidade']) ? $report_settings['localidade'] : ''), 30),
			str_pad((!empty($report_settings['postalcode']) ? $report_settings['postalcode'] : ''), 4),
			str_pad((!empty($report_settings['zonapostal']) ? $report_settings['zonapostal'] : ''), 3),
			str_pad((!empty($report_settings['telefone']) ? $report_settings['telefone'] : ''), 10),
			str_pad((!empty($report_settings['fax']) ? $report_settings['fax'] : ''), 10),
			str_pad((!empty($report_settings['contacto']) ? $report_settings['contacto'] : ''), 40),
			str_pad((!empty($report_settings['email']) ? $report_settings['email'] : ''), 140),
		];

		// push registration header line
		$lines[] = implode('|', $header_line_parts) . '|';

		// build registration lines
		$registration_lines = [];

		// push the lines of the DAT file
		foreach ($this->rows as $ind => $row) {
			// build registration line for this guest
			$registration_line_parts = [
				// fixed registration type for each guest is 1
				'1'
			];

			// parse row for this guest
			foreach ($row as $field) {
				if (!isset($keys_length_map[$field['key']]) || isset($field['ignore_export'])) {
					// we don't need this information
					continue;
				}

				if ($field['key'] == 'idbooking' && !in_array($field['value'], $booking_ids)) {
					// register booking ID for later history update
					$booking_ids[] = $field['value'];
				}

				// report value
				if (isset($pfiller[$ind]) && isset($pfiller[$ind][$field['key']])) {
					if (strlen((string)$pfiller[$ind][$field['key']])) {
						$field['value'] = $pfiller[$ind][$field['key']];
					}
				}

				// always cast to string
				$field['value'] = (string)$field['value'];
				if (!$field['value'] && !in_array($field['key'], $optional_fields)) {
					// raise error message without stopping
					VikError::raiseWarning('', 'Row #' . ($ind + 1) . ' has got an empty value that should have been manually filled (' . $field['key'] . '). The file may be broken or incomplete.');
				}

				// get the final value to be included in the exported file
				if (isset($field['callback_export'])) {
					$field['callback'] = $field['callback_export'];
				}
				$value = !isset($field['no_export_callback']) && isset($field['callback']) && is_callable($field['callback']) ? $field['callback']($field['value']) : $field['value'];

				// set guest registration value with proper length
				$registration_line_parts[] = str_pad($value, $keys_length_map[$field['key']]);
			}

			// push guest registration line
			$registration_lines[] = implode('|', $registration_line_parts) . '|';
		}

		// append all guest registration lines
		$lines = array_merge($lines, $registration_lines);

		// build file number (size 5) by using the last 2 digit of the current year, and the day of the year (0 through 365)
		$now_info = getdate();
		$file_number = substr((string)$now_info['year'], -2) . $now_info['yday'];

		// build last registration line with summary
		$last_line_parts = [
			// fixed registration type for the last line is 9
			'9',
			// number of records (lines) in the file, including this one (header + guests + last line)
			str_pad((string)(count($registration_lines) + 2), 5, '0', STR_PAD_LEFT),
			// generation date
			date('Ymd'),
			// Hotel unit file serial number
			str_pad($file_number, 5, '0', STR_PAD_LEFT),
		];

		// append last line
		$lines[] = implode('|', $last_line_parts) . '|';

		// update the history for all bookings affected
		foreach ($booking_ids as $bid) {
			VikBooking::getBookingHistoryInstance()->setBid($bid)->store('RP', $this->reportName);
		}

		/**
		 * Custom export method supports a custom export handler, if previously set.
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
	 * AJAX endpoint to store the property settings.
	 * 
	 * @param 	array 	$prop_settings 	the settings to store.
	 * 
	 * @return 	void
	 */
	public function savePtSefSettings(array $prop_settings = [])
	{
		if (!$prop_settings) {
			VBOHttpDocument::getInstance()->close(500, 'Missing property settings');
		}

		VBOFactory::getConfig()->set("report_{$this->reportFile}_settings", $prop_settings);

		VBOHttpDocument::getInstance()->json(['success' => 1]);
	}

	/**
	 * Helper method invoked via AJAX by the controller.
	 * Needed to save the manual entries for the pax data.
	 * 
	 * @param 	array 	$manual_data 	the object representation of the manual entries.
	 * 
	 * @return 	array 					one boolean value array with the operation result.
	 */
	public function updatePaxData($manual_data = [])
	{
		if (!is_array($manual_data) || !$manual_data) {
			VBOHttpDocument::getInstance()->close(400, 'Nothing to save!');
		}

		// re-build manual entries object representation
		$bids_guests = [];
		foreach ($manual_data as $guest_ind => $guest_data) {
			if (!is_numeric($guest_ind) || !is_array($guest_data) || empty($guest_data['bid']) || !isset($guest_data['bid_index']) || count($guest_data) < 2) {
				// empty or invalid manual entries array
				continue;
			}
			// the guest index in the reportObj starts from 0
			$use_guest_ind = ($guest_ind + 1 - (int)$guest_data['bid_index']);
			if (!isset($bids_guests[$guest_data['bid']])) {
				$bids_guests[$guest_data['bid']] = [];
			}
			// set manual entries for this guest number
			$bids_guests[$guest_data['bid']][$use_guest_ind] = $guest_data;
			// remove the "bid" and "bid_index" keys
			unset($bids_guests[$guest_data['bid']][$use_guest_ind]['bid'], $bids_guests[$guest_data['bid']][$use_guest_ind]['bid_index']);
		}

		if (!$bids_guests) {
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
			$room_guests = [];
			foreach ($b_rooms as $b_room) {
				$room_guests[] = $b_room['adults'] + $b_room['children'];
			}
			// get current booking pax data
			$pax_data = VBOCheckinPax::getBookingPaxData($bid);
			$pax_data = empty($pax_data) ? [] : $pax_data;
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
					$pax_data[$room_num] = [];
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
	 */
	protected function registerExportFileName()
	{
		// load settings from DB to populate the first line of the DAT file
		$report_settings = VBOFactory::getConfig()->getArray("report_{$this->reportFile}_settings", []);

		// build file number (size 5) by using the last 2 digit of the current year, and the day of the year (0 through 365)
		$now_info = getdate();
		$file_number = substr((string)$now_info['year'], -2) . $now_info['yday'];

		// build file name (Nomenclatura – <NIF><Estabelecimento><Numero de Ficheiro>.DAT)
		$dat_fname = 'SEF - ';
		$dat_fname .= (!empty($report_settings['nif']) ? $report_settings['nif'] : '') . (!empty($report_settings['estabelecimento']) ? $report_settings['estabelecimento'] : '');
		$dat_fname .= $file_number . '.DAT';

		$this->setExportCSVFileName($dat_fname);
	}

	/**
	 * Loads the country names from DB.
	 *
	 * @return 	array
	 */
	protected function loadCountries()
	{
		return VikBooking::getCountriesArray();
	}

	/**
	 * Returns the associative list of ID types for Portugal.
	 *
	 * @return 	array
	 */
	protected function loadIdTypes()
	{
		return [
			"B" => "Identity card (Bilhete de Identidade)",
			"P" => "Passport (Passaporte)",
			"O" => "Other (Outro documento de identificação)",
		];
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

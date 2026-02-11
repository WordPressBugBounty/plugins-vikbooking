<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBookingReport implementation for (Italy - Sicily) ISTAT OTRS - Osservatorio Turistico Regione Sicilia.
 * This report requires the check-in guests data collection driver for Italy to be enabled.
 * 
 * @see 	Sistema_Informativo_Osservatorio_Turistico_ProtocolloPMS.pdf
 * 
 * @since 	1.18.3 (J) - 1.8.3 (WP)
 */
class VikBookingReportIstatSiciliaOtrs extends VikBookingReport
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
	 * List of Italian Police countries.
	 * 
	 * @var  array
	 */
	protected $italianPoliceCountries = [];

	/**
	 * List of Italian Police comuni.
	 * 
	 * @var  array
	 */
	protected $italianPoliceComuni = [];

	/**
	 * List of booking IDs affected by the export.
	 * 
	 * @var  	array
	 */
	protected $export_booking_ids = [];

	/**
	 * List of exported check-in dates (range).
	 * 
	 * @var  	array
	 */
	protected $exported_checkin_dates = [];

	/**
	 * The production (live, producción) endpoint.
	 * 
	 * @var  	string
	 */
	protected $endpoint_production = 'https://osservatorioturistico.regione.sicilia.it/webapi/api';

	/**
	 * The path to the temporary directory used by this report.
	 * 
	 * @var  	string
	 */
	protected $report_tmp_path = '';

	/**
	 * The software application name.
	 * 
	 * @var 	string
	 */
	protected $software_application_name = 'VikBooking - E4jConnect';

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = 'ISTAT Sicilia (OTRS)';
		$this->reportFilters = [];

		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

		$this->italianPoliceCountries = $this->loadItalianPoliceCountries();
		$this->italianPoliceComuni = $this->loadItalianPoliceComuni();

		// set the temporary report directory path
		$this->report_tmp_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'istat_otrs_tmp';

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
	 * @inheritDoc
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public function allowsProfileSettings()
	{
		// allow multiple report profile settings
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getSettingFields()
	{
		return [
			'hotelcode' => [
				'type'  => 'text',
				'label' => 'Codice Struttura',
				'help'  => 'Il codice identificativo della struttura (Hotel Code) per accedere alle funzionalità API dell\'osservatorio turistico mediante PMS. Il formato è spesso simile a TRS-IT-SIC-00000.',
			],
			'userid' => [
				'type'  => 'text',
				'label' => 'User ID',
				'help'  => 'Lo username della struttura per accedere alle funzionalità API dell\'osservatorio turistico mediante PMS. Non si tratta del nome utente per accedere al sistema via web, ma bensì quello relativo all\'accesso tramite API dei PMS. Il parametro viene fornito al momento della registrazione al sistema API dell\'osservatorio turistico. In alcuni casi è uguale al Codice Struttura.',
			],
			'password' => [
				'type'  => 'password',
				'label' => 'Password',
				'help'  => 'La password della struttura per accedere alle funzionalità API dell\'osservatorio turistico mediante PMS. Non si tratta della password per accedere al sistema via web, ma bensì quella relativo all\'accesso tramite API dei PMS. Il parametro viene fornito al momento della registrazione al sistema API dell\'osservatorio turistico.',
			],
			'defaultage' => [
				'type'    => 'number',
				'label'   => 'Età predefinita adulti',
				'help'    => 'Per ogni ospite è necessario specificare l\'età. Nel caso non fosse disponibile per alcuni ospiti, valorizzare un numero predefinito da utilizzare.',
				'min'     => 18,
				'max'     => 120,
				'default' => 30,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getScopedActions($scope = null, $visible = true)
	{
		// list of custom actions for this report
		$actions = [
			[
				'id' => 'registerCheckins',
				'name' => 'Trasmissione Arrivi',
				'help' => 'Trasmette i dati degli ospiti in arrivo (check-in) in un determinato giorno al sistema Osservatorio Turistico Regione Sicilia (OTRS).',
				'icon' => VikBookingIcons::i('cloud-upload-alt'),
				// flag to indicate that it requires the report data (lines)
				'export_data' => true,
				'scopes' => [
					'web',
					'cron',
				],
			],
			[
				'id' => 'registerCheckouts',
				'name' => 'Trasmissione Partenze',
				'help' => 'Trasmette i dati degli ospiti in partenza (check-out) in un determinato giorno al sistema Osservatorio Turistico Regione Sicilia (OTRS).',
				'icon' => VikBookingIcons::i('cloud-upload-alt'),
				// flag to indicate that it requires the report data (lines)
				'export_data' => true,
				'scopes' => [
					'web',
					'cron',
				],
			],
			[
				'id' => 'registerEndDay',
				'name' => 'Chiusura Giornaliera',
				'help' => 'Trasmette un messaggio di chiusura giornata al sistema Osservatorio Turistico Regione Sicilia (OTRS). Questo messaggio deve essere inviato sempre, in presenze e in assenza di movimenti di ospiti all\'interno della struttura. Deve essere omesso durante le chiusure temporanee (ad esempio per le strutture stagionali). I giorni che hanno ricevuto una chiusura di fine giornata non potranno più ricevere aggiornamenti su arrivi e/o partenze.',
				'icon' => VikBookingIcons::i('calendar-check'),
				'scopes' => [
					'web',
					'cron',
				],
				'params' => [
					'end_date' => [
						'type'    => 'calendar',
						'label'   => 'Data fine giornata',
						'help'    => 'Seleziona la data per la quale inviare la chiusura dei movimenti del giorno.',
						'default' => date($this->getDateFormat(), strtotime('today')),
					],
					'end_time' => [
						'type'    => 'time',
						'label'   => 'Orario fine giornata',
						'help'    => 'Seleziona l\'orario da usare per inviare la chiusura dei movimenti del giorno. Utile per evitare di ottenere date nel passato.',
						'default' => '16:00',
					],
				],
				'params_submit_lbl' => 'Esegui chiusura',
			],
			[
				'id' => 'testLoginLogout',
				'name' => 'Test Login',
				'icon' => VikBookingIcons::i('play'),
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'otrsLogin',
				'name' => 'Login',
				// flag to indicate that it's callable internally, but not graphically
				'hidden' => true,
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'otrsLogout',
				'name' => 'Logout',
				// flag to indicate that it's callable internally, but not graphically
				'hidden' => true,
				'scopes' => [
					'web',
				],
			],
		];

		// filter actions by scope
		if ($scope && (!strcasecmp($scope, 'cron') || !strcasecmp($scope, 'web'))) {
			$actions = array_filter($actions, function($action) use ($scope) {
				if (!($action['scopes'] ?? [])) {
					return true;
				}

				return in_array(strtolower($scope), $action['scopes']);
			});
		}

		// filter by visibility
		if ($visible) {
			$actions = array_filter($actions, function($action) {
				return !($action['hidden'] ?? false);
			});
		}

		return array_values($actions);
	}

	/**
	 * Returns the filters of this report.
	 *
	 * @return 	array
	 */
	public function getFilters()
	{
		if ($this->reportFilters) {
			// do not run this method twice, as it could load JS and CSS files.
			return $this->reportFilters;
		}

		$app = JFactory::getApplication();

		// get VBO Application Object
		$vbo_app = VikBooking::getVboApplication();

		// get the possibly injected report options
		$options = $this->getReportOptions();

		// load the jQuery UI Datepicker
		$this->loadDatePicker();

		// custom export button
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadRecordOtrs();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download File</span></a>';

		// load report settings
		$settings = $this->loadSettings();

		// build the hidden values for the selection of Comuni & Province and much more.
		$hidden_vals = '<div id="vbo-report-otrs-hidden" style="display: none;">';

		// build params container HTML structure
		$hidden_vals .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
		$hidden_vals .= '	<div class="vbo-params-wrap">';
		$hidden_vals .= '		<div class="vbo-params-container">';
		$hidden_vals .= '			<div class="vbo-params-block vbo-params-block-noborder">';

		// Sesso
		$hidden_vals .= '	<div id="vbo-report-otrs-sesso" class="vbo-report-otrs-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Sesso</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-sesso" onchange="vboReportChosenSesso(this);"><option value=""></option>';
		$sessos = [
			1 => 'M',
			2 => 'F'
		];
		foreach ($sessos as $code => $ses) {
			$hidden_vals .= '		<option value="' . $code . '">' . $ses . '</option>' . "\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// guest age
		$hidden_vals .= '	<div id="vbo-report-otrs-guestage" class="vbo-report-otrs-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Età ospite</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input id="choose-guestage" type="number" min="1" max="120" onchange="vboReportChosenGuestage(this.value);"/>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// provincia italiana o stato estero (bplacecode and rplacecode)
		$hidden_vals .= '	<div id="vbo-report-otrs-provenience" class="vbo-report-otrs-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Provincia Italiana o Stato Estero</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-provenience" onchange="vboReportChosenProvenience(this);"><option value=""></option>';
		$hidden_vals .= '				<optgroup label="Comuni Italiani">';
		foreach ($this->italianPoliceComuni as $code => $comune) {
			$hidden_vals .= '				<option value="' . $code . '">' . sprintf('%s (%s)', $comune['name'], $comune['province']) . '</option>'."\n";
		}
		$hidden_vals .= '				</optgroup>';
		$hidden_vals .= '				<optgroup label="Stati Esteri">';
		foreach ($this->italianPoliceCountries as $code => $policecountry) {
			$hidden_vals .= '				<option value="' . $code . '">' . $policecountry['name'] . '</option>'."\n";
		}
		$hidden_vals .= '				</optgroup>';
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// nazionalità (AlloggiatiWeb country codes)
		$hidden_vals .= '	<div id="vbo-report-otrs-country" class="vbo-report-otrs-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Nazionalità</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-country" onchange="vboReportChosenCountry(this);"><option value=""></option>';
		foreach ($this->italianPoliceCountries as $code => $policecountry) {
			$hidden_vals .= '			<option value="' . $code . '">' . $policecountry['name'] . '</option>'."\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// close params container HTML structure
		$hidden_vals .= '			</div>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';
		$hidden_vals .= '</div>';

		// close hidden values container
		$hidden_vals .= '</div>';

		// From Date Filter (with hidden values for the dropdown menus of Comuni, Province, Stati etc..)
		$filter_opt = array(
			'label' => '<label for="fromdate">Data movimento</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />' . $hidden_vals,
			'type' => 'calendar',
			'name' => 'fromdate'
		);
		array_push($this->reportFilters, $filter_opt);

		// Listings Filter
		$filter_opt = array(
			'label' => '<label for="listingsfilt">' . JText::translate('VBO_LISTINGS') . '</label>',
			'html' => '<span class="vbo-toolbar-multiselect-wrap">' . $vbo_app->renderElementsDropDown([
				'id'              => 'listingsfilt',
				'elements'        => 'listings',
				'placeholder'     => JText::translate('VBO_LISTINGS'),
				'allow_clear'     => 1,
				'attributes'      => [
					'name' => 'listings[]',
					'multiple' => 'multiple',
				],
				'selected_values' => (array) ($app->input->get('listings', [], 'array') ?: $options->get('listings', [])),
			]) . '</span>',
			'type' => 'select',
			'multiple' => true,
			'name' => 'listings',
		);
		array_push($this->reportFilters, $filter_opt);

		// export type filter
		$types = [
			'checkin'  => JText::translate('VBPICKUPAT'),
			'checkout' => JText::translate('VBRELEASEAT'),
		];
		$ptype = VikRequest::getString('type', 'checkin', 'request');
		$types_sel_html = $vbo_app->getNiceSelect($types, $ptype, 'type', '', '', '', '', 'type');
		$filter_opt = array(
			'label' => '<label for="type">' . JText::translate('VBPSHOWSEASONSTHREE') . '</label>',
			'html' => $types_sel_html,
			'type' => 'select',
			'name' => 'type',
		);
		array_push($this->reportFilters, $filter_opt);

		// append button to save the data when creating manual values
		$filter_opt = array(
			'label' => '<label class="vbo-report-otrs-manualsave" style="display: none;">Dati inseriti</label>',
			'html' => '<button type="button" class="btn vbo-config-btn vbo-report-otrs-manualsave" style="display: none;" onclick="vboOtrsSaveData();"><i class="' . VikBookingIcons::i('save') . '"></i> ' . JText::translate('VBSAVE') . '</button>',
		);
		array_push($this->reportFilters, $filter_opt);

		//jQuery code for the datepicker calendars, select2 and triggers for the dropdown menus
		$pfromdate = VikRequest::getString('fromdate', '', 'request');

		$js = 'var reportActiveCell = null, reportObj = {};
		var vbo_otrs_ajax_uri = "' . VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=invoke_report&report=' . $this->reportFile) . '";
		var vbo_otrs_save_icn = "' . VikBookingIcons::i('save') . '";
		var vbo_otrs_saving_icn = "' . VikBookingIcons::i('circle-notch', 'fa-spin fa-fw') . '";
		var vbo_otrs_saved_icn = "' . VikBookingIcons::i('check-circle') . '";
		jQuery(function() {
			//prepare main filters
			jQuery(".vbo-report-datepicker:input").datepicker({
				// data massima di generazione movimenti = ieri
				maxDate: "0d",
				dateFormat: "'.$this->getDateFormat('jui').'",
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			//prepare filler helpers
			jQuery("#vbo-report-otrs-hidden").children().detach().appendTo(".vbo-info-overlay-report");
			jQuery("#choose-provenience").select2({placeholder: "- Provincia o Stato Estero -", width: "200px"});
			jQuery("#choose-country").select2({placeholder: "- Nazionalità -", width: "200px"});
			//click events
			jQuery(".vbo-report-load-sesso").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-otrs-selcont").hide();
				jQuery("#vbo-report-otrs-sesso").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-guestage").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-otrs-selcont").hide();
				jQuery("#vbo-report-otrs-guestage").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-bplacecode, .vbo-report-load-rplacecode").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-otrs-selcont").hide();
				jQuery("#vbo-report-otrs-provenience").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-country").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-otrs-selcont").hide();
				jQuery("#vbo-report-otrs-country").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
		});
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
			jQuery("#choose-sesso").val("");
			jQuery(".vbo-report-otrs-manualsave").show();
		}
		function vboReportChosenGuestage(val) {
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
					reportObj[nowindex]["guest_age"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-guestage").val("");
			jQuery(".vbo-report-otrs-manualsave").show();
		}
		function vboReportChosenProvenience(naz) {
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
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-bplacecode")) {
						reportObj[nowindex]["bplacecode"] = c_code;
					} else {
						reportObj[nowindex]["rplacecode"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-provenience").val("").select2("data", null, false);
			jQuery(".vbo-report-otrs-manualsave").show();
		}
		function vboReportChosenCountry(naz) {
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
					reportObj[nowindex]["nationality"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-country").val("").select2("data", null, false);
			jQuery(".vbo-report-otrs-manualsave").show();
		}
		// download function
		function vboDownloadRecordOtrs(type, report_type) {
			if (!confirm("Sei sicuro di aver compilato tutte le informazioni necessarie?")) {
				return false;
			}

			let use_blank = true;
			if (typeof type === "undefined") {
				type = 1;
			} else {
				use_blank = false;
			}

			if (typeof report_type !== "undefined" && report_type) {
				jQuery(\'#adminForm\').find(\'select[name="type"]\').val(report_type).trigger(\'change\');
			}

			if (use_blank) {
				document.adminForm.target = "_blank";
				document.adminForm.action += "&tmpl=component";
			}

			vboSetFilters({exportreport: type, filler: JSON.stringify(reportObj)}, true);

			setTimeout(function() {
				document.adminForm.target = "";
				document.adminForm.action = document.adminForm.action.replace("&tmpl=component", "");
				vboSetFilters({exportreport: "0", filler: ""}, false);
			}, 1000);
		}
		// save data function
		function vboOtrsSaveData() {
			jQuery("button.vbo-report-otrs-manualsave").find("i").attr("class", vbo_otrs_saving_icn);
			VBOCore.doAjax(
				vbo_otrs_ajax_uri,
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
					jQuery("button.vbo-report-otrs-manualsave").addClass("btn-success").find("i").attr("class", vbo_otrs_saved_icn);
				},
				function(error) {
					alert(error.responseText);
					jQuery("button.vbo-report-otrs-manualsave").removeClass("btn-success").find("i").attr("class", vbo_otrs_save_icn);
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
		if ($this->getError()) {
			// export functions may set errors rather than exiting the process, and
			// the View may continue the execution to attempt to render the report.
			return false;
		}

		if ($this->rows) {
			// method must have run already
			return true;
		}

		$dbo = JFactory::getDbo();

		// load report settings
		$settings = $this->loadSettings();

		// get the possibly injected report options
		$options = $this->getReportOptions();

		// injected options will replace request variables, if any
		$opt_fromdate = $options->get('fromdate', '');
		$opt_type     = $options->get('type', '');

		// input fields and other vars
		$pfromdate = $opt_fromdate ?: VikRequest::getString('fromdate', '', 'request');
		$ptype = $opt_type ?: VikRequest::getString('type', 'checkin', 'request');

		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';
		$plistings = ((array) VikRequest::getVar('listings', array())) ?: ((array) $options->get('listings', []));
		$plistings = array_filter(array_map('intval', $plistings));

		$currency_symb = VikBooking::getCurrencySymb();
		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();

		// get translator
		$vbo_tn = VikBooking::getTranslator();

		// load all countries
		$all_countries = VikBooking::getCountriesArray();

		// load all rooms
		$all_rooms = VikBooking::getAvailabilityInstance(true)->loadRooms();

		// get date timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($pfromdate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts) || empty($to_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}

		// set the dates being exported
		$this->exported_checkin_dates = [
			date('Y-m-d', $from_ts),
			date('Y-m-d', $to_ts),
		];

		// query to obtain the records (all reservations or stays within the dates filter)
		$q = $dbo->getQuery(true)
			->select([
				$dbo->qn('o.id'),
				$dbo->qn('o.custdata'),
				$dbo->qn('o.ts'),
				$dbo->qn('o.days'),
				$dbo->qn('o.status'),
				$dbo->qn('o.checkin'),
				$dbo->qn('o.checkout'),
				$dbo->qn('o.custmail'),
				$dbo->qn('o.totpaid'),
				$dbo->qn('o.roomsnum'),
				$dbo->qn('o.total'),
				$dbo->qn('o.idorderota'),
				$dbo->qn('o.channel'),
				$dbo->qn('o.country'),
				$dbo->qn('or.idorder'),
				$dbo->qn('or.idroom'),
				$dbo->qn('or.adults'),
				$dbo->qn('or.children'),
				$dbo->qn('or.childrenage'),
				$dbo->qn('or.t_first_name'),
				$dbo->qn('or.t_last_name'),
				$dbo->qn('or.cust_cost'),
				$dbo->qn('or.cust_idiva'),
				$dbo->qn('or.extracosts'),
				$dbo->qn('or.room_cost'),
				$dbo->qn('co.idcustomer'),
				$dbo->qn('co.pax_data'),
				$dbo->qn('c.first_name'),
				$dbo->qn('c.last_name'),
				$dbo->qn('c.country', 'customer_country'),
				$dbo->qn('c.address'),
				$dbo->qn('c.city'),
				$dbo->qn('c.state'),
				$dbo->qn('c.doctype'),
				$dbo->qn('c.docnum'),
				$dbo->qn('c.gender'),
				$dbo->qn('c.bdate'),
				$dbo->qn('c.pbirth'),
			])
			->from($dbo->qn('#__vikbooking_orders', 'o'))
			->leftJoin($dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $dbo->qn('or.idorder') . ' = ' . $dbo->qn('o.id'))
			->leftJoin($dbo->qn('#__vikbooking_customers_orders', 'co') . ' ON ' . $dbo->qn('co.idorder') . ' = ' . $dbo->qn('o.id'))
			->leftJoin($dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $dbo->qn('c.id') . ' = ' . $dbo->qn('co.idcustomer'))
			->where($dbo->qn('o.closure') . ' = 0')
			->where($dbo->qn('o.status') . ' = ' . $dbo->q('confirmed'))
			->order($dbo->qn('o.checkin') . ' ASC')
			->order($dbo->qn('o.id') . ' ASC')
			->order($dbo->qn('or.id') . ' ASC');

		if ($plistings) {
			$q->where($this->dbo->qn('or.idroom') . ' IN (' . implode(', ', $plistings) . ')');
		}

		if ($ptype === 'checkin') {
			// get all the arrivals within the range of dates (single date)
			$q->where('(' . $dbo->qn('o.checkin') . ' BETWEEN ' . $from_ts . ' AND ' . $to_ts . ')');
		} else {
			// get all the departures within the range of dates (single date)
			$q->where('(' . $dbo->qn('o.checkout') . ' BETWEEN ' . $from_ts . ' AND ' . $to_ts . ')');
		}

		$dbo->setQuery($q);
		$records = $dbo->loadAssocList();

		if (!$records) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			$this->setError('Nessuna prenotazione trovata nelle date selezionate.');
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
		$this->cols = [
			// id booking
			[
				'key' => 'idbooking',
				'label' => 'ID',
			],
			// id guest
			[
				'key' => 'idguest',
				'label' => 'ID Guest',
				// hide this field in the View
				'ignore_view' => 1,
			],
			// guest type
			[
				'key' => 'guest_type',
				'label' => 'Tipo',
			],
			// first name
			[
				'key' => 'first_name',
				'label' => JText::translate('VBTRAVELERNAME'),
			],
			// last name
			[
				'key' => 'last_name',
				'label' => JText::translate('VBTRAVELERLNAME'),
			],
			// gender
			[
				'key' => 'gender',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBCUSTOMERGENDER'),
			],
			// guest age
			[
				'key' => 'guest_age',
				'attr' => [
					'class="center"',
				],
				'label' => 'Età',
			],
			// email
			[
				'key' => 'email',
				'label' => 'eMail',
				// hide this field in the View
				'ignore_view' => 1,
			],
			// camera
			[
				'key' => 'idroom',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBEDITORDERTHREE'),
			],
			// check-in
			[
				'key' => 'checkin',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBPICKUPAT'),
			],
			// check-out
			[
				'key' => 'checkout',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBRELEASEAT'),
			],
			// is check-in
			[
				'key' => 'is_checkin',
				'label' => 'is check-in',
				// hide this field in the View
				'ignore_view' => 1,
			],
			// is check-out
			[
				'key' => 'is_checkout',
				'label' => 'is check-out',
				// hide this field in the View
				'ignore_view' => 1,
			],
			// nationality
			[
				'key' => 'nationality',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBOCUSTNATIONALITY'),
			],
			// birth place code
			[
				'key' => 'bplacecode',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBCUSTOMERPBIRTH'),
			],
			// residence place code
			[
				'key' => 'rplacecode',
				'attr' => [
					'class="center"',
				],
				'label' => 'Residenza',
			],
		];

		// line number (to facilitate identifying a specific guest in case of errors with the file submission)
		$line_number = 0;

		// loop over the bookings to build the rows of the report
		$from_info = getdate($from_ts);
		$today_ymd = date('Y-m-d', $from_ts);
		foreach ($bookings as $gbook) {
			// count the total number of guests and adults for all rooms of this booking
			$tot_booking_guests = 0;
			$tot_booking_adults = 0;
			$room_guests = [];
			foreach ($gbook as $rbook) {
				$tot_booking_guests += ($rbook['adults'] + $rbook['children']);
				$tot_booking_adults += $rbook['adults'];
				$room_guests[] = ($rbook['adults'] + $rbook['children']);
			}

			// make sure to decode the current pax data
			if (!empty($gbook[0]['pax_data'])) {
				$gbook[0]['pax_data'] = (array) json_decode($gbook[0]['pax_data'], true);
			}

			// whether today is the check-in day, the check-out day (otherwise a stay)
			$is_checkin = $today_ymd == date('Y-m-d', $gbook[0]['checkin']);
			$is_checkout = $today_ymd == date('Y-m-d', $gbook[0]['checkout']);

			// push a copy of the booking for each guest
			$guests_rows = [];
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
				$insert_row = [];

				// find the actual guest-room-index
				$guest_room_ind = $this->calcGuestRoomIndex($room_guests, $guest_ind);

				// determine the type of guest, either automatically or from the check-in pax data
				$use_tipo = $ind > 0 && $tipo == 17 ? 19 : $tipo;
				$pax_guest_type = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'guest_type');
				$use_tipo = !empty($pax_guest_type) ? $pax_guest_type : $use_tipo;

				// id booking
				array_push($insert_row, [
					'key' => 'idbooking',
					'callback' => function($val) {
						// make sure to keep the data-bid attribute as it's used by JS to identify the booking ID
						return '<a data-bid="' . $val . '" href="index.php?option=com_vikbooking&task=editorder&cid[]=' . $val . '" target="_blank"><i class="' . VikBookingIcons::i('external-link') . '"></i> ' . $val . '</a>';
					},
					'no_export_callback' => 1,
					'value' => $guests['id'],
				]);

				// id guest
				array_push($insert_row, [
					'key' => 'idguest',
					'value' => sprintf('%d-%d', $guests['id'], $guest_ind),
					'ignore_view' => 1,
				]);

				// guest type
				array_push($insert_row, array(
					'key' => 'guest_type',
					'callback' => function($val) {
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
					'value' => $use_tipo,
				));

				// nome
				$nome = !empty($guests['t_first_name']) ? $guests['t_first_name'] : $guests['first_name'];
				$pax_nome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'first_name');
				$nome = !empty($pax_nome) ? $pax_nome : $nome;
				array_push($insert_row, [
					'key' => 'first_name',
					'value' => $nome,
				]);

				// cognome
				$cognome = !empty($guests['t_last_name']) ? $guests['t_last_name'] : $guests['last_name'];
				$pax_cognome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'last_name');
				$cognome = !empty($pax_cognome) ? $pax_cognome : $cognome;
				array_push($insert_row, [
					'key' => 'last_name',
					'value' => $cognome,
				]);

				// gender
				$gender = !empty($guests['gender']) && $guest_ind < 2 ? strtoupper($guests['gender']) : '';
				$pax_gender = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'gender');
				$gender = !empty($pax_gender) ? $pax_gender : $gender;
				/**
				 * We make sure the gender will be compatible with both back-end and front-end
				 * check-in/registration data collection driver and processes.
				 */
				if (is_numeric($gender)) {
					$gender = (int) $gender;
				} elseif (!strcasecmp($gender, 'F')) {
					$gender = 2;
				} elseif (!strcasecmp($gender, 'M')) {
					$gender = 1;
				}
				array_push($insert_row, [
					'key' => 'gender',
					'attr' => [
						'class="center' . (empty($gender) ? ' vbo-report-load-sesso' : '') . '"',
					],
					'callback' => function($val) {
						return $val == 2 ? 'F' : ($val === 1 ? 'M' : '?');
					},
					'no_export_callback' => 1,
					'value' => $gender,
				]);

				// guest age
				$default_age = (int) ($settings['defaultage'] ?? 30);
				$guest_age = intval($this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'guest_age') ?: 0);
				$guest_is_child = false;
				if (!$guest_age && !empty($guests['childrenage']) && $guest_ind > $guests['adults']) {
					// turn flag on
					$guest_is_child = true;
					// find the guest index for this child (zero-based)
					$child_guest_ind = $guest_ind - $guests['adults'] - 1;
					$child_age_list = !is_array($guests['childrenage']) ? (array) json_decode($guests['childrenage'], true) : $guests['childrenage'];
					// set the child age, if available
					$guest_age = intval($child_age_list['age'][$child_guest_ind] ?? $guest_age);
				}
				if (!$guest_is_child && !$guest_age) {
					// calculate the age from the birth date of the main customer/guest
					if (!empty($guests['bdate']) && $guest_ind < 2) {
						$dbirth = VikBooking::getDateTimestamp($guests['bdate'], 0, 0);
					} else {
						$dbirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'date_birth');
						$dbirth = $dbirth ? VikBooking::getDateTimestamp($dbirth, 0, 0) : null;
					}
					if ($dbirth && is_numeric($dbirth)) {
						// calculate the years between today and the birth date
						$dt_dbirth = new DateTime(date('Y-m-d', $dbirth));
						$dt_today = new DateTime(date('Y-m-d'));
						$dates_diff = $dt_dbirth->diff($dt_today);
						$guest_age = (int) $dates_diff->y;
					}
				}

				if (empty($guest_age)) {
					// optional selection style
					$guest_age_elem_class = ' vbo-report-load-guestage vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$guest_age_elem_class = ' vbo-report-load-guestage vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, [
					'key' => 'guest_age',
					'attr' => [
						'class="center' . $guest_age_elem_class . '"',
					],
					'callback' => function($val) use ($default_age, $guest_is_child) {
						if ($val && is_numeric($val)) {
							return intval($val);
						}
						if ($guest_is_child) {
							return 5;
						}
						return max(18, $default_age);
					},
					'value' => $guest_age,
				]);

				// email (not required)
				array_push($insert_row, [
					'key' => 'email',
					'value' => ($guest_ind < 2 ? (string) $guests['custmail'] : ''),
					'ignore_view' => 1,
				]);

				// camera
				array_push($insert_row, [
					'key' => 'idroom',
					'attr' => [
						'class="center"',
					],
					'callback' => function($val) use ($all_rooms) {
						return $all_rooms[$val]['name'] ?? $val;
					},
					'no_export_callback' => 1,
					'value' => $guests['idroom'],
				]);

				// checkin
				array_push($insert_row, [
					'key' => 'checkin',
					'attr' => [
						'class="center"',
					],
					'callback' => function($val) {
						return date('Y-m-d', $val);
					},
					'no_export_callback' => 1,
					'value' => $guests['checkin'],
				]);

				// checkout
				array_push($insert_row, [
					'key' => 'checkout',
					'attr' => [
						'class="center"',
					],
					'callback' => function($val) {
						return date('Y-m-d', $val);
					},
					'no_export_callback' => 1,
					'value' => $guests['checkout'],
				]);

				// is checkin
				array_push($insert_row, [
					'key' => 'is_checkin',
					'value' => $is_checkin,
					'ignore_view' => 1,
				]);

				// is checkout
				array_push($insert_row, [
					'key' => 'is_checkout',
					'value' => $is_checkout,
					'ignore_view' => 1,
				]);

				// nationality
				$pax_country_c = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_c');
				$citizen = !empty($guests['country']) && $guest_ind < 2 ? $guests['country'] : '';
				$citizenval = '';
				if (!empty($citizen) && $guest_ind < 2) {
					$citizenval = $citizen;
				}

				// check nationality field from pre-checkin
				$pax_citizen = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'nationality');
				$citizen = $pax_citizen ?: $citizen;
				$citizen = $pax_country_c ?: $citizen;
				$citizenval = $pax_country_c ?: $citizen;

				$citizenval_elem_class = '';
				if (empty($citizenval)) {
					// optional selection style
					$citizenval_elem_class = ' vbo-report-load-country vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$citizenval_elem_class = ' vbo-report-load-country vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, [
					'key' => 'nationality',
					'attr' => [
						'class="center' . $citizenval_elem_class . '"'
					],
					'callback' => function($val) {
						return !empty($val) ? ($this->italianPoliceCountries[$val]['name'] ?? '?') : '?';
					},
					'no_export_callback' => 1,
					'value' => $citizenval ?: '',
				]);

				// access any possible country value
				$pax_country_list = [
					$this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country'),
					$this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_c'),
					$this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_b'),
					$guests['customer_country'],
					$guests['country'],
				];

				// filter empty countries
				$pax_country_list = array_values(array_filter($pax_country_list));

				// map italian police country codes to 3-char codes
				$pax_country_list = array_map(function($country_val) {
					if ($this->italianPoliceCountries[$country_val]['three_code'] ?? null) {
						return $this->italianPoliceCountries[$country_val]['three_code'];
					}
					return $country_val;
				}, $pax_country_list);

				// birth place code (codice luogo nascita, codice comune Italiano o nome nazione estera)
				$bplacecode = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'bplacecode') ?: ($guests['bplacecode'] ?? '') ?: '';
				$bplacecode = $bplacecode ?: $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'province_b');
				$bplacecode = (string) ($bplacecode == 'ES' ? '' : $bplacecode);

				if (!$bplacecode && $pax_country_list && stripos($pax_country_list[0], 'ITA') === false) {
					// for non-italian guests we can try to guess the ISTAT foreign country code
					$foreign_matches = [];

					// determine the value to match
					$match_name = $pax_country_list[0];
					$country_data = VBOStateHelper::getCountryData((int) VBOStateHelper::getCountryId($pax_country_list[0]));
					if ($country_data) {
						// try to forse the record conversion into Italian for a more accurate matching of the foreign country
						$country_data = $vbo_tn->translateRecord($country_data, '#__vikbooking_countries', 'it-IT');

						// assign the country name to match
						$match_name = $country_data['country_name'];
					}

					// calculate the similarity for each country AlloggiatiWeb code
					foreach ($this->italianPoliceCountries as $fc_code => $itpc) {
						// get the italian police country name
						$fc_name = $itpc['name'];

						// calculate similarity
						similar_text(strtolower($fc_name), strtolower($match_name), $similarity);

						// assign similarity to country AlloggiatiWeb code
						$foreign_matches[$fc_code] = $similarity;
					}

					// sort similarity in descending order
					arsort($foreign_matches);

					// assign the first match found, the most similar one
					foreach ($foreign_matches as $fc_code => $similarity_score) {
						// we trust the first match to be valid
						$bplacecode = $fc_code;
						break;
					}
				}

				$bplacecode_elem_class = '';
				if (empty($bplacecode)) {
					// optional selection style
					$bplacecode_elem_class = ' vbo-report-load-bplacecode vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$bplacecode_elem_class = ' vbo-report-load-bplacecode vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, [
					'key' => 'bplacecode',
					'attr' => [
						'class="center' . $bplacecode_elem_class . '"',
					],
					'callback' => function($val) {
						return $this->italianPoliceCountries[$val]['name'] ?? $this->italianPoliceComuni[$val]['name'] ?? $val ?: '?';
					},
					'callback_export' => function($val) {
						if (empty($val)) {
							return 'MISSING';
						}
						if (is_numeric($val) && (isset($this->italianPoliceCountries[$val]) || isset($this->italianPoliceComuni[$val]))) {
							// we have a correct 9-char code from the Italian Police list as required
							return $val;
						}
						// we may have a province code (i.e. "TP" or "Trapani") that must be matched against a 9-char code
						foreach ($this->italianPoliceCountries as $code => $policecountry) {
							if (!strcasecmp($policecountry['name'], $val)) {
								// match found
								return $code;
							}
						}
						foreach ($this->italianPoliceComuni as $code => $comune) {
							if (!strcasecmp($comune['province'], $val) || !strcasecmp($comune['name'], $val)) {
								// match found
								return $code;
							}
						}
						return strlen((string) $val) == 9 ? $val : 'MISSING';
					},
					'value' => $bplacecode,
				]);

				// residence place code (codice luogo residenza, codice comune Italiano o nome nazione estera)
				$rplacecode = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'rplacecode') ?: ($guests['rplacecode'] ?? '') ?: '';
				$rplacecode = $rplacecode ?: $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'province_s');
				$rplacecode = (string) ($rplacecode == 'ES' ? '' : $rplacecode);

				if (!$rplacecode && $pax_country_list && stripos($pax_country_list[0], 'ITA') === false) {
					// for non-italian guests we can try to guess the ISTAT foreign country code
					$foreign_matches = [];

					// determine the value to match
					$match_name = $pax_country_list[0];
					$country_data = VBOStateHelper::getCountryData((int) VBOStateHelper::getCountryId($pax_country_list[0]));
					if ($country_data) {
						// try to forse the record conversion into Italian for a more accurate matching of the foreign country
						$country_data = $vbo_tn->translateRecord($country_data, '#__vikbooking_countries', 'it-IT');

						// assign the country name to match
						$match_name = $country_data['country_name'];
					}

					// calculate the similarity for each country AlloggiatiWeb code
					foreach ($this->italianPoliceCountries as $fc_code => $itpc) {
						// get the italian police country name
						$fc_name = $itpc['name'];

						// calculate similarity
						similar_text(strtolower($fc_name), strtolower($match_name), $similarity);

						// assign similarity to country AlloggiatiWeb code
						$foreign_matches[$fc_code] = $similarity;
					}

					// sort similarity in descending order
					arsort($foreign_matches);

					// assign the first match found, the most similar one
					foreach ($foreign_matches as $fc_code => $similarity_score) {
						// we trust the first match to be valid
						$rplacecode = $fc_code;
						break;
					}
				}

				$rplacecode_elem_class = '';
				if (empty($rplacecode)) {
					// optional selection style
					$rplacecode_elem_class = ' vbo-report-load-rplacecode vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$rplacecode_elem_class = ' vbo-report-load-rplacecode vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, [
					'key' => 'rplacecode',
					'attr' => [
						'class="center' . $rplacecode_elem_class . '"',
					],
					'callback' => function($val) {
						return $this->italianPoliceCountries[$val]['name'] ?? $this->italianPoliceComuni[$val]['name'] ?? $val ?: '?';
					},
					'callback_export' => function($val) {
						if (empty($val)) {
							return 'MISSING';
						}
						if (is_numeric($val) && (isset($this->italianPoliceCountries[$val]) || isset($this->italianPoliceComuni[$val]))) {
							// we have a correct 9-char code from the Italian Police list as required
							return $val;
						}
						// we may have a province code (i.e. "TP" or "Trapani") that must be matched against a 9-char code
						foreach ($this->italianPoliceCountries as $code => $policecountry) {
							if (!strcasecmp($policecountry['name'], $val)) {
								// match found
								return $code;
							}
						}
						foreach ($this->italianPoliceComuni as $code => $comune) {
							if (!strcasecmp($comune['province'], $val) || !strcasecmp($comune['name'], $val)) {
								// match found
								return $code;
							}
						}
						return strlen((string) $val) == 9 ? $val : 'MISSING';
					},
					'value' => $rplacecode,
				]);

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
		array_push($this->footerRow, [
			[
				'attr' => [
					'class="vbo-report-total"',
				],
				'value' => '<h3>' . JText::translate('VBOREPORTSTOTALROW') . '</h3>',
			],
			[
				'attr' => [
					'colspan="' . (count($this->cols) - 1) . '"',
				],
				'value' => count($this->rows),
			],
		]);

		return true;
	}

	/**
	 * Generates the authority file, then sends it to output for download.
	 * In case of errors, the process is not terminated (exit) to let the View display the
	 * error message(s). The export type argument can eventually determine an action to run.
	 *
	 * @param 	string 	$export_type 	Differentiates the type of export requested.
	 *
	 * @return 	void|bool 				Void in case of script termination, boolean otherwise.
	 */
	public function customExport($export_type = null)
	{
		// build the XML file
		$xml = $this->buildXMLFile();

		// build report action data, if needed
		$action_data = array_merge($this->getActionData($registry = false), ['xml' => $xml]);

		/**
		 * Custom export method can run a custom action.
		 */
		if ($export_type && !is_numeric($export_type)) {
			try {
				// ensure the type of export is a callable scoped action, hidden or visible
				$actions = $this->getScopedActions($this->getScope(), $visible = false);
				$action_ids = array_column($actions, 'id');
				$action_names = array_column($actions, 'name');
				if (!in_array($export_type, $action_ids)) {
					throw new Exception(sprintf('Cannot invoke the requested type of export [%s].', $export_type), 403);
				}

				// get the requested action readable name
				$action_name = $action_names[array_search($export_type, $action_ids)];

				if ($this->getScope() === 'web') {
					// run the action and output the HTML response string
					$html_result = $this->_callActionReturn($export_type, 'html', $this->getScope(), $action_data);

					// build the action result data object
					$js_result = json_encode([
						'actionName' => $action_name,
						'actionHtml' => $html_result,
					]);

					// render modal script with the action result
					JFactory::getDocument()->addScriptDeclaration(
<<<JS
;(function($) {
	$(function() {
		let result = $js_result;
		VBOCore.displayModal({
			suffix:      'report-custom-scopedaction-result',
			extra_class: 'vbo-modal-rounded vbo-modal-tall vbo-modal-nofooter',
			title:       result.actionName,
			body:        result.actionHtml,
		});
	});
})(jQuery);
JS
					);

					// abort and let the View render the result within a modal
					return;
				}

				// let the report custom action run and return a boolean value if invoked by a cron
				return (bool) $this->_callActionReturn($export_type, 'success', $this->getScope(), $action_data);

			} catch (Exception $e) {
				// silently catch the error and set it
				$this->setError(sprintf('(%s) %s', $e->getCode(), $e->getMessage()));

				// abort
				return false;
			}
		}

		// proceed with the regular export function (write on file through cron or download file through web)

		if (!$xml) {
			// abort
			return false;
		}

		// update history for all bookings affected before exporting
		foreach ($this->export_booking_ids as $bid) {
			VikBooking::getBookingHistoryInstance($bid)->store('RP', $this->reportName . ' - Export');
		}

		// custom export method supports a custom export handler, if previously set.
		if ($this->hasExportHandler()) {
			// write data onto the custom file handler
			$fp = $this->getExportCSVHandler();
			fwrite($fp, $xml);
			fclose($fp);

			// return true as data was written
			return true;
		}

		// force text file download in case of regular export
		header("Content-type: text/xml");
		header("Cache-Control: no-store, no-cache");
		header('Content-Disposition: attachment; filename="' . $this->getExportCSVFileName() . '"');
		echo $xml;

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
			// count guests per room (adults + children)
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
	 * Parses the file Comuni.csv and returns two associative
	 * arrays: one for the Comuni and one for the Province.
	 * Every line of the CSV is composed of: Codice, Comune, Provincia.
	 *
	 * @return 	array
	 */
	protected function loadItalianPoliceComuni()
	{
		$comuni = [];

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

			$comune_id = trim($v[0]);
			$comune_name = trim($v[1]);
			$comune_prov = trim($v[2]);

			if (!isset($comprov_codes['comuni'][$v[0]])) {
				$comprov_codes['comuni'][$v[0]] = [];
			}

			$comuni[$comune_id]['name'] = $comune_name;
			$comuni[$comune_id]['province'] = $comune_prov;
		}

		return $comuni;
	}

	/**
	 * Parses the file Nazioni.csv and returns an associative
	 * array with the code and name of the Nazione.
	 * Every line of the CSV is composed of: Codice, Nazione.
	 *
	 * @return 	array
	 */
	protected function loadItalianPoliceCountries()
	{
		$nazioni = [];

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

			$country_id = trim($v[0]);
			$country_name = trim($v[1]);
			$country_code = trim($v[2]);

			$nazioni[$country_id]['name'] = $country_name;
			$nazioni[$country_id]['three_code'] = $country_code;
		}

		return $nazioni;
	}

	/**
	 * Builds the XML file for export or transmission.
	 * 
	 * @param 	string 	$type 	The XML export type (checkin or reservation).
	 * 
	 * @return 	string 			Empty string in case of errors, XML otherwise.
	 */
	protected function buildXMLFile($type = '')
	{
		if (!$this->getReportData()) {
			return '';
		}

		// load report settings
		$settings = $this->loadSettings();

		// get the possibly injected report options
		$options = $this->getReportOptions();

		// injected options will substitute request variables, if any
		$export_type = $type ?: VikRequest::getString('type', $options->get('type', 'checkin'), 'request');

		// access manually filled values, if any
		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? (array) json_decode($pfiller, true) : [];

		// start building the XML
		$xml = simplexml_load_string('<StaysPmsDTO></StaysPmsDTO>');

		// group all rows by booking id
		$booking_rows = [];
		foreach ($this->rows as $ind => $row) {
			$bid = 0;
			foreach ($row as $row_ind => $field) {
				if ($field['key'] == 'idbooking') {
					// set booking ID
					$bid = (int) $field['value'];

					if (!isset($booking_rows[$bid])) {
						// start booking rows container
						$booking_rows[$bid] = [];
					}

					// check for manual report value
					if (strlen((string) ($pfiller[$ind][$field['key']] ?? ''))) {
						$row[$row_ind]['value'] = $pfiller[$ind][$field['key']];
					}

					// break this one loop
					break;
				}
			}
			// push booking row
			$booking_rows[$bid][] = $row;
		}

		// scan all booking rows
		foreach ($booking_rows as $bid => $rows) {
			// add <Stay> node for this reservation
			$stay = $xml->addChild('Stay');

			// add stay details
			$stay->addChild('StayId', date('Y') . '-' . $bid);
			$stay->addChild('HotelCode', htmlspecialchars($settings['hotelcode'] ?? ''));

			// add <Guests> node for the current booking
			$guests = $stay->addChild('Guests');

			// scan all guest rows for this reservation to build the various <persona> nodes
			foreach ($rows as $guest_ind => $row) {
				// get the associative list of guest data from the current row
				$row_data = $this->getAssocRowData($row);

				// add <Guest> node for this guest
				$guest = $guests->addChild('Guest');

				// build guest nodes
				$guest->addChild('GuestId', $bid . '-' . ($guest_ind + 1));
				$guest->addChild('Age', $row_data['guest_age']);
				$guest->addChild('NationalityCode', $row_data['nationality']);
				$guest->addChild('BirthPlaceCode', $row_data['bplacecode']);
				$guest->addChild('ResidencePlaceCode', $row_data['rplacecode']);
				$guest->addChild('Type', $row_data['guest_type']);
				$guest->addChild('Gender', $row_data['gender']);

				if (!empty($row_data['email'])) {
					// this is the only optional field per guest
					$guest->addChild('EMail', htmlspecialchars($row_data['email']));
				}

				// date timestamps to UTC date objects
				$in_ts = $row_data['checkin'];
				$in_dt = new DateTime(date('Y-m-d H:i:s', $in_ts));
				$in_dt->setTimezone(new DateTimeZone('UTC'));
				$checkin_utc = $in_dt->format('Y-m-d\TH:i:s.v\Z');
				$out_ts = $row_data['checkout'];
				$out_dt = new DateTime(date('Y-m-d H:i:s', $out_ts));
				$out_dt->setTimezone(new DateTimeZone('UTC'));
				$checkout_utc = $out_dt->format('Y-m-d\TH:i:s.v\Z');

				$guest->addChild('ArrivalDate', $checkin_utc);
				$guest->addChild('DepartureDate', $checkout_utc);
				$guest->addChild('Checkout', ($export_type != 'checkin' ? 'true' : 'false'));
				$guest->addChild('BedOccupancy', 'true');

				// rooms
				$roomsNode = $guest->addChild('Rooms');
				$roomNode = $roomsNode->addChild('Room');
				$roomNode->addChild('RoomId', $row_data['idroom']);
				$roomNode->addChild('StartDate', $checkin_utc);
				$roomNode->addChild('EndDate', $checkout_utc);
			}
		}

		// set pool of booking IDs to update their history
		$this->export_booking_ids = array_keys($booking_rows);

		// get the formatted XML file string
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml->asXml());
        $dom->formatOutput = true;
		$formatted_xml = $dom->saveXML();

		// return the final XML file string
		return $formatted_xml;
	}

	/**
	 * Custom scoped action to test the login and logout operations.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 */
	protected function testLoginLogout($scope = null, array $data = [])
	{
		// prepare the response properties
		$html   = '';
		$error  = '';
		$token  = '';
		$logout = false;

		try {
			// get the (login) token to perform the request
			$token = $this->_callActionReturn('otrsLogin', 'token', $scope, $data);

			// log out
			$logout = $this->_callActionReturn('otrsLogout', 'success', $scope, array_merge($data, ['token' => $token]));
		} catch (Exception $e) {
			$error = $e->getMessage();
		}

		// build HTML response string
		$html .= '<p class="' . ($error ? 'err' : 'successmade') . '">' . ($error ? 'Errore' : 'Successo!') . '</p>';
		$html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
		$html .= '	<div class="vbo-params-wrap">';
		$html .= '		<div class="vbo-params-container">';
		$html .= '			<div class="vbo-params-block">';
		$html .= '				<div class="vbo-param-container">';
		$html .= '					<div class="vbo-param-label">Risposta</div>';
		$html .= '					<div class="vbo-param-setting">' . ($error ?: $token) . '</div>';
		$html .= '				</div>';

		if (!$error) {
			$html .= '			<div class="vbo-param-container">';
			$html .= '				<div class="vbo-param-label">Logout</div>';
			$html .= '				<div class="vbo-param-setting">' . ($logout ? 'Eseguito' : 'Non eseguito') . '</div>';
			$html .= '			</div>';
		}

		$html .= '			</div>';
		$html .= '		</div>';
		$html .= '	</div>';
		$html .= '</div>';

		return [
			'html'  => $html,
			'token' => $token,
		];
	}

	/**
	 * Custom scoped action to obtain a (login) token for executing the requests.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function otrsLogin($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['hotelcode']) || empty($settings['userid']) || empty($settings['password'])) {
			throw new Exception(sprintf('[%s] errore: impostazioni account mancanti.', __METHOD__), 500);
		}

		// prepare the response properties
		$html   = '';
		$token  = '';

		// start HTTP transporter
		$http = new JHttp;

		// build connection request headers
		$headers = [
			'UserId'   => $settings['userid'],
			'Password' => $settings['password'],
		];

		// make GET request to the specified URL
		$response = $http->get('https://osservatorioturistico.regione.sicilia.it/webapi/api/auth/login', $headers);

		if ($response->code != 200) {
			// invalid response, raise an error
			throw new Exception(strip_tags((string) $response->body), $response->code);
		}

		// trim quote characters
		$token = trim($response->body, '"');

		// build HTML response string
		$html .= '<p class="successmade">Successo!</p>';
		$html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
		$html .= '	<div class="vbo-params-wrap">';
		$html .= '		<div class="vbo-params-container">';
		$html .= '			<div class="vbo-params-block">';
		$html .= '				<div class="vbo-param-container">';
		$html .= '					<div class="vbo-param-label">Token</div>';
		$html .= '					<div class="vbo-param-setting">' . $token . '</div>';
		$html .= '				</div>';
		$html .= '			</div>';
		$html .= '		</div>';
		$html .= '	</div>';
		$html .= '</div>';

		return [
			'html'  => $html,
			'token' => $token,
		];
	}

	/**
	 * Custom scoped action to perform the logout action from the web-service after the login.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 */
	protected function otrsLogout($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		// prepare the response properties
		$html   = '';
		$token  = '';

		// start HTTP transporter
		$http = new JHttp;

		// build connection request headers
		$headers = [
			'UserId'        => $settings['userid'] ?? '',
			'Authorization' => $data['token'] ?? '',
		];

		// make POST request to the specified URL
		$response = $http->post('https://osservatorioturistico.regione.sicilia.it/webapi/api/auth/logout', $headers, $headers);

		// build HTML response string
		$html .= '<p class="info">Operazione eseguita</p>';
		$html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
		$html .= '	<div class="vbo-params-wrap">';
		$html .= '		<div class="vbo-params-container">';
		$html .= '			<div class="vbo-params-block">';
		$html .= '				<div class="vbo-param-container">';
		$html .= '					<div class="vbo-param-label">Codice risposta</div>';
		$html .= '					<div class="vbo-param-setting">' . $response->code . '</div>';
		$html .= '				</div>';
		$html .= '				<div class="vbo-param-container">';
		$html .= '					<div class="vbo-param-label">Messaggio</div>';
		$html .= '					<div class="vbo-param-setting">' . $response->body . '</div>';
		$html .= '				</div>';
		$html .= '			</div>';
		$html .= '		</div>';
		$html .= '	</div>';
		$html .= '</div>';

		return [
			'html'     => $html,
			'success'  => $response->code == 200,
			'response' => $response->body,
		];
	}

	/**
	 * Custom scoped action to transmit the check-in details.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function registerCheckins($scope = null, array $data = [])
	{
		if (!($data['xml'] ?? '') && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadRecordOtrs(\'registerCheckins\', \'checkin\');</script>',
			];
		}

		if (!($data['xml'] ?? '')) {
			// attempt to build the XML file if not set
			$data['xml'] = $this->buildXMLFile();
		}

		if (!$data['xml']) {
			throw new Exception('Empty XML request message.', 500);
		}

		// load report settings
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['hotelcode']) || empty($settings['userid']) || empty($settings['password'])) {
			throw new Exception(sprintf('[%s] errore: impostazioni web-service mancanti.', __METHOD__), 500);
		}

		try {
			// get the token to perform the request (log in)
			$token = $this->_callActionReturn('otrsLogin', 'token', $scope, $data);

			// start HTTP transporter
			$http = new JHttp;

			// build connection request headers
			$headers = [
				'Content-Type'  => 'text/xml',
				'Authorization' => $token,
			];

			// make POST request to the specified URL
			$response = $http->post('https://osservatorioturistico.regione.sicilia.it/webapi/api/stay/addfrompms', $data['xml'], $headers);

			if (empty($response->body)) {
				// invalid response, raise an error
				throw new Exception((strip_tags((string) $response->body)) ?: 'Invalid Response Body', $response->code ?: 500);
			}

			// log out
			$logout = $this->_callActionReturn('otrsLogout', 'success', $scope, array_merge($data, ['token' => $token]));

			// process the response (could be XML or JSON)
			$xmlResponse = simplexml_load_string($response->body);

			if ($response->code != 200) {
				// an error must have occurred
				if (!is_object($xmlResponse) || !isset($xmlResponse->ValidationResultDTO) || !isset($xmlResponse->ValidationResultDTO->IsValid)) {
					// attempt to obtain the error details in JSON format
					$json_error = json_decode($response->body);
					$json_error = is_array($json_error) && is_object($json_error[0] ?? null) ? $json_error[0] : $json_error;
					if (is_object($json_error) && isset($json_error->IsValid) && !$json_error->IsValid && ($json_error->Messages ?? [])) {
						$errors_list = [];
						foreach ($json_error->Messages as $message) {
							if (($message->Level ?? null) || ($message->Message ?? null)) {
								$errors_list[] = sprintf('(%s) %s', ($message->Level ?? ''), ($message->Message ?? null));
							}
						}
						if ($errors_list) {
							// raise error
							throw new Exception(implode("\n", $errors_list), $response->code ?: 500);
						}
					}
					// unexpected response format, raise an error
					throw new Exception(sprintf('Unexpected response format: %s', (strip_tags((string) $response->body) ?: '')), $response->code ?: 500);
				}

				// gather validation error messages
				$validation_errors = [];

				foreach ($xmlResponse->ValidationResultDTO as $validationResult) {
					if (!isset($validationResult->NestedValidation->ValidationResultDTO)) {
						continue;
					}
					foreach ($validationResult->NestedValidation->ValidationResultDTO as $nestedValidationResult) {
						$obj_type = (string) $nestedValidationResult->ObjectType;
						$obj_id = (string) $nestedValidationResult->ObjectId;
						$obj_valid = !strcasecmp((string) $nestedValidationResult->IsValid, 'true');
						if ($obj_valid) {
							// no errors returned
							continue;
						}
						if (!isset($nestedValidationResult->Messages->ValidationMessageDTO)) {
							// no validation messages found
							continue;
						}
						foreach ($nestedValidationResult->Messages->ValidationMessageDTO as $validationMessage) {
							// gather error details
							$err_level = (string) $validationMessage->Level;
							$err_message = (string) $validationMessage->Message;
							$err_field_name = (string) ($validationMessage->FieldName ?? '');
							$err_field_value = (string) ($validationMessage->FieldValue ?? '');

							// push validation error message
							$validation_errors[] = sprintf('Oggetto %s (ID %s): [%s] %s - Campo %s (%s). ', $obj_type, $obj_id, $err_level, $err_message, $err_field_name, $err_field_value);
						}
					}
				}

				// errors expected
				if ($validation_errors) {
					// raise the errors collected
					throw new Exception(implode("\n", $validation_errors), $response->code ?: 500);
				}
				// raise a generic error
				throw new Exception(sprintf('Risposta di errore dal sistema: %s', (string) htmlentities($response->body)), $response->code ?: 500);
			}

			// build HTML response string
			$html = '';
			$html .= '<p class="successmade">Trasmissione e validazione dei dati di check-in eseguite con successo.</p>';
			$html .= '<p class="info">Risposta del sistema</p>';
			$html .= '<pre><code>' . htmlentities((string) $response->body) . '</code></pre>';

		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] errore: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// get the currently active profile ID, if any
		$activeProfileId = $this->getActiveProfile();

		/**
		 * When the report is executed through a cron-job, the transmission of the guest details
		 * will also dump the data transmitted onto a resource file, useful for sending it via email.
		 */
		if ($scope === 'cron') {
			// prepare the XML resource file information
			$xml_name = implode('_', array_filter([$this->getFileName(), 'gestione', 'arrivi', time(), $activeProfileId, rand(100, 999)])) . '.xml';
			$xml_dest = $this->getDataMediaPath() . DIRECTORY_SEPARATOR . $xml_name;
			$xml_url  = $this->getDataMediaUrl() . $xml_name;

			// store the XML bytes into a local file on disk
			$stored = JFile::write($xml_dest, $data['xml']);

			if ($stored && VBOPlatformDetection::isWordPress()) {
				/**
				 * Trigger files mirroring operation
				 */
				VikBookingLoader::import('update.manager');
				VikBookingUpdateManager::triggerUploadBackup($xml_dest);
			}

			if ($stored) {
				// define a new report resource for the generated file
				$this->defineResourceFile([
					'summary' => sprintf(
						'%sGestione Arrivi - Trasmissione e validazione dei dati eseguite con successo.',
						($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : '')
					),
					'url'  => $xml_url,
					'path' => $xml_dest,
				]);
			}
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.registercheckins.ok',
				'title'   => 'OTRS - Gestione Arrivi',
				'summary' => 'Trasmissione e validazione dei dati eseguite con successo.',
			];

			try {
				// store the notification record
				VBOFactory::getNotificationCenter()->store([$notification]);
			} catch (Exception $e) {
				// silently catch the error without doing anything
			}
		}

		return [
			'html'    => $html,
			'success' => true,
			'xml'     => $data['xml'],
		];
	}

	/**
	 * Custom scoped action to transmit the check-out details after previously submitted check-in details.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function registerCheckouts($scope = null, array $data = [])
	{
		if (!($data['xml'] ?? '') && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadRecordOtrs(\'registerCheckouts\', \'checkout\');</script>',
			];
		}

		if (!($data['xml'] ?? '')) {
			// attempt to build the XML file if not set
			$data['xml'] = $this->buildXMLFile();
		}

		if (!$data['xml']) {
			throw new Exception('Empty XML request message.', 500);
		}

		// load report settings
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['hotelcode']) || empty($settings['userid']) || empty($settings['password'])) {
			throw new Exception(sprintf('[%s] errore: impostazioni web-service mancanti.', __METHOD__), 500);
		}

		try {
			// get the token to perform the request (log in)
			$token = $this->_callActionReturn('otrsLogin', 'token', $scope, $data);

			// start HTTP transporter
			$http = new JHttp;

			// build connection request headers
			$headers = [
				'Content-Type'  => 'text/xml',
				'Authorization' => $token,
			];

			// make POST request to the specified URL
			$response = $http->post('https://osservatorioturistico.regione.sicilia.it/webapi/api/stay/updatefrompms', $data['xml'], $headers);

			if (empty($response->body)) {
				// invalid response, raise an error
				throw new Exception((strip_tags((string) $response->body)) ?: 'Invalid Response Body', $response->code ?: 500);
			}

			// log out
			$logout = $this->_callActionReturn('otrsLogout', 'success', $scope, array_merge($data, ['token' => $token]));

			// process the response (could be XML or JSON)
			$xmlResponse = simplexml_load_string($response->body);

			if ($response->code != 200) {
				// an error must have occurred
				if (!is_object($xmlResponse) || !isset($xmlResponse->ValidationResultDTO) || !isset($xmlResponse->ValidationResultDTO->IsValid)) {
					// attempt to obtain the error details in JSON format
					$json_error = json_decode($response->body);
					$json_error = is_array($json_error) && is_object($json_error[0] ?? null) ? $json_error[0] : $json_error;
					if (is_object($json_error) && isset($json_error->IsValid) && !$json_error->IsValid && ($json_error->Messages ?? [])) {
						$errors_list = [];
						foreach ($json_error->Messages as $message) {
							if (($message->Level ?? null) || ($message->Message ?? null)) {
								$errors_list[] = sprintf('(%s) %s', ($message->Level ?? ''), ($message->Message ?? null));
							}
						}
						if ($errors_list) {
							// raise error
							throw new Exception(implode("\n", $errors_list), $response->code ?: 500);
						}
					}
					// unexpected response format, raise an error
					throw new Exception(sprintf('Unexpected response format: %s', (strip_tags((string) $response->body) ?: '')), $response->code ?: 500);
				}

				// gather validation error messages
				$validation_errors = [];

				foreach ($xmlResponse->ValidationResultDTO as $validationResult) {
					if (!isset($validationResult->NestedValidation->ValidationResultDTO)) {
						continue;
					}
					foreach ($validationResult->NestedValidation->ValidationResultDTO as $nestedValidationResult) {
						$obj_type = (string) $nestedValidationResult->ObjectType;
						$obj_id = (string) $nestedValidationResult->ObjectId;
						$obj_valid = !strcasecmp((string) $nestedValidationResult->IsValid, 'true');
						if ($obj_valid) {
							// no errors returned
							continue;
						}
						if (!isset($nestedValidationResult->Messages->ValidationMessageDTO)) {
							// no validation messages found
							continue;
						}
						foreach ($nestedValidationResult->Messages->ValidationMessageDTO as $validationMessage) {
							// gather error details
							$err_level = (string) $validationMessage->Level;
							$err_message = (string) $validationMessage->Message;
							$err_field_name = (string) ($validationMessage->FieldName ?? '');
							$err_field_value = (string) ($validationMessage->FieldValue ?? '');

							// push validation error message
							$validation_errors[] = sprintf('Oggetto %s (ID %s): [%s] %s - Campo %s (%s). ', $obj_type, $obj_id, $err_level, $err_message, $err_field_name, $err_field_value);
						}
					}
				}

				// errors expected
				if ($validation_errors) {
					// raise the errors collected
					throw new Exception(implode("\n", $validation_errors), $response->code ?: 500);
				}
				// raise a generic error
				throw new Exception(sprintf('Risposta di errore dal sistema: %s', (string) htmlentities($response->body)), $response->code ?: 500);
			}

			// build HTML response string
			$html = '';
			$html .= '<p class="successmade">Trasmissione e validazione dei dati di check-out eseguite con successo.</p>';
			$html .= '<p class="info">Risposta del sistema</p>';
			$html .= '<pre><code>' . htmlentities((string) $response->body) . '</code></pre>';

		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] errore: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// get the currently active profile ID, if any
		$activeProfileId = $this->getActiveProfile();

		/**
		 * When the report is executed through a cron-job, the transmission of the guest details
		 * will also dump the data transmitted onto a resource file, useful for sending it via email.
		 */
		if ($scope === 'cron') {
			// prepare the XML resource file information
			$xml_name = implode('_', array_filter([$this->getFileName(), 'gestione', 'checkout', time(), $activeProfileId, rand(100, 999)])) . '.xml';
			$xml_dest = $this->getDataMediaPath() . DIRECTORY_SEPARATOR . $xml_name;
			$xml_url  = $this->getDataMediaUrl() . $xml_name;

			// store the XML bytes into a local file on disk
			$stored = JFile::write($xml_dest, $data['xml']);

			if ($stored && VBOPlatformDetection::isWordPress()) {
				/**
				 * Trigger files mirroring operation
				 */
				VikBookingLoader::import('update.manager');
				VikBookingUpdateManager::triggerUploadBackup($xml_dest);
			}

			if ($stored) {
				// define a new report resource for the generated file
				$this->defineResourceFile([
					'summary' => sprintf(
						'%sGestione Checkout - Trasmissione e validazione dei dati eseguite con successo.',
						($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : '')
					),
					'url'  => $xml_url,
					'path' => $xml_dest,
				]);
			}
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.registercheckouts.ok',
				'title'   => 'OTRS - Gestione Checkout',
				'summary' => 'Trasmissione e validazione dei dati eseguite con successo.',
			];

			try {
				// store the notification record
				VBOFactory::getNotificationCenter()->store([$notification]);
			} catch (Exception $e) {
				// silently catch the error without doing anything
			}
		}

		return [
			'html'    => $html,
			'success' => true,
			'xml'     => $data['xml'],
		];
	}

	/**
	 * Custom scoped action to transmit the XML message of type "end day". A date is required
	 * for sending an "end day" message, and the following formats are supported: {today}, today,
	 * {now}, now, Y-m-d, Y-m-d\TH:i:s, alternatively any valid Date and Time Format modifier,
	 * either with or without curly brackets, such as {-1 day}, {-14 days}, -1 day, -14 days.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function registerEndDay($scope = null, array $data = [])
	{
		if (!($data['end_date'] ?? '') && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadRecordOtrs(\'registerEndDay\', \'\');</script>',
			];
		}

		// ensure the end time is set
		$data['end_time'] = $data['end_time'] ?? '16:00';

		// load report settings
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['hotelcode']) || empty($settings['userid']) || empty($settings['password'])) {
			throw new Exception(sprintf('[%s] errore: impostazioni web-service mancanti.', __METHOD__), 500);
		}

		// build the date for ending the day
		$end_date = ($data['end_date'] ?? '') ?: date('Y-m-d');
		if ($end_date && preg_match("/^[0-9]+/", $end_date)) {
			// ensure the date received is in military format
			$end_date = date('Y-m-d', VikBooking::getDateTimestamp($end_date));
		}
		if (!$end_date || stripos((string) $end_date, 'now') !== false || stripos((string) $end_date, 'today') !== false) {
			// default to today's date
			$end_date = date('Y-m-d\TH:i:s');
		} elseif (stripos((string) $end_date, 'yesterday') !== false || preg_match("/[+-][0-9]+\s?days?/i", (string) $end_date)) {
			// calculate the requested date
			$end_date = trim(str_replace(['{', '}'], '', $end_date));
			$end_date = date('Y-m-d\TH:i:s', strtotime($end_date));
		}

		// get closing date in UTC format
		$day_ts = strtotime($end_date);
		$day_dt = new DateTime(date('Y-m-d ' . $data['end_time'] . ':00', $day_ts));
		$day_dt->setTimezone(new DateTimeZone('UTC'));
		$day_utc = $day_dt->format('Y-m-d\TH:i:s.000\Z');

		// build XML message
		$xml = simplexml_load_string('<EndDayPmsDTO></EndDayPmsDTO>');
		$xml->addChild('HotelCode', $settings['hotelcode']);
		$xml->addChild('CurrentDate', $day_utc);

		try {
			// get the token to perform the request (log in)
			$token = $this->_callActionReturn('otrsLogin', 'token', $scope, $data);

			// start HTTP transporter
			$http = new JHttp;

			// build connection request headers
			$headers = [
				'Content-Type'  => 'text/xml',
				'Authorization' => $token,
			];

			// make POST request to the specified URL
			$response = $http->post('https://osservatorioturistico.regione.sicilia.it/webapi/api/entity/enddayfrompms', $xml->asXML(), $headers);

			// log out
			$logout = $this->_callActionReturn('otrsLogout', 'success', $scope, array_merge($data, ['token' => $token]));

			if ($response->code != 200) {
				// invalid response, raise an error
				throw new Exception(sprintf('Invalid response: %s', (strip_tags((string) $response->body) ?: '')), $response->code ?: 500);
			}

			// build HTML response string
			$html = '';
			$html .= '<p class="successmade">Chiusura giornaliera eseguita con successo.</p>';

		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] errore: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// get the currently active profile ID, if any
		$activeProfileId = $this->getActiveProfile();

		/**
		 * When the report is executed through a cron-job, the XML transmission of the end-day
		 * will also dump the data transmitted onto a resource file, useful for sending it via email.
		 */
		if ($scope === 'cron') {
			// prepare the XML resource file information
			$xml_name = implode('_', array_filter([$this->getFileName(), 'chiusura', 'giornaliera', time(), $activeProfileId, rand(100, 999)])) . '.xml';
			$xml_dest = $this->getDataMediaPath() . DIRECTORY_SEPARATOR . $xml_name;
			$xml_url  = $this->getDataMediaUrl() . $xml_name;

			// store the XML bytes into a local file on disk
			$stored = JFile::write($xml_dest, $xml->asXML());

			if ($stored && VBOPlatformDetection::isWordPress()) {
				/**
				 * Trigger files mirroring operation
				 */
				VikBookingLoader::import('update.manager');
				VikBookingUpdateManager::triggerUploadBackup($xml_dest);
			}

			if ($stored) {
				// define a new report resource for the generated file
				$this->defineResourceFile([
					'summary' => sprintf(
						'%sChiusura Giornaliera eseguita con successo.',
						($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : '')
					),
					'url'  => $xml_url,
					'path' => $xml_dest,
				]);
			}
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.registerendday.ok',
				'title'   => 'OTRS - Chiusura Giornaliera',
				'summary' => 'Chiusura eseguita con successo.',
			];

			try {
				// store the notification record
				VBOFactory::getNotificationCenter()->store([$notification]);
			} catch (Exception $e) {
				// silently catch the error without doing anything
			}
		}

		return [
			'html'    => $html,
			'success' => true,
			'xml'     => $xml->asXML(),
		];
	}

	/**
	 * Parses a report row into an associative list of key-value pairs.
	 * 
	 * @param 	array 	$row 	The report row to parse.
	 * 
	 * @return 	array
	 */
	protected function getAssocRowData(array $row)
	{
		$row_data = [];

		foreach ($row as $field) {
			$field_val = $field['value'];
			if (is_callable($field['callback_export'] ?? null)) {
				$field_val = $field['callback_export']($field_val);
			} elseif (!($field['no_export_callback'] ?? 0) && is_callable($field['callback'] ?? null)) {
				$field_val = $field['callback']($field_val);
			}
			$row_data[$field['key']] = $field_val;
		}

		return $row_data;
	}

	/**
	 * Registers the name to give to the file being exported.
	 * 
	 * @return 	void
	 */
	protected function registerExportFileName()
	{
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptype = VikRequest::getString('type', '', 'request');

		// build report type name
		if ($ptype === 'checkin') {
			$export_name = 'gestione_arrivi';
		} else {
			$export_name = 'checkout';
		}

		$this->setExportCSVFileName($export_name . '-' . $this->reportName . '-' . str_replace('/', '_', $pfromdate) . '.xml');
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
		if (!is_array($pax_data) || !$pax_data || empty($key)) {
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

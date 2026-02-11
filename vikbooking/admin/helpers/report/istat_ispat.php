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
 * ISPAT PMS Report implementation.
 * The report can generate and transmit data to Istituto Statistico Provincia Autonoma Trento (ISPAT).
 * 
 * @see 	Documentazione WS C59 - V4.pdf
 * @see 	Flusso dati in formato C-59 ISTAT.
 * @see 	Trentino Digitale SpA.
 * @see 	It is suggested to run the report every day for the data transmission with the presences of the day before.
 * 
 * @since 	1.18.2 (J) - 1.8.2 (WP)
 */
class VikBookingReportIstatIspat extends VikBookingReport
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
	 * List of foreign country names and codes.
	 * 
	 * @var  array
	 */
	protected $foreignCountries = [];

	/**
	 * List of foreign country 3-char values and codes.
	 * 
	 * @var  array
	 */
	protected $foreignCountryCodes = [];

	/**
	 * List of Italian province codes.
	 * 
	 * @var  array
	 */
	protected $italianProvinces = [];

	/**
	 * List of Italian Police countries.
	 * 
	 * @var  array
	 */
	protected $italianPoliceCountries = [];

	/**
	 * The path to the temporary directory used by this report.
	 * 
	 * @var  	string
	 */
	protected $report_tmp_path = '';

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
	 * The URL to the WSDL for the SOAP operations.
	 * 
	 * @var  	string
	 */
	protected $wsdl_url = 'https://dtu.provincia.tn.it/c59service?wsdl';

	/**
	 * The URL to the WSDL for the SOAP operations (test mode).
	 * 
	 * @var  	string
	 */
	protected $wsdl_url_test = 'https://dtu-test.infotn.it/c59service?wsdl';

	/**
	 * String representation of the PHP constant for the SOAP
	 * protocol version (either SOAP 1.1 or SOAP 1.2).
	 * 
	 * @var  	string
	 */
	protected $soap_version = 'SOAP_1_2';

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = 'ISPAT (Trentino Digitale SpA)';
		$this->reportFilters = [];

		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

		$this->foreignCountries = $this->loadIstatForeignCountries();
		$this->foreignCountryCodes = $this->loadIstatForeignCountries('3');
		$this->italianProvinces = $this->loadCountryStates('ITA');
		$this->italianPoliceCountries = $this->loadItalianPoliceCountries();

		// set the temporary report directory path
		$this->report_tmp_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ispat_tmp';

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
			'title' => [
				'type'  => 'custom',
				'label' => '',
				'html'  => '<p class="info">Configura le impostazioni per la trasmissione dei flussi turistici verso il sistema amministrativo del turismo ISPAT.<br/>Le informazioni su ID Struttura, Utente, Password sono le medesime credenziali della struttura ricettiva nel sistema amministrativo STU.</p>',
			],
			'test_mode' => [
                'type'    => 'checkbox',
                'label'   => 'Ambiente di Test',
                'help'    => 'Abilitando questa impostazione, la comunicazione dei dati avverrà con l\'ambiente di test. Abilitarla soltanto se si è in possesso di un\'utenza di test.',
                'default' => 0,
            ],
			'propertyid' => [
				'type'  => 'text',
				'label' => 'ID Struttura',
				'help'  => 'ID della struttura ricettiva a cui i dati si riferiscono. Ogni file trasmesso può contenere i dati di una singola struttura ricettiva. Utilizza l\'azione &quot;Lista Strutture&quot; per leggere gli ID assegnati al tuo account.',
			],
			'user' => [
				'type'  => 'text',
				'label' => 'Utente',
				'help'  => 'Username dell\'utente, utilizzato per l\'autenticazione al sistema amministrativo STU.',
			],
			'pwd' => [
				'type'  => 'password',
				'label' => 'Password',
				'help'  => 'Password dell\'utente, utilizzata per l\'autenticazione al sistema amministrativo STU.',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getScopedActions($scope = null, $visible = true)
	{
		// count the total number of rooms
		$tot_room_units = $this->countRooms();

		// list of custom actions for this report
		$actions = [
			[
				'id' => 'listaStrutture',
				'name' => 'Lista Strutture',
				'help' => 'Lettura dell\'elenco strutture ricettive e relativi ID associati all\'utente configurato dalle impostazioni.',
				'icon' => VikBookingIcons::i('hotel'),
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'ultimoC59',
				'name' => 'Ultima trasmissione C59',
				'help' => 'La funzione ritorna l\'ultima trasmissione dati eseguita secondo il formato C59.',
				'icon' => VikBookingIcons::i('calendar-check'),
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'uploadC59Full',
				'name' => 'Carica presenze turistiche',
				'help' => 'Carica il file sul web-service per le presenze turistiche.',
				'icon' => VikBookingIcons::i('cloud-upload-alt'),
				// flag to indicate that it requires the report data (lines)
				'export_data' => true,
				'scopes' => [
					'web',
					'cron',
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
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadRecordIspat();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download File</span></a>';

		// load report settings
		$settings = $this->loadSettings();

		// build the hidden values for the selection of Comuni & Province and much more.
		$hidden_vals = '<div id="vbo-report-ispat-hidden" style="display: none;">';

		// build params container HTML structure
		$hidden_vals .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
		$hidden_vals .= '	<div class="vbo-params-wrap">';
		$hidden_vals .= '		<div class="vbo-params-container">';
		$hidden_vals .= '			<div class="vbo-params-block vbo-params-block-noborder">';

		// provenienza
		$hidden_vals .= '	<div id="vbo-report-ispat-provenience" class="vbo-report-ispat-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Provincia Italiana o Stato Estero</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-provenience" onchange="vboReportChosenProvenience(this);"><option value=""></option>';
		$hidden_vals .= '				<optgroup label="Province Italiane">';
		foreach ($this->italianProvinces as $code => $province) {
			$hidden_vals .= '				<option value="' . $code . '">' . $province . '</option>'."\n";
		}
		$hidden_vals .= '				</optgroup>';
		$hidden_vals .= '				<optgroup label="Stati Esteri">';
		foreach ($this->foreignCountries as $code => $fcountry) {
			$hidden_vals .= '				<option value="' . $code . '">' . $fcountry . '</option>'."\n";
		}
		$hidden_vals .= '				</optgroup>';
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

		// append button to save the data when creating manual values
		$filter_opt = array(
			'label' => '<label class="vbo-report-ispat-manualsave" style="display: none;">Dati inseriti</label>',
			'html' => '<button type="button" class="btn vbo-config-btn vbo-report-ispat-manualsave" style="display: none;" onclick="vboIspatSaveData();"><i class="' . VikBookingIcons::i('save') . '"></i> ' . JText::translate('VBSAVE') . '</button>',
		);
		array_push($this->reportFilters, $filter_opt);

		//jQuery code for the datepicker calendars, select2 and triggers for the dropdown menus
		$pfromdate = VikRequest::getString('fromdate', '', 'request');

		$js = 'var reportActiveCell = null, reportObj = {};
		var vbo_ispat_ajax_uri = "' . VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=invoke_report&report=' . $this->reportFile) . '";
		var vbo_ispat_save_icn = "' . VikBookingIcons::i('save') . '";
		var vbo_ispat_saving_icn = "' . VikBookingIcons::i('circle-notch', 'fa-spin fa-fw') . '";
		var vbo_ispat_saved_icn = "' . VikBookingIcons::i('check-circle') . '";
		jQuery(function() {
			//prepare main filters
			jQuery(".vbo-report-datepicker:input").datepicker({
				// data massima di generazione movimenti = ieri
				maxDate: "-1d",
				dateFormat: "'.$this->getDateFormat('jui').'",
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			//prepare filler helpers
			jQuery("#vbo-report-ispat-hidden").children().detach().appendTo(".vbo-info-overlay-report");
			jQuery("#choose-provenience").select2({placeholder: "- Provincia o Stato Estero -", width: "200px"});
			//click events
			jQuery(".vbo-report-load-provenience").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-ispat-selcont").hide();
				jQuery("#vbo-report-ispat-provenience").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
		});
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
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-provenience")) {
						reportObj[nowindex]["state"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-provenience").val("").select2("data", null, false);
			jQuery(".vbo-report-ispat-manualsave").show();
		}
		//download function
		function vboDownloadRecordIspat(type) {
			if (!confirm("Sei sicuro di aver compilato tutte le informazioni necessarie?")) {
				return false;
			}

			let use_blank = true;
			if (typeof type === "undefined") {
				type = 1;
			} else {
				use_blank = false;
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
		function vboIspatSaveData() {
			jQuery("button.vbo-report-ispat-manualsave").find("i").attr("class", vbo_ispat_saving_icn);
			VBOCore.doAjax(
				vbo_ispat_ajax_uri,
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
					jQuery("button.vbo-report-ispat-manualsave").addClass("btn-success").find("i").attr("class", vbo_ispat_saved_icn);
				},
				function(error) {
					alert(error.responseText);
					jQuery("button.vbo-report-ispat-manualsave").removeClass("btn-success").find("i").attr("class", vbo_ispat_save_icn);
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

		// get the possibly injected report options
		$options = $this->getReportOptions();

		// injected options will replace request variables, if any
		$opt_fromdate = $options->get('fromdate', '');

		// input fields and other vars
		$pfromdate = $opt_fromdate ?: VikRequest::getString('fromdate', '', 'request');

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

		// fetch all bookings with a stay date between the interval of dates requested
		$q->andWhere([
			'(' . $dbo->qn('o.checkin') . ' BETWEEN ' . $from_ts . ' AND ' . $to_ts . ')',
			'(' . $dbo->qn('o.checkout') . ' BETWEEN ' . $from_ts . ' AND ' . $to_ts . ')',
			'(' . $dbo->qn('o.checkin') . ' < ' . $from_ts . ' AND ' . $dbo->qn('o.checkout') . ' > ' . $to_ts . ')',
		], 'OR');

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
				'label' => 'ID'
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
			// provincia (ITA)
			[
				'key' => 'provincia',
				'label' => 'Provincia',
				// hide this field in the View
				'ignore_view' => 1,
			],
			// country
			[
				'key' => 'country',
				'label' => 'Nazione',
				// hide this field in the View
				'ignore_view' => 1,
			],
			// country full name
			[
				'key' => 'country_full_name',
				'label' => 'Nazione completa',
				// hide this field in the View
				'ignore_view' => 1,
			],
			// provenienza
			[
				'key' => 'provenience',
				'attr' => [
					'class="center"',
				],
				'label' => 'Provenienza',
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

			// create one row for each guest
			$guest_ind = 1;
			foreach ($guests_rows as $ind => $guests) {
				// prepare row record for this room-guest
				$insert_row = [];

				// find the actual guest-room-index
				$guest_room_ind = $this->calcGuestRoomIndex($room_guests, $guest_ind);

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

				// provenience (Provenienza)
				$provenience = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'state') ?: $guests['state'] ?: '';
				$provenience = $provenience ?: $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'province_s');
				$provenience = $provenience ?: $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'province_b');
				$provenience = (string) ($provenience == 'ES' ? '' : $provenience);

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

				// provincia (ITA)
				array_push($insert_row, [
					'key' => 'provincia',
					'value' => (!strcasecmp(($pax_country_list[0] ?? ''), 'ITA') && $provenience && isset($this->italianProvinces[$provenience]) ? $provenience : null),
					'ignore_view' => 1,
				]);

				// country
				array_push($insert_row, [
					'key' => 'country',
					'value' => strtoupper($pax_country_list[0] ?? ''),
					'ignore_view' => 1,
				]);

				if (!$provenience && $pax_country_list && stripos($pax_country_list[0], 'ITA') === false) {
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

					// calculate the similarity for each country ISTAT code
					foreach ($this->foreignCountries as $fc_code => $fc_name) {
						// calculate similarity
						similar_text(strtolower($fc_name), strtolower($match_name), $similarity);

						// assign similarity to country ISTAT code
						$foreign_matches[$fc_code] = $similarity;
					}

					// sort similarity in descending order
					arsort($foreign_matches);

					// assign the first match found, the most similar one
					foreach ($foreign_matches as $fc_code => $similarity_score) {
						// we trust the first match to be valid
						$provenience = $fc_code;
						break;
					}

					// at last, check if we can match an exact 3-char known country code
					if (strlen($pax_country_list[0]) === 3 && $matched_code = array_search($pax_country_list[0], $this->foreignCountryCodes)) {
						// do not use similarity, but rather the exact country code
						$provenience = $matched_code;
					}
				}

				// country full name
				array_push($insert_row, [
					'key' => 'country_full_name',
					'callback_export' => function($val) {
						return $val ? strtoupper($val) : $val;
					},
					'value' => (($pax_country_list[0] ?? '') && $provenience && strcasecmp($pax_country_list[0], 'ITA') ? ($this->foreignCountries[$provenience] ?? $pax_country_list[0]) : null),
					'ignore_view' => 1,
				]);

				$provenience_elem_class = '';
				if (empty($provenience)) {
					// optional selection style
					$provenience_elem_class = ' vbo-report-load-provenience vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$provenience_elem_class = ' vbo-report-load-provenience vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, [
					'key' => 'provenience',
					'attr' => [
						'class="center' . $provenience_elem_class . '"',
					],
					'callback' => function($val) {
						return $this->italianProvinces[$val] ?? $this->foreignCountries[$val] ?? $val ?: '?';
					},
					'no_export_callback' => 1,
					'value' => $provenience,
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
	 * Builds the report data for export or transmission.
	 * 
	 * @return 	array 	Empty array in case of errors, or list of rows.
	 */
	protected function buildRecordData()
	{
		if (!$this->getReportData()) {
			return [];
		}

		$app = JFactory::getApplication();

		// get the possibly injected report options
		$options = $this->getReportOptions();

		// from date filter
		$pfromdate = $app->input->getString('fromdate', '') ?: $options->get('fromdate', '');
		$pfromdate_ts = VikBooking::getDateTimestamp($pfromdate);

		// optional listings filtered
		$plistings = (array) ($app->input->get('listings', [], 'array') ?: $options->get('listings', []));

		// custom data manually filled before saving and reloading
		$pfiller = $app->input->get('filler', '', 'raw');
		$pfiller = !empty($pfiller) ? (array) json_decode($pfiller, true) : [];

		// pool of booking IDs to update their history
		$this->export_booking_ids = [];

		// records data pool
		$data = [
			'bookings'           => [],
			'booking_groups'     => [],
			'tot_rooms'          => $this->countRooms($plistings),
			'tot_rooms_occupied' => $this->countDayRoomsOccupied($pfromdate_ts, $plistings),
			'tot_arrivals'       => 0,
			'tot_departures'     => 0,
			'tot_stays'          => 0,
			'xml_c59_full'       => '',
		];

		// record the main guest information for each booking
		$bookings_main_guest = [];

		// push the bookings data
		foreach ($this->rows as $ind => $row) {
			// build row data
			$row_data = [];

			foreach ($row as $field) {
				if ($field['key'] == 'idbooking' && !in_array($field['value'], $this->export_booking_ids)) {
					array_push($this->export_booking_ids, $field['value']);
				}

				if (isset($field['ignore_export'])) {
					continue;
				}

				// report value
				if (is_array($pfiller) && isset($pfiller[$ind]) && isset($pfiller[$ind][$field['key']])) {
					if (strlen($pfiller[$ind][$field['key']])) {
						$field['value'] = $pfiller[$ind][$field['key']];
					}
				}

				if (isset($field['callback_export'])) {
					$field['callback'] = $field['callback_export'];
				}

				// get field value
				$value = !isset($field['no_export_callback']) && isset($field['callback']) && is_callable($field['callback']) ? $field['callback']($field['value']) : $field['value'];

				// set row data
				$row_data[$field['key']] = $value;
			}

			// check main guest booking
			if (!isset($bookings_main_guest[$row_data['idbooking']])) {
				// push the main guest data for this booking
				$bookings_main_guest[$row_data['idbooking']] = $row_data;
			} else {
				// when parsing the Nth guest of the same booking, ensure we've got no missing values
				$needed_props = [
					'provincia',
					'country',
					'country_full_name',
					'provenience',
				];

				foreach ($needed_props as $needed_prop) {
					if (!empty($bookings_main_guest[$row_data['idbooking']][$needed_prop]) && empty($row_data[$needed_prop])) {
						// copy value from the main guest
						$row_data[$needed_prop] = $bookings_main_guest[$row_data['idbooking']][$needed_prop];
					}
				}
			}

			// push the booking row data to the pool
			$data['bookings'][] = $row_data;
		}

		// group the bookings by italian province or foreign countries
		$foreign_countries_map = [];
		foreach ($data['bookings'] as $booking_row) {
			// determine the group key (default to the empty key "0")
			$group_key = 0;

			if (!empty($booking_row['provenience']) && is_numeric($booking_row['provenience']) && isset($this->foreignCountryCodes[$booking_row['provenience']])) {
				// must be a custom ISTAT country code
				$group_key = $this->foreignCountryCodes[$booking_row['provenience']];
			} elseif (!empty($booking_row['provenience']) && is_numeric($booking_row['provenience']) && isset($this->foreignCountries[$booking_row['provenience']])) {
				// must be a custom ISPAT country code
				$group_key = $this->foreignCountries[$booking_row['provenience']];
			} elseif (!empty($booking_row['country'])) {
				if (!strcasecmp($booking_row['country'], 'ITA')) {
					// for italian guests we need to use the province, if available
					$group_key = 'ITA_0';
					if (!empty($booking_row['provincia'])) {
						// italian customer will be grouped by province
						$group_key = 'ITA_' . $booking_row['provincia'];
					}
				} else {
					// foreign country
					$group_key = $booking_row['country'];
					$foreign_countries_map[$group_key] = $booking_row['country_full_name'] ?: $group_key;
				}
			}

			// start group key container
			$data['booking_groups'][$group_key] = $data['booking_groups'][$group_key] ?? [];

			// push booking to proper group container
			$data['booking_groups'][$group_key][] = $booking_row;
		}

		// build the XML node for C59 "full"
		$data['xml_c59_full'] .= '<c59>' . "\n";

		// whether the property is open today
		$is_open = (bool) (!VikBooking::validateClosingDates($pfromdate_ts, $pfromdate_ts));

		// add nodes
		// $data['xml_c59_full'] .= '<dataMovimentazione>' . date('Y-m-d', $pfromdate_ts) . '</dataMovimentazione>' . "\n";
		$data['xml_c59_full'] .= '<dataMovimentazione>' . date('Y-m-d', $pfromdate_ts) . 'T00:00:00+02:00</dataMovimentazione>' . "\n";
		$data['xml_c59_full'] .= '<esercizioAperto>' . intval($is_open) . '</esercizioAperto>' . "\n";

		if ($is_open) {
			// start building the "movimenti nodes"
			foreach ($data['booking_groups'] as $group_key => $bookings_group) {
				// tell whether we have an italian group
				$is_italian = (bool) preg_match('/^ITA_/', $group_key);
				if ($is_italian) {
					// adjust the group key to only contain the province 2-char code (unless empty, hence "0")
					$group_key = preg_replace('/^ITA_/', '', $group_key);
				}

				// count arrivals, departures and stays for the current group
				$group_tot_arrivals = 0;
				$group_tot_departures = 0;
				$group_tot_stays = 0;

				foreach ($bookings_group as $booking_row) {
					if ($booking_row['is_checkin']) {
						$group_tot_arrivals++;
					}
					if ($booking_row['is_checkout']) {
						$group_tot_departures++;
					}
					if (!$booking_row['is_checkin'] && !$booking_row['is_checkout']) {
						$group_tot_stays++;
					}
				}

				// increase overall counters
				$data['tot_arrivals'] += $group_tot_arrivals;
				$data['tot_departures'] += $group_tot_departures;
				$data['tot_stays'] += $group_tot_stays;

				if (!$group_tot_arrivals && !$group_tot_departures) {
					// the node "movimenti" for each "targa" does not accept empty arrivals and empty departures
					continue;
				}

				// build "movimento" node for the current group ("targa")
				$data['xml_c59_full'] .= '<movimenti>' . "\n";

				// build "targa" node
				$targaNode = '';
				if ($is_italian && !empty($group_key)) {
					// 2-char province expected
					$targaNode = '<targa>' . htmlspecialchars(strtoupper($group_key)) . '</targa>' . "\n";
				} elseif (!$is_italian && !empty($group_key) && isset($foreign_countries_map[$group_key])) {
					// foreign country full name expected
					$targaNode = '<targa>' . htmlspecialchars(strtoupper($foreign_countries_map[$group_key])) . '</targa>' . "\n";
				} elseif (!$is_italian && !empty($group_key) && array_search($group_key, $this->foreignCountryCodes) !== false) {
					// 3-char country name expected from ISTAT country code
					$istat_ccode = array_search($group_key, $this->foreignCountryCodes);
					$targaNode = '<targa>' . htmlspecialchars(strtoupper($this->foreignCountries[$istat_ccode])) . '</targa>' . "\n";
				} elseif (!$is_italian && !empty($group_key) && array_search($group_key, $this->foreignCountries) !== false) {
					// full country name expected from ISPAT country code
					$istat_ccode = array_search($group_key, $this->foreignCountries);
					$targaNode = '<targa>' . htmlspecialchars(strtoupper($this->foreignCountries[$istat_ccode])) . '</targa>' . "\n";
				}

				// set "movimento" child nodes
				$data['xml_c59_full'] .= '<arrivi>' . $group_tot_arrivals . '</arrivi>' . "\n";
				$data['xml_c59_full'] .= '<italia>' . intval($is_italian) . '</italia>' . "\n";
				$data['xml_c59_full'] .= '<partenze>' . $group_tot_departures . '</partenze>' . "\n";
				if ($targaNode) {
					// the "targa" node is optional
					$data['xml_c59_full'] .= $targaNode;
				}

				// finalize movimento node
				$data['xml_c59_full'] .= '</movimenti>' . "\n";
			}
		}

		// set overall arrivals
		$data['xml_c59_full'] .= '<totaleArrivi>' . $data['tot_arrivals'] . '</totaleArrivi>' . "\n";
		// set overall departures
		$data['xml_c59_full'] .= '<totalePartenze>' . $data['tot_departures'] . '</totalePartenze>' . "\n";
		// set overall stays
		$data['xml_c59_full'] .= '<totalePresenti>' . $data['tot_stays'] . '</totalePresenti>' . "\n";
		// set tot rooms available
		$data['xml_c59_full'] .= '<unitaAbitativeDisponibili>' . $data['tot_rooms'] . '</unitaAbitativeDisponibili>' . "\n";
		// set tot rooms booked
		$data['xml_c59_full'] .= '<unitaAbitativeOccupate>' . $data['tot_rooms_occupied'] . '</unitaAbitativeOccupate>' . "\n";

		// finalize C59 node
		$data['xml_c59_full'] .= '</c59>' . "\n";

		return $data;
	}

	/**
	 * Generates the text file for ISTAT Valle d'Aosta, then sends it to output for download.
	 * In case of errors, the process is not terminated (exit) to let the View display the
	 * error message(s). The export type argument can eventually determine an action to run.
	 *
	 * @param 	string 	$export_type 	Differentiates the type of export requested.
	 *
	 * @return 	void|bool 				Void in case of script termination, boolean otherwise.
	 */
	public function customExport($export_type = null)
	{
		// build the record data
		$data = $this->buildRecordData();

		// build report action data, if needed
		$action_data = array_merge($this->getActionData($registry = false), $data);

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
				$this->setError($e->getMessage());

				// abort
				return false;
			}
		}

		// proceed with the regular export function (write on file through cron or download file through web)

		if (!$data || empty($data['xml_c59_full'])) {
			// abort
			return false;
		}

		// update history for all bookings affected before exporting
		foreach ($this->export_booking_ids as $bid) {
			VikBooking::getBookingHistoryInstance($bid)->store('RP', $this->reportName . ' - Export');
		}

		/**
		 * Attempt to format the XML string for the C59 node to have a better result.
		 */
		$formattedC59 = $this->loadXmlSoap($data['xml_c59_full'])->formatXml() ?: $data['xml_c59_full'];

		/**
		 * Custom export method supports a custom export handler, if previously set.
		 */
		if ($this->hasExportHandler()) {
			// write data onto the custom file handler
			$fp = $this->getExportCSVHandler();
			fwrite($fp, $formattedC59);
			fclose($fp);

			// return true as data was written
			return true;
		}

		// force text file download in case of regular export
		header("Content-type: text/xml");
		header("Cache-Control: no-store, no-cache");
		header('Content-Disposition: attachment; filename="' . $this->getExportCSVFileName() . '"');
		echo $formattedC59;

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
	 * Returns an associative list of states/provinces for the given country.
	 * Visibility should be public in case other ISTAT reports for Italy may
	 * require to load the same provinces.
	 * 
	 * @return 	array
	 */
	public function loadCountryStates($country)
	{
		$country_states = [];

		foreach (VBOStateHelper::getCountryStates((int) VBOStateHelper::getCountryId($country)) as $state_record) {
			$country_states[$state_record['state_2_code']] = $state_record['state_name'];
		}

		return $country_states;
	}

	/**
	 * Returns an associative list of ISTAT foreign country codes. The full list includes the country
	 * codes compatible with the Alloggiati Web integration (ISTAT codes), as well as a merged list
	 * of country "false" codes (negative keys) that are supported and required by ISPAT.
	 * 
	 * @param 	string 	$type 	The string "3" can be passed to get the known 3-char country codes.
	 * 
	 * @return 	array 			Associative list of countries, according to type param.
	 */
	public function loadIstatForeignCountries($type = '')
	{
		if ($type === '3') {
			// return the list for only the known country ISO code (3-char)
			return [
				'528' => "ARG",
				'800' => "AUS",
				'038' => "AUT",
				'017' => "BEL",
				'508' => "BRA",
				'068' => "BGR",
				'404' => "CAN",
				'720' => "CHN",
				'600' => "CYP",
				'728' => "KOR",
				'092' => "HRV",
				'008' => "DNK",
				'220' => "EGY",
				'053' => "EST",
				'032' => "FIN",
				'001' => "FRA",
				'004' => "DEU",
				'732' => "JPN",
				'009' => "GRC",
				'664' => "IND",
				'007' => "IRL",
				'024' => "ISL",
				'624' => "ISR",
				'999' => "ITA",
				'054' => "LVA",
				'055' => "LTU",
				'018' => "LUX",
				'046' => "MLT",
				'412' => "MEX",
				'028' => "NOR",
				'804' => "NZL",
				'230' => "MED",
				'003' => "NLD",
				'060' => "POL",
				'010' => "PRT",
				'006' => "GBR",
				'061' => "CZE",
				'066' => "ROU",
				'075' => "RUS",
				'063' => "SVK",
				'091' => "SVN",
				'011' => "ESP",
				'400' => "USA",
				'388' => "ZAF",
				'030' => "SWE",
				'036' => "CHE",
				'052' => "TUR",
				'072' => "UKR",
				'064' => "HUN",
				'484' => "VEN",
			];
		}

		// return the whole ISTAT + ISPAT (negative keys) list
		return [
			'-990' => "AFGHANISTAN",
			'-991' => "ALBANIA",
			'-992' => "ALGERIA",
			'-993' => "ANDORRA",
			'-994' => "ANGOLA",
			'-995' => "ANTIGUA E BARBUDA",
			'-996' => "ARABIA SAUDITA",
			'528' => "ARGENTINA",
			'-997' => "ARMENIA",
			'800' => "AUSTRALIA",
			'038' => "AUSTRIA",
			'-998' => "AZERBAIGIAN",
			'-999' => "BAHAMAS",
			'-9910' => "BAHREIN",
			'-9911' => "BANGLADESH",
			'-9912' => "BARBADOS",
			'017' => "BELGIO",
			'-9913' => "BELIZE",
			'-9914' => "BENIN",
			'-9915' => "BHUTAN",
			'-9916' => "BIELORUSSIA",
			'-9917' => "BOLIVIA",
			'-9918' => "BOSNIA-ERZEGOVINA",
			'-9919' => "BOTSWANA",
			'508' => "BRASILE",
			'-9920' => "BRUNEI",
			'068' => "BULGARIA",
			'-9921' => "BURKINA-FASO",
			'-9922' => "BURUNDI",
			'-9923' => "CAMBOGIA",
			'-9924' => "CAMERUN",
			'404' => "CANADA",
			'-9925' => "CAPO VERDE",
			'-9926' => "CIAD",
			'-9927' => "CILE",
			'600' => "CIPRO",
			'-9928' => "COLOMBIA",
			'-9929' => "COMORE",
			'-9930' => "CONGO",
			'-9931' => "COSTA D'AVORIO",
			'-9932' => "COSTARICA",
			'092' => "CROAZIA",
			'-9933' => "CUBA",
			'008' => "DANIMARCA",
			'-9934' => "DOMINICA",
			'-9935' => "ECUADOR",
			'220' => "EGITTO",
			'-9936' => "EL SALVADOR",
			'-9937' => "EMIRATI ARABI UNITI",
			'-9938' => "ERITREA",
			'053' => "ESTONIA",
			'-9939' => "ETIOPIA",
			'075' => "FEDERAZIONE RUSSA",
			'-9940' => "FIGI",
			'-9941' => "FILIPPINE",
			'032' => "FINLANDIA",
			'001' => "FRANCIA",
			'-9942' => "GABON",
			'-9943' => "GAMBIA",
			'-9944' => "GEORGIA",
			'004' => "GERMANIA",
			'-9945' => "GHANA",
			'-9946' => "GIAMAICA",
			'732' => "GIAPPONE",
			'-9947' => "GIBUTI",
			'-9948' => "GIORDANIA",
			'009' => "GRECIA",
			'-9949' => "GRENADA",
			'-9950' => "GUATEMALA",
			'-9951' => "GUINEA",
			'-9952' => "GUINEA BISSAU",
			'-9953' => "GUINEA EQUATORIALE",
			'-9954' => "GUYANA",
			'-9955' => "HAITI",
			'-9956' => "HONDURAS",
			'664' => "INDIA",
			'-9957' => "INDONESIA",
			'-9958' => "IRAN, REPUBBLICA ISLAMICA DEL",
			'-9959' => "IRAQ",
			'007' => "IRLANDA",
			'024' => "ISLANDA",
			'624' => "ISRAELE",
			'999' => "ITALIA",
			'-9960' => "JERSEY, ISOLE",
			'-9961' => "KAZAKISTAN",
			'-9962' => "KENIA",
			'-9963' => "KIRGHIZISTAN",
			'-9964' => "KIRIBATI",
			'-9965' => "KOSOVO",
			'-9966' => "KUWAIT",
			'-9967' => "LAOS",
			'-9968' => "LESOTHO",
			'054' => "LETTONIA",
			'-9969' => "LIBANO",
			'-9970' => "LIBERIA",
			'-9971' => "LIBIA",
			'-9972' => "LIECHTENSTEIN",
			'055' => "LITUANIA",
			'018' => "LUSSEMBURGO",
			'-9973' => "MACEDONIA (EX REPUBBLICA JUGOSLAVA)",
			'-9974' => "MADAGASCAR",
			'-9975' => "MALAWI",
			'-9976' => "MALAYSIA",
			'-9977' => "MALDIVE",
			'-9978' => "MALI",
			'046' => "MALTA",
			'-9979' => "MAROCCO",
			'-9980' => "MARSHALL",
			'-9981' => "MAURITANIA",
			'-9982' => "MAURITIUS",
			'412' => "MESSICO",
			'-9983' => "MICRONESIA",
			'-9984' => "MOLDOVA",
			'-9985' => "MONACO",
			'-9986' => "MONGOLIA",
			'-9987' => "MONTENEGRO",
			'-9988' => "MOZAMBICO",
			'-9989' => "MYANMAR",
			'-9990' => "NAMIBIA",
			'-9991' => "NAURU",
			'-9992' => "NEPAL",
			'-9993' => "NICARAGUA",
			'-9994' => "NIGER",
			'-9995' => "NIGERIA",
			'028' => "NORVEGIA",
			'804' => "NUOVA ZELANDA",
			'-9996' => "OMAN",
			'230' => "PAESI AFRICA MEDITERRANEA",
			'003' => "PAESI BASSI",
			'-9997' => "PAKISTAN",
			'-9998' => "PALAU",
			'-9999' => "PANAMA",
			'-99100' => "PAPUA NUOVA GUINEA",
			'-99101' => "PARAGUAY",
			'-99102' => "PERU'",
			'060' => "POLONIA",
			'010' => "PORTOGALLO",
			'-99103' => "QATAR",
			'006' => "REGNO UNITO",
			'-99104' => "REP.POPOLARE DEMOCRATICA DI COREA (COREA DEL NORD)",
			'061' => "REPUBBLICA CECA",
			'-99105' => "REPUBBLICA CENTRAFRICANA",
			'-99106' => "REPUBBLICA DEMOCRATICA DEL CONGO (EX ZAIRE)",
			'728' => "REPUBBLICA DI COREA (COREA DEL SUD)",
			'-99107' => "REPUBBLICA DOMINICANA",
			'720' => "REPUBBLICA POPOLARE CINESE",
			'063' => "REPUBBLICA SLOVACCA",
			'-99108' => "RICONOSCIUTI NON CITTADINI (LETTONI)",
			'066' => "ROMANIA",
			'-99110' => "S. VINCENT E GRENADINE",
			'-99111' => "SAINT KITTS E NEVIS",
			'-99112' => "SAINT LUCIA",
			'-99113' => "SALOMONE",
			'-99114' => "SAMOA",
			'-99115' => "SAN MARINO",
			'-99116' => "SANTA SEDE",
			'-99117' => "SAO TOME' E PRINCIPE",
			'-99118' => "SENEGAL",
			'-99119' => "SERBIA REPUBBLICA DI",
			'-99120' => "SEYCHELLES",
			'-99121' => "SIERRA LEONE",
			'-99122' => "SINGAPORE",
			'-99123' => "SIRIA",
			'091' => "SLOVENIA",
			'-99124' => "SOMALIA",
			'011' => "SPAGNA",
			'-99125' => "SRI LANKA (CEYLON)",
			'400' => "STATI UNITI D'AMERICA",
			'388' => "SUD AFRICA",
			'-99126' => "SUD SUDAN, REPUBBLICA DEL",
			'-99127' => "SUDAN",
			'-99128' => "SURINAME",
			'030' => "SVEZIA",
			'036' => "SVIZZERA",
			'-99129' => "SWAZILAND",
			'-99130' => "TAGIKISTAN",
			'-99131' => "TAIWAN",
			'-99132' => "TANZANIA",
			'-99133' => "TERRITORI AUTONOMIA PALESTINESE",
			'-99134' => "THAILANDIA",
			'-99135' => "TIMOR ORIENTALE",
			'-99136' => "TOGO",
			'-99137' => "TONGA",
			'-99138' => "TRINIDAD E TOBAGO",
			'-99139' => "TUNISIA",
			'052' => "TURCHIA",
			'-99140' => "TURKMENISTAN",
			'-99141' => "TUVALU",
			'-99109' => "UANDA",
			'072' => "UCRAINA",
			'-99142' => "UGANDA",
			'064' => "UNGHERIA",
			'-99143' => "URUGUAY",
			'-99144' => "UZBEKISTAN",
			'-99145' => "VANUATU",
			'484' => "VENEZUELA",
			'-99146' => "VIETNAM",
			'-99147' => "YEMEN",
			'-99148' => "ZAMBIA",
			'-99149' => "ZIMBABWE",
			'777' => "ALTRI PAESI",
			'300' => "ALTRI PAESI AFRICA",
			'530' => "ALTRI PAESI AMERICA LATINA",
			'760' => "ALTRI PAESI DELL'ASIA",
			'100' => "ALTRI PAESI EUROPEI",
			'750' => "ALTRI PAESI MEDIO ORIENTE",
			'410' => "ALTRI PAESI NORD AMERICA",
			'810' => "ALTRI PAESI OCEANIA",
		];
	}

	/**
	 * Custom scoped action to upload the guest presence records.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function uploadC59Full($scope = null, array $data = [])
	{
		if (!($data['xml_c59_full'] ?? null) && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadRecordIspat(\'uploadC59Full\');</script>',
			];
		}

		if (!($data['xml_c59_full'] ?? null)) {
			// attempt to build the report data if not set
			$data = array_merge($data, $this->buildRecordData());
		}

		if (empty($data['xml_c59_full'])) {
			throw new Exception('Nessun dato presente per la trasmissione dei record delle presenze turistiche.', 500);
		}

		$settings = $this->loadSettings();

		if (!$settings || empty($settings['propertyid']) || empty($settings['user']) || empty($settings['pwd'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// determine the WS url to use
		$use_ws_url = !empty($settings['test_mode']) ? $this->wsdl_url_test : $this->wsdl_url;

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:mov="http://movimentazione.manager.web.module.dtu.infotn.it/">
	<soapenv:Header/>
	<soapenv:Body>
		<mov:inviaC59Full>
			<username>' . $settings['user'] . '</username>
			<password>' . $settings['pwd'] . '</password>
			<struttura>' . $settings['propertyid'] . '</struttura>
			' . $data['xml_c59_full'] . '
		</mov:inviaC59Full>
	</soapenv:Body>
</soapenv:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient($settings);

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, preg_replace('/\?wsdl$/i', '', $use_ws_url), 'inviaC59Full', SOAP_1_2);

			// wrap the response into an object
			$xmlResponse = $this->loadXmlSoap($response);

			// check for response errors
			$responseFault = $xmlResponse->getSoapRecursiveNsElement([
				['soap', true, 'Body'],
				['soap', true, 'Fault'],
			]);

			if (in_array(@$responseFault->getName(), ['faultcode', 'faultstring'])) {
				// errors returned
				$fault_code = $responseFault->faultcode ?? 0;
				$fault_mess = $responseFault->faultstring ?? '';
				throw new Exception(sprintf("Errore (%s):\n%s.", $fault_code, $fault_mess), 500);
			}

			// build HTML response string
			$html = '';
			$html .= '<p class="successmade">Totale registrazioni analizzate: ' . count($data['bookings']) . '</p>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// update history for all bookings affected
		foreach ($this->export_booking_ids as $bid) {
			// build extra data payload for the history event
			$hdata = [
				'transmitted' => 1,
				'method'      => 'uploadC59Full',
				'report'      => $this->getFileName(),
			];
			// store booking history event
			VikBooking::getBookingHistoryInstance($bid)
				->setExtraData($hdata)
				->store('RP', $this->reportName . ' - Trasmissione presenze turistiche');
		}

		// get the currently active profile ID, if any
		$activeProfileId = $this->getActiveProfile();

		/**
		 * When the report is executed through a cron-job, the transmission of the guest details
		 * will also dump the data transmitted onto a resource file, useful for sending it via email.
		 */
		if ($scope === 'cron') {
			// prepare the XML resource file information
			$xml_name = implode('_', array_filter([$this->getFileName(), 'trasmissione', 'flussi', time(), $activeProfileId, rand(100, 999)])) . '.xml';
			$xml_dest = $this->getDataMediaPath() . DIRECTORY_SEPARATOR . $xml_name;
			$xml_url  = $this->getDataMediaUrl() . $xml_name;

			// store the XML bytes into a local file on disk
			$stored = JFile::write($xml_dest, $soap_xml);

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
						'%sFlussi turistici generati e trasmessi con successo.',
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
				'type'    => 'pmsreport.transmit.ok',
				'title'   => $this->reportName . ' - Trasmissione presenze turistiche',
				'summary' => sprintf(
					'%sSono stati trasmessi i dati degli ospiti per la data %s. Prenotazioni analizzate: %d.',
					($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : ''),
					$this->exported_checkin_dates[0] ?? '',
					count($data['bookings'])
				),
			];

			try {
				// store the notification record
				VBOFactory::getNotificationCenter()->store([$notification]);
			} catch (Exception $e) {
				// silently catch the error without doing anything
			}
		}

		return [
			'html'     => $html,
			'success'  => true,
			'bookings' => $data['bookings'],
		];
	}

	/**
	 * Custom scoped action to get a list of properties ("listaStrutture").
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function listaStrutture($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['user']) || empty($settings['pwd'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// determine the WS url to use
		$use_ws_url = !empty($settings['test_mode']) ? $this->wsdl_url_test : $this->wsdl_url;

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:mov="http://movimentazione.manager.web.module.dtu.infotn.it/">
	<soapenv:Header/>
	<soapenv:Body>
		<mov:listaStrutture>
			<username>' . $settings['user'] . '</username>
			<password>' . $settings['pwd'] . '</password>
		</mov:listaStrutture>
	</soapenv:Body>
</soapenv:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient($settings);

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, preg_replace('/\?wsdl$/i', '', $use_ws_url), 'listaStrutture', SOAP_1_2);

			// wrap the response into an object
			$xmlResponse = $this->loadXmlSoap($response);

			// check for response errors
			$responseFault = $xmlResponse->getSoapRecursiveNsElement([
				['soap', true, 'Body'],
				['soap', true, 'Fault'],
			]);

			if (isset($responseFault->faultstring) && strlen((string) $responseFault->faultstring)) {
				// errors returned
				$fault_code = $responseFault->faultcode ?? 0;
				$fault_mess = $responseFault->faultstring ?? '';
				throw new Exception(sprintf("Errore (%s):\n%s.", $fault_code, $fault_mess), 500);
			}

			// access the response body
			$xmlBody = $xmlResponse->getSoapRecursiveNsElement([
				['soap', true, 'Body'],
				['ns2', true, 'listaStruttureResponse'],
			]);

			if (!isset($xmlBody->return)) {
				// no properties returned
				throw new UnexpectedValueException(sprintf('Nessuna struttura trovata [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// list of properties found
			$properties = [];

			// build HTML response string
			$html = '';
			$html .= '<p class="successmade">Lista strutture assegnate:</p>';
			$html .= '<ol>';

			// scan all properties returned
			foreach ($xmlBody->return as $returnNode) {
				// get the property name and id
				$property_id = (string) ($returnNode->struttura ?? 0);
				$property_name = (string) ($returnNode->name ?? '');

				// push property found
				$properties[$property_id] = $property_name;

				// get the readable properties name and id
				$property_id_read = (string) ($returnNode->struttura ?? '??');
				$property_name_read = (string) ($returnNode->name ?? '----');

				// build HTML property string
				$html .= '<li>' . sprintf('ID <strong>%s</strong> - <strong>%s</strong>', $property_id_read, $property_name_read) . '</li>';
			}

			// finalize HTML
			$html .= '</ol>';

		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		return [
			'html' => $html,
			'properties' => $properties,
		];
	}

	/**
	 * Custom scoped action to get the last C59 transmission uploaded ("ultimoC59").
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function ultimoC59($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['user']) || empty($settings['pwd'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		if (empty($settings['propertyid'])) {
			throw new Exception('ID struttura mancante nelle impostazioni. Utilizza la funzione Lista Strutture per leggere gli ID associati al tuo account ed inserisci un ID nelle impostazioni.', 500);
		}

		// determine the WS url to use
		$use_ws_url = !empty($settings['test_mode']) ? $this->wsdl_url_test : $this->wsdl_url;

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:mov="http://movimentazione.manager.web.module.dtu.infotn.it/">
	<soapenv:Header/>
	<soapenv:Body>
		<mov:ultimoC59>
			<username>' . $settings['user'] . '</username>
			<password>' . $settings['pwd'] . '</password>
			<struttura>' . $settings['propertyid'] . '</struttura>
		</mov:ultimoC59>
	</soapenv:Body>
</soapenv:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient($settings);

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, preg_replace('/\?wsdl$/i', '', $use_ws_url), 'ultimoC59', SOAP_1_2);

			// wrap the response into an object
			$xmlResponse = $this->loadXmlSoap($response);

			// check for response errors
			$responseFault = $xmlResponse->getSoapRecursiveNsElement([
				['soap', true, 'Body'],
				['soap', true, 'Fault'],
			]);

			if (isset($responseFault->faultstring) && strlen((string) $responseFault->faultstring)) {
				// errors returned
				$fault_code = $responseFault->faultcode ?? 0;
				$fault_mess = $responseFault->faultstring ?? '';
				throw new Exception(sprintf("Errore (%s):\n%s.", $fault_code, $fault_mess), 500);
			}

			// access the response body
			$xmlBody = $xmlResponse->getSoapRecursiveNsElement([
				['soap', true, 'Body'],
				['ns2', true, 'ultimoC59Response'],
			]);

			if (!isset($xmlBody->return)) {
				// no operations returned
				throw new UnexpectedValueException(sprintf('Nessun movimento C59 trovato [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// build HTML response string
			$html = '';
			$html .= '<p class="successmade">Ultimo C59 disponibile nella base dati:</p>';
			$html .= '<pre>' . htmlspecialchars($xmlBody->formatXml() ?: $response ?: '') . '</pre>';

		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		return [
			'html' => $html,
			'ultimoC59Return' => $xmlBody->return,
		];
	}

	/**
	 * Establishes a SOAP connection with the remote WSDL and returns the client.
	 * Updates the internal properties according to settings related to the WS.
	 * 
	 * @param 	array 	$settings 	List of report settings for production or test mode.
	 * 
	 * @return 	SoapClient
	 * 
	 * @throws 	Exception
	 */
	protected function getWebServiceClient(array $settings = [])
	{
		// determine the WS url to use
		$use_ws_url = !empty($settings['test_mode']) ? $this->wsdl_url_test : $this->wsdl_url;

		try {
			return new SoapClient($use_ws_url, [
				'soap_version' => constant($this->soap_version),
			]);
		} catch (Throwable $e) {
			// prevent PHP fatal errors by catching and propagating them as Exceptions
			throw new Exception(sprintf('PHP Fatal Error: %s', $e->getMessage()), $e->getCode() ?: 500);
		}
	}

	/**
	 * Parses a SOAP XML message into a VBOXmlSoap object.
	 * 
	 * @param 	string 	$xmlMessage 	The SOAP XML message to load.
	 * 
	 * @return 	VBOXmlSoap
	 * 
	 * @throws 	Exception
	 */
	protected function loadXmlSoap($xmlMessage)
	{
		if (empty($xmlMessage)) {
			throw new Exception('Empty Soap XML message.', 500);
		}

		// suppress warning messages
		libxml_use_internal_errors(true);

		// parse the Soap XML message
		return simplexml_load_string($xmlMessage, VBOXmlSoap::class);
	}

	/**
	 * Registers the name to give to the file being exported.
	 * 
	 * @return 	void
	 */
	protected function registerExportFileName()
	{
		$pfromdate = VikRequest::getString('fromdate', '', 'request');

		$this->setExportCSVFileName($this->reportName . '-' . str_replace('/', '_', $pfromdate) . '.xml');
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
	 * Counts the number of occupied rooms on the given day.
	 * 
	 * @param 	int 	$day_ts 	The day timestamp (at midnight).
	 * @param 	array 	$listings 	Optional list of filtered listing IDs.
	 * 
	 * @return 	int 	Number of occupied rooms found.
	 */
	protected function countDayRoomsOccupied($day_ts, array $listings = [])
	{
		// get the timestamp at the last second of the day
		$day_ts = strtotime('23:59:59', $day_ts);

		$dbo = JFactory::getDbo();

		if ($listings) {
			// perform a more accurate query over the listings specified
			$dbo->setQuery(
				$dbo->getQuery(true)
					->select('COUNT(*)')
					->from($dbo->qn('#__vikbooking_orders', 'o'))
					->leftJoin($dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $dbo->qn('o.id') . ' = ' . $dbo->qn('or.idorder'))
					->where($dbo->qn('o.status') . ' = ' . $dbo->q('confirmed'))
					->where($dbo->qn('o.closure') . ' = 0')
					->where($dbo->qn('o.checkin') . ' < ' . (int) $day_ts)
					->where($dbo->qn('o.checkout') . ' > ' . (int) $day_ts)
					->where($dbo->qn('or.idroom') . ' IN (' . implode(', ', array_map('intval', $listings)) . ')')
			);
		} else {
			// quick query to fetch the total number of occupied rooms
			$dbo->setQuery(
				$dbo->getQuery(true)
					->select('SUM(' . $dbo->qn('roomsnum') . ')')
					->from($dbo->qn('#__vikbooking_orders'))
					->where($dbo->qn('status') . ' = ' . $dbo->q('confirmed'))
					->where($dbo->qn('closure') . ' = 0')
					->where($dbo->qn('checkin') . ' < ' . (int) $day_ts)
					->where($dbo->qn('checkout') . ' > ' . (int) $day_ts)
			);
		}

		return (int) $dbo->loadResult();
	}

	/**
     * Parses a report row into an associative list of key-value pairs.
     * 
     * @param   array   $row    The report row to parse.
     * 
     * @return  array           Associative list of row keys and values.
     */
    protected function getAssocRowData(array $row)
    {
        $row_data = [];

        foreach ($row as $field) {
            $field_val = $field['value'];
            if (!($field['no_export_callback'] ?? 0) && is_callable($field['callback_export'] ?? null)) {
                $field_val = $field['callback_export']($field_val);
            }
            $row_data[$field['key']] = $field_val;
        }

        return $row_data;
    }
}

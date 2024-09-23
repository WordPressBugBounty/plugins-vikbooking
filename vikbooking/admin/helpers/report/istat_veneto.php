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

class VikBookingReportIstatVeneto extends VikBookingReport
{

	private $from_ts;
	private $to_ts;
	public $bookings;
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 */
	public $defaultKeySort = 'checkin';
	/**
	 * Property 'defaultKeyOrder' is used by the View that renders the report.
	 */
	public $defaultKeyOrder = 'ASC';
	/**
	 * Property 'customExport' is used by the View to display custom export buttons.
	 * We should not define the property $exportAllowed.
	 */
	public $customExport = '';
	/**
	 * Debug mode is activated by passing the value 'e4j_debug' > 0
	 */
	private $debug;
	/**
	 * An associative array of regions (keys) and provinces (sub-arrays)
	 * 
	 * @var 	array
	 */
	private $map_regioni_prov = array(
		'Abruzzo' => array(
			'Aquila',
			'Chieti',
			'Pescara',
			'Teramo',
		),
		'Basilicata' => array(
			'Potenza',
			'Matera',
		),
		'Calabria' => array(
			'Reggio Calabria',
			'Catanzaro',
			'Crotone',
			'Vibo Valentia Marina',
			'Cosenza',
		),
		'Campania' => array(
			'Napoli',
			'Avellino',
			'Caserta',
			'Benevento',
			'Salerno',
		),
		'EmiliaRomagna' => array(
			'Bologna',
			'Reggio Emilia',
			'Parma',
			'Modena',
			'Ferrara',
			'Forlì Cesena',
			'Piacenza',
			'Ravenna',
			'Rimini',
		),
		'FriuliVeneziaGiulia' => array(
			'Trieste',
			'Gorizia',
			'Pordenone',
			'Udine',
		),
		'Lazio' => array(
			'Roma',
			'Rome',
			'Latina',
			'Frosinone',
			'Viterbo',
			'Rieti',
		),
		'Liguria' => array(
			'Genova',
			'Imperia',
			'La Spezia',
			'Savona',
		),
		'Lombardia' => array(
			'Milano',
			'Bergamo',
			'Brescia',
			'Como',
			'Cremona',
			'Mantova',
			'Monza e Brianza',
			'Pavia',
			'Sondrio',
			'Lodi',
			'Lecco',
			'Varese',
		),
		'Marche' => array(
			'Ancona',
			'Ascoli Piceno',
			'Fermo',
			'Macerata',
			'Pesaro Urbino',
		),
		'Molise' => array(
			'Campobasso',
			'Isernia',
		),
		'Piemonte' => array(
			'Torino',
			'Asti',
			'Alessandria',
			'Cuneo',
			'Novara',
			'Vercelli',
			'Verbania',
			'Biella',
		),
		'ValledAosta' => array(
			'Aosta',
		),
		'Puglia' => array(
			'Bari',
			'Barletta-Andria-Trani',
			'Brindisi',
			'Foggia',
			'Lecce',
			'Taranto',
		),
		'Sardegna' => array(
			'Cagliari',
			'Sassari',
			'Nuoro',
			'Oristano',
			'Carbonia Iglesias',
			'Medio Campidano',
			'Olbia Tempio',
			'Ogliastra',
		),
		'Sicilia' => array(
			'Palermo',
			'Agrigento',
			'Caltanissetta',
			'Catania',
			'Enna',
			'Messina',
			'Ragusa',
			'Siracusa',
			'Trapani',
		),
		'Toscana' => array(
			'Arezzo',
			'Massa Carrara',
			'Firenze',
			'Florence',
			'Livorno',
			'Grosseto',
			'Lucca',
			'Pisa',
			'Pistoia',
			'Prato',
			'Siena',
		),
		'Trento' => array(
			'Trento',
		),
		'Bolzano' => array(
			'Bolzano',
		),
		'Umbria' => array(
			'Perugia',
			'Terni',
		),
		'Veneto' => array(
			'Venezia',
			'Venice',
			'Belluno',
			'Padova',
			'Rovigo',
			'Treviso',
			'Verona',
			'Vicenza',
		)
	);

	private $map_prov_codes = array(

		"Torino" => "172",
		"Vercelli" => "078",
		"Novara" => "079",
		"Cuneo" => "080",
		"Asti" => "081",
		"Alessandria" => "082",
		"Aosta" => "083",
		"Imperia" => "106",
		"Savona" => "107",
		"Genova" => "108",
		"La Spezia" => "109",
		"Varese" => "084",
		"Como" => "085",
		"Sondrio" => "086",
		"Milano" => "087",
		"Bergamo" => "088",
		"Brescia" => "089",
		"Pavia" => "090",
		"Cremona" => "091",
		"Mantova" => "092",
		"Bolzano" => "093",
		"Trento" => "094",
		"Verona" => "095",
		"Vicenza" => "096",
		"Belluno" => "097",
		"Treviso" => "098",
		"Venezia" => "099",
		"Venice" => "099",
		"Padova" => "100",
		"Rovigo" => "101",
		"Udine" => "102",
		"Gorizia" => "103",
		"Trieste" => "104",
		"Piacenza" => "110",
		"Parma" => "111",
		"Reggio Emilia" => "112",
		"Modena" => "113",
		"Bologna" => "114",
		"Ferrara" => "115",
		"Ravenna" => "116",
		"Forlì Cesena" => "117",
		"Pesaro Urbino" => "129",
		"Ancona" => "130",
		"Macerata" => "131",
		"Ascoli Piceno" => "132",
		"Massa Carrara" => "118",
		"Lucca" => "119",
		"Pistoia" => "120",
		"Firenze" => "121",
		"Florence" => "121",
		"Livorno" => "122",
		"Pisa" => "123",
		"Arezzo" => "124",
		"Siena" => "125",
		"Grosseto" => "126",
		"Perugia" => "127",
		"Terni" => "128",
		"Viterbo" => "133",
		"Rieti" => "134",
		"Roma" => "135",
		"Rome" => "135",
		"Latina" => "136",
		"Frosinone" => "137",
		"Isernia" => "143",
		"Caserta" => "144",
		"Benevento" => "145",
		"Napoli" => "146",
		"Avellino" => "147",
		"Salerno" => "148",
		"Aquila" => "138",
		"Teramo" => "139",
		"Pescara" => "140",
		"Chieti" => "141",
		"Campobasso" => "142",
		"Foggia" => "149",
		"Bari" => "150",
		"Taranto" => "151",
		"Brindisi" => "152",
		"Lecce" => "153",
		"Potenza" => "154",
		"Matera" => "155",
		"Cosenza" => "156",
		"Catanzaro" => "157",
		"Reggio Calabria" => "158",
		"Trapani" => "159",
		"Palermo" => "160",
		"Messina" => "161",
		"Agrigento" => "162",
		"Caltanissetta" => "163",
		"Enna" => "164",
		"Catania" => "165",
		"Ragusa" => "166",
		"Siracusa" => "167",
		"Sassari" => "168",
		"Nuoro" => "169",
		"Cagliari" => "170",
		"Pordenone" => "105",
		"Oristano" => "171",
		"Biella" => "173",
		"Lecco" => "175",
		"Lodi" => "176",
		"Rimini" => "179",
		"Prato" => "180",
		"Crotone" => "177",
		"Vibo Valentia Marina" => "178",
		"Verbania" => "174",
		"Olbia Tempio" => "216",
		"Ogliastra" => "215",
		"Medio Campidano" => "214",
		"Carbonia Iglesias" => "213",
		"Monza e Brianza" => "218",
		"Fermo" => "217",
		"Barletta-Andria-Trani" => "219"
	);
	private $map_country_codes = array(
		'AUT' => 'Austria',
		'BEL' => 'Belgio',
		'HRV' => 'Croazia',
		'DNK' => 'Danimarca',
		'EGY' => 'Egitto',
		'FIN' => 'Finlandia',
		'FRA' => 'Francia',
		'DEU' => 'Germania',
		'GRC' => 'Grecia',
		'IRL' => 'Irlanda',
		'ISL' => 'Islanda',
		'LUX' => 'Lussemburgo',
		'NOR' => 'Norvegia',
		'NLD' => 'PaesiBassi',
		'POL' => 'Polonia',
		'PRT' => 'Portogallo',
		'GBR' => 'RegnoUnito',
		'CZE' => 'RepubblicaCeca',
		'RUS' => 'Russia',
		'SVK' => 'Slovacchia',
		'SVN' => 'Slovenia',
		'ESP' => 'Spagna',
		'SWE' => 'Svezia',
		'CHE' => 'Svizzera',
		'LIE' => 'Svizzera',
		//
		'TUR' => 'Turchia',
		'HUN' => 'Ungheria',
		'BGR' => 'Bulgaria',
		'ROM' => 'Romania',
		'EST' => 'Estonia',
		'CYP' => 'Cipro',
		'LTU' => 'Lituania',
		'LVA' => 'Lettonia',
		'MLT' => 'Malta',
		'UKR' => 'Ucraina',
		'CAN' => 'Canada',
		'USA' => 'StatiUniti',
		'MEX' => 'Messico',
		'VEN' => 'Venezuela',
		'BRA' => 'Brasile',
		'ARG' => 'Argentina',
		'CHN' => 'Cina',
		'JPN' => 'Giappone',
		'KOR' => 'CoreaSud',
		'IND' => 'India',
		'ISR' => 'Israele',
		'ZAF' => 'Sudafrica',
		'AUS' => 'Australia',
		'NZL' => 'NuovaZelanda',
	);
	/**
	 * An associative array of country 3-char codes (keys) and ISTAT "tipo" codes (values).
	 * Only for those who live outside Italy
	 * 
	 * @var 	array
	 */
	public $map_country_codes_transfer = array(
		'Austria' => '032',
		'Belgio' => '011',
		'Croazia' => '053',
		'Danimarca' => '017',
		'Egitto' => '043',
		'Finlandia' => '023',
		'Francia' => '010',
		'Germania' => '014',
		'Grecia' => '018',
		'Irlanda' => '016',
		'Islanda' => '069',
		'Lussemburgo' => '012',
		'Norvegia' => '028',
		'PaesiBassi' => '013',
		'Polonia' => '027',
		'Portogallo' => '019',
		'RegnoUnito' => '015',
		'RepubblicaCeca' => '052',
		'Russia' => '062',
		'Slovacchia' => '063',
		'Slovenia' => '064',
		'Spagna' => '020',
		'Svezia' => '022',
		'Svizzera' => '024',
		'Liechtenstein' => '036',
		// Liechtenstein = Svizzera. We keep it here for the 3-char country code
		//
		'Turchia' => '025',
		'Ungheria' => '028',
		'Bulgaria' => '030',
		'Romania' => '029',
		'Estonia' => '054',
		'Cipro' => '210',
		'Lituania' => '059',
		'Lettonia' => '058',
		'Malta' => '211',
		'Ucraina' => '067',
		'Canada' => '033',
		'StatiUniti' => '034',
		'Messico' => '035',
		'Venezuela' => '036',
		'Brasile' => '037',
		'Argentina' => '038',
		'Cina' => '071',
		'Giappone' => '040',
		'CoreaSud' => '072',
		'India' => '212',
		'Israele' => '624',
		'Sudafrica' => '045',
		'Australia' => '041',
		'NuovaZelanda' => '804',
		'AltriEuropa' => '031',
		'AltriNordAmerica' => '220',
		'AltriSudAmerica' => '039',
		'AltriAsiaOccid' => '044',
		'AltriAsia' => '074',
		'AltriAfricaMed' => '075',
		'AltriAfrica' => '076',
		'AltriOceania' => '221',
		'NonSpecificato' => 	'077' 
		

	);
	/**
	 * An associative array of valid country types (keys)
	 * and readable country types (values).
	 * 
	 * @var 	array
	 */
	private $map_country_others = array(
		'AltriEuropa' => 'Albania, Andorra, Bielorussia, Bosnia-Erzegovina, Faeroe Islands (DK), Gibilterra (UK), Guernsey, Isle of Man,
		 			  	  Jersey, Kosovo, Macedonia, Moldova, Monaco, Montenegro, San Marino, Serbia, Stato della città del Vaticano, Svalbard e Jan
		 				  Mayen, Bonaire, Saint Eustatius and Saba (NL).',
		'AltriNordAmerica' => 'Bermuda, Greenland, Saint Pierre e Miquelon',
		'AltriSudAmerica' => 'Antigua e Barbuda, Bahamas, Barbados, Belize, Bolivia, Chile, Colombia, Costa Rica, Cuba, Domenica, Dominican Republic, Ecuador, El Salvador, Jamaica, Grenada, Guatemala, Guyana, Haiti, Honduras, Nicaragua, Panama, Paraguay, Peru, Saint Kitts e Nevis, Saint Lucia, Saint Vincent and Grenadine, Suriname, Trinidad and Tobago, Uruguay',
		'AltriAsiaOccid' => 'Saudi Arabia, Armenia, Azerbaigian, Bahrein, United Arab Emirates, Georgia, Jordan, Iran, Iraq, Kuwait, Lebanon, Oman, Qatar, Siria, Palestina, Yemen',
		'AltriAsia' => 'Altri Asia',
		'AltriAfricaMed' => 'Libia, Tunisia, Algeria, Morocco',
		'AltriAfrica' => 'Altri Africa',
		'AltriOceania' => 'Fiji, Kiribati, Isole Marshall, Micronesia, Nauru, Palau, Papua New Guinea, Solomon Islands, Samoa, Tonga, Tuvalu, Vanuatu',
		'NonSpecificato' => 'Sconosciuto',
	);

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = 'ISTAT Veneto';
		$this->reportFilters = array();

		$this->cols = array();
		$this->rows = array();
		$this->footerRow = array();

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

		//load the jQuery UI Datepicker
		$this->loadDatePicker();

		//custom export button
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadIstatDoc();" class="vbcsvexport"><i class="fa fa-download"></i> <span>Genera Documento Dati ISTAT</span></a>';

		// helper filters
		$hidden_vals = '<div id="vbo-report-istat-hidden" style="display: none;">';
		// Italian filter
		$hidden_vals .= '	<div id="vbo-report-istat-italia" class="vbo-report-istat-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-italia" onchange="vboReportChosenItalia(this);"><option value=""></option>';
		$italias = array(
			1 => 'Cittadino Italiano',
			0 => 'Cittadino Estero'
		);
		foreach ($italias as $code => $v) {
			$hidden_vals .= '	<option value="'.$code.'">'.$v.'</option>'."\n";
		}
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';
		// tipo (provenienza) filter
		$hidden_vals .= '	<div id="vbo-report-istat-tipo" class="vbo-report-istat-selcont" style="display: none;">';
		$hidden_vals .= '		<select id="choose-tipo" onchange="vboReportChosenTipo(this);"><option value=""></option>';
		foreach ($this->map_regioni_prov as $region => $cities) {
			$hidden_vals .= '		<optgroup label="Italia - '.$region.'">';
			foreach ($cities as $city) {
				$hidden_vals .= '		<option value="'.$city.'">'.$city.'</option>';
			}
			$hidden_vals .= '		</optgroup>';
		}
		// sort all foreign countries in a clone to not lose keys. 
		$map_country_codes = array_merge(array_unique($this->map_country_codes));
		sort($map_country_codes);
		//
		$hidden_vals .= '		<optgroup label="Estero - Nazioni">';
		foreach ($map_country_codes as $countrytipo) {
			$hidden_vals .= '		<option value="'.$countrytipo.'">'.$countrytipo.'</option>';
		}
		$hidden_vals .= '		</optgroup>';
		$hidden_vals .= '		<optgroup label="Estero - Altri">';
		foreach ($this->map_country_others as $countrytipo => $countryother) {
			$hidden_vals .= '		<option value="'.$countrytipo.'">'.$countryother.'</option>';
		}
		$hidden_vals .= '		</optgroup>';
		$hidden_vals .= '		</select>';
		$hidden_vals .= '	</div>';
		//
		$hidden_vals .= '</div>';

		//From Date Filter
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

		// Filtro Codice Struttura
		$pcodstru = VikRequest::getString('codstru', '', 'request');
		$filter_opt = array(
			'label' => '<label for="codstru">Codice Struttura</label>',
			'html' => '<input type="text" id="codstru" name="codstru" value="'.$pcodstru.'" size="10" />',
			'type' => 'text',
			'name' => 'codstru'
		);
		array_push($this->reportFilters, $filter_opt);

		//jQuery code for the datepicker calendars and select2
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$js = 'var reportActiveCell = null, reportObj = {};
		jQuery(document).ready(function() {
			jQuery(".vbo-report-datepicker:input").datepicker({
				maxDate: 0,
				dateFormat: "'.$this->getDateFormat('jui').'",
				onSelect: vboReportCheckDates
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			'.(!empty($ptodate) ? 'jQuery(".vbo-report-datepicker-to").datepicker("setDate", "'.$ptodate.'");' : '').'
			//prepare filler helpers
			jQuery("#vbo-report-istat-hidden").children().detach().appendTo(".vbo-info-overlay-report");
			jQuery("#choose-italia").select2({placeholder: "- Seleziona Nazionalità -", width: "200px"});
			jQuery("#choose-tipo").select2({placeholder: "- Seleziona Regione o Nazione -", width: "400px"});
			//click events
			jQuery(".vbo-report-load-nazione").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-istat-selcont").hide();
				jQuery("#vbo-report-istat-italia").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-cittadinanza").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-istat-selcont").hide();
				jQuery("#vbo-report-istat-tipo").show();
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
		function vboReportChosenItalia(italia) {
			var c_code = italia.value;
			var c_val = italia.options[italia.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					reportObj[nowindex].italia = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-italia").val("").select2("data", null, false);
		}
		function vboReportChosenTipo(tipo) {
			var c_code = tipo.value;
			var c_val = tipo.options[tipo.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					// we need to display the value of the tipo, not the option text
					jQuery(reportActiveCell).addClass("vbo-report-load-elem-filled").find("span").text(c_code);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {};
					}
					reportObj[nowindex].tipo = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-tipo").val("").select2("data", null, false);
		}
		//download function
		function vboDownloadIstatDoc() {
			// check if empty values have been filled in or ask for confirmation
			var missing_italia = jQuery(".vbo-report-load-nazione").not(".vbo-report-load-elem-filled").length;
			var missing_tipo = jQuery(".vbo-report-load-cittadinanza").not(".vbo-report-load-elem-filled").length;
			if ((missing_italia > 0 || missing_tipo > 0) && !confirm("Qualche dato mancante evidenziato in rosso non è stato compilato. Vuoi continuare?")) {
				return false;
			} else if (!confirm("Sei sicuro di aver compilato tutti i dati per il documento?")) {
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
		//Input fields and other vars
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$pcodstru = VikRequest::getString('codstru', '', 'request');
		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';
		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();
		if (empty($ptodate)) {
			$ptodate = $pfromdate;
		}
		
		//Get dates timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		$this->from_ts = $from_ts;
		$this->to_ts = $to_ts;
		if (empty($pfromdate) || empty($from_ts) || empty($to_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}
		if (empty($pcodstru)) {
			$this->setError('Inserisci il codice della tua Struttura.<br/>Si tratta di un codice univoco di identificazione che ti viene assegnato dall\'Amministrazione competente.');
			return false;
		}

		//Query to obtain the records (all check-ins within the dates filter)
		$records = array();
		$q = "SELECT `o`.`id`,`o`.`custdata`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,".
			"`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`t_first_name`,`or`.`t_last_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,".
			"`co`.`idcustomer`,`co`.`pax_data`,`c`.`first_name`,`c`.`last_name`,`c`.`country` AS `customer_country`,`c`.`city`,`c`.`doctype`,`c`.`docnum`,`c`.`gender`,`c`.`bdate`,`c`.`pbirth`,`cy`.`country_name`,".
			"(SELECT `h`.`dt` FROM `#__vikbooking_orderhistory` AS `h` WHERE `h`.`idorder`=`o`.`id` AND `h`.`type`='RP' AND `h`.`descr`=".$this->dbo->quote($this->reportName)." ORDER BY `h`.`dt` DESC LIMIT 1) AS `history_last` ".
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` ".
			"LEFT JOIN `#__vikbooking_customers_orders` AS `co` ON `co`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_customers` AS `c` ON `c`.`id`=`co`.`idcustomer` LEFT JOIN `#__vikbooking_countries` AS `cy` ON `cy`.`country_3_code`=`c`.`country` ".
			"WHERE `o`.`status`='confirmed' AND `o`.`closure`=0 AND ((`o`.`checkin`>=".$from_ts." AND `o`.`checkin`<=".$to_ts.") OR (`o`.`checkout`>=".$from_ts." AND `o`.`checkout`<=".$to_ts.") OR (`o`.`checkin`<=".$from_ts." AND `o`.`checkout`>=".$to_ts.")) ".
			"ORDER BY `o`.`checkin` ASC, `o`.`id` ASC;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows() > 0) {
			$records = $this->dbo->loadAssocList();
		}
		if (!count($records)) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			return false;
		}



		//nest records with multiple rooms booked inside sub-array
		$bookings = array();

		foreach ($records as $v) {
			if (!isset($bookings[$v['id']])) {
				$bookings[$v['id']] = array();
			}
			// for bc, if the country is defined at booking level, set it also for the customer
			if (empty($v['customer_country']) && !empty($v['country'])) {
				$v['customer_country'] = $v['country'];
			}
			//calculate the from_ts and to_ts values for later comparison
			$in_info = getdate($v['checkin']);
			$out_info = getdate($v['checkout']);
			$v['from_ts'] = mktime(0, 0, 0, $in_info['mon'], $in_info['mday'], $in_info['year']);
			$v['to_ts'] = mktime(23, 59, 59, $out_info['mon'], ($out_info['mday'] - 1), $out_info['year']);
			//
			array_push($bookings[$v['id']], $v);
		}
		$this->bookings = $bookings;

		//define the columns of the report
		$this->cols = array(
			//IdSWH (identificativo posizione - we use the booking ID)
			array(
				'key' => 'idswh',
				'sortable' => 1,
				'label' => 'Codice IDSWH',
				'tip' => 'Questo è il codice identificativo della trasmissione del dato verso l\'ISTAT, ed è uguale all\'ID della prenotazione nel sistema.',
				'export_name' => 'IdSWH'
			),
			//Codice Struttura
			array(
				'key' => 'codstru',
				'label' => 'Codice Struttura',
				'tip' => 'Il codice della tua struttura che l\'Amministrazione competente ti ha assegnato. Il dato verrà comunicato all\'ISTAT.',
				'export_name' => 'Codice'
			),
			//customer
			array(
				'key' => 'customer',
				'sortable' => 1,
				'label' => 'Cliente',
				'tip' => 'I nominativi dei clienti non verranno comunicati all\'ISTAT in quanto non necessari.',
				'ignore_export' => 1
			),
			//checkin
			array(
				'key' => 'checkin',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Arrivo'
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
			//italia
			array(
				'key' => 'italia',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Italia',
				'tip' => 'Cittadino Italiano o Estero. Assicurati di fare la giusta selezione in caso di dati mancanti nel sistema (?) per certe prenotazioni.',
			),
			//tipo (provenienza)
			array(
				'key' => 'tipo',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Provenienza',
				'tip' => 'Codice di Provenienza del cliente. Assicurati di fare la giusta selezione in caso di dati mancanti nel sistema (?) per certe prenotazioni.',
				'export_name' => 'Tipo'
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
				'export_name' => 'Quantita'
			),
			//occupazione (numero di camere prenotate)
			array(
				'key' => 'roomsbooked',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Occupazione',
				'tip' => 'Questo valore indica il numero di camere occupate da ogni prenotazione, ed è un dato che verrà comunicato all\'ISTAT.',
			),
			//camere (numero di camere totali)
			array(
				'key' => 'totrooms',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => 'Camere',
				'tip' => 'Questo valore indica il numero totale di camere nella tua struttura, ed è un dato che deve essere comunicato all\'ISTAT. Viene calcolato con una somma delle unità di tutte le camere attualmente Pubblicate nel sistema.',
			),
			
		);

		// total rooms units ("camere")
		$total_rooms_units = $this->countRooms();

		//loop over the bookings to build the rows of the report
		foreach ($bookings as $gbook) {
			$guestsnum = 0;
			foreach ($gbook as $book) {
				$guestsnum += $book['adults'] + $book['children'];
			}
			$country = '';
			if (!empty($gbook[0]['country'])) {
				$country = $gbook[0]['country'];
			} elseif (!empty($gbook[0]['customer_country'])) {
				$country = $gbook[0]['customer_country'];
			}
			$history_last = $gbook[0]['history_last'];
			$tipo_provenienza = $this->guessTipoProvenienza($gbook[0]);
			//push fields in the rows array as a new row
			array_push($this->rows, array(
				array(
					'key' => 'idswh',
					'callback' => function ($val) use ($history_last) {
						if (!empty($history_last)) {
							$tip = VikBooking::getVboApplication()->createPopover(array('title' => 'Data ultimo invio', 'content' => $history_last.'. Puoi comunque ritrasmettere questa informazione, e se cambia verrà aggiornata.'));
							return '<a href="index.php?option=com_vikbooking&task=editorder&cid[]='.$val.'" target="_blank">'.$tip.' '.$val.'</a>';
						}
						return '<a href="index.php?option=com_vikbooking&task=editorder&cid[]='.$val.'" target="_blank"><i class="fa fa-external-link"></i> '.$val.'</a>';
					},
					'callback_export' => function ($val) {
						return $val;
					},
					'value' => $gbook[0]['id']
				),
				array(
					'key' => 'codstru',
					'value' => $pcodstru
				),
				array(
					'key' => 'customer',
					'callback' => function ($val) use ($country) {
						if (file_exists(VBO_ADMIN_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'countries'.DIRECTORY_SEPARATOR.$country.'.png')) {
							return $val.'<img src="'.VBO_ADMIN_URI.'resources/countries/'.$country.'.png" title="'.$country.'" class="vbo-country-flag vbo-country-flag-left" />';
						}
						return $val;
					},
					'value' => $gbook[0]['first_name'] . ' ' . $gbook[0]['last_name'],
					'ignore_export' => 1
				),
				array(
					'key' => 'checkin',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($df, $datesep) {
						return date(str_replace("/", $datesep, $df), $val);
					},
					'callback_export' => function ($val) use ($df, $datesep) {
						return date('Ymd', $val);
					},
					'value' => $gbook[0]['checkin']
				),
				array(
					'key' => 'checkout',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($df, $datesep) {
						return date(str_replace("/", $datesep, $df), $val);
					},
					'callback_export' => function ($val) use ($df, $datesep) {
						return date('Ymd', $val);
					},
					'value' => $gbook[0]['checkout']
				),
				array(
					'key' => 'italia',
					'attr' => array(
						'class="center'.(empty($gbook[0]['customer_country']) ? ' vbo-report-load-nazione' : '').'"'
					),
					'callback' => function ($val) {
						if ($val < 0) {
							// empty country
							return '?';
						}
						if ($val == 1) {
							// Italian customer
							return 'Cittadino Italiano';
						}
						// non Italian customer
						return 'Cittadino Estero';
					},
					'callback_export' => function ($val) {
						return $val;
					},
					'value' => (empty($gbook[0]['customer_country']) ? -1 : (substr(strtoupper($gbook[0]['customer_country']), 0, 3) == 'ITA' ? 1 : 0))
				),
				array(
					'key' => 'tipo',
					'attr' => array(
						'class="center'.($tipo_provenienza == -1 ? ' vbo-report-load-cittadinanza' : '').'"'
					),
					'callback' => function ($val) {
						if ($val < 0) {
							// empty value
							return '?';
						}
						return $val;
					},
					'callback_export' => function ($val) {
						return $val;
					},
					'value' => $tipo_provenienza
				),
				array(
					'key' => 'guestsnum',
					'attr' => array(
						'class="center"'
					),
					'value' => $guestsnum
				),
				array(
					'key' => 'roomsbooked',
					'attr' => array(
						'class="center"'
					),
					'value' => count($gbook)
				),
				array(
					'key' => 'totrooms',
					'attr' => array(
						'class="center"'
					),
					'value' => $total_rooms_units
				),
				
			));
		}

		//sort rows
		$this->sortRows($pkrsort, $pkrorder);

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
	 * Attempts to guess the column 'tipo' (provenienza)
	 * depending on the country and city given. For Italians,
	 * the name of the region is returned (if any, given the city).
	 * For foreigners, the country type is returned.
	 * 
	 * @param 	array 	the booking record
	 * 
	 * @return 	mixed 	string to use for the column 'tipo', -1 if empty.
	 */
	private function guessTipoProvenienza($booking)
	{
		if (empty($booking['customer_country'])) {
			// unable to proceed when the country is missing (-1).
			return -1;
		}

		// uppercase 3-char code of the country only
		$country3 = substr(strtoupper($booking['customer_country']), 0, 3);

		if ($country3 == 'ITA') {
			// Italian customer
			if (!empty($booking['city'])) {
				foreach ($this->map_regioni_prov as $tipo => $cities) {
					foreach ($cities as $city) {
						if (stripos($city, $booking['city']) !== false) {
							// region was found
							return $city;
						}
					}
				}
			}
			// empty city or no city found in the map
			return -1;
		}

		// foreigner customer
		if (isset($this->map_country_codes[$country3])) {
			// country name found for 'tipo' column
			return $this->map_country_codes[$country3];
		}
		// check the 'country_name' in 'other countries'
		if (!empty($booking['country_name'])) {
			foreach ($this->map_country_others as $tipo => $countries) {
				if (stripos($countries, $booking['country_name']) !== false) {
					// the name of the country taken from the 3-char code matches with an 'other country'. Guessing makes sense.
					return $tipo;
				}
			}
		}

		// nothing found
		return -1;
	}

	/**
	 * Generates the report columns and rows, then it outputs a CSV file
	 * for download. In case of errors, the process is not terminated (exit)
	 * to let the View display the error message.
	 * We use customExport() rather than exportCSV() only because we need a
	 * different download button rather than the classic "Export as CSV".
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
		$pcodstru = VikRequest::getString('codstru', '', 'request');
		// manual values in filler
		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? json_decode($pfiller, true) : array();
		$pfiller = !is_array($pfiller) ? array() : $pfiller;

		//Debug
		if ($this->debug) {
			$this->setError('<pre>'.print_r($pfiller, true).'</pre><br/>');
			return false;
		}
		//
		if (empty($ptodate)) {
			$ptodate = $pfromdate;
		}

		// pool of booking IDs to update their history
		$booking_ids = array();
		// update the history for all bookings affected
		foreach ($booking_ids as $bid) {
			VikBooking::getBookingHistoryInstance()->setBid($bid)->store('RP', $this->reportName);
		}
		$txt = "";
		$numOccupiedrooms = 0; 
		
		$date = new DateTime();
		$ts = $date->getTimestamp();
		
		$map_country_codes_transfer = array_merge(array_unique($this->map_country_codes_transfer));
		sort($map_country_codes_transfer);

		$map_prov_codes = array_merge(array_unique($this->map_prov_codes));
		sort($map_prov_codes);
		$arr = array(
			'italia' => array(),
			'estero' => array(),
			'idres'  => array()

		);

		$arrivi = 0;
		$partenze = 0;
		$presenti = 0;
		
		foreach ($this->rows as $ind => $row) {
			
			$italia = 0;
			$arrivi = 0;
			$partenze = 0;
			$presenti = 0;

			$idswh = 0;

			foreach ($row as $field) {
				$arrcode = 0;

				
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
				if($field['key'] == 'idswh'){
					$idswh = $field['value'];
				}
				if (isset($field['callback_export'])) {
					$field['callback'] = $field['callback_export'];
				}
				$export_value = isset($field['callback']) && is_callable($field['callback']) ? $field['callback']($field['value']) : $field['value'];
				
				
				if ($field['key'] == 'italia') {
					if ($export_value == "1") {
						$italia = 1;
					} 	
				}
				
				if ($field['key'] == 'tipo') {
					$arrcode = 0;

					if (isset($this->map_country_codes_transfer[$export_value])) {
						$arrcode = $this->map_country_codes_transfer[$export_value];
					} elseif (isset($this->map_prov_codes[$export_value])) {
						$arrcode = $this->map_prov_codes[$export_value];
					}

					if ($italia == 1 && !isset($arr['italia'][$arrcode])) {
						$arr['italia'][$arrcode] = array(
							'partiti' => 0,
							'arrivi' => 0,
							'presenti' => 0,
							
						);
					} elseif(!isset($arr['estero'][$arrcode])) {
						$arr['estero'][$arrcode] = array(
							'partiti' => 0,
							'arrivi' => 0,
							'presenti' => 0,	
							

						);
					}
					foreach ($this->bookings as $gbook) {
							$guestsnum = 0;
						foreach ($gbook as $book) {
							$guestsnum += $book['adults'] + $book['children'];
						}
						
						
						if($italia == 1){
							if($idswh == $gbook[0]['id'] && $arrcode == $this->map_prov_codes[$export_value] && !in_array($gbook[0]['id'], $arr['idres'])){
								array_push($arr['idres'], $gbook[0]['id']);
								if (date('Y-m-d',$gbook[0]['checkin']) == date('Y-m-d',$this->from_ts) || date('Y-m-d',$gbook[0]['checkin']) == date('Y-m-d',$this->to_ts) ) {
									$arr['italia'][$arrcode]['arrivi'] += $guestsnum;
									$arr['italia'][$arrcode]['presenti'] += $guestsnum;
									$arrivi = $arrivi + $guestsnum;
									$presenti = $presenti + $guestsnum;
								} elseif (date('Y-m-d',$gbook[0]['checkout']) == date('Y-m-d',$this->from_ts)|| date('Y-m-d',$gbook[0]['checkout']) == date('Y-m-d',$this->to_ts) ) {
									$arr['italia'][$arrcode]['partiti'] += $guestsnum;
									$partenze = $partenze + $guestsnum;
								} else {
									$arr['italia'][$arrcode]['presenti'] += $guestsnum;
									$presenti = $presenti + $guestsnum;
								}
							}
						}
						else{
							if ($idswh == $gbook[0]['id'] && $arrcode == $this->map_country_codes_transfer[$export_value] && !in_array($gbook[0]['id'], $arr['idres'])){
								array_push($arr['idres'], $gbook[0]['id']);
								if (date('Y-m-d',$gbook[0]['checkin']) == date('Y-m-d',$this->from_ts) || date('Y-m-d',$gbook[0]['checkin']) == date('Y-m-d',$this->to_ts) ) {
									$arr['estero'][$arrcode]['arrivi'] += $guestsnum;
									$arr['estero'][$arrcode]['presenti'] += $guestsnum;
									$arrivi = $arrivi + $guestsnum;
									$presenti = $presenti + $guestsnum;
								} elseif (date('Y-m-d',$gbook[0]['checkout']) == date('Y-m-d',$this->from_ts)|| date('Y-m-d',$gbook[0]['checkout']) == date('Y-m-d',$this->to_ts) ) {
									$arr['estero'][$arrcode]['partiti'] += $guestsnum;
									$partenze = $partenze + $guestsnum;
								} else {
									$arr['estero'][$arrcode]['presenti'] += $guestsnum;
									$presenti = $presenti + $guestsnum;
								}
							}
						}
					}	
					
				}
				if ($field['key'] == 'roomsbooked') {
					if($partenze == 0){
						if($presenti != 0 ||$arrivi != 0){
							$numOccupiedrooms = $numOccupiedrooms + $export_value;
						}
					}
				}

			}
		}
	
		$codstr = '';
		foreach ($arr['italia'] as $key => $value) {
			
			if($arr['italia'][$key]['arrivi'] > 0 || $arr['italia'][$key]['partiti'] > 0){	
			
				if ($arr['italia'][$key]['presenti'] < 0){
					$arr['italia'][$key]['presenti'] = 0;
				}	
				if (strlen($this->rows['0']['0']['value']) > 11) { 
					$codstr = substr($this->rows['0']['0']['value'], 2);
					$txt .= "X".$codstr;
				} else {
					$txt .= $this->rows['0']['0']['value'];
				}
				$txt .= substr($pfromdate,0,2)."/".substr($pfromdate,3,2)."/".substr($pfromdate,6,4). " " ;
				
				$txt .= "00".$key;
				
				if ($arr['italia'][$key]['arrivi'] < 10) {
					$txt .= "0000".$arr['italia'][$key]['arrivi'];
				} elseif ($arr['italia'][$key]['arrivi'] < 100){
					$txt .= "000".$arr['italia'][$key]['arrivi'];
				} elseif ($arr['italia'][$key]['arrivi'] < 1000){
					$txt .= "00".$arr['italia'][$key]['arrivi'];
				} elseif ($arr['italia'][$key]['arrivi'] < 10000){
					$txt .= "0".$arr['italia'][$key]['arrivi'];
				} else {
					$txt .= $arr['italia'][$key]['arrivi'];
				}
				if ($arr['italia'][$key]['partiti'] < 10) {
					$txt .= "0000".$arr['italia'][$key]['partiti'];
				} elseif ($arr['italia'][$key]['partiti'] < 100){
					$txt .= "000".$arr['italia'][$key]['partiti'];
				} elseif ($arr['italia'][$key]['partiti'] < 1000){
					$txt .= "00".$arr['italia'][$key]['partiti'];
				} elseif ($arr['italia'][$key]['partiti'] < 10000){
					$txt .= "0".$arr['italia'][$key]['partiti'];
				} else {
					$txt .= $arr['italia'][$key]['partiti'];
				}
				$txt .= "\n";
			}
		}

		foreach ($arr['estero'] as $key => $value) {

			if ($arr['estero'][$key]['arrivi'] > 0 || $arr['estero'][$key]['partiti'] > 0) {		
			
				if ($arr['estero'][$key]['presenti'] < 0) {
					$arr['estero'][$key]['presenti'] = 0;
				}	
				if (strlen($this->rows['0']['0']['value']) > 11) { 
					$codstr = substr($this->rows['0']['0']['value'], 2);
					$txt .= "X".$codstr;
				} else {
					$txt .= $this->rows['0']['0']['value'];
				}
				$txt .= date('d/m/Y'). " " ;
				
				$txt .= "00".$key;
			
				if ($arr['estero'][$key]['arrivi'] < 10) {
					$txt .= "0000".$arr['estero'][$key]['arrivi'];
				} elseif ($arr['estero'][$key]['arrivi'] < 100) {
					$txt .= "000".$arr['estero'][$key]['arrivi'];
				} elseif ($arr['estero'][$key]['arrivi'] < 1000) {
					$txt .= "00".$arr['estero'][$key]['arrivi'];
				} elseif ($arr['estero'][$key]['arrivi'] < 10000) {
					$txt .= "0".$arr['estero'][$key]['arrivi'];
				} else {
					$txt .= $arr['estero'][$key]['arrivi'];
				}
				if ($arr['estero'][$key]['partiti'] < 10) {
					$txt .= "0000".$arr['estero'][$key]['partiti'];
				} elseif ($arr['estero'][$key]['partiti'] < 100) {
					$txt .= "000".$arr['estero'][$key]['partiti'];
				} elseif ($arr['estero'][$key]['partiti'] < 1000) {
					$txt .= "00".$arr['estero'][$key]['partiti'];
				} elseif ($arr['estero'][$key]['partiti'] < 10000) {
					$txt .= "0".$arr['estero'][$key]['partiti'];
				} else {
					$txt .= $arr['estero'][$key]['partiti'];
				}
				$txt .= "\n";
			}
		}
		if (!empty($txt)) {

			if (strlen($this->rows['0']['0']['value']) > 11) { 
				$codstr = substr($this->rows['0']['0']['value'], 2);
				$txt .= "X".$codstr;
			} else {
				$txt .= $this->rows['0']['0']['value'];
			}
			$txt .= substr($pfromdate,0,2)."/".substr($pfromdate,3,2)."/".substr($pfromdate,6,4). " " ;

			$txt .= "NNNNN";
			if ($numOccupiedrooms<10) {
				$txt .= "0000".$numOccupiedrooms;
			} elseif ($numOccupiedrooms<100) {
				$txt .= "000".$numOccupiedrooms;
			} elseif ($numOccupiedrooms<1000) {
				$txt .= "00".$numOccupiedrooms;
			} elseif ($numOccupiedrooms<10000) {
				$txt .= "0".$numOccupiedrooms;
			} else {
				$txt .= $numOccupiedrooms;
			}

			$txt .= "\n";
		} else {
			if (strlen($this->rows['0']['0']['value']) > 11) { 
				$codstr = substr($this->rows['0']['0']['value'], 2);
				$txt .= "X".$codstr;
			} else {
				$txt .= $this->rows['0']['0']['value'];
			}
			$txt .= substr($pfromdate, 0, 2)."/".substr($pfromdate, 3, 2)."/".substr($pfromdate, 6, 4). " -1" ;
		}

		/**
		 * Custom export method supports a custom export handler, if previously set.
		 * 
		 * @since 	1.16.1 (J) - 1.6.1 (WP)
		 */
		if ($this->hasExportHandler()) {
			// write data onto the custom file handler
			$fp = $this->getExportCSVHandler();
			fwrite($fp, $txt);
			fclose($fp);

			return true;
		}

		if (!empty($codstr)) {
			$filename = "X" . substr($pfromdate, 0, 2) . substr($pfromdate, 3, 2) . substr($pfromdate, 6, 4) . '.txt';
		} else {
			$filename = $this->rows['0']['0']['value'] . substr($pfromdate, 0, 2) . substr($pfromdate, 3, 2) . substr($pfromdate, 6, 4) . '.txt';
		}

		// force text file download
		header("Content-type: text/plain");
		header("Cache-Control: no-store, no-cache");
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		echo $txt;

		exit;
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

		$this->setExportCSVFileName("X" . substr($pfromdate, 0, 2) . substr($pfromdate, 3, 2) . substr($pfromdate, 6, 4) . '.txt');
	}
}

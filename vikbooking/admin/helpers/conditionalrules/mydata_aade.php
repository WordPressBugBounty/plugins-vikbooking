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
 * Class handler for conditional rule "myDATA AADE" (electronic invoices driver for Greece).
 * This Conditional Text Rule will include a QR Code image and the Invoice Mark number.
 * 
 * @since 	1.16.7 (J) - 1.6.7 (WP)
 */
class VikBookingConditionalRuleMydataAade extends VikBookingConditionalRule
{
	/**
	 * @var  object
	 */
	protected $transmission_data = null;

	/**
	 * @var  array
	 */
	protected $correlated_invoice_data = [];

	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = 'myDATA - ΑΑΔΕ Greece';
		$this->ruleDescr = 'Include QR Code and Mark for the e-invoices generated through ΑΑΔΕ (Greece).';
		$this->ruleId = basename(__FILE__);
	}

	/**
	 * Displays the rule parameters.
	 * 
	 * @return 	void
	 */
	public function renderParams()
	{
		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label">Choose the type of invoice</div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('invoice_type'); ?>">
					<option value=""></option>
					<option value="main"<?php echo $this->getParam('invoice_type', '') == 'main' ? ' selected="selected"' : ''; ?>>Reservation main invoice</option>
					<option value="envfee"<?php echo $this->getParam('invoice_type', '') == 'envfee' ? ' selected="selected"' : ''; ?>>Environmental fee invoice</option>
				</select>
				<span class="vbo-param-setting-comment">Choose for which type of invoice the elements should be included. It is necessary to create a different Conditional Text Rule to cover both invoice types.</span>
			</div>
		</div>
		<div class="vbo-param-container">
			<div class="vbo-param-label">QR Code</div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->printYesNoButtons($this->inputName('qr_code'), JText::translate('VBYES'), JText::translate('VBNO'), (int)$this->getParam('qr_code', 0), 1, 0); ?>
				<span class="vbo-param-setting-comment">You can optionally use the tag <strong onclick="vboMydataAadeAddContentEditor('{mydata_aade_qrcode_img}');" style="cursor: pointer;">{mydata_aade_qrcode_img}</strong> if you would like to place the QR Code PNG image on a specific section of the message.</span>
			</div>
		</div>
		<div class="vbo-param-container">
			<div class="vbo-param-label">QR Code width (px)</div>
			<div class="vbo-param-setting">
				<input type="number" name="<?php echo $this->inputName('qr_code_width'); ?>" value="<?php echo $this->getParam('qr_code_width', ''); ?>" min="1" max="999" />
			</div>
		</div>
		<div class="vbo-param-container">
			<div class="vbo-param-label">Invoice Mark</div>
			<div class="vbo-param-setting">
				<?php echo $this->vbo_app->printYesNoButtons($this->inputName('invoice_mark'), JText::translate('VBYES'), JText::translate('VBNO'), (int)$this->getParam('invoice_mark', 0), 1, 0); ?>
				<span class="vbo-param-setting-comment">You can optionally use the tag <strong onclick="vboMydataAadeAddContentEditor('{mydata_aade_invmark}');" style="cursor: pointer;">{mydata_aade_invmark}</strong> if you would like to place the <i>invoice mark</i> on a specific section of the message.</span>
			</div>
		</div>

		<script type="text/javascript">
			function vboMydataAadeAddContentEditor(str) {
				if (!str) {
					return;
				}

				try {
					// "msg" is the name of the WYSIWYG editor of the conditional text
					Joomla.editors.instances.msg.replaceSelection(str);
				} catch(e) {
					// do nothing
				}
			}
		</script>
		<?php
	}

	/**
	 * Tells whether the rule is compliant.
	 * 
	 * @return 	bool 	True on success, false otherwise.
	 */
	public function isCompliant()
	{
		$booking_id = (int)$this->getPropVal('booking', 'id', 0);

		// ensure the myDATA - AADE driver is configured
		$mydata_driver_id = $this->getDriverId();

		if (!$mydata_driver_id) {
			return false;
		}

		// check if an electronic invoice was generated and transmitted for this booking
		$this->transmission_data = $this->getBookingTransmissionData($mydata_driver_id, $booking_id);

		if (!$this->transmission_data) {
			return false;
		}

		if (!strcasecmp($this->getParam('invoice_type', 'main'), 'envfee')) {
			// make sure data is available for the environmental fee invoice

			$last_einv_id = $this->getLastEinvoiceId($mydata_driver_id, $booking_id);

			$this->correlated_invoice_data = VBOFactory::getConfig()->getArray("envfee_invoice_{$mydata_driver_id}_{$last_einv_id}_{$booking_id}", []);

			if (!$this->correlated_invoice_data || empty($this->correlated_invoice_data['transmission'])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Allows to manipulate the message of the conditional text with dynamic contents.
	 * 
	 * @override
	 * 
	 * @param 	string 	$msg 	the current conditional text message.
	 * 
	 * @return 	string 			the manipulated conditional text message.
	 */
	public function manipulateMessage($msg)
	{
		if (!is_object($this->transmission_data)) {
			// reservation main invoice transmission data is always mandatory
			return $msg;
		}

		$invoice_type = $this->getParam('invoice_type', 'main');

		if (!strcasecmp($invoice_type, 'envfee') && !$this->correlated_invoice_data) {
			// missing transmission data for the environmental fee invoice
			return $msg;
		}

		$add_qr   = (bool)$this->getParam('qr_code', 0);
		$add_mark = (bool)$this->getParam('invoice_mark', 0);
		$qr_width = (int)$this->getParam('qr_code_width', 128);

		if (!$add_qr && !$add_mark) {
			return $msg;
		}

		if (!strcasecmp($invoice_type, 'envfee')) {
			// correlated invoice
			$invoice_qrcode = !empty($this->correlated_invoice_data['transmission']['qrcode_img']) ? $this->correlated_invoice_data['transmission']['qrcode_img'] : '';
			$invoice_mark   = !empty($this->correlated_invoice_data['transmission']['mark']) ? $this->correlated_invoice_data['transmission']['mark'] : '';
		} else {
			// reservation main invoice
			$invoice_qrcode = !empty($this->transmission_data->qrcode_img) ? $this->transmission_data->qrcode_img : '';
			$invoice_mark   = !empty($this->transmission_data->invoice_mark) ? $this->transmission_data->invoice_mark : '';
		}

		// build the HTML content for the QR Code PNG image
		$qrcode_html = '';
		if ($add_qr && !empty($invoice_qrcode)) {
			// build the image tag
			$qrcode_html = '<img src="' . $this->getQRCodeUri($invoice_qrcode) . '" width="' . $qr_width . '" alt="Invoice QR Code" />';

			if (strpos($msg, '{mydata_aade_qrcode_img}') !== false) {
				// exact placeholder tag found
				$msg = str_replace('{mydata_aade_qrcode_img}', $qrcode_html, $msg);
			} else {
				// append image tag to message
				$msg .= $qrcode_html;
			}
		}

		// build the HTML content for the invoice mark (number)
		if ($add_mark && !empty($invoice_mark)) {
			if (strpos($msg, '{mydata_aade_invmark}') !== false) {
				// exact placeholder tag found, so use the plain mark string
				$msg = str_replace('{mydata_aade_invmark}', $invoice_mark, $msg);
			} else {
				// append P tag to message
				$msg .= '<p>' . $invoice_mark . '</p>';
			}
		}

		// return the manipulated message
		return $msg;
	}

	/**
	 * Tells if the myDATA - AADE driver is configured by returning its ID.
	 * 
	 * @return 	mixed 	null or integer ID, if configured.
	 */
	protected function getDriverId()
	{
		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select($dbo->qn('id'))
				->from($dbo->qn('#__vikbooking_einvoicing_config'))
				->where($dbo->qn('driver') . ' = ' . $dbo->q('mydata_aade'))
		);

		return $dbo->loadResult();
	}

	/**
	 * Tells if the myDATA - AADE driver transmitted an invoice for the given booking ID.
	 * 
	 * @param 	int 	$driver_id 	the myDATA - AADE driver ID.
	 * @param 	int 	$bid 		the VikBooking reservation ID.
	 * 
	 * @return 	mixed 	null or transmission result object, if invoice is available.
	 */
	protected function getBookingTransmissionData($driver_id, $bid)
	{
		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select($dbo->qn('trans_data'))
				->from($dbo->qn('#__vikbooking_einvoicing_data'))
				->where($dbo->qn('driverid') . ' = ' . (int)$driver_id)
				->where($dbo->qn('idorder') . ' = ' . (int)$bid)
				->where($dbo->qn('transmitted') . ' = 1')
				->where($dbo->qn('obliterated') . ' = 0')
				->order($dbo->qn('id') . ' DESC')
		);

		$transmission_data = $dbo->loadResult();

		if (!$transmission_data) {
			return null;
		}

		$transmission_data = json_decode($transmission_data);

		if (!is_object($transmission_data) || (empty($transmission_data->invoice_mark) && empty($transmission_data->qrcode_img))) {
			return null;
		}

		return $transmission_data;
	}

	/**
	 * Returns the last e-invoice record ID for the given reservation ID.
	 * 
	 * @param 	int 	$driver_id 	the myDATA - AADE driver ID.
	 * @param 	int 	$bid 		the VikBooking reservation ID.
	 * 
	 * @return 	int 	the last e-invoice ID generated, or 0.
	 */
	protected function getLastEinvoiceId($driver_id, $bid)
	{
		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select($dbo->qn('id'))
				->from($dbo->qn('#__vikbooking_einvoicing_data'))
				->where($dbo->qn('driverid') . ' = ' . (int)$driver_id)
				->where($dbo->qn('idorder') . ' = ' . (int)$bid)
				->where($dbo->qn('transmitted') . ' = 1')
				->where($dbo->qn('obliterated') . ' = 0')
				->order($dbo->qn('id') . ' DESC')
		);

		$einv_id = $dbo->loadResult();

		return $einv_id ?: 0;
	}

	/**
	 * Returns the full URI to the given PNG filename for the QR Code.
	 * Rather than using static paths or URIs, we use the apposite helper.
	 * 
	 * @param 	string 	$qrcode_fname 	the QR Code PNG filename.
	 * 
	 * @return 	string
	 */
	protected function getQRCodeUri($qrcode_fname)
	{
		// load the helper for the myDATA - AADE driver
		require_once implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'helpers', 'einvoicing', 'drivers', 'MydataAade', 'constants.php']);

		if (!class_exists('VikBookingMydataAadeConstants')) {
			return $qrcode_fname;
		}

		return VikBookingMydataAadeConstants::getQRCodeBase('uri', $qrcode_fname);
	}
}

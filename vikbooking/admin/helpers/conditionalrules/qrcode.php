<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for conditional rule "QRCode".
 * This Conditional Text Rule will include a QR Code image with the booking link.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
class VikBookingConditionalRuleQrcode extends VikBookingConditionalRule
{
    /**
     * @var  string
     */
    protected $qrcode_base_path;

    /**
     * @var  string
     */
    protected $qrcode_base_uri;

    /**
     * Class constructor will define the rule name, description and identifier.
     */
    public function __construct()
    {
        // call parent constructor
        parent::__construct();

        $this->ruleName = 'QR Code';
        $this->ruleDescr = 'Generate a QR Code with the booking link.';
        $this->ruleId = basename(__FILE__);

        $this->qrcode_base_path = VBO_MEDIA_PATH;
        $this->qrcode_base_uri = VBO_MEDIA_URI;
    }

    /**
     * Displays the rule parameters.
     * 
     * @return  void
     */
    public function renderParams()
    {
        // build the list of rule params to collect during the configuration
        $params = [
            'qr_code_width' => [
                'type'  => 'number',
                'label' => 'QR Code width (px)',
                'help'  => 'You can optionally use the tag <strong onclick="vboQrcodeCtrAddContentEditor(\'{qrcode_img}\');" style="cursor: pointer;">{qrcode_img}</strong> if you would like to place the <i>QR code image</i> on a specific section of the message.',
                'min'   => 1,
                'max'   => 999,
                'default' => 128,
            ],
        ];

        // build the list of current rule settings that were saved
        $settings = (array) $this->getParams();

        // render all params/settings for this custom rule
        echo VBOParamsRendering::getInstance(
            $params,
            $settings
        )->setInputName(basename($this->ruleId, '.php'))->getHtml();

        ?>

        <script type="text/javascript">
            function vboQrcodeCtrAddContentEditor(str) {
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
     * @return  bool    True on success, false otherwise.
     */
    public function isCompliant()
    {
        $booking_id = (int) $this->getPropVal('booking', 'id', 0);
        $qrcode_width = (int) $this->getParam('qr_code_width', 0);

        return !empty($booking_id) && $qrcode_width > 0;
    }

    /**
     * Allows to manipulate the message of the conditional text with dynamic contents.
     * 
     * @override
     * 
     * @param   string  $msg    The configured conditional text message.
     * 
     * @return  string          The manipulated conditional text message.
     */
    public function manipulateMessage($msg)
    {
        $qrcode_width = (int) $this->getParam('qr_code_width', 128);

        $qrcode_uri = $this->getQRCodeUri();

        if (!$qrcode_uri) {
            // an error occurred
            return $msg;
        }

        // build the HTML content for the QR Code PNG image
        $qrcode_html = '<img src="' . $qrcode_uri . '" width="' . $qrcode_width . '" alt="Reservation QR Code" />';

        if (strpos($msg, '{qrcode_img}') !== false) {
            // exact placeholder tag found
            $msg = str_replace('{qrcode_img}', $qrcode_html, $msg);
        } else {
            // append image tag to message
            $msg .= $qrcode_html;
        }

        // return the manipulated message
        return $msg;
    }

    /**
     * Obtains the QR Code image URI by checking if the file exists for the current
     * reservation, or by creating the PNG file at runtime and by saving it on disk.
     * 
     * @return  string  QR Code URI or empty string in case of errors.
     */
    protected function getQRCodeUri()
    {
        // gather booking details
        $booking_id = (int) $this->getPropVal('booking', 'id', 0);
        $booking_ts = (int) $this->getPropVal('booking', 'ts', 0);
        $booking_sid = (string) $this->getPropVal('booking', 'sid', 0);
        $booking_idota = (string) $this->getPropVal('booking', 'idorderota', '');
        $booking_channel = (string) $this->getPropVal('booking', 'channel', '');
        $booking_lang = (string) $this->getPropVal('booking', 'lang', '');
        $use_sid = !empty($booking_idota) && !empty($booking_channel) ? $booking_idota : $booking_sid;

        if (empty($booking_id) || empty($booking_ts)) {
            return '';
        }

        $qrcode_file_name = 'qr-booking-' . $use_sid . '-' . $booking_ts . '.png';
        $final_qrcode_path = implode(DIRECTORY_SEPARATOR, [$this->qrcode_base_path, $qrcode_file_name]);

        if (is_file($final_qrcode_path)) {
            // QR code file exists, return the URI
            return $this->qrcode_base_uri . $qrcode_file_name;
        }

        // route booking details URL
        $lang_link = !empty($booking_lang) ? "&lang={$booking_lang}" : '';
        $book_link = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid={$use_sid}&ts={$booking_ts}{$lang_link}", false);

        // access the model for shortening URLs
        $model = VBOModelShortenurl::getInstance($onlyRouted = true)->setBooking((array) $this->getProperty('booking', []));

        // get the final booking link URL for the QR code content
        $book_link = $model->getShortUrl($book_link);

        try {
            // require the TCPDF 2D Barcode library
            require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf_barcodes_2d.php';

            // set the barcode content and type
            $barCode = new TCPDF2DBarcode($book_link, 'QRCODE,H');

            // generate the QR code as PNG image
            $qr = $barCode->getBarcodePngData(
                /**
                 * QR Code PNG default width (points indicating pixels per cell).
                 * 1 ~= 49px/69px.
                 */
                4,
                /**
                 * QR Code PNG default height (points indicating pixels per cell).
                 * 1 ~= 49px/69px.
                 */
                4,
                /**
                 * QR Code PNG default color (RGB) (black)
                 */
                explode(',', preg_replace("/[^0-9\.\,]/", '', '0, 0, 0'))
            );

            // write the image on disk
            if (JFile::write($final_qrcode_path, $qr)) {
                // QR code file created, return the URI
                return $this->qrcode_base_uri . $qrcode_file_name;
            }
        } catch (Throwable $t) {
            // do nothing in case of errors
        }

        // an error occurred
        return '';
    }
}

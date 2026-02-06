<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Defines an abstract implementation for pax fields MRZ mapping.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
abstract class VBOCheckinPaxfieldsMrzMap
{
    /**
     * @var     string
     */
    protected string $collector_id = '';

    /**
     * @var     array
     */
    protected array $fieldsList = [];

    /**
     * @var     array
     */
    protected array $fieldsMap = [];

    /**
     * @var     array
     */
    protected array $mrzProperties = [];

    /**
     * @var     bool
     */
    protected bool $checkDigitVerified = false;

    /**
     * @var     array
     */
    protected array $propertyList = [
        'documentCode',
        'documentType',
        'issuer',
        'documentNumber',
        'dateOfBirth',
        'sex',
        'dateOfExpiry',
        'nationality',
        'lastName',
        'firstName',
    ];

    /**
     * Class constructor will bind collector and pax fields.
     * 
     * @param   array   $fields     Default pax fields.
     */
    public function __construct(string $collector, array $fields)
    {
        // bind collector ID
        $this->collector_id = $collector ?: preg_replace('/^VBOCheckinPaxfieldsMrzMap/i', '', strtolower(get_class($this)));

        // bind default check-in fields for data collector
        $this->setPaxFields($fields);
    }

    /**
     * Given a list of MRZ detected properties, obtains the assoc list of
     * known and supported field identifiers within the data collector.
     * 
     * @param   array   $propertyList   List of MRZ detected properties.
     * 
     * @return  array                   Associative list of known and supported properties.
     */
    abstract public function getKnownFields(array $propertyList);

    /**
     * Sets current check-in fields for data collector.
     * 
     * @param   array   $fields     Default pax fields.
     * 
     * @return  self
     * 
     * @throws  Exception
     */
    public function setPaxFields(array $fields)
    {
        // ensure the fields list contains labels and attributes
        if (count($fields) !== 2 || !is_array($fields[0] ?? null) || !is_array($fields[1] ?? null)) {
            throw new InvalidArgumentException('Invalid check-in pax fields provided.', 400);
        }

        // bind current check-in fields list for data collector
        $this->fieldsList = $fields;

        return $this;
    }

    /**
     * Tells whether the MRZ check-digit validation was verified.
     * 
     * @return  bool
     */
    public function isVerified()
    {
        return $this->checkDigitVerified;
    }

    /**
     * Sets whether the MRZ check-digit validation was verified.
     * 
     * @param   bool    $verified   True if check-digit was verified.
     * 
     * @return  self
     */
    public function setVerified(bool $verified)
    {
        $this->checkDigitVerified = $verified;

        return $this;
    }

    /**
     * Returns the current MRZ properties detected from the ID document.
     * 
     * @return  array
     */
    public function getMrzProperties()
    {
        return $this->mrzProperties;
    }

    /**
     * Binds the MRZ properties detected from the ID document.
     * 
     * @param   array   $properties     The raw MRZ detected properties.
     * 
     * @return  self
     */
    public function setMrzProperties(array $properties)
    {
        $this->mrzProperties = $properties;

        return $this;
    }

    /**
     * Returns the requested MRZ propery from raw data detected.
     * 
     * @param   string  $type   The raw property type identifier.
     * 
     * @return  ?string
     */
    public function getRawMrzProperty(string $type)
    {
        return $this->mrzProperties[$type] ?? null;
    }

    /**
     * Returns the current collector identifier.
     * 
     * @return  string
     */
    public function getCollector()
    {
        return $this->collector_id;
    }

    /**
     * Returns the list of mapped MRZ properties to fields.
     * 
     * @return  array
     */
    public function getMappedFields()
    {
        return $this->fieldsMap;
    }

    /**
     * Given an associative list of MRZ properties detected from an ID document,
     * maps the values according to the pax data collector supported fields.
     * 
     * @param   array   $properties     Associative list of raw MRZ properties from ID document.
     * 
     * @return  self
     */
    public function mapDetectedProperties(array $properties)
    {
        // bind the raw MRZ detected properties from ID document
        $this->setMrzProperties($properties);

        // obtain the associative list of known and supported properties
        $knownProperties = $this->getKnownFields(array_intersect(array_keys($properties), $this->propertyList));

        // iterate all property types and values
        foreach ($properties as $type => $value) {
            // ensure the value is supported
            if (is_null($value) || !is_scalar($value)) {
                // unsupported field value
                continue;
            }

            // ensure the value is not empty
            $value = (string) $value;
            if ($value === '') {
                // ignore empty values
                continue;
            }

            if (!($knownProperties[$type] ?? null)) {
                // unsupported property by data collector
                continue;
            }

            // check property type to map
            switch ($type) {
                case 'documentCode':
                    // map value
                    $this->mapProperty(
                        (array) $knownProperties[$type],
                        $this->mapDocumentCode($value)
                    );
                    break;

                case 'documentType':
                    // map value
                    $this->mapProperty(
                        (array) $knownProperties[$type],
                        $this->mapDocumentType($value)
                    );
                    break;

                case 'issuer':
                    // map value
                    $this->mapProperty(
                        (array) $knownProperties[$type],
                        $this->mapDocumentIssuer($this->convertCountryValue($value))
                    );
                    break;

                case 'documentNumber':
                    // map value
                    $this->mapProperty(
                        (array) $knownProperties[$type],
                        $this->mapDocumentNumber($value)
                    );
                    break;

                case 'dateOfBirth':
                    // map value
                    $this->mapProperty(
                        (array) $knownProperties[$type],
                        // ensure the argument is passed in Y-m-d format
                        $this->mapDateOfBirth($this->convertDateOfBirth($value))
                    );
                    break;

                case 'sex':
                    // map value
                    $this->mapProperty(
                        (array) $knownProperties[$type],
                        $this->mapGender($value)
                    );
                    break;

                case 'dateOfExpiry':
                    // map value
                    $this->mapProperty(
                        (array) $knownProperties[$type],
                        // ensure the argument is passed in Y-m-d format
                        $this->mapDateOfExpiry($this->convertDateOfExpiry($value))
                    );
                    break;

                case 'nationality':
                    // map value
                    $this->mapProperty(
                        (array) $knownProperties[$type],
                        $this->mapNationality($this->convertCountryValue($value))
                    );
                    break;

                case 'lastName':
                    // map value
                    $this->mapProperty(
                        (array) $knownProperties[$type],
                        $this->mapLastName($value)
                    );
                    break;

                case 'firstName':
                    // map value
                    $this->mapProperty(
                        (array) $knownProperties[$type],
                        $this->mapFirstName($value)
                    );
                    break;
                
                default:
                    // nothing to map
                    break;
            }
        }

        return $this;
    }

    /**
     * Returns the associative list of pax field labels.
     * 
     * @return  array   Pax fields associative labels.
     */
    protected function getLabels()
    {
        return $this->fieldsList[0];
    }

    /**
     * Returns the associative list of pax field attributes.
     * 
     * @return  array   Pax fields associative attributes.
     */
    protected function getAttributes()
    {
        return $this->fieldsList[1];
    }

    /**
     * Helper method to allow the MRZ pax fields map implementor
     * to call methods declared by the data collection driver.
     * 
     * @param   string  $method     The method to call from the collector.
     * 
     * @return  mixed
     */
    protected function callCollector($method)
    {
        // access the collector class
        $collector = VBOCheckinPax::getInstance($this->getCollector());

        if (!$collector || empty($method) || !is_callable([$collector, $method])) {
            return null;
        }

        // build extra arguments, if any
        $args = func_get_args();
        unset($args[0]);

        // invoke the collector's method
        return call_user_func_array([$collector, $method], $args);
    }

    /**
     * The MRZ standard includes dates in "ymd" format.
     * For easier handling, we convert it to "Y-m-d" format.
     * 
     * @param   string  $ymd    The date string in "ymd" format.
     * 
     * @return  string          The converted date in "Y-m-d" format, or empty string.
     */
    protected function convertDateOfBirth(string $ymd)
    {
        if (!preg_match('/^[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])$/', $ymd)) {
            // invalid date, expected format is "ymd"
            return '';
        }

        // convert "ymd" into "y-m-d" by adding a dash every two characters
        $ymd = implode('-', str_split($ymd, 2));

        // first two digits of current year
        $yearFirstDigits = (int) substr(date('Y'), 0, 2);

        // prepend first two digits of current year to date
        $fullDate = $yearFirstDigits . $ymd;

        if (strtotime($fullDate) > time()) {
            // the date of birth cannot be in the future

            // decrease by one century
            $yearFirstDigits -= 1;

            // prepend first two digits of current year to date
            $fullDate = $yearFirstDigits . $ymd;
        }

        return $fullDate;
    }

    /**
     * The MRZ standard includes dates in "ymd" format.
     * For easier handling, we convert it to "Y-m-d" format.
     * 
     * @param   string  $ymd    The date string in "ymd" format.
     * 
     * @return  string          The converted date in "Y-m-d" format, or empty string.
     */
    protected function convertDateOfExpiry(string $ymd)
    {
        if (!preg_match('/^[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])$/', $ymd)) {
            // invalid date, expected format is "ymd"
            return '';
        }

        // convert "ymd" into "y-m-d" by adding a dash every two characters
        $ymd = implode('-', str_split($ymd, 2));

        // first two digits of current year
        $yearFirstDigits = (int) substr(date('Y'), 0, 2);

        // first two digits of last century
        $lastCenturyYear = $yearFirstDigits - 1;

        // first two digits of next century
        $nextCenturyYear = $yearFirstDigits + 1;

        // prepend first two digits of current year to date
        $fullDateCurrent = $yearFirstDigits . $ymd;

        // prepend first two digits of last century year to date
        $fullDateLast = $lastCenturyYear . $ymd;

        // prepend first two digits of next century year to date
        $fullDateNext = $nextCenturyYear . $ymd;

        // construct date-timezone object
        $tz = new DateTimeZone(date_default_timezone_get());

        // construct nowadays date-time object
        $nowadays = new DateTime('now', $tz);

        // construct date-time object with current year full date
        $currentDt = new DateTime($fullDateCurrent, $tz);

        // construct date-time object with last century full date
        $lastCenturyDt = new DateTime($fullDateLast, $tz);

        // construct date-time object with next century full date
        $nextCenturyDt = new DateTime($fullDateNext, $tz);

        // check what date is closest to nowadays to determine the best expiration year
        $yearDistances = [
            'current' => (int) $nowadays->diff($currentDt)->y,
            'last'    => (int) $nowadays->diff($lastCenturyDt)->y,
            'next'    => (int) $nowadays->diff($nextCenturyDt)->y,
        ];

        // sort in ascending order
        asort($yearDistances);

        // get closest expiration date to nowadays
        $closestDateType = key($yearDistances);

        if ($closestDateType === 'next') {
            return $fullDateNext;
        }

        if ($closestDateType === 'last') {
            return $fullDateLast;
        }

        return $fullDateCurrent;
    }

    /**
     * Given the country code identifier extracted from the document, checks if
     * the value is known to be normalized to an existing country ISO code.
     * 
     * @param   string  $countryCode    The raw country code read from MRZ.
     * 
     * @return  string                  The normalized (or same) country code.
     */
    protected function convertCountryValue(string $countryCode)
    {
        if ($countryCode === 'D<<') {
            // normaly country code for Germany
            return 'DEU';
        }

        if ($countryCode === 'GB<') {
            // normaly country code for UK
            return 'GBR';
        }

        if ($countryCode === 'ZIM') {
            // normaly country code for Zimbabwe
            return 'ZWE';
        }

        if ($countryCode === 'RKS') {
            // normaly country code for Kosovo (2-char)
            return 'KV';
        }

        return $countryCode;
    }

    /**
     * Maps the value of an ID document property to the list
     * of known and supported pax fields of that type.
     * 
     * @param   array   $fieldIds   List of pax field identifiers.
     * @param   string  $value      The normalized value for the field(s).
     * 
     * @return  self
     */
    protected function mapProperty(array $fieldIds, string $value)
    {
        foreach (array_filter($fieldIds) as $fieldId) {
            if (!is_string($fieldId)) {
                // unexpected pax field identifier
                continue;
            }

            if ($value === '') {
                // skip empty values
                continue;
            }

            // push field property data
            $this->fieldsMap[] = [
                'id'    => $fieldId,
                'value' => $value,
            ];
        }

        return $this;
    }

    /**
     * Maps the MRZ value for "document code" ("P", "I", "A", "C", "V").
     * 
     * @param   string  $value  The value to normalize.
     * 
     * @return  string          The normalized value.
     */
    protected function mapDocumentCode(string $value)
    {
        return str_replace('<', '', $value);
    }

    /**
     * Maps the MRZ value for "document type" (document variant, often "<").
     * 
     * @param   string  $value  The value to normalize.
     * 
     * @return  string          The normalized value.
     */
    protected function mapDocumentType(string $value)
    {
        return $value;
    }

    /**
     * Maps the MRZ value for "document issuer" (country code).
     * 
     * @param   string  $value  The value to normalize.
     * 
     * @return  string          The normalized value.
     */
    protected function mapDocumentIssuer(string $value)
    {
        return strtoupper(str_replace('<', '', $value));
    }

    /**
     * Maps the MRZ value for "document number".
     * 
     * @param   string  $value  The value to normalize.
     * 
     * @return  string          The normalized value.
     */
    protected function mapDocumentNumber(string $value)
    {
        return str_replace('<', '', $value);
    }

    /**
     * Maps the MRZ value for "date of birth" (converted in Y-m-d format).
     * 
     * @param   string  $value  The value to normalize (Y-m-d format).
     * 
     * @return  string          The normalized value.
     */
    protected function mapDateOfBirth(string $value)
    {
        if (empty($value)) {
            return '';
        }

        $nowdf = VikBooking::getDateFormat();
        if ($nowdf == "%d/%m/%Y") {
            $df = 'd/m/Y';
        } elseif ($nowdf == "%m/%d/%Y") {
            $df = 'm/d/Y';
        } else {
            $df = 'Y/m/d';
        }

        return date($df, strtotime($value));
    }

    /**
     * Maps the MRZ value for "gender".
     * 
     * @param   string  $value  The value to normalize.
     * 
     * @return  string          The normalized value.
     */
    protected function mapGender(string $value)
    {
        return strtoupper($value) === 'F' ? 'F' : 'M';
    }

    /**
     * Maps the MRZ value for "document expiry date" (converted in Y-m-d format).
     * 
     * @param   string  $value  The value to normalize (Y-m-d format).
     * 
     * @return  string          The normalized value.
     */
    protected function mapDateOfExpiry(string $value)
    {
        if (empty($value)) {
            return '';
        }

        $nowdf = VikBooking::getDateFormat();
        if ($nowdf == "%d/%m/%Y") {
            $df = 'd/m/Y';
        } elseif ($nowdf == "%m/%d/%Y") {
            $df = 'm/d/Y';
        } else {
            $df = 'Y/m/d';
        }

        return date($df, strtotime($value));
    }

    /**
     * Maps the MRZ value for "nationality" (country code).
     * 
     * @param   string  $value  The ISO country code value to normalize.
     * 
     * @return  string          The normalized value.
     */
    protected function mapNationality(string $value)
    {
        return strtoupper(str_replace('<', '', $value));
    }

    /**
     * Maps the MRZ value for "last name".
     * 
     * @param   string  $value  The value to normalize.
     * 
     * @return  string          The normalized value.
     */
    protected function mapLastName(string $value)
    {
        return ucwords(trim(str_replace('<', ' ', strtolower($value))));
    }

    /**
     * Maps the MRZ value for "first name".
     * 
     * @param   string  $value  The value to normalize.
     * 
     * @return  string          The normalized value.
     */
    protected function mapFirstName(string $value)
    {
        return ucwords(trim(str_replace('<', ' ', strtolower($value))));
    }
}

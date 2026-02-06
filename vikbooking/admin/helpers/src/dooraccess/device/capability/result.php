<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Door Access device capability result implementation.
 * 
 * @since   1.18.4 (J) - 1.8.4 (WP)
 */
final class VBODooraccessDeviceCapabilityResult
{
    /**
     * @var  ?string
     */
    protected ?string $passcode = null;

    /**
     * @var  ?string
     */
    protected ?string $html = null;

    /**
     * @var  ?string
     */
    protected ?string $text = null;

    /**
     * @var  ?array
     */
    protected ?array $properties = null;

    /**
     * Class constructor.
     * 
     * @param   ?array  $data   Optional result data properties to bind.
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            // bind result data properties
            $this->properties = $data;
        }
    }

    /**
     * Returns the result passcode string.
     * 
     * @return  ?string
     */
    public function getPasscode()
    {
        return $this->passcode;
    }

    /**
     * Sets the passcode result string.
     * 
     * @param   string   $passcode  The passcode to set.
     * 
     * @return  self
     */
    public function setPasscode(string $passcode)
    {
        $this->passcode = $passcode;

        return $this;
    }

    /**
     * Returns the HTML result output string.
     * 
     * @return  ?string
     */
    public function getOutput()
    {
        return $this->html;
    }

    /**
     * Sets the HTML output string.
     * 
     * @param   string   $output     The result value to set.
     * 
     * @return  self
     */
    public function setOutput(string $output)
    {
        $this->html = $output;

        return $this;
    }

    /**
     * Returns the text result string.
     * 
     * @return  ?string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Sets the text result string.
     * 
     * @param   string   $output     The result value to set.
     * 
     * @return  self
     */
    public function setText(string $output)
    {
        $this->text = $output;

        return $this;
    }

    /**
     * Returns the result data properties array (nullable).
     * 
     * @return  ?array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Binds the result data properties array.
     * 
     * @param   array    $data   Data properties to bind.
     * 
     * @return  self
     */
    public function setProperties(array $data)
    {
        $this->properties = $data;

        return $this;
    }

    /**
     * Returns the proper result output value.
     */
    public function __toString()
    {
        return $this->getOutput() ?: $this->getText();
    }
}

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
 * Door Access device capability. Such objects are
 * serialized and stored onto the database as blobs.
 * 
 * @since   1.18.4 (J) - 1.8.4 (WP)
 */
final class VBODooraccessDeviceCapability
{
    /**
     * @var  ?string
     */
    protected ?string $id = null;

    /**
     * @var  ?string
     */
    protected ?string $title = null;

    /**
     * @var  ?string
     */
    protected ?string $description = null;

    /**
     * @var  ?string
     */
    protected ?string $icon = null;

    /**
     * @var  ?array
     */
    protected ?array $params = null;

    /**
     * @var  ?string
     */
    protected ?string $callback = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {}

    /**
     * Magic method to get or set protected properties.
     * 
     * @param   string  $name       The method name called.
     * @param   array   $arguments  The arguments of the method called.
     * 
     * @return  mixed
     * 
     * @throws  Exception
     */
    public function __call(string $name, array $arguments)
    {
        if (preg_match('/^get([a-z]+)/i', $name, $matches)) {
            // getter method invoked
            $property = strtolower($matches[1]);

            // access the requested property, if set
            return $this->{$property} ?? null;
        }

        if (preg_match('/^set([a-z]+)/i', $name, $matches) && $arguments) {
            // setter method invoked
            $property = strtolower($matches[1]);

            if (property_exists($this, $property)) {
                // set the value for the requested property
                $this->{$property} = $arguments[0];
            }

            // return self for chain-ability
            return $this;
        }

        throw new Exception(sprintf('Could not call device capability method %s.', $name), 500);
    }

    /**
     * Tells if the device capability provides parameters for its execution.
     * 
     * @return  bool
     */
    public function providesParams()
    {
        return !empty($this->params);
    }

    /**
     * Tells if the device capability meets the minimum requirements.
     * 
     * @return  bool
     */
    public function isValid()
    {
        return !empty($this->id) && !empty($this->title) && !empty($this->callback);
    }

    /**
     * Executes the capability through the callback instructions, if set.
     * 
     * @param   VBODooraccessIntegrationAware   $integration    The provider integration object.
     * @param   VBODooraccessIntegrationDevice  $device         The device executing the capability.
     * @param   ?array                          $options        Optional settings obtained from the params.
     * 
     * @return  ?string     Optional execution result string to display.
     * 
     * @throws  Exception
     */
    public function execute(VBODooraccessIntegrationAware $integration, VBODooraccessIntegrationDevice $device, ?array $options = null)
    {
        if (!is_string($this->callback) || !is_callable([$integration, $this->callback])) {
            throw new Exception('Could not execute device capability.', 500);
        }

        // execute the capability on the provider integration object
        return call_user_func_array([$integration, $this->callback], [$device, $options]);
    }
}

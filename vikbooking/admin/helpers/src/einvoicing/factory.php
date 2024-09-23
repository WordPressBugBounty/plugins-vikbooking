<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Factory class for the e-invoicing framework.
 * 
 * @since   1.16.7 (J) - 1.6.7 (WP)
 */
final class VBOEinvoicingFactory
{
    /**
     * The singleton instance of the class.
     *
     * @var  VBOEinvoicingFactory
     */
    private static $instance = null;

    /**
     * List of loaded driver objects.
     *
     * @var  array
     */
    private $drivers = [];

    /**
     * Returns an instance of the class.
     * 
     * @return  VBOEinvoicingFactory
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Class constructor.
     */
    public function __construct()
    {
        // load dependencies
        $driver_base = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'einvoicing' . DIRECTORY_SEPARATOR;

        require_once $driver_base . 'einvoicing.php';

        // load drivers
        $this->drivers = $this->loadDrivers();
    }

    /**
     * Returns the list of loaded drivers.
     * 
     * @return  array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * Loads the list of available e-invoicing drivers.
     * 
     * @return  array
     */
    public function loadDrivers()
    {
        $loaded = [];

        $dbo = JFactory::getDbo();

        // always load all drivers, db and plugin based ones
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('*')
                ->from($dbo->qn('#__vikbooking_einvoicing_config'))
                ->where($dbo->qn('automatic') . ' = 1')
        );
        $drivers = $dbo->loadAssocList();

        /**
         * Trigger event to let other plugins register additional drivers.
         *
         * @return  array   A list of supported drivers.
         */
        $list = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadEinvoicingDrivers');
        foreach ($list as $chunk) {
            // merge default driver files with the returned ones
            $drivers = array_merge($drivers, (array)$chunk);
        }

        // drivers base path
        $driver_base = implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'helpers', 'einvoicing', 'drivers']) . DIRECTORY_SEPARATOR;

        // invoke all drivers that should run automatically, or that were loaded by third-party plugins
        foreach ($drivers as $driver) {
            $driver_file = is_string($driver) ? $driver : $driver['driver'] . '.php';
            $driver_path = $driver_base . $driver_file;

            if (is_file($driver_path)) {
                // require the driver sub-class
                require_once $driver_path;
            } elseif (is_file($driver_file)) {
                // require the plugin-loaded driver
                require_once $driver_file;
            }

            // build driver class name
            $classname = 'VikBookingEInvoicing' . str_replace(' ', '', ucwords(str_replace('.php', '', str_replace('_', ' ', $driver_file))));
            if (!class_exists($classname)) {
                continue;
            }

            // instantiate the object
            $loaded[] = new $classname;
        }

        return $loaded;
    }

    /**
     * Returns a list of additional invoices available for a specific booking.
     * For example, one e-invoicing driver like myDATA could generate one
     * correlated invoice for the same reservation ID.
     * 
     * @param   int     $bid    the VikBooking reservation ID.
     * 
     * @return  array
     */
    public function getBookingExtraInvoices($bid)
    {
        $extra_invoices = [];

        foreach ($this->drivers as $driver) {
            $extras = $driver->getBookingExtraInvoices($bid);
            if ($extras) {
                $extra_invoices = array_merge($extra_invoices, $extras);
            }
        }

        return $extra_invoices;
    }
}

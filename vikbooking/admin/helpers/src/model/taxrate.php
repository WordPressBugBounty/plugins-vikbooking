<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking tax rate records model.
 *
 * @since   1.18.15 (J) - 1.8.15 (WP)
 */
class VBOModelTaxrate extends VBOMvcModel
{
    /**
     * The database table name.
     * 
     * @var string
     */
    protected $tableName = '#__vikbooking_iva';
}

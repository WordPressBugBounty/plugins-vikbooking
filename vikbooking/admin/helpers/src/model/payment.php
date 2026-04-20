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
 * VikBooking payment model.
 *
 * @since   1.18.8 (J) - 1.8.8 (WP)
 */
class VBOModelPayment extends VBOMvcModel
{
    /**
     * The database table name.
     * 
     * @var string
     */
    protected $tableName = '#__vikbooking_gpayments';

    /**
     * @inheritDoc
     */
    public function getItem($pk)
    {
        $item = parent::getItem($pk);

        if ($item) {
            // auto-decode parameters in array format
            $item->params = !empty($item->params) ? (array) json_decode($item->params, true) : [];
        }

        return $item;
    }

    /**
     * @inheritDoc
     */
    protected function preflight(array &$data)
    {
        if (!empty($data['params']) && is_scalar($data['params'])) {
            // make sure to encode the parameters
            $data['params'] = json_encode($data['params']);
        }

        return parent::preflight($data);
    }
}

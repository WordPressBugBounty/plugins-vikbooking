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
 * Holds the details of the user that applied the changes.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOHistoryCommitter
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $role;

    /**
     * Class constructor.
     * 
     * @param  string  $id
     * @param  string  $name
     * @param  string  $role
     */
    public function __construct(string $id, string $name, string $role)
    {
        $this->id = $id;
        $this->name = $name;
        $this->role = $role;
    }

    /**
     * Returns the user identifier.
     * 
     * @return  string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Returns the user name.
     * 
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the user role (guest, admin, operator).
     * 
     * @return  string
     */
    public function getRole()
    {
        return $this->role;
    }
}

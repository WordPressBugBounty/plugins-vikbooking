<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Chat user null pointer pattern.
 * 
 * @since 1.8
 */
final class VBOChatUserNull extends VBOChatUseraware
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $name;

    /**
     * Class constructor.
     * 
     * @param  int     $id
     * @param  string  $name
     */
    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function can(string $scope, ?VBOChatContext $context = null)
    {
        return false;
    }
}

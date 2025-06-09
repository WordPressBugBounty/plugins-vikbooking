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
 * Generic history tracker.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOHistoryTracker
{
    /**
     * The model that will be used to save the changes.
     * 
     * @var VBOHistoryModel
     */
    protected $model;

    /**
     * The rules to run in search of changes.
     * 
     * @var VBOHistoryDetector[]
     */
    protected $rules;

    /**
     * The instance of the user that made the changes.
     * 
     * @var VBOHistoryCommitter
     */
    protected $committer = null;

    /**
     * Class constructor.
     * 
     * @param  VBOHistoryModel       $model  The storage model.
     * @param  VBOHistoryDetector[]  $rules  An array of rules.
     */
    public function __construct(VBOHistoryModel $model, array $rules)
    {
        $this->model = $model;
        $this->rules = $rules;
    }

    /**
     * Returns the details of the committer.
     * 
     * @return  VBOHistoryCommitter
     */
    public function getCommitter()
    {
        if ($this->committer === null) {
            // fetch client section
            $isSite = JFactory::getApplication()->isClient('site');

            // check if the user is an operator
            $operator = $isSite ? VikBooking::getOperatorInstance()->getOperatorAccount() : null;

            if ($operator) {
                // user logged in as operator
                $user = (object) $operator;
                $user->name = trim($user->first_name . ' ' . $user->last_name);
                $role = 'operator';
            } else {
                // CMS user
                $user = JFactory::getUser();
                $role = $isSite ? 'guest' : 'admin';
            }

            // instantiate committer only once
            $this->committer = new VBOHistoryCommitter($user->id, $user->name, $role);
        }

        return $this->committer;
    }

    /**
     * Tracks any changes made to the provided item.
     * 
     * @param   object|array  $prev  The item details before the changes.
     * @param   object|array  $curr  The item details after the changes.
     * 
     * @return  bool  True in case of changes, false otherwise.
     */
    public function track($prev, $curr)
    {
        $events = [];

        // iterate all the supported rules
        foreach ($this->rules as $rule) {
            try {
                if (!$rule instanceof VBOHistoryDetector) {
                    // detector class not valid
                    throw new UnexpectedValueException('Changes detector rule not valid: ' . get_class($rule), 400);
                }

                // check whether the current observer found some changes
                if ($rule->hasChanged((object) $prev, (object) $curr)) {
                    $events[] = $rule;
                }
            } catch (Exception $error) {
                // ignore error and go ahead
            }
        }

        if (!$events) {
            // nothing has changed
            return false;
        }

        try {
            // save the changes
            $this->model->save($events, $this->getCommitter());
        } catch (Exception $error) {
            // ignore saving errors
        }

        return true;
    }
}

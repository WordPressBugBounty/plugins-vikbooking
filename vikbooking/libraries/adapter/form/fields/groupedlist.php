<?php
/** 
 * @package     VikWP - Libraries
 * @subpackage  adapter.form
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

jimport('joomla.form.formfield');

/**
 * Form field class to handle dropdown fields with groups.
 *
 * @since 10.1.51
 */
class JFormFieldGroupedlist extends JFormField
{
	/**
	 * The layout identifier for list fields.
	 *
	 * @var string
	 */
	protected $layoutId = 'html.form.fields.groupedlist';

	/**
	 * Internally saves the default option groups.
	 * 
	 * @var array[]
	 */
	protected $groups = [];

	/**
	 * Helper method to setup the field.
	 *
	 * @param 	SimpleXMLElement 	$field 	The field element.
	 *
	 * @return 	void
	 */
	protected function setup($element)
	{
		parent::setup($element);

		// unset group and option erroneously registered
		unset($this->option, $this->group);

		$this->groups = [];
		$label = '';

		foreach ($element->children() as $child)
		{
            switch ($child->getName())
            {
                // the element is an <option />
                case 'option':
                    // initialize the group if necessary
                    if (!isset($this->groups[$label]))
                    {
                        $this->groups[$label] = [];
                    }

                    $disabled = (string) $child['disabled'];
                    $disabled = ($disabled === 'true' || $disabled === 'disabled' || $disabled === '1');

                    // create a new option object based on the <option /> element
                    $this->groups[$label][] = JHtml::fetch(
                        'select.option',
                        $child['value'] ? (string) $child['value'] : trim((string) $child),
                        JText::translate(trim((string) $child)),
                        'value',
                        'text',
                        $disabled
                    );

                    break;

                // the element is a <group />
                case 'group':
                    // get the group label
                    if ($groupLabel = (string) $child['label'])
                    {
                        $label = JText::translate($groupLabel);
                    }

                    // initialize the group if necessary
                    if (!isset($groups[$label]))
                    {
                        $groups[$label] = [];
                    }

                    // Iterate through the children and build an array of options.
                    foreach ($child->children() as $option)
                    {
                        // only add <option /> elements
                        if ($option->getName() !== 'option')
                        {
                            continue;
                        }

                        $disabled = (string) $option['disabled'];
                        $disabled = ($disabled === 'true' || $disabled === 'disabled' || $disabled === '1');

                        // create a new option object based on the <option /> element
                        $this->groups[$label][] = JHtml::fetch(
                            'select.option',
                            $option['value'] ? (string) $option['value'] : JText::translate(trim((string) $option)),
                            JText::translate(trim((string) $option)),
                            'value',
                            'text',
                            $disabled
                        );
                    }

                    break;
            }
        }

        reset($this->groups);
	}

	/**
	 * Method to get the options to populate list
	 *
	 * @return  array  The field option objects.
	 */
	public function getGroups()
	{
		return $this->groups;
	}

	/**
	 * @override
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return 	array 	An associative array of display data.
	 */
	public function getLayoutData()
	{
		$data = array();
		$data['name']     = $this->name;
		$data['class']    = $this->class;
		$data['id']       = $this->id;
		$data['value']    = is_null($this->value) ? $this->default : $this->value;
		$data['required'] = $this->required === "true" || $this->required === true ? true : false;
		$data['multiple'] = !is_null($this->multiple) && $this->multiple != "false" ? true : false;
		$data['disabled'] = $this->disabled === "true" || $this->disabled === true ? true : false;
		$data['groups']   = $this->getGroups();

		// flatten the groups list as the options might be used by other components
		$data['options'] = array_merge([], ...array_values($data['groups']));

		return $data;
	}
}

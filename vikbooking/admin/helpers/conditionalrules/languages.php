<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for conditional rule "languages".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleLanguages extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBOBOOKINGLANG');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_LANG_DESCR');
		$this->ruleId = basename(__FILE__);
	}

	/**
	 * Displays the rule parameters.
	 * 
	 * @return 	void
	 */
	public function renderParams()
	{
		$this->vbo_app->loadSelect2();
		$langs = $this->loadLanguages();
		$current_langs = $this->getParam('langs', array());
		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBOBOOKINGLANG'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('langs', true); ?>" id="<?php echo $this->inputID('langs'); ?>" multiple="multiple">
				<?php
				foreach ($langs as $ltag => $lang) {
					?>
					<option value="<?php echo $ltag; ?>"<?php echo is_array($current_langs) && in_array($ltag, $current_langs) ? ' selected="selected"' : ''; ?>><?php echo $lang['name']; ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('#<?php echo $this->inputID('langs'); ?>').select2();
			});
		</script>
		<?php
	}

	/**
	 * Tells whether the rule is compliant.
	 * 
	 * @return 	bool 	True on success, false otherwise.
	 */
	public function isCompliant()
	{
		$book_lang = $this->getPropVal('booking', 'lang');

		if (empty($book_lang)) {
			$book_lang = VikBooking::getTranslator()->getDefaultLang('admin');
		}

		return in_array($book_lang, $this->getParam('langs', array()));
	}

	/**
	 * Internal function for this rule only.
	 * 
	 * @return 	array
	 */
	protected function loadLanguages()
	{
		$known_langs = $this->vbo_app->getKnownLanguages();
		$default_lang = VikBooking::getTranslator()->getDefaultLang('site');
		$langs = array();

		foreach ($known_langs as $ltag => $ldet) {
			if ($ltag == $default_lang) {
				$langs = array($ltag => $ldet) + $langs;
			} else {
				$langs[$ltag] = $ldet;
			}
		}

		return $langs;
	}

}

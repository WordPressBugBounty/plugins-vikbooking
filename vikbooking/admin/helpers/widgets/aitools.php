<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "AI Tools".
 * 
 * @since 1.17 (J) - 1.7 (WP)
 */
class VikBookingAdminWidgetAitools extends VikBookingAdminWidget
{
	/**
	 * @inheritDOc
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_AITOOLS_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_AITOOLS_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		$this->widgetIcon = '<i class="' . VikBookingIcons::i('magic') . '"></i>';
		$this->widgetStyleName = 'violet';
	}

	/**
	 * @inheritDoc
	 */
	public function getPriority()
	{
		// give this widget a higher priority
		return 15;
	}

	/**
	 * @inheritDoc
	 */
	public function preflight()
	{
		if (VBOPlatformDetection::isJoomla()) {
			// can be used only if VCM is installed and the AI channel is supported (not necessarily active)
			return class_exists('VikChannelManager') && defined('VikChannelManagerConfig::AI');
		}

		return parent::preflight();
	}

	/**
	 * @inheritDoc
	 */
	public function preload()
	{
		$options = [
			'version' => defined('VIKCHANNELMANAGER_SOFTWARE_VERSION') ? VIKCHANNELMANAGER_SOFTWARE_VERSION : VIKBOOKING_SOFTWARE_VERSION,
		];

		if (class_exists('VikChannelManager')) {
			// manually load required dependencies
			JHtml::fetch('script', VCM_ADMIN_URI . 'layouts/ai/assistant/aitools.js', $options);
			JHtml::fetch('stylesheet', VCM_ADMIN_URI . 'layouts/ai/assistant/aitools.css', $options);

			JHtml::fetch('script', VCM_ADMIN_URI . 'assets/js/katex/katex.min.js', $options);
			JHtml::fetch('script', VCM_ADMIN_URI . 'assets/js/katex/auto-render.min.js', $options);
			JHtml::fetch('stylesheet', VCM_ADMIN_URI . 'assets/js/katex/katex.min.css', $options);

			// language definitions
			JText::script('VBO_AI_ASSISTANT_DISCLAIMER');
			JText::script('VBO_AI_ASSISTANT_DISCOVER_HINT');
			JText::script('VBO_AI_ASSISTANT_DISCOVER_TITLE');
		}
	}

	/**
	 * @inheritDoc
	 */
	public function render(?VBOMultitaskData $data = null)
	{
		$auto_focus = false;
		$prompt = null;
		$scope = null;

		// if we are rendering the widget on a modal, register a script to prevent the latter
		// from dismissing whenever the ESC key is pressed
		if ($data && $data->isModalRendering()) {
			$auto_focus = true;
			$prompt = (array) $this->getOption('prompt', null);
			$scope = $this->getOption('scope');
			?>
<script>
	(function() {
		const vbo_modal_ai_assistant_dismiss = (event) => {
			if (event.key == 'Escape') {
				event.preventDefault();
				event.stopPropagation();
				return false;
			}
		};

		document.addEventListener('keyup', vbo_modal_ai_assistant_dismiss);

		document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $data->getModalJsIdentifier(); ?>', (e) => {
			document.removeEventListener('keyup', vbo_modal_ai_assistant_dismiss);
			VBOCore.emitEvent('vbo-widget-ai-tools-dismissed');
		});
	})();
</script>
			<?php
		}

		// display by using a specific layout provided by VCM
		echo JLayoutHelper::render(
			'ai.assistant.chat',
			[
				// prevent the layout from loading the required assets
				'load_assets' => false,
				// auto-focus textarea
				'auto_focus' => $auto_focus,
				// default prompt
				'prompt' => $prompt,
				// default scope
				'scope' => $scope,
				// show widget title (VBO dashboard only)
				'widget_title' => is_null($data),
				// widget icon
				'widget_icon' => $this->widgetIcon,
				// widget name
				'widget_name' => $this->widgetName,
			],
			null,
			[
				'component' => 'com_vikchannelmanager',
				'client' => 'admin',
			]
		);
	}
}

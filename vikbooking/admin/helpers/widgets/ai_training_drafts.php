<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "AI Training Drafts".
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
class VikBookingAdminWidgetAiTrainingDrafts extends VikBookingAdminWidget
{
    /**
     * @var  int
     */
    protected static $instance_counter = -1;

    /**
     * @inheritDOc
     */
    public function __construct()
    {
        // call parent constructor
        parent::__construct();

        $this->widgetName = JText::translate('VBO_W_AITRAININGDRAFTS_TITLE');
        $this->widgetDescr = JText::translate('VBO_W_AITRAININGDRAFTS_DESCR');
        $this->widgetId = basename(__FILE__, '.php');

        $this->widgetIcon = '<i class="' . VikBookingIcons::i('book') . '"></i>';
        $this->widgetStyleName = 'pink';
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        // give this widget a higher priority
        return 14;
    }

    /**
     * @inheritDoc
     */
    public function preflight()
    {
        // can be used only if VCM is installed and the AI channel is supported (not necessarily active)
        return class_exists('VikChannelManager') && defined('VikChannelManagerConfig::AI') && parent::preflight();
    }

    /**
     * @inheritDoc
     */
    public function preload()
    {
        // load assets
        $this->vbo_app->loadSelect2();
    }

    /**
     * @inheritDoc
     */
    public function render(?VBOMultitaskData $data = null)
    {
        // increase widget's instance counter
        static::$instance_counter++;

        // check whether the widget is being rendered via AJAX when adding it through the customizer
        $is_ajax = $this->isAjaxRendering();

        // generate a unique ID for the sticky notes wrapper instance
        $wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
        $wrapper_id = 'vbo-widget-bookdets-' . $wrapper_instance;

        // get permissions
        if (!JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking')) {
            // display nothing
            return;
        }

        // check for multitask data values
        $js_intvals_id = '';
        if ($data) {
            // access Multitask data
            if ($data->isModalRendering()) {
                // get modal JS identifier
                $js_intvals_id = $data->getModalJsIdentifier();
            }
        }

        try {
            if (!class_exists('VCMAiModelTraining')) {
                throw new Exception('E4jConnect Channel Manager unavailable or unsupported.', 500);
            }

            // load the draft training sets
            $response = (new VCMAiModelTraining)->getItems([
                // needs review
                'status' => 2,
            ], [
                'ordering' => 'created',
                'direction' => 'asc',
                'offset' => 0,
                'limit' => 20,
            ]);
        } catch (Exception $e) {
            // store the exception
            $response = $e;
        }

        ?>
        <div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" data-instance="<?php echo $wrapper_instance; ?>">

            <div class="vbo-admin-widget-head">
                <div class="vbo-admin-widget-head-inline">
                    <h4><?php echo $this->getIcon(); ?> <span><?php echo $this->getName(); ?></span></h4>
                </div>
            </div>

            <div class="vbo-widget-aitrainingdrafts-wrap">
            <?php
            try {
                // load draft records upon widget rendering
                echo $this->loadDrafts(true)['html'];
            } catch (Exception $e) {
                ?>
                <p class="err"><?php echo $e->getMessage(); ?></p>
                <?php
            }
            ?>
            </div>

        </div>
        <?php
        if (static::$instance_counter === 0 || $is_ajax) {
            ?>
        <script>
            /**
             * Register function for loading the draft training sets.
             */
            function vbo_w_aitrainingdrafts_load(wrapper_id) {
                const wrapper = document.getElementById(wrapper_id);

                const output = wrapper.querySelector('.vbo-widget-aitrainingdrafts-wrap');

                // the widget method to call
                let call_method = 'loadDrafts';

                VBOCore.doAjax(
                    "<?php echo $this->getExecWidgetAjaxUri(); ?>",
                    {
                        widget_id: "<?php echo $this->getIdentifier(); ?>",
                        call:      call_method,
                        return:    1,
                    },
                    (response) => {
                        // append HTML response
                        output.innerHTML = response.html;
                    },
                    (error) => {
                        // display error
                        let errorEl = document.createElement('p');
                        errorEl.classList.add('err');
                        errorEl.innerText = error.responseText || 'Unknown error';
                        // set error content
                        output.innerHTML = errorEl;
                    }
                );
            }

            /**
             * Register function to perform a translation of an approved draft ID.
             */
            function vbo_w_aitrainingdrafts_translate(draftId) {
                return new Promise((resolve, reject) => {
                    // make a request to translate the draft
                    VBOCore.doAjax(
                        "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=training.translate'); ?>",
                        {
                            id: [draftId],
                            languages: <?php echo json_encode(array_column(VikBooking::getVboApplication()->getKnownLanguages(), 'tag')); ?>,
                        },
                        (response) => {
                            // resolve promise
                            resolve(response);
                        },
                        (error) => {
                            // reject promise
                            reject(error);
                        }
                    );
                });
            }

            /**
             * Register function for handling a global body click event delegation.
             */
            function vbo_w_aitrainingdrafts_click_delegation(e) {
                // draft record click
                if (e.target.matches('.vbo-ai-training-draft-record') || e.target.closest('.vbo-ai-training-draft-record')) {
                    const draftEl = !e.target.matches('.vbo-ai-training-draft-record') ? e.target.closest('.vbo-ai-training-draft-record') : e.target;
                    const draftId = draftEl.getAttribute('data-item-id');

                    const wrapper_id = draftEl.closest('.vbo-admin-widget-wrapper').getAttribute('id');

                    // reject button
                    let modalCancelBtn = document.createElement('button');
                    modalCancelBtn.setAttribute('type', 'button');
                    modalCancelBtn.classList.add('btn', 'btn-danger');
                    modalCancelBtn.innerText = <?php echo json_encode(JText::translate('VBO_REJECT')); ?>;
                    modalCancelBtn.addEventListener('click', () => {
                        if (!confirm(<?php echo json_encode(JText::translate('VBDELCONFIRM')); ?>)) {
                            return;
                        }

                        // disable button
                        modalCancelBtn.disabled = true;

                        // start loading
                        VBOCore.emitEvent('vbo-ai-training-drafts-loading');

                        // make a request to delete the draft
                        VBOCore.doAjax(
                            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=training.delete'); ?>",
                            {
                                cid: [draftId],
                                ajax: 1,
                            },
                            (response) => {
                                // dismiss modal
                                VBOCore.emitEvent('vbo-ai-training-drafts-dismiss');

                                // reload drafts
                                vbo_w_aitrainingdrafts_load(wrapper_id);
                            },
                            (error) => {
                                // log and display error
                                console.error(error);
                                alert(error.responseText);

                                // stop loading
                                VBOCore.emitEvent('vbo-ai-training-drafts-loading');

                                // re-enable button
                                modalCancelBtn.disabled = false;
                            }
                        );
                    });

                    // approve button
                    let modalApproveBtn = document.createElement('button');
                    modalApproveBtn.setAttribute('type', 'button');
                    modalApproveBtn.classList.add('btn', 'btn-success');
                    modalApproveBtn.innerText = <?php echo json_encode(JText::translate('VBO_APPROVE')); ?>;
                    modalApproveBtn.addEventListener('click', () => {
                        // disable button
                        modalApproveBtn.disabled = true;

                        // start loading
                        VBOCore.emitEvent('vbo-ai-training-drafts-loading');

                        // get form data
                        let draftForm = new FormData(document.querySelector('#vbo-training-draft-form'));

                        // append additional parameters
                        draftForm.append('ajax', 1);

                        // make a request to approve the draft
                        VBOCore.doAjax(
                            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=training.save'); ?>",
                            draftForm,
                            async (response) => {
                                // draft approved successfully, attempt to translate it into other known languages
                                try {
                                    // hold until the promise resolves
                                    await vbo_w_aitrainingdrafts_translate(draftId);
                                } catch(e) {
                                    // do nothing
                                    console.error(e);
                                }

                                // dismiss modal
                                VBOCore.emitEvent('vbo-ai-training-drafts-dismiss');

                                // reload drafts
                                vbo_w_aitrainingdrafts_load(wrapper_id);
                            },
                            (error) => {
                                // log and display error
                                console.error(error);
                                alert(error.responseText);

                                // re-enable button
                                modalApproveBtn.disabled = false;

                                // stop loading
                                VBOCore.emitEvent('vbo-ai-training-drafts-loading');
                            }
                        );
                    });

                    // display modal
                    let modalBody = VBOCore.displayModal({
                        suffix:        'ai-training-drafts-review',
                        title:         <?php echo json_encode(JText::translate('VBO_REVIEW_TRAINING_DRAFT')); ?>,
                        lock_scroll:   true,
                        footer_left:   modalCancelBtn,
                        footer_right:  modalApproveBtn,
                        extra_class:   'vbo-modal-rounded vbo-modal-tall vbo-modal-taller vbo-modal-fullscreen',
                        loading_event: 'vbo-ai-training-drafts-loading',
                        dismiss_event: 'vbo-ai-training-drafts-dismiss',
                    });

                    // start loading
                    VBOCore.emitEvent('vbo-ai-training-drafts-loading');

                    // make a request to obtain the template
                    VBOCore.doAjax(
                        "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=training.editdraft'); ?>",
                        {
                            cid: [draftId],
                        },
                        (response) => {
                            // append response to body
                            modalBody.append(response?.html);

                            // stop loading
                            VBOCore.emitEvent('vbo-ai-training-drafts-loading');
                        },
                        (error) => {
                            // log and display error
                            console.error(error);
                            alert(error.responseText);

                            // dismiss modal
                            VBOCore.emitEvent('vbo-ai-training-drafts-dismiss');
                        }
                    );

                    // do not proceed
                    return;
                }
            }
        </script>
            <?php
        }
        ?>

        <script>
            VBOCore.DOMLoaded(() => {
                /**
                 * Add body click event delegation for elements that will be later added to the DOM.
                 */
                if (!VBOCore.wasEventDelegated('ai.training.drafts')) {
                    document.body.addEventListener('click', vbo_w_aitrainingdrafts_click_delegation);
                    VBOCore.setEventDelegated('ai.training.drafts');
                }

            <?php
            if ($js_intvals_id) {
                // widget can be dismissed through the modal
                ?>
                document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $js_intvals_id; ?>', (e) => {
                    // remove body click events delegation for this widget
                    document.body.removeEventListener('click', vbo_w_aitrainingdrafts_click_delegation);
                    VBOCore.unsetEventDelegated('ai.training.drafts');
                });
                <?php
            }
            ?>
            });
        </script>
        <?php
    }

    /**
     * Loads the draft training sets.
     * 
     * @param   bool    $return     True to return just the HTML template.
     * 
     * @return  mixed
     * 
     * @throws  Exception
     */
    public function loadDrafts(bool $return = false)
    {
        $app = JFactory::getApplication();

        if (!class_exists('VCMAiModelTraining')) {
            if ($return) {
                throw new Exception('E4jConnect Channel Manager unavailable or unsupported.', 500);
            }
            VBOHttpDocument::getInstance($app)->close(500, 'E4jConnect Channel Manager unavailable or unsupported.');
        }

        try {
            // load the draft training sets
            $response = (new VCMAiModelTraining)->getItems([
                // needs review
                'status' => 2,
            ], [
                'ordering' => 'created',
                'direction' => 'asc',
                'offset' => 0,
                'limit' => 20,
            ]);
        } catch (Exception $e) {
            // raise an error
            if ($return) {
                throw $e;
            }
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        // build HTML response
        $html = <<<HTML
        <div class="vbo-widget-latestevents-list vbo-widget-aitrainingdrafts-list">
        HTML;

        // render drafts list
        $pendingLabel = JText::translate('VBO_TASK_STATUS_TYPE_PENDING');

        foreach (($response->items ?? []) as $item) {
            // template variables
            $draftContent = JHtml::fetch('vikbooking.shorten', $item->content, 200);
            $draftDate = JHtml::fetch('date.relative', $item->created, null, null, 'Y-m-d H:i:s');

            // append HTML record
            $html .= <<<HTML
            <div class="vbo-widget-history-record vbo-ai-training-draft-record" data-item-id="{$item->id}">
                <div class="vbo-widget-history-content">
                    <div class="vbo-widget-history-content-head">
                        <div class="vbo-widget-history-content-info-details">
                            <span class="label label-warning vbo-status-label">$pendingLabel</span>
                            <h4>{$item->title}</h4>
                        </div>
                        <div class="vbo-widget-history-content-info-booking">
                            <div class="vbo-widget-history-content-info-dates">
                                <span>$draftContent</span>
                            </div>
                        </div>
                    </div>
                    <div class="vbo-widget-history-content-info-msg">
                        <span class="vbo-widget-history-content-info-msg-descr">$draftDate</span>
                    </div>
                </div>
            </div>
            HTML;
        }

        if (!($response->items ?? [])) {
            $html .= '<p class="info">' . JText::translate('VBO_NO_RECORDS_FOUND') . '</p>';
        }

        $html .= <<<HTML
        </div>
        HTML;

        // build response data
        $responseData = [
            'html' => $html,
            'response' => $response,
        ];

        if ($return) {
            // return just the HTML string
            return $responseData;
        }

        // send response to output
        VBOHttpDocument::getInstance($app)->json($responseData);
    }
}

(function($, w) {
	'use strict';

	const makeHelpWizardRequest = (data) => {
		return new Promise((resolve, reject) => {
			VBOCore.doAjax(
				w.VBO_HELP_WIZARD_AJAX_URL,
				data,
				(resp) => {
					resolve(resp);
				}, (err) => {
					reject(err.responseText || err.statusText || 'Error');
				}
			);
		});
	}

	const normalizePostData = (data) => {

		if (data === undefined) {
			data = {};
		} else if (Array.isArray(data)) {
			// the form data is serialized @see jQuery.serializeArray()
			var form = data;

			data = {};

			for (var i = 0; i < form.length; i++) {
				// if the field ends with [] it should be an array
				if (form[i].name.endsWith("[]")) {
					// if the field doesn't exist yet, create a new list
					if (!data.hasOwnProperty(form[i].name)) {
						data[form[i].name] = new Array();
					}

					// append the value to the array
					data[form[i].name].push(form[i].value);
				} else {
					// otherwise overwrite the value (if any)
					data[form[i].name] = form[i].value;
				}
			}
		}

		return data;
	}

	w.VBOHelpWizard = class VBOHelpWizard {
		static getNextInstruction() {
			return makeHelpWizardRequest({
				task: 'helpwizard.show',
			});
		}

		static dismissInstruction(id, datetime) {
			return makeHelpWizardRequest({
				task: 'helpwizard.dismiss',
				instruction: id,
				datetime: datetime,
			});
		}

		static processInstruction(id, args) {
			return makeHelpWizardRequest({
				task: 'helpwizard.process',
				instruction: id,
				args: args,
			});
		}

		static setupEligibleInstruction() {
			VBOHelpWizard.getNextInstruction().then((instruction) => {
				const currentInstruction = $('.vbo-menu-help-wizard-container[data-instruction]');

				if (currentInstruction.length) {
					currentInstruction.remove();
				}

				if (instruction.has) {
					VBOHelpWizard.displayBadge(instruction);
				}
			}).catch((error) => {
				console.warn('Help wizard failure!\n' + error);
			});
		}

		static displayBadge(instruction) {
			const menuContainer = $('<div class="vbo-menu-help-wizard-container"></div>')
				.attr('data-instruction', instruction.id);

			const menuButton = $('<button type="button" class="vbo-help-wizard-handler"></button>')
				.attr('title', instruction.title)
				.html($('<i></i>').addClass(instruction.icon));

			menuButton.on('click', () => {
				const modalOptions = {
					suffix: 'help-wizard-modal',
                    title: instruction.title,
                    extra_class: 'vbo-modal-rounded vbo-modal-dialog',
                    escape_dismiss: false,
                    body_prepend: false,
                    lock_scroll: true,
                    dismiss_event: 'vbo-help-wizard-instruction-modal-dismiss',
				};

				if (instruction.dismissible) {
					modalOptions.footer_left = $('<button type="button" class="btn"></button>')
						.text(Joomla.JText._('VBDISMISS'))
						.on('click', async function() {
							$(this).prop('disabled', true);

							try {
								// process instruction
								await VBOHelpWizard.dismissInstruction(instruction.id);

								// dismiss succeeded, remove "help" button from menu
								menuContainer.remove();

								VBOCore.emitEvent('vbo-help-wizard-instruction-modal-dismiss');

								// move to the next instruction, if any
								setTimeout(() => {
									VBOHelpWizard.setupEligibleInstruction();
								}, 1000);
							} catch (err) {
								alert(err);
							}

							$(this).prop('disabled', false);
						});
				}

				if (instruction.processable) {
					modalOptions.footer_right = $('<button type="button" class="btn btn-success"></button>')
						.text(instruction.processtext || Joomla.JText._('VBSAVE'))
						.on('click', async function() {
							$(this).prop('disabled', true);

							// converts the form inputs into a key-value pairs object
							const data = normalizePostData($('#help-wizard-instruction-form').serializeArray());

							try {
								// process instruction
								const response = await VBOHelpWizard.processInstruction(instruction.id, data);

								// process succeeded, remove "help" button from menu
								menuContainer.remove();

								VBOCore.emitEvent('vbo-help-wizard-instruction-modal-dismiss');

								setTimeout(() => {
									if (response.redirect) {
										// follow the returned URL
										document.location.href = response.redirect;
									} else {
										VBOHelpWizard.setupEligibleInstruction();
									}
								}, 1000);
							} catch (err) {
								alert(err);
							}

							$(this).prop('disabled', false);
						});
				}

				// display modal
                const modalBody = VBOCore.displayModal(modalOptions);
                modalBody.append(instruction.html);
			});

			menuContainer.append(menuButton);

			$('.vbo-menu-container .vbo-menu-updates').append(menuContainer);
		}
	}

	$(function() {
		VBOHelpWizard.setupEligibleInstruction();
	});

	/**
	 * For configurable reports only.
	 */
	document.addEventListener('vbo-report-settings-saved', (event) => {
        VBOHelpWizard.setupEligibleInstruction();
    });
})(jQuery, window);
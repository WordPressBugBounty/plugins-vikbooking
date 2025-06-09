/**
 * @package     VikBooking
 * @subpackage  vik-content-builder
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com - https://e4j.com - https://e4jconnect.com
 */

/**
 * Class handler to store the editor instances and switch building modes.
 * 
 * AJAX requests may load this asset multiple times, so we declare an anonymous class, but
 * we assign it to a variable using "var" because it is not block-scoped like "let" or "const".
 */
var VikContentBuilder = class {

	/** @var  array */
	static editors = new Array;

	/**
	 * Push the instance of an editor to the pool.
	 * 
	 * @param 	object 	editor 	The editor instance.
	 * 
	 * @return 	void
	 */
	static pushEditor(editor) {
		this.editors.push(editor);
	}

	/**
	 * Switch between building modes.
	 * 
	 * @param 	object 	elem 	DOM element object clicked.
	 * @param 	string 	mode 	Optional mode to switch to.
	 * 
	 * @return 	void
	 */
	static switchMode(elem, mode) {
		// determine who triggered the mode switch
		let btn = jQuery(elem);
		if (mode) {
			btn = btn.closest('.vik-contentbuilder-wrapper').find('.vik-contentbuilder-switcher-btn[data-switch="' + mode + '"]');
		}

		// determine the new building mode to activate
		let switch_to = btn.attr("data-switch");

		// toggle active mode button
		btn.parent('.vik-contentbuilder-switcher').find('.vik-contentbuilder-switcher-btn').removeClass('vik-contentbuilder-switcher-btn-active');
		btn.addClass('vik-contentbuilder-switcher-btn-active');

		// hide any mode content except the wanted one
		let ed_conts = btn.closest('.vik-contentbuilder-wrapper');
		ed_conts.find('.vik-contentbuilder-inner [data-switch]').not('[data-switch="' + switch_to + '"]').hide();

		// target building mode content
		let target = ed_conts.find('.vik-contentbuilder-inner [data-switch="' + switch_to + '"]');

		// determine how to display the requested building mode
		if (switch_to.indexOf('modal') >= 0) {
			// modal window
			let container_id = target.attr('data-container');
			if (!container_id) {
				container_id = switch_to;
			}

			// get the proper visual editor container to move and display
			let editor_container = ed_conts.find('.vik-contentbuilder-inner [data-switch="' + container_id + '"]').find('.vik-contentbuilder-editor-wrap');
			if (editor_container.length) {
				// check if we also have an inline visual editor mode
				let has_inline_ve = ed_conts.find('.vik-contentbuilder-inner [data-switch="visual"]').length;

				// display the modal window with the visual editor
				let modal_body = VBOCore.displayModal({
					suffix: 'contentbuilder-' + switch_to,
					extra_class: 'vbo-modal-rounded vbo-modal-large vbo-modal-nofooter vik-contentbuilder-modal-content',
					lock_scroll: true,
					draggable: false,
					onDismiss: () => {
						// put back the editor container element on its original position
						editor_container.appendTo(ed_conts.find('.vik-contentbuilder-inner [data-switch="' + container_id + '"]'));
						// switch to text mode or inline visual mode
						VikContentBuilder.switchMode(btn, (has_inline_ve ? 'visual' : 'text'));
					},
				});

				// move the editor container element to the body of the modal window
				editor_container.appendTo(modal_body);
			} else {
				// unexpcted markup, fallback to displaying the target
				target.show();
			}
		} else {
			// show document target element
			target.show();
		}

		// handle text mode special tag buttons
		let text_mode_tags = jQuery('.vik-contentbuilder-textmode-sptags');
		if (text_mode_tags.length && switch_to.indexOf('text') >= 0) {
			text_mode_tags.show();
		} else if (text_mode_tags.length && switch_to.indexOf('visual') >= 0) {
			text_mode_tags.hide();
		}
	}
}

/**
 * Class handler for uploading an image file and display it through Quill.
 * 
 * AJAX requests may load this asset multiple times, so we declare an anonymous class, but
 * we assign it to a variable using "var" because it is not block-scoped like "let" or "const".
 */
var VikContentBuilderImageHandler = class {
	
	constructor(editor, endpoint) {
		this.editor = editor;
		this.endpoint = endpoint || null;
	}

	setEndpoint(endpoint) {
		if (endpoint && endpoint.length) {
			this.endpoint = endpoint;
		}
		return this;
	}

	present() {
		var input = document.createElement('input');
		input.setAttribute('type', 'file');
		input.onchange = () => {
			var file = input.files[0];
			var rgxi = /^image\//;
			if (!file || !file.name || !rgxi.test(file.type)) {
				alert('Only images are allowed.');
				return;
			}
			this.upload(file);
		};
		input.click();
	}

	upload(file) {
		if (!file instanceof File) {
			console.error('Invalid file');
			return false;
		}

		if (!this.endpoint) {
			console.error('Missing endpoint');
			return false;
		}
		
		// build form data
		var formd = new FormData();
		formd.append('file', file);
		formd.append('type', 'image');

		// start progress
		this.startProgress();

		// perform the AJAX upload
		var that = this;
		var jqxhr = jQuery.ajax({
			xhr: function() {
				var xhrobj = jQuery.ajaxSettings.xhr();
				if (xhrobj.upload) {
					xhrobj.upload.addEventListener('progress', function(event) {
						var percent = 0;
						var position = event.loaded || event.position;
						var total = event.total;
						if (event.lengthComputable) {
							percent = Math.ceil(position / total * 100);
						}
						// update progress
						that.setProgress(percent);
					}, false);
				}
				return xhrobj;
			},
			url: that.endpoint,
			type: 'POST',
			contentType: false,
			processData: false,
			cache: false,
			data: formd,
			success: function(resp) {
				try {
					resp = JSON.parse(resp);
					if (resp.status == 1) {
						that.complete(resp);
					} else {
						throw resp.error ? resp.error : 'An error occurred! Please try again.';
					}
				} catch (err) {
					that.stopProgress();
					console.warn(err, resp);
					alert(err);
				}
			},
			error: function(err) {
				that.stopProgress();
				console.error(err.responseText);
				alert('An error occurred. Please try again.');
			}, 
		});
	}

	complete(resp) {
		this.stopProgress();
		this.editor.insertEmbed(this.editor.getSelection().index, 'image', resp.url);
	}

	startProgress() {
		// access the toolbar module
		var toolbar = null;
		try {
			toolbar = this.editor.getModule('toolbar').container;
		} catch(e) {
			// do nothing
		}
		if (!toolbar) {
			return false;
		}

		// create progress element
		this.progress_el = document.createElement("PROGRESS");
		this.progress_el.setAttribute('max', 100);
		this.progress_el.setAttribute('value', 0);

		// append progress to toolbar
		toolbar.appendChild(this.progress_el);
	}

	setProgress(value) {
		if (!this.progress_el) {
			return false;
		}
		this.progress_el.setAttribute('value', value);
	}

	stopProgress() {
		if (!this.progress_el) {
			return false;
		}
		this.progress_el.remove();
		this.progress_el = null;
	}
}

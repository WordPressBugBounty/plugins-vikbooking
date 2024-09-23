(function(wp) {

	/**
	 * Registers a new block provided a unique name and an object defining its behavior.
	 * @link https://github.com/WordPress/gutenberg/tree/master/blocks#api
	 */
	const registerBlockType = wp.blocks.registerBlockType;

	/**
	 * Returns a new element of given type. Element is an abstraction layer atop React.
	 * @link https://github.com/WordPress/gutenberg/tree/master/packages/element#element
	 */
	const el = wp.element.createElement;

	/**
	 * Retrieves the translation of text.
	 * @link https://github.com/WordPress/gutenberg/tree/master/i18n#api
	 */
	const __ = wp.i18n.__;

	/**
	 * This variable is used to keep the very first shortcode
	 * after the loading of the page.
	 */
	let currentShortcode = null;

	/**
	 * This registry holds the information specified during the
	 * creation of a new shortcode.
	 */
	let newShortcodeRegistry = null;

	/**
	 * Every block starts by registering a new block type definition.
	 * @link https://wordpress.org/gutenberg/handbook/block-api/
	 */
	registerBlockType('vikbooking/gutenberg-shortcodes', {
		/**
		 * This is the block display title, which can be translated with `i18n` functions.
		 * The block inserter will show this name.
		 */
		title: __('VikBooking Shortcode', 'vikbooking'),

		/**
		 * This is the block description, which is displayed within the right sidebar.
		 */
		description: __('Add a shortcode configured through VikBooking.', 'vikbooking'),

		/**
		 * The icon can be a DASHICON or a SVG entity.
		 * NOTE: we need to use a different icon because Gutenberg seems
		 * to have problems in displaying the coffee icon.
		 */
		icon: 'building',

		/**
		 * Blocks are grouped into categories to help users browse and discover them.
		 * The categories provided by core are `common`, `embed`, `formatting`, `layout` and `widgets`.
		 */
		category: 'widgets',

		/**
		 * Sometimes a block could have aliases that help users discover it while searching.
		 * You can do so by providing an array of terms (which can be translated).
		 * It is only allowed to add as much as three terms per block.
		 */
		keywords: [
			__('shortcodes'), __('list'), __('page'),
		],

		/**
		 * Optional block extended support features.
		 */
		supports: {
			// do not edit as HTML
			html: false,

			// use the block just once per post
			multiple: false,

			// don't allow the block to be converted into a reusable block
			reusable: false,
		},

		/**
		 * Attributes provide the structured data needs of a block.
		 * They can exist in different forms when they are serialized, 
		 * but they are declared together under a common interface.
		 */
		attributes: {
			shortcode: {
				type: 'string',
				source: 'html',
				selector: 'div',
			},
			toggler: {
				type: 'boolean',
				default: false,
			}
		},

		/**
		 * The edit function describes the structure of your block in the context of the editor.
		 * This represents what the editor will render when the block is used.
		 * @link https://wordpress.org/gutenberg/handbook/block-edit-save/#edit
		 *
		 * @param 	Object 	 props 	Properties passed from the editor.
		 *
		 * @return 	Element  Element to render.
		 */
		edit: (props) => {

			// iterate vikbooking shortcodes to build select options
			let options = [];

			let shortcodes_boxes = [];

			if (currentShortcode === null) {
				// if not set, define current value
				currentShortcode = props.attributes.shortcode;
			}

			// insert empty option
			options.push({
				label: __('- pick a shortcode -', 'vikbooking'),
				value: '',
			});

			// insert an option to support the creation of new shortcodes
			options.push({
				label: __('- create a new shortcode -', 'vikbooking'),
				value: 'new',
			});

			// evaluate if toggler checkbox is checked
			let togglerChecked = props.attributes.toggler;

			for (let group in VIKBOOKING_SHORTCODES_BLOCK.shortcodes) {
				let groups = VIKBOOKING_SHORTCODES_BLOCK.shortcodes[group];

				for (let i = 0; i < groups.length; i++) {
					let data = groups[i];

					let post_id  = parseInt(data.post_id);

					// push option only in case:
					// - toggler is enabled (see all)
					// - the shortcode is not assigned to any post
					// - the page of the shortcode is equal to this one
					// - the shortcode is equal to the current one
					if (togglerChecked || !post_id || post_id === wp.data.select('core/editor').getCurrentPostId() || data.shortcode == currentShortcode) {

						options.push({
							label: data.name,
							value: data.shortcode,
						});

						// build ASSIGNEE field
						let assigneeField = null;

						if (post_id && data.shortcode != currentShortcode) {
							assigneeField = el(
								'a',
								{
									href: 'javascript:void(0);',
									onClick: () => {
										alert(__('This shortcode is already used by a different post. If you select this shortcode, it will be detached from the existing post and assigned to this new post.', 'vikbooking'));
									},
								},
								el(
									'span',
									{
										className: 'assigned',
									},
									__('Post #', 'vikbooking') + post_id
								)
							);
						} else {
							// safe shortcode
							assigneeField = el('span', {}, (post_id ? __('Post #', 'vikbooking') + post_id : '--'));
						}

						// check if the box should be displayed
						let toggled = props.attributes.shortcode == data.shortcode;

						// setup information div
						shortcodes_boxes.push(el(
							'div',
							{
								className: 'vikbooking-shortcode-info-box' + (toggled ? ' toggled' : ''),
							},
							// create child elements
							el(
								'div',
								{
									className: 'vbo-sh-info-control'
								},
								[
									el('label', {}, __('Type:', 'vikbooking')),
									el('span', {}, group),
								]
							),
							el(
								'div',
								{
									className: 'vbo-sh-info-control'
								},
								[
									el('label', {}, __('Name:', 'vikbooking')),
									el('span', {}, data.name),
								]
							),
							el(
								'div',
								{
									className: 'vbo-sh-info-control'
								},
								[
									el('label', {}, __('Created on:', 'vikbooking')),
									el('span', {}, data.createdon),
								]
							),
							el(
								'div',
								{
									className: 'vbo-sh-info-control'
								},
								[
									el('label', {}, __('Assignee:', 'vikbooking')),
									assigneeField
								]
							),
							el(
								'div',
								{
									className: 'vbo-sh-info-control'
								},
								el(
									wp.components.TextareaControl,
									{
										label: __('Shortcode:', 'vikbooking'),
										value: data.shortcode,
										readonly: true,
									}
								)
							),
						));
					}
				}
			}

			// build SVG for shortcode dashicon
			const svg = el(
				'svg',
				{
					className: 'dashicon dashicons-shortcode',
					role: 'img',
					focusable: false,
					xmlns: 'http://www.w3.org/2000/svg',
					width: '20',
					height: '20',
					viewBox: '0 0 20 20',
				},
				el(
					'path',
					{
						d: 'M6 14H4V6h2V4H2v12h4M7.1 17h2.1l3.7-14h-2.1M14 4v2h2v8h-2v2h4V4',
					}
				)
			);

			// use the new version of InspectorControls
			// provided by WordPress 5.4, if supported
			let InspectorControls;

			if (wp.blockEditor && wp.blockEditor.InspectorControls) {
				// use new version
				InspectorControls = wp.blockEditor.InspectorControls;
			} else {
				// fallback to the older one
				InspectorControls = wp.editor.InspectorControls;
			}

			let navBar = [];

			// add shortcode accordion
			navBar.push(
				el(
					wp.components.PanelBody,
					{
						title: __('Shortcode'),
						initialOpen: true,
					},
					// add shortcodes information
					shortcodes_boxes,
					el(
						wp.components.ToggleControl,
						{
							label: __('See all', 'vikbooking'),
							checked: togglerChecked,
							help: togglerChecked ? __('Display all the existing shortcodes.', 'vikbooking') : __('Toggle to display also the shortcodes that are already assigned to a post.', 'vikbooking'),
							onChange: (toggler) => {
								props.setAttributes({ toggler: toggler });
							}
						}
					)
				)
			);

			// in case the shortcode dropdown has the "new" option selected,
			// display a new panel to start the creation of a new shortcode
			if (props.attributes.shortcode === 'new') {

				if (!newShortcodeRegistry) {
					// initialize only once the registry used to hold the data
					// of the new shortcode that we want to create
					newShortcodeRegistry = new VBOBlockPropertiesDecorator({lang: '*'}, props);
				} else {
					// always reconnect the registry to the updated attributes holder 
					// as the saved reference might differ in case the block gets deleted
					newShortcodeRegistry.connect(props);
				}

				let newItemPanelFields = [];

				// add field to select the site page
				newItemPanelFields.push(
					el(
						wp.components.SelectControl,
						{
							label: __('Page Type', 'vikbooking'),
							help: __('Choose the type of the page that should be displayed in the front-end.', 'vikbooking'),
							value: newShortcodeRegistry.attributes.type,
							options: [
								{
									label: __('Select an option', 'vikbooking'),
									value: '',
								}
							].concat(VIKBOOKING_SHORTCODES_BLOCK.views.map((page) => {
								return {
									label: page.name,
									value: page.type,
								};
							})),
							onChange: (value) => {
								// preserve only the language whenever the type changes
								newShortcodeRegistry.replaceAttributes({type: value, lang: newShortcodeRegistry.attributes.lang});
								newShortcodeRegistry.setAttributes({});
							},
						}
					)
				);

				// add field to select the language
				newItemPanelFields.push(
					el(
						wp.components.SelectControl,
						{
							label: __('Language', 'vikbooking'),
							help: __('Choose whether the shortcode should be available for a specific language only.', 'vikbooking'),
							value: newShortcodeRegistry.attributes.lang,
							options: [
								{
									label: __('All', 'vikbooking'),
									value: '*',
								}
							].concat(VIKBOOKING_SHORTCODES_BLOCK.languages.map((lang) => {
								return {
									label: lang.name,
									value: lang.tag,
								};
							})),
							onChange: (value) => {
								newShortcodeRegistry.setAttributes({lang: value});
							},
						}
					)
				);

				// get the details of the selected view
				let selectedView = VIKBOOKING_SHORTCODES_BLOCK.views.filter(view => view.type === newShortcodeRegistry.attributes.type).shift();

				if (selectedView) {
					selectedView.fields.forEach((field) => {
						// check if we have a list with predefined options
						if (Array.isArray(field.options)) {
							// make sure the default value is supported
							if (field.options.filter(opt => opt.value == field.value).length === 0) {
								// take the first option as default value, or an empty array in case of multiple list
								field.value = field.multiple ? [] : field.options[0].value;
							}
						}

						// set up a default value for the current field
						newShortcodeRegistry.defineAttribute(field.name, field.value);

						// create element according to the provided fields
						let fieldControl = window.vboCreateControlElement(field, newShortcodeRegistry);

						// register the element within the list of fields
						newItemPanelFields.push(fieldControl);
					});
				}

				// add the panel to create a new shortcode at runtime
				navBar.push(
					el(
						wp.components.PanelBody,
						{
							title: __('New Item', 'vikbooking'),
							initialOpen: true,
						},
						newItemPanelFields
					)
				);

				// add button to save the shortcode
				newItemPanelFields.push(
					el(
						wp.components.Button,
						{
							text: __('Create Shortcode', 'vikbooking'),
							isPrimary: true,
							onClick: (event) => {
								// make sure we don't have a pending request
								// if (event.target.disabled) {
								if (event.target.getAttribute('aria-disabled') === 'true') {
									return false;
								}

								// prevent duplicate requests
								event.target.setAttribute('aria-disabled', true);
								event.target.classList.add('is-busy');

								// access the post data container
								let postData = wp.data.select('core/editor');

								// access the view parameters
								let params = Object.assign({}, newShortcodeRegistry.attributes);
								delete params.type;
								delete params.lang;

								// get the currently selected parent for this post
								let parentId = postData.getEditedPostAttribute('parent');

								// prepare request data
								const request = {
									name: postData.getEditedPostAttribute('title'),
									type: newShortcodeRegistry.attributes.type,
									lang: newShortcodeRegistry.attributes.lang,
									parent_id: 0,
									jform: params,
								};

								if (parentId) {
									request.parent_id = (
										// we need to find the shortcode assigned to a post ID equal to the parent selected for this page/post
										Object.values(VIKBOOKING_SHORTCODES_BLOCK.shortcodes).flat().filter(shortcode => shortcode.post_id == parentId).shift()
										// use a placeholder in case of no matching shortcodes
										|| {id: 0}
									).id;
								}

								// rely on a promise for a better ease of use
								new Promise((resolve, reject) => {
									// validate the title first
									if (!request.name) {
										reject(__('You need to specify a title for this post first.', 'vikbooking'));
										return;
									}

									// make request to save the shortcode
									jQuery.ajax({
										url: VIKBOOKING_SHORTCODES_BLOCK.ajaxurl,
										type: 'post',
										data: request,
									}).done((shortcode) => {
										resolve(shortcode);
									}).fail((error) => {
										reject(error.responseText || error.statusText);
									});
								}).then((shortcode) => {
									// create a new custom slot is not yet registered
									if (VIKBOOKING_SHORTCODES_BLOCK.shortcodes.custom === undefined) {
										VIKBOOKING_SHORTCODES_BLOCK.shortcodes.custom = [];
									}

									// append the newly created shortcode to the custom slot
									VIKBOOKING_SHORTCODES_BLOCK.shortcodes.custom.push(shortcode);

									// update the selected shortcode
									props.setAttributes({shortcode: shortcode.shortcode});
								}).catch((error) => {
									// something went wrong, alert the user
									alert(error || __('An error has occurred', 'vikbooking'));

									// re-enable save button
									event.target.setAttribute('aria-disabled', false);
									event.target.classList.remove('is-busy');
								});
							},
						}
					)
				);
			}

			// setup inspector (right-side area)
			let controls = el(
				// create InspectorControls element
				InspectorControls,
				// define inspector properties
				{
					key: 'controls',
				},
				navBar
			);

			let renderer;

			if (props.attributes.shortcode === 'new') {
				renderer = el(
					'div',
					{
						style: {
							border: '2px solid #ddd',
							padding: '10px',
							background: '#eee',
						},
					},
					__('You can create a new shortcode from the block settings under the right sidebar.', 'vikbooking')
				);
			} else {
				renderer = el(
					wp.serverSideRender,
					{
						block: 'vikbooking/gutenberg-shortcodes',
						attributes: props.attributes,
					}
				);
			}

			return [
				controls,
				el(
					// create <div> wrapper
					'div',
					// define wrapper properties
					{
						className: 'vbo-shortcode-admin-wrapper',
					},
					// <div> contains select
					[
						el(
							// create <select> for shortcode
							wp.components.SelectControl,
							// define select properties
							{
								label: [svg, __('Shortcode')],
								value: props.attributes.shortcode,
								onChange: (shortcode) => {
									props.setAttributes({shortcode: shortcode});
								},
								options: options,
								className: 'wp-block-shortcode',
							}
						),
						renderer,
					]
				)
			];
		},

		/**
		 * The save function defines the way in which the different attributes should be combined
		 * into the final markup, which is then serialized by Gutenberg into `post_content`.
		 * @link https://wordpress.org/gutenberg/handbook/block-edit-save/#save
		 *
		 * @return 	Element  Element to render.
		 */
		 save: (props) => {
			let shortcode = props.attributes.shortcode;

			return el(
				'div',
				{
					className: props.className,
				},
				shortcode === 'new' ? '' : shortcode
			);
		}
	});
	
	/**
	 * Use a placeholder for the props parameter provided by the edit callback.
	 */
	const VBOBlockPropertiesDecorator = class VBOBlockPropertiesDecorator {
		constructor(props, adaptee) {
			this.attributes = props || {};

			this.connect(adaptee);
		}

		connect(adaptee) {
			// keep a reference to the original inspector properties holder
			this.adaptee = adaptee;
		}

		setAttributes(props) {
			Object.assign(this.attributes, props);

			// update a temporary field within the original attributes to force the inspector refresh
			this.adaptee.setAttributes({refreshTrigger: new Date().toISOString()});
		}

		defineAttribute(key, value) {
			if (!this.attributes.hasOwnProperty(key) && value !== undefined && value !== null) {
				this.attributes[key] = value;
			}
		}

		replaceAttributes(props) {
			this.attributes = props;
		}
	}
})(window.wp);
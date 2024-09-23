(function(wp) {
	'use strict';

	/**
	 * Creates a control element according to the provided field data.
	 * 
	 * @param   object  field  The field structure.
	 * @param   object  props  The block properties object.
	 * 
	 * @return  mixed   A react control.
	 */
	window.vboCreateControlElement = (field, props) => {
		let fieldControl;

		switch (field.layout) {
			case 'html.form.fields.list':
				fieldControl = field.class === 'btn-group btn-group-yesno'
					? new VBOWidgetBlockFieldCheckbox(field)
					: new VBOWidgetBlockFieldList(field);
				break;

			case 'html.form.fields.groupedlist':
				fieldControl = new VBOWidgetBlockFieldGroupedList(field);
				break;

			case 'html.form.fields.textarea':
				fieldControl = new VBOWidgetBlockFieldTextarea(field);
				break;

			case 'html.form.fields.media':
				fieldControl = new VBOWidgetBlockFieldMedia(field);
				break;

			case 'html.form.fields.number':
				// Do not use NUMBER field yet because this control is experimental
				// and might be subject to drastic and breaking changes
				// fieldControl = new VBOWidgetBlockFieldNumber(field);
				break;
		}

		if (!fieldControl) {
			switch (field.type) {
				case 'color':
					fieldControl = new VBOWidgetBlockFieldColor(field);
					break;

				case 'editor':
					fieldControl = new VBOWidgetBlockFieldTextarea(field);
					break;

				case 'html':
					fieldControl = new VBOWidgetBlockFieldHtml(field);
					break;

				default:
					// type not supported, use a text field
					fieldControl = new VBOWidgetBlockFieldText(field);
			}
		}

		return fieldControl.createElement(props);
	}

	/**
	 * Abstract field control.
	 */
	const VBOWidgetBlockField = class VBOWidgetBlockField {
		constructor(data) {
			for (let key in data) {
				if (data.hasOwnProperty(key)) {
					this[key] = data[key];
				}
			}
		}

		shouldDisplay(props) {
			if (!this.showon) {
				return true;
			}

			// detect the conditions glue
			let glue = this.showon.match(/\[OR\]/i) ? 'or' : 'and';

			// extract all the conditions
			let conditions = this.showon.split(/\[AND\]|\[OR\]/i);

			let verified = false;

			for (let condition of conditions) {
				// extract the attribute [1], the comparator [2] and the comparison value [3]
				let chunks = condition.match(/^([a-z0-9_\[\]]+)([!:]+)(.*$)$/i);

				if (!chunks) {
					// invalid condition
					continue;
				}

				let fieldName  = chunks[1];
				let comparator = chunks[2];
				let match      = (chunks[3] === null || chunks[3] === undefined ? '' : chunks[3]).split(/\s*,\s*/);

				if (!props.attributes.hasOwnProperty(fieldName)) {
					// field not found
					continue;
				}

				// validate condition
				switch (comparator) {
					case ':':
						verified = match.indexOf(props.attributes[fieldName].toString()) !== -1;
						break;

					case '!:':
						verified = match.indexOf(props.attributes[fieldName].toString()) === -1;
						break;
				}

				if (!verified && glue === 'and') {
					// all the conditions must be verified
					return false;
				}

				if (verified && glue === 'or') {
					// at least a condition can be verified
					return true;
				}
			}

			return verified;
		}

		createElement(props) {
			if (this.shouldDisplay(props)) {
				return this.buildElement(props);
			}

			return null;
		}

		buildElement(props) {
			return null;
		}
	}

	/**
	 * Text control field renderer.
	 * 
	 * @see  wp.components.TextControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/text-control/
	 */
	const VBOWidgetBlockFieldText = class VBOWidgetBlockFieldText extends VBOWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.TextControl,
				{
					label: this.label,
					help: this.description,
					value: props.attributes[this.name],
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * Textarea control field renderer.
	 * 
	 * @see  wp.components.TextareaControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/text-control/
	 */
	const VBOWidgetBlockFieldTextarea = class VBOWidgetBlockFieldTextarea extends VBOWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.TextareaControl,
				{
					label: this.label,
					help: this.description,
					value: props.attributes[this.name],
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * Number control field renderer.
	 * 
	 * @see  wp.components.NumberControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/number-control/
	 */
	const VBOWidgetBlockFieldNumber = class VBOWidgetBlockFieldNumber extends VBOWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.__experimentalNumberControl,
				{
					label: this.label,
					help: this.description,
					value: props.attributes[this.name],
					min: this.min !== 'undefined' ? this.min : Number.NEGATIVE_INFINITY,
					max: this.max !== 'undefined' ? this.max : Number.POSITIVE_INFINITY,
					step: this.step !== 'undefined' ? this.step : 'any',
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * Color control field renderer.
	 * 
	 * @see  wp.components.ColorPicker
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/color-picker/
	 */
	const VBOWidgetBlockFieldColor = class VBOWidgetBlockFieldColor extends VBOWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.BaseControl,
				{
					label: this.label,
					help: this.description,
					children: wp.element.createElement(
						wp.components.ColorPicker,
						{
							color: props.attributes[this.name],
							enableAlpha: true,
							copyFormat: 'hex',
							onChange: (value) => {
								let attrs = {};
								attrs[this.name] = value;

								props.setAttributes(attrs);
							}
						}
					),
				}
			);
		}
	}

	/**
	 * Checkbox control field renderer.
	 * 
	 * @see  wp.components.ToggleControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/toggle-control/
	 */
	const VBOWidgetBlockFieldCheckbox = class VBOWidgetBlockFieldCheckbox extends VBOWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.ToggleControl,
				{
					label: this.label,
					help: this.description,
					checked: parseInt(props.attributes[this.name]) ? true : false,
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value ? 1 : 0;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * List control field renderer.
	 * 
	 * @see  wp.components.SelectControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/select-control/
	 */
	const VBOWidgetBlockFieldList = class VBOWidgetBlockFieldList extends VBOWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.SelectControl,
				{
					label: this.label,
					help: this.description,
					value: props.attributes[this.name],
					options: this.options,
					multiple: this.multiple ? true : false,
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * Grouped list control field renderer.
	 * 
	 * @see  wp.components.SelectControl
	 * @link https://developer.wordpress.org/block-editor/reference-guides/components/select-control/
	 */
	const VBOWidgetBlockFieldGroupedList = class VBOWidgetBlockFieldGroupedList extends VBOWidgetBlockField {
		constructor(data) {
			super(data);

			this.children = [];

			for (let groupLabel in this.groups) {
				if (!this.groups.hasOwnProperty(groupLabel)) {
					continue;
				}

				let options = [];

				this.groups[groupLabel].forEach((option) => {
					options.push(
						wp.element.createElement(
							'option',
							{
								value: option.value,
							},
							option.text
						)
					);
				});

				this.children.push(
					wp.element.createElement(
						'optgroup',
						{
							label: groupLabel,
						},
						options
					)
				);
			}
		}

		buildElement(props) {
			return wp.element.createElement(
				wp.components.SelectControl,
				{
					label: this.label,
					help: this.description,
					value: props.attributes[this.name],
					children: this.children,
					multiple: this.multiple ? true : false,
					onChange: (value) => {
						let attrs = {};
						attrs[this.name] = value;

						props.setAttributes(attrs);
					}
				}
			);
		}
	}

	/**
	 * Media manager control field renderer.
	 * 
	 * @see  wp.editor.MediaPlaceholder
	 * @link https://github.com/WordPress/gutenberg/blob/HEAD/packages/block-editor/src/components/media-placeholder/README.md
	 */
	const VBOWidgetBlockFieldMedia = class VBOWidgetBlockFieldMedia extends VBOWidgetBlockField {
		buildElement(props) {
			let mediaPreview = null;

			if (props.attributes[this.name]) {
				let children = [];

				// display image only in case the preview is not disabled
				if (this.preview === undefined || this.preview) {
					children.push(
						wp.element.createElement(
							'img',
							{
								src: props.attributes[this.name],
								style: {
									marginBottom: '10px',
								},
							}
						)
					);
				}

				// add button to clear the selected image
				children.push(
					wp.element.createElement(
						wp.components.Button,
						{
							text: wp.i18n.__('Clear'),
							isDestructive: true,
							isSecondary: true,
							style: {
								marginBottom: '10px',
							},
							onClick: () => {
								let attrs = {};
								attrs[this.name] = '';

								props.setAttributes(attrs);
							},
						}
					)
				);

				mediaPreview = wp.element.createElement(
					'div',
					{},
					children
				);
			}

			return wp.element.createElement(
				wp.components.BaseControl,
				{
					label: this.label,
					help: this.description,
					children: wp.element.createElement(
						wp.blockEditor.MediaPlaceholder,
						{
							labels: { title: '' },
							multiple: this.multiple ? true : false,
							// value: props.attributes[this.name],
							allowedTypes: ['image'],
							mediaPreview: mediaPreview,
							onSelect: (value) => {
								let attrs = {};
								attrs[this.name] = value.url;

								props.setAttributes(attrs);
							}
						}
					),
				}
			);
		}
	}

	/**
	 * Generic HTML control field renderer.
	 */
	const VBOWidgetBlockFieldHtml = class VBOWidgetBlockFieldHtml extends VBOWidgetBlockField {
		buildElement(props) {
			return wp.element.createElement(
				wp.components.BaseControl,
				{
					label: this.label,
					help: this.description,
					children: this.createTree(this.layout),
				}
			);
		}

		createTree(nodes) {
			let children = [];

			nodes.forEach((node) => {
				let content = [];

				if (node.content) {
					content.push(node.content);
				}

				if (node.children) {
					content = content.concat(this.createTree(node.children));
				}

				if (node.attributes && node.attributes.class) {
					// remove any class that might render an element as a notice
					node.attributes.class = node.attributes.class.replace(/\bnotice(-[a-z]+)?\b/g, '').trim();
					// convert button classes
					node.attributes.class = node.attributes.class.replace(/\bbtn\b/g, 'button');
				}

				children.push(
					wp.element.createElement(
						node.tag,
						node.attributes || {},
						content
					)
				);
			});

			return children;
		}
	}
})(window.wp);
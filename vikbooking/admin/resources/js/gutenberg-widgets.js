(function(wp) {
	'use strict';

	/**
	 * Helper function used to convert a VikBooking widget into a block.
	 * 
	 * @param   object  manifest
	 * 
	 * @return  void
	 */
	window.vboRegisterBlockEditor = (manifest) => {
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
		 * Every block starts by registering a new block type definition.
		 * @link https://wordpress.org/gutenberg/handbook/block-api/
		 */
		registerBlockType(manifest.id, {
			/**
			 * This is the block display title, which can be translated with `i18n` functions.
			 * The block inserter will show this name.
			 */
			title: manifest.title,

			/**
			 * This is the block description, which is displayed within the right sidebar.
			 */
			description: manifest.description,

			/**
			 * The icon can be a DASHICON or a SVG entity.
			 * NOTE: we need to use a different icon because Gutenberg seems
			 * to have problems in displaying the coffee icon.
			 */
			icon: manifest.icon,

			/**
			 * Blocks are grouped into categories to help users browse and discover them.
			 * The categories provided by core are `common`, `embed`, `formatting`, `layout` and `widgets`.
			 */
			category: manifest.category,

			/**
			 * Sometimes a block could have aliases that help users discover it while searching.
			 * You can do so by providing an array of terms (which can be translated).
			 * It is only allowed to add as much as three terms per block.
			 */
			keywords: manifest.keywords,

			/**
			 * Optional block extended support features.
			 */
			supports: manifest.supports,

			/**
			 * Attributes provide the structured data needs of a block.
			 * They can exist in different forms when they are serialized, 
			 * but they are declared together under a common interface.
			 */
			attributes: manifest.attributes,

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
				// use the new version of InspectorControls
				// provided by WordPress 5.4, if supported
				let InspectorControls, InspectorAdvancedControls;

				if (wp.blockEditor && wp.blockEditor.InspectorControls) {
					// use new version
					InspectorControls = wp.blockEditor.InspectorControls;
					InspectorAdvancedControls = wp.blockEditor.InspectorAdvancedControls;
				} else {
					// fallback to the older one
					InspectorControls = wp.editor.InspectorControls;
					InspectorAdvancedControls = wp.editor.InspectorAdvancedControls;
				}

				let navBar = [], advancedFields = [];

				// iterate all the form fieldsets
				manifest.form.forEach((fieldset, index) => {
					let fields = [];

					// iterate all the fields under this tab
					fieldset.fields.forEach((field) => {
						// create element according to the provided fields
						let fieldControl = window.vboCreateControlElement(field, props);

						// register the element within the list of fields
						fields.push(fieldControl);
					});

					if (fieldset.name !== 'advanced') {
						// add accordion
						navBar.push(
							el(
								wp.components.PanelBody,
								{
									title: fieldset.title,
									initialOpen: index === 0,
									key: fieldset.name,
								},
								fields
							)
						);
					} else {
						advancedFields.push(fields);
					}

				});

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

				// setup advanced tab
				let advancedControls = el(
					// create InspectorAdvancedControls element
					InspectorAdvancedControls,
					// define inspector properties
					{
						key: 'controls',
					},
					advancedFields
				);

				return [
					controls,
					advancedControls,
					el(
						wp.serverSideRender,
						{
							block: manifest.id,
							attributes: props.attributes,
						}
					)
				];
			}
		});
	}
})(window.wp);
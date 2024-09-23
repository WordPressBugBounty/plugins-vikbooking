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

$vbo_tn = $this->vbo_tn;

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadVisualEditorAssets();

$editor = JEditor::getInstance(JFactory::getApplication()->get('editor'));

$langs = $vbo_tn->getLanguagesList();
$xml_tables = $vbo_tn->getTranslationTables();
$active_table = '';
$active_table_key = '';

// JS lang vars
JText::script('VBO_COPY_ORIGINAL_TN');

if (!(count($langs) > 1)) {
	//Error: only one language is published. Translations are useless
	?>
	<p class="err"><?php echo JText::translate('VBTRANSLATIONERRONELANG'); ?></p>
	<form name="adminForm" id="adminForm" action="index.php" method="post">
		<input type="hidden" name="task" value="">
		<input type="hidden" name="option" value="com_vikbooking">
	</form>
	<?php
} elseif (!$xml_tables || strlen($vbo_tn->getError())) {
	//Error: XML file not readable or errors occurred
	?>
	<p class="err"><?php echo $vbo_tn->getError(); ?></p>
	<form name="adminForm" id="adminForm" action="index.php" method="post">
		<input type="hidden" name="task" value="">
		<input type="hidden" name="option" value="com_vikbooking">
	</form>
	<?php
} else {
	$cur_langtab = VikRequest::getString('vbo_lang', '', 'request');
	$table = VikRequest::getString('vbo_table', '', 'request');
	if (!empty($table)) {
		$table = $vbo_tn->replacePrefix($table);
	}

	/**
	 * Allows to filter/search translations by a key-search value.
	 * 
	 * @since 	1.16.6 (J) - 1.6.6 (WP)
	 */
	$keysearch = VikRequest::getString('keysearch', '', 'request');
	$vbo_tn->setKeySearch($keysearch);
?>

<form action="index.php?option=com_vikbooking&amp;task=translations" method="post" onsubmit="return vboCheckChanges();">
	<div style="width: 100%; display: inline-block;" class="btn-toolbar vbo-btn-toolbar" id="filter-bar">
		<div class="btn-group pull-right">
			<button class="btn" type="submit"><?php echo JText::translate('VBOGETTRANSLATIONS'); ?></button>
		</div>
		<div class="btn-group pull-right">
			<select name="vbo_table">
				<option value="">-----------</option>
			<?php
			foreach ($xml_tables as $key => $value) {
				$active_table = $vbo_tn->replacePrefix($key) == $table ? $value : $active_table;
				$active_table_key = $vbo_tn->replacePrefix($key) == $table ? $key : $active_table_key;
				?>
				<option value="<?php echo $key; ?>"<?php echo $vbo_tn->replacePrefix($key) == $table ? ' selected="selected"' : ''; ?>><?php echo $value; ?></option>
				<?php
			}
			?>
			</select>
		</div>
	<?php
	if (!empty($active_table_key)) {
		?>
		<div class="btn-group pull-left">
			<input type="text" name="keysearch" value="<?php echo JHtml::fetch('esc_attr', $keysearch); ?>" placeholder="<?php echo JHtml::fetch('esc_attr', JText::translate('VBODASHSEARCHKEYS')); ?>..." />
		</div>
		<?php
	}
	?>
	</div>
	<input type="hidden" name="vbo_lang" class="vbo_lang" value="<?php echo $vbo_tn->default_lang; ?>">
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="translations" />
</form>
<form name="adminForm" id="adminForm" action="index.php" method="post">
	<div class="vbo-translation-langtabs">
<?php
foreach ($langs as $ltag => $lang) {
	$is_def = ($ltag == $vbo_tn->default_lang);
	$lcountry = substr($ltag, 0, 2);
	$flag = '';
	if (VBOPlatformDetection::isJoomla() && is_file(JPATH_SITE . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'mod_languages' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $lcountry . '.gif')) {
		$flag = '<img src="' . JUri::root() . 'media/mod_languages/images/' . $lcountry . '.gif"/>';
	}
		?><div class="vbo-translation-tab<?php echo $is_def ? ' vbo-translation-tab-default' : ''; ?>" data-vbolang="<?php echo $ltag; ?>">
		<?php
		if (!empty($flag)) {
			?>
			<span class="vbo-translation-flag"><?php echo $flag; ?></span>
			<?php
		}
		?>
			<span class="vbo-translation-langname"><?php echo $lang['name']; ?></span>
		</div><?php
}

if (VBOPlatformDetection::isJoomla()) {
	?>
		<div class="vbo-translation-tab vbo-translation-tab-ini" data-vbolang="">
			<span class="vbo-translation-iniflag">.INI</span>
			<span class="vbo-translation-langname"><?php echo JText::translate('VBTRANSLATIONINISTATUS'); ?></span>
		</div>
	<?php
}
?>
	</div>
	<div class="vbo-translation-tabscontents">
<?php
$table_cols = !empty($active_table_key) ? $vbo_tn->getTableColumns($active_table_key) : [];
$table_def_dbvals = !empty($active_table_key) ? $vbo_tn->getTableDefaultDbValues($active_table_key, array_keys($table_cols)) : [];
if (!empty($active_table_key)) {
	echo '<input type="hidden" name="vbo_table" value="'.$active_table_key.'"/>'."\n";
	if (!empty($keysearch)) {
		echo '<input type="hidden" name="keysearch" value="' . JHtml::fetch('esc_attr', $keysearch) . '"/>'."\n";
	}
}
foreach ($langs as $ltag => $lang) {
	$is_def = ($ltag == $vbo_tn->default_lang);
	?>
		<div class="vbo-translation-langcontent" style="display: <?php echo $is_def ? 'block' : 'none'; ?>;" id="vbo_langcontent_<?php echo $ltag; ?>">
	<?php
	if (empty($active_table_key)) {
		?>
			<p class="warn"><?php echo JText::translate('VBTRANSLATIONSELTABLEMESS'); ?></p>
		<?php
	} elseif (strlen($vbo_tn->getError()) > 0) {
		?>
			<p class="err"><?php echo $vbo_tn->getError(); ?></p>
		<?php
	} else {
		?>
			<fieldset class="adminform">
				<legend class="adminlegend"><?php echo $active_table; ?> - <?php echo $lang['name'].($is_def ? ' - '.JText::translate('VBTRANSLATIONDEFLANG') : ''); ?></legend>
				<div class="vbo-translations-tab-container">
	<?php
	if ($is_def) {
		// values of Default Language to be translated
		foreach ($table_def_dbvals as $reference_id => $values) {
			?>
					<div class="vbo-translations-default-element">
						<div class="vbo-translations-element-title" data-reference="<?php echo $ltag.'-'.$reference_id; ?>">
							<div class="vbo-translate-element-cell"><?php echo $vbo_tn->getRecordReferenceName($table_cols, $values); ?></div>
						</div>
						<div class="vbo-translations-element-contents">
			<?php
			foreach ($values as $field => $def_value) {
				$title = $table_cols[$field]['jlang'];
				$type = $table_cols[$field]['type'];
				if ($type == 'html') {
					$def_value = VBOPlatformDetection::isWordPress() ? wpautop($def_value) : $def_value;
				}
				?>
							<div class="vbo-translations-element-row" data-reference="<?php echo $ltag.'-'.$reference_id; ?>">
								<div class="vbo-translations-element-lbl"><?php echo $title; ?></div>
								<div class="vbo-translations-element-val" data-origvalue="<?php echo $reference_id . '-' . $field; ?>"><?php echo $type != 'json' ? $def_value : ''; ?></div>
							</div>
				<?php
				if ($type == 'json') {
					$tn_keys = $table_cols[$field]['keys'];
					$keys = !empty($tn_keys) ? explode(',', $tn_keys) : [];
					$json_def_values = json_decode($def_value, true);
					if ($json_def_values) {
						foreach ($json_def_values as $jkey => $jval) {
							if ((!in_array($jkey, $keys) && count($keys) > 0) || empty($jval)) {
								continue;
							}
							$json_lbl = '&nbsp;';
							if (!is_numeric($jkey)) {
								$guess_lbl = JText::translate('VBO_' . strtoupper($jkey));
								$json_lbl = $guess_lbl != 'VBO_' . strtoupper($jkey) ? $guess_lbl : ucwords($jkey);
							}
							?>
							<div class="vbo-translations-element-row vbo-translations-element-row-nested" data-reference="<?php echo $ltag.'-'.$reference_id; ?>">
								<div class="vbo-translations-element-lbl"><?php echo $json_lbl; ?></div>
								<div class="vbo-translations-element-val" data-origvalue="<?php echo $reference_id . '-' . $field . '-' . $jkey; ?>"><?php echo $jval; ?></div>
							</div>
							<?php
						}
					}
				}
				?>
				<?php
			}
			?>
						</div>
					</div>
			<?php
		}
	} else {
		// translation fields for this language
		$lang_record_tn = $vbo_tn->getTranslatedTable($active_table_key, $ltag);
		foreach ($table_def_dbvals as $reference_id => $values) {
			?>
					<div class="vbo-translations-language-element">
						<div class="vbo-translations-element-title" data-reference="<?php echo $ltag.'-'.$reference_id; ?>">
							<div class="vbo-translate-element-cell"><?php echo $vbo_tn->getRecordReferenceName($table_cols, $values); ?></div>
						</div>
						<div class="vbo-translations-element-contents">
			<?php
			foreach ($values as $field => $def_value) {
				$title = $table_cols[$field]['jlang'];
				$type = $table_cols[$field]['type'];
				if ($type == 'skip') {
					continue;
				}
				$tn_value = '';
				$tn_class = ' vbo-missing-translation';
				if (array_key_exists($reference_id, $lang_record_tn) && array_key_exists($field, $lang_record_tn[$reference_id]['content']) && strlen($lang_record_tn[$reference_id]['content'][$field])) {
					if (in_array($type, array('text', 'textarea', 'html'))) {
						$tn_class = ' vbo-field-translated';
					} else {
						$tn_class = '';
					}
				}
				?>
							<div class="vbo-translations-element-row<?php echo $tn_class; ?>" data-reference="<?php echo $ltag.'-'.$reference_id; ?>" data-copyoriginal="<?php echo $reference_id . '-' . $field; ?>">
								<div class="vbo-translations-element-lbl"><?php echo $title; ?></div>
								<div class="vbo-translations-element-val">
						<?php
						if ($type == 'text') {
							if (array_key_exists($reference_id, $lang_record_tn) && array_key_exists($field, $lang_record_tn[$reference_id]['content'])) {
								$tn_value = $lang_record_tn[$reference_id]['content'][$field];
							}
							?>
									<input type="text" name="tn[<?php echo $ltag; ?>][<?php echo $reference_id; ?>][<?php echo $field; ?>]" value="<?php echo htmlspecialchars($tn_value); ?>" size="40" placeholder="<?php echo htmlspecialchars($def_value); ?>"/>
							<?php
						} elseif ($type == 'textarea') {
							if (array_key_exists($reference_id, $lang_record_tn) && array_key_exists($field, $lang_record_tn[$reference_id]['content'])) {
								$tn_value = $lang_record_tn[$reference_id]['content'][$field];
							}
							?>
									<textarea name="tn[<?php echo $ltag; ?>][<?php echo $reference_id; ?>][<?php echo $field; ?>]" rows="7" cols="170"><?php echo $tn_value; ?></textarea>
							<?php
						} elseif ($type == 'html') {
							if (array_key_exists($reference_id, $lang_record_tn) && array_key_exists($field, $lang_record_tn[$reference_id]['content'])) {
								$tn_value = $lang_record_tn[$reference_id]['content'][$field];
							}
							if (VBOPlatformDetection::isWordPress() && interface_exists('Throwable')) {
								/**
								 * With PHP >= 7 supporting throwable exceptions for Fatal Errors
								 * we try to avoid issues with third party plugins that make use
								 * of the WP native function get_current_screen().
								 * 
								 * @wponly
								 */
								try {
									echo $editor->display( "tn[".$ltag."][".$reference_id."][".$field."]", $tn_value, '100%', 350, 70, 20, true, "tn_".$ltag."_".$reference_id."_".$field );
								} catch (Throwable $t) {
									echo $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine() . '<br/>';
								}
							} else {
								// we cannot catch Fatal Errors in PHP 5.x
								echo $editor->display( "tn[".$ltag."][".$reference_id."][".$field."]", $tn_value, '100%', 350, 70, 20, true, "tn_".$ltag."_".$reference_id."_".$field );
							}
						}
						?>
								</div>
							</div>
				<?php
				if ($type == 'json') {
					$tn_keys = $table_cols[$field]['keys'];
					$keys = !empty($tn_keys) ? explode(',', $tn_keys) : [];
					$json_def_values = json_decode($def_value, true);
					if ($json_def_values) {
						$tn_json_value = [];
						if (array_key_exists($reference_id, $lang_record_tn) && array_key_exists($field, $lang_record_tn[$reference_id]['content'])) {
							$tn_json_value = json_decode($lang_record_tn[$reference_id]['content'][$field], true);
						}
						foreach ($json_def_values as $jkey => $jval) {
							if ((!in_array($jkey, $keys) && count($keys) > 0) || empty($jval)) {
								continue;
							}
							$json_lbl = '&nbsp;';
							if (!is_numeric($jkey)) {
								$guess_lbl = JText::translate('VBO_' . strtoupper($jkey));
								$json_lbl = $guess_lbl != 'VBO_' . strtoupper($jkey) ? $guess_lbl : ucwords($jkey);
							}
							?>
							<div class="vbo-translations-element-row vbo-translations-element-row-nested" data-reference="<?php echo $ltag.'-'.$reference_id; ?>" data-copyoriginal="<?php echo $reference_id . '-' . $field . '-' . $jkey; ?>">
								<div class="vbo-translations-element-lbl"><?php echo $json_lbl; ?></div>
								<div class="vbo-translations-element-val">
								<?php
								if (strlen($jval) > 40) {
									// check if this translation field requires the rich text visual editor
									if ($jkey == 'tpl_text' && strpos($jval, '<') !== false) {
										// the original field contains HTML code built through the visual editor
										$veditor_name = 'tn[' . $ltag . '][' . $reference_id . '][' . $field . '][' . $jkey . ']';
										$veditor_id   = 'tn_' . $ltag . '_' . $reference_id . '_' . $field . '_' . $jkey;
										$tarea_attr = array(
											'id' => $veditor_id,
											'rows' => '7',
											'cols' => '170',
											'style' => 'min-width: 60%;',
										);
										$editor_opts = array(
											'modes' => array(
												'text',
												'visual',
											),
										);
										echo $vbo_app->renderVisualEditor($veditor_name, (isset($tn_json_value[$jkey]) ? $tn_json_value[$jkey] : ''), $tarea_attr, $editor_opts);
									} else {
										?>
									<textarea rows="7" cols="170" style="min-width: 60%;" name="tn[<?php echo $ltag; ?>][<?php echo $reference_id; ?>][<?php echo $field; ?>][<?php echo $jkey; ?>]"><?php echo isset($tn_json_value[$jkey]) ? $tn_json_value[$jkey] : ''; ?></textarea>
										<?php
									}
								} else {
									?>
									<input type="text" name="tn[<?php echo $ltag; ?>][<?php echo $reference_id; ?>][<?php echo $field; ?>][<?php echo $jkey; ?>]" value="<?php echo isset($tn_json_value[$jkey]) ? $tn_json_value[$jkey] : ''; ?>" size="40" placeholder="<?php echo htmlspecialchars($jval); ?>"/>
									<?php
								}
								?>
								</div>
							</div>
							<?php
						}
					}
				}
			}
			?>
						</div>
					</div>
			<?php
		}
	}
	?>
				</div>
			</fieldset>
		<?php
	}
	?>
		</div>
	<?php
}

// ini files status
if (VBOPlatformDetection::isJoomla()) {
	$all_inis = $vbo_tn->getIniFiles();
	?>
		<div class="vbo-translation-langcontent" style="display: none;" id="vbo_langcontent_ini">
			<fieldset class="adminform">
				<legend class="adminlegend">.INI <?php echo JText::translate('VBTRANSLATIONINISTATUS'); ?></legend>
				<div class="vbo-translations-tab-container">
				<?php
				foreach ($all_inis as $initype => $inidet) {
					$inipath = $inidet['path'];
					?>
					<div class="vbo-translations-ini-element">
						<div class="vbo-translations-element-title">
							<div class="vbo-translate-element-cell"><?php echo JText::translate('VBINIEXPL'.strtoupper($initype)); ?></div>
						</div>
						<div class="vbo-translations-element-contents">
					<?php
					foreach ($langs as $ltag => $lang) {
						$t_file_exists = file_exists(str_replace('en-GB', $ltag, $inipath));
						$t_parsed_ini = $t_file_exists ? parse_ini_file(str_replace('en-GB', $ltag, $inipath)) : false;
						?>
							<div class="vbo-translations-element-row <?php echo $t_file_exists ? 'vbo-field-translated' : 'vbo-missing-translation'; ?>">
								<div class="vbo-translations-element-lbl"><?php echo ($ltag == 'en-GB' ? 'Native ' : '').$lang['name']; ?></div>
								<div class="vbo-translations-element-val">
									<span class="vbo-inifile-totrows <?php echo $t_file_exists ? 'vbo-inifile-exists' : 'vbo-inifile-notfound'; ?>"><?php echo $t_file_exists && $t_parsed_ini !== false ? JText::translate('VBOINIDEFINITIONS').': '.count($t_parsed_ini) : JText::translate('VBOINIMISSINGFILE'); ?></span>
									<span class="vbo-inifile-path <?php echo $t_file_exists ? 'vbo-inifile-exists' : 'vbo-inifile-notfound'; ?>"><?php echo JText::translate('VBOINIPATH').': '.str_replace('en-GB', $ltag, $inipath); ?></span>
								</div>
							</div>
						<?php
					}
					?>
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</fieldset>
		</div>
	<?php
	// end ini files status
}
?>
	</div>
	<input type="hidden" name="vbo_lang" class="vbo_lang" value="<?php echo $vbo_tn->default_lang; ?>">
	<input type="hidden" name="task" value="translations">
	<input type="hidden" name="option" value="com_vikbooking">
	<?php echo JHtml::fetch('form.token'); ?>

	<div class="vbo-translations-lim-wrap">
		<table align="center">
			<tr>
				<td align="center"><?php echo $vbo_tn->getPagination(); ?></td>
			</tr>
			<tr>
				<td align="center">
					<select name="limit" onchange="vboHandleCustomLimit(this.value);">
						<option value="2"<?php echo $vbo_tn->lim == 2 ? ' selected="selected"' : ''; ?>>2</option>
						<option value="5"<?php echo $vbo_tn->lim == 5 ? ' selected="selected"' : ''; ?>>5</option>
						<option value="10"<?php echo $vbo_tn->lim == 10 ? ' selected="selected"' : ''; ?>>10</option>
						<option value="20"<?php echo $vbo_tn->lim == 20 ? ' selected="selected"' : ''; ?>>20</option>
					</select>
				</td>
			</tr>
		</table>
	</div>
</form>

<script type="text/Javascript">
var vbo_tn_changes = false;
var vbo_copy_delay = 500;
var vbo_copy_timeout = null;

function vboHandleCustomLimit(lim) {
	var cur_limstart = document.adminForm.limitstart;
	if (typeof cur_limstart === 'undefined') {
		// append hidden input field to form
		var limstart_node = document.createElement('INPUT');
		limstart_node.setAttribute('type', 'hidden');
		limstart_node.setAttribute('name', 'limitstart');
		limstart_node.setAttribute('value', '0');
		document.adminForm.appendChild(limstart_node);
	} else {
		// update existing value
		document.adminForm.limitstart.value = '0';
	}
	// submit form
	document.adminForm.submit();
}

function vboCheckChanges() {
	if (!vbo_tn_changes) {
		return true;
	}
	return confirm("<?php echo addslashes(JText::translate('VBTANSLATIONSCHANGESCONF')); ?>");
}

function vboHoverCopyTranslation(elem) {
	if (!elem) {
		return false;
	}
	var copy_reference = elem.attr('data-copyoriginal');
	if (!copy_reference) {
		return false;
	}
	var orig_elem = jQuery('.vbo-translations-element-val[data-origvalue="' + copy_reference + '"]');
	if (!orig_elem || !orig_elem.length || !orig_elem.html().length) {
		return false;
	}
	// check if translation field has got a value
	var tn_field_val = elem.find('.vbo-translations-element-val').find('input[type="text"],textarea').val();
	if (!(tn_field_val || '').trim()) {
		// append button to copy the original content
		elem.
			find('.vbo-translations-element-lbl').
			append('<div class="vbo-tn-copyoriginal"><span title="' + Joomla.JText._('VBO_COPY_ORIGINAL_TN') + '" onclick="vboApplyCopyTranslation(this);"><?php VikBookingIcons::e('copy'); ?></span></div>');
	}
}

function vboApplyCopyTranslation(elem) {
	elem = jQuery(elem).closest('.vbo-translations-element-row[data-copyoriginal]');
	if (!elem || !elem.length) {
		return false;
	}
	var copy_reference = elem.attr('data-copyoriginal');
	if (!copy_reference) {
		return false;
	}
	var orig_elem = jQuery('.vbo-translations-element-val[data-origvalue="' + copy_reference + '"]');
	if (!orig_elem || !orig_elem.length) {
		return false;
	}

	// make sure to remove any copy-from-original button
	jQuery('.vbo-tn-copyoriginal').remove();

	// set original content to translation field
	var input = elem.find('.vbo-translations-element-val').find('input[type="text"]');
	if (input && input.length) {
		input.val(orig_elem.html()).trigger('change');
	}
	var tarea = elem.find('.vbo-translations-element-val').find('textarea');
	if (tarea && tarea.length) {
		tarea.val(orig_elem.html()).trigger('change');
	}

	// grab lang tag, which could be part of the ID of a WYSIWYG editor
	var lang_reference_arr = elem.attr('data-reference').split('-');
	lang_reference_arr.splice((lang_reference_arr.length - 1), 1);
	var current_lang = lang_reference_arr.join('-');
	var wysiwyg_hipo_id = current_lang + '-' + copy_reference;
	wysiwyg_hipo_id = 'tn_' + wysiwyg_hipo_id.replaceAll('-', '_');
	
	// attempt to inject value inside wysiwyg editor
	try {
		// native wysiwyg editor
		if (typeof Joomla !== undefined && Joomla.editors && Joomla.editors.instances) {
			if (Joomla.editors.instances.hasOwnProperty(wysiwyg_hipo_id)) {
				Joomla.editors.instances[wysiwyg_hipo_id].setValue(orig_elem.html());
			}
		}
	} catch(e) {
		// do nothing
	}
}

jQuery(function() {
	
	jQuery('.vbo-translation-tab').click(function() {
		var langtag = jQuery(this).attr('data-vbolang');
		if (jQuery('#vbo_langcontent_'+langtag).length) {
			jQuery('.vbo_lang').val(langtag);
			jQuery('.vbo-translation-tab').removeClass('vbo-translation-tab-default');
			jQuery(this).addClass('vbo-translation-tab-default');
			jQuery('.vbo-translation-langcontent').hide();
			jQuery('#vbo_langcontent_'+langtag).fadeIn();
		} else {
			jQuery('.vbo-translation-tab').removeClass('vbo-translation-tab-default');
			jQuery(this).addClass('vbo-translation-tab-default');
			jQuery('.vbo-translation-langcontent').hide();
			jQuery('#vbo_langcontent_ini').fadeIn();
		}
	});

	jQuery('#adminForm input[type=text], #adminForm textarea').change(function() {
		vbo_tn_changes = true;
	});

	jQuery('.vbo-translations-element-row[data-copyoriginal]').hover(function() {
		var elem = jQuery(this);
		vbo_copy_timeout = setTimeout(function() {
			vboHoverCopyTranslation(elem);
		}, vbo_copy_delay);
	}, function() {
		// cancel scheduled hovering function
		clearTimeout(vbo_copy_timeout);
		// make sure to remove any copy-from-original button
		jQuery('.vbo-tn-copyoriginal').remove();
	});
<?php
if (!empty($cur_langtab)) {
	?>
	jQuery('.vbo-translation-tab').each(function() {
		var langtag = jQuery(this).attr('data-vbolang');
		if (langtag != '<?php echo $cur_langtab; ?>') {
			return true;
		}
		if (jQuery('#vbo_langcontent_'+langtag).length) {
			jQuery('.vbo_lang').val(langtag);
			jQuery('.vbo-translation-tab').removeClass('vbo-translation-tab-default');
			jQuery(this).addClass('vbo-translation-tab-default');
			jQuery('.vbo-translation-langcontent').hide();
			jQuery('#vbo_langcontent_'+langtag).fadeIn();
		}
	});
	<?php
}
?>
});
</script>
<?php
}

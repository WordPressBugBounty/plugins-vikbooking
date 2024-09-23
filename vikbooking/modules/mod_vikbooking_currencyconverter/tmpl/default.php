<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_currencyconverter
 * @author      Alessio Gaggii - E4J s.r.l
 * @copyright   Copyright (C) 2018 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$session = JFactory::getSession();
$last_lang = $session->get('vboLastCurrency', '');

/**
 * The currency to convert to, can also be injected via query string.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
$req_currency = JFactory::getApplication()->input->getString('user_currency', '');
if (!empty($req_currency) && empty($last_lang)) {
	foreach ($currencies as $cur) {
		$three_code = substr($cur, 0, 3);
		if ($req_currency == $three_code) {
			// user currency is supported, force it to be used for conversion
			$last_lang = $req_currency;
			break;
		}
	}
}

$document = JFactory::getDocument();
$document->addStyleSheet($baseurl.'modules/mod_vikbooking_currencyconverter/mod_vikbooking_currencyconverter.css');

$active_suff = empty($last_lang) ? $def_currency : $last_lang;

?>
<script type="text/javascript">
var sendprices = new Array();
var vbcurconvbasepath = '<?php echo $baseurl.'modules/mod_vikbooking_currencyconverter/images/flags/'; ?>';
var vbcurconvbaseflag = '<?php echo $baseurl.'modules/mod_vikbooking_currencyconverter/images/flags/'.$active_suff.'.png'; ?>';
var fromCurrency = '<?php echo $def_currency; ?>';
var fromSymbol;
var pricestaken = 0;
jQuery(document).ready(function() {
	if (jQuery(".vbo_price").length > 0) {
		jQuery(".vbo_price").each(function() {
			sendprices.push(jQuery(this).text());
		});
		pricestaken = 1;
	}
	if (jQuery(".vbo_currency").length > 0) {
		fromSymbol = jQuery(".vbo_currency").first().html();
	}
	<?php
	if (!empty($last_lang) && $last_lang != $def_currency) {
		?>
	if (jQuery(".vbo_price").length > 0) {
		vboConvertCurrency('<?php echo $last_lang; ?>');
	}
		<?php
	}
	?>
});
function vboConvertCurrency(toCurrency) {
	if (sendprices.length > 0) {
		jQuery(".vbo_currency").text(toCurrency);
		jQuery(".vbo_price").text("").addClass("vbo_converting");
		var modvbocurconvax = jQuery.ajax({
			type: "POST",
			url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=currencyconverter', false); ?>",
			data: {prices: sendprices, fromsymbol: fromSymbol, fromcurrency: fromCurrency, tocurrency: toCurrency, tmpl: "component"}
		}).done(function(resp) {
			jQuery(".vbo_price").removeClass("vbo_converting");
			var convobj = JSON.parse(resp);
			if (convobj.hasOwnProperty("error")) {
				alert(convobj.error);
				vboUndoConversion();
			} else {
				jQuery(".vbo_currency").html(convobj[0].symbol);
				jQuery(".vbo_price").each(function(i) {
					jQuery(this).text(convobj[i].price);
				});
				if (jQuery('.vbcurconv-flag').length) {
					jQuery(".vbcurconv-flag-img").attr("src", vbcurconvbasepath+toCurrency+".png");
					jQuery(".vbcurconv-flag-img").attr("alt", toCurrency);
					jQuery(".vbcurconv-flag-img").attr("title", toCurrency);
					jQuery(".vbcurconv-flag-symb").html(convobj[0].symbol);
				}
			}
		}).fail(function(){
			jQuery(".vbo_price").removeClass("vbo_converting");
			vboUndoConversion();
		});
	} else {
		jQuery(".modcurconvsel").val("<?php echo $active_suff; ?>");
	}
}
function vboUndoConversion() {
	jQuery(".vbo_currency").text(fromSymbol);
	jQuery(".vbo_price").each(function(i) {
		jQuery(this).text(sendprices[i]);
	});
	if (jQuery('.vbcurconv-flag').length) {
		jQuery(".vbcurconv-flag-symb").text(fromSymbol);
		jQuery(".vbcurconv-flag-img").attr("src", vbcurconvbaseflag);
		jQuery(".vbcurconv-flag-img").attr("alt", fromCurrency);
		jQuery(".vbcurconv-flag-img").attr("title", fromCurrency);
	}
	jQuery(".modcurconvsel").val(fromCurrency);
}
</script>

<div class="vbcurconvcontainer">
<?php
if ((int)$params->get('showflag', '0')) {
?>
	<div class="vbcurconv-flag">
		<?php
		echo '<img class="vbcurconv-flag-img" alt="'.$active_suff.'" title="'.$active_suff.'" src="'.$baseurl.'modules/mod_vikbooking_currencyconverter/images/flags/'.$active_suff.'.png'.'"/>';
		$active_symb = array_key_exists($active_suff, $currencymap) && isset($currencymap[$active_suff]['symbol']) ? '&#'.$currencymap[$active_suff]['symbol'].';' : '';
		?>
		<span class="vbcurconv-flag-symb"><?php echo $active_symb; ?></span>
	</div>
<?php
}
?>
	<div class="vbcurconv-menu">
		<select class="modcurconvsel" name="mod_vikbooking_currencyconverter" onchange="vboConvertCurrency(this.value);">
	<?php
	foreach ($currencies as $cur) {
		$three_code = substr($cur, 0, 3);
		$curparts = explode(':', $cur);
		if ($currencynameformat == 1) {
			$curname = $three_code;
		} elseif ($currencynameformat == 2) {
			$curname = trim($curparts[1]);
		} else {
			$curname = trim($curparts[1]).' ('.$three_code.')';
		}
		?>
		<option value="<?php echo $three_code; ?>"<?php echo ((empty($last_lang) && $three_code == $def_currency) || (!empty($last_lang) && $three_code == $last_lang) ? ' selected="selected"' : ''); ?>><?php echo $curname; ?></option>
		<?php
	}
	?>
		</select>
	</div>
</div>

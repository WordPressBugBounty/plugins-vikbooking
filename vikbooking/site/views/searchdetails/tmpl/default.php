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

//load jQuery lib
$document = JFactory::getDocument();
//JHtml::fetch('jquery.framework', true, true);
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-1.12.4.min.js');
//

$session = JFactory::getSession();
$last_lang = $session->get('vboLastCurrency', '');

$vat_included = VikBooking::ivaInclusa();
$tax_summary = !$vat_included && VikBooking::showTaxOnSummaryOnly() ? true : false;

$room = $this->room;
$tar = $this->tar;
$checkin = $this->checkin;
$checkout = $this->checkout;
$adults = $this->adults;
$children = $this->children;
$daysdiff = $this->daysdiff;
$vbo_tn = $this->vbo_tn;

$currencysymb = VikBooking::getCurrencySymb();
$def_currency = VikBooking::getCurrencyName();

$carats = VikBooking::getRoomCaratOriz($room['idcarat'], $vbo_tn);

if (!empty($room['moreimgs'])) {
	$document->addStyleSheet(VBO_SITE_URI . 'resources/vik-dots-slider.css');
	JHtml::fetch('script', VBO_SITE_URI . 'resources/vik-dots-slider.js');

	$gallery_images = array();
	$gallery_captions = array();
	$moreimages = explode(';;', $room['moreimgs']);
	$imgcaptions = json_decode($room['imgcaptions'], true);
	$usecaptions = empty($imgcaptions) || !is_array($imgcaptions) || !count($imgcaptions) ? false : true;
	foreach ($moreimages as $iind => $mimg) {
		if (!empty($mimg)) {
			array_push($gallery_images, VBO_SITE_URI . 'resources/uploads/big_' . $mimg);
			array_push($gallery_captions, ($usecaptions && isset($imgcaptions[$iind]) ? $imgcaptions[$iind] : ''));
		}
	}
	/**
	 * @wponly 	we render vikDotsSlider with a 1-second delay for the AJAX request to load the modal to complete.
	 */
	$vikdotsslider = '
jQuery(document).ready(function() {
	setTimeout(function() {
		jQuery(".vbo-searchdet-gallery-container").html("").vikDotsSlider({
			images: ' . json_encode($gallery_images) . ',
			captions: ' . json_encode($gallery_captions) . ',
			navButPrevContent: \'<i class="' . VikBookingIcons::i('chevron-left') . '"></i>\',
			navButNextContent: \'<i class="' . VikBookingIcons::i('chevron-right') . '"></i>\',
			containerHeight: "284px"
		});
	}, 1000);
});';
	$document->addScriptDeclaration($vikdotsslider);
}
?>

<div class="vbdetroom">
	<div class="vbroomdetcont">
		<div class="vbroomimgdesc">
			<div class="vbo-searchdet-head">
				<div class="vbo-searchdet-gallery-container">
					<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $room['img']; ?>" class="vblistimg"/>
				</div>
			</div>
			<div class="room_description_box">
				<div class="vblistroomnamediv">
					<h3><span class="vblistroomname"><?php echo $room['name']; ?></span></h3>
				</div>
		<?php
		/**
		 * @wponly 	we try to parse any shortcode inside the HTML description of the room
		 */
		echo do_shortcode(wpautop($room['info']));
		//
		?>
			</div>
		</div>

	<?php 
	if (!empty($carats)) {
	?>
		<div class="room_carats">
			<h4><?php echo JText::translate('VBCHARACTERISTICS'); ?></h4>
			<?php echo $carats; ?>
		</div>
	<?php
	}
	?>
	</div>
	<div class="vb_detcostroom">
		<div id="vbsrchdetpriceopen" class="vb_detpriceroombt"><span><?php echo JText::translate('VBPRICEDETAILS'); ?></span></div>
		<div id="vbsrchdetpricebox" class="vbsrchdetpricebox">
			<div id="vbsrchdetpriceboxinner" class="vbsrchdetpriceboxinner">
				<span class="vbroomnumnightsdet vbo-pref-color-element"><?php echo $daysdiff; ?> <?php echo ($daysdiff > 1 ? JText::translate('VBSEARCHRESNIGHTS') : JText::translate('VBSEARCHRESNIGHT')); ?></span>
				<div class="vbpricedetstable">
					<div class="vbpricedetstrhead">
						<div class="vbpricedetstable-leftcol"><?php echo JText::translate('VBPRICEDETAILSDAY'); ?></div>
						<div class="vbpricedetstable-rightcol"><?php echo JText::translate('VBPRICEDETAILSPRICE'); ?></div>
					</div>
					<?php
					$one = getdate($checkin);
					$fromdayts = mktime(0, 0, 0, $one['mon'], $one['mday'], $one['year']);
					$rowk = 0;
					for ($i = 0; $i < $daysdiff; $i++) {
						$todayts = $fromdayts + ($i * 86400);
						$checkwday = getdate($todayts);
						if (array_key_exists('affdayslist', $tar[0])) {
							if (array_key_exists($checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon'], $tar[0]['affdayslist'])) {
								$todaycost = $tar[0]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']];
							} else {
								$todaycost = $tar[0]['origdailycost'];
							}
						} else {
							$todaycost = $tar[0]['cost'] / $tar[0]['days'];
						}
						?>
						<div class="vbpricedetstr<?php echo $rowk; ?>">
							<div class="vbpricedetstable-leftcol"><?php echo VikBooking::sayWeekDay($checkwday['wday']).' '.$checkwday['mday']; ?></div>
							<div class="vbpricedetstable-rightcol"><span class="vbo_currency"><?php echo $currencysymb; ?></span> <span class="vbo_price"><?php echo $tax_summary ? VikBooking::numberFormat($todaycost) : VikBooking::numberFormat(VikBooking::sayCostPlusIva($todaycost, $tar[0]['idprice'])); ?></span></div>
						</div>
						<?php
						$rowk = 1 - $rowk;
					}
					if (array_key_exists('diffusage', $tar[0])) {
						if (!empty($tar[0]['diffusagecost'])) {
							$operator = substr($tar[0]['diffusagecost'], 0, 1);
							$valpcent = substr($tar[0]['diffusagecost'], -1);
							$saydiffusage = $valpcent == "%" ? "" : '<span class="vbo_currency">'.$currencysymb."</span> ";
							$saydiffusage .= $operator." ".($valpcent != "%" ? '<span class="vbo_price">' : '').VikBooking::numberFormat(substr($tar[0]['diffusagecost'], 1, (strlen($tar[0]['diffusagecost']) - 1))).($valpcent == "%" ? " %" : "</span>");
							?>
						<div class="vbpricedetstr<?php echo $rowk; ?>">
							<div class="vbpricedetstable-leftcol">&nbsp;</div>
							<div class="vbpricedetstable-rightcol">&nbsp;</div>
						</div>
							<?php
							$rowk = 1 - $rowk;
							?>
						<div class="vbpricedetstr<?php echo $rowk; ?>">
							<div class="vbpricedetstable-leftcol"><?php echo $tar[0]['diffusage']; ?> <?php echo $tar[0]['diffusage'] > 1 ? JText::translate('VBSEARCHRESADULTS') : JText::translate('VBSEARCHRESADULT'); ?></div>
							<div class="vbpricedetstable-rightcol"><?php echo $saydiffusage; ?></div>
						</div>
							<?php
						}
					}
					?>
				</div>
			</div>
		</div>
		
		<div class="vbpricedet-priceblock">
			<div class="vbpricedet-priceinner">
		<?php
		if ($tar[0]['cost'] > 0) {
			?>
				<span class="room_cost"><span class="vbo_currency"><?php echo $currencysymb; ?></span> <span class="vbo_price"><?php echo $tax_summary ? VikBooking::numberFormat($tar[0]['cost']) : VikBooking::numberFormat(VikBooking::sayCostPlusIva($tar[0]['cost'], $tar[0]['idprice'])); ?></span></span>
			<?php
		}
		?>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
jQuery.noConflict();
var sendprices = new Array();
var fromCurrency = '<?php echo $def_currency; ?>';
var fromSymbol;
var pricestaken = 0;
jQuery(document).ready(function() {
	if(jQuery(".vbo_price").length > 0) {
		jQuery(".vbo_price").each(function() {
			sendprices.push(jQuery(this).text());
		});
		pricetaken = 1;
	}
	if(jQuery(".vbo_currency").length > 0) {
		fromSymbol = jQuery(".vbo_currency").first().html();
	}
	<?php
	if(!empty($last_lang) && $last_lang != $def_currency) {
		?>
	if(jQuery(".vbo_price").length > 0) {
		vboConvertCurrency('<?php echo $last_lang; ?>');
	}
		<?php
	}
	?>
});
function vboConvertCurrency(toCurrency) {
	if(sendprices.length > 0) {
		jQuery(".vbo_currency").text(toCurrency);
		jQuery(".vbo_price").text("...").addClass("vbo_converting");
		var modvbocurconvax = jQuery.ajax({
			type: "POST",
			url: "<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=currencyconverter'); ?>",
			data: {prices: sendprices, fromsymbol: fromSymbol, fromcurrency: fromCurrency, tocurrency: toCurrency, tmpl: "component"}
		}).done(function(resp) {
			jQuery(".vbo_price").removeClass("vbo_converting");
			var convobj = JSON.parse(resp);
			if(convobj.hasOwnProperty("error")) {
				alert(convobj.error);
				vboUndoConversion();
			}else {
				jQuery(".vbo_currency").html(convobj[0].symbol);
				jQuery(".vbo_price").each(function(i) {
					jQuery(this).text(convobj[i].price);
				});
			}
		}).fail(function(){
			jQuery(".vbo_price").removeClass("vbo_converting");
			vboUndoConversion();
		});
	}
}
function vboUndoConversion() {
	jQuery(".vbo_currency").text(fromSymbol);
	jQuery(".vbo_price").each(function(i) {
		jQuery(this).text(sendprices[i]);
	});
}
</script>
<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2022 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('_JEXEC') or defined('ABSPATH') or die('No script kiddies please!');

defined('_VIKBOOKINGEXEC') OR die('Restricted Area');

/**
 * List of the available special tags that could be used in this template file:
 * {company_name} - Can be set from the Configuration page.
 * {logo} - Can be uploaded from the Configuration page.
 * {order_id} - The ID of this booking
 * {confirmnumb} - Confirmation Number
 * {order_status} - Status of the booking
 * {order_date} - Date of booking
 * {customer_info} - String containing the customer information
 * {rooms_count} - Number of rooms booked
 * {rooms_info} - String formatted containing the information about the rooms booked
 * {roomfeature VBODEFAULTDISTFEATUREONE} - Rooms Distinctive Features (see comments below)
 * {checkin_date} - Date of arrival
 * {checkout_date} - Date of departure
 * {order_details} - String formatted with the booking details
 * {order_total} - Booking total amount
 * {order_deposit} - Deposit string formatted
 * {order_total_paid} - Amount paid formatted
 * {order_link} - HTML link to the booking details page in the front-end
 * {booking_url} - Raw URL string to the booking details page in the front-end
 * {footer_emailtext} - Can be set from the Configuration page
 * {customfield 2} - Will be replaced with the Custom Field of ID 2 (Last Name by default - you can use any ID)
 *
 * The record of the booking can be accessed from the following global array in case you need any extra special tag or to perform queries for a deeper customization level:
 * $booking_info (booking array)
 * Example: the ID of the booking is contained in $booking_info['id'] - you can see the whole array content with the code "print_r($booking_info)"
 * 
 * It is also possible to add some extra admin recipient address for the email message. This is useful in case you want to send the message to a particular
 * email address, maybe when certain combinations are met, for example, when a specific room ID is booked. You can add one or more addresses by calling the
 * method shown below with some examples of possible usage:
 * 
 * VikBooking::addAdminEmailRecipient('extra@email.com'); // this will add one recipient
 * VikBooking::addAdminEmailRecipient(array('extra1@email.com', 'extra2@email.com')); // this will add two extra recipients
 * 
 * In this example with the two calls of the method, three more recipients in total will receive the email message.
 */

?>

<center class="text-direction-{lang_direction}" style="background: #fdfdfd; padding: 40px 0; color: #666; width: 100%; table-layout: fixed; direction: {lang_direction};">
	<div style="text-align: center;">
			<p>{logo}</p>
	</div>
	<div style="max-width: 800px; margin: 0 auto; background: #fff; box-shadow: 0 0 5px rgba(0, 0, 0, .2); padding: 30px; box-sizing: border-box; border-radius: 6px; border: 1px solid #ddd;">
		<h1 style="margin-bottom: 20px; font-size: 32px; font-weight: 500; color: #45c29d; margin:0 0 10px; padding:0;">{company_name}</h1>
		<!--[if (gte mso 9)|(IE)]>
			<table width="800" align="center">
			<tr>
			<td>
			<![endif]-->
		<table style="margin: 0 auto; width: 100%; max-width: 800px; border-spacing: 0; font-family: sans-serif;">
			<tbody>
				<tr>
					<td style="padding:0;">
						<!--[if (gte mso 9)|(IE)]>
						<table width="100%">
						<tr>
						<td width="50%" valign="top">
						<![endif]-->
						<div style="width: 100%; max-width: 355px; display: inline-block; vertical-align: top; text-align: {text_natural_direction};">
							<table width="90%" style="margin: 10px auto 0; padding: 5px; font-size: 14px; background:#f2f3f7;">
								<tr>
									<td style="padding: 10px; line-height: 1.4em;">
										<div style="min-height: 270px;">
											<h3 style="background:#78B8C4; display:inline-block; padding:5px 10px; text-transform:uppercase; font-size:16px; color:#fff;"><?php echo JText::translate('VBMAILYOURBOOKING'); ?></h3>
											<div>
												<p><span><?php echo JText::translate('VBORDERNUMBER'); ?>:</span> <span>{order_id}</span></p>
											</div>
											{confirmnumb_delimiter}
											<div>
												<p><span><?php echo JText::translate('VBCONFIRMNUMB'); ?>:</span> <span>{confirmnumb}</span></p>
											</div>
											{/confirmnumb_delimiter}
											<div>
												<p><span><?php echo JText::translate('VBLIBSEVEN'); ?>:</span> <span class="{order_status_class}">{order_status}</span></p>
											</div>
											<div>
												<p><span><?php echo JText::translate('VBLIBEIGHT'); ?>:</span> <span>{order_date}</span></p>
											</div>
										</div>
									</td>
								</tr>
							</table>
						</div>
						<!--[if (gte mso 9)|(IE)]>
						</td><td width="50%" valign="top">
						<![endif]-->
						<div style="width: 100%; max-width: 355px; display: inline-block; vertical-align: top; text-align: {text_natural_direction};">
							<table width="90%" style="margin: 10px auto 0; padding: 5px; font-size: 14px; background:#f2f3f7;">
								<tr>
									<td style="padding: 10px; line-height: 1.4em;">
										<div style="min-height: 270px;">
											<h3 style="background:#78B8C4; display:inline-block; padding:5px 10px; text-transform:uppercase; font-size:16px; color:#fff;"><?php echo JText::translate('VBLIBNINE'); ?></h3>
											<p>{customer_info}</p>
										</div>
									</td>
								</tr>
							</table>
						</div>
						<!--[if (gte mso 9)|(IE)]>
						</td>
						</tr>
						</table>
						<![endif]-->
					</td>
				</tr>
				<tr>
					<td style="padding:0;">
						<!--[if (gte mso 9)|(IE)]>
						<table width="100%">
						<tr>
						<td width="50%" valign="top">
						<![endif]-->
						<div style="width: 100%; max-width: 355px; display: inline-block; vertical-align: top; text-align: {text_natural_direction};">
							<table width="90%" style="background:#f2f3f7; margin: 10px auto 0; padding: 5px; font-size: 14px;">
								<tr>
									<td style="padding: 10px; line-height: 1.4em;">
										<div>
											<div><strong><?php echo JText::translate('VBLIBTEN'); ?></strong><span> {rooms_count}</span></div>
											<div>
												{rooms_info}
												<?php
												//BEGIN: Rooms Distinctive Features - Default code
												//Each unit of your rooms can have some distinctive features.
												//Here you can list some of them for the customer email.
												//The distintive features are composed of Key-Value pairs where Key is the name of the feature (i.e. Key: Room Number - Value: 102)
												//By default the system generates 1 empty Key (Feature): Room Number.
												//in this example we will only be listing this Key and others could be used for management purposes only.
												//each Key (feature) can be expressed as a language definition contained in your .INI Translation Files. You could also express a Key literally as "Room Number" without translating it.
												//By using the special-syntax {roomfeature KEY_NAME} the system will replace the Key with the corresponding value that you would like to display.
												//By default the Key "Room Number" corresponds to the language definition VBODEFAULTDISTFEATUREONE.
												//Let's display the Room Number (if it is not empty for the rooms booked):
												?>
												{roomfeature VBODEFAULTDISTFEATUREONE}
												<?php
												//END: Rooms Distinctive Features - Default code
												?>
											</div>
										</div>
									</td>
								</tr>
							</table>
						</div>
						<!--[if (gte mso 9)|(IE)]>
						</td><td width="50%" valign="top">
						<![endif]-->
						<div style="width: 100%; max-width: 355px; display: inline-block; vertical-align: top; text-align: {text_natural_direction};">
							<table width="90%" style="margin: 10px auto 0; padding: 5px; font-size: 14px; background:#f2f3f7;">
								<tr>
									<td style="padding: 10px; line-height: 1.4em;">
										<div>
											<p>
												<span style="font-weight:600;"><?php echo JText::translate('VBLIBELEVEN'); ?>:</span>
												<span>{checkin_date}</span>
											</p>
										</div>
										<div>
											<p>
												<span style="font-weight:600;"><?php echo JText::translate('VBLIBTWELVE'); ?>:</span>
												<span>{checkout_date}</span>
											</p>
										</div>
									</td>
								</tr>
							</table>
						</div>
						<!--[if (gte mso 9)|(IE)]>
						</td>
						</tr>
						</table>
						<![endif]-->
					</td>
				</tr>
				<tr>
					<td style="padding: 0; text-align: center;">
						<table width="95%" style="border-spacing: 0; margin: 10px auto 0; padding: 15px; font-size: 14px; background: #fff;">
							<tr>
								<td style="padding: 10px; line-height: 1.4em; text-align: {text_natural_direction};">
									<div>
										<h3 style="background:#78B8C4; display:inline-block; padding:5px 10px; text-transform:uppercase; font-size:16px; color:#fff;"><?php echo JText::translate('VBORDERDETAILS'); ?></h3>
										<div style="padding:10px; margin:2px 0;">
											<div>
												{order_details}
											</div>
											<div style="padding:10px; background:#f2f3f7; border:1px solid #45C29D; margin:10px 0;">
												<span><?php echo JText::translate('VBLIBSIX'); ?></span>
												<span style="float: {text_opposite_direction};">
													<strong>{order_total}</strong>
												</span>
											</div>
											<div>{order_deposit}</div>
											<div>{order_total_paid}</div>
										</div>
									</div>	
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td style="padding: 0; text-align: center;">
						<table width="95%" style="border-spacing: 0; margin: 0 auto; font-size: 14px; background: #fff;">
							<tr>
								<td style="line-height: 1.4em; text-align: {text_natural_direction};">
									<div>
										<strong><?php echo JText::translate('VBLIBTENTHREE'); ?></strong><br/>
										{order_link}
									</div>
									<div>
										<div>{footer_emailtext}</div>
									</div>
									<div>
										<br/>
									</div>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		<!--[if (gte mso 9)|(IE)]>
		</td>
		</tr>
		</table>
		<![endif]-->
	</div>
</center>

<style type="text/css">
<!--
.confirmed {color: #009900;}
.standby {color: #cc9a04;}
.cancelled {color: #ff0000;}
.text-direction-ltr .service-amount {float: right;}
.text-direction-rtl .service-amount {display: inline-block; margin-right: 5px;}
-->
</style>

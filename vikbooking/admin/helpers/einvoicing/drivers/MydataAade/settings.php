<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2022 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * MydataAade driver main settings
 */

$vbo_app = VikBooking::getVboApplication();
$data 	 = !is_array($data) ? array() : $data;

?>

<input type="hidden" name="driveraction" value="saveSettings" />

<fieldset class="vbo-driver-fieldset">
	<legend class="adminlegend">Invoice Generation</legend>
	<table cellspacing="1" class="admintable table">
		<tbody>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Auto-generation</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Auto-generate invoices', 'content' => 'If enabled, by creating a PDF (courtesy) invoice for any reservation, this driver will be invoked automatically to generate an electronic copy of the same invoice. The same thing works if you use a Cron Job to schedule an automated generation of the invoices.')); ?>
				</td>
				<td><?php echo $vbo_app->printYesNoButtons('automatic', JText::translate('VBYES'), JText::translate('VBNO'), (int)$data['automatic'], 1, 0); ?></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Auto-increment registration number</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Registration number (Mark)', 'content' => 'This is an auto-increment and unique invoice registration number used by the system to assign a unique value to every electronic invoice. Also called mark (Μοναδικός Αριθμός Καταχώρησης)')); ?>
				</td>
				<td><input type="number" name="progcount" min="1" value="<?php echo isset($data['progcount']) && !empty($data['progcount']) ? $data['progcount'] : '1'; ?>" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Auto-increment invoice number</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Invoice number', 'content' => 'This is the auto-increment invoice number used by the system to assign a new number to every invoice generated, no matter if this is in PDF or electronic format. Change it only if you know what you are doing, or if, for example, you are also using a third party software to generate invoices. This value will be used to generate the next invoices and will be incremented automatically.')); ?>
				</td>
				<td><input type="number" name="invoiceinum" min="1" value="<?php echo VikBooking::getNextInvoiceNumber(); ?>" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Invoice date</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Invoice date', 'content' => 'Choose what type of date should be used for the next generation of the invoices. The system supports the reservation date (no matter when the invoice is generated), or the invoice creation date. Choose the format that best fits your needs.')); ?>
				</td>
				<td>
					<select name="einvdttype">
						<option value="today"<?php echo isset($data['params']) && !empty($data['params']['einvdttype']) && $data['params']['einvdttype'] == 'today' ? ' selected="selected"' : ''; ?>>Creation date</option>
						<option value="ts"<?php echo isset($data['params']) && !empty($data['params']['einvdttype']) && $data['params']['einvdttype'] == 'ts' ? ' selected="selected"' : ''; ?>>Reservation date</option>
					</select>
				</td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Modify existing invoices</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Date and numbering for existing invoices', 'content' => 'Whenever an existing invoice needs to be modified or updated, you can choose to keep the previous date and number, or to use new values. In any case, the previous copy of the invoice will remain on your system.')); ?>
				</td>
				<td>
					<select name="einvexnumdt">
						<option value="new"<?php echo isset($data['params']) && !empty($data['params']['einvexnumdt']) && $data['params']['einvexnumdt'] == 'new' ? ' selected="selected"' : ''; ?>>Use new date and numbering</option>
						<option value="old"<?php echo isset($data['params']) && !empty($data['params']['einvexnumdt']) && $data['params']['einvexnumdt'] == 'old' ? ' selected="selected"' : ''; ?>>Keep previous date and number</option>
					</select>
				</td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Invoice Type</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Invoice Type', 'content' => 'Every electronic invoice in XML format requires a type-code to be specified. Choose the most appropriate one according to your business.')); ?>
				</td>
				<td>
					<?php
					$current_einv_typecode = isset($data['params']) && !empty($data['params']['einvtypecode']) ? $data['params']['einvtypecode'] : '1.1';
					?>
					<select name="einvtypecode">
						<optgroup label="Domestic/Foreign Issuer Mirrored Accounting Source Documents">
							<option value="1.1"<?php echo $current_einv_typecode == '1.1' ? ' selected="selected"' : ''; ?>>Sales Invoice</option>
							<option value="1.2"<?php echo $current_einv_typecode == '1.2' ? ' selected="selected"' : ''; ?>>Sales Invoice/Intra-community Supplies</option>
							<option value="1.3"<?php echo $current_einv_typecode == '1.3' ? ' selected="selected"' : ''; ?>>Sales Invoice/Third Country Supplies</option>
							<option value="1.4"<?php echo $current_einv_typecode == '1.4' ? ' selected="selected"' : ''; ?>>Sales Invoice/Sale on Behalf of Third Parties</option>
							<option value="1.5"<?php echo $current_einv_typecode == '1.5' ? ' selected="selected"' : ''; ?>>Sales Invoice/Clearance of Sales on Behalf of Third Parties</option>
							<option value="1.6"<?php echo $current_einv_typecode == '1.6' ? ' selected="selected"' : ''; ?>>Sales Invoice/Supplemental Accounting Source Document</option>
						</optgroup>
						<optgroup label="Service Rendered Invoice">
							<option value="2.1"<?php echo $current_einv_typecode == '2.1' ? ' selected="selected"' : ''; ?>>Service Rendered Invoice</option>
							<option value="2.2"<?php echo $current_einv_typecode == '2.2' ? ' selected="selected"' : ''; ?>>Intra-community Service Rendered Invoice</option>
							<option value="2.3"<?php echo $current_einv_typecode == '2.3' ? ' selected="selected"' : ''; ?>>Third Country Service Rendered Invoice</option>
							<option value="2.4"<?php echo $current_einv_typecode == '2.4' ? ' selected="selected"' : ''; ?>>Service Rendered Invoice/Supplemental Accounting Source Document</option>
						</optgroup>
						<optgroup label="Proof of Expenditure">
							<option value="3.1"<?php echo $current_einv_typecode == '3.1' ? ' selected="selected"' : ''; ?>>Proof of Expenditure (non-liableIssuer)</option>
							<option value="3.2"<?php echo $current_einv_typecode == '3.2' ? ' selected="selected"' : ''; ?>>Proof of Expenditure (denial of issuance by liable Issuer)</option>
						</optgroup>
						<optgroup label="For future use">
							<option value="4">F<?php echo $current_einv_typecode == '4">' ? ' selected="selected"' : ''; ?>or future use</option>
						</optgroup>
						<optgroup label="Credit invoice">
							<option value="5.1"<?php echo $current_einv_typecode == '5.1' ? ' selected="selected"' : ''; ?>>Credit Invoice/Associated</option>
							<option value="5.2"<?php echo $current_einv_typecode == '5.2' ? ' selected="selected"' : ''; ?>>Credit Invoice/Non-Associated</option>
						</optgroup>
						<optgroup label="Invoice for Self-Delivery and Self-Supply">
							<option value="6.1"<?php echo $current_einv_typecode == '6.1' ? ' selected="selected"' : ''; ?>>Self-Delivery Record</option>
							<option value="6.2"<?php echo $current_einv_typecode == '6.2' ? ' selected="selected"' : ''; ?>>Self-Supply Record</option>
						</optgroup>
						<optgroup label="Contract – Income">
							<option value="7.1"<?php echo $current_einv_typecode == '7.1' ? ' selected="selected"' : ''; ?>>Contract – Income</option>
						</optgroup>
						<optgroup label="Special Record (Income) – Collection/Payment Receipt">
							<option value="8.1"<?php echo $current_einv_typecode == '8.1' ? ' selected="selected"' : ''; ?>>Rents – Income</option>
							<option value="8.2"<?php echo $current_einv_typecode == '8.2' ? ' selected="selected"' : ''; ?>>Special Record – Accommodation Tax Collection/Payment Receipt</option>
						</optgroup>
						<optgroup label="Domestic/Foreign Recipient Non-Mirrored Accounting Source Documents">
							<option value="11.1"<?php echo $current_einv_typecode == '11.1' ? ' selected="selected"' : ''; ?>>Retail Sales Receipt</option>
							<option value="11.2"<?php echo $current_einv_typecode == '11.2' ? ' selected="selected"' : ''; ?>>Service Rendered Receipt</option>
							<option value="11.3"<?php echo $current_einv_typecode == '11.3' ? ' selected="selected"' : ''; ?>>Simplified Invoice</option>
							<option value="11.4"<?php echo $current_einv_typecode == '11.4' ? ' selected="selected"' : ''; ?>>Retail Sales Credit Note</option>
							<option value="11.5"<?php echo $current_einv_typecode == '11.5' ? ' selected="selected"' : ''; ?>>Retail Sales Receipt on Behalf of Third Parties</option>
						</optgroup>
						<optgroup label="Domestic/Foreign Recipient Non-Mirrored Accounting Source Documents">
							<option value="13.1"<?php echo $current_einv_typecode == '13.1' ? ' selected="selected"' : ''; ?>>Expenses – Domestic/Foreign Retail Transaction Purchases</option>
							<option value="13.2"<?php echo $current_einv_typecode == '13.2' ? ' selected="selected"' : ''; ?>>Domestic/Foreign Retail Transaction Provision</option>
							<option value="13.3"<?php echo $current_einv_typecode == '13.3' ? ' selected="selected"' : ''; ?>>Shared Utility Bills</option>
							<option value="13.4"<?php echo $current_einv_typecode == '13.4' ? ' selected="selected"' : ''; ?>>Subscriptions</option>
							<option value="13.30"<?php echo $current_einv_typecode == '13.30' ? ' selected="selected"' : ''; ?>>Self-Declared Entity Accounting Source Documents (Dynamic)</option>
							<option value="13.31"<?php echo $current_einv_typecode == '13.31' ? ' selected="selected"' : ''; ?>>Domestic/Foreign Retail Sales Credit Note</option>
						</optgroup>
						<optgroup label="Domestic/Foreign Recipient Mirrored Accounting Source Documents">
							<option value="14.1"<?php echo $current_einv_typecode == '14.1' ? ' selected="selected"' : ''; ?>>Invoice/Intra-community Acquisitions</option>
							<option value="14.2"<?php echo $current_einv_typecode == '14.2' ? ' selected="selected"' : ''; ?>>Invoice/Third Country Acquisitions</option>
							<option value="14.3"<?php echo $current_einv_typecode == '14.3' ? ' selected="selected"' : ''; ?>>Invoice/Intra-community Services Receipt</option>
							<option value="14.4"<?php echo $current_einv_typecode == '14.4' ? ' selected="selected"' : ''; ?>>Invoice/Third Country Services Receipt</option>
							<option value="14.5"<?php echo $current_einv_typecode == '14.5' ? ' selected="selected"' : ''; ?>>EFKA</option>
							<option value="14.30"<?php echo $current_einv_typecode == '14.30' ? ' selected="selected"' : ''; ?>>Self-Declared Entity Accounting Source Documents (Dynamic)</option>
							<option value="14.31"<?php echo $current_einv_typecode == '14.31' ? ' selected="selected"' : ''; ?>>Domestic/Foreign Credit Note</option>
						</optgroup>
						<optgroup label="Contract – Expense">
							<option value="15.1"<?php echo $current_einv_typecode == '15.1' ? ' selected="selected"' : ''; ?>>Contract-Expense</option>
						</optgroup>
						<optgroup label="Special Record (Expense) – Payment Receipt">
							<option value="16.1"<?php echo $current_einv_typecode == '16.1' ? ' selected="selected"' : ''; ?>>Rent-Expense</option>
						</optgroup>
						<optgroup label="Input/Output Adjustment/Regularisation Entries">
							<option value="17.1"<?php echo $current_einv_typecode == '17.1' ? ' selected="selected"' : ''; ?>>Payroll</option>
							<option value="17.2"<?php echo $current_einv_typecode == '17.2' ? ' selected="selected"' : ''; ?>>Amortisations</option>
							<option value="17.3"<?php echo $current_einv_typecode == '17.3' ? ' selected="selected"' : ''; ?>>Other Income Adjustment/Regularisation Entries – Accounting Base</option>
							<option value="17.4"<?php echo $current_einv_typecode == '17.4' ? ' selected="selected"' : ''; ?>>Other Income Adjustment/Regularisation Entries – Tax Base</option>
							<option value="17.5"<?php echo $current_einv_typecode == '17.5' ? ' selected="selected"' : ''; ?>>Other Expense Adjustment/Regularisation Entries – Accounting Base</option>
							<option value="17.6"<?php echo $current_einv_typecode == '17.6' ? ' selected="selected"' : ''; ?>>Other Expense Adjustment/Regularisation Entries – Tax Base</option>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>VAT Exemption Category</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'VAT Exemption Category Code', 'content' => 'Whenever a line-item, hence a service, has got no tax rate assigned (like a Tourist Tax which is a tax itself), it is necessary to specify the VAT exemption category code. Choose the most appropriate category for your services that apply no tax rates.')); ?>
				</td>
				<td>
					<?php
					$current_vatexempt_catcode = isset($data['params']) && !empty($data['params']['vat_exempt_cat']) ? $data['params']['vat_exempt_cat'] : '';
					?>
					<select name="vat_exempt_cat">
						<option value="">Not specified</option>
						<option value="1"<?php echo $current_vatexempt_catcode == '1' ? ' selected="selected"' : ''; ?>>Without VAT - article 3 of the VAT code</option>
						<option value="2"<?php echo $current_vatexempt_catcode == '2' ? ' selected="selected"' : ''; ?>>Without VAT - article 5 of the VAT code</option>
						<option value="3"<?php echo $current_vatexempt_catcode == '3' ? ' selected="selected"' : ''; ?>>Without VAT - article 13 of the VAT code</option>
						<option value="4"<?php echo $current_vatexempt_catcode == '4' ? ' selected="selected"' : ''; ?>>Without VAT - article 14 of the VAT code</option>
						<option value="5"<?php echo $current_vatexempt_catcode == '5' ? ' selected="selected"' : ''; ?>>Without VAT - article 16 of the VAT code</option>
						<option value="6"<?php echo $current_vatexempt_catcode == '6' ? ' selected="selected"' : ''; ?>>Without VAT - article 19 of the VAT code</option>
						<option value="7"<?php echo $current_vatexempt_catcode == '7' ? ' selected="selected"' : ''; ?>>Without VAT - article 22 of the VAT code</option>
						<option value="8"<?php echo $current_vatexempt_catcode == '8' ? ' selected="selected"' : ''; ?>>Without VAT - article 24 of the VAT code</option>
						<option value="9"<?php echo $current_vatexempt_catcode == '9' ? ' selected="selected"' : ''; ?>>Without VAT - article 25 of the VAT code</option>
						<option value="10"<?php echo $current_vatexempt_catcode == '10' ? ' selected="selected"' : ''; ?>>Without VAT - article 26 of the VAT code</option>
						<option value="11"<?php echo $current_vatexempt_catcode == '11' ? ' selected="selected"' : ''; ?>>Without VAT - article 27 of the VAT code</option>
						<option value="12"<?php echo $current_vatexempt_catcode == '12' ? ' selected="selected"' : ''; ?>>Without VAT - article 27 - Seagoing Vessels of the VAT code</option>
						<option value="13"<?php echo $current_vatexempt_catcode == '13' ? ' selected="selected"' : ''; ?>>Without VAT - article 27.1.γ - Seagoing Vessels of the VAT code</option>
						<option value="14"<?php echo $current_vatexempt_catcode == '14' ? ' selected="selected"' : ''; ?>>Without VAT - article 28 of the VAT code</option>
						<option value="15"<?php echo $current_vatexempt_catcode == '15' ? ' selected="selected"' : ''; ?>>Without VAT - article 39 of the VAT code</option>
						<option value="16"<?php echo $current_vatexempt_catcode == '16' ? ' selected="selected"' : ''; ?>>Without VAT - article 39a of the VAT code</option>
						<option value="17"<?php echo $current_vatexempt_catcode == '17' ? ' selected="selected"' : ''; ?>>Without VAT - article 40 of the VAT code</option>
						<option value="18"<?php echo $current_vatexempt_catcode == '18' ? ' selected="selected"' : ''; ?>>Without VAT - article 41 of the VAT code</option>
						<option value="19"<?php echo $current_vatexempt_catcode == '19' ? ' selected="selected"' : ''; ?>>Without VAT - article 47 of the VAT code</option>
						<option value="20"<?php echo $current_vatexempt_catcode == '20' ? ' selected="selected"' : ''; ?>>VAT included - article 43 of the VAT code</option>
						<option value="21"<?php echo $current_vatexempt_catcode == '21' ? ' selected="selected"' : ''; ?>>VAT included - article 44 of the VAT code</option>
						<option value="22"<?php echo $current_vatexempt_catcode == '22' ? ' selected="selected"' : ''; ?>>VAT included - article 45 of the VAT code</option>
						<option value="23"<?php echo $current_vatexempt_catcode == '23' ? ' selected="selected"' : ''; ?>>VAT included - article 46 of the VAT code</option>
					</select>
				</td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Payment Method Types</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Payment Methods', 'content' => 'Electronic invoices may require the payment method of the invoice to be specified.')); ?>
				</td>
				<td>
					<?php
					$current_einv_paymethod = isset($data['params']) && !empty($data['params']['einv_paymethod']) ? $data['params']['einv_paymethod'] : '1';
					?>
					<select name="einv_paymethod">
						<option value="1"<?php echo $current_einv_paymethod == '1' ? ' selected="selected"' : ''; ?>>Domestic Payments Account Number</option>
						<option value="2"<?php echo $current_einv_paymethod == '2' ? ' selected="selected"' : ''; ?>>Foreign Payments Account Number</option>
						<option value="3"<?php echo $current_einv_paymethod == '3' ? ' selected="selected"' : ''; ?>>Cash</option>
						<option value="4"<?php echo $current_einv_paymethod == '4' ? ' selected="selected"' : ''; ?>>Check</option>
						<option value="5"<?php echo $current_einv_paymethod == '5' ? ' selected="selected"' : ''; ?>>On credit</option>
					</select>
				</td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Income Classification Type</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Income Classification Type', 'content' => 'Certain invoices may require the Income Classification Type to be specified. Please select the appropriate type for your business.')); ?>
				</td>
				<td>
					<?php
					$current_einv_inc_class_type = isset($data['params']) && !empty($data['params']['einv_inc_class_type']) ? $data['params']['einv_inc_class_type'] : '';
					?>
					<select name="einv_inc_class_type">
						<option value=""></option>
						<option value="E3_106"<?php echo $current_einv_inc_class_type == 'E3_106' ? ' selected="selected"' : ''; ?>>Self-Production of Fixed Assets – Self-Deliveries – Destroying inventory/Commodities</option>
						<option value="E3_205"<?php echo $current_einv_inc_class_type == 'E3_205' ? ' selected="selected"' : ''; ?>>Self-Production of Fixed Assets – Self-Deliveries – Destroying inventory/Raw and other materials</option>
						<option value="E3_210"<?php echo $current_einv_inc_class_type == 'E3_210' ? ' selected="selected"' : ''; ?>>Self-Production of Fixed Assets – Self-Deliveries – Destroying inventory/Products and production in progress</option>
						<option value="E3_305"<?php echo $current_einv_inc_class_type == 'E3_305' ? ' selected="selected"' : ''; ?>>Self-Production of Fixed Assets – Self-Deliveries – Destroying inventory/Raw and other materials</option>
						<option value="E3_310"<?php echo $current_einv_inc_class_type == 'E3_310' ? ' selected="selected"' : ''; ?>>Self-Production of Fixed Assets – Self-Deliveries – Destroying inventory/ Products and production in progress</option>
						<option value="E3_318"<?php echo $current_einv_inc_class_type == 'E3_318' ? ' selected="selected"' : ''; ?>>Self-Production of Fixed Assets – Self-Deliveries – Destroying inventory/Production expenses</option>
						<option value="E3_561_001"<?php echo $current_einv_inc_class_type == 'E3_561_001' ? ' selected="selected"' : ''; ?>>Wholesale Sales of Goods and Services – for Traders</option>
						<option value="E3_561_002"<?php echo $current_einv_inc_class_type == 'E3_561_002' ? ' selected="selected"' : ''; ?>>Wholesale Sales of Goods and Services pursuant to article 39a paragraph 5 of the VAT Code (Law 2859/2000)</option>
						<option value="E3_561_003"<?php echo $current_einv_inc_class_type == 'E3_561_003' ? ' selected="selected"' : ''; ?>>Retail Sales of Goods and Services – Private Clientele</option>
						<option value="E3_561_004"<?php echo $current_einv_inc_class_type == 'E3_561_004' ? ' selected="selected"' : ''; ?>>Retail Sales of Goods and Services pursuant to article 39a paragraph 5 of the VAT Code (Law 2859/2000)</option>
						<option value="E3_561_005"<?php echo $current_einv_inc_class_type == 'E3_561_005' ? ' selected="selected"' : ''; ?>>Intra-Community Foreign Sales of Goods and Services</option>
						<option value="E3_561_006"<?php echo $current_einv_inc_class_type == 'E3_561_006' ? ' selected="selected"' : ''; ?>>Third Country Foreign Sales of Goods and Services</option>
						<option value="E3_561_007"<?php echo $current_einv_inc_class_type == 'E3_561_007' ? ' selected="selected"' : ''; ?>>Other Sales of Goods and Services</option>
						<option value="E3_562"<?php echo $current_einv_inc_class_type == 'E3_562' ? ' selected="selected"' : ''; ?>>Other Ordinary Income</option>
						<option value="E3_563"<?php echo $current_einv_inc_class_type == 'E3_563' ? ' selected="selected"' : ''; ?>>Credit Interest and Related Income</option>
						<option value="E3_564"<?php echo $current_einv_inc_class_type == 'E3_564' ? ' selected="selected"' : ''; ?>>Credit Exchange Differences</option>
						<option value="E3_565"<?php echo $current_einv_inc_class_type == 'E3_565' ? ' selected="selected"' : ''; ?>>Income from Participations</option>
						<option value="E3_566"<?php echo $current_einv_inc_class_type == 'E3_566' ? ' selected="selected"' : ''; ?>>Profits from Disposing Non-Current Assets</option>
						<option value="E3_567"<?php echo $current_einv_inc_class_type == 'E3_567' ? ' selected="selected"' : ''; ?>>Profits from the Reversal of Provisions and Impairments</option>
						<option value="E3_568"<?php echo $current_einv_inc_class_type == 'E3_568' ? ' selected="selected"' : ''; ?>>Profits from Measurement at Fair Value</option>
						<option value="E3_570"<?php echo $current_einv_inc_class_type == 'E3_570' ? ' selected="selected"' : ''; ?>>Extraordinary income and profits</option>
						<option value="E3_595"<?php echo $current_einv_inc_class_type == 'E3_595' ? ' selected="selected"' : ''; ?>>Self-Production Expenses</option>
						<option value="E3_596"<?php echo $current_einv_inc_class_type == 'E3_596' ? ' selected="selected"' : ''; ?>>Subsidies - Grants</option>
						<option value="E3_597"<?php echo $current_einv_inc_class_type == 'E3_597' ? ' selected="selected"' : ''; ?>>Subsidies – Grants for Investment Purposes – Expense Coverage</option>
						<option value="E3_880_001"<?php echo $current_einv_inc_class_type == 'E3_880_001' ? ' selected="selected"' : ''; ?>>Wholesale Sales of Fixed Assets</option>
						<option value="E3_880_002"<?php echo $current_einv_inc_class_type == 'E3_880_002' ? ' selected="selected"' : ''; ?>>Retail Sales of Fixed Assets</option>
						<option value="E3_880_003"<?php echo $current_einv_inc_class_type == 'E3_880_003' ? ' selected="selected"' : ''; ?>>Intra-Community Foreign Sales of Fixed Assets</option>
						<option value="E3_880_004"<?php echo $current_einv_inc_class_type == 'E3_880_004' ? ' selected="selected"' : ''; ?>>Third Country Foreign Sales of Fixed Assets</option>
						<option value="E3_881_001"<?php echo $current_einv_inc_class_type == 'E3_881_001' ? ' selected="selected"' : ''; ?>>Wholesale Sales on behalf of Third Parties</option>
						<option value="E3_881_002"<?php echo $current_einv_inc_class_type == 'E3_881_002' ? ' selected="selected"' : ''; ?>>Retail Sales on behalf of Third Parties</option>
						<option value="E3_881_003"<?php echo $current_einv_inc_class_type == 'E3_881_003' ? ' selected="selected"' : ''; ?>>Intra-Community Foreign Sales on behalf of Third Parties</option>
						<option value="E3_881_004"<?php echo $current_einv_inc_class_type == 'E3_881_004' ? ' selected="selected"' : ''; ?>>Third Country Foreign Sales on behalf of Third Parties</option>
					</select>
				</td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Income Classification Category</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Income Classification Category', 'content' => 'Certain invoices may require the Income Classification Category to be specified. Please select the appropriate category for your business.')); ?>
				</td>
				<td>
					<?php
					$current_einv_inc_class_cat = isset($data['params']) && !empty($data['params']['einv_inc_class_cat']) ? $data['params']['einv_inc_class_cat'] : '';
					?>
					<select name="einv_inc_class_cat">
						<option value=""></option>
						<option value="category1_1"<?php echo $current_einv_inc_class_cat == 'category1_1' ? ' selected="selected"' : ''; ?>>Commodity Sale Income</option>
						<option value="category1_2"<?php echo $current_einv_inc_class_cat == 'category1_2' ? ' selected="selected"' : ''; ?>>Product Sale Income</option>
						<option value="category1_3"<?php echo $current_einv_inc_class_cat == 'category1_3' ? ' selected="selected"' : ''; ?>>Provision of Services Income</option>
						<option value="category1_4"<?php echo $current_einv_inc_class_cat == 'category1_4' ? ' selected="selected"' : ''; ?>>Sale of Fixed Assets Income</option>
						<option value="category1_5"<?php echo $current_einv_inc_class_cat == 'category1_5' ? ' selected="selected"' : ''; ?>>Other Income/Profits</option>
						<option value="category1_6"<?php echo $current_einv_inc_class_cat == 'category1_6' ? ' selected="selected"' : ''; ?>>Self-Deliveries/Self-Supplies</option>
						<option value="category1_7"<?php echo $current_einv_inc_class_cat == 'category1_7' ? ' selected="selected"' : ''; ?>>Income on behalf of Third Parties</option>
						<option value="category1_8"<?php echo $current_einv_inc_class_cat == 'category1_8' ? ' selected="selected"' : ''; ?>>Past fiscal years income</option>
						<option value="category1_9"<?php echo $current_einv_inc_class_cat == 'category1_9' ? ' selected="selected"' : ''; ?>>Future fiscal years income</option>
						<option value="category1_10"<?php echo $current_einv_inc_class_cat == 'category1_10' ? ' selected="selected"' : ''; ?>>Other Income Adjustment/Regularisation Entries</option>
						<option value="category1_95"<?php echo $current_einv_inc_class_cat == 'category1_95' ? ' selected="selected"' : ''; ?>>Other Income-related Information</option>
					</select>
				</td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>XML Schema Validation</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'XML Schema Validation', 'content' => 'It is possible to validate every XML (eletronic) invoice generated before actually send them to the myDATA platform. This will definitely prevent any possible error with the transmission if the generation has passed the official AADE schema validation. However, some servers may not support this feature, and so in this case you should disable this setting.')); ?>
				</td>
				<td><?php echo $vbo_app->printYesNoButtons('schema_validate', JText::translate('VBYES'), JText::translate('VBNO'), (isset($data['params']) && !empty($data['params']['schema_validate']) ? 1 : 0), 1, 0); ?></td>
			</tr>
			<?php
			$gen_env_invoice = isset($data['params']) && !empty($data['params']['environmental_invoice']);
			?>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Generate Environmental Fee Invoice</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Generate Environmental Fee Invoice', 'content' => 'If enabled, the system will generate the regular reservation invoice, plus an additional and correlated invoice just for the Environmental Fee, as long as this is configured and available in the reservation being invoiced.')); ?>
				</td>
				<td><?php echo $vbo_app->printYesNoButtons('environmental_invoice', JText::translate('VBYES'), JText::translate('VBNO'), ($gen_env_invoice ? 1 : 0), 1, 0, 'mydataToggleSettings(this.checked, \'environmental-invoice\');'); ?></td>
			</tr>
			<tr data-relatedto="environmental-invoice" style="<?php echo $gen_env_invoice ? '' : 'display: none;'; ?>">
				<td width="200" class="vbo-config-param-cell"> 
					<b>Auto-increment number</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Auto-increment number - Environmental invoice', 'content' => 'This is the auto-increment invoice number used by the system to assign a new number to every environmental fee invoice generated. This value will be used to generate the next environmental fee invoices, and will be automatically increased.')); ?>
				</td>
				<td><input type="number" name="envfeeinvoiceinum" min="1" value="<?php echo isset($data['params']) && !empty($data['params']['envfeeinvoiceinum']) ? $data['params']['envfeeinvoiceinum'] : 1; ?>" /></td>
			</tr>
			<tr data-relatedto="environmental-invoice" style="<?php echo $gen_env_invoice ? '' : 'display: none;'; ?>">
				<td width="200" class="vbo-config-param-cell"> 
					<b>Active Environmental Fee</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Active Environmental Fee', 'content' => 'Choose the configured environmental fee in Vik Booking to allow the system to detect the service from every reservation.')); ?>
				</td>
				<td>
					<select name="envfeevboid">
						<option value="">-----</option>
					<?php
					$active_env_fee = isset($data['params']) && !empty($data['params']['envfeevboid']) ? $data['params']['envfeevboid'] : 0;
					foreach (($data['mandatory_fees'] ?? []) as $fee) {
						?>
						<option value="<?php echo $fee['id']; ?>"<?php echo $fee['id'] == $active_env_fee ? ' selected="selected"' : ''; ?>><?php echo $fee['name']; ?></option>
						<?php
					}
					?>
					</select>
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>

<fieldset class="vbo-driver-fieldset">
	<legend class="adminlegend">myDATA Transmission Settings</legend>
	<table cellspacing="1" class="admintable table">
		<tbody>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>ΑΑΔΕ User name</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'AADE User name', 'content' => 'Log into your myDATA account to find this information for your profile. Every request made to the myDATA service requires an authentication composed of User ID (username of the account) and Subscription Key.')); ?>
				</td>
				<td><input type="text" name="aade_user_id" value="<?php echo isset($data['params']) && !empty($data['params']['aade_user_id']) ? $data['params']['aade_user_id'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Subscription key</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Ocp Apim Subscription Key', 'content' => 'Subscription key which provides access to the API. Found in your myDATA Profile.')); ?>
				</td>
				<td><input type="text" id="aade_subscription_key" name="aade_subscription_key" value="<?php echo isset($data['params']) && !empty($data['params']['aade_subscription_key']) ? $data['params']['aade_subscription_key'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>myDATA Requests Base URL</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'myDATA Requests Base URL', 'content' => 'In your myDATA Profile you should be able to detect the endpoint base URL for all requests in live mode. When working in development mode, this URL is https://mydataapidev.aade.gr/ but for live mode you should enter your own endpoint URL, which is usually https://mydatapi.aade.gr/myDATA/')); ?>
				</td>
				<td><input type="text" id="mydata_endpoint_url" name="mydata_endpoint_url" value="<?php echo isset($data['params']) && !empty($data['params']['mydata_endpoint_url']) ? $data['params']['mydata_endpoint_url'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Test (Dev) Environment</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Test Environment (myDATADev)', 'content' => 'If enabled, the system will use the development endpoint of myDATA to transmit the e-invoices. Make sure to turn off this setting when in live mode. Also, your User Name and Key credentials for the test mode will be different from the ones of the live mode. You should also define the proper requests base URL.')); ?>
				</td>
				<td>
					<?php echo $vbo_app->printYesNoButtons('test_mode', JText::translate('VBYES'), JText::translate('VBNO'), (isset($data['params']) && !empty($data['params']['test_mode']) ? 1 : 0), 1, 0); ?>
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>

<fieldset class="vbo-driver-fieldset">
	<legend class="adminlegend"><?php echo JText::translate('VBSEPDRIVERD'); ?> (Issuer)</legend>
	<table cellspacing="1" class="admintable table">
		<tbody>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b><?php echo JText::translate('VBCONFIGTHREEONE'); ?></b> 
					<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBCONFIGTHREEONE'), 'content' => 'The issuer name value, hence your company name.')); ?>
				</td>
				<td><input type="text" name="companyname" value="<?php echo isset($data['params']) && !empty($data['params']['companyname']) ? $data['params']['companyname'] : VikBooking::getFrontTitle(); ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b><?php echo JText::translate('VBCUSTOMERCOMPANYVAT'); ?></b> </td>
				<td><input type="text" name="vatid" value="<?php echo isset($data['params']) && !empty($data['params']['vatid']) ? $data['params']['vatid'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b><?php echo JText::translate('ORDER_STATE'); ?> (2-char code)</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'ISO 3166-1 alpha-2 code', 'content' => 'The issuer (company) country of residence 2-char ISO code, like GR for Greece.')); ?>
				</td>
				<td><input type="text" name="country" value="<?php echo isset($data['params']) && !empty($data['params']['country']) ? $data['params']['country'] : 'GR'; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b><?php echo JText::translate('ORDER_ADDRESS'); ?></b> </td>
				<td><input type="text" name="address" value="<?php echo isset($data['params']) && !empty($data['params']['address']) ? $data['params']['address'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b>Street Number</b> </td>
				<td><input type="text" name="streetnumber" value="<?php echo isset($data['params']) && !empty($data['params']['streetnumber']) ? $data['params']['streetnumber'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b><?php echo JText::translate('ORDER_ZIP'); ?></b> </td>
				<td><input type="text" name="zip" value="<?php echo isset($data['params']) && !empty($data['params']['zip']) ? $data['params']['zip'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b><?php echo JText::translate('ORDER_CITY'); ?></b> </td>
				<td><input type="text" name="city" value="<?php echo isset($data['params']) && !empty($data['params']['city']) ? $data['params']['city'] : ''; ?>" size="30" /></td>
			</tr>
		</tbody>
	</table>
</fieldset>

<script type="text/javascript">
	/** some browsers may be overriding any password field with what was saved, so we change the type of field after 1 second **/
	setTimeout(function() {
		document.getElementById('aade_subscription_key').setAttribute('type', 'password');
	}, 1000);

	function mydataToggleSettings(on, type) {
		if (on) {
			jQuery('tr[data-relatedto="' + type + '"]').show();
		} else {
			jQuery('tr[data-relatedto="' + type + '"]').hide();
		}
	}
</script>

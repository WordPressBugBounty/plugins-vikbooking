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

// import Joomla view library
jimport('joomla.application.component.view');

class VikBookingViewOrders extends JViewVikBooking
{
	public function display($tpl = null)
	{
		// Set the toolbar
		$this->addToolBar();

		if (!JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		$rows = "";
		$navbut = "";
		$app = JFactory::getApplication();
		$dbo = JFactory::getDbo();
		if (file_exists(VCM_ADMIN_PATH.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'vcm-channels.css')) {
			$document = JFactory::getDocument();
			$document->addStyleSheet(VCM_ADMIN_URI.'assets/css/vcm-channels.css');
		}
		$pconfirmnumber = VikRequest::getString('confirmnumber', '', 'request');
		$pidroom = $app->getUserStateFromRequest("vbo.orders.idroom", 'idroom', 0, 'int');
		$pchannel = $app->getUserStateFromRequest("vbo.orders.channel", 'channel', '', 'string');
		$pcust_id = $app->getUserStateFromRequest("vbo.orders.cust_id", 'cust_id', 0, 'int');
		$pdatefilt = $app->getUserStateFromRequest("vbo.orders.datefilt", 'datefilt', 0, 'int');
		$pdatefiltfrom = $app->getUserStateFromRequest("vbo.orders.datefiltfrom", 'datefiltfrom', '', 'string');
		$pdatefiltto = $app->getUserStateFromRequest("vbo.orders.datefiltto", 'datefiltto', '', 'string');
		$pcategory_id = $app->getUserStateFromRequest("vbo.orders.category_id", 'category_id', 0, 'int');
		$dates_filter = '';
		if (!empty($pdatefilt) && (!empty($pdatefiltfrom) || !empty($pdatefiltto))) {
			$dates_filter_field = '`o`.`ts`';
			if ($pdatefilt == 2) {
				$dates_filter_field = '`o`.`checkin`';
			} elseif ($pdatefilt == 3) {
				$dates_filter_field = '`o`.`checkout`';
			}
			$dates_filter_clauses = array();
			if (!empty($pdatefiltfrom)) {
				$dates_filter_clauses[] = $dates_filter_field.'>='.VikBooking::getDateTimestamp($pdatefiltfrom, '0', '0');
			}
			if (!empty($pdatefiltto)) {
				$dates_filter_clauses[] = $dates_filter_field.'<='.VikBooking::getDateTimestamp($pdatefiltto, 23, 60);
			}
			$dates_filter = implode(' AND ', $dates_filter_clauses);
		}
		$pstatus = $app->getUserStateFromRequest("vbo.orders.status", 'status', 0, 'string');
		$status_filter = !empty($pstatus) && in_array($pstatus, array('confirmed', 'standby', 'cancelled')) ? "`o`.`status`='".$pstatus."' AND `o`.`closure`=0" : '';
		if (empty($status_filter) && !empty($pstatus)) {
			if ($pstatus == 'closure') {
				$status_filter = "`o`.`closure`=1";
			} elseif (in_array($pstatus, array('checkedin', 'checkedout', 'noshow', 'none'))) {
				switch ($pstatus) {
					case 'checkedin':
						$status_filter = "`o`.`checked`=1";
						break;
					case 'checkedout':
						$status_filter = "`o`.`checked`=2";
						break;
					case 'noshow':
						$status_filter = "`o`.`checked` < 0";
						break;
					case 'none':
						$status_filter = "`o`.`checked`=0 AND `o`.`closure`=0";
						break;
					default:
						break;
				}
			} elseif (in_array($pstatus, ['request', 'inquiry', 'overbooking'])) {
				$status_filter = "`o`.`type`=" . $dbo->quote($pstatus);
			} elseif ($pstatus == 'split_stay') {
				$status_filter = "`o`.`split_stay`=1";
			}
		}
		$pidpayment = $app->getUserStateFromRequest("vbo.orders.idpayment", 'idpayment', 0, 'int');
		$payment_filter = '';
		if (!empty($pidpayment)) {
			$payment_filter = "`o`.`idpayment` LIKE '".$pidpayment."=%'";
		}
		$ordersfound = false;
		$lim = $app->getUserStateFromRequest("com_vikbooking.limit", 'limit', $app->get('list_limit'), 'int');
		$lim0 = $app->getUserStateFromRequest("vbo.orders.limitstart", 'limitstart', 0, 'int');
		$session = JFactory::getSession();
		$pvborderby = VikRequest::getString('vborderby', '', 'request');
		$pvbordersort = VikRequest::getString('vbordersort', '', 'request');
		$validorderby = array('id', 'ts', 'days', 'checkin', 'checkout', 'total');
		$orderby = $session->get('vbViewOrdersOrderby', 'ts');
		$ordersort = $session->get('vbViewOrdersOrdersort', 'DESC');
		if (!empty($pvborderby) && in_array($pvborderby, $validorderby)) {
			$orderby = $pvborderby;
			$session->set('vbViewOrdersOrderby', $orderby);
			if (!empty($pvbordersort) && in_array($pvbordersort, array('ASC', 'DESC'))) {
				$ordersort = $pvbordersort;
				$session->set('vbViewOrdersOrdersort', $ordersort);
			}
		}

		$q = "SELECT `id`,`name` FROM `#__vikbooking_rooms` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$allrooms = $dbo->loadAssocList();

		if (!empty($pconfirmnumber)) {
			/**
			 * We allow looking for a specific ID, OTAID, Coupon code etc.. by using syntaxes like "id: 500"
			 */
			$q = $dbo->getQuery(true)->select('SQL_CALC_FOUND_ROWS `o`.*');
			$q->from($dbo->qn('#__vikbooking_orders', 'o'));
			$q->where(1);

			if (stripos($pconfirmnumber, 'id:') === 0) {
				// search by ID or OTA ID
				$seek_parts = explode('id:', $pconfirmnumber);
				$seek_value = trim($seek_parts[1]);
				$q->andWhere([
					$dbo->qn('o.id') . ' = ' . $dbo->q($seek_value),
					$dbo->qn('o.idorderota') . ' = ' . $dbo->q($seek_value),
				], $glue = 'OR');
			} elseif (stripos($pconfirmnumber, 'otaid:') === 0) {
				// search by OTA Booking ID
				$seek_parts = explode('otaid:', $pconfirmnumber);
				$seek_value = trim($seek_parts[1]);
				$q->where($dbo->qn('o.idorderota') . ' = ' . $dbo->q($seek_value));
			} elseif (stripos($pconfirmnumber, 'coupon:') === 0) {
				// search by coupon code
				$seek_parts = explode('coupon:', $pconfirmnumber);
				$seek_value = trim($seek_parts[1]);
				$q->where($dbo->qn('o.coupon') . ' LIKE ' . $dbo->q("%{$seek_value}%"));
			} elseif (stripos($pconfirmnumber, 'name:') === 0) {
				// search by customer nominative
				$seek_parts = explode('name:', $pconfirmnumber);
				$seek_value = trim($seek_parts[1]);
				$q->leftJoin($dbo->qn('#__vikbooking_customers_orders', 'co') . ' ON ' . $dbo->qn('co.idorder') . ' = ' . $dbo->qn('o.id'));
				$q->leftJoin($dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $dbo->qn('c.id') . ' = ' . $dbo->qn('co.idcustomer'));
				$q->where('CONCAT_WS(\' \', ' . $dbo->qn('c.first_name') . ', ' . $dbo->qn('c.last_name') . ') LIKE ' . $dbo->q("%{$seek_value}%"));
			} else {
				// seek for various values
				$q->andWhere([
					$dbo->qn('o.id') . ' = ' . $dbo->q($pconfirmnumber),
					$dbo->qn('o.confirmnumber') . ' LIKE ' . $dbo->q("%{$pconfirmnumber}%"),
					$dbo->qn('o.idorderota') . ' = ' . $dbo->q($pconfirmnumber),
				], $glue = 'OR');
			}

			$q->order($dbo->qn("o.{$orderby}") . ' ' . $ordersort);

			$dbo->setQuery($q, $lim0, $lim);
			$rows = $dbo->loadAssocList();

			if ($rows) {
				$dbo->setQuery('SELECT FOUND_ROWS();');
				$totres = $dbo->loadResult();
				if ($totres == 1 && count($rows) == 1) {
					$app->redirect("index.php?option=com_vikbooking&task=editorder&cid[]=".$rows[0]['id']);
					$app->close();
				}

				$ordersfound = true;
				jimport('joomla.html.pagination');
				$pageNav = new JPagination( $totres, $lim0, $lim );
				$navbut = "<table align=\"center\"><tr><td>".$pageNav->getListFooter()."</td></tr></table>";
			}
		}

		if (!$ordersfound) {
			if (!empty($pcust_id)) {
				$q = "SELECT SQL_CALC_FOUND_ROWS `o`.*,`co`.`idcustomer`,CONCAT_WS(' ', `c`.`first_name`, `c`.`last_name`) AS `customer_fullname` FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `co`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_customers` `c` ON `c`.`id`=`co`.`idcustomer` AND `c`.`id`=".$pcust_id." WHERE ".(!empty($dates_filter) ? $dates_filter.' AND ' : '').(!empty($payment_filter) ? $payment_filter.' AND ' : '').(!empty($status_filter) ? $status_filter.' AND ' : '')."`co`.`idcustomer`=".$pcust_id." ORDER BY `o`.`".$orderby."` ".$ordersort;
			} elseif (!empty($pidroom) || !empty($pcategory_id)) {
				// ONLY_FULL_GROUP_BY safe
				$clauses = array();
				if (!empty($dates_filter)) {
					array_push($clauses, $dates_filter);
				}
				if (!empty($payment_filter)) {
					array_push($clauses, $payment_filter);
				}
				if (!empty($status_filter)) {
					array_push($clauses, $status_filter);
				}
				if (!empty($pidroom)) {
					array_push($clauses, '`or`.`idroom`=' . $pidroom);
				}
				if (!empty($pcategory_id)) {
					array_push($clauses, "(`r`.`idcat`='" . $pcategory_id . ";' OR `r`.`idcat` LIKE '" . $pcategory_id . ";%' OR `r`.`idcat` LIKE '%;" . $pcategory_id . ";%' OR `r`.`idcat` LIKE '%;" . $pcategory_id . ";')");
				}
				if (strlen($pchannel)) {
					array_push($clauses, '`o`.`channel` ' . ($pchannel == '-1' ? 'IS NULL' : "LIKE " . $dbo->quote("%" . $pchannel . "%")));
				}
				$q = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `o`.*,`or`.`idorder`,`r`.`idcat` FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `o`.`id`=`or`.`idorder` LEFT JOIN `#__vikbooking_rooms` AS `r` ON `or`.`idroom`=`r`.`id` WHERE " . implode(' AND ', $clauses) . " ORDER BY `o`.`".$orderby."` ".$ordersort;
			} else {
				$clauses = array();
				if (!empty($dates_filter)) {
					$clauses[] = $dates_filter;
				}
				if (!empty($payment_filter)) {
					$clauses[] = $payment_filter;
				}
				if (!empty($status_filter)) {
					$clauses[] = $status_filter;
				}
				if (strlen($pchannel)) {
					$clauses[] = "`o`.`channel` ".($pchannel == '-1' ? 'IS NULL' : "LIKE ".$dbo->quote("%".$pchannel."%"));
				}
				$q = "SELECT SQL_CALC_FOUND_ROWS `o`.* FROM `#__vikbooking_orders` AS `o`".(count($clauses) > 0 ? " WHERE ".implode(' AND ', $clauses) : "")." ORDER BY `o`.`".$orderby."` ".$ordersort.($orderby == 'ts' && $ordersort == 'DESC' ? ', `o`.`id` DESC' : '');
			}
			$dbo->setQuery($q, $lim0, $lim);
			$dbo->execute();

			/**
			 * Call assertListQuery() from the View class to make sure the filters set
			 * do not produce an empty result. This would reset the page in this case.
			 * 
			 * @since 	1.12.0 (J) - 1.2.0 (WP)
			 */
			$this->assertListQuery($lim0, $lim);
			//

			if ($dbo->getNumRows()) {
				$rows = $dbo->loadAssocList();
				$dbo->setQuery('SELECT FOUND_ROWS();');
				jimport('joomla.html.pagination');
				$pageNav = new JPagination( $dbo->loadResult(), $lim0, $lim );
				$navbut = "<table align=\"center\"><tr><td>".$pageNav->getListFooter()."</td></tr></table>";
			}
		}

		/**
		 * Filter by category
		 * 
		 * @since 	1.13
		 */
		$categories = array();
		$q = "SELECT `id`,`name` FROM `#__vikbooking_categories` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$allcats = $dbo->loadAssocList();
			foreach ($allcats as $cat) {
				$categories[$cat['id']] = $cat['name'];
			}
		}
		//
		
		$this->rows = $rows;
		$this->allrooms = $allrooms;
		$this->lim0 = $lim0;
		$this->navbut = $navbut;
		$this->orderby = $orderby;
		$this->ordersort = $ordersort;
		$this->categories = $categories;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		JToolBarHelper::title(JText::translate('VBMAINORDERTITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
			JToolBarHelper::addNew('calendar', JText::translate('VBO_NEW_BOOKING'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
			JToolBarHelper::editList('editorder', JText::translate('VBMAINORDEREDIT'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.vbo.management', 'com_vikbooking')) {
			JToolBarHelper::custom( 'orders', 'file-2', 'file-2', JText::translate('VBOGENINVOICES'), true);
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.delete', 'com_vikbooking')) {
			JToolBarHelper::deleteList(JText::translate('VBDELCONFIRM'), 'removeorders', JText::translate('VBMAINORDERDEL'));
			JToolBarHelper::spacer();
			JToolBarHelper::spacer();
		}
	}

}

<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

$node = $this->currentNode;

?>

<a
	href="javascript:void(0)"
	class="folder"
	data-path="<?php echo $this->escape(base64_encode($node['path'])); ?>"
>
	<i class="fas fa-folder<?php echo $node['selected'] ? '-open' : ''; ?>"></i>
	<?php echo $node['name']; ?>
</a>

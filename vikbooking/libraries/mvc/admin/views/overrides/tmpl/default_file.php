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

if ($node['has'])
{
	$class = ' has-override';

	if (!$node['published'])
	{
		$class .= ' unpublished';
	}
}
else
{
	$class = '';
}

?>

<a
	href="javascript:void(0)"
	class="file"
	data-path="<?php echo $this->escape(base64_encode($node['path'])); ?>"
	data-override="<?php echo $this->escape(base64_encode($node['override'])); ?>"
>
	<i class="far fa-file-code<?php echo $this->escape($class); ?>"></i>
	<?php echo $node['name']; ?>
</a>

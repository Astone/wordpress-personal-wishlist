<?php
/**
 * The Template for displaying Wishlist Header
 *
 * Override this template by copying it to yourtheme/personal-wishlist/wishlist-header.php
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $item_list

?>
<?php if($item_list): ?>
<table>
	<thead>
		<tr>
			<th><?php _e('Item', 'personal-wishlist') ?></th>
			<!-- th><?php _e('Price (+/-)', 'personal-wishlist') ?></th -->
			<th><?php _e('Link', 'personal-wishlist') ?></th>
			<th align="center"><?php _e('Givers', 'personal-wishlist') ?></th>
			<th align="center"><?php _e('Action', 'personal-wishlist') ?></th>
		</tr>
	</thead>
	<tbody>
<?php endif ?>


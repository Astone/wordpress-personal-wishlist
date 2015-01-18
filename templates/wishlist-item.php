<?php
/**
 * The Template for displaying Wishlist Item
 *
 * Override this template by copying it to yourtheme/personal-wishlist/wishlist-item.php
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $item;

$site = $item->url;
$pattern = '/[.\/]([^.\/]+\.[^.\/]+)\//';
preg_match($pattern, $site, $matches);
if ($matches) $site = $matches[1];

?>
<tr>
	<td><?php echo $item->name ?></td>
	<!-- td style="text-align: right"><div style="float: left">&euro;</div><?php echo $item->price ?></td -->
	<td><?php if($site):?><a href="<?php echo $item->url ?>" name="<?php echo $item->name ?>" target="_blank"><?php echo $site ?></a><?php endif ?></td>
	<td align="center"><?php echo $item->givers ?></td>
	<td align="center">
	<?php if ($item->done): ?>
		<?php if ($item->is_giver): ?><a href="./?item_id=<?php echo $item->id?>&action=ungive"><?php _e('Don\'t give', 'personal-wishlist') ?></a>
		<?php else: ?><?php _e('Has been given', 'personal-wishlist') ?>
		<?php endif ?>
	<?php else: ?>
		<?php if ($item->is_giver): ?><a href="./?item_id=<?php echo $item->id?>&action=unjoin"><?php _e('Unjoin', 'personal-wishlist') ?></a>
		<?php else: ?><a href="./?item_id=<?php echo $item->id?>&action=join"><?php _e('Join', 'personal-wishlist') ?></a>
		<?php endif ?> |
		<a href="./?item_id=<?php echo $item->id?>&action=give"><?php _e('Give', 'personal-wishlist') ?></a>
	<?php endif ?>
	</td>
</tr>

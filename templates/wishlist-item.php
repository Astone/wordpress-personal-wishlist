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
	<?php if ($item->done >= 0) : ?>
	<td><?php echo $item->name ?></td>
	<!-- td style="text-align: right"><div style="float: left">&euro;</div><?php echo $item->price ?></td -->
	<td><?php if($site):?><a href="<?php echo $item->url ?>" target="_blank"><?php echo $site ?></a><?php endif ?></td>
        <?php else: ?>
	<td colspan="2"><?php echo $item->name ?></td>
	<?php endif ?>
	<td align="center"><?php echo $item->givers ?></td>
	<td align="center">
	<?php if ($item->done > 0): ?>
		<?php if ($item->is_giver): ?><a href="./?item_id=<?php echo $item->id?>&action=ungive"><?php _e('Don\'t give', 'personal-wishlist') ?></a>
		<?php else: ?><?php _e('Has been given', 'personal-wishlist') ?>
		<?php endif ?>
	<?php elseif ($item->done < 0): ?>
		<?php if ($item->is_giver): ?><a href="./?item_id=<?php echo $item->id?>&action=ungive"><?php _e('Don\'t give', 'personal-wishlist') ?></a>
		<?php else: ?><a href="./?item_id=<?php echo $item->id?>&action=give"><?php if ($item->givers > 0): ?><?php _e('Give too', 'personal-wishlist') ?><?php else: ?><? _e('Give', 'personal-wishlist') ?><?php endif ?></a>
		<?php endif ?>
	<?php else: ?>
		<?php if ($item->is_giver): ?><a href="./?item_id=<?php echo $item->id?>&action=unjoin"><?php _e('Unjoin', 'personal-wishlist') ?></a>
		<?php else: ?><a href="./?item_id=<?php echo $item->id?>&action=join"><?php _e('Join', 'personal-wishlist') ?></a>
		<?php endif ?> |
		<a href="./?item_id=<?php echo $item->id?>&action=give"><?php _e('Give', 'personal-wishlist') ?></a>
	<?php endif ?>
	</td>
</tr>

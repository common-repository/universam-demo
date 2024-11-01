<?php 
global $post;
$product_id = !empty($product_id)?$product_id:$post->ID;
$email = (string)usam_get_contact_metadata( usam_get_contact_id(), 'email' );
?>
<div class='product-subscription' v-cloak>
	<product-subscription :id="<?php echo $product_id ?>" :email="'<?php echo $email; ?>'" :subscription="<?php echo (int)usam_checks_product_from_customer_list('subscription', $product_id); ?>" inline-template>
		<span>
			<button class="button subscribed_product" v-if="status" @click="status=!status"><?php _e('Подписаны', 'usam'); ?></button>
			<button class="button" v-else @click="status=!status"><?php _e('Подписаться', 'usam'); ?></button>
		</span>
	</product-subscription>
</div>
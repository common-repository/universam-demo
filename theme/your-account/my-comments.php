<?php
// Описание: Мои комментарии

?>
<div class = 'profile__title'>
	<h1 class="title"><?php _e( 'Мои отзывы', 'usam'); ?></h1>
</div>
<?php 
$user_id = get_current_user_id();
$customer_reviews = new USAM_Customer_Reviews_Theme( );
echo $customer_reviews->output_reviews_show(['user_id' => $user_id]);
?>	

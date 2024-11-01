<?php
// Описание: Шаблон страницы лицензионные договоры
?>
<div class="page-agreements">
<?php
$agreements = (array)get_posts(['post_type' => 'usam-agreement', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC']);	

if ( !empty($agreements) )
{
	foreach ( $agreements as $agreement )
	{
		?>
		<a href="<?php echo get_permalink( $agreement->ID ); ?>" class="column4 agreement">	
			<img src="<?php echo usam_get_template_file_url( 'contract.png', 'images' ); ?>">
			<div class="agreement__text">
				<div class="agreement__date"><?php _e('Дата','usam') ?> <span class="date_agreement"><?php echo date( "d.m.Y", strtotime($agreement->post_date_gmt) ); ?></span></div>
				<div class="agreement__title"><?php echo $agreement->post_title; ?></div>
			</div>
		</a>
		<?php
	}
}
else
{
	_e('Нет лицензионных договоров','usam');
}
?>
</div>
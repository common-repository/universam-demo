<?php
// Описание: Шаблон страницы "Пункты выдачи"

global $wp_query; 
$location_id = 0;
if ( !empty($wp_query->query['id']) )
	$location_id = $wp_query->query['id'];
else
{
	$locations = usam_get_locations(['code' => 'city', 'orderby' => 'name', 'point_delivery' => true]);	
	if ( count($locations) == 1 )
		$location_id = $locations[0]->id;
}
if ( $location_id || empty($attributes['location']) )
{
	$args = ['issuing' => 1];
	if ( isset($attributes['owner']) )
		$args['owner'] = $attributes['owner'];
	if ( isset($attributes['type']) )
		$args['type'] = $attributes['type'];	
	if ( !empty($attributes['location']) )
		$args['location_id'] = $location_id;
	$storages = usam_get_storages( $args );	
	echo usam_get_map(['route' => 'points_delivery', 'location_id' => $location_id]);
	?>
	<div class="point_delivery">
		<?php
		foreach ( $storages as $storage )
		{
			$location = usam_get_location( $storage->location_id );
			$city = isset($location['name'])?__('г. ','usam').' '.htmlspecialchars($location['name']).", ":'';
			$phone = usam_get_storage_metadata( $storage->id, 'phone');
			$index = htmlspecialchars(usam_get_storage_metadata( $storage->id, 'index'));
			$index = $index?"{$index}, ":'';	
			$schedule = htmlspecialchars(usam_get_storage_metadata( $storage->id, 'schedule'));
			$address = htmlspecialchars(usam_get_storage_metadata( $storage->id, 'address'));
			$email = usam_get_storage_metadata( $storage->id, 'email');
			$email = $email?usam_encode_email($email):usam_get_shop_mail( );
			$phone = $phone?usam_get_phone_format($phone):usam_get_shop_phone();			
			?>
			<div class="store_list">
				<div class="store_list__name store_list__icon"><?php usam_svg_icon("marker"); ?><?php echo "{$index}{$city}{$address}"; ?></div>
				<div class="store_list__phone store_list__icon"><?php usam_svg_icon("phone", "phone_svg_icon"); ?><a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a></div>
				<div class="store_list__email store_list__icon"><?php usam_svg_icon("email", "mail_svg_icon"); ?><a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></div>			
				<div class="store_list__schedule_store">
					<div class="store_list__schedule_name store_list__icon"><?php usam_svg_icon("schedule", "schedule_svg_icon"); ?><?php _e("Режим работы","usam"); ?></div>		
					<div class="store_list__schedule"><?php echo $schedule; ?></div>	
				</div>				
			</div>
			<?php	
		}
		?>
	</div>
	<?php	
}
else
{	
	?>
	<div class="locations">
		<?php		
		foreach ( $locations as $location )
		{			
			?>
			<div class="locations__item">
				<a href="<?php echo usam_get_url_system_page('point-delivery').'/'.$location->id; ?>" class="locations__name"><?php echo htmlspecialchars($location->name); ?></a>				
			</div>
			<?php	
		}
		?>
	</div>
	<?php	
}
?>
<?php
require_once( USAM_FILE_PATH . '/admin/includes/grid_view.class.php' );
class USAM_All_Applications_Grid_View extends USAM_Grid_View
{	
	protected function class_grid() 
	{ 
		return 'applications_grid';
	}
		
	public function display_grid_items( ) 
	{  
		?>
		<div class="application" v-for="(item, i) in items">				
			<img v-if="item.icon" :src="item.icon" width = "100" height="100"/>
			<div class="application__content">
				<div class="application__content_name">{{item.name}}</div>
				<div class="application__content_description">{{item.description}}</div>
				<div class="application__content_footer">
					<a :href="item.url" class="button"><?php _e('Установить', 'usam') ?></a>
					<span class="application_price" v-if="item.price=='free'"><?php _e('Бесплатное', 'usam') ?></span>
					<span class="application_price" v-else><?php _e('Платное', 'usam') ?></span>
				</div>
			</div>
		</div>		
		<?php 
	}
}
?>
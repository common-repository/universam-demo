<?php
require_once( USAM_FILE_PATH . '/admin/includes/grid_view.class.php' );
class USAM_Installed_Applications_Grid_View extends USAM_Grid_View
{			
	protected function class_grid() 
	{ 
		return 'applications_grid';
	}
	
	public function display_grid_items( ) 
	{ 
		?>
		<a :href="item.url" class="application" v-for="(item, i) in items" :class="{'active':item.active}">
			<img v-if="item.icon" :src="item.icon" width = "100" height="100"/>
			<div class="application__content">
				<div class="application__content_name">{{item.name}}</div>
				<div class="application__content_description">{{item.description}}</div>			
			</div>
		</a>
		<?php 
	}
}
?>
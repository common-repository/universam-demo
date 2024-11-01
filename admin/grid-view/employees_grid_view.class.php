<?php
require_once( USAM_FILE_PATH . '/admin/includes/grid_view.class.php' );
require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
class USAM_Employees_Grid_View extends USAM_Grid_View
{	
	public function display_grid_items( ) 
	{  
		?>
		<div class="grid_column" v-for="(column, k) in columns">
			<div class="grid_column_header">
				<div class="grid_column_title">
					<div class="grid_column_title_text">
						<div class="status_column_title_text_inner">{{column.name}}</div>
					</div>
				</div>
				<div class="title_status_sum">
					<div class="sum_status_inner">{{column.count}}</div>			
				</div>
			</div>
			<div class="grid_items" @dragover="allowDrop" @drop="drop($event, k)">		
				<div v-if="item.department_id==column.id" class="grid_view__item" v-for="(item, i) in items" draggable='true' @dragstart="drag($event, i, k)" @dragend="dragEnd($event, i)" :class="{'grid_view__item_checked':item.checked}">
					<div class="grid_view__item_wrapper" @click="checked(i,$event)">
						<div class="grid_item__row grid_item__employee_name">					
							<div class="grid_item_row__online" v-if="item.online"></div>
							<img class ='grid_item_row__foto' :src='item.foto'>
							<div class="grid_item_row__employee_name">
								<div class="grid_item_row__appeal">
									<a :href="'<?php echo admin_url("admin.php?page=personnel&tab=employees&form=view&form_name=employee&id=") ?>'+item.id">{{item.appeal}}</a>
								</div>
								<div class="grid_item_row__email">{{item.email}}</div>
								<div v-if="!item.checked" class="grid_item_row__phone">{{item.mobilephone}}</div>
								<div v-else class="grid_view_checkbox"></div>
							</div>
						</div>	
						<div v-if="item.post" class="grid_item__row grid_item_row__post">{{item.post}}</div>		
					</div>
				</div>
			</div>
		</div>	
		<?php 
	}
}
?>
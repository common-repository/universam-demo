<?php
require_once( USAM_FILE_PATH . '/admin/includes/grid_view.class.php' );
class USAM_delivery_documents_Grid_View extends USAM_Grid_View
{				
	public function display_grid_items( ) 
	{ 		
		?>
		<div class="grid_column" v-for="(column, k) in columns">
			<div class="grid_column_header">
				<div class="grid_column_title">
					<div class="grid_column_title_text">
						<div class="status_column_title_text_inner">
							<div class="delivery_document_courier">
								<a :href="'<?php echo admin_url("admin.php?page=delivery&form=view&form_name=employee&id="); ?>'+column.id" v-html="column.appeal"></a>
								<div class="customer_online" v-if="column.online"></div>
							</div>								
						</div>
						<div class='item_status' :style="'background:'+column.status_color" v-html="column.status_name"></div>	
					</div>
				</div>
				<div class="title_status_sum">
					<div class="sum_status_inner">{{column.formatted_sum}}</div>		
				</div>
			</div>			
			<div class="grid_items" @dragover="allowDrop" @drop="drop($event, k)">	
				<div class="grid_view__item" v-if="item.courier==column.user_id" v-for="(item, i) in items" draggable='true' @dragstart="drag($event, i, k)" @dragend="dragEnd($event, i)" :class="{'grid_view__item_checked':item.checked}">
					<div class="grid_view__item_wrapper" @click="checked(i,$event)">
						<div class="grid_item__row grid_item__title">
							<a :href="'<?php echo admin_url("admin.php?page=delivery&form=edit&form_name=shipped&id=") ?>'+item.id" class="document_id">№ {{item.number}}</a>
							<a v-if="!item.checked" :href="'<?php echo admin_url("admin.php?page=delivery&form=edit&form_name=shipped&id=") ?>'+item.id" class="document_totalprice" v-html="item.totalprice_currency"></a>
							<div v-else class="grid_view_checkbox"></div>
						</div>
						<div class="grid_item__row" v-if="item.order_number">
							<a :href="'<?php echo admin_url("admin.php?page=delivery&form=edit&form_name=order&id=") ?>'+item.order_id" class="order_number"><?php _e('Заказ', 'usam') ?> № {{item.order_number}}</a>
						</div>						
						<div class="grid_item__row">
							<div class='item_status' :style="'background:'+item.status_color" v-html="item.status_name"></div>
						</div>
						<div class="grid_item__row" v-if="item.delivery_contact" v-html="item.delivery_contact._name"></div>
						<div class="grid_item__row" v-if="item.delivery_address" v-html="item.delivery_address._name"></div>
						<div class="grid_item__row grid_item__date_delivery" v-if="item.date_delivery"><label><?php _e('Время доставки', 'usam') ?>:</label> <span class="delivery_date">{{localDate(item.date_delivery,'d.m.Y')}}</span><span class="delivery_time">{{localDate(item.date_delivery,'H:i')}}</span></div>
						<div class="grid_item__row" v-if="item.note">	
							<label><?php esc_html_e( 'Указания курьеру', 'usam'); ?>:</label>
						</div>
						<div class="grid_item__row user_comment" v-if="item.note" v-html="item.note"></div>
						<div class="grid_item__row grid_item__problem" v-if="item.problem" v-html="item.problem"></div>
					</div>		
				</div>
			</div>	
		</div>
		<div class="grid_status_close" :class="{'draggable':draggable}">	
			<div class="grid_status_close__statuses">
				<div v-if="column.close" v-for="(column, k) in statuses" @dragover="allowDrop" @drop="dropStatusDelivery($event, k)" class="grid_status_close__status_title drop_area" v-html="column.name"></div>
			</div>					
		</div>
		<?php 	
	}
}
?>
<?php
require_once( USAM_FILE_PATH . '/admin/includes/grid_view.class.php' );
class USAM_Orders_Grid_View extends USAM_Grid_View
{			
	protected function counter_panel() 
	{ 
		printf( __('Всего: %s заказов на сумму %s', 'usam'), '<span class="counter_panel__total_items">{{items.length}}</span>', '<span class="counter_panel__total_price" v-html="formatted_sum"></span>' );
	}
			
	public function display_grid_items( ) 
	{ 		
		$possibility_to_call = apply_filters( 'usam_possibility_to_call', false );
		?>								
		<div v-if="(column.visibility || column.number > 0) && !column.close" class="grid_column drop_area" v-for="(column, k) in columns">
			<div class="grid_column_header">
				<div class="grid_column_title" :style="{backgroundColor:column.color}">
					<div class="grid_column_title_text">
						<div class="status_column_title_text_inner">{{column.name}}</div>
					</div>
				</div>
				<div class="title_status_sum">
					<div class="sum_status_inner">{{column.formatted_sum}}</div>			
				</div>
			</div>
			<div class="grid_items" @dragover="allowDrop" @drop="drop($event, k)">		
				<div class="grid_view__item" v-if="item.status==column.internalname" v-for="(item, i) in items" draggable='true' @dragstart="drag($event, i, k)" @dragend="dragEnd($event, i)" :class="{'grid_view__item_checked':item.checked}">
					<div class="grid_view__item_wrapper" @click="checked(i,$event)">
						<div class="grid_item__row grid_item__title">
							<a :href="'<?php echo admin_url("admin.php?page=orders&form=view&form_name=order&id=") ?>'+item.id" class="document_id" :class="{'item_status_valid item_status':item.paid}">№ {{item.number}}</a>
							<a v-if="!item.checked" :href="'<?php echo admin_url("admin.php?page=orders&form=view&form_name=order&id=") ?>'+item.id" class="document_totalprice" v-html="item.totalprice_currency"></a>
							<div v-else class="grid_view_checkbox"></div>
						</div>
						<div class="grid_item__row order_date">
							<a :href="'<?php echo admin_url("admin.php?page=orders&tab=orders&form=view&form_name=order&id=") ?>'+item.id">{{localDate(item.date_insert,'<?php echo get_option('date_format', 'Y/m/j') ?>')}}</a>
							<span v-if="item.paid==2" class="item_status_valid item_status" title="<?php _e("Заказ полностью оплачен","usam"); ?>">{{localDate(item.date_paid,'<?php echo get_option('date_format', 'Y/m/j') ?>')}}</span>
							<span v-else-if="item.paid==1" class="item_status_valid item_status"><?php _e("Частично оплачен","usam"); ?></span>
						</div>	
						<div class="grid_item__row grid_item__row_customer" v-if="typeof item.properties.billingfirstname !== typeof undefined">
							<a v-if="item.contact_id" :href="'<?php echo admin_url("admin.php?page=orders&tab=orders&form=view&form_name=contact&id=") ?>'+item.contact_id">
								<span v-html="item.properties.billingfirstname.value + ' ' +item.properties.billinglastname.value"></span>
							</a>
							<span v-else v-html="item.properties.billingfirstname.value + ' ' +item.properties.billinglastname.value"></span>
						</div>
						<div class="grid_item__row grid_item__row_customer" v-if="typeof item.properties.company !== typeof undefined">	
							<a v-if="item.company_id" :href="'<?php echo admin_url("admin.php?page=orders&tab=orders&form=view&form_name=company&id=") ?>'+item.company_id" v-html="item.properties.company.value"></a>
						</div>						
						<div class="grid_item__row communication_icon">
							<a v-if="Object.keys(item.emails).length" class="dashicons dashicons-email-alt" @click="openEmail(i)" title="<?php _e("Отправить письмо","usam"); ?>"></a>
							<?php
							if( $possibility_to_call )
							{
								?><a v-if="property.field_type=='mobile_phone'" v-for="(property, k) in item.properties" class="dashicons dashicons-phone" @click="call(property, i)" class="{'active_icon':property.value}" title="<?php _e("Позвонить","usam"); ?>"></a>			<?php		
							}
							?>								
							<a v-if="Object.keys(item.phones).length" class="dashicons dashicons-email-alt2" @click="openSMS(i)" title="<?php _e("Отправить СМС","usam"); ?>"></a>
						</div>																						
						<div class="grid_item__row order_manager" v-if="item.manager_id">
							<div class="grid_item__row_name"><?php _e("Ответственный","usam"); ?>:</div><div class="grid_item__row_option" v-html="item.manager.appeal"></div>
						</div>						
					</div>	
					<div class='last_comment' v-if="item.last_comment">
						<div class='user_block user_comment'>								
							<div class='user_block__content'>
								<div class='user_comment__user'>
									<span class='user_block__user_name' v-html="item.last_comment_user_name"></span>
									<span class='user_comment__date' v-html="item.display_last_comment_date"></span>
								</div>
								<div class='user_comment__message' v-html="item.last_comment"></div>
							</div>
						</div>
					</div>					
				</div>
			</div>	
		</div>
		<div class="grid_status_close" :class="{'draggable':draggable}">	
			<div class="grid_status_close__statuses">
				<div v-if="column.close" v-for="(column, k) in columns" @dragover="allowDrop" @drop="drop($event, k)" class="grid_status_close__status_title drop_area" v-html="column.name"></div>
			</div>					
		</div>
		<teleport to="body">			
			<?php include( usam_get_filepath_admin('templates/template-parts/modal-panel/modal-panel-send_email.php') ); ?>	
			<?php include( usam_get_filepath_admin('templates/template-parts/modal-panel/modal-panel-send_sms.php') ); ?>
			<?php
			if( $possibility_to_call )
			{
				?><phone-call :phone="phone" :object_id="object_id" :object_type="'order'"></phone-call><?php		
			}
			?>	
		</teleport>
		<?php 	
	}
}
?>
<div class="edit_form">
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php _e('Дата', 'usam'); ?>:</div>
		<div class ="edit_form__item_option" v-if="edit">
			<datetime-picker v-model="data.date_insert"/>
		</div>
		<div class ="edit_form__item_option" v-else>{{localDate(data.date_insert,'<?php echo get_option('date_format', 'Y/m/j'); ?>')}}</div>
	</div>	
	<?php
	if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
	{
		?>	
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e( 'Продавец', 'usam'); ?></div>
			<div class ="edit_form__item_option" v-if="Object.entries(data.seller).length">
				<a :href="'<?php echo admin_url("admin.php?page=crm&tab=companies&table=companies&form=edit&form_name=company&id="); ?>'+data.seller.id" target="_blank" v-html="data.seller.name"></a>
			</div>
		</div>
		<?php
	}
	?>
	<div class ="edit_form__item" v-if="data.order_id">
		<div class ="edit_form__item_name"><?php esc_html_e( 'Способ доставки', 'usam'); ?>:</div>
		<div class ="edit_form__item_option">
			<select v-model="data.method" v-if="edit" @change="data.storage_pickup=0">
				<option :value="service.id" v-for="service in delivery" :class="{'delivery_services_disabled':service.disabled}" v-html="service.name"></option>
			</select>	
			<span v-else v-html="data.name"></span>
		</div>
	</div>
	<div class ="edit_form__item" v-if="data.order_id && storagePickup.delivery_option==1">	
		<div class ="edit_form__item_name"><?php _e( 'Офис получения', 'usam'); ?>:</div>
		<div class ="edit_form__item_option" v-if="edit">
			<div class="object change_object" v-if="data.storage_pickup>0" @click="sidebar('storages',{'code':'storage_pickup', id:data.id, owner:storagePickup.handler})">
				<div class="object_title" v-html="data.storage_pickup_data.title"></div>
				<div class="object_description" v-html="data.storage_pickup_data.city+' '+data.storage_pickup_data.address"></div>		
			</div>			
			<a v-else @click="sidebar('storages',{'code':'storage_pickup', id:data.id, owner:storagePickup.handler})"><?php esc_html_e( 'Выбрать склад', 'usam'); ?></a>	
		</div>
		<div class ="edit_form__item_option" v-else-if="data.storage_pickup>0">
			<div class='crm_customer'>
				<span v-html="data.storage_pickup_data.title" @click="sidebar('storages',{'code':'storage_pickup', id:data.id, owner:storagePickup.handler})"></span>
				<div class='crm_customer__info'>
					<div class='crm_customer__info_rows'>
						<div class='crm_customer__info_row'>
							<div class="crm_customer__info_row_name"><?php _e("Код","usam"); ?>:</div>
							<div class="crm_customer__info_row_option" v-html="data.storage_pickup_data.code"></div>
						</div>
						<div class='crm_customer__info_row'>
							<div class="crm_customer__info_row_name"><?php _e("Адрес","usam"); ?>:</div>
							<div class="crm_customer__info_row_option" v-html="data.storage_pickup_data.address"></div>
						</div>
						<div class='crm_customer__info_row'>
							<div class="crm_customer__info_row_name"><?php _e("т.","usam"); ?>:</div>
							<div class="crm_customer__info_row_option">{{data.storage_pickup_data.phone_format}}</div>
						</div>
						<div class='crm_customer__info_row'>
							<div class="crm_customer__info_row_name"><?php _e("График работы","usam"); ?>:</div>
							<div class="crm_customer__info_row_option">{{data.storage_pickup_data.schedule}}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>					
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_html_e( 'Склад списания', 'usam'); ?>:</div>
		<div class ="edit_form__item_option" v-if="edit">
			<select v-model='data.storage'>
				<option value="0"><?php _e( 'Не выбрано', 'usam'); //v-if="storage.shipping || storage.issuing" ?></option>
				<option :value="storage.id" v-for="storage in storages" v-html="storage.title"></option>
			</select>	
		</div>
		<div class ="edit_form__item_option" v-else-if="typeof storages[data.storage] !== typeof undefined">
			<div class='crm_customer'>
				<a :href="'<?php echo admin_url("admin.php?page=storage&tab=storage&table=storage&form=edit&form_name=storage"); ?>&id='+data.storage" v-html="storages[data.storage].title"></a>
				<div class='crm_customer__info'>
					<div class='crm_customer__info_rows'>
						<div class='crm_customer__info_row'>
							<div class="crm_customer__info_row_name"><?php _e("Код","usam"); ?>:</div>
							<div class="crm_customer__info_row_option" v-html="storages[data.storage].code"></div>
						</div>
						<div class='crm_customer__info_row'>
							<div class="crm_customer__info_row_name"><?php _e("Адрес","usam"); ?>:</div>
							<div class="crm_customer__info_row_option" v-html="storages[data.storage].address"></div>
						</div>
						<div class='crm_customer__info_row'>
							<div class="crm_customer__info_row_name"><?php _e("т.","usam"); ?>:</div>
							<div class="crm_customer__info_row_option">{{storages[data.storage].phone_format}}</div>
						</div>
						<div class='crm_customer__info_row'>
							<div class="crm_customer__info_row_name"><?php _e("График работы","usam"); ?>:</div>
							<div class="crm_customer__info_row_option">{{storages[data.storage].schedule}}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>	
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_html_e( 'Стоимость доставки', 'usam'); ?>:</div>
		<div class ="edit_form__item_option">
			<input size = "10" maxlength = "10" type="text" v-model="data.price" v-if="edit">	
			<span v-else v-html="data.price"></span>						
		</div>
	</div>
	<div class ="edit_form__item" v-if="data.tax_id">
		<div class ="edit_form__item_name" v-html="data.tax_name+':'"></div>
		<div class ="edit_form__item_option" v-html="data.tax_value"></div>
	</div>								
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_html_e( 'Включить в стоимость заказа', 'usam'); ?>:</div>
		<div class ="edit_form__item_option">	
			<span v-if="edit">
				<input type="radio" value="0" v-model="data.include_in_cost"><?php _e('Нет', 'usam'); ?>&nbsp;
				<input type="radio" value="1" v-model="data.include_in_cost"><?php _e('Да', 'usam'); ?>
			</span>
			<span v-else-if="data.include_in_cost"><?php _e('Да', 'usam'); ?></span>	
			<span v-else><?php _e('Нет', 'usam'); ?></span>	
		</div>
	</div>
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><?php esc_html_e( 'Статус', 'usam'); ?>:</div>
		<div class ="edit_form__item_option" v-if="edit">
			<select v-model='data.status'>
				<option v-for="status in statuses" v-if="status.type=='shipped' && (status.internalname == data.status || status.visibility)" :value='status.internalname' :style="status.color?'background:'+status.color+';':''+status.text_color?'color:'+status.text_color+';':''" v-html="status.name"></option>
			</select>
		</div>
		<div class ="edit_form__item_option" v-else>
			<div class='item_status' :style="statusStyle(data, 'shipped')" v-html="statusName(data, 'shipped')"></div>
		</div>					
	</div>
	<div class ="edit_form__item" v-if="data.readiness_date || edit">
		<div class ="edit_form__item_name"><?php _e('Дата сборки', 'usam'); ?>:</div>
		<div class ="edit_form__item_option" v-if="edit">
			<v-date-picker v-model="data.readiness_date" :input-debounce="800" :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
				<template v-slot="{ inputValue, inputEvents }"><input type="text" :value="inputValue" v-on="inputEvents"/></template>
			</v-date-picker>
		</div>
		<div class ="edit_form__item_option" v-else>{{localDate(data.readiness_date,'<?php echo get_option('date_format', 'Y/m/j'); ?>')}}</div>
	</div>	
</div>
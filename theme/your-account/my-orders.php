<?php
// Описание: Заказы
?>
<div v-if="tab=='list'">		
	<div class = 'profile__title'>				
		<h1 class="title"><?php _e('Заказы', 'usam'); ?></h1>
	</div>
	<?php 
	usam_include_template_file( "orders_interface_filters.class", 'interface-filters' );
	$interface_filters = new Orders_Interface_Filters();	
	$interface_filters->display(); 
	?>
	<?php usam_include_template_file( 'orders_lists', 'lists' ); ?>
</div>
<div class = 'order_details' v-else-if="tab=='order' && loaded">
	<?php usam_include_template_file('order', 'your-account'); ?>
</div>
<div class = '' v-else-if="tab=='cancel_order'">	
	<?php
	// Описание: Отменить заказ
	?>
	<div class = 'profile__title'>
		<button @click="key=null" class="button go_back"><?php usam_svg_icon("angle-down-solid")?><span class="go_back_name"><?php _e('Назад', 'usam'); ?></span></button>
		<h1 class="title"><?php _e('Отменить заказ','usam'); ?> <span class ='order_id'>№ {{order.number}}</span></h1>
	</div>				
	<div class ="view_form">
		<div class ='view_form__item order_status_name'>	
			<div class ='view_form__name'><?php _e( 'Дата заказа', 'usam'); ?>:</div>	
			<div class ='view_form__option'>{{localDate(order.date_insert,'d.m.Y')}}</div>	
		</div>
		<div class ='view_form__item order_status_name'>	
			<div class ='view_form__name'><?php _e( 'Статус заказа', 'usam'); ?>:</div>
			<div class ='view_form__option'><span class='item_status order_status_name' :style="'background:'+order.status_color+';color:'+order.status_text_color" v-html="order.status_name"></span></div>	
		</div>
		<div class ='view_form__item order_status_name' v-if="order.shipping_documents.length == 1">
			<div class ='view_form__name'><?php _e( 'Способ получения', 'usam'); ?>:</div>
			<div class ='view_form__option' v-html="order.shipping_documents[0].name"></div>
		</div>
		<div class ='view_form__item order_status_name' v-if="order.shipping_documents.length == 1 && order.shipping_documents[0].storage_pickup>0">
			<div class ='view_form__name'><?php _e( 'Офис получения', 'usam'); ?>:</div>
			<div class ='view_form__option'><span v-html="order.shipping_documents[0].pickup.city+' '+order.shipping_documents[0].pickup.address"></span> <span><?php _e( 'т.', 'usam'); ?> {{order.shipping_documents[0].pickup.phone}}</span></div>
		</div>
		<div class ='view_form__item order_status_name' v-if="order.paid==2 || order.paid==0">	
			<div class ='view_form__name'><?php _e( 'Статус оплаты', 'usam'); ?>:</div>
			<div class ='view_form__option'>
				<span class="item_status order_footer__payment_status item_status_valid" v-if="order.paid==2">{{order.payment_status}}</span>
				<span class="item_status order_footer__payment_status item_status_attention" v-else-if="order.paid==0">{{order.payment_status}}</span>
			</div>	
		</div>
		<div class ='view_form__item order_status_name' v-if="order.paid && order.date_paid">	
			<div class ='view_form__name'><?php _e( 'Дата оплаты', 'usam'); ?>:</div>
			<div class ='view_form__option'>
				<span class="order_footer__payment_date">{{localDate(order.date_paid,'d.m.Y')}}</span>
			</div>	
		</div>	
		<div class ='view_form__item order_status_name' v-if="!order.status_is_completed">	
			<div class ='view_form__name'><?php _e( 'Причина отмены', 'usam'); ?>:</div>
			<div class ='view_form__option'>
				<textarea id = "cancellation_reason" cols="35" maxlength="9000" rows="3" class="typearea" v-model="order.cancellation_reason" placeholder="<?php _e( 'Напишите причину отмены...', 'usam'); ?>"></textarea>
			</div>	
		</div>	
		<div class ="view_form__buttons">
			<input type="submit" class="button main-button" @click="cancelOrder" value="<?php _e( 'Отменить', 'usam'); ?>">
		</div>		
	</div>	
	<div class ="usam_message" v-if="order.status=='canceled'">	
		<?php _e( 'Заказ отменен', 'usam'); ?>
	</div>
</div>
<?php usam_include_template_file('loading', 'template-parts'); ?>	
<div class="view_form payment_block" v-if="basket.payment_methods.length>0">		
	<div class ="view_form__title"><?php _e('Способ оплаты', 'usam'); usam_change_block( admin_url( "admin.php?page=orders&tab=orders&view=table&table=payment_gateway" ), __("Добавить или изменить способы оплаты", "usam") ); ?></div>
	<div class ="gateways_form">							
		<div class='gateways_form__gateway' v-for="(value, k) in basket.payment_methods" :class="[value.id==selected.payment?'selected_method':'']">		
			<div class="gateways_form__radio"><input class="option-input radio" type='radio' v-model="selected.payment" :value="value.id"/></div>
			<div class="gateways_form__info">
				<div class="gateways_form__name" v-html="value.name" @click="selected.payment=value.id"></div>								
				<div class="gateways_form__description" v-html="value.description" @click="selected.payment=value.id"></div>	
			</div>	
			<div class="gateways_form__gateway_logo" v-if="value.image!=''" :style="'background-image:url('+value.image+');'"></div>
		</div>			
	</div>	
</div>	
<div class="shipping_block" v-if="basket.payment_methods.length==0">		
	<?php _e('Нет доступного способа оплаты', 'usam'); ?>
</div>
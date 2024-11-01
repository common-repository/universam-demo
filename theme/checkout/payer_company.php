<?php
//Выбор компании при оформлении заказа если добавлена в личном кабинете
?>		
<div class="view_form company_block" v-if="companies.length>0 && is_type_payer_company()">
	<div class ="view_form__title"><?php _e('Компании', 'usam'); ?></div>
	<div class ="view_form__row" v-for="(value, k) in companies">	
		<label><input class="option-input radio" type='radio' v-model="selected.company" :value="value.id"/>{{value.name}}</label>
	</div>
</div>	
<div class="" else>
	<?php echo apply_filters( 'usam_message_ordering_no_client_companies', '' ); ?>
</div>	
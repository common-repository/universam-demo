<div class = 'profile__title'>
	<h1 class="title"><?php _e( 'Партнерская программа', 'usam'); ?></h1>	
</div>
<div class ="view_form">	
	<div class ='view_form__item' v-if="coupon.url!=undefined">
		<div class ='view_form__name'><?php _e( 'Партнерская ссылка', 'usam'); ?>:</div>	
		<div class ='view_form__option' @click="clipboard"><a v-html="coupon.url"></a></div>		
	</div>		
	<div class ='view_form__item' v-if="referral.url!=undefined">
		<div class ='view_form__name'><?php _e( 'Партнерская ссылка', 'usam'); ?>:</div>	
		<div class ='view_form__option' @click="clipboard" v-html="referral.url"></div>		
	</div>		
</div>
<div class ="view_form referral_statistics" v-if="referral.url!=undefined">	
	<div class ='view_form__title'><?php _e( 'Общая информация', 'usam'); ?>:</div>			
	<div class ='view_form__item'>
		<div class ='view_form__name'><?php _e( 'Партнерская код', 'usam'); ?>:</div>	
		<div class ='view_form__option' v-html="referral.id"></div>		
	</div>
	<div class ='view_form__item'>
		<div class ='view_form__name'><?php _e( 'Сумма зачисления', 'usam'); ?>:</div>	
		<div class ='view_form__option'>{{referral.amount}}</div>
	</div>
	<div class ='view_form__title'><?php _e( 'Статистика', 'usam'); ?>:</div>	
	<div class ='view_form__item'>
		<div class ='view_form__name'><?php _e( 'Количество рефералов', 'usam'); ?>:</div>	
		<div class ='view_form__option'>{{referral.contacts}}</div>
	</div>
	<div class ='view_form__item'>
		<div class ='view_form__name'><?php _e( 'Последние зачисление', 'usam'); ?>:</div>	
		<div class ='view_form__option'>{{referral.last_payout}}</div>
	</div>
	<div class ='view_form__item'>
		<div class ='view_form__name'><?php _e( 'Сумма последнего зачисления', 'usam'); ?>:</div>	
		<div class ='view_form__option'>{{referral.last_amount}}</div>
	</div>		
</div>
<?php usam_include_template_file('loading', 'template-parts'); ?>
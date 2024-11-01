<div id='modal-product-subscription' class="modal_product_subscription" v-cloak>
	<modal-window ref="productSubscription" :backdrop="false">
		<template v-slot:title><?php _e('Создание уведомления', 'usam'); ?></template>
		<template v-slot:body>
			<div class="usam_message message_success" v-if="info&&subscription"><?php _e('Вы подписаны на товар', 'usam'); ?></div>
			<div class="view_form" v-if="!setting">
				<div class="view_form__item">
					<div class ="view_form__name"><?php _e( 'Электронная почта', 'usam') ?></div>
					<div class ="view_form__option">
						<input type='text' class="option-input" v-model="email">
					</div>
				</div>					
			</div>
			<div class="view_form" v-else>				
				<div class="view_form__item view_form__item_section">				
					<strong><?php _e( 'Выберите способ уведомления', 'usam') ?></strong>
				</div>
				<div class="view_form__item">
					<label><input type='checkbox' class="option-input" v-model="notifications_email"><?php _e( 'Электронная почта', 'usam') ?></label>
				</div>				
			</div>			
		</template>
		<template v-slot:buttons>
			<button class="button main-button" v-if="setting" @click="saveNotifications"><?php _e('Сохранить', 'usam'); ?></button>
			<button class="button main-button" v-else @click="saveContact"><?php _e('Сохранить', 'usam'); ?></button>			
		</template>
	</modal-window>
</div>
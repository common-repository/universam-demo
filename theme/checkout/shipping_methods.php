<?php //Выбор способа доставки ?>
<div class="view_form shipping_block" v-if="basket.shipping_methods.length>0">					
	<div class ="view_form__title"><?php _e('Способ доставки', 'usam'); usam_change_block( admin_url("admin.php?page=orders&tab=orders&view=settings&table=shipping"), __("Добавить или изменить способ доставки", "usam") ); ?></div>					
	<div class ="gateways_form">
		<div class='gateways_form__gateway' v-for="(value, k) in basket.shipping_methods" :class="[value.id==selected.shipping?'selected_method':'']">	
			<div class="gateways_form__radio"><input class="option-input radio" type='radio' v-model="selected.shipping" :value="value.id"/></div>
			<div class="gateways_form__info">
				<div class="gateways_form__name">
					<span v-html="value.name" @click="selected.shipping=value.id"></span>
					<a class = "select_pickup_button" v-if="value.delivery_option && value.id==selected.shipping && value.storages.length>1" @click="select_pickup"><?php _e('изменить пункт выдачи', 'usam'); ?></a>					
				</div>
				<div class="gateways_form__description" v-html="value.description" @click="selected.shipping=value.id"></div>	
				<div class="point_receipt" v-if="value.id==selected.shipping && value.delivery_option" @click="select_pickup">
					<?php _e('Выбран пункт выдачи', 'usam'); ?>: <span class="gateways_form__select_storage_address">{{basket.selected_storage_address}}</span><span class ='validation-error' v-if="basket.selected_storage_address==''"><?php _e('не выбран', 'usam'); ?></span>
				</div>
				<div class="gateways_form__price" v-html="value.info_price"></div>			
				<div class="gateways_form__delivery_period" v-if="value.delivery_period!=''">
					<span class="gateways_form__option_name"><?php _e('Срок', 'usam');?>:</span>
					<span class="gateways_form__option_value" v-html="value.delivery_period"></span>
				</div>									
			</div>
			<div class="gateways_form__gateway_logo" v-if="value.image!=''" :style="'background-image:url('+value.image+');'"></div>
		</div>
	</div>
	<teleport to="body">						
		<modal-window :ref="'modalpickup'" :backdrop="true">
			<template v-slot:title><?php printf(__('Пункты выдачи заказа в городе %s', 'usam'), '<span class="city">{{customer.location.name}}</span>'); ?></template>
			<template v-slot:body>
				<store-lists :args="pointsArgs" @change="selectedPickup" inline-template>
					<div class="stores_viewing">
						<div class="stores_viewing__tabs modal__panel">
							<div class="stores_viewing__tab" @click="tab='list'" :class="{active: tab=='list'}"><?php _e("Список","usam"); ?></div>
							<div class="stores_viewing__tab" @click="tab='map'" :class="{active: tab=='map'}"><?php _e("Карта","usam"); ?></div>
						</div>
						<div class="store_lists_selected" v-show="tab=='list'||screen.width>=1024">	
							<input type="text" class="store_lists_search option-input" v-on:keyup="search_enter" placeholder="<?php _e("Поиск по названию","usam"); ?>" value ="" v-show="display_search"/>
							<div class="screen_loading" v-if="isLoading">
								<div class="screen_loading__post">
									<div class="screen_loading__line"></div>
									<div class="screen_loading__line"></div>
									<div class="screen_loading__line"></div>
								</div>
								<div class="screen_loading__post">
									<div class="screen_loading__line"></div>
									<div class="screen_loading__line"></div>
									<div class="screen_loading__line"></div>
								</div>		
								<div class="screen_loading__post">
									<div class="screen_loading__line"></div>
									<div class="screen_loading__line"></div>
									<div class="screen_loading__line"></div>
								</div>
							</div>	
							<div ref="lists" class="store_lists" v-show="isLoading==false">
								<div class="store_lists__row js-select-store" :class="[selected===k?'selected':'']" v-for="(storage, k) in storages" @click="click_pickup(k)" >
									<div class="store_list">
										<div class="store_list__name" v-html="storage.title"></div>
										<div class="store_list__schedule">{{storage.schedule}}</div>						
										<div class="store_list__footer"><span class="store_list__phone">{{storage.phone}}</span></div>	
									</div>
									<div class="store_list__actions">
										<div class="store_list__in_stock" :class="[storage.available?'usam_product_in_stock':'usam_product_not_available']">{{storage.delivery_period}}</div>
										<button class="button" @click="close_pickup_points"><?php _e("Выбрать","usam"); ?></button>
									</div>
								</div>
								<div id="load-store-list"></div>
							</div>
						</div>
						<div class="stores_viewing__map" v-show="tab=='map'||screen.width>=1024">
							<div ref="map" class="stores_map"></div>
						</div>
					</div>	
				</store-lists>
			</template>		
			<template v-slot:footer></template>							
		</modal-window>						
	</teleport>
</div>
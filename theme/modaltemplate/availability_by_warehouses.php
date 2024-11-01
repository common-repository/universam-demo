<?php
$location_id = usam_get_customer_location( );
$location = usam_get_location( $location_id );
if ( $location )
	$title = sprintf(__('Наличие товара в городе %s', 'usam'), $location['name']);
else
	$title = __('Наличие товара', 'usam');
?>
<div id="availability_by_warehouses" class="modal fade modal-large modal_store_lists">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php echo $title; ?></div>
	</div>
	<div id="availability_by_warehouses_vue">
	<store-lists :args="{}" inline-template>
		<div class="stores_viewing" data-resize="0">		
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
					<div class="store_lists__row js-select-store" :class="[selected===k?'selected':'']" v-for="(storage, k) in storages" @click="click_pickup(k)">
						<div class="store_list">
							<div class="store_list__name" v-html="storage.title"></div>
							<div class="store_list__phone">{{storage.phone}}</div>
							<div class="store_list__schedule">{{storage.schedule}}</div>					
						</div>
						<div class="store_list__in_stock" :class="[storage.available?'usam_product_in_stock':'usam_product_not_available']">{{storage.stock}}</div>
					</div>
					<div id="load-store-list"></div>
				</div>
			</div>	
			<div class="stores_viewing__map" v-show="tab=='map'||screen.width>=1024">
				<div ref="map" class="stores_map"></div>
			</div>
		</div>
	</store-lists>
	</div>
</div>
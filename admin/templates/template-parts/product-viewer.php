<div id="product_viewer" class="media_viewer" :class="{'is-active':open}">	
	<div class="media_viewer__topbar">
		<div class="media_viewer__topbar_title" v-html="product.post_title"></div>
		<div class="media_viewer__topbar_buttons">
			<?php usam_system_svg_icon("fullscreen", ["@click" => "fullScreenChange", "v-if" => "!fullScreen"]); ?>
			<?php usam_system_svg_icon("fullscreen-exit", ["@click" => "fullScreenChange", "v-if" => "fullScreen"]); ?>
			<?php usam_system_svg_icon("close", ["class" => "media_viewer__topbar_buttons_close", "@click" => "open=!open"]); ?>	
		</div>		
	</div>
	<div class="media_viewer__tabs">
		<div class="media_viewer__tab" @click="tab='images'" :class="{'active':tab=='images'}" v-if="images.length"><?php _e("Фото", "usam"); ?></div>
		<div class="media_viewer__tab" @click="tab='attribute'" :class="{'active':tab=='attribute'}"><?php _e("Характеристики", "usam"); ?></div>
		<div class="media_viewer__tab hide" @click="tab='prices'" :class="{'active':tab=='prices'}"><?php _e("Цены", "usam"); ?></div>
		<div class="media_viewer__tab" @click="tab='stock'" :class="{'active':tab=='stock'}"><?php _e("Остатки", "usam"); ?></div>
		<div class="media_viewer__tab" @click="tab='counters'" :class="{'active':tab=='counters'}"><?php _e("Счетчики", "usam"); ?></div>
	</div>
	<div class="media_viewer__content media_viewer__images" v-if="tab=='images'">
		<site-slider :items='images' :number='image_key' :amount="1" :classes="'media_viewer__image'">
			<template v-slot:body="sliderProps">
				<div class="media_viewer__nav media_viewer__nav_left" @click="sliderProps.prev" v-if="sliderProps.n!=0"><?php usam_system_svg_icon("chevron_left"); ?></div>
				<div class="media_viewer__nav media_viewer__nav_right" @click="sliderProps.next" v-if="sliderProps.n!=sliderProps.items.length-1"><?php usam_system_svg_icon("chevron_right"); ?></div>
				<div class='media_viewer__image_zoom' v-for="(image, i) in sliderProps.items" v-if="sliderProps.n==i" @click="sliderProps.changeZoom">
					<image-zoom :img-normal="image.full" :disabled="!sliderProps.zoom" @change="sliderProps.enable=!$event"></image-zoom>
				</div>	
			</template>
		</site-slider>
		<site-slider :items='images' :number='image_key' :classes="'media_viewer__small_images'">
			<template v-slot:body="sliderProps">
				<div class='media_viewer__small_image' v-for="(image, i) in sliderProps.items" :class="{'active':sliderProps.n==i}">
					<img :src='image.small_image' @click="image_key=i">		
				</div>	
			</template>
		</site-slider>
	</div>	
	<div class="media_viewer__content media_viewer__attributes media_viewer__product_data" v-if="tab=='attribute'">
		<div class ='media_viewer__content_data'>
			<div class ='edit_form' v-if="edit">
				<div class ='edit_form__item attribute' v-for="(property, k) in properties">
					<div class ='edit_form__title' v-html="property.name" v-if="property.parent==0"></div>			
					<div class ='edit_form__item_name' v-if="property.parent">
						<a :href="'<?php echo admin_url('term.php?taxonomy=usam-product_attributes&post_type=usam-product&tag_ID=') ?>'+property.term_id" target='_blank' title='<?php _e('Изменить характеристику','usam'); ?>' v-html="property.name"></a>
					</div>
					<div class ="edit_form__item_option" v-if="property.parent">
						<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/product-property.php' ); ?>
					</div>
				</div>
			</div>
			<div class ='edit_form' v-else>
				<div class ='edit_form__item attribute' v-for="(property, k) in properties">
					<div class ='edit_form__title' v-html="property.name" v-if="property.parent==0"></div>			
					<div class ='edit_form__item_name' v-if="property.parent" v-html="property.name"></div>
					<div class ="edit_form__item_option" v-if="property.parent">
						<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/view-product-property.php' ); ?>
					</div>
				</div>						
			</div>
		</div>			
	</div>	
	<div class="media_viewer__content media_viewer__prices media_viewer__product_data" v-if="tab=='prices'">
		<div class ='media_viewer__content_data'>
		
		</div>
	</div>	
	<div class="media_viewer__content media_viewer__stock media_viewer__product_data" v-if="tab=='stock'">
		<div class ='media_viewer__content_data'>
			<div class="edit_form">
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for="not_limited"><?php esc_attr_e( 'Запас не ограничен', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option"><input type='checkbox' id="not_limited" v-model="product.not_limited"></div>
				</div>		
			</div>						
			<div class="usam_table_container" v-if="!product.not_limited">
				<table class = "widefat product_table storage_table">
					<thead>
						<tr>
							<td><?php _e( 'Склад', 'usam'); ?></td>
							<td><?php _e( 'Остаток', 'usam'); ?></td>	
							<td><?php _e( 'Резерв', 'usam'); ?></td>	
							<td><?php _e( 'Доступно', 'usam'); ?></td>	
						</tr>
					</thead>
					<tbody>				
						<tr v-for="(storage, k) in product.storages">
							<td>
								<a class="row_name" :class="{'row_important':storage.shipping}" :href="'<?php echo admin_url("admin.php?page=storage&tab=storage&form=edit&form_name=storage&id="); ?>'+storage.id" v-html="storage.title"></a>
								<div v-html="storage.address"></div>
							</td>							
							<td>
								<?php	
								if ( !get_option("usam_inventory_control") )
								{
									?><input type='text' :id="'storage_'+storage.id" v-model='storage.stock' v-if="edit"><span v-else>{{storage.stock}}</span><?php	
								}
								else
									echo "<span  v-if='storage.stock!==0'>{{storage.stock}}</span>"; 
								?>	
							</td>
							<td><span class='item_status item_status_valid' v-if="storage.reserve>0">{{storage.reserve}}</span></td>
							<td><span v-if="storage.stock!==''">{{storage.stock-storage.reserve}}</span></td>
						</tr>
					</tbody>
					<tfoot>						
						<tr v-if="product.stock!=<?php echo USAM_UNLIMITED_STOCK; ?>">
							<td></td>
							<td>{{product.reserve+product.stock}}</td>
							<td>{{product.reserve}}</td>
							<td>{{product.stock}}</td>
						</tr>
					</tfoot>
				</table>
			</div>		
		</div>			
	</div>	
	<div class="media_viewer__content media_viewer__counters media_viewer__product_data" v-if="tab=='counters'">
		<div class ='media_viewer__content_data'>
			<div class='edit_form'>
				<div class='edit_form__item'>
					<div class='edit_form__item_name'><?php _e('Просмотров','usam') ?>:</div>	
					<div class='edit_form__item_option'>{{product.views}}</div>						
				</div>	
				<div class='edit_form__item'>
					<div class='edit_form__item_name'><?php _e('Комментариев','usam') ?>:</div>	
					<div class='edit_form__item_option'>{{product.comment}}</div>						
				</div>
				<div class='edit_form__item'>
					<div class='edit_form__item_name'><?php _e('В сравнении','usam') ?>:</div>	
					<div class='edit_form__item_option'>{{product.compare}}</div>						
				</div>
				<div class='edit_form__item'>
					<div class='edit_form__item_name'><?php _e('В избранном','usam') ?>:</div>	
					<div class='edit_form__item_option'>{{product.desired}}</div>						
				</div>
				<div class='edit_form__item'>
					<div class='edit_form__item_name'><?php _e('Подписано','usam') ?>:</div>	
					<div class='edit_form__item_option'>{{product.subscription}}</div>						
				</div>				
				<div class='edit_form__item'>
					<div class='edit_form__item_name'><?php _e('В корзине','usam') ?>:</div>	
					<div class='edit_form__item_option'>{{product.basket}}</div>						
				</div>
				<div class='edit_form__item'>
					<div class='edit_form__item_name'><?php _e('Продано','usam') ?>:</div>	
					<div class='edit_form__item_option'>{{product.purchased}}</div>						
				</div>
			</div>		
		</div>	
	</div>
</div>
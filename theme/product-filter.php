<?php
/*
Шаблон фильтра товаров. Подключается через блок Gutenberg в виджетах
*/
?>
<div class="screen_loading" v-if="loading">
	<div class="screen_loading__post">
		<div class="screen_loading__avatar"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
	</div>
	<div class="screen_loading__post">
		<div class="screen_loading__avatar"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
	</div>
	<div class="screen_loading__post">
		<div class="screen_loading__avatar"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
	</div>
	<div class="screen_loading__post">
		<div class="screen_loading__avatar"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
	</div>
	<div class="screen_loading__post">
		<div class="screen_loading__avatar"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
	</div>
	<div class="screen_loading__post">
		<div class="screen_loading__avatar"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
	</div>
	<div class="screen_loading__post">
		<div class="screen_loading__avatar"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
	</div>
	<div class="screen_loading__post">
		<div class="screen_loading__avatar"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
		<div class="screen_loading__line"></div>
	</div>
</div>			
<?php
if ( !empty($instance['storages']) )
{		
	?>
	<div class='filter_form__filter filter_form__shop' ref="shop" :class="[class_custom('favorite_shop')]">
		<div class='filter_form__title' @click="custom_open($event, 'favorite_shop')">
			<span class='filter_form__attribute_name'><?php _e("Наличие в магазине","usam"); ?></span>
			<?php usam_svg_icon("angle-down-solid", "term_arrow"); ?>
		</div>
		<div class='filter_form__list'>
			<div class='filter_form__items'>
				<div class='filter_form__item' :class="custom.favorite_shop.selected==storage.id?'filter_form__item_selected':''" v-for="storage in custom.favorite_shop.data" :key="storage.id"><label><input type='radio' :value="storage.id" v-model="custom.favorite_shop.selected" class="option-input radio"><span v-html="storage.name"></span></label></div>
			</div>
			<div class="filter_form__actions"><button @click="apply_filters" class="button apply_filters_button"/><?php _e( 'Применить', 'usam'); ?></div>
		</div>
	</div>		
	<?php	
} 
if ( !empty($instance['individual_price']) )
{		
	?>
	<div class='filter_form__filter filter_form__individual_price' ref="individual_price" :class="[class_custom('individual_price')]">
		<div class='filter_form__title' @click="custom_open($event, 'individual_price')">
			<span class='filter_form__attribute_name'><?php _e("Цена для компании","usam"); ?></span>
			<?php usam_svg_icon("angle-down-solid", "term_arrow"); ?>
		</div>
		<div class='filter_form__list'>
			<div class='filter_form__items'>
				<div class='filter_form__item' :class="custom.individual_price.selected==price.id?'filter_form__item_selected':''" v-for="price in custom.individual_price.data" :key="price.id"><label><input type='radio' :value="price.id" v-model="custom.individual_price.selected" class="option-input radio">{{price.name}}</label></div>
			</div>
			<div class="filter_form__actions"><button @click="apply_filters" class="button apply_filters_button"/><?php _e( 'Применить', 'usam'); ?></div>
		</div>
	</div>		
	<?php	
} 
if ( !empty($instance['range_price']) )
{	
	?>
	<div class='filter_form__filter filter_form__price' ref="range_price" :class="[class_custom('prices')]" v-show="custom.prices.max_price!=0">
		<div class='filter_form__title' :class="{'selected':custom.prices.min_price != custom.prices.selected[0] || custom.prices.max_price != custom.prices.selected[1]}" @click="custom_open($event, 'prices')">
			<span class='filter_form__attribute_name'><?php _e("Цена","usam"); ?></span>
			<?php usam_svg_icon("angle-down-solid", "term_arrow"); ?>
			<span class="filter_form__reset" @click="custom_reset('prices')">&times;</span>
		</div>
		<div class='filter_form__list'>
			<div class='filter_form__items price_range_slider'>
				<filter-prices @changeprice="custom.prices.selected = $event" :min="custom.prices.min_price" :max="custom.prices.max_price" :min_value="custom.prices.selected[0]" :max_value="custom.prices.selected[1]"></filter-prices>
			</div>
			<div class="filter_form__actions"><button @click="apply_filters" class="button apply_filters_button"/><?php _e( 'Применить', 'usam'); ?></div>
		</div>
	</div>		
	<?php	
}
if ( !empty($instance['categories']) ) 
{
	if ( $instance['categories'] == 'hierarchy' )
	{
		?>
		<div class='filter_form__filter filter_form__categories' ref="scat_hierarchy" :class="[class_custom('scat')]">
			<div class='filter_form__title' :class="[custom.scat.selected.length?'selected':'']" @click="custom_open($event, 'scat')">
				<span class='filter_form__attribute_name'><?php _e("Категории","usam"); ?></span>
				<?php usam_svg_icon("angle-down-solid", "term_arrow"); ?>
			</div>
			<div class='filter_form__list'>
				<div class='filter_form__items'>
					<div class='filter_form__item' :class="custom.scat.selected.includes(category.id)?'filter_form__item_selected':''" v-for="category in custom.scat.data" :key="category.id"><label><a :href="category.url">{{category.name}}</a></label></div>
				</div>
			</div>
		</div>		
		<?php
	}
	else
	{
		?>
		<div class='filter_form__filter filter_form__categories' ref="scat" :class="[class_custom('scat')]">
			<div class='filter_form__title' :class="[custom.scat.selected.length?'selected':'']" @click="custom_open($event, 'scat')">
				<span class='filter_form__attribute_name'><?php _e("Категории","usam"); ?></span>
				<?php usam_svg_icon("angle-down-solid", "term_arrow"); ?>
				<span class="filter_form__reset" @click="custom_reset('scat')">&times;</span>
			</div>
			<div class='filter_form__list'>
				<div class='filter_form__items'>
					<div class='filter_form__item' :class="custom.scat.selected.includes(category.id)?'filter_form__item_selected':''" v-for="category in custom.scat.data" :key="category.id"><label><input class="option-input" type='checkbox' :value='category.id' v-model="custom.scat.selected">{{category.name}}</label><span class='filter_form__item_counter'>{{category.count}}</span></div>
				</div>
				<div class="filter_form__actions"><button @click="apply_filters" class="button apply_filters_button"/><?php _e( 'Применить', 'usam'); ?></div>
			</div>
		</div>		
		<?php		
	}
}
if ( !empty($instance['product_rating']) )
{	
	?>
	<div class='filter_form__filter filter_form__rating' :class="[custom.rating.active?'active':'']">
		<div class='filter_form__title' :class="[custom.rating.selected!='undefined' && custom.rating.selected.length?'selected':'']" @click="custom_open($event, 'rating')">
			<span class='filter_form__attribute_name'><?php _e("Рейтинг","usam"); ?></span>
			<?php usam_svg_icon("angle-down-solid", "term_arrow"); ?>
			<span class="filter_form__reset" @click="custom_reset('rating')">&times;</span>
		</div>
		<div class='filter_form__list'>
			<div class='filter_form__items'>
				<?php
				foreach( array(1, 2, 3, 4, 5) as $i )
				{
					?><div class='filter_form__item' :class="custom.rating.selected.includes(<?php echo $i ?>)?'filter_form__item_selected':''">
						<label><input class='option-input' type='checkbox' v-model='custom.rating.selected' :value='<?php echo $i ?>'><?php echo usam_get_rating( $i ) ?></label>
					</div><?php
				}
				?>
			</div>
			<div class="filter_form__actions"><button @click="apply_filters" class="button apply_filters_button"/><?php _e( 'Применить', 'usam'); ?></div>
		</div>
	</div>
	<?php	
}
?>
<div class='filter_form__filter filter_form__attributes' v-for="(attribute, k) in attributes" :key="attribute.id" :class="[attribute.active?'active':'']" v-if="attribute.type=='C' || attribute.type!='O' || attribute.type!='N' || attribute.filters && attribute.filters.length > 2">
	<div class='filter_form__title' :class="{'selected':attributeSelected(attribute)}" @click="filter_open(attribute.id, $event)">
		<span class='filter_form__attribute_name' v-html="attribute.name"></span> <span class="filter_form__count_selected" v-html="count_selected(attribute)"></span><?php usam_svg_icon("angle-down-solid", "term_arrow"); ?><span @click="filter_reset(k)" class="filter_form__reset">&times;</span>
	</div>
	<div class='filter_form__list'>
		<div class='filter_form__items' v-if="attribute.type=='C'">
			<label><input type="checkbox" class="option-input" value="1" v-model="attribute.selected"><?php _e('Да','usam'); ?></label> &nbsp;
			<label><input type="checkbox" class="option-input" value="0" v-model="attribute.selected"><?php _e('Нет','usam'); ?></label>
		</div>
		<div class='filter_form__items filter_form__range_slider' v-else-if="attribute.type=='O' || attribute.type=='N'">			
			<filter-prices @changeprice="change_attribute_slider(k,$event)" v-bind:min="attribute.min_price" v-bind:max="attribute.max_price" v-bind:min_value="attribute.selected[0]" v-bind:max_value="attribute.selected[1]"></filter-prices>
		</div>
		<div class='filter_form__items' v-else>
			<div class='filter_form__search' v-if="attribute.filters.length>8">
				<input type="text" class="option-input" v-model="attribute.search"/><?php echo usam_get_svg_icon('search') ?>
			</div>
			<div class='filter_form__item' :class="attribute.selected.includes(filter.id)?'filter_form__item_selected':''" v-for="filter in attribute.filters" :key="filter.id" v-if="attribute.id==filter.attribute_id" v-show="!attribute.search || filter.name.toLowerCase().includes(attribute.search)">
				<label><input class="option-input" :class="[attribute.type=='COLOR_SEVERAL'?'radio':'']" type='checkbox' :value='filter.id' v-model="attribute.selected" :style="{background:filter.code}"><span v-html="filter.name"></span></label>
				<span class='filter_form__item_counter'>{{filter.count}}</span>
			</div>
		</div>
		<div class="filter_form__actions"><button @click="apply_filters" class="button apply_filters_button"><?php _e( 'Применить', 'usam'); ?></button></div>
	</div>
</div>
<button v-if="selectedFilters" @click="resetAll" class="button main-button reset_filter_button"><?php _e( 'Сбросить', 'usam'); ?></button>
<?php
/**
 * Поиск на сайте. Используется в блоке Gutenberg
 */
?>
<div id="site_search" v-cloak class="search_form" :class="{'active':active}">
	<div class="search_form_container">
		<div class="search_form__keyword" @click="active=1">		
			<input class="search_form__input" ref="search" type="text" placeholder="<?php _e( 'Поиск по каталогу', 'usam' ); ?>" v-model="keyword" @keydown="autocomplete_change" @paste="searchPaste" @focus="focus" autocomplete="off">
			<span v-if="isLoading" class="loading_process"></span>
			<?php usam_svg_icon("search", ['@click' => 'go_page_search', "v-else" => "", ":class" => '{"is-loading":isLoading}']); ?>								
		</div> 
		<div class="search_panel" v-show="active">
			<div class="search_panel__popular" v-if="products==null">
				<div class="search_panel__popular_word" v-for="(word, k) in popular" @click="popular_word(k)" v-html="word"></div>								
			</div>
			<div class="search_panel__products" v-else>
				<div class="search_panel__categories" v-if="categories.length">
					<div class="search_panel__category" v-for="category in categories"><a :href="category.url" v-html="category.name"></a></div>
				</div>
				<div class="search_panel__products_container">
					<div class="search_panel__product" v-if="products.length" v-for="product in products">
						<div class="search_panel__product_image"><a :href="product.url"><img :src="product.small_image"></a></div>	
						<div class="search_panel__product_content">
							<div class="search_panel__product_name"><a :href="product.url" v-html="product.post_title"></a></div>
							<div class="search_panel__product_description"><a :href="product.url" v-html="product.block_title"></a></div>
							<div class="search_panel__product_prices">
								<div class="search_panel__product_old_price old_price" v-if="product.old_price" v-html="product.old_price"></div>
								<div class="search_panel__product_price" v-html="product.price_currency"></div>	
							</div>											
						</div>
					</div>
				</div>
				<div class="search_panel__products_count" v-if="products.length">
					<a ref="more_results" :href="'<?php echo usam_get_url_system_page('search'); ?>/'+keyword"><?php _e( 'Все результаты', 'usam' ); ?> ({{count}})</a></div>
				<div class="search_panel__products_none" v-if="products.length==0"><?php _e( 'По вашему запросу нет результатов', 'usam' ); ?></div>
			</div>
		</div>						
	</div> 
</div>
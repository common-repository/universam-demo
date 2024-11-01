<?php
/**
 * Главные функции продукта в админском интерфейсе. Карточка товара.
 */ 
function usam_redirect_variation_update( $location, $post_id )
{
	global $post;
	if ( !empty($post->post_parent) && 'usam-product' == $post->post_type )
		wp_redirect( get_edit_post_link( $post->post_parent ) );
	else
		return $location;
}
//add_filter( 'redirect_post_location', 'usam_redirect_variation_update', 10, 2 );
	
function usam_meta_boxes()
{ 
	global $post;		
	if ( $post->post_type == 'usam-product' )
	{				
		remove_meta_box( 'usam-product_attributesdiv', null, 'side' );			
		$meta_box = new USAM_Product_Meta_Box( $post->ID );				
		// если страница вариации то не показывать эти метабоксы
		if ( is_object( $post ) && $post->post_parent == 0 ) 
		{			
			add_meta_box( 'usam_attributes_forms', __('Торговое предложение', 'usam'), [$meta_box, 'product_attributes_forms'], $post->post_type, 'normal', 'high' );			
		}
		elseif( is_object( $post ) && $post->post_status == "inherit" ) 
		{
			remove_meta_box( 'tagsdiv-product_tag', $post->post_type, 'core' );	
			remove_meta_box( 'usam_product_categorydiv', $post->post_type, 'core' );
			remove_meta_box( 'usam-brandsdiv',  $post->post_type, 'side' );
		}			
	//	add_action('usam_type_product', array($meta_box, 'type_product'), 5 );
	//	add_action( 'post_submitbox_misc_actions', array( $meta_box, 'product_data_visibility' ) );
		if ( !empty($post->post_title) )
			usam_employee_viewing_objects(['object_type' => 'product', 'object_id' => $post->ID]);	
	}
}	
add_action( 'add_meta_boxes', 'usam_meta_boxes' );

/**
 * Удалить мета бокс категорий и брендов в редакторе вариации.
 */
function usam_variation_remove_metaboxes()
{	
	global $post;
		
	if ( ! $post->post_parent )
		return;
	remove_meta_box( 'usam-categorydiv', 'usam-product', 'side' );
	remove_meta_box( 'usam-brandsdiv', 'usam-product', 'side' );
}
add_action( 'add_meta_boxes_usam-product', 'usam_variation_remove_metaboxes', 99 );

class USAM_Product_Meta_Box
{
	private $id;
	private $product_type;
	
	public function __construct( $product_id )
	{				
		$this->id = $product_id;
		$this->product_type = usam_get_product_type( $product_id );	
	}	
	
	function product_data_visibility() 
	{		
		?>000000000000
		<?php		
	}	
	
	function type_product() 
	{		
		?>
		<span class="type_box"> — 
			<label for="product-type">
				<select id="product-type" name="pmeta[<?php echo $this->id; ?>][product-type]">
					<optgroup label="<?php _e('Тип продукта', 'usam'); ?>">
						<option value="simple" selected="selected"><?php _e('Простой продукт', 'usam'); ?></option>
						<option value="grouped"><?php _e('Группировка продуктов', 'usam'); ?></option>
						<option value="external"><?php _e('Внешний/Партнерский продукт', 'usam'); ?></option>
						<option value="variable"><?php _e('Вариативный товар', 'usam'); ?></option>
					</optgroup>
				</select>	
			</label>
			<label for="_virtual" class="show_if_simple tips"><?php _e('Виртуальный', 'usam'); ?>: <input type="checkbox" name="_virtual" id="_virtual"></label>
			<label for="_downloadable" class="show_if_simple tips"><?php _e('Загружаемый', 'usam'); ?>: <input type="checkbox" name="_downloadable" id="_downloadable"></label>
		</span>
		<?php		
	}
	
	function product_attributes_forms() 
	{	
		usam_vue_module('table-products');
		usam_vue_module('paginated-list');		
		//usam_update_product_meta( $product_id, 'rule_'.$this->rule['id'], date("Y-m-d H:i:s") );
		$types_product = usam_get_types_products_sold();
		?>
<site-slider>
<template v-slot:body="sliderProps">
	<div class="section_tabs">
		<div class="section_tab" @click="sectionTab='properties'" :class="{'active': sectionTab=='properties'}"><?php _e( 'Характеристики', 'usam'); ?></div>
		<div class="section_tab" @click="sectionTab='variations'" :class="{'active': sectionTab=='variations'}"><?php _e( 'Вариации', 'usam'); ?><span v-if="data.variations.length"> ({{data.variations.length}})</span></div>
		<?php
		if ( !usam_is_multisite() || is_main_site() )
		{
			?>
			<div class="section_tab" @click="sectionTab='price'" :class="{'active': sectionTab=='price'}"><?php _e( 'Цена', 'usam'); ?></div>
			<div class="section_tab" @click="sectionTab='stock'" :class="{'active': sectionTab=='stock'}" v-if="data.sale_type=='product'"><?php _e( 'Запас', 'usam'); ?><span v-if="data.stock>0 && data.stock<<?php echo USAM_UNLIMITED_STOCK ?>"> ({{data.stock}} {{data.name_unit_measure}})</span></div>
		<?php }	?>
		<div class="section_tab" @click="sectionTab='images'" :class="{'active': sectionTab=='images'}"><?php _e( 'Изображения', 'usam'); ?><span v-if="data.images.length"> ({{data.images.length}})</span></div>
		<div class="section_tab" @click="sectionTab='webspy_link'" :class="{'active': sectionTab=='webspy_link'}"  v-if="data.webspy_link"><?php get_option('usam_website_type', 'store' ) == 'price_platform' ? _e( 'Ссылка', 'usam') : _e( 'Парсинг', 'usam'); ?></div>
		<div class="section_tab" @click="sectionTab='seo'" :class="{'active': sectionTab=='seo'}">SEO</div>			
		<div class="section_tab" @click="sectionTab='post_excerpt'" :class="{'active': sectionTab=='post_excerpt'}"><?php _e( 'Описание', 'usam'); ?><span class="customer_online" v-if="data.post_excerpt!=''"></span></div>
		<?php if( current_user_can('view_seo') ) { ?>
		<div class="section_tab" @click="sectionTab='tabs'" :class="{'active': sectionTab=='tabs'}" v-if="productTabs.length"><?php _e( 'Вкладки', 'usam'); ?><span v-if="data.tabs.length"> ({{data.tabs.length}})</span></div>
		<?php } ?>
		<div class="section_tab" @click="sectionTab='crosssell'" :class="{'active': sectionTab=='crosssell'}" v-if="data.product_type=='simple' || data.product_type=='variable'"><?php _e( 'Сопутствующая', 'usam'); ?><span v-if="product_lists.crosssell.length"> ({{product_lists.crosssell.length}})</span></div>	
		<div class="section_tab" @click="sectionTab='similar'" :class="{'active': sectionTab=='similar'}" v-if="data.product_type=='simple' || data.product_type=='variable'"><?php _e( 'Аналоги', 'usam'); ?><span v-if="product_lists.similar.length"> ({{product_lists.similar.length}})</span></div>	
		<div class="section_tab" @click="sectionTab='options'" :class="{'active': sectionTab=='options'}" v-if="data.product_type=='simple' || data.product_type=='variable'"><?php _e( 'Опции или услуги', 'usam'); ?><span v-if="product_lists.options.length"> ({{product_lists.options.length}})</span></div>	
		<div class="section_tab" @click="sectionTab='posts'" :class="{'active': sectionTab=='posts'}"><?php _e( 'Статьи', 'usam'); ?><span v-if="posts.length"> ({{posts.length}})</span></div>
		<div class="section_tab" @click="sectionTab='reputation'" :class="{'active': sectionTab=='reputation'}"><?php _e( 'Репутация товара', 'usam'); ?><span v-if="reputation_items.length"> ({{reputation_items.length}})</span></div>	
	</div>
</template>
</site-slider>
		<div v-show="sectionTab=='images'" class="section_content">
			<div class="photo_gallery">
				<div class="image" v-for="(image, i) in data.images" draggable="true" @dragover="allowDrop($event, i)" @dragstart="drag($event, i)" @dragend="dragEnd($event, i)">
					<div class="image_container"><img :src="image.medium_image" loading='lazy' @click="openViewer"></div>
					<a href="#" class="delete dashicons" @click="deleteMedia(i)"><?php _e('Удалить', 'usam'); ?></a>
					<input type="hidden" v-model="image.ID" name="image_gallery[]">
				</div>
			</div>
			<div class="empty_items" v-show="data.images.length==0">
				<div class="empty_items__icon"><span class="dashicons dashicons-format-image"></span></div>
				<div class="empty_items__title"><?php  _e('Изображения еще не добавлены', 'usam'); ?></div>				
			</div>
			<div class="photo_gallery_add">
				<wp-media inline-template @change="addMedia" :title="'<?php esc_attr_e( 'Добавить изображение в галерею', 'usam'); ?>'" :multiple="true" :file="{}">
					<a class="button" @click="addMedia"><?php _e( 'Добавить изображение', 'usam'); ?></a>
				</wp-media>
			</div>
		</div>
		<div v-show="sectionTab=='properties'" class="section_content product_properties">		
			<div class ="colum1 colums_style">
				<div id="message_required_field" v-show="codeError"><p><?php _e('Заполните обязательные характеристики товара, чтобы сохранить товар', 'usam'); ?>.</p></div>
				<div class ='edit_form'>	
					<div class ='edit_form__item'>
						<div class ='edit_form__item_name'></div>
						<div class ='edit_form__item_option attribute_filter'>
							<a @click="attributeFilter='all'" :class="{'active':attributeFilter=='all'}"><?php _e( 'Все', 'usam'); ?></a>
							<a @click="attributeFilter='completed'" :class="{'active':attributeFilter=='completed'}"><?php _e( 'Заполненные', 'usam'); ?></a>
							<a @click="attributeFilter='not_completed'" :class="{'active':attributeFilter=='not_completed'}"><?php _e( 'Не заполненные', 'usam'); ?></a>
						</div>
					</div>
					<?php
					$select_products = get_option('usam_types_products_sold', ['product', 'services']);
					if ( count($select_products) > 1 )
					{
						?>	
						<div class ='edit_form__item product_sale_type'>
							<div class ='edit_form__item_name'><?php _e('Тип товара', 'usam'); ?></div>
							<div class ="edit_form__item_option">
								<?php	
								$types = [];
								foreach( $types_product as $key => $type )
								{					
									if( in_array($key, $select_products) )
									{
										$types[] = ['id' => $key, 'name' => $type['single']];
									}
								} 
								?>
								<selector v-model="data.sale_type" :items='<?php echo json_encode( $types ); ?>'></selector>
							</div>
							<input type='hidden' :name="'productmeta[<?php echo $this->id; ?>][virtual]'" v-model="data.sale_type">
						</div>						
						<?php
					}								
					if( current_user_can('view_showcases') )
					{
						?>					
						<div class ='edit_form__item' v-if="showcases.length">
							<div class ='edit_form__item_name'><?php _e('Витрины', 'usam'); ?></div>
							<div class ="edit_form__item_option checkblock">
								<div><input type="hidden" name="showcases[]" :value="id" v-for="(id, k) in data.showcases"></div>
								<check-list :lists='showcases' :selected='data.showcases' @change="data.showcases=$event"/>
							</div>
						</div>
						<?php
					} 
					?>
					<div class ='edit_form__item' v-for="(value, k) in data.code_names" v-if="k!='barcode' || data.sale_type=='product'">
						<div class ='edit_form__item_name'><label :for="'property_'+k" v-html="value"></label></div>
						<div class ="edit_form__item_option"><input size='32' type='text' class='text' :name="'productmeta[<?php echo $this->id; ?>]['+k+']'" v-model="data[k]"></div>
					</div>					
					<div class ='edit_form__item' v-if="data.sale_type=='product'">
						<div class ='edit_form__item_name'><?php _e('Длина х Ширина х Высота', 'usam'); ?></div>							
						<div class ="edit_form__item_option product_dimensions">
							<input size='32' type='text' class='text' name="productmeta[<?php echo $this->id; ?>][length]" v-model="data.length" title="<?php _e('Длина', 'usam'); ?>">
							<input size='32' type='text' class='text' :name="'productmeta[<?php echo $this->id; ?>][width]'" v-model="data.width" title="<?php _e('Ширина', 'usam'); ?>">
							<input size='32' type='text' class='text' :name="'productmeta[<?php echo $this->id; ?>][height]'" v-model="data.height" title="<?php _e('Высота', 'usam'); ?>">
						</div>
					</div>
					<div class ='edit_form__item' v-if="data.sale_type=='product'" v-for="(value, k) in {'weight': '<?php _e('Вес', 'usam'); ?>', 'volume': '<?php _e('Объем', 'usam'); ?>'}">
						<div class ='edit_form__item_name'><label :for="'property_'+k" v-html="value"></label></div>
						<div class ="edit_form__item_option"><input size='32' type='text' class='text' :name="'productmeta[<?php echo $this->id; ?>]['+k+']'" v-model="data[k]"></div>
					</div>
					<div class ='edit_form__item' v-if="data.sale_type=='product'">
						<div class ='edit_form__item_name'><?php _e('Под заказ', 'usam'); ?></div>
						<div class ="edit_form__item_option"><input type="checkbox" name="productmeta[<?php echo $this->id; ?>][under_order]" value="1" v-model="data.under_order"></div>
					</div>
					<?php 
					$companies = usam_get_companies(['fields' => ['id', 'name'], 'type' => 'contractor']);	
					if ( !empty($companies) ){ ?>
						<div class ='edit_form__item' v-if="data.sale_type=='product'">
							<div class ='edit_form__item_name'><?php _e('Поставщик товара', 'usam'); ?></div>
							<div class ="edit_form__item_option">
								<select name="productmeta[<?php echo $this->id; ?>][contractor]" v-model="data.contractor">
									<?php			
									foreach ( $companies as $company )
									{					
										?><option value="<?php echo $company->id; ?>"><?php echo $company->name; ?></option><?php
									}				
									?>
								</select>	
							</div>
						</div>
					<?php } 
					$units = usam_get_list_units();
					$unit_measure = usam_get_product_meta($this->id, 'unit_measure');		
					$unit = current($units);		
					$unit_measure = $unit_measure ? $unit_measure : $unit['code'];					
					?>	
					<div class ='edit_form__item' v-if="data.sale_type=='product'">
						<div class ='edit_form__item_name'><?php _e('Единица измерения', 'usam'); ?></div>
						<div class ="edit_form__item_option">
							<div class="unit_measure">		
								<?php 
								$unit = usam_get_product_meta($this->id, 'unit'); 
								$unit = $unit == 0?1:$unit;
								?>
								<input size='8' type='text' id="unit" name='productmeta[<?php echo $this->id; ?>][unit]' value='<?php echo $unit; ?>' />
								<select name="productmeta[<?php echo $this->id; ?>][unit_measure]">
									<?php								
									foreach ( $units as $unit )
									{					
										?><option value="<?php echo $unit['code']; ?>" <?php selected( $unit['code'],$unit_measure ); ?>><?php echo $unit['title']; ?></option><?php
									}				
									?>
								</select>	
							</div>						
							<div class="additional_units" v-if="data.additional_units.length">
								<abbr title="<?php esc_attr_e( 'Дополнительные единицы измерения', 'usam'); ?>"><?php _e( 'Дополнительные единицы измерения', 'usam'); ?>:</abbr>
								<level-table :lists='data.additional_units'>												
									<template v-slot:tbody="slotProps">
										<td class="units_table_rate__unit">
											<input size='8' type='text' name='pmeta[<?php echo $this->id; ?>][additional_unit][unit][]' v-model="slotProps.row.unit">
										</td>	
										<td>
											<select name="pmeta[<?php echo $this->id; ?>][additional_unit][unit_measure][]" v-model="slotProps.row.unit_measure">
												<?php								
												foreach ( $units as $unit )
												{					
													?><option value="<?php echo $unit['code']; ?>"><?php echo $unit['title']; ?></option><?php
												}				
												?>
											</select>
										</td>											
										<td class="column_actions">					
											<?php usam_system_svg_icon("drag", ["draggable" => "true"]); ?>
											<?php usam_system_svg_icon("plus", ["@click" => "slotProps.add(slotProps.k)"]); ?>
											<?php usam_system_svg_icon("minus", ["@click" => "slotProps.del(slotProps.k)"]); ?>
										</td>
									</template>
								</level-table>	
							</div>	
							<a @click="data.additional_units=[{unit:'', unit_measure:''}]" v-else><?php _e( 'Требуются дополнительные единицы измерения', 'usam'); ?></a>						
						</div>
					</div>
					<?php
					if ( usam_check_type_product_sold( 'electronic_product' ) )
					{
						?>
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><?php esc_attr_e( 'Загружаемые файлы при продаже', 'usam'); ?>:</div>
							<div class ="edit_form__item_option">
								<?php
								$change =  current_user_can('view_my_files') || current_user_can('view_all_files');
								require( USAM_FILE_PATH ."/admin/templates/template-parts/attachments.php"); 
								?>
							</div>
						</div>
						<?php					
					}
					?>		
					<div class ="edit_form__item" v-show="agreements.length">
						<div class ="edit_form__item_name"><?php esc_attr_e( 'Лицензионное соглашение', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<select id="license_agreement" name='productmeta[<?php echo $this->id; ?>][license_agreement]' v-model="data.license_agreement">
								<option value="0"><?php _e( 'Не требует', 'usam'); ?></option>
								<option v-for="(agreement, k) in agreements" :value="agreement.ID">{{agreement.post_title}}</option>
							</select>		
						</div>
					</div>
				</div>						
				<div id="attributes" class ='edit_form product_attributes_form'>					
					<div class ='edit_form__item attribute' v-for="(property, k) in properties" v-if="property.parent==0 || attributeFilter=='all' || attributeFilter=='completed' && property.value!=='' || attributeFilter=='not_completed' && property.value===''">
						<div class ='edit_form__title' v-html="property.name" v-if="property.parent==0"></div>			
						<div class ='edit_form__item_name' v-if="property.parent">
							<a :href="'<?php echo admin_url('term.php?taxonomy=usam-product_attributes&post_type=usam-product&tag_ID=') ?>'+property.term_id" target='_blank' title='<?php _e('Изменить характеристику','usam'); ?>' v-html="property.name+(property.field_type=='COLOR_SEVERAL' || property.field_type=='M'?' ('+property.value.length+')':'')"></a>
						</div>
						<div class ="edit_form__item_option" v-if="property.parent">
							<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/product-property.php' ); ?>
						</div>
					</div>	
					<div class ='edit_form__item'>
						<div class ='edit_form__item_name'></div>
						<div class ="edit_form__item_option"><a href='<?php echo admin_url('edit-tags.php?taxonomy=usam-product_attributes&post_type=usam-product') ?>' target='_blank'><?php _e('Добавить характеристику', 'usam') ?></a></div>
					</div>
				</div>		
				<div class ="components">
					<h3><?php _e('Комплектация', 'usam'); ?></h3>
					<level-table :lists='data.components' :fixed="false">				
						<template v-slot:thead="slotProps">
							<th><?php _e( 'Количество', 'usam'); ?></th>
							<th><?php _e( 'Наименование', 'usam'); ?></th>	
							<th class="column_actions"></th>		
						</template>			
						<template v-slot:tbody="slotProps">
							<td class="column_quantity">
								<input type='hidden' name='components[id][]' v-model="slotProps.row.id"/>
								<input :ref="'value'+slotProps.k" type="text" name="components[quantity][]" v-model="slotProps.row.quantity" size="4">	
							</td>	
							<td class="column_component">
								<autocomplete :selected="slotProps.row.component" v-on:keydown="property.value=$event.value" @change="slotProps.row.component=$event.name" request="products/components" :none="'<?php _e('Подобные данные не найдены','usam'); ?>'"></autocomplete>
								<input type="hidden" name="components[component][]" v-model="slotProps.row.component">
							</td>									
							<td class="column_actions">											
								<?php usam_system_svg_icon("drag", ["draggable" => "true"]); ?>
								<?php usam_system_svg_icon("plus", ["@click" => "slotProps.add(slotProps.k)"]); ?>
								<?php usam_system_svg_icon("minus", ["@click" => "slotProps.del(slotProps.k)"]); ?>										
							</td>
						</template>
					</level-table>	
				</div>				
			</div>
			<div class="photo_gallery_vertical photo_gallery vertical">
				<wp-media inline-template @change="addMedia" :title="'<?php esc_attr_e( 'Добавить изображение в галерею', 'usam'); ?>'" :multiple="true" :file="{}">
					<a class="button" @click="addMedia"><span class="dashicons dashicons-plus-alt2"></span><span class="button_text"><?php _e( 'Добавить изображение', 'usam'); ?></span></a>
				</wp-media>
				<div class="image" v-for="(image, i) in data.images" draggable="true" @dragover="allowDrop($event, i)" @dragstart="drag($event, i)" @dragend="dragEnd($event, i)">
					<div class="image_container"><img :src="image.medium_image" loading='lazy' @click="openViewer"></div>
					<a href="#" class="delete dashicons" @click="deleteMedia(i)"><?php _e('Удалить', 'usam'); ?></a>
				</div>					
			</div>	
		</div>			
		<div v-show="sectionTab=='post_excerpt'" class ="section_content colums_style">
			<h4><?php _e( 'Дополнительное описание', 'usam'); ?></h4>
			<?php	
			global $post;
			$settings = array(
				'textarea_name' => 'post_excerpt',
				'textarea_rows' => 30,
				'editor_height' => 400,
				'quicktags'     => ['buttons' => 'em,strong,link'],
				'tinymce'       => array(
					'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
					'theme_advanced_buttons2' => '',
				),
				'media_buttons' => 0,
			);
			wp_editor( htmlspecialchars_decode($post->post_excerpt), 'excerpt', apply_filters( 'usam_product_short_description_editor_settings', $settings ) );
			?>		
		</div>
		<div v-show="sectionTab=='tabs'" class ="section_content custom_product_tabs colums_style">
			<div class ="custom_tabs" v-if="productTabs.length">
				<div class ="custom_tabs__list">
					<div class ="custom_tabs__list_item" v-for="(item, i) in productTabs" :class="[{'inactive_tab':!item.global && !data.tabs.includes(item.id)},{'active':custom_tab==i}]" @click="custom_tab=i;">
						<div class ="custom_tabs__list_item_title"><span class="custom_tabs__list_item_title_tab" v-html="item.title"></span><span class="custom_tab_global" v-if="item.global">(<?php _e( 'Глобальная', 'usam') ?>)</span><selector v-else @input="addTab($event, i)" :value="data.tabs.includes(item.id)?1:0"/></div>					
						<a class ="custom_tabs__list_item_edit" @click="editTab=!editTab" v-if="confirmationDeletion!==i && editTab==false"><?php _e( 'Редактировать', 'usam') ?></a>
						<a class ="custom_tabs__list_item_view" @click="editTab=!editTab" v-if="confirmationDeletion!==i && editTab!==false && custom_tab==i"><?php _e( 'Посмотреть', 'usam') ?></a>
						<input type="hidden" name="custom_product_tab[]" v-if="!item.global && data.tabs.includes(item.id)" :value="item.id">
						<span class="custom_tabs__list_item_delete dashicons dashicons-no-alt" @click="confirmationDeletion=i" v-if="confirmationDeletion===false"></span>
						<div class="custom_tabs__list_item_confirmation_delete" v-else-if="confirmationDeletion==i">
							<button type="button" class="button button-primary" @click="deleteTab(i)"><?php esc_html_e( 'Удалить', 'usam'); ?></button>					
							<button type="button" class="button" @click="confirmationDeletion=false"><?php esc_html_e( 'Отменить', 'usam'); ?></button>					
						</div>
					</div>
					<a class ="custom_tabs__list_item" @click="addTab"><?php _e( 'Добавить новую вкладку', 'usam') ?></a>
				</div>
				<div class ="custom_tabs__content">					
					<div v-show="editTab">
						<div class="custom_tabs__content_settings">							
							<div class="edit_form">				
								<div class ="edit_form__item">
									<div class ="edit_form__item_name"><?php esc_html_e( 'Название', 'usam'); ?>:</div>
									<div class ="edit_form__item_option">
										<input type="text" v-model="productTabs[custom_tab].title">
									</div>
								</div>
								<div class ="edit_form__item">
									<div class ="edit_form__item_name"><?php esc_html_e( 'Активность', 'usam'); ?>:</div>
									<div class ="edit_form__item_option">
										<selector v-model="productTabs[custom_tab].active"></selector>
									</div>
								</div>
								<div class ="edit_form__item">
									<div class ="edit_form__item_name"><?php esc_html_e( 'Глобальная', 'usam'); ?>:</div>
									<div class ="edit_form__item_option">
										<selector v-model="productTabs[custom_tab].global"></selector>
									</div>
								</div>
								<div class ="edit_form__item">
									<div class ="edit_form__item_name"><?php esc_html_e( 'Название для посетителей', 'usam'); ?>:</div>
									<div class ="edit_form__item_option">
										<input type="text" v-model="productTabs[custom_tab].name">
									</div>
								</div>
								<div class ="edit_form__item">
									<div class ="edit_form__item_name"><?php esc_html_e( 'Код', 'usam'); ?>:</div>
									<div class ="edit_form__item_option">
										<input type="text" v-model="productTabs[custom_tab].code">
									</div>
								</div>	
								<div class ="edit_form__buttons">			
									<button type="button" class="button button-primary" @click="saveTab"><?php esc_html_e( 'Сохранить', 'usam'); ?></button>
								</div>								
							</div>
						</div>		
						<?php
						$settings = [
							'textarea_name' => "description",
							'quicktags'     => ['buttons' => 'em,strong,link'],
							'tinymce'       => [
								'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
								'theme_advanced_buttons2' => '',
							],
							'media_buttons' => 0,
						];
						wp_editor( '', "custom_product_tab_editor", $settings );
						?>
					</div>
					<div v-show="!editTab" v-html="productTabs[custom_tab].description"></div>
				</div>
			</div>
		</div>	
		<div class ="section_content colums_style" v-show="sectionTab=='webspy_link'">
			<div class="edit_form">
				<label class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Ссылка', 'usam'); ?>:</div>
					<div class ="edit_form__item_option">
						<input type="text" name="productmeta[<?php echo $this->id; ?>][webspy_link]" v-model="data.webspy_link">
					</div>
				</label>
				<?php	
				$link = usam_get_product_meta($this->id, 'webspy_link');
				if ( $link != '' )
				{						
					$host = parse_url($link, PHP_URL_HOST);	
					?>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Ссылка на товар', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<a href='<?php echo $link; ?>' target='_blank'><?php echo $host; ?></a>
						</div>
					</div>			
				<?php
				}
				if ( usam_get_product_meta($this->id, 'date_externalproduct') )
				{
					?>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Последняя проверка', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<?php echo date('d.m.Y H:i', strtotime(usam_get_product_meta($this->id, 'date_externalproduct'))); ?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>		
		<div :id="list+'_products'" v-for="(products, list) in product_lists" v-show="sectionTab==list" v-if="data.product_type=='simple' || data.product_type=='variable'" class="section_content">
			<?php include_once( USAM_FILE_PATH.'/admin/templates/template-parts/product/products-related-table.php' ); ?>
		</div>	
		<div v-show="sectionTab=='variations'" class="product_variation_iframe section_content">
			<?php //include_once( usam_get_filepath_admin('templates/template-parts/product/products-variations-table.php') ); ?>
			
			<iframe src="<?php echo usam_url_admin_action('product_variations_table', ['product_id' => get_the_ID()]); ?>"></iframe>
		</div>	
		<div v-show="sectionTab=='price'"  class="section_content">
			<?php include_once( usam_get_filepath_admin('templates/template-parts/product/product-price-control.php') ); ?>
		</div> 
		<div v-show="sectionTab=='stock'"  class="section_content">
			<?php include_once( usam_get_filepath_admin('templates/template-parts/product/product-stock-control.php') ); ?>
		</div>
		<div v-show="sectionTab=='posts'"  class="section_content">
			<?php 
			usam_vue_module('form-table');
			include_once( usam_get_filepath_admin('templates/template-parts/product/posts-table.php') );
			?>
		</div>	
		<div v-show="sectionTab=='reputation'" class="section_content">
			<?php include_once( usam_get_filepath_admin('templates/template-parts/product/reputation-items.php') ); ?>		
		</div>		
		<div v-show="sectionTab=='seo'" class="section_content">
			<meta-seo :data="meta" inline-template>
				<?php include_once( usam_get_filepath_admin('templates/template-parts/post-meta.php') ); ?>
			</meta-seo>
		</div>		
		<?php
	}		
	
	function stock_control()
	{
		include( usam_get_filepath_admin('templates/template-parts/product/product-stock-control.php') );
	}
	
	function price_control()
	{
		include( usam_get_filepath_admin('templates/template-parts/product/product-price-control.php') );
	}	
			
	//Описание: фильтры товаров
	function product_filter()
	{		
		require_once( USAM_FILE_PATH . '/includes/media/colors.inc.php' );	
		require_once( USAM_FILE_PATH . '/includes/media/group_rgb.php' );	
//Описание: фильтры цветов товаров
		$p_colors = array();
		if ( !empty($p_meta['colors']) )
			$p_colors = array_flip($p_meta['colors']); 
		
		$out = "<div class = 'usam_product_filter_colors'><tr>";
		$out .= "<h4>".__('Фильтры цветов','usam')."</h4>";
		$out .= "<table id = 'table_colors'><tr>";
		foreach ( $group_rgb as $key => $value ) 	
		{			
			$out .= "<td data-color='$key' class = '".(isset($p_colors[$key])?"current":"")."'>
			<div class ='color' style ='background-color: rgb(".$value['red'].", ".$value['green'].", ".$value['blue'].");'></div>			
			<input type='hidden' value='$key' id='bgcolor_$key' name='pmeta[{$this->id}][product_metadata][colors][]' ".(isset($p_colors[$key])?"":"disabled")."></td>";
		}	
		$out .= "</tr></table></div>";
		echo $out;
	}
}
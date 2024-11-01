<?php
/**
 * Метабокс 
 */
class USAM_Meta
{	
	public function __construct() 
	{
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes') ); 
		add_action( 'save_post', [$this,'save_meta'], 10, 2 );
	}
	
	public static function add_meta_boxes()
	{				
		global $post;	
		if( $post->post_type != 'usam-product' )
		{
			$section_tabs = [];
			if( current_user_can('view_seo') )
				$section_tabs['seo'] = ['name' => 'SEO'];	
			if( $post->post_type == 'post' || $post->post_type == 'page' )
			{
				$section_tabs['additional_fields'] = ['name' => __( 'Дополнительные поля', 'usam')];					
				$section_tabs['shortcode'] = ['name' => __( 'Шорткоды', 'usam')];					
			}
			if( $section_tabs )				
			{
				$meta_box = [
					['id' => 'usam-possibilities', 'title' => __("Возможности","usam"), 'context' => 'normal', 'priority' => 'low', 'function' => ['USAM_Meta', 'meta_box'], 'type_page' => ['post','page']]
				];			
				foreach($meta_box as $box) 
					add_meta_box($box['id'], $box['title'], $box['function'], $box['type_page'], $box['context'], $box['priority']);
				add_action('admin_footer', function () use ($section_tabs){
					?>
					<script>				
						var sectiontabs = <?php echo json_encode( $section_tabs ); ?>;
					</script>	
					<?php
				});
			}
		}
	}
		
	public static function save_meta( $post_id, $post )
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
		{
			return $post_id;
		}
		if (!current_user_can('edit_post', $post_id)) 
			return $post_id;
		
		$post_status = get_post_status($post_id);
		if( $post_status != false  && $post_status != 'inherit') 
		{
			if ( isset($_POST['meta']) )
			{				
				if ( current_user_can('view_seo') )
				{
					foreach( $_POST['meta'] as $k => $value ) 
					{
						if( $k == 'exclude_sitemap' )
						{
							if ( $value )
								usam_update_post_meta($post_id, 'exclude_sitemap', 1);
							else
								usam_delete_post_meta($post_id, 'exclude_sitemap', 1);
						}
						elseif( $k == 'description' || $k ==  'opengraph_description' )
							update_post_meta($post_id, 'meta_'.$k, sanitize_textarea_field(stripslashes($value)));
						elseif( $k == 'title' || $k ==  'opengraph_title' )
							update_post_meta($post_id, 'meta_'.$k, sanitize_text_field(stripslashes($value)));
						else
							update_post_meta($post_id, 'meta_'.$k, sanitize_text_field($value));
					}
				}	
				unset($_POST['meta']);
			}		
		}
		$properties = usam_get_properties(['type' => $post->post_type]);		
		foreach($properties as $property) 
		{
			if ( isset($_POST['properties'][$property->code]) )
				usam_update_post_meta( $post->ID, $property->code, sanitize_textarea_field(stripslashes($_POST['properties'][$property->code])) );
			else
				usam_delete_post_meta( $post->ID, $property->code );
		}
		return $post_id;
	}	 

	public static function meta_box()
	{		
		global $post;
		$field_types = usam_get_field_types();	
		?>
		<site-slider>
			<template v-slot:body="sliderProps">
				<div class="section_tabs">
					<div class="section_tab" @click="sectionTab=k" :class="{'active': sectionTab==k}" v-for="(sectionData, k) in sectionTabs">{{sectionData.name}}</div>
				</div>
			</template>		
		</site-slider>
		<div v-show="sectionTab=='additional_fields'" class="section_content">
			<div class="tabs">
				<div class="tabs__name" @click="tab='list'" :class="{'active':tab=='list'}"><?php esc_html_e('Список', 'usam'); ?></div>
				<div class="tabs__name" @click="tab='control'" :class="{'active':tab=='control'}"><?php esc_html_e('Управление полями', 'usam'); ?></div>
			</div>
			<div class = "additional_fields__control" v-show="tab=='list'">			
				<div class="edit_form">			
					<label class ="edit_form__item" v-for="property in properties">
						<div class ="edit_form__item_name" v-html="property.name"></div>
						<div class ="edit_form__item_option">
							<input type='text' v-model="property.value" :name="'properties['+property.code+']'"/>
						</div>
					</label>
				</div>
			</div>	
			<div class = "additional_fields__control" v-show="tab=='control'">
				<a class="button additional_fields__button" @click="newdata=!newdata"><?php esc_html_e('Новое дополнительное поле', 'usam'); ?></a>
				<div class = "additional_fields__new" v-show="newdata">
					<div class="edit_form">			
						<label class ="edit_form__item">
							<div class ="edit_form__item_name"><?php _e( 'Название','usam'); ?>:</div>
							<div class ="edit_form__item_option">
								<input type='text' v-model="data.name"/>
							</div>
						</label>					
						<label class ="edit_form__item">
							<div class ="edit_form__item_name"><?php _e( 'Код','usam'); ?>:</div>
							<div class ="edit_form__item_option">
								<input type='text' v-model="data.code"/>
							</div>
						</label>					
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><?php esc_html_e( 'Тип', 'usam'); ?>:</div>
							<div class ="edit_form__item_option">
								<select v-model="data.field_type">				
									<?php 					
									foreach ( $field_types as $key => $name )			
									{									
										?><option value="<?php echo $key; ?>"><?php echo $name; ?></option><?php
									}									
									?>	
								</select>
							</div>
						</div>
						<label class ="edit_form__item">
							<div class ="edit_form__item_name"><?php _e( 'Сортировка','usam'); ?>:</div>
							<div class ="edit_form__item_option">
								<input type='text' v-model="data.sort"/>
							</div>
						</label>
						<div class ="edit_form__buttons">
							<button class="button button-primary" @click="add"><?php _e('Добавить дополнительное поле', 'usam'); ?></button>
						</div>
					</div>				
				</div>
				<table class="usam_list_table">
					<thead>
						<tr>
							<th><?php _e('Название', 'usam'); ?></th>
							<th><?php _e('Код', 'usam'); ?></th>
							<th><?php _e('Тип', 'usam'); ?></th>
							<th><?php _e('Сортировка', 'usam'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(property, k) in properties">
							<td v-html="property.name"></td>
							<td>{{property.code}}</td>
							<td>{{property.field_type}}</td>
							<td>{{property.sort}}</td>
							<td class="column-delete">
								<a class="action_delete" href="" @click="del(k, $event)"></a>
							</td>	
						</tr>
					</tbody>				
				</table>
			</div>
		</div>
		<div v-show="sectionTab=='seo'" class="section_content">
			<meta-seo :data="meta" inline-template>
				<?php include_once( usam_get_filepath_admin('templates/template-parts/post-meta.php') ); ?>
			</meta-seo>
		</div>	
		<div v-show="sectionTab=='shortcode'" class="section_content">
			<div class="edit_form">
				<div class='edit_form__item'>
					<div class='edit_form__item_name'><?php _e('Адрес сайта', 'usam'); ?></div>
					<div class='edit_form__item_option'><span class='js-copy-clipboard'>[company property="site_url"]</span></div>	
				</div>
				<div class='edit_form__item'>
					<div class='edit_form__item_name'><?php _e('Страница регистрации', 'usam'); ?></div>
					<div class='edit_form__item_option'><span class='js-copy-clipboard'>[company property="registration_page"]</span></div>	
				</div>
				<div class='edit_form__item'>
					<div class='edit_form__item_name'><?php _e('Страница контакты', 'usam'); ?></div>
					<div class='edit_form__item_option'><span class='js-copy-clipboard'>[company property="contact_page"]</span></div>	
				</div>				
			<?php 					
				$properties = usam_get_properties(['type' => 'company']);
				foreach( $properties as $property )
				{					
					?>
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><?php echo $property->name ?></div>
						<div class='edit_form__item_option'><span class='js-copy-clipboard'>[company property="<?php echo $property->code ?>"]</span></div>	
					</div>
					<?php
				}
			?>	
			</div>
		</div>
		<?php	
	}	 
 	
}
$meta = new USAM_Meta();
?>
<?php
/*
 *  Сохранение данных продукта в админском интерфейсе (сохранение цены, описания ...)
 */
class USAM_Save_Product
{	
	public $message_code = 0;
	
	public function __construct( ) 
	{			
		add_action( 'save_post', [$this, 'save_product'], 10, 2 );			
		add_filter( 'post_updated_messages', [$this, 'updated_messages'] );	
		add_filter( 'redirect_post_location', [$this, 'redirect_product_location'] , 99, 2);	
	}	
	
	function redirect_product_location( $location, $post_id ) 
	{
		if( $this->message_code )
			$location = add_query_arg( 'message', $this->message_code, $location );
		return $location;
	}
	
	/**
	 * Сообщения при обновлении продукта
	 */
	function updated_messages( $messages ) 
	{
		global $post, $post_ID;				
		
		$url = esc_url( usam_product_url() );
		$sku = usam_get_product_meta( $post_ID, 'sku' );
		$messages['usam-product'] = [
			0  => '', 
			1  => sprintf( __('Товар обновлен. <a href="%s">Посмотреть</a>', 'usam'), $url ),
			2  => __('Пользовательские поля обновлены.', 'usam'),
			3  => __('Пользовательские поля удалены.', 'usam'),
			4  => __('Товар обновлен.', 'usam'),			
			5  => isset($_GET['revision'] ) ? sprintf( __('Продукт восстановлен до ревизии от %s', 'usam'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __('Товар опубликован. <a href="%s">Посмотреть товар</a>', 'usam'), $url ),
			7  => __('Товар сохранен.', 'usam'),
			8  => sprintf( __('Представлены продуктов. <a target="_blank" href="%s">Просмотр товара</a>', 'usam'), esc_url( add_query_arg('preview', 'true', $url)) ),
			9  => sprintf( __('Публикация товара запланирована на: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Просмотреть товар</a>', 'usam'),
				date_i18n( __('M j, Y @ G:i', 'usam'), strtotime($post->post_date) ), $url ),
			10 => sprintf( __('Черновик товара обновлен. <a target="_blank" href="%s">Просмотр товара</a>', 'usam'), esc_url( add_query_arg( 'preview', 'true', $url ) ) ),
			11 => __('Артикул не может быть пустым. Публикация товара в этом случае не возможна. Укажите артикул!', 'usam'),
			12 => sprintf( __('Артикул "%s" уже существует. Укажите другой артикул!', 'usam'), $sku ),
		];
		return $messages;
	}	
		
	public function save_product( $product_id, $post )
	{	
		remove_action( 'save_post', array($this, 'save_product'), 10, 2 );// выполняется много раз;!!!!!!!!!!!!!		
		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || $post->post_type != 'usam-product' )
			return $product_id;	
		
		remove_action( 'set_object_terms', ['USAM_Product_Filters', 'usam_recalculate_price_product_set_terms'], 10, 6 );

		$post_data = $_POST;		
	
		$post_data = array_merge($post_data, (array)$post);			
		if ( !isset($post_data['productmeta'][$product_id]) )
			return $product_id;		
			
		if ( isset($post_data['pmeta']) )
		{
			$post_data['meta'] = $post_data['pmeta'][$product_id];
			unset($post_data['pmeta']);				
		} 
		if ( isset($post_data['postmeta']) )
			$post_data['postmeta'] = $post_data['postmeta'][$product_id];
		if ( isset($post_data['productmeta']) )
			$post_data['productmeta'] = $post_data['productmeta'][$product_id];	
		if ( isset($post_data['prices']) )
		{
			$post_data['prices'] = $post_data['prices'][$product_id];
		} 	
		if ( isset($post_data['attributes']) )
		{
			$attributes = $post_data['attributes'];	
			unset($post_data['attributes']);
		}
		else
			$attributes = array();	
		$post_data['meta']['product_metadata']['tabs'] = isset($_POST['custom_product_tab'])?array_map('intval', $_POST['custom_product_tab']):[];		
		$default_catalog = (int)get_option('usam_default_catalog');
		if ( $default_catalog && ( empty($post_data['tax_input']) || empty($post_data['tax_input']['usam-catalog']) || count($post_data['tax_input']['usam-catalog']) == 1 ) )
		{				
			$terms = get_the_terms( $product_id, 'usam-catalog' );			
			if ( empty($terms) )
				wp_set_object_terms( $product_id, $default_catalog, 'usam-catalog' );	
			
		}		
		//	  Сохраняет вход для различных мета в быстрых полях редактирования
		$custom_fields = array( 'weight', 'sku' );
		foreach ( $custom_fields as $meta_key ) 
		{			
			if ( isset($_REQUEST[$meta_key]) )
				switch ( $meta_key ) 
				{
					case 'weight':
						$post_data['productmeta']['weight'] = (float)$_REQUEST[$meta_key];
					break;			
					case 'sku':					
						$post_data['productmeta']['sku'] = sanitize_text_field($_REQUEST[$meta_key]);
					break;				
				}
		}					
		if ( $post->post_parent != 0 )	
			$product_type = 'variation';  // это вариация
		else
		{	
			global $wpdb;
			$product_variation = $wpdb->get_var("SELECT COUNT(ID) FROM `{$wpdb->posts}` WHERE `post_parent` = '$product_id' AND `post_type` = 'usam-product'");
			if ( $product_variation != 0 )									
				$product_type = 'variable'; // это главный товар вариации				
			else
				$product_type =  'simple'; // простой товар	
		}			
		$post_data['product_type'] = $product_type;			
		if ( !empty($_POST['components']) )
		{
			$post_data['components'] = array();
			foreach( $_POST['components']['component'] as $key => $component)
			{								
				if ( trim($component) != '' )
				{
					$quantity = !empty($_POST['components']['quantity'][$key])?$_POST['components']['quantity'][$key]:1;
					$id = !empty($_POST['components']['id'][$key])?$_POST['components']['id'][$key]:0;
					$post_data['components'][] = array('quantity' => $quantity, 'component' => $component, 'id' => $id);					
				}
			}
		}	
		if( current_user_can('view_showcases') )
		{
			$showcases = !empty($_POST['showcases']) ? array_map('intval', $_POST['showcases']) : [];
			usam_add_product_showcase($product_id, $showcases);	
		}
		if( isset($post_data['productmeta']['virtual']) )
			$type_sold = $post_data['productmeta']['virtual'];
		else
			$type_sold = usam_get_product_type_sold( $product_id );		
		
		$storages = usam_get_storages( );
		foreach ( $storages as $storage )
		{
			if ( !empty($post_data['not_limited']) || $type_sold != 'product' )
				$post_data['product_stock']['storage_'.$storage->id] = USAM_UNLIMITED_STOCK;
			else
				$post_data['product_stock']['storage_'.$storage->id] = isset($_REQUEST['storage_'.$storage->id][$product_id])?$_REQUEST['storage_'.$storage->id][$product_id]:0;
		}
		if ( isset($post_data['meta']['additional_unit']) )
		{
			$additional_units = $post_data['meta']['additional_unit'];
			$post_data['meta']['product_metadata']['additional_units'] = [];
			foreach ( $additional_units['unit'] as $key => $unit )
			{
				if ( $unit )
					$post_data['meta']['product_metadata']['additional_units'][] = ['unit' => usam_string_to_float($unit), 'unit_measure' => $additional_units['unit_measure'][$key]];
			}
			unset($post_data['meta']['additional_unit']);
		}	
		else
			$post_data['meta']['product_metadata']['additional_units'] = [];
		if ( $product_type == 'variable' ) 
		{
			$childs = usam_get_products(['post_parent' => $product_id, 'post_status' => 'all', 'numberposts' => -1, 'fields' => 'ids', 'stocks_cache' => false, 'prices_cache' => false]);
			foreach( $childs as $id )
				usam_update_product_meta( $id, 'virtual', $type_sold );
		}	
		if ( !isset($post_data['productmeta']['under_order']) )		
			$post_data['productmeta']['under_order'] = 0;			
		$product = new USAM_Product( $product_id );	
		$product->set( $post_data );
		$product->calculate_product_attributes( $attributes );			
		$product->insert_media();			
		if ( $product_type == 'variable' ) 
		{
			$product->calculate_price_variations();
			$product->calculation_of_inventory_product_variations();			
		}
		$product->save_product_meta( );	
		wp_set_object_terms( $product_id, [$product_type], 'usam-product_type' );		

		usam_update_attachments( $product_id, 'product' );		
		foreach(['similar', 'crosssell', 'options', 'posts'] as $list )
		{			
			if( isset($post_data[$list]) )
			{
				$product_ids = array_map('intval', (array)$post_data[$list]);
				usam_add_associated_products( $product_id, $product_ids, $list );	
			}	
			else
				usam_delete_associated_products($product_id, $list);				
		}
		do_action('usam_edit_product', $product_id, $post_data, $attributes );	
		return $product_id;
	}
	
	public function modify_upload_directory( $input )
	{
		$previous_subdir = $input['subdir'];
		$download_subdir = str_replace($input['basedir'], '', USAM_FILE_DIR);
		$input['path'] = substr_replace(str_replace($previous_subdir, $download_subdir, $input['path']),'',-1);
		$input['url'] = substr_replace(str_replace($previous_subdir, $download_subdir, $input['url']),'',-1);
		$input['subdir'] = substr_replace(str_replace($previous_subdir, $download_subdir, $input['subdir']),'',-1);
		return $input;
	}	
}	
new USAM_Save_Product();
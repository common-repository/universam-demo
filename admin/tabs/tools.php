<?php
require_once(USAM_FILE_PATH.'/includes/installer.class.php');
class USAM_Tab_Tools extends USAM_Page_Tab
{
	protected $views = ['simple'];
	public function get_title_tab()
	{			
		return __('Инструменты магазина', 'usam');	
	}
	
	public function get_message()
	{		
		$message = '';		
		if( isset($_REQUEST['update']) )
			$message = sprintf( _n( '%s товаров обновлен.', '%s товаров обновлено.', $_REQUEST['update'], 'usam'), $_REQUEST['update'] );
		if( isset($_REQUEST['delete_revision']) )
			$message = sprintf( _n( 'Удалена %s ревизия.', 'Удалено %s ревизий.', $_REQUEST['delete_revision'], 'usam'), $_REQUEST['delete_revision'] );	
		if( isset($_REQUEST['vk_publish_birthday']) )
			$message = sprintf( _n( 'Поздравлен %s человек.', 'Поздравлено %s людей.', $_REQUEST['vk_publish_birthday'], 'usam'), $_REQUEST['vk_publish_birthday'] );
		
		return $message;
	} 
	
	protected function action_processing()
	{
		global $wpdb;	
		
		set_time_limit(40000);		
		
	/*	if ( isset($_POST['calculate_color_filter_products']))
		{	//рассчитать фильтр цветов товаров				
			$args =  array( 'fields' => 'ids', 'cache_results' => false ,'update_post_meta_cache' => true, 'update_post_term_cache' => false );
			$products = usam_get_products( $args );							
			$i = 0;			
			foreach ( $products as $products_id )
			{		
				$attachment_ID = get_post_thumbnail_id( $products_id );
				$old_filepath = get_attached_file( $attachment_ID ); 
				
				if ( $old_filepath != '' )
				{
					// Узнаем цвет изображения
					require_once( USAM_FILE_PATH . '/includes/media/colors.inc.php' );	
					$pal = new GetMostCommonColors( $old_filepath );
					$colors = $pal->get_group_color( $colors_to_show );	
					
					$array = array_flip($colors); 
					unset ($array['white']); 
					$colors = array_flip($array); 
				
					usam_update_product_metadata( $products_id, 'colors', $colors );
					$i++;
				}
			}
			$this->sendback = add_query_arg( array( 'calculate_color' => $i ), $this->sendback );
			$this->redirect = true;			
		}		*/
			
		if ( isset($_POST['calculate_stock_products']))
		{
			usam_recalculate_stock_products( );					
		}			
		if ( isset($_POST['update_db']))
			USAM_Install::create_or_update_tables();
				
		if ( isset($_POST['db_charset']))
		{			
			$tables = $wpdb->get_results( "SHOW TABLES ", ARRAY_A);			
			foreach( $tables as $table )
			{
				foreach( $table as $table_name )
				{
					$table_status_data = $wpdb->get_row( "SHOW TABLE STATUS LIKE '$table_name'", ARRAY_A );					
					if ( $table_status_data['Collation'] != $wpdb->collate )
					{
						$wpdb->query( "ALTER TABLE ".$table_name." CONVERT TO CHARACTER SET {$wpdb->charset} COLLATE ".$wpdb->collate );
						$wpdb->query( "OPTIMIZE TABLE ".$table_name );
					}
				}
			}				
		}
		if ( isset($_POST['clear_stock_claims']))		
		{
			$products = $wpdb->get_col( "SELECT product_id FROM `".USAM_TABLE_STOCK_BALANCES."` WHERE `meta_key` LIKE 'reserve%'");			
			$wpdb->query( "UPDATE `".USAM_TABLE_STOCK_BALANCES."` SET `meta_value` = '0' WHERE `meta_key` LIKE 'reserve%'");	
			$wpdb->query( "UPDATE `".USAM_TABLE_SHIPPED_PRODUCTS."` SET `reserve` = '0' WHERE reserve!=0");	
			foreach ( $products as $product_id )
			{				
				usam_recalculate_stock_product( $product_id );
			}				
		}		
		if ( isset($_POST['clear_cron']))
		{	
			wp_clear_scheduled_hook( 'usam_tracker_send_event' );	
			$cron = _get_cron_array();
			foreach ( $cron as $timestamp => $cronhooks ) { 
				foreach ( (array) $cronhooks as $hook => $events ) 
				{ 
					wp_clear_scheduled_hook($hook); // очистить очередь		
				}
			}						
		}				
		if ( isset($_POST['delete_revision']))
		{
			$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'revision')");
			$wpdb->query("DELETE FROM $wpdb->term_relationships WHERE object_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'revision')");
			$result = $wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = 'revision'");
			$this->sendback = add_query_arg( array( 'delete_revision' => $result ), $this->sendback );
			$this->redirect = true;
		}
		if ( isset($_POST['delete_code_moysklad']))
		{
			$meta_key = 'code_moysklad';
			$meta_key = 'sku';
			$product_ids = $wpdb->get_col("SELECT t1.product_id FROM ".USAM_TABLE_PRODUCT_META." AS t1 LEFT JOIN ".USAM_TABLE_PRODUCT_META." AS t2 ON (t1.meta_value = t2.meta_value AND t1.meta_id < t2.meta_id && t2.meta_key=t1.meta_key) WHERE t1.meta_key='$meta_key' AND t2.meta_id IS NOT NULL");		
			if ( $product_ids )
				$result = usam_create_system_process( __('Удалить дубликаты товаров','usam'), ['post__in' => $product_ids, 'post_type' => 'usam-product'], 'delete_post', count($product_ids), 'remove_duplicate_products' );
		}	
		if ( isset($_POST['delete_productmeta']))
			$wpdb->query("DELETE FROM ".USAM_TABLE_PRODUCT_META." WHERE NOT EXISTS (SELECT ID FROM $wpdb->posts WHERE $wpdb->posts.ID=".USAM_TABLE_PRODUCT_META.".product_id)");
		if ( isset($_POST['increase_sales_product']))
			usam_process_calculate_increase_sales_product( null, 0 );
		if ( isset($_POST['create_virtual_pages']))
			USAM_Install::create_virtual_pages();
		if ( isset($_POST['create_system_pages']))
			USAM_Install::create_system_pages();
		
		if ( isset($_POST['update_company_data']))
		{
			$companies = usam_get_companies(['fields' => 'ids']);
			usam_create_system_process( __("Обновление данных компаний","usam" ), array(), 'update_company_data', count($companies), 'update_company_data' );
		}
		if ( isset($_POST['conversion_to_webp']))
		{		
			$posts = count(usam_get_posts(['post_type' => 'attachment', 'post_parent__not_in' => 0, 'fields' => 'ids', 'nopaging' => true]));	
			usam_create_system_process( __("Конвертирование в WebP фотографий","usam" ), [], 'conversion_to_webp', $posts, 'conversion_to_webp' );
		}
		if ( isset($_POST['check_files_database']))
		{		
			$upload_dir = wp_upload_dir();
			$files = 0;			
			$args = [];
			if ( $handle = opendir($upload_dir['basedir']) )
			{ 
				while (false !== ($file = readdir($handle))) 
				{  
					$dir = $upload_dir['basedir'].'/'.$file;
					if ( is_dir($dir) && $file != '.' && $file != '..' && is_numeric($file) && strlen($file) == 4 ) 
					{ 
						if ( $handle2 = opendir($dir) )
						{ 
							while (false !== ($file2 = readdir($handle2))) 
							{  
								if ( is_dir($dir.'/'.$file2) && $file2 != '.' && $file2 != '..' ) 
								{
									$count = count(list_files( $dir.'/'.$file2 ));
									$args[] = ['dir' => $dir.'/'.$file2, 'number' => 0];
									$files += $count;
								}
							}
						}						
					}
				}
			}
			usam_create_system_process( __("Очистить медиабиблиотеку от не существующих в базе файлов","usam" ), $args, 'check_files_database', $files, 'check_files_database' );				
		}			
		if ( isset($_POST['regenerate_thumbnails']))
		{		
			$posts = count(usam_get_posts(['post_type' => 'attachment', 'post_parent__not_in' => 0, 'fields' => 'ids', 'nopaging' => true]));	
			usam_create_system_process( __("Пересоздания миниатюры фотографий","usam" ), ['regenerate' => ['delete_unregistered' => true]], 'regenerate_thumbnails', $posts, 'regenerate_thumbnails' );
		}		
		if ( isset($_POST['fix_thumbnail_sizes']))
		{		
			$posts = count(usam_get_posts(['post_type' => 'attachment', 'post_parent__not_in' => 0, 'fields' => 'ids', 'nopaging' => true]));	
			usam_create_system_process( __("Исправить размеры миниатюр","usam" ), array(), 'fix_thumbnail_sizes', $posts, 'fix_thumbnail_sizes' );
		}			
		if ( isset($_POST['geocode_contact']))
		{
			$meta_query = array( );					
			$meta_query[] = array('key' => 'latitude', 'compare' => 'NOT EXISTS' );		
			$meta_query[] = array('key' => 'address', 'compare' => '!=', 'value' => '', 'relation' => 'AND');
			//$meta_query[] = array('key' => 'location', 'compare' => '!=', 'value' => '', 'relation' => 'AND');
			
			$contacts = usam_get_contacts( array( 'fields' => 'id', 'meta_query' => $meta_query, 'number' => 10, 'source' => 'all' ) );
			foreach ( $contacts as $contact ) 
			{
				$address = usam_get_full_contact_address( $contact->id );
				if ( !empty($address) )
				{
					$results = usam_get_geocode_map( $address );
					usam_update_contact_metadata( $contact->id, 'latitude', $results[1] );
					usam_update_contact_metadata( $contact->id, 'longitude', $results[0] );
				}
			}
		}
		if ( isset($_POST['formatting_phone_number_contacts']))
		{
			$contact_meta = $wpdb->get_results("SELECT * FROM `".USAM_TABLE_CONTACT_META."` WHERE `meta_key` LIKE '%phone%' AND `meta_value` LIKE '8%'");
			foreach ( $contact_meta as $meta ) 
			{
				if ( strlen($meta->meta_value) == 11 )
				{
					$value = preg_replace('/^8/',7, $meta->meta_value);					
					usam_update_contact_metadata( $meta->contact_id, $meta->meta_key, $value );
				}
			}
		}		
		if ( isset($_POST['delete_empty_contacts']))
		{
			$args = ['fields' => 'count', 'number' => 1, 'meta_cache' => true, 'user_id' => 0, 'company_id' => 0, 'conditions' => [['key' => 'appeal', 'value' => '', 'compare' => '='], ['key' => 'number_orders', 'value' => '', 'compare' => '=']]];	
			$count = usam_get_contacts( $args );
			usam_create_system_process( __('Удалить пустые контакты','usam'), [], 'delete_empty_contacts', $count, 'delete_empty_contacts' );	
		}
	}
	
	public function display() 
	{		
		global $wpdb;
		?>			
		<form method='post' action='' id='usam-page-tabs-form'>
			<?php $this->nonce_field(); ?>			
			<div class = "container">
				<h3><?php esc_html_e( 'Cron', 'usam'); ?></h3>
				<table class = "usam_tools">				
					<tr>
						<td><input type="submit" name="clear_cron" class="button button-primary" value="<?php _e( 'Очистить', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Очистить запланированные задания (cron)', 'usam'); ?></td>				
					</tr>					
				</table>
			</div>
			<div class = "container">
				<h3><?php esc_html_e( 'Страницы', 'usam'); ?></h3>
				<table class = "usam_tools">				
					<tr>
						<td><input type="submit" name="create_system_pages" class="button button-primary" value="<?php _e( 'Сделать', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Создать все системные страницы', 'usam'); ?></td>				
					</tr>		
					<tr>
						<td><input type="submit" name="create_virtual_pages" class="button button-primary" value="<?php _e( 'Сделать', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Все виртуальные страницы сделать обычными', 'usam'); ?></td>				
					</tr>						
				</table>
			</div>			
			<div class = "container">
				<h3><?php esc_html_e( 'База данных', 'usam'); ?></h3>
				<table class = "usam_tools">				
					<tr>
						<td><input type="submit" name="update_db" class="button button-primary" value="<?php _e( 'Обновить', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Обновить таблицы', 'usam'); ?></td>				
					</tr>	
					<tr>
						<td><input type="submit" name="db_charset" class="button button-primary" value="<?php _e( 'Изменить', 'usam') ?>"/></td>
						<td><?php printf( __( 'Изменить кодировку всех таблиц БД на %s', 'usam'), $wpdb->collate); ?></td>				
					</tr>							
					<tr>
						<td><input type="submit" name="clear_stock_claims" class="button button-primary" value="<?php _e( 'Убрать', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Убрать резерв товарных остатков', 'usam'); ?></td>				
					</tr>						
					<tr>
						<td>
							<input type="submit" name="delete_revision" id="delete_revision" class="button button-primary" value="<?php _e( 'Удалить', 'usam') ?>"/>
						</td>
						<td><?php esc_html_e( 'Удалить ревизии постов, записей и товаров', 'usam'); ?></td>				
					</tr>	
					<tr>		
						<td>
							<input type="submit" name="delete_productmeta" id="delete_productmeta" class="button button-primary" value="<?php _e( 'Удалить', 'usam') ?>"/>
						</td>
						<td><?php esc_html_e( 'Удалить несуществующие записи товаров в productmeta', 'usam'); ?></td>				
					</tr>		
					<tr>		
						<td>
							<input type="submit" name="delete_code_moysklad" id="delete_code_moysklad" class="button button-primary" value="<?php _e( 'Удалить', 'usam') ?>"/>
						</td>
						<td><?php esc_html_e( 'Удалить дубликаты товаров по артикулу', 'usam'); ?></td>				
					</tr>						
				</table>
			</div>
			<div class = "container">
				<h3><?php esc_html_e( 'Товары', 'usam'); ?></h3>
				<table class = "usam_tools">					
					<tr>
						<td><input type="submit" name="calculate_color_filter_products" id="calculate_color_filter_products" class="button button-primary" value="<?php _e( 'Рассчитать', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Рассчитать фильтр цветов товаров', 'usam'); ?></td>				
					</tr>		
					<tr>
						<td><input type="submit" name="calculate_stock_products" id="calculate_stock_products" class="button button-primary" value="<?php _e( 'Пересчитать', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Пересчитать остатки', 'usam'); ?></td>				
					</tr>						
					<tr>
						<td><input type="submit" name="increase_sales_product" id="increase_sales_product" class="button button-primary" value="<?php _e( 'Расчитать', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Расчет товаров для увеличения продаж', 'usam'); ?></td>				
					</tr>	
					<tr>
						<td><input type="submit" name="check_product_availability" class="button button-primary" value="<?php _e( 'Проверить', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Проверить наличие продукта на сайте поставщика', 'usam'); ?></td>				
					</tr>				
					<tr>
						<td><input type="submit" name="conversion_to_webp" class="button button-primary" value="<?php _e( 'Конвертировать', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Конвертировать в WebP', 'usam'); ?></td>				
					</tr>	
					<tr>
						<td><input type="submit" name="check_files_database" class="button button-primary" value="<?php _e( 'Очистить', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Очистить медиабиблиотеку от не существующих в базе файлов', 'usam'); ?></td>				
					</tr>						
					<tr>
						<td><input type="submit" name="regenerate_thumbnails" class="button button-primary" value="<?php _e( 'Пересоздать', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Пересоздания миниатюры фотографий', 'usam'); ?></td>				
					</tr>					
					<tr>
						<td><input type="submit" name="fix_thumbnail_sizes" class="button button-primary" value="<?php _e( 'Исправить', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Исправить размеры миниатюр', 'usam'); ?></td>				
					</tr>					
				</table>
			</div>			
			<div class = "container">
				<h3><?php esc_html_e( 'CRM', 'usam'); ?></h3>
				<table class = "usam_tools">								
					<tr>
						<td><input type="submit" name="update_company_data" class="button button-primary" value="<?php _e( 'Обновить', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Обновить данные компаний', 'usam'); ?></td>				
					</tr>
					<tr>
						<td><input type="submit" name="delete_empty_contacts" class="button button-primary" value="<?php _e( 'Удалить', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Удалить пустые контакты', 'usam'); ?></td>				
					</tr>					
					<tr>
						<td><input type="submit" name="formatting_phone_number_contacts" class="button button-primary" value="<?php _e( 'Обновить', 'usam') ?>"/></td>
						<td><?php esc_html_e( 'Форматировать телефонные номера контактов в стандарт 7xxxxxxxxxx', 'usam'); ?></td>				
					</tr>						
				</table>
			</div>			
			<input type='hidden' value='true' name='tols' />
		</form>
		<?php		
	}
}
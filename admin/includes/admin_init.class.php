<?php
/**
 *  ========================== Обработка запросов ================================================
 */ 	
require_once( USAM_FILE_PATH .'/includes/ajax.php' );
 
class USAM_Admin_Init_Nonce extends USAM_Callback
{
	protected $query = 'usam_admin_action';
	
	public function __construct() 
	{	
		if ( isset($_REQUEST[$this->query]) )
			add_action( 'admin_init', array($this, 'handler') );		
	}	
	
	/**
	 * Получить бланк
	 */
	function controller_edit_blank()
	{		
		if ( isset($_GET['blank']))
		{			
			$blank = sanitize_title($_REQUEST['blank']);			
			$company = absint($_REQUEST['company']);
			echo usam_get_edit_printing_forms( $blank, $company );
			exit;
		}
	}	
	
	function controller_display_items_list( )
	{			
		if ( !empty($_REQUEST['screen']) )
		{
			$_SERVER['REQUEST_URI'] = remove_query_arg( '_wp_http_referer', $_SERVER['REQUEST_URI'] );
			set_current_screen();		
			$list = !empty($_GET['list'])?sanitize_title($_REQUEST['list']):'company';
			$screen = sanitize_title($_REQUEST['screen']);		
			wp_iframe( 'usam_get_display_table_lists', $list, $screen ); 
		} 
		exit;
	}	
			
	function controller_display_mail_body()
	{	
		if ( !empty($_REQUEST['email_id'] ) )
		{					
			$id = absint( $_REQUEST['email_id'] );				
			$email = usam_get_email( $id );		
			if( !empty($email) )
			{
				echo '
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
					<link rel="stylesheet" href="'. USAM_URL . '/admin/assets/css/email-editor-style.css'.'" type="text/css">
				</head>';
				echo preg_replace_callback("/\n>+/u", 'usam_email_replace_body', $email['body'] );
			}
		}
		else
		{
			echo "<span class='dashicons no_messages'></span>";
		}
		exit;
	}
	
	function controller_send_sms()
	{	
		if ( !empty($_REQUEST['message']) && !empty($_REQUEST['phone']) )
		{			
			$args = array( 'phone' => $_REQUEST['phone'] );				
			$args['message'] = nl2br( sanitize_textarea_field(stripslashes($_REQUEST['message'])) );		
			$links = [];
			if ( !empty($_REQUEST['object_type']) && !empty($_REQUEST['object_id']) )
				$links[] = ['object_id' => $_REQUEST['object_id'], 'object_type' => $_REQUEST['object_type']];	
			if ( usam_add_send_sms( $args, $links ) )				
				$this->sendback = add_query_arg( 'send_sms', 1, $this->sendback );	
			else
				$this->sendback = add_query_arg( 'send_sms', 0, $this->sendback ); 
		}	
	}	
	
	function controller_send_email()
	{		
		if ( !empty($_REQUEST['from_email']) && is_numeric($_REQUEST['from_email']) )
		{			
			$message = !empty($_REQUEST['message'])?stripcslashes($_REQUEST['message']):'';//nl2br
			$title = !empty($_REQUEST['title'])?sanitize_text_field($_REQUEST['title']):get_bloginfo('name');					
			$mailbox_id = absint($_REQUEST['from_email']);		
			
			$message = str_replace( array( "\n\r" ), '<br>', $message );
			
			$files = array();
			if ( !empty($_REQUEST['fileupload']) )	
			{ // Загруженные пользователем файлы						
				foreach ($_REQUEST['fileupload'] as $file_id ) 
					$files[] = array( 'file_id' => $file_id );	
			} 	
			elseif ( !empty($_REQUEST['file']) )	
			{ 			
				$ids = array_map('intval', (array)$_REQUEST['file']);
				foreach (usam_get_files( array('include' => $ids) ) as $file ) 
				{			
					$insert = (array)$file;					
					$upload_dir = usam_get_upload_dir( $file->type, $file->object_id );	
					$sanitized_title = wp_unique_filename( $upload_dir, $insert['name'] );					
					if ( !@copy(USAM_UPLOAD_DIR.$insert['file_path'], $upload_dir.$sanitized_title) )		
						break;
								
					$insert['file_path'] = $upload_dir.$sanitized_title;				
					$file_id = usam_insert_file( $insert );								
					$files[] = array( 'file_id' => $file_id );	
				}
			} 			
			if ( $message != '' )
			{
				$style = new USAM_Mail_Styling( $mailbox_id );
				$message = $style->get_message( $message );
			} 
			$insert_email = ['body' => $message, 'title' => $title, 'mailbox_id' => $mailbox_id];		
			
			$links = [];
			if ( !empty($_REQUEST['object_type']) && !empty($_REQUEST['object_id']) )
				$links[] = ['object_id' => absint($_REQUEST['object_id']), 'object_type' => sanitize_title($_REQUEST['object_type'])];
			
			$result = 1;
			if ( !empty($_REQUEST['to_companies']) )
			{				
				$companies = array_map('intval', $_REQUEST['to_companies']);
				foreach ($companies as $id ) 
				{								
					$to_email = usam_get_company_metadata($id, 'email' );
					if ( !empty($to_email) )
					{
						$company = usam_get_company( $id );
						$insert_email['to_name'] = $company['name'];						
						$insert_email['to_email'] = $to_email;
						usam_send_mail($insert_email, $files, $links);
					}					
				}						
			}
			elseif ( !empty($_REQUEST['to_contacts']) )
			{				
				$contacts = array_map('intval', $_REQUEST['to_contacts']);
				foreach ($contacts as $id ) 
				{					
					$to_email = usam_get_contact_metadata($id, 'email' );
					if ( !empty($to_email) )
					{
						$contact = usam_get_contact( $id );
						$insert_email['to_name'] = $contact['appeal'];
						$insert_email['to_email'] = $to_email;
						usam_send_mail($insert_email, $files, $links);
					}
				}						
			}			
			elseif ( !empty($_REQUEST['to_orders']) )
			{				
				$orders = array_map('intval', $_REQUEST['to_orders']);
				foreach ($orders as $id ) 
				{					
					$to_email = usam_get_order_customerdata( $id, 'email' );
					if ( !empty($to_email) )
					{
						$insert_email['to_email'] = $to_email;						
						$order_shortcode = new USAM_Order_Shortcode( $id );				
						$insert_email['body'] = $order_shortcode->get_html( $insert_email['body'] );							
						usam_send_mail($insert_email, $files, $links);
					}
				}				
			}		
			elseif ( !empty($_REQUEST['to']) )
			{
				$insert_email['to_email'] = sanitize_email($_REQUEST['to']);
				usam_send_mail($insert_email, $files, $links);
			}
			else
				$result = 0;	
			$this->sendback = add_query_arg( 'send_email', $result, $this->sendback ); 
		}
	}	
/**
 *  ========================== Товар ================================================
*/ 	
	/**
	* Перенести товар в архив
	*/
	protected function controller_set_product_status_archive( ) 
	{		
		if ( isset($_GET['id']) )
		{
			$product_id = absint($_GET['id']);
			wp_update_post(['ID' => $product_id, 'post_status' => 'archive']);
		}
	}		
	/**
	* Функции и действия для дублирования товаров
	*/
	function controller_duplicate_product() 
	{
		$id = absint( $_GET['id'] );
		$post = get_post( $id );
		if ( isset($post ) && $post != null ) 
		{			
			$new_id = usam_duplicate_product_process( $post );
			//$this->sendback = add_query_arg( array( 'duplicated' => 1 ), get_edit_post_link( $new_id ) );
			$this->sendback = add_query_arg( array('duplicated' => 1), $this->sendback );
		} 
		else 
			wp_die( __('К сожалению, мы не можем дублировать этот Товар, поскольку он не найден в базе данных, проверьте ID этого товара:', 'usam').' '.$id );	
	}
			
	function controller_product_variations_table() 
	{		
		require_once( USAM_FILE_PATH . '/admin/includes/product/product-variations-page.class.php' );
		$page = new USAM_Product_Variations_Page();
		$page->display();
		exit;
	}
}
new USAM_Admin_Init_Nonce();
?>
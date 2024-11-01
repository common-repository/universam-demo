<?php
/**
 * Запросы магазина
 */		
require_once( USAM_FILE_PATH .'/includes/ajax.php' );
class USAM_Processing_Requests extends USAM_Callback
{	
	protected $sendback;
	protected $verify_nonce = false;
	protected $query = 'usam_action';
	public function __construct() 
	{				
		add_action( 'init', array($this, 'handler'), 11 );
	}
	
	function _verify_nonce( ) 
	{
		$option_work = get_option("usam_option_work","simple");				
		if ( $option_work == 'central' )
		{
			return true;
		}	
		$result = $this->verify_nonce( $this->action.'_nonce' );
		if ( is_wp_error( $result ) )			
			return true;
		
		return false;
	}	
	
	function controller_feed( ) 
	{		
		require_once( USAM_FILE_PATH . '/includes/exchange/feed.class.php');
		$id = (int)$_REQUEST['id'];
		$platform_instance = usam_get_trading_platforms_class( $id );		
		if ( is_object($platform_instance) )			
			$platform_instance->upload_file( ); 
		exit;
	}	
			
	function controller_buy_product()
	{				
		if ( !isset($_REQUEST['product_id']) )
			return;
				
		$product_id = apply_filters( 'usam_add_to_cart_product_id', (int)$_REQUEST['product_id'] );
		$product = get_post( $product_id );	
		$product_id = usam_get_post_id_main_site( $product_id );
				
		$post_type_object = get_post_type_object( 'usam-product' );
		$permitted_post_statuses = current_user_can( $post_type_object->cap->edit_posts ) ? ['private', 'draft', 'pending', 'publish'] : ['publish'];
		
		$parameters = array();
		if ( ! in_array($product->post_status, $permitted_post_statuses) || 'usam-product' != $product->post_type )
			return false;		
		
		if ( isset($_REQUEST['usam_quantity']) && $_REQUEST['usam_quantity'] > 0 )		
			$parameters['quantity'] = (int)$_REQUEST['usam_quantity'];		
		
		$cart = usam_core_setup_cart();
		$cart->empty_cart( );
		$cart->add_product_basket( $product_id, $parameters ); 
		$cart->recalculate();
		$this->sendback	= usam_get_url_system_page('basket'); 
	}	
	
	function controller_printed_form()
	{
		$access = false;
		if ( isset($_REQUEST['form']) )
		{			
			$printed_form = sanitize_title($_REQUEST['form']);
			if ( current_user_can( 'universam_settings' ) )
				$access = true;
			else
			{
				$id = absint($_REQUEST['id']);			
				$data = usam_get_data_printing_forms( $printed_form );
				if ( $data['object_name'] == 'order' || current_user_can( 'print_'.$data['object_name'] ) )
				{
					if ( usam_check_access_to_view_document( $id, $data['object_name'] ) )
						$access = true;			
				}
			}
			if ( $access )
			{
				echo usam_get_printing_forms( $printed_form );
				exit;
			}
		}
		wp_die( __('Доступ закрыт', 'usam') );
	}
	
	function controller_printed_form_to_excel()
	{				
		if ( isset($_REQUEST['form']) )
		{			
			$id = absint($_REQUEST['id']);				
			$printed_form = sanitize_title($_REQUEST['form']);	
			$filename = 'document_'.$id;			
			$data = usam_get_data_printing_forms( $printed_form );
			if ( $data['object_name'] == 'order' || current_user_can( 'print_'.$data['object_name'] ) )
			{
				if ( !usam_check_access_to_view_document( $id, $data['object_name'] ) )
					wp_die( __('Доступ закрыт', 'usam') );
				if ( isset($data['object_type']) && $data['object_type'] == 'document' )
					$filename = usam_get_document_full_name( $data['object_name'], $id );	
				$writer = usam_get_export_form_to_xlsx( $printed_form, $id );		
				header('Content-Type: application/xlsx');
				header('Content-Disposition: inline; filename="'.$filename.'.xlsx"' );
				$writer->save('php://output');
				exit;
			}
		}	
		wp_die( __('Доступ закрыт', 'usam') );
	}
			
	function controller_printed_form_to_pdf()
	{
		if ( isset($_REQUEST['form']) )
		{		
			$id = absint($_REQUEST['id']);				
			$printed_form = sanitize_title($_REQUEST['form']);	
			$filename = 'document_'.$id;		
			$data = usam_get_data_printing_forms( $printed_form );
			if ( $data['object_name'] == 'order' || current_user_can( 'print_'.$data['object_name'] ) )
			{ 
				if ( !usam_check_access_to_view_document( $id, $data['object_name'] ) )
					wp_die( __('Доступ закрыт', 'usam') );
				if ( isset($data['object_type']) && $data['object_type'] == 'document' )
					$filename = usam_get_document_full_name( $data['object_name'], $id );
				$html = usam_get_export_form_to_pdf( $printed_form, $id );		
				header('Content-Type: application/pdf');
				header('Content-Disposition: inline; filename="'.$filename.'.pdf"' );
				echo $html;
				exit;
			}
		}
		wp_die( __('Доступ закрыт', 'usam') );
	}
		
	//Письмо открыто
	function controller_email_open()
	{	
		$mail_id = (int)$_REQUEST['mail_id'];
		if ( $mail_id > 0)
		{ 
			usam_update_email_metadata( $mail_id, 'opened_at', date( "Y-m-d H:i:s" ) );		
			
			$mail = usam_get_email( $mail_id );
			if ( $mail )
				usam_update_location_subscriber( $mail['to_email'] );				
		}
		header("Content-type: image/png");
		readfile(USAM_CORE_IMAGES_PATH.'/mailtemplate/picsel.gif');		
		exit;
	}
	
	//Переход из рассылки. Рассылка открыта
	function controller_mailing_open()
	{	
		if ( !empty($_REQUEST['stat_id']) )
		{		
			$stat_id = (int)$_REQUEST['stat_id'];	
			$stat = usam_get_user_stat_mailing( $stat_id );	
			if ( $stat )
			{
				$mailing = usam_get_newsletter( $stat['newsletter_id'] );
				if ( !empty($mailing) && empty($stat['opened_at']) )  
				{					
					$update_user_stat = array( 'opened_at' => date( "Y-m-d H:i:s" ), 'status' => 2,  );
					usam_update_user_stat_newsletter( $stat_id, $update_user_stat );							
						
					$mailing['number_opened']++;
					usam_update_newsletter( $stat['newsletter_id'], $mailing );		
					usam_update_location_subscriber( $stat['communication'] );		
				}
			}
		}		
		header("Content-type: image/png");
		readfile(USAM_CORE_IMAGES_PATH.'/mailtemplate/picsel.gif');	
		exit;		
	}
	
	// На ссылку в рассылке нажали
	function controller_m_click()
	{	
		if ( !empty($_REQUEST['stat_id']) )
		{	
			$stat_id = (int)$_REQUEST['stat_id'];
			$stat = usam_get_user_stat_mailing( $stat_id );			
			$mailing = usam_get_newsletter( $stat['newsletter_id'] );
			if ( !empty($mailing) )  
			{ 
				$stat_id = absint($_REQUEST['stat_id']);			
				$mailing['number_clicked']++;				
				if ( empty($stat['opened_at']) )  
				{
					$mailing['number_opened']++;
				}	
				usam_update_newsletter( $stat['newsletter_id'], $mailing );	
							
				$stat['status'] = 2;
				$stat['clicked']++;
				$stat['opened_at'] = !empty($stat['opened_at'])?$stat['opened_at']:date( "Y-m-d H:i:s" );
				usam_update_user_stat_newsletter( $stat_id, $stat );					
				usam_update_location_subscriber( $stat['communication'] );
			}
		}	
	}
	
	// Отписаться в рассылке
	function controller_mailing_unsub()
	{	
		$this->sendback = usam_get_user_account_url('your-subscribed');
		if ( !empty($_REQUEST['stat_id']) )
		{		
			$stat_id = (int)$_REQUEST['stat_id'];	
			$stat = usam_get_user_stat_mailing( $stat_id );			
			$mailing = usam_get_newsletter( $stat['newsletter_id'] );			
			if ( !empty($mailing) )  
			{	
				if ( empty($stat['unsub']) )  
				{
					$update_user_stat = array( 'status' => 2, 'unsub' => 1 );
					$update_user_stat['opened_at'] = !empty($stat['opened_at'])?$stat['opened_at']:date( "Y-m-d H:i:s" );
					usam_update_user_stat_newsletter( $stat_id, $update_user_stat );
				
					$mailing['number_unsub']++;
					usam_update_newsletter( $stat['newsletter_id'], $mailing );
				}
				require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );
				$lists = usam_get_mailing_lists(['fields' => 'id', 'newsletter_id' => $stat['newsletter_id']]);				
				foreach ( $lists as $list_id )
				{
					usam_set_subscriber_lists(['communication' => $stat['communication'], 'status' => 2, 'id' => $list_id]);	
				}	
				usam_update_mailing_statuses(['include' => $lists]);				
				usam_update_location_subscriber( $stat['communication'] );
				$this->sendback = add_query_arg(['email' => $stat['communication'], 'subscribe' => 0], $this->sendback );	
			}
		}	
	}
		
	// Скачать прайс лист
	function controller_download_price() 
	{		
		if ( !empty($_REQUEST['id']) )
		{
			$id = (int)$_REQUEST['id'];			
			require_once( USAM_FILE_PATH . '/includes/product/price_list.class.php' );
			$class = new USAM_Price_List( $id );
			$class->customer_pricelist_download();
		}		
	}		
}
new USAM_Processing_Requests();
?>
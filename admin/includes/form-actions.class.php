<?php
final class USAM_Form_Actions
{	
	private static $id = null;
	private static $action = null;
	private static $request_data = [];
	
	public static function start( $action = null, $form_name = null )
	{
		self::$request_data = $_POST;		
		if ( $form_name == null )
		{ 
			if ( isset(self::$request_data['form_name']) )
				$form_name = sanitize_title(self::$request_data['form_name']);
			else
				return false;
		}			
		if ( $action == null )
		{
			if ( isset(self::$request_data['action']) )
				self::$action = sanitize_title(self::$request_data['action']);
			else
				return false;
		}		
		else
			self::$action = $action;		
		
		if ( isset(self::$request_data['id']) )
			self::$id = sanitize_text_field(self::$request_data['id']);	
	
		$method = 'controller_'.$form_name;	
		if ( method_exists(__CLASS__, $method) )
			return self::$method();		
		else
			return apply_filters( 'usam_form_action', false, $form_name, self::$action, self::$id, self::$request_data);
	}		
	
	private static function actions_import( $type, $metas = array() ) 
	{ 	
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
		switch( self::$action )
		{
			case 'save': 					
				$new_rule['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));	
				$new_rule['headings'] = sanitize_title(self::$request_data['headings']);	
				$new_rule['type'] = $type;							
				$new_rule['encoding'] = sanitize_text_field(self::$request_data['encoding']);		
				$new_rule['splitting_array'] = isset(self::$request_data['splitting_array'])?sanitize_text_field(self::$request_data['splitting_array']):'|';
				$new_rule['type_file'] = sanitize_text_field(self::$request_data['type_file']);				
				$new_rule['exchange_option'] = isset(self::$request_data['exchange_option'])?sanitize_text_field(self::$request_data['exchange_option']):'';
				if ( $new_rule['exchange_option'] == 'folder' )
					$new_rule['file_data'] = absint(self::$request_data['folder']);	
				elseif ( isset(self::$request_data['file_data']) )
					$new_rule['file_data'] = sanitize_text_field(stripcslashes(self::$request_data['file_data']));	
				else
					$new_rule['file_data'] = '';									
				$new_rule['time'] = isset(self::$request_data['time'])?date( "H:i", strtotime(self::$request_data['time'])):'';
				$new_rule['schedule'] = isset(self::$request_data['schedule'])?sanitize_text_field(self::$request_data['schedule']):'';
				$new_rule['start_line'] = isset(self::$request_data['start_line'])?absint(self::$request_data['start_line']):'';
				$new_rule['end_line'] = isset(self::$request_data['end_line'])?absint(self::$request_data['end_line']):'';				
				if ( self::$id )
					usam_update_exchange_rule( self::$id, $new_rule );		
				else				
					self::$id = usam_insert_exchange_rule( $new_rule );
				
				$metas['columns'] = [];
				$metas['columns2'] = [];				
				$metas['compare_columns'] = [];
				$metas['compare_columns_value'] = [];				
				if ( !empty(self::$request_data['columns']) )
				{ 
					foreach ( self::$request_data['columns'] as $key => $name )
					{
						if ( !empty($name) )
						{
							$key = sanitize_text_field($key);
							if ( self::$request_data['headings'] )
								$metas['columns'][$name] = isset(self::$request_data['columns_name'][$key])?sanitize_text_field(self::$request_data['columns_name'][$key]):'';
							else
								$metas['columns'][$key] = sanitize_text_field($name);							
							$metas['compare_columns'][$key] = isset(self::$request_data['_columns_comparison'][$key])?sanitize_text_field(self::$request_data['_columns_comparison'][$key]):'';
							$metas['compare_columns_value'][$key] = isset(self::$request_data['_compare_columns_value'][$key])?sanitize_text_field(self::$request_data['_compare_columns_value'][$key]):'';
						}
					}
				}
				if ( !empty(self::$request_data['columns2']) )
				{ 
					foreach ( self::$request_data['columns2'] as $key => $name )
					{
						if ( !empty($name) )
						{
							$key = sanitize_text_field($key);
							if ( self::$request_data['headings'] )
								$metas['columns2'][$name] = isset(self::$request_data['columns_name'][$key])?sanitize_text_field(self::$request_data['columns_name'][$key]):'';
							else
								$metas['columns2'][$key] = sanitize_text_field($name);
						}
					}
				}				
				if ( $new_rule['exchange_option'] == 'email' )
				{
					$metas['to_email'] = sanitize_text_field(self::$request_data['to_email']);
					$metas['subject'] = sanitize_text_field(stripcslashes(self::$request_data['subject']));
				}
				$metas['weekday'] = isset(self::$request_data['weekday'])?array_map('intval', self::$request_data['weekday']):[];
				$metas['delete_file'] = !empty(self::$request_data['delete_file'])?1:0;	
				foreach ( $metas as $key => $value )
				{
					usam_update_exchange_rule_metadata( self::$id, $key, $value );
					usam_insert_change_history(['object_id' => self::$id, 'object_type' => 'exchange_rule', 'operation' => 'update', 'field' => $key]);	
				}
				$hook = 'usam_exchange_rules_cron';
				wp_clear_scheduled_hook($hook, [self::$id]);
				if ( $new_rule['schedule'] && $new_rule['exchange_option'] !== '' && $new_rule['exchange_option'] !== 'email' )
				{	
					$ve = get_option('gmt_offset') < 0 ? '+' : '-';		
					wp_schedule_event( strtotime($new_rule['time'].' ' . $ve . get_option('gmt_offset') . ' HOURS'), $new_rule['schedule'], $hook, [self::$id] );
				}
				return array( 'id' => self::$id ); 
			break;	
		}
	}
	
	private static function save_rule_coupon( $type, $new_rule = array() ) 
	{	
		$new_rule['title'] = sanitize_text_field(stripslashes(self::$request_data['name']));
		$new_rule['discount'] = (float)self::$request_data['discount'];	
		$new_rule['discount_type'] = sanitize_text_field(self::$request_data['discount_type']);
		$new_rule['format'] = !empty(self::$request_data['format'])?sanitize_text_field(self::$request_data['format']):'';		
		$new_rule['type_format'] = !empty(self::$request_data['type_format'])?sanitize_title(self::$request_data['type_format']):'n';			
		$new_rule['active'] = !empty(self::$request_data['active'])?1:0;
		$new_rule['rule_type'] = $type;
		$new_rule['roles'] = isset(self::$request_data['roles'])?stripslashes_deep(self::$request_data['roles']):array();
		$new_rule['sales_area'] = isset(self::$request_data['sales_area'])?array_map('intval', self::$request_data['sales_area']):array();
		$new_rule['message'] = sanitize_textarea_field(stripslashes(self::$request_data['message']));
		$new_rule['subject'] = sanitize_text_field(stripslashes(self::$request_data['subject']));
		$new_rule['sms_message'] = sanitize_textarea_field(stripslashes(self::$request_data['sms_message']));
	
		$coupon = array();				
		$coupon['max_is_used'] = !empty(self::$request_data['max_is_used'])?absint(self::$request_data['max_is_used']):0;		
		$coupon['start_date']       = usam_get_datepicker('start');
		$coupon['end_date']      = usam_get_datepicker('end');	
		if ( !empty(self::$request_data['bonuses_author']) )
		{
			$coupon['amount_bonuses_author'] = preg_replace("/[^0-9\\.,]/", '', self::$request_data['bonuses_author']);					
			$coupon['amount_bonuses_author'] .= self::$request_data['bonus_calculation_option']?'%':'';			
		}
		else
			$coupon['amount_bonuses_author'] = 0;

		if ( self::$id != null )	
		{
			$rule = usam_get_data(self::$id, 'usam_coupons_roles');
			$new_rule['coupon_id'] = $rule['coupon_id'];
			usam_update_coupon( $rule['coupon_id'], $coupon );
			usam_edit_data( $new_rule, self::$id, 'usam_coupons_roles' );	
		}
		else			
		{			
			$coupon['coupon_code']   = '888';	
			$coupon['value']         = 0;
			$coupon['is_percentage'] = 0;
			$coupon['description']   = '';						
			$coupon['coupon_type']   = 'rule';		
			
			$new_rule['coupon_id'] = usam_insert_coupon( $coupon );				
			$new_rule['date_insert'] = date( "Y-m-d H:i:s" );		
			self::$id = usam_add_data( $new_rule, 'usam_coupons_roles' );				
		}		
		$conditions = self::get_rules_basket_conditions( );	
		usam_update_coupon_metadata( $new_rule['coupon_id'], 'conditions', $conditions );
		return array( 'id' => self::$id ); 
	}
	
	private static function get_rules_basket_conditions( ) 
	{				
		require_once( USAM_FILE_PATH . '/admin/includes/rules/basket_discount_rules.class.php' );	
			
		$rules_work_basket = new USAM_Basket_Discount_Rules( );	
		return $rules_work_basket->get_rules_basket_conditions(  );	
	}
	
	private static function save_coupon( $type ) 
	{	
		switch( self::$action )
		{			
			case 'save':	
				$new['coupon_code'] = sanitize_text_field(self::$request_data['coupon_code']);
				$new['user_id']     = !empty(self::$request_data['user'])?absint(self::$request_data['user']):0;
				$new['description'] = sanitize_textarea_field(stripslashes(self::$request_data['description']));	
				if ( !empty(self::$request_data['bonuses_author']) )
				{
					$new['amount_bonuses_author'] = preg_replace("/[^0-9\\.,]/", '', self::$request_data['bonuses_author']);			
					$new['amount_bonuses_author'] .= self::$request_data['bonus_calculation_option']?'%':'';
				}
				else
					$new['amount_bonuses_author'] = 0;			
				$new['value']         = absint(self::$request_data['value']);		
				$new['start_date']    = usam_get_datepicker('start');
				$new['end_date']      = usam_get_datepicker('end');	
				$new['active']        = !empty(self::$request_data['active'])?1:0;	
				$new['coupon_type']   = $type;	
				$new['is_percentage'] = !empty(self::$request_data['is_percentage'])?1:0;	
				$new['action']        = !empty(self::$request_data['coupon_action'])?sanitize_title(self::$request_data['coupon_action']):'b';		
				$new['max_is_used']   = !empty(self::$request_data['max_is_used']) && $type != 'certificate' ? absint(self::$request_data['max_is_used']):0;	
				if ( self::$id )	
					usam_update_coupon( self::$id, $new );			
				else
					self::$id = usam_insert_coupon( $new );
				$conditions = self::get_rules_basket_conditions( );	
				usam_update_coupon_metadata( self::$id, 'conditions', $conditions );	
				return array( 'id' => self::$id ); 
			break;					
		} 
	}
		
	private static function generate_coupon( $type ) 
	{		
		switch( self::$action )
		{	
			case 'generate':			
				$new['user_id']      = !empty(self::$request_data['user'])?absint(self::$request_data['user']):0;
				if ( !empty(self::$request_data['bonuses_author']) )
				{
					$new['amount_bonuses_author'] = preg_replace("/[^0-9\\.,]/", '', self::$request_data['bonuses_author']);		
					$new['amount_bonuses_author'] .= self::$request_data['bonus_calculation_option']?'%':'';
				}
				else
					$new['amount_bonuses_author'] = 0;
				$new['value']         = absint(self::$request_data['value']);
				$new['description']   = sanitize_textarea_field(stripslashes(self::$request_data['description']));	
				$new['start_date']    = usam_get_datepicker('start');
				$new['end_date']        = usam_get_datepicker('end');					
				$new['active']        = !empty(self::$request_data['active'])?1:0;	
				$new['coupon_type']   = $type;	
				$new['is_percentage'] = !empty(self::$request_data['is_percentage'])?1:0;	
				$new['action']        = !empty(self::$request_data['coupon_action'])?sanitize_title(self::$request_data['coupon_action']):'b';		
				$new['max_is_used']      = !empty(self::$request_data['max_is_used']) && $type != 'certificate' ? absint(self::$request_data['max_is_used']):0;	

				$quantity    = !empty(self::$request_data['quantity'])?absint(self::$request_data['quantity']):1;		
				$format    = !empty(self::$request_data['format'])?sanitize_text_field(self::$request_data['format']):'';		
				$type_format = !empty(self::$request_data['type_format'])?sanitize_title(self::$request_data['type_format']):'n';		
				
				$ids = array();
				$conditions = self::get_rules_basket_conditions(  );
				for ($i=0; $i < $quantity; $i++)
				{					
					$new['coupon_code'] = usam_generate_coupon_code( $format, $type_format );						
					$_coupon = new USAM_Coupon( $new );
					$_coupon->save();					
					$ids[] = $_coupon->get('id');
					usam_update_coupon_metadata( $_coupon->get('id'), 'conditions', $conditions );		
				} 
				return array( 'ready' => count($ids) );
			break;					
		} 
	}
	
	private static function save_shipped_document( $document_id, $document, $products )
	{ 						
		if ( $document_id )
		{ 	
			$shipped_document = usam_get_shipped_document( $document_id );			
			if ( !usam_check_document_access( $shipped_document, 'shipped', 'edit' ) )
				return 0;
			if ( empty($shipped_document['manager_id']) )
				$document['manager_id'] = get_current_user_id();
			usam_update_shipped_document($document_id, $document, $products);
		}
		else
		{			
			if ( !current_user_can('add_shipped') )
				return 0;	
			$document['manager_id'] = get_current_user_id();
			$document_id = usam_insert_shipped_document($document, $products, ['document_id' => $document['order_id'], 'document_type' => 'order']);
		}
		if ( $document_id && !empty($document['metas']) )	
		{ 
			foreach( $document['metas'] as $key => $value )
			{
				if ( $value )
					usam_update_shipped_document_metadata( $document_id, $key, $value );
				else
					usam_delete_shipped_document_metadata( $document_id, $key );
			}
		}	
		$current_time = time(); 
				
		$document_date = usam_get_datepicker('document_date_'.$document_id);
		usam_update_shipped_document_metadata( $document_id, 'external_document_date', $document_date );
								
		$date_delivery = usam_get_datepicker('date_delivery_'.$document_id);	
		usam_update_shipped_document_metadata( $document_id, 'date_delivery', $date_delivery );
		
		$readiness_date = usam_get_shipped_document_metadata( $document_id, 'readiness_date' );
		$new_readiness_date = usam_get_datepicker('readiness_date_'.$document_id);	
		usam_update_shipped_document_metadata( $document_id, 'readiness_date', $new_readiness_date );
					
		if ( $new_readiness_date && $readiness_date != $new_readiness_date && strtotime($new_readiness_date) > $current_time )
		{				
			do_action( 'usam_order_is_collected', $document_id );
			
			$shipped_document = usam_get_shipped_document( $document_id );
			$order = usam_get_order( $shipped_document['order_id'] );
			
			$event = array( 'title' => sprintf( __('Готовность отгрузки заказа %s', 'usam'), $shipped_document['order_id'] ) );
			$event['description'] = sprintf( __('Отгрузка заказа №%s будет готова в %s', 'usam'), $shipped_document['order_id'], usam_local_date( $new_readiness_date ) );
			$event['user_id'] = $order['manager_id'];		
			$event_id = usam_insert_system_event( $event, array('object_type' => 'shipped_document', 'object_id' => $document_id), $new_readiness_date );
		} 	
		return $document_id;
	}
	
	private static function save_payment_document( $document_id, $payment )
	{ 
		if ( !empty($payment['sum']) )
		{						
			$payment['date_payed'] = usam_get_datepicker('date_payed-'.$document_id);	
			if ( $document_id )
			{				
				$document = usam_get_payment_document( $document_id );			
				if ( !usam_check_document_access( $document, 'payment', 'edit' ) )
					return false;
				if ( empty($document['manager_id']) )
					$payment['manager_id'] = get_current_user_id();
				
				$payment['sum'] = usam_string_to_float( $payment['sum'] );
				usam_update_payment_document( $document_id, $payment );
			}
			else
			{						
				if ( !current_user_can('add_payment') )
					return false;
				$payment['manager_id'] = get_current_user_id();
				$document_id = usam_insert_payment_document( $payment, ['document_id' => $payment['document_id'], 'document_type' => 'order']);
			} 
			return $document_id;
		}
		return false;
	}
	
	private static function save_meta( $type, $code, $values )
	{	
		return usam_save_meta( self::$id, $type, $code, $values );
	}
		
	private static function save_group_property( $type )
	{ 
		$new['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));		
		$new['code'] = !empty(self::$request_data['code'])?sanitize_title(self::$request_data['code']):sanitize_title(self::$request_data['name']);
		$new['sort'] = !empty(self::$request_data['sort'])?absint(self::$request_data['sort']):0;	
		$new['parent_id'] = !empty(self::$request_data['parent_id'])?absint(self::$request_data['parent_id']):0;			
		$new['type'] = $type;					
		if ( self::$id )		
		{			
			$property = new USAM_Property_Group( self::$id );
			$property->set( $new );
			$property->save();
		}
		else			
		{					
			$property = new USAM_Property_Group( $new );
			$property->save();
			self::$id = $property->get('id');						
		}
		return ['id' => self::$id]; 
	}	
	
	private static function save_document_status( $type )
	{ 
		$data['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));			
		$data['description'] = sanitize_textarea_field(stripcslashes(self::$request_data['description']));	
		$data['short_name'] = sanitize_text_field(stripcslashes(self::$request_data['short_name']));	
		$data['internalname'] = sanitize_title(self::$request_data['internalname']);		
		$data['sort'] = isset(self::$request_data['sort'])?(int)self::$request_data['sort']:100;
		$data['visibility'] = isset(self::$request_data['visibility'])?self::$request_data['visibility']:1;
		if ( isset(self::$request_data['pay']) )
			$data['pay'] = !empty(self::$request_data['pay'])?1:0;
		$data['active'] = !empty(self::$request_data['active'])?1:0;					
		if ( isset(self::$request_data['close']) )
			$data['close'] = !empty(self::$request_data['close'])?1:0;	
		if ( isset(self::$request_data['subject']) )
			$data['subject_email'] = sanitize_textarea_field(stripcslashes(self::$request_data['subject']));
		if ( isset(self::$request_data['email']) )
			$data['email'] = stripcslashes(self::$request_data['email']);	
		if ( isset(self::$request_data['sms']) )
			$data['sms'] = sanitize_text_field(stripcslashes(self::$request_data['sms']));			
		$data['color'] = sanitize_text_field(stripcslashes(trim(self::$request_data['color'])));	
		$data['text_color'] = sanitize_text_field(stripcslashes(trim(self::$request_data['text_color'])));	
		$data['type'] = $type;	
		if ( self::$id != null )
		{
			$data['number'] = usam_get_number_objects_status( $data['internalname'], $data['type'] );
			usam_update_object_status( self::$id, $data );			
		}		
		else
			self::$id = usam_insert_object_status( $data );		
		
		$statuses = !empty(self::$request_data['statuses'])?array_map('sanitize_title', self::$request_data['statuses']):array();
		usam_save_array_metadata(self::$id, 'object_status', 'statuses', $statuses);
		return ['id' => self::$id]; 
	}
	
	private static function save_property( $type )
	{ 
		$new['name'] = sanitize_text_field(stripslashes(self::$request_data['name']));	
		$new['code'] = !empty(self::$request_data['code'])?sanitize_title(self::$request_data['code']):sanitize_title(self::$request_data['name']);		
		if ( isset(self::$request_data['description']) )
			$new['description'] = stripcslashes(self::$request_data['description']);			
		$new['field_type'] = sanitize_text_field(self::$request_data['field_type']);	
		$new['mask'] = sanitize_text_field(self::$request_data['mask']);	
		$new['active'] = !empty(self::$request_data['active'])?absint(self::$request_data['active']):0;	
		$new['sort'] = !empty(self::$request_data['sort'])?absint(self::$request_data['sort']):0;	
		$new['mandatory'] = !empty(self::$request_data['mandatory'])?1:0;	
		$new['show_staff'] = !empty(self::$request_data['show_staff'])?1:0;			
		$new['group'] = !empty(self::$request_data['group'])?sanitize_title(self::$request_data['group']):0;		
		$new['type'] = $type;	
		$add = false;
		if ( self::$id )	
		{				
			usam_update_property( self::$id, $new );
		}
		else			
		{					
			self::$id = usam_insert_property( $new );
			$add = true;
		}	
		if ( ($new['field_type'] == 'select' || $new['field_type'] == 'checkbox' || $new['field_type'] == 'radio') && !empty( self::$request_data['options']) )
		{ 				
			$options = array();
			foreach ( self::$request_data['options']['name'] as $key => $name )	
			{
				if ( !empty($name) )
				{
					$code = !empty(self::$request_data['options']['code'][$key])?sanitize_title(self::$request_data['options']['code'][$key]):sanitize_title($name);
					$group = !empty(self::$request_data['options']['group'][$key]) && $new['group'] != self::$request_data['options']['group'][$key] ?sanitize_title(self::$request_data['options']['group'][$key]):0;		
					$options[] = array( 'name' => sanitize_text_field(stripslashes($name)), 'code' => $code, 'group' => $group );									
				}
			}												
		}		
		if ( !empty($options) )
			usam_update_property_metadata(self::$id, 'options', $options );	
		elseif ( !$add )
			usam_delete_property_metadata(self::$id, 'options' );	
		
		$roles = !empty(self::$request_data['roles']) && !empty(self::$request_data['limit_visibility'])?array_map('sanitize_title', self::$request_data['roles']):array();		
		if ( !empty($roles) )
			usam_save_array_metadata(self::$id, 'property', 'role', $roles);		
		elseif ( !$add )
			usam_delete_property_metadata(self::$id, 'role' );
		if( $type == 'contact' || $type == 'company' )
		{
			$profile = !empty(self::$request_data['profile'])?1:0;
			usam_update_property_metadata(self::$id, 'profile', $profile );
		}
		if ( !empty(self::$request_data['metas']) )
		{
			foreach ( self::$request_data['metas'] as $key => $value )			
			{
				if ( !empty($value) )
					usam_update_property_metadata(self::$id, $key, $value );	
				else
					usam_delete_property_metadata(self::$id, $key );					
			}
		}
		return array( 'id' => self::$id ); 
	}
	
	private static function controller_bonus_card() 
	{			
		switch( self::$action )
		{			
			case 'save':				
				if ( empty(self::$request_data['user_id']) )
				{
					usam_set_user_screen_error( __('Клиент не указан.','usam') );
					return false; 
				}
				$new_rule['user_id'] = absint(self::$request_data['user_id']);	
				$new_rule['code'] = absint(self::$request_data['code']);	
				$new_rule['percent'] = (float)self::$request_data['percent'];
				$new_rule['status'] = sanitize_title(self::$request_data['status']);									
				if ( self::$id != null )
				{
					usam_update_bonus_card( self::$id, $new_rule );
				}
				else
				{
					global $wpdb;
					$code = $wpdb->get_var( "SELECT code FROM ".USAM_TABLE_BONUS_CARDS." WHERE user_id ='".$new_rule['user_id']."'" );
					if ( $code )
					{
						usam_set_user_screen_error( sprintf(__('У клиента уже есть карта %s.','usam'), "<a href='".admin_url('admin.php?page=crm&tab=contacts&table=bonus_cards&view=table&form=view&form_name=bonus_card&id='.$code)."'>$code</a>" ));
						return false; 
					}
					$code = $wpdb->get_var( "SELECT code FROM ".USAM_TABLE_BONUS_CARDS." WHERE code ='".$new_rule['code']."'" );
					if ( $code )
					{
						usam_set_user_screen_error( sprintf(__('Карта с номером %s уже существует. Укажите другой номер.','usam'), "<a href='".admin_url('admin.php?page=crm&tab=contacts&table=bonus_cards&view=table&form=view&form_name=bonus_card&id='.$code)."'>$code</a>" ));
						return false; 
					}
					self::$id = usam_insert_bonus_card( $new_rule );
					if ( !self::$id )
					{
						usam_set_user_screen_error( __('Ошибка создания карты.','usam') );
						return false; 
					}
				}					
			break;				
		}
		return array( 'id' => self::$id ); 
	}	
		
	private static function controller_customer_account() 
	{			
		require_once( USAM_FILE_PATH . '/includes/customer/customer_account.class.php' );
		switch( self::$action )
		{			
			case 'save':				
				if ( empty(self::$request_data['user_id']) )
				{
					usam_set_user_screen_error( __('Клиент не указан.','usam') );
					return false; 
				}
				$new_rule['user_id'] = absint(self::$request_data['user_id']);	
				$new_rule['status'] = sanitize_title(self::$request_data['status']);									
				if ( self::$id != null )
				{
					usam_update_customer_account( self::$id, $new_rule );
				}
				else
				{				
					self::$id = usam_insert_customer_account( $new_rule );
					if ( !self::$id )
					{
						usam_set_user_screen_error( __('Ошибка создания клиентского счета.','usam') );
						return false; 
					}
				}					
			break;				
		}
		return array( 'id' => self::$id ); 
	}
				
	private static function controller_group() 
	{			
		switch( self::$action )
		{			
			case 'save':				
				$type = !empty(self::$request_data['type'])?sanitize_title(self::$request_data['type']):'';		
				require_once( USAM_FILE_PATH . '/includes/crm/group.class.php' );
				$new['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));		
				$new['code'] = !empty(self::$request_data['code'])?sanitize_title(self::$request_data['code']):sanitize_title(self::$request_data['name']);
				$new['sort'] = !empty(self::$request_data['sort'])?absint(self::$request_data['sort']):0;		
				$new['type'] = $type;					
				if ( self::$id )	
					usam_update_group( self::$id, $new );
				else	
					self::$id = usam_insert_group( $new );
				return ['id' => self::$id]; 		
			break;			
		}
		return array( 'id' => self::$id ); 
	}
	
	private static function controller_company_property_group() 
	{			
		switch( self::$action )
		{			
			case 'save':				
				self::save_group_property( 'company' );
			break;				
		}
		return array( 'id' => self::$id ); 
	}
	
	private static function controller_webform_property_group() 
	{			
		switch( self::$action )
		{			
			case 'save':				
				self::save_group_property( 'webform' );
			break;				
		}
		return array( 'id' => self::$id ); 
	}
	
	private static function controller_contact_property_group() 
	{			
		switch( self::$action )
		{			
			case 'save':				
				self::save_group_property( 'contact' );
			break;				
		}
		return ['id' => self::$id]; 
	}
	
	private static function controller_employee_property_group() 
	{			
		switch( self::$action )
		{			
			case 'save':				
				self::save_group_property( 'employee' );
			break;				
		}
		return ['id' => self::$id]; 
	}
	
	private static function controller_employee_property() 
	{		
		switch( self::$action )
		{			
			case 'save':				
				self::save_property( 'employee' );
			break;				
		}
		return ['id' => self::$id]; 
	}	
	
	private static function controller_webform_property() 
	{		
		switch( self::$action )
		{			
			case 'save':				
				self::save_property( 'webform' );
				
				$connection = !empty(self::$request_data['connection'])?sanitize_title(self::$request_data['connection']):'';						
				usam_update_property_metadata(self::$id, 'connection', $connection );
			break;				
		}
		return array( 'id' => self::$id ); 
	}
	
	private static function controller_company_property() 
	{		
		switch( self::$action )
		{			
			case 'save':				
				self::save_property( 'company' );
				$profile = !empty(self::$request_data['profile'])?1:0;			
				usam_update_property_metadata(self::$id, 'profile', $profile );
				
				$registration = !empty(self::$request_data['registration'])?1:0;			
				usam_update_property_metadata(self::$id, 'registration', $registration );
			break;				
		}
		return array( 'id' => self::$id ); 
	}	
	
	private static function controller_contact_property() 
	{		
		switch( self::$action )
		{			
			case 'save':				
				self::save_property( 'contact' );
				$profile = !empty(self::$request_data['profile'])?1:0;			
				usam_update_property_metadata(self::$id, 'profile', $profile );
				
				$registration = !empty(self::$request_data['registration'])?1:0;			
				usam_update_property_metadata(self::$id, 'registration', $registration );			
			break;				
		}
		return array( 'id' => self::$id ); 
	}
	
	private static function controller_order_property() 
	{		
		switch( self::$action )
		{			
			case 'save':				
				self::save_property( 'order' );
				
				$connection = !empty(self::$request_data['connection'])?sanitize_title(self::$request_data['connection']):'';						
				usam_update_property_metadata(self::$id, 'connection', $connection );	
				
				$delivery_address = !empty(self::$request_data['delivery_address'])?1:0;					
				usam_update_property_metadata(self::$id, 'delivery_address', $delivery_address );	
				
				$delivery_contact = !empty(self::$request_data['delivery_contact'])?1:0;			
				usam_update_property_metadata(self::$id, 'delivery_contact', $delivery_contact );				
				
				$payer_address = !empty(self::$request_data['payer_address'])?1:0;			
				usam_update_property_metadata(self::$id, 'payer_address', $payer_address );	
				
				$payer = !empty(self::$request_data['payer'])?1:0;			
				usam_update_property_metadata(self::$id, 'payer', $payer );					
						
				$types_products = !empty(self::$request_data['types_products'])?array_map('sanitize_title', self::$request_data['types_products']):array();
				$shippings = !empty(self::$request_data['selected_shipping'])?array_map('intval', self::$request_data['selected_shipping']):array();	
				$delivery_option = !empty(self::$request_data['delivery_option'])?array_map('intval', self::$request_data['delivery_option']):array();
				$category = isset(self::$request_data['tax_input']['usam-category'])?array_map('intval', self::$request_data['tax_input']['usam-category']):array();
								
				usam_save_array_metadata(self::$id, 'property', 'category', $category);
				usam_save_array_metadata(self::$id, 'property', 'types_products', $types_products);
				usam_save_array_metadata(self::$id, 'property', 'shipping', $shippings);
				usam_save_array_metadata(self::$id, 'property', 'delivery_option', $delivery_option);				
			break;				
		}
		return array( 'id' => self::$id ); 
	}
		
	private static function controller_company() 
	{		
		switch( self::$action )
		{			
			case 'save':				
				self::save_company();
			break; 				
		}
		return array( 'id' => self::$id ); 
	}	
		
	private static function controller_company_division() 
	{
		switch( self::$action )
		{			
			case 'save':				
				self::save_company( );
			break; 				
		}
		return array( 'id' => self::$id ); 
	}
		
	private static function controller_calendar() 
	{		
		$user_id = get_current_user_id();		
		switch( self::$action )
		{			
			case 'save': 					
				$new['sort'] = absint(self::$request_data['sort']);
				$new['name'] =  sanitize_text_field(stripcslashes(self::$request_data['name']));
				if ( self::$id != null )	
				{				
					$data = usam_get_data(self::$id, 'usam_calendars');
					
					if ( $data['user_id'] !== $user_id )
						return;	
					
					usam_edit_data( $new, self::$id, 'usam_calendars' );								
				}
				else			
				{								
					$new['user_id'] = $user_id;				
					self::$id = usam_add_data( $new, 'usam_calendars' );		
				}
			break;				
		}
		return array( 'id' => self::$id ); 
	}
	
	private static function controller_price() 
	{		
		switch( self::$action )
		{			
			case 'save': 				
				$new['title'] = sanitize_text_field(self::$request_data['name']);
				$new['base_type'] = isset(self::$request_data['base_type'])?sanitize_title(self::$request_data['base_type']):'0';	
				$new['code'] = !empty(self::$request_data['code'])?sanitize_title(strtolower(self::$request_data['code'])):strtolower(usam_rand_string( 6 ));					
				$new['type'] = isset(self::$request_data['type']) && self::$request_data['type'] == 'P'?'P':'R';		
				$new['locations'] = !empty(self::$request_data['locations']) ? array_map('intval', self::$request_data['locations']) : array();
				$new['roles'] = !empty(self::$request_data['roles']) ? stripslashes_deep(self::$request_data['roles']) : array();													
				$new['underprice'] =  !empty(self::$request_data['underprice'])?usam_string_to_float(self::$request_data['underprice']):0;		
				$new['sort'] = absint(self::$request_data['sort']);
				$new['external_code'] = isset(self::$request_data['external_code'])?sanitize_text_field(self::$request_data['external_code']):'';	
				$new['currency'] = isset(self::$request_data['currency'])?sanitize_text_field(self::$request_data['currency']):'RUB';	
									
				if ( empty(self::$request_data['rounding']) )
					$new['rounding'] = 0;
				else
				{
					$rounding = explode('.', sanitize_text_field(self::$request_data['rounding']));
					$new['rounding'] = !empty($rounding[1])?strlen($rounding[1]):"-".strlen($rounding[0]);
				}			
				$new['date'] = date( "Y-m-d H:i:s" );			
				$new['available'] = !empty(self::$request_data['available'])?1:0;		
				
				$prices = usam_get_prices( array('type' => 'all') );	
				foreach ( $prices as $value )
				{
					if ( $value['code'] == $new['code'] && $value['id'] != self::$id )
					{							
						usam_set_user_screen_error( __('Код цены не уникальный. Измените код цены.','usam') );
					}
				}			
				if ( self::$id != null )	
				{ 							
					$default = usam_get_data(self::$id, 'usam_type_prices');
					$new = array_merge( $default, $new );
					$result = usam_edit_data( $new, self::$id, 'usam_type_prices' );	
					do_action( 'usam_type_price_update', self::$id );				
				}
				else							
					self::$id = usam_insert_type_price( $new );	
				if ( self::$id )
					usam_recalculate_price_products();
			break;				
		}
		return array( 'id' => self::$id ); 
	}
		
	private static function controller_distance() 
	{		
		switch( self::$action )
		{			
			case 'save': 
				if( !empty(self::$request_data['distance']) )
				{			
					$distance = absint(self::$request_data['distance']);
					$from_location_id = absint(self::$request_data['from_location_id']);	
					$to_location_id = absint(self::$request_data['to_location_id']);	
					
					usam_set_locations_distance( $from_location_id, $to_location_id, $distance );
				}
			break;				
		}
		return array( 'id' => self::$id ); 
	}
	
	private static function controller_country() 
	{		
		switch( self::$action )
		{			
			case 'save': 
				if( !empty(self::$request_data['code']) )
				{						
					$data['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));		
					$data['code'] = sanitize_text_field(self::$request_data['code']);	
					$data['numerical'] = absint(self::$request_data['numerical']);
					$data['currency'] = sanitize_text_field(self::$request_data['currency']);
					$data['phone_code'] = sanitize_text_field(self::$request_data['phone_code']);
					$data['language'] = sanitize_text_field(self::$request_data['language']);
					$data['language_code'] = sanitize_text_field(self::$request_data['language_code']);					
					$data['location_id'] = sanitize_text_field(self::$request_data['location_id']);					
					if ( self::$id != null )
					{	
						$currencies = new USAM_Country( self::$id );
						$currencies->set( $data );				
					}				
					else
					{							
						$currencies = new USAM_Country( $data );		
					}
					$currencies->save( );	
					self::$id = $currencies->get('code');						
				}	
			break;				
		}
		return array( 'id' => self::$id ); 
	}	
	
	private static function controller_currency() 
	{		
		switch( self::$action )
		{			
			case 'save': 
				if( !empty(self::$request_data['code']) )
				{	
					$data['code'] = sanitize_text_field(self::$request_data['code']);	
					$data['numerical'] = absint(self::$request_data['numerical']);
					$data['symbol'] = sanitize_text_field(self::$request_data['symbol']);
					$data['symbol_html'] = sanitize_text_field(self::$request_data['symbol_html']);
					$data['display_currency'] = !empty(self::$request_data['display_currency'])?1:0;			
					$data['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));
					if ( self::$id != null )
					{	
						usam_update_currency( self::$id, $data );				
					}				
					else
					{							
						self::$id = usam_insert_currency( $data );		
					}					
				}	
			break;				
		}
		return array( 'id' => self::$id ); 
	}	
	
	private static function controller_sales_area() 
	{		
		switch( self::$action )
		{			
			case 'save': 
				$new = array();
				$new['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));				
				$new['locations'] = isset(self::$request_data['locations']) ? array_map('intval', self::$request_data['locations']) : array();
				
				if ( empty($new['locations']) )
					return false;
						
				if ( self::$id != null )	
				{
					usam_edit_data( $new, self::$id, 'usam_sales_area' );	
				}
				else			
				{			
					self::$id = usam_add_data( $new, 'usam_sales_area' );				
				}	
			break;				
		}
		return array( 'id' => self::$id ); 
	}

	private static function controller_language() 
	{		
		switch( self::$action )
		{			
			case 'save': 
				$new = array();
				$new['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));				
				$new['code'] = strtolower(sanitize_text_field(self::$request_data['code']));		
				$new['sort'] = absint(self::$request_data['sort']);			
				if ( self::$id != null )	
				{
					usam_edit_data( $new, self::$id, 'usam_languages' );	
				}
				else			
				{			
					self::$id = usam_add_data( $new, 'usam_languages' );				
				}	
			break;				
		}
		return array( 'id' => self::$id ); 
	}	
	
	private static function controller_location_type() 
	{		
		global $wpdb;			
		switch( self::$action )
		{			
			case 'save': 
				if( !empty(self::$request_data['name']) )
				{			
					$format = array( 'name' => '%s', 'code' => '%s', 'sort' => '%d' );
					$new['name'] = sanitize_text_field(stripslashes(self::$request_data['name']));
					$new['code'] = sanitize_text_field(self::$request_data['code']);
					$new['sort'] = absint(self::$request_data['sort']);	
					if ( self::$id != null )
					{		
						$where = array( 'id' => '%d');
						$new = apply_filters( 'usam_location_update_data', $new );							
						$wpdb->update( USAM_TABLE_LOCATION_TYPE, $new, $where, $format, array( self::$id ) );	
					} 
					else 
					{   
						$new = apply_filters( 'usam_location_insert_data', $new );					
							
						$wpdb->insert( USAM_TABLE_LOCATION_TYPE, $new, $format );	
						self::$id = $wpdb->insert_id;
					} 						
				}
			break;				
		}
		return array( 'id' => self::$id ); 
	}	
	
	private static function controller_location() 
	{		
		global $wpdb;	
		switch( self::$action )
		{			
			case 'save': 
				if( !empty(self::$request_data['name']) )
				{			
					$new['name'] = sanitize_text_field(self::$request_data['name']);
					$new['sort'] = absint(self::$request_data['sort']);
					$new['code'] = sanitize_title(self::$request_data['code']);			
					$parent = absint(self::$request_data['location']);			
					if ( $parent )
					{ // Защита от зацикливания						
						$location = usam_get_location( $parent );	
						$types_location = usam_get_types_location( );
						$first = false;
						foreach ( $types_location as $type )
						{
							if ( $type->code == $new['code'] )
							{
								if ( $first == false )
									$parent = 0;
								break;
							}										
							elseif ( $type->code == $location['code'] )
								$first = true;	
						}
						$new['parent'] = $parent;		
					}
					else
						$new['parent'] = 0;										
					
					if ( self::$id != null )
						usam_update_location( self::$id, $new );
					else		
						self::$id = usam_insert_location( $new );	
					if ( !empty(self::$request_data['metas']) )
					{			
						foreach ( self::$request_data['metas'] as $meta_key => $meta_value )
						{
							$meta_key = sanitize_text_field($meta_key);
							if ( $meta_key == 'longitude' || $meta_key == 'longitude')
								$meta_value = usam_string_to_float($meta_value);
							else
								$meta_value = sanitize_text_field($meta_value);
							usam_update_location_metadata( self::$id, $meta_key, $meta_value );
						}
					}
				}				
			break;								
		}
		return array( 'id' => self::$id ); 
	}	
		
	
	private static function controller_payment_received() 
	{				
		switch( self::$action )
		{			
			case 'save': 
				require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	

				$document = usam_get_document( self::$id );
							
				$parent_documents = usam_get_parent_documents( self::$id, $document['type'] );
				$document_ids = [];
				foreach ( $parent_documents as $parent_document )
				{
					if ( $parent_document->document_type == 'invoice' )
						$document_ids[] = $parent_document->document_id;
				}			
				if ( $new_document_ids )
				{					
					foreach ( array_diff($new_document_ids, $document_ids) as $document_id )
					{
						usam_add_document_link(['document_id' => $document_id, 'document_type' => 'invoice', 'document_link_id' => $document['id'], 'document_link_type' => $document['type']]);							
						if ( current_user_can('add_act') )
						{
							$doc = usam_get_document( $document_id );
							if ( $doc['totalprice'] <= $totalprice )
							{
								$child_documents = usam_get_documents(['fields' => 'id', 'child_document' => ['id' => $doc['id'], 'type' => $doc['type']], 'type' => 'act', 'number' => 1]);
								if ( $child_documents )
								{								
									usam_update_document( $document_id, ['status' => 'sent'] );								
									usam_document_copy( $document_id, ['type' => 'act', 'date_insert' => $doc['date_insert']]);		
								}
							}
						}
					}
				}
				$delete_document_ids = array_diff($document_ids, $new_document_ids);					
				foreach ( $delete_document_ids as $document_id )
					usam_delete_document_link(['document_id' => $document_id, 'document_type' => 'invoice', 'document_link_id' => self::$id, 'document_link_type' => 'payment_received']);
				$counterparty_account_number = !empty(self::$request_data['counterparty_account_number'])?sanitize_text_field(self::$request_data['counterparty_account_number']):0;
				usam_update_document_metadata(self::$id, 'counterparty_account_number', $counterparty_account_number ); 
			break;				
		}
		return ['id' => self::$id]; 
	}
	
	private static function controller_vk_user() 
	{
		switch( self::$action )
		{			
			case 'save': 
				if ( !empty(self::$request_data['code']) )
				{		
					$new['code'] = absint(self::$request_data['code']);	
					$new['access_token'] = sanitize_text_field(self::$request_data['access_token']);	
					$new['type_social'] = 'vk_user';
					$new['birthday'] = !empty(self::$request_data['birthday'])?1:0;	
					$new['type_price'] = sanitize_text_field(self::$request_data['type_price']);		
					$new['contact_group'] = absint(self::$request_data['contact_group']);	
					$vkontakte = new USAM_VKontakte_API( $new );
					$contact = $vkontakte->get_user( $new['code'], ['photo_50'] );
					$new['name'] = $contact['first_name'].' '.$contact['last_name'];
					$new['photo'] = $contact['foto'];	
					if ( self::$id != null )
						usam_update_social_network_profile( self::$id, $new );	
					else						
						self::$id = usam_insert_social_network_profile( $new );		
					if ( !empty(self::$request_data['metas']) )
					{			
						foreach ( self::$request_data['metas'] as $meta_key => $meta_value )
						{													
							$meta_key = sanitize_text_field($meta_key);
							$meta_value = sanitize_text_field($meta_value);
							usam_update_social_network_profile_metadata( self::$id, $meta_key, $meta_value );		
						}
					}
				}	
			break;				
		}
		return array( 'id' => self::$id ); 	
	}
	
	private static function controller_fb_user() 
	{
		switch( self::$action )
		{			
			case 'save': 
				if ( !empty(self::$request_data['code']) )
				{		
					
				//	$api = new USAM_Facebook_API( self::$id );	
				//	$api->wall_post( array('message' => 'fffffffffff') );		
						
					
					$new['code'] = absint(self::$request_data['code']);	
					$new['access_token'] = sanitize_text_field(self::$request_data['access_token']);	
					$params = array(						
						'fields'       => 'id,email,first_name,last_name,picture',	
						'access_token' => $new['access_token'],
					); 				
					$info = file_get_contents('https://graph.facebook.com/me?' . urldecode(http_build_query($params)));
					$info = json_decode($info, true);
					if ( !empty($info) )
					{
						$new['name'] = $info['first_name'].' '.$info['last_name'];
						$new['photo'] = $info['picture']['data']['url'];			
					}				
					$new['type_social'] = 'fb_user';
					$new['birthday'] = !empty(self::$request_data['birthday'])?1:0;	
					$new['type_price'] = sanitize_text_field(self::$request_data['type_price']);		
					$new['contact_group'] = absint(self::$request_data['contact_group']);	
					if ( self::$id != null )	
					{				
						usam_update_social_network_profile( self::$id, $new );					
					}
					else			
					{							
						self::$id = usam_insert_social_network_profile( $new );		 
					}	
					if ( !empty(self::$request_data['metas']) )
					{			
						foreach ( self::$request_data['metas'] as $meta_key => $meta_value )
						{													
							$meta_key = sanitize_text_field($meta_key);
							$meta_value = sanitize_text_field($meta_value);
							usam_update_social_network_profile_metadata( self::$id, $meta_key, $meta_value );		
						}
					}
				}	
			break;				
		}
		return array( 'id' => self::$id ); 	
	}	
	
	private static function controller_fb_group() 
	{
		switch( self::$action )
		{			
			case 'save': 
				if ( !empty(self::$request_data['code']) )
				{		
					require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
										
					$new['code'] = absint(self::$request_data['code']);			
					$new['contact_group'] = absint(self::$request_data['contact_group']);						
					$new['birthday'] = !empty(self::$request_data['birthday'])?1:0;					
					$new['access_token'] = !empty(self::$request_data['access_token'])?sanitize_text_field(self::$request_data['access_token']):'';		
					$new['from_group'] = !empty(self::$request_data['from_group'])?1:0;	
					$new['type_price'] = sanitize_text_field(self::$request_data['type_price']);						
					$new['name'] = '';
					$new['photo'] = '';	
					$new['type_social'] = 'fb_group';		

					
				/*	$group = usam_vkontakte_send_request( 'groups.getById', array('group_ids' => $new['code'], 'access_token' => $new['access_token'] ) );				
					if (  !empty($group[0]) )
					{
						$new['name'] = $group[0]['name'];
						$new['photo'] = $group[0]['photo_50'];
						$new['uri'] = $group[0]['screen_name'];	
					}		*/
					if ( self::$id != null )	
					{				
						$api = new USAM_Facebook_API( self::$id );	
					//	$new['access_token'] = $api->get_access_token();
						
						
						$api->wall_post( array('message' => 'fffffffffff') );		
						
						$group = $api->get_group_info();						
						if (  !empty($group[0]) )
						{
							
						}
						usam_update_social_network_profile( self::$id, $new );						
					}
					else			
					{							
						self::$id = usam_insert_social_network_profile( $new );		 
						
						$api = new USAM_Facebook_API( self::$id );			
						$group = $api->get_group_info();
						
						usam_update_social_network_profile( self::$id, $new );	
					}							
					if ( !empty(self::$request_data['metas']) )
					{			
						foreach ( self::$request_data['metas'] as $meta_key => $meta_value )
						{								
							$meta_key = sanitize_text_field($meta_key);
							$meta_value = sanitize_text_field($meta_value);
							usam_update_social_network_profile_metadata( self::$id, $meta_key, $meta_value );		
						}
					}		
					if ( !empty(self::$request_data['messages']) )
					{			
						foreach ( self::$request_data['messages'] as $meta_key => $meta_value )
						{		
							$meta_key = sanitize_text_field($meta_key);
							$meta_value = sanitize_textarea_field(stripslashes($meta_value));
							usam_update_social_network_profile_metadata( self::$id, $meta_key, $meta_value );		
						}
					}	
				}		
			break;				
		}
		return array( 'id' => self::$id ); 
	}		
	
	private static function controller_vk_contest() 
	{
		switch( self::$action )
		{			
			case 'save': 
				$new = self::$request_data['contest'];		
				$new['title'] = sanitize_text_field(stripcslashes(self::$request_data['name']));	
				$new['message'] = sanitize_textarea_field(stripslashes(self::$request_data['message']));	
				$new['active'] = !empty(self::$request_data['active'])?1:0;			
				
				$new['start_date'] = usam_get_datepicker('start');
				$new['end_date'] = usam_get_datepicker('end');	
				
				if ( self::$id != null )					
					usam_edit_data( $new, self::$id, 'usam_vk_contest' );			
				else			
				{				
					$new['date_insert'] = date( "Y-m-d H:i:s" );
					self::$id = usam_add_data( $new, 'usam_vk_contest' );	
				}
			break;				
		}
		return array( 'id' => self::$id ); 
	}
	
	private static function controller_vk_group() 
	{
		switch( self::$action )
		{			
			case 'save': 
				if ( !empty(self::$request_data['code']) )
				{		
					$new['code'] = absint(self::$request_data['code']);			
					$new['contact_group'] = absint(self::$request_data['contact_group']);						
					$new['birthday'] = !empty(self::$request_data['birthday'])?1:0;					
					$new['access_token'] = !empty(self::$request_data['access_token'])?sanitize_text_field(self::$request_data['access_token']):'';		
					$new['from_group'] = !empty(self::$request_data['from_group'])?1:0;	
					$new['type_price'] = sanitize_text_field(self::$request_data['type_price']);						
					$new['name'] = '';
					$new['photo'] = '';	
					$new['type_social'] = 'vk_group';			
					$vk_group = usam_vkontakte_send_request( 'groups.getById', array('group_ids' => $new['code'], 'access_token' => $new['access_token'] ) );				
					if (  !empty($vk_group[0]) )
					{
						$new['name'] = $vk_group[0]['name'];
						$new['photo'] = $vk_group[0]['photo_50'];
						$new['uri'] = $vk_group[0]['screen_name'];	
					}		
					if ( self::$id != null )	
					{				
						usam_update_social_network_profile( self::$id, $new );					
					}
					else			
					{							
						self::$id = usam_insert_social_network_profile( $new );		 
					}						
					if ( !empty(self::$request_data['metas']) )
					{			
						foreach ( self::$request_data['metas'] as $meta_key => $meta_value )
						{								
							$meta_key = sanitize_text_field($meta_key);
							$meta_value = sanitize_text_field($meta_value);
							usam_update_social_network_profile_metadata( self::$id, $meta_key, $meta_value );		
						}
					}		
					if ( !empty(self::$request_data['messages']) )
					{			
						foreach ( self::$request_data['messages'] as $meta_key => $meta_value )
						{		
							$meta_key = sanitize_text_field($meta_key);
							$meta_value = sanitize_textarea_field(stripslashes($meta_value));
							usam_update_social_network_profile_metadata( self::$id, $meta_key, $meta_value );		
						}
					}
				}		
			break;				
		}
		return array( 'id' => self::$id ); 
	}	
	
	private static function controller_ok_group() 
	{
		switch( self::$action )
		{			
			case 'save': 
				require_once( USAM_APPLICATION_PATH . '/social-networks/ok_api.class.php' );		
				if ( !empty(self::$request_data['code']) )
				{		
					$new['code'] = absint(self::$request_data['code']);			
					$new['contact_group'] = absint(self::$request_data['contact_group']);	
					$new['type_price'] = sanitize_text_field(self::$request_data['type_price']);		
					$new['from_group'] = !empty(self::$request_data['from_group'])?1:0;								
					$new['birthday'] = !empty(self::$request_data['birthday'])?1:0;									
					$new['name'] = '';
					$new['photo'] = '';	
					$new['type_social'] = 'ok_group';							
				
					$ok = new USAM_OK_API( $new );	
					$info = $ok->get_group_info( array('uids' => $new['code']) );
					$errors = $ok->get_errors();
					foreach ( $errors as $error ) 
						usam_set_user_screen_error( $error );	
					if (  !empty($info[0]) )
					{
						$new['name'] = $info[0]['name'];
						$new['photo'] = !empty($info[0]['main_photo'])?$info[0]['main_photo']['pic180min']:'';				
					}		
					if ( self::$id != null )	
					{				
						usam_update_social_network_profile( self::$id, $new );					
					}
					else			
					{							
						self::$id = usam_insert_social_network_profile( $new );		 
					}						
					if ( !empty(self::$request_data['metas']) )
					{			
						foreach ( self::$request_data['metas'] as $meta_key => $meta_value )
						{													
							$meta_key = sanitize_text_field($meta_key);
							$meta_value = sanitize_text_field($meta_value);
							usam_update_social_network_profile_metadata( self::$id, $meta_key, $meta_value );		
						}
					}
				}	
			break;				
		}
		return array( 'id' => self::$id ); 				
	}
	
	private static function controller_sms() 
	{
		switch( self::$action )
		{			
			case 'save':				
				if ( isset(self::$request_data['phone']) )
				{ 										
					$phone = absint(self::$request_data['phone']);	
					$message = self::$request_data['message'];				
					$message = stripslashes( $message );
					$message = str_replace( array( "\n\r" ), '<br>', $message );				
					$message = htmlspecialchars_decode( $message );						
					$insert = array('message' => $message, 'phone' => $phone, 'folder' => 'drafts' );		
					if ( self::$id != null )	
					{
						$_email = new USAM_SMS( self::$id );						
						$_email->set( $insert );	
						$_email->save();	
					}
					else
					{ 		
						$_email = new USAM_SMS( $insert );
						$_email->save();
						self::$id = $_email->get('id');					
					}			 
					if ( isset(self::$request_data['send']) )
					{			
						$number_message = usam_send_sms( $phone, $message );						
						$args = array( 'folder' => 'outbox' );			
						if ( $number_message )
						{				
							$args['folder'] = 'sent';
							$args['sent_at'] = date( "Y-m-d H:i:s" );	
							$args['server_message_id'] = $number_message;	
							$sent = 1;
						}
						else
							$sent = 0;
						$_email->set( $args );	
						$_email->save();
						return array( 'send_sms' => $sent ); 
					}
					else
						return array( 'id' => self::$id ); 
				}
			break;				
		}						
	}	
	
	private static function controller_application( ) 
	{				
		switch( self::$action )
		{	
			case 'save':
				if ( isset(self::$request_data['access_token']) && self::$request_data['access_token'] != '***' )
					$new['access_token'] = sanitize_text_field(self::$request_data['access_token']);
				if ( isset(self::$request_data['login']) )
					$new['login'] = sanitize_text_field(self::$request_data['login']);	
				if ( isset(self::$request_data['password']) && self::$request_data['password'] !== '***' )
					$new['password'] = sanitize_text_field(self::$request_data['password']);					
				$new['active'] = !empty(self::$request_data['active'])?1:0;	
				$integrations = usam_get_data_integrations( 'applications', ['name' => 'Name', 'icon' => 'Icon', 'group' => 'Group'] );
				if ( isset($integrations[self::$request_data['service_code']]) )
					$new['group_code'] = $integrations[self::$request_data['service_code']]['group'];			
				if ( self::$id )
					usam_update_application( self::$id, $new );
				else
				{ 
					if ( !empty(self::$request_data['service_code']) )
					{
						$new['service_code'] = sanitize_text_field(self::$request_data['service_code']);					
						self::$id = usam_insert_application( $new );
					}					
				} 
				if ( self::$id )
				{
					if ( !empty(self::$request_data['metadata']) )	
					{ 
						foreach( self::$request_data['metadata'] as $meta_key => $meta_value )
						{						
							usam_update_application_metadata( self::$id, sanitize_text_field($meta_key), sanitize_text_field($meta_value) );	
						}
					}	
					$class = usam_get_class_application( self::$id );				
					if ( $class )
						$class->save_form();	
				}
				return ['id' => self::$id]; 
			break;
		}	
	}
		
	private static function controller_email() 
	{
		switch( self::$action )
		{	
			case 'save':			 	
				if ( !isset(self::$request_data['from_mailbox_id']) )
					return false;					
				
				$mailbox_id  = absint(self::$request_data['from_mailbox_id']);					
				$address = trim(self::$request_data['to']);	
				$to_name = '';	
				$to_email = $address;
				$separator = '';
				if ( stripos($address, ';') === false)
					$separator = ';';
				if ( stripos($address, ',') === false)
					$separator = ',';
				
				if ( $separator != '' )
				{
					if( preg_match('/<[ ]?(([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6})>$/i', $address, $regs) )	
					{				
						$to_email = $regs[1];
					}											
					if( preg_match('/^(.*?)</i', $address, $regs) )	
					{		
						$to_name = $regs[1];
					}							
				}																
				$title = sanitize_text_field(self::$request_data['title']);
				
				$message = self::$request_data['message'];				
				$message = stripslashes( $message );
				$message = str_replace( array( "\n\r" ), '<br>', $message );				
				$message = htmlspecialchars_decode( $message );							
				if ( !empty(self::$request_data['template']) ) 
				{ 
					$style = new USAM_Mail_Styling( $mailbox_id );
					$message = $style->get_message( $message );							
				}
				$insert_email = ['body' => $message, 'title' => $title, 'to_email' => $to_email, 'to_name' => $to_name, 'read' => 1, 'folder' => 'drafts', 'mailbox_id' => $mailbox_id];
				if ( self::$id != null && (!isset(self::$request_data['screen']) || self::$request_data['screen'] != 'reply' && self::$request_data['screen'] != 'forward') )	
				{
					$_email = new USAM_Email( self::$id );						
					$_email->set( $insert_email );	
					$_email->save();	
				}
				else
				{ 		
					$_email = new USAM_Email( $insert_email );
					$_email->save();
					self::$id = $_email->get('id');					
				}								
				$metas['copy_email'] = trim(self::$request_data['copy_to']);
				if ( $metas )
				{			
					foreach ( $metas as $meta_key => $meta_value )
					{
						if ( $meta_value == '' )
						{
							usam_delete_email_metadata( self::$id, $meta_key );								
						}
						else
						{																
							$meta_key = sanitize_text_field($meta_key);
							$meta_value = sanitize_text_field($meta_value);
							usam_update_email_metadata( self::$id, $meta_key, $meta_value );		
						}
					}
				}								
				usam_update_attachments( self::$id, 'email' );			
				if ( isset(self::$request_data['send']) )
				{			
					$to_email = $_email->get('to_email');
					$emails = explode(',',$to_email);
					$email_sent = 1;					
					foreach ( $emails as $email ) 
					{ 
						$email = trim($email);
						if ( !is_email($email) ) 
						{ 
							usam_set_user_screen_error( sprintf(__('Электронная почта %s указана не верно','usam'), $email ) );
							$email_sent = 0;							
						}
					}
					if ( $email_sent )
						$email_sent = $_email->send_mail();
					return ['send_email' => $email_sent]; 
				}		
				else
					return ['id' => self::$id]; 
			break;			
			case 'add_contact':				
				usam_add_contact_from_email( self::$id );
				return ['ready' => 1]; 
			break;			
		}
	}	
	
	private static function controller_order_status() 
	{
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{			
				case 'save':				
					self::save_document_status( 'order' );	
				break;				
			}
		}
		return ['id' => self::$id]; 
	}

	private static function controller_contact_status() 
	{
		if ( current_user_can('setting_crm') )
		{	
			switch( self::$action )
			{			
				case 'save':				
					self::save_document_status( 'contact' );	
				break;				
			}
		}
		return ['id' => self::$id]; 
	}	
	
	private static function controller_company_status() 
	{
		if ( current_user_can('setting_crm') )
		{	
			switch( self::$action )
			{			
				case 'save':				
					self::save_document_status( 'company' );	
				break;				
			}
		}
		return ['id' => self::$id]; 
	}
	
	private static function controller_lead_status() 
	{
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{			
				case 'save':				
					self::save_document_status( 'lead' );	
				break;				
			}
			return ['id' => self::$id]; 
		}		
	}
	
	private static function controller_contacting_status() 
	{
		if ( usam_check_current_user_role('administrator' ) )
		{	
			switch( self::$action )
			{			
				case 'save':				
					self::save_document_status( 'contacting' );	
				break;				
			}
			return ['id' => self::$id];	
		}		
	}	
	
	private static function controller_purchase_rule() 
	{
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{			
				case 'save':				
					$new_rule['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));		
					$new_rule['active'] = !empty(self::$request_data['active'])?1:0;				
					$new_rule['conditions'] = self::get_rules_basket_conditions(  );	
					$new_rule['description'] = sanitize_textarea_field(stripslashes(self::$request_data['description']));
					if ( self::$id )		
					{			
						usam_edit_data( $new_rule, self::$id, 'usam_purchase_rules' );			
					}
					else			
					{					
						self::$id = usam_add_data( $new_rule, 'usam_purchase_rules' );		
					}
				break;				
			}
			return ['id' => self::$id]; 
		}		
	}
	
	private static function controller_type_payer() 
	{
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{			
				case 'save':				
					$new['name'] =  sanitize_text_field(stripcslashes(self::$request_data['name']));		
					$new['active'] = !empty(self::$request_data['active'])?1:0;		
					$new['type'] = !empty(self::$request_data['type']) && self::$request_data['type'] == 'contact'?'contact':'company';				
					$new['sort'] = isset(self::$request_data['sort'])?(int)self::$request_data['sort']:100;							
					if ( self::$id != null )	
						usam_edit_data( $new, self::$id, 'usam_types_payers', false );	
					else	
						self::$id = usam_add_data( $new, 'usam_types_payers', false );	
				break;				
			}
			return ['id' => self::$id]; 
		}		
	}
	
	private static function controller_order_property_group() 
	{
		if ( current_user_can('setting_document') )
		{
			switch( self::$action )
			{			
				case 'save':				
					$new['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));		
					$new['code'] = !empty(self::$request_data['code'])?sanitize_title(self::$request_data['code']):sanitize_title(self::$request_data['name']);
					$new['sort'] = (int)self::$request_data['sort'];						
					$new['type'] = 'order';		
					if ( self::$id )		
					{			
						usam_update_property_group( self::$id, $new );
					}
					else			
					{					
						self::$id = usam_insert_property_group( $new );
					}	
					$type_payer = !empty(self::$request_data['type_payer'])?array_map('intval', self::$request_data['type_payer']):array();
					self::save_meta( 'property_group', 'type_payer', $type_payer );
				break;				
			}
			return ['id' => self::$id]; 
		}		
	}
	
	private static function controller_view_grouping() 
	{
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{			
				case 'save':				
					$new = array();
					$new['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));				
					$new['type_prices'] = isset(self::$request_data['type_prices'])?stripslashes_deep(self::$request_data['type_prices']):array();

					if ( self::$id != null )	
					{
						usam_edit_data( $new, self::$id, 'usam_order_view_grouping' );	
					}
					else			
					{			
						self::$id = usam_add_data( $new, 'usam_order_view_grouping' );				
					}	
				break;				
			}
			return ['id' => self::$id]; 
		}		
	}
		
	private static function controller_payment_gateway() 
	{
		global $wpdb;
		if ( current_user_can('setting_document') )
		{	
			switch( self::$action )
			{		
				case 'save':				
					$new = self::$request_data['payment_gateway'];	
					$new['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));		
					$new['description'] = sanitize_textarea_field(stripcslashes(self::$request_data['description']));	
					$new['active'] = !empty(self::$request_data['active'])?1:0;	
					$new['ipn'] = !empty($new['ipn'])?1:0;						
					$new['img'] = !empty(self::$request_data['thumbnail'])?absint(self::$request_data['thumbnail']):0;				
					if ( self::$id != null )
						usam_update_payment_gateway(self::$id, $new);
					else
						self::$id = usam_insert_payment_gateway( $new );
					if ( !empty(self::$request_data['gateway_handler']) )
					{
						$gateway_system = self::$request_data['gateway_handler'];
						foreach( $gateway_system as $key => $value)
						{
							usam_update_payment_gateway_metadata(self::$id, $key, $value);	
						}
					}					
					$message_fail = stripcslashes(self::$request_data['message_fail']);	
					$message_completed = stripcslashes(self::$request_data['message_completed']);						
					usam_update_payment_gateway_metadata(self::$id, 'message_completed', $message_completed);
					usam_update_payment_gateway_metadata(self::$id, 'message_fail', $message_fail);	
					
					$types_payers = !empty(self::$request_data['types_payers'])?array_map('intval', self::$request_data['types_payers']):array();			
					self::save_meta( 'payment_gateway', 'types_payers', $types_payers );
					$selected_shipping = !empty(self::$request_data['selected_shipping'])?array_map('intval', self::$request_data['selected_shipping']):array();			
					self::save_meta( 'payment_gateway', 'shipping', $selected_shipping );
					$sales_area = !empty(self::$request_data['sales_area'])?array_map('intval', self::$request_data['sales_area']):array();					
					self::save_meta( 'payment_gateway', 'sales_area', $sales_area );
					$roles = !empty(self::$request_data['roles'])?array_map('sanitize_text_field', self::$request_data['roles']):array();						
					self::save_meta( 'payment_gateway', 'roles', $roles );
					$units = !empty(self::$request_data['units'])?array_map('sanitize_text_field', self::$request_data['units']):array();	
					self::save_meta( 'payment_gateway', 'units', $units );					
					$category = isset(self::$request_data['tax_input']['usam-category'])?array_map('intval', self::$request_data['tax_input']['usam-category']):array();
					self::save_meta( 'payment_gateway', 'category', $category );
					$brands = isset(self::$request_data['tax_input']['usam-brands'])?array_map('intval', self::$request_data['tax_input']['usam-brands']):array();
					self::save_meta( 'payment_gateway', 'brands', $brands );
				break;				
			}
			return array( 'id' => self::$id ); 	
		}		
	}
		
	private static function controller_payment() 
	{
		switch( self::$action )
		{			
			case 'save':				
				self::$id = (int)self::$id;
				$document = self::$request_data['_payment'];
				$document['date_insert'] = usam_get_datepicker('insert');		
				self::$id = self::save_payment_document( self::$id, $document );
				return array( 'id' => self::$id ); 	
			break;				
		} 		
	}	
		
	private static function controller_mailbox() 
	{
		switch( self::$action )
		{			
			case 'save':				
				$new = stripslashes_deep(self::$request_data['mailbox']);					
				$new['template_name'] = sanitize_text_field(self::$request_data['template_name']);
				$message = stripslashes( self::$request_data['template'] );
				$message = str_replace( array( "\n\r" ), '<br>', $message );			
				$new['name'] = trim(sanitize_text_field(stripcslashes(self::$request_data['name'])));	
				$new['email'] = mb_strtolower(trim(sanitize_text_field(self::$request_data['email'])));	
				$new['template'] = htmlspecialchars_decode( $message );					
				
				
				$metas['pop3server'] = sanitize_text_field(self::$request_data['pop3server']);
				$metas['pop3port'] = sanitize_text_field(self::$request_data['pop3port']);
				$metas['pop3user'] = sanitize_text_field(self::$request_data['pop3user']);
				$metas['pop3pass'] = sanitize_text_field(self::$request_data['pop3pass']);
				$metas['pop3ssl'] = sanitize_text_field(self::$request_data['pop3ssl']);
				$metas['smtpserver'] = sanitize_text_field(self::$request_data['smtpserver']);
				$metas['smtpport'] = sanitize_text_field(self::$request_data['smtpport']);
				$metas['smtpuser'] = sanitize_text_field(self::$request_data['smtpuser']);
				$metas['smtppass'] = sanitize_text_field(self::$request_data['smtppass']);
				$metas['smtp_secure'] = sanitize_text_field(self::$request_data['smtp_secure']);	
				$metas['newsletter'] = !empty(self::$request_data['newsletter'])?1:0;				
				if ( !is_email($new['email']) )
					return false;		
				if ( $metas['smtppass'] == '***' )
					unset($metas['smtppass']);			
				elseif ( $metas['smtppass'] != '' )
					$new['smtppass'] = $metas['smtppass'];
				else
					$metas['smtppass'] = '';
				
				if ( $metas['pop3pass'] == '***' )
					unset($metas['pop3pass']);			
				elseif ( $metas['pop3pass'] != '' )
					$metas['pop3pass'] = $metas['pop3pass'];
				else
					$metas['pop3pass'] = '';
				
				if ( self::$id )		
					usam_update_mailbox( self::$id, $new );				
				else				
					self::$id = usam_insert_mailbox( $new );	
				$primary = usam_get_primary_mailbox();
				if ( empty($primary) )
					update_option( 'usam_return_email', $new['email'] );
								
				$manager_ids = usam_get_mailbox_users( self::$id );
				if ( !empty(self::$request_data['user_ids'])  )
				{
					$new_manager_ids = array_map('intval', self::$request_data['user_ids']);	
					$delete_manager_ids = array_diff($manager_ids, $new_manager_ids);
					$add_manager_ids = array_diff($new_manager_ids, $manager_ids);						
					foreach ( $add_manager_ids as $user_id )
					{
						self::add_mailbox_user( $user_id ); 
					}
					foreach ( $delete_manager_ids as $user_id )
					{
						self::delete_mailbox_user( $user_id );
					}
				}
				elseif ( !empty($manager_ids) )
				{
					foreach ( $manager_ids as $user_id )
					{
						self::delete_mailbox_user( $user_id );
					}
				}
				foreach($metas as $key => $value)	
				{
					usam_update_mailbox_metadata( self::$id, $key, $value );
				} 
			break;				
		} 
		return array( 'id' => self::$id ); 	
	}
	
	
	private static function controller_yandex_connect_mailbox() 
	{
		switch( self::$action )
		{			
			case 'save':									
				require_once( USAM_FILE_PATH . '/includes/seo/yandex/pddimp.class.php' );
				
				$new['template_name'] = sanitize_text_field(self::$request_data['template_name']);
				$message = stripslashes( self::$request_data['template'] );
				$message = str_replace( array( "\n\r" ), '<br>', $message );			
				$new['template'] = htmlspecialchars_decode( $message );							
				$new['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));					
				$metas['pop3pass'] = $metas['smtppass'] = sanitize_text_field(self::$request_data['pop3pass']);	
				$metas['pop3server'] = 'pop.yandex.ru';	
				$metas['pop3port'] = '995';				
				$metas['pop3ssl'] = 1;
				$metas['smtpserver'] = 'smtp.yandex.ru';	
				$metas['smtpport'] = '465';			
				$metas['smtp_secure'] = 'ssl';						
				if ( self::$id )		
				{					
					$mailbox = usam_get_mailbox( self::$id );					
					$pddimp = new USAM_Yandex_pddimp();					
					$result = $pddimp->edit_mailbox(  array('email' => $mailbox['email'], 'password' => $new['pop3pass'] ) );
					
					usam_update_mailbox( self::$id, $new );		
				}
				else
				{		
					if ( !is_email($new['email']) )
					{
						return array( 'id' => 0, 'ready' => 0 ); 					
					}					
					$metas['pop3user'] = $new['email'];
					$metas['smtpuser'] = $new['email'];
					$new['email'] = trim(self::$request_data['email']);								
					$pddimp = new USAM_Yandex_pddimp();					
					$result =  $pddimp->add_mailbox(['email' => $new['email'], 'password' => $metas['pop3pass']]);
					
					self::$id = usam_insert_mailbox( $new );						
				}			
				foreach($metas as $key => $value)	
					usam_update_mailbox_metadata( self::$id, $key, $value );
				$manager_ids = usam_get_mailbox_users( self::$id );
				if ( !empty(self::$request_data['user_ids'])  )
				{
					$new_manager_ids = array_map('intval', self::$request_data['user_ids']);	
					$delete_manager_ids = array_diff($manager_ids, $new_manager_ids);
					$add_manager_ids = array_diff($new_manager_ids, $manager_ids);						
					foreach ( $add_manager_ids as $user_id )
					{
						self::add_mailbox_user( $user_id ); 
					}
					foreach ( $delete_manager_ids as $user_id )
					{
						self::delete_mailbox_user( $user_id );
					}
				}
				elseif ( !empty($manager_ids) )
				{
					foreach ( $manager_ids as $user_id )
					{
						self::delete_mailbox_user( $user_id );
					}
				}
				return array( 'id' => self::$id, 'ready' => $result ); 	
			break;				
		} 		
	}
	
	private static function add_mailbox_user( $user_id ) 
	{		
		global $wpdb;	
		$sql = "INSERT INTO `".USAM_TABLE_MAILBOX_USERS."` (`id`,`user_id`) VALUES ('%d','%d') ON DUPLICATE KEY UPDATE `user_id`='%d'";	
		return $wpdb->query( $wpdb->prepare($sql, self::$id, $user_id, $user_id ));	
	}			
	
	private static function delete_mailbox_user( $user_id ) 
	{			
		global $wpdb;	
		return $wpdb->delete( USAM_TABLE_MAILBOX_USERS, array( 'id' => self::$id, 'user_id' => $user_id ), array( '%d', '%d' ) );	
	}
	
	private static function controller_email_filter() 
	{
		switch( self::$action )
		{			
			case 'save':				
				$insert['if'] = sanitize_title(self::$request_data['if']);
				$insert['condition'] = sanitize_title(self::$request_data['condition']);
				$insert['value'] = sanitize_text_field(self::$request_data['value']);
				$insert['action'] = sanitize_title(self::$request_data['option_action']);
				$insert['mailbox_id'] = absint(self::$request_data['mailbox_id']);					
				
				if ( self::$id != null )	
				{
					$_email = new USAM_Email_Filter( self::$id );						
					$_email->set( $insert );	
					$_email->save();	
				}
				else
				{ 		
					$_email = new USAM_Email_Filter( $insert );
					$_email->save();
					self::$id = $_email->get('id');					
				}
				return array( 'id' => self::$id ); 	
			break;						
		} 		
	}
	
	private static function controller_signature() 
	{
		switch( self::$action )
		{			
			case 'save':				
				global $wpdb;
				$new['signature'] = stripcslashes(self::$request_data['signature']);		
				$new['name'] = stripcslashes(self::$request_data['name']);					
				$new['mailbox_id'] = absint(self::$request_data['mailbox_id']);					
				$new['manager_id'] = get_current_user_id();	
				$_formats = array( 'signature' => '%s', 'manager_id' => '%d', 'mailbox_id' => '%d', 'id' => '%d', 'name' => '%s' );
				$formats = array();
				foreach( $new as $key => $value) 				
					$formats[] = $_formats[$key];
					
				if ( self::$id != null )	
				{
					$result = $wpdb->update( USAM_TABLE_SIGNATURES, $new, array( 'id' => self::$id ), $formats );		
				}
				else
				{
					$result = $wpdb->insert( USAM_TABLE_SIGNATURES, $new, $formats );		
					self::$id = $wpdb->insert_id;
				}
				return array( 'id' => self::$id ); 	
			break;				
		} 		
	}
	
	private static function controller_tax() 
	{
		switch( self::$action )
		{			
			case 'save':				
				if( !empty(self::$request_data['tax']) )
				{			
					$insert = self::$request_data['tax'];	
					$insert['name'] = sanitize_text_field(stripslashes(self::$request_data['name']));		
					$insert['description'] = sanitize_textarea_field( stripcslashes(self::$request_data['description']) );	
					$insert['active']     = !empty(self::$request_data['active'])?1:0;			
				
					$insert['setting']['locations'] = isset(self::$request_data['locations'])?array_map('intval', self::$request_data['locations']):array();
					$insert['setting']['category'] = isset(self::$request_data['tax_input']['usam-category'])?array_map('intval', self::$request_data['tax_input']['usam-category']):array();
					$insert['setting']['brands'] = isset(self::$request_data['tax_input']['usam-brands'])?array_map('intval', self::$request_data['tax_input']['usam-brands']):array();
					$insert['setting']['payments'] = isset(self::$request_data['selected_gateway'])?array_map('intval', self::$request_data['selected_gateway']):array();
					if( self::$id != null )
					{
						$_tax = new USAM_Tax( self::$id );
						$_tax->set( $insert );
					}
					else
					{ 
						$_tax = new USAM_Tax( $insert );
					}
					$_tax->save( );			
					self::$id = $_tax->get('id');
				}
				return array( 'id' => self::$id ); 	
			break;				
		} 		
	}
	
	private static function controller_underprice() 
	{
		switch( self::$action )
		{			
			case 'save':				
				$new_rule['title'] =  sanitize_text_field(stripcslashes(self::$request_data['name']));			
				$new_rule['value'] = (int)self::$request_data['value'];
				$new_rule['type_prices'] = isset(self::$request_data['type_prices'])?stripslashes_deep(self::$request_data['type_prices']):array();						
				if ( !empty(self::$request_data['installation']) && self::$request_data['installation'] == 'group' )
				{
					$new_rule['category'] = isset(self::$request_data['tax_input']['usam-category'])?array_map('intval', self::$request_data['tax_input']['usam-category']):array();
					$new_rule['category_sale'] = isset(self::$request_data['tax_input']['usam-category_sale'])?array_map('intval', self::$request_data['tax_input']['usam-category_sale']):array();
					$new_rule['brands'] = isset(self::$request_data['tax_input']['usam-brands'])?array_map('intval', self::$request_data['tax_input']['usam-brands']):array();	
					$new_rule['catalogs'] = isset(self::$request_data['tax_input']['usam-catalog'])?array_map('intval', self::$request_data['tax_input']['usam-catalog']):array();		
					$new_rule['contractors'] = isset(self::$request_data['contractors'])?array_map('intval', self::$request_data['contractors']):array();					
				}
				else
				{
					$new_rule['category'] = array();
					$new_rule['category_sale'] = array();
					$new_rule['brands'] = array();	
					$new_rule['catalogs'] = array();	
					$new_rule['contractors'] = array();					
				}
				if ( self::$id != null )	
					$result = usam_edit_data( $new_rule, self::$id, 'usam_underprice_rules' );	
				else						
					$result = self::$id = usam_add_data( $new_rule, 'usam_underprice_rules' );	
				if ( $result )
					usam_recalculate_price_products();		
				return array( 'id' => self::$id ); 	
			break;				
		} 				
	}
	
	private static function controller_site() 
	{
		require_once( USAM_FILE_PATH . '/includes/seo/site.class.php' );
		switch( self::$action )
		{			
			case 'save':				
				$new['description'] = sanitize_textarea_field(stripcslashes(self::$request_data['description']));	
				$new['type'] = sanitize_title(self::$request_data['type']);	
				$new['domain'] = sanitize_text_field(self::$request_data['domain']);	
				if ( self::$id != null )	
					usam_update_site( self::$id, $new );	
				else						
					self::$id = usam_insert_site( $new );	
				return array( 'id' => self::$id ); 	
			break;				
		} 				
	}
		
	private static function controller_basket_discount() 
	{
		global $wpdb;
		switch( self::$action )
		{			
			case 'save':				
				if ( !empty(self::$request_data['name']) )
				{				
					$new_rule['name'] =  sanitize_text_field( stripslashes(self::$request_data['name']));
					$new_rule['description'] = sanitize_textarea_field(stripslashes(self::$request_data['description']));				
					$new_rule['active'] = !empty(self::$request_data['active'])?1:0;
					$new_rule['priority'] = isset(self::$request_data['priority'])?(int)self::$request_data['priority']:100;	
					$new_rule['end'] = !empty(self::$request_data['end'])?1:0;
					$new_rule['start_date'] = usam_get_datepicker('start');
					$new_rule['end_date'] = usam_get_datepicker('end');						
					$new_rule['discount'] = usam_string_to_float(self::$request_data['discount']);
					$new_rule['dtype'] = sanitize_title(self::$request_data['dtype']);	
					$new_rule['term_slug'] = isset(self::$request_data['term_slug'])?sanitize_title(self::$request_data['term_slug']):'';
					if ( isset(self::$request_data['code']) )
						$new_rule['code'] = sanitize_text_field(self::$request_data['code']);						
					$new_rule['type_rule'] = 'basket';					
												
					$new_type_prices = isset(self::$request_data['type_prices'])?self::$request_data['type_prices']:array();				
					if ( self::$id != null )	
					{
						usam_update_discount_rule( self::$id, $new_rule);							
					}
					else			
					{												
						self::$id = usam_insert_discount_rule( $new_rule );				
					}
					$new_conditions = self::get_rules_basket_conditions();	
					usam_update_discount_rule_metadata( self::$id, 'conditions', $new_conditions);						
					$perform_action = sanitize_title(self::$request_data['perform_action']);
					usam_update_discount_rule_metadata( self::$id, 'perform_action', $perform_action);
					if ( !empty(self::$request_data['perform_action']) && (self::$request_data['perform_action'] == 'g' || self::$request_data['perform_action'] == 'gift_choice' || self::$request_data['perform_action'] == 'gift_one_choice') && !empty(self::$request_data['products']) )
					{
						$product_ids = isset(self::$request_data['products'])?array_map('intval', self::$request_data['products']):[];		
						$wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_META." WHERE product_id NOT IN (".implode(',',$product_ids).") AND meta_key='gift' AND meta_value='".self::$id."'" );
						foreach ( $product_ids as $product_id )
							usam_update_product_meta($product_id, 'gift', self::$id);
					}
					else
						$wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_META." WHERE meta_key='gift' AND meta_value='".self::$id."'" );
				}
				return array( 'id' => self::$id ); 	
			break;				
		} 				
	}
	
	private static function controller_product_discount() 
	{
		switch( self::$action )
		{			
			case 'save':				
				if ( !empty(self::$request_data['name']) )
				{						
					require_once( USAM_FILE_PATH . '/admin/includes/rules/product_discount_rules.class.php' );	
					
					$recalculate = false;
		
					$new_rule['name'] =  sanitize_text_field( stripslashes(self::$request_data['name']));
					$new_rule['description'] = sanitize_textarea_field( stripslashes(self::$request_data['description']));				
					$new_rule['active'] = !empty(self::$request_data['active'])?1:0;
					$new_rule['priority'] = isset(self::$request_data['priority'])?(int)self::$request_data['priority']:100;	
					$new_rule['end'] = !empty(self::$request_data['end'])?1:0;	
					$new_rule['start_date'] = usam_get_datepicker('start');
					$new_rule['end_date'] = usam_get_datepicker('end');						
					$new_rule['discount'] = usam_string_to_float(self::$request_data['discount']);
					$new_rule['term_slug'] = sanitize_title(self::$request_data['term_slug']);						
					$new_rule['dtype'] = sanitize_title(self::$request_data['dtype']);
					if ( isset(self::$request_data['code']) )
						$new_rule['code'] = sanitize_text_field(self::$request_data['code']);	
					$new_rule['type_rule'] = 'product';
					$new_rule['included'] = 0;					
			
					$product_discount_rules = new USAM_Product_Discount_Rules( );	
					$new_conditions = $product_discount_rules->get_rules_basket_conditions(  );	
					$new_type_prices = isset(self::$request_data['type_prices'])?self::$request_data['type_prices']:[];
					if ( self::$id != null )	
					{						
						if ( usam_update_discount_rule( self::$id, $new_rule) )
							$recalculate = true;
					}
					else			
					{				
						if ( usam_validate_rule($new_rule) )
							$recalculate = true;
											
						self::$id = usam_insert_discount_rule( $new_rule );				
					}
					if ( usam_update_discount_rule_metadata( self::$id, 'conditions', $new_conditions) )
						$recalculate = true;
					if ( usam_update_discount_rule_metadata( self::$id, 'type_prices', $new_type_prices) )
						$recalculate = true;
					
					$label_name = sanitize_text_field( stripslashes(self::$request_data['label_name']));	
					$label_color = sanitize_text_field( stripslashes(self::$request_data['label_color']));		
			
					usam_update_discount_rule_metadata( self::$id, 'label_name', $label_name);
					usam_update_discount_rule_metadata( self::$id, 'label_color', $label_color);
				} 				
				if ( $recalculate )
					usam_recalculate_price_products();
				return ['id' => self::$id]; 
			break;				
		} 				
	}
	
	private static function controller_fix_price_discount() 
	{ 	
		switch( self::$action )
		{			
			case 'save':				
				if ( !empty(self::$request_data['name']) )
				{								
					$recalculate = false;
					
					$new_rule['name'] =  sanitize_text_field( stripslashes(self::$request_data['name']));
					$new_rule['description'] = sanitize_textarea_field( stripslashes(self::$request_data['description']));				
					$new_rule['active'] = !empty(self::$request_data['active'])?1:0;
					$new_rule['priority'] = isset(self::$request_data['priority'])?(int)self::$request_data['priority']:100;	
					$new_rule['end'] = !empty(self::$request_data['end'])?1:0;	
					$new_rule['start_date'] = usam_get_datepicker('start');
					$new_rule['end_date'] = usam_get_datepicker('end');						
					$new_rule['term_slug'] = sanitize_title(self::$request_data['term_slug']);	
					if ( isset(self::$request_data['code']) )
						$new_rule['code'] = sanitize_text_field(self::$request_data['code']);	
					$new_rule['type_rule'] = 'fix_price';
					$new_rule['included'] = 0;		
					$new_type_prices = isset(self::$request_data['type_prices'])?self::$request_data['type_prices']:array();					
					if ( self::$id != null )	
					{					
						$new = false;
						if ( usam_update_discount_rule( self::$id, $new_rule) )
							$recalculate = true;					
					}
					else			
					{				
						$new = true;
						if ( usam_validate_rule($new_rule) )
							$recalculate = true;											
						self::$id = usam_insert_discount_rule( $new_rule );				
					}					
					if( usam_update_discount_rule_metadata( self::$id, 'type_prices', $new_type_prices) )
						$recalculate = true;
					
					$label_name = sanitize_text_field( stripslashes(self::$request_data['label_name']));	
					$label_color = sanitize_text_field( stripslashes(self::$request_data['label_color']));		
			
					usam_update_discount_rule_metadata( self::$id, 'label_name', $label_name);
					usam_update_discount_rule_metadata( self::$id, 'label_color', $label_color);					
										
					if ( !empty(self::$request_data['products']) )
					{
						foreach ( self::$request_data['products'] as $product_id => $discount )
						{
							$product_id = usam_get_post_id_main_site( $product_id );
							usam_update_product_metaprice( $product_id, 'fix_price_'.self::$id, $discount );
						}
					}
					if ( !$new )
					{
						$product_ids = usam_get_products(['fields' => 'ids', 'price_meta_query' => [['key' => 'fix_price_'.self::$id, 'compare' => "EXISTS"]], 'update_post_term_cache' => false, 'stocks_cache' => false]);
						$ids = [];
						foreach ( $product_ids as $product_id )
						{
							if ( !isset(self::$request_data['products'][$product_id]) )
							{								
								$ids[] = $product_id;
								$product_id = usam_get_post_id_main_site( $product_id );
								usam_delete_product_metaprice( $product_id, 'fix_price_'.self::$id );
							}
						}
						if ( !empty($ids) )
							usam_recalculate_price_products(['post__in' => $ids]);
					}		
					elseif ( $recalculate )
						usam_recalculate_price_products(['price_meta_query' => [['key' => 'fix_price_'.self::$id, 'compare' => "EXISTS"]]]);					
				} 		
				return ['id' => self::$id]; 	
			break;				
		} 				
	}
	
	private static function controller_chat_bot_template() 
	{
		switch( self::$action )
		{			
			case 'save':				
				$new['name'] =  sanitize_text_field( stripslashes(self::$request_data['name']));
				$new['active'] = !empty(self::$request_data['active'])?1:0;							
				if ( self::$request_data['channel'] == 'chat' )	
				{
					$new['channel'] = 'chat';
					$new['channel_id'] = 0;
				}
				elseif ( self::$request_data['channel'] == 'all' )
				{
					$new['channel'] = 'all';
					$new['channel_id'] = 0;							
				}
				else
					$new['channel'] =  sanitize_title( self::$request_data['channel'] );
				if ( self::$id != null )	
				{
					usam_update_сhat_bot_template( self::$id, $new );							
				}
				else			
				{												
					self::$id = usam_insert_сhat_bot_template( $new );				
				}
				return array( 'id' => self::$id ); 	
			break;				
		} 				
	}
	
	private static function controller_chat_bot_command() 
	{
		switch( self::$action )
		{			
			case 'save':				
				$new['message'] =  sanitize_textarea_field( stripslashes(self::$request_data['message']));
				$new['active'] = !empty(self::$request_data['active'])?1:0;			
				$new['template_id'] = absint(self::$request_data['n']);
				$new['time_delay'] = absint(self::$request_data['time_delay']);					
				if ( self::$id != null )	
				{
					usam_update_chat_bot_command( self::$id, $new );							
				}
				else			
				{												
					self::$id = usam_insert_chat_bot_command( $new );				
				}
				if ( isset(self::$request_data['templates']) )
				{
					$templates = self::$request_data['templates'];
					usam_update_chat_bot_command_metadata( self::$id, 'templates', $templates );
				}	
				return array( 'id' => self::$id, 'n' => $new['template_id'] ); 	
			break;				
		} 				
	}
	
	private static function controller_viber() 
	{
		switch( self::$action )
		{			
			case 'save':				
				$new['access_token'] = !empty(self::$request_data['access_token'])?sanitize_text_field(self::$request_data['access_token']):'';	
				$new['from_group'] = !empty(self::$request_data['from_group'])?1:0;						
				$new['type_social'] = 'viber';	
				$new['contact_group'] = absint(self::$request_data['contact_group']);				
				require_once( USAM_APPLICATION_PATH . '/social-networks/viber_api.class.php' );	
				$viber = new USAM_Viber_API( $new );
				if ( $viber->set_webhook() )
				{
					$info = $viber->get_account_info();
					if ( !empty($info) )
					{ 
						$new['code'] = !empty($info['id'])?$info['id']:'';
						$new['name'] = !empty($info['name'])?$info['name']:'';
						$new['uri'] = !empty($info['uri'])?$info['uri']:'';
						$new['subscribers_count'] = !empty($info['subscribers_count'])?$info['subscribers_count']:'';
						$new['icon'] = !empty($info['icon'])?$info['icon']:'';
					}
				
				}							
				if ( self::$id != null )																
					usam_update_social_network_profile( self::$id, $new );	
				else				
					self::$id = usam_insert_social_network_profile( $new );	
				return array( 'id' => self::$id ); 	
			break;				
		} 				
	}	
	
	private static function controller_telegram() 
	{
		switch( self::$action )
		{			
			case 'save':				
				$new['access_token'] = !empty(self::$request_data['access_token'])?sanitize_text_field(self::$request_data['access_token']):'';	
				$new['from_group'] = !empty(self::$request_data['from_group'])?1:0;	
				$new['type_social'] = 'telegram';					
				$new['contact_group'] = absint(self::$request_data['contact_group']);
				require_once( USAM_APPLICATION_PATH . '/social-networks/telegram_api.class.php' );
				$telegram = new USAM_Telegram_API( $new );
				if ( $telegram->set_webhook() )
				{
					$info = $telegram->get_account_info();						
					if ( !empty($info) )
					{							
						$new['code'] = !empty($info['id'])?$info['id']:'';
						$new['name'] = !empty($info['first_name'])?$info['first_name']:'';
						$new['uri'] = !empty($info['username'])?$info['username']:'';
						$new['subscribers_count'] = 0;							
						$new['photo'] = 0;
					}					
				}						
				if ( self::$id != null )																
					usam_update_social_network_profile( self::$id, $new );	
				else				
					self::$id = usam_insert_social_network_profile( $new );		
				return array( 'id' => self::$id ); 	
			break;				
		} 				
	}	
	
	private static function controller_skype() 
	{
		switch( self::$action )
		{			
			case 'save':				
				$new['access_token'] = !empty(self::$request_data['access_token'])?sanitize_text_field(self::$request_data['access_token']):'';	
				$new['from_group'] = !empty(self::$request_data['from_group'])?1:0;	
				$new['app_id'] = !empty(self::$request_data['app_id'])?sanitize_text_field(self::$request_data['app_id']):'';					
				$new['contact_group'] = absint(self::$request_data['contact_group']);
				$new['type_social'] = 'skype';					
				/*require_once( USAM_FILE_PATH . '/includes/feedback/telegram.class.php' );	
				$telegram = new USAM_Telegram( $new );
				if ( $telegram->set_webhook() )
				{
					$info = $telegram->get_account_info();
					if ( !empty($info) )
					{
						$new['code'] = $info['id'];
						$new['name'] = $info['name'];
						$new['uri'] = $info['uri'];
						$new['subscribers_count'] = $info['subscribers_count'];							
						$new['photo'] = $info['icon'];
					}
				
				}							*/
				if ( self::$id != null )																
					usam_update_social_network_profile( self::$id, $new );	
				else				
					self::$id = usam_insert_social_network_profile( $new );
				return array( 'id' => self::$id ); 	
			break;				
		} 				
	}
	
	private static function controller_facebook() 
	{
		switch( self::$action )
		{			
			case 'save':				
				$new['access_token'] = !empty(self::$request_data['access_token'])?sanitize_text_field(self::$request_data['access_token']):'';	
				$new['from_group'] = !empty(self::$request_data['from_group'])?1:0;	
				$new['app_id'] = !empty(self::$request_data['app_id'])?sanitize_text_field(self::$request_data['app_id']):'';					
				$new['contact_group'] = absint(self::$request_data['contact_group']);
				$new['type_social'] = 'facebook';					
				/*require_once( USAM_FILE_PATH . '/includes/feedback/telegram.class.php' );	
				$telegram = new USAM_Telegram( $new );
				if ( $telegram->set_webhook() )
				{
					$info = $telegram->get_account_info();
					if ( !empty($info) )
					{
						$new['code'] = $info['id'];
						$new['name'] = $info['name'];
						$new['uri'] = $info['uri'];
						$new['subscribers_count'] = $info['subscribers_count'];							
						$new['photo'] = $info['icon'];
					}
				
				}							*/
				if ( self::$id != null )																
					usam_update_social_network_profile( self::$id, $new );	
				else				
					self::$id = usam_insert_social_network_profile( $new );		
				return array( 'id' => self::$id ); 	
			break;				
		} 				
	}
	
	private static function controller_advertising_campaign() 
	{
		switch( self::$action )
		{			
			case 'save':
				$new['title'] = sanitize_text_field(stripcslashes(self::$request_data['name']));		
				$new['description'] = sanitize_textarea_field(stripslashes(self::$request_data['description']));				
				$new['code'] = !empty(self::$request_data['code'])?sanitize_title(self::$request_data['code']):sanitize_title(self::$request_data['name']);
				$new['medium'] = sanitize_title(self::$request_data['medium']);		
				$new['source'] = sanitize_title(self::$request_data['source']);	
				$new['redirect'] = sanitize_text_field(self::$request_data['redirect']);
				if ( self::$id )
				{
					global $wpdb;
					$r = $wpdb->get_var( "SELECT id FROM " . USAM_TABLE_CAMPAIGNS." WHERE code='".$new['code']."' AND id!=".self::$id );
					if ( $r )
						$new['code'] = sanitize_title(self::$request_data['name']);
					usam_update_advertising_campaign( self::$id, $new );						
				}
				else				
					self::$id = usam_insert_advertising_campaign( $new );	
				return ['id' => self::$id]; 	
			break;				
		} 				
	}
	
	private static function controller_list() 
	{
		require_once( USAM_FILE_PATH .'/includes/feedback/mailing_list.php' );
		switch( self::$action )
		{			
			case 'save':				
				$list['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));
				$list['description'] = sanitize_textarea_field(stripcslashes(self::$request_data['description']));
				$list['view'] = !empty(self::$request_data['view_list'])?1:0;	
				if ( self::$id != null )		
					usam_update_mailing_list( self::$id, $list );	
				else		
					self::$id = usam_insert_mailing_list( $list );
				return ['id' => self::$id]; 	
			break;				
		} 				
	}
	
	private static function controller_plan() 
	{
		require_once( USAM_FILE_PATH .'/includes/personnel/sales_plan.class.php' );		
		switch( self::$action )
		{			
			case 'save':						
				$new['period_type'] =  sanitize_title(self::$request_data['period_type']);		
				$year =  sanitize_title(self::$request_data['year']);		
				if ( $new['period_type'] == 'month' )
				{
					$month =  sanitize_title(self::$request_data['month']);		
					$new['from_period'] = date('Y-m-d', strtotime($year."-$month-01 00:00:00"));					
					$new['to_period'] = date('Y-m-d', strtotime($new['from_period']."  +1 month")-86400);					
				}
				elseif ( $new['period_type'] == 'quarter' )
				{
					$quarter =  sanitize_title(self::$request_data['quarter']);		
					$month = $quarter*3-2;						
					$new['from_period'] = date('Y-m-d', strtotime($year."-$month-01 00:00:00"));	
					$new['to_period'] = date('Y-m-d', strtotime($new['from_period']."  +3 month")-86400);							
				}
				elseif ( $new['period_type'] == 'half-year' )
				{					
					$half_year =  sanitize_title(self::$request_data['half-year']);		
					$month = $half_year==1?'01':'06';
					$new['from_period'] = date('Y-m-d', strtotime($year."-$month-01 00:00:00"));
					$new['to_period'] = date('Y-m-d', strtotime($new['from_period']."  +6 month")-86400);		
				}
				elseif ( $new['period_type'] == 'year' )
				{
					$new['from_period'] = date('Y-m-d', strtotime($year."-01-01 00:00:00"));
					$new['to_period'] = date('Y-m-d', strtotime($new['from_period']."  +1 year")-86400);		
				}					
				$new['target'] =  sanitize_title(self::$request_data['target']);		
				$new['plan_type'] =  sanitize_title(self::$request_data['plan_type']);
				$new['sum'] = 0;
				$amounts = isset(self::$request_data[$new['plan_type']])?self::$request_data[$new['plan_type']]:array();					
				foreach( $amounts as $object_id => $price )
				{						
					$new['sum'] += (int)$price;	
				} 	
				if ( self::$id != null )	
				{
					$data = usam_get_sales_plan(self::$id);
					if ( $data['plan_type'] != $new['plan_type'] )
					{
						usam_delete_sales_plan_amounts( self::$id );
					}
					usam_update_sales_plan( self::$id, $new );	
				}
				else			
				{			
					self::$id = usam_insert_sales_plan( $new );				
				}							
				foreach( $amounts as $object_id => $price )
				{					
					$amount['plan_id'] = self::$id;
					$amount['object_id'] = $object_id;
					$amount['price'] = $price;	
					usam_save_sales_plan_amounts( $amount );
				} 			
				return array( 'id' => self::$id ); 	
			break;				
		} 				
	}
		
	private static function controller_price_analysis() 
	{		
		switch( self::$action )
		{			
			case 'save':										
				$data['url'] = stripslashes_deep(self::$request_data['url']);							
				if ( isset(self::$request_data['product']) )
					$data['product_id'] = absint(self::$request_data['product']);
				if ( self::$id != null )
				{
					usam_update_product_competitor( self::$id, $data );
				}	
				else				
				{								
					$data['status'] = 2;					
					self::$id = usam_insert_product_competitor( $data );
				}
				return array( 'id' => self::$id ); 	
			break;				
		} 				
	}
			
	private static function controller_rate() 
	{		
		switch( self::$action )
		{			
			case 'save':	
				require_once( USAM_FILE_PATH . '/includes/directory/currency_rate.class.php' );			
				
				$new['basic_currency'] = substr(sanitize_text_field(self::$request_data['basic']), 0, 3);
				$new['currency'] = substr(sanitize_text_field(self::$request_data['currency']), 0, 3);
				$new['rate'] = !empty(self::$request_data['rate'])?(float)self::$request_data['rate']:0;	
				$new['markup'] = !empty(self::$request_data['markup'])?(float)self::$request_data['markup']:0;
				$new['autoupdate'] = !empty(self::$request_data['autoupdate'])?1:0;				
				if ( self::$id )
					$insert = usam_update_currency_rate( self::$id, $new );	
				else				
					$insert = self::$id = usam_insert_currency_rate( $new );		
				if ( $insert )
					usam_recalculate_price_products();
				return array( 'id' => self::$id ); 
			break;				
		} 				
	}
	
	private static function controller_trading_platform() 
	{		
		switch( self::$action )
		{			
			case 'save':		
				require_once( USAM_FILE_PATH . '/includes/exchange/feed.class.php');
				$new_rule['platform'] = sanitize_title(self::$request_data['platform']);		
				$new_rule['name'] = !empty(self::$request_data['name'])?sanitize_text_field(stripcslashes(self::$request_data['name'])):usam_get_name_integration( ['group_code' => 'trading-platforms', 'service_code' => $new_rule['platform']]);	
				$new_rule['type_price'] = !empty(self::$request_data['type_price'])?sanitize_text_field(self::$request_data['type_price']):usam_get_manager_type_price();
				$new_rule['active'] = !empty(self::$request_data['active'])?1:0;					
				$new_rule['start_date'] = usam_get_datepicker('start');
				$new_rule['end_date'] = usam_get_datepicker('end');									
							
				$metas['from_price'] = !empty(self::$request_data['from_price'])?(float)self::$request_data['from_price']:0;
				$metas['to_price'] = !empty(self::$request_data['to_price'])?(float)self::$request_data['to_price']:0;
				$metas['from_stock'] = !empty(self::$request_data['from_stock'])?usam_string_to_float(self::$request_data['from_stock']):0;
				$metas['to_stock'] = !empty(self::$request_data['to_stock'])?usam_string_to_float(self::$request_data['to_stock']):0;
				$metas['from_views'] = !empty(self::$request_data['from_views'])?absint(self::$request_data['from_views']):0;
				$metas['to_views'] = !empty(self::$request_data['to_views'])?absint(self::$request_data['to_views']):0;	
				$metas['location_id'] = !empty(self::$request_data['location'])?absint(self::$request_data['location']):0;	
				$metas['limit'] = !empty(self::$request_data['limit'])?absint(self::$request_data['limit']):0;		
				$metas['orderby'] = !empty(self::$request_data['orderby'])?sanitize_text_field(self::$request_data['orderby']):'id';		
				$metas['order'] = !empty(self::$request_data['order'])?sanitize_text_field(self::$request_data['order']):'DESC';		
																
				$metas['category'] = isset(self::$request_data['tax_input']['usam-category'])?array_map('intval', self::$request_data['tax_input']['usam-category']):[];
				$metas['catalog'] = isset(self::$request_data['tax_input']['usam-catalog'])?array_map('intval', self::$request_data['tax_input']['usam-catalog']):[];
				$metas['selection'] = isset(self::$request_data['tax_input']['usam-selection'])?array_map('intval', self::$request_data['tax_input']['usam-selection']):[];
				$metas['product_tag'] = isset(self::$request_data['tax_input']['product_tag'])?array_map('intval', self::$request_data['tax_input']['product_tag']):[];
				$metas['category_sale'] = isset(self::$request_data['tax_input']['usam-category_sale'])?array_map('intval', self::$request_data['tax_input']['usam-category_sale']):[];
				$metas['brands'] = isset(self::$request_data['tax_input']['usam-brands'])?array_map('intval', self::$request_data['tax_input']['usam-brands']):[];
				$metas['contractors'] = isset(self::$request_data['contractors'])?array_map('intval', self::$request_data['contractors']):[];				
				$metas['product_title'] = !empty(self::$request_data['product_title'])?sanitize_text_field(self::$request_data['product_title']):'';
				$metas['product_description'] = !empty(self::$request_data['product_description'])?sanitize_text_field(self::$request_data['product_description']):'';
				$metas['product_characteristics'] = !empty(self::$request_data['product_characteristics'])?array_map('intval', self::$request_data['product_characteristics']):[];				
				$metas['from_day'] = !empty(self::$request_data['from_day'])?absint(self::$request_data['from_day']):0;				
				$metas['to_day'] = !empty(self::$request_data['to_day'])?absint(self::$request_data['to_day']):0;
				$metas['campaign'] = !empty(self::$request_data['campaign'])?absint(self::$request_data['campaign']):0;	
				if ( self::$id )	
					usam_update_feed( self::$id, $new_rule );
				else		
					self::$id = usam_insert_feed( $new_rule );
				$platform_instance = usam_get_trading_platforms_class( self::$id );			
				if ( is_object($platform_instance) )
				{
					$default = $platform_instance->save_form();	
					if ( $default )
						$metas = array_merge( $default, $metas );
				}			
				foreach($metas as $key => $value)	
				{
					usam_update_feed_metadata( self::$id, $key, $value );
				} 
				return ['id' => self::$id]; 
			break;				
		} 				
	}
	
	private static function controller_subscription_renewal() 
	{		
		$user_id = get_current_user_id(); 		
		require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php'  );
		switch( self::$action )
		{			
			case 'save':	
				$insert['status']       = sanitize_title(self::$request_data['status']);	
				$insert['start_date']   = usam_get_datepicker('start');	
				$insert['end_date']     = usam_get_datepicker('end');	
				if ( self::$id != null )
				{
					if ( current_user_can('edit_subscription') )
						usam_update_subscription_renewal( self::$id, $insert );
				}
				elseif ( current_user_can('add_subscription') )
					self::$id = usam_insert_subscription_renewal( $insert );
				return array( 'id' => self::$id ); 
			break;				
		} 	
	}	
		
	private static function controller_shipped() 
	{
		switch( self::$action )
		{			
			case 'save':					
				self::$id = (int)self::$id;
				if ( !empty(self::$request_data['_shipped'][self::$id]) )
				{ 	
					$document = self::$request_data['_shipped'][self::$id];
					$document['date_insert'] = usam_get_datepicker('insert');
					$products = !empty($document['products'])?$document['products']:[];		
					self::$id = self::save_shipped_document( self::$id, $document, $products );
				}
				return array( 'id' => self::$id ); 
			break;							
		} 	
	}
	
	private static function controller_webform() 
	{		
		require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
		switch( self::$action )
		{			
			case 'save':	
				$data = [];									
				$data['title'] = sanitize_text_field(stripslashes(self::$request_data['name']));				
				$data['active'] = !empty(self::$request_data['active'])?1:0;					
				$data['start_date'] = '';
				$data['end_date'] = '';
				if( !empty(self::$request_data['start_date']) )
					$data['start_date'] = get_gmt_from_date(self::$request_data['start_date'], "Y-m-d H:i:s");
				if( !empty(self::$request_data['end_date']) )
					$data['end_date'] = get_gmt_from_date(self::$request_data['end_date'], "Y-m-d H:i:s");
				if ( !empty(self::$request_data['show_webform']) )				
					$data['actuation_time'] = self::$request_data['actuation_time'] === '' ? 0 : absint(self::$request_data['actuation_time']);	
				else
					$data['actuation_time'] = 0;
				if ( isset(self::$request_data['language']) )
					$data['language'] = sanitize_title(self::$request_data['language']);					
								
				if( isset(self::$request_data['template']) )
					$data['template'] = sanitize_title(self::$request_data['template']);	
				
				if( !empty(self::$request_data['code']) )
					$data['code'] = sanitize_title(self::$request_data['code']);
				else
					$data['code'] = substr(sanitize_title(self::$request_data['name']), 0, 20);
				
				if( isset(self::$request_data['webform_action']) )
					$data['action'] = sanitize_title(self::$request_data['webform_action']);	
								
				foreach( self::$request_data['settings'] as $key => &$value )
				{
					if( is_string($value) )
						$data['settings'][$key] = trim(sanitize_textarea_field(stripslashes($value)));
					elseif( is_numeric($value) )
						$data['settings'][$key] = absint($value);
					else
						$data['settings'][$key] = $value;
				}					
				if ( self::$id != null )
					usam_update_webform( self::$id, $data );	
				else
					self::$id = usam_insert_webform( $data );		
				return array( 'id' => self::$id ); 
			break;				
		} 
	}
	
	private static function controller_accumulative() 
	{		
		switch( self::$action )
		{			
			case 'save':	
				if ( ! isset(self::$request_data['sum'] ) || ! isset(self::$request_data['discounts'] ) )
					return array( 'ready' => 0 ); 
				$new_rule['name'] = sanitize_text_field( stripslashes(self::$request_data['name']));		
				$new_rule['active'] = !empty(self::$request_data['active'])?1:0;									
				$new_rule['sort'] = isset(self::$request_data['sort'])?(int)self::$request_data['sort']:100;	
							
				$new_rule['method'] = isset(self::$request_data['method']) && self::$request_data['method'] == 'bonus'?'bonus':'price';			
							
				$new_rule['type_prices'] = isset(self::$request_data['type_prices'])?self::$request_data['type_prices']:array();			
				$new_rule['start_date'] = '';
				$new_rule['end_date'] = '';
				if( !empty(self::$request_data['start_date']) )
					$new_rule['start_date'] = get_gmt_from_date(self::$request_data['start_date'], "Y-m-d H:i:s");
				if( !empty(self::$request_data['end_date']) )
					$new_rule['end_date'] = get_gmt_from_date(self::$request_data['end_date'], "Y-m-d H:i:s");
				
				$new_rule['start_calculation_date'] = '';
				$new_rule['end_date'] = '';
				if( !empty(self::$request_data['start_calculation_date']) )
					$new_rule['start_calculation_date'] = get_gmt_from_date(self::$request_data['start_calculation_date'], "Y-m-d H:i:s");
				if( !empty(self::$request_data['end_calculation_date']) )
					$new_rule['end_calculation_date'] = get_gmt_from_date(self::$request_data['end_calculation_date'], "Y-m-d H:i:s");

				$new_rule['period'] = isset(self::$request_data['period'])?substr(self::$request_data['period'], 0,1):'u';	
				$new_rule['period_from_type'] = isset(self::$request_data['period_from_type'])?substr(self::$request_data['period_from_type'], 0,1):'u';
				$new_rule['period_from'] = isset(self::$request_data['period_from'])?(int)self::$request_data['period_from']:100;							
							
				$layers = (array) self::$request_data['sum'];
				$discounts = (array) self::$request_data['discounts'];		
				$new_rule['layers'] = [];
				if ( !empty($discounts) ) 
				{					
					foreach( $discounts as $key => $discount)	
						if ( is_numeric($discount) )
							$new_rule['layers'][] = ['discount' => $discount, 'sum' => $layers[$key]];
					uksort($new_rule['layers'], function($a, $b){  return ($a['sum'] - $b['sum']); });	
				}				
				$calculation_accumulative = false;
				if ( self::$id != null )	
				{
					$rule = usam_get_data(self::$id, 'usam_accumulative_discount');
					if ( $new_rule['active'] && $rule['active'] != $new_rule['active'] && $new_rule['method'] != $rule['method'] )
						$calculation_accumulative = true;
					usam_edit_data( $new_rule, self::$id, 'usam_accumulative_discount' );	
				}
				else			
				{			
					if ( $new_rule['active'] )
						$calculation_accumulative = true;				
					$new_rule['date_insert'] = date( "Y-m-d H:i:s" );	
					self::$id = usam_add_data( $new_rule, 'usam_accumulative_discount' );				
				}				
				if ( $calculation_accumulative )
				{
					$user_ids = get_users(['fields' => ['ID']]);
					usam_create_system_process( __("Пересчет накопительной скидки", "usam" ), 1, 'calculation_accumulative_discount_customer', count($user_ids), 'calculation_accumulative_discount' );
				}
				return ['id' => self::$id]; 
			break;				
		} 
	}
	
	private static function controller_certificate() 
	{		
		return self::save_coupon( 'certificate' );
	}
	
	private static function controller_coupon() 
	{		
		$coupon_type = isset(self::$request_data['coupon_type'])?sanitize_text_field(self::$request_data['coupon_type']):'coupon';	
		return self::save_coupon( $coupon_type ); 
	}
	
	private static function controller_generate_certificate() 
	{		
		return self::generate_coupon( 'certificate' );
	}
	
	private static function controller_generate_coupon() 
	{		
		return self::generate_coupon( 'coupon' ); 
	}

	private static function controller_crosssell() 
	{		
		switch( self::$action )
		{			
			case 'save':	
				if ( empty(self::$request_data['crosssell']) )
					return ['ready' => 0]; 
				
				$crosssell = self::$request_data['crosssell'];		
				$crosssell['active'] = !empty(self::$request_data['active'])?1:0;					
				if ( !empty(self::$request_data['conditions']) )
				{
					$conditions = [];				
					foreach(self::$request_data['conditions']['type'] as $id => $type )
					{	
						if ( isset(self::$request_data['conditions']['logic'][$id]) )
							$logic = self::$request_data['conditions']['logic'][$id];
						else
							continue;
						if ( isset(self::$request_data['conditions']['value'][$id]) )
							$value = self::$request_data['conditions']['value'][$id];
						else
							continue;
						
						if ( isset(self::$request_data['conditions']['logic_operator'][$id]) )
							$logic_operator = self::$request_data['conditions']['logic_operator'][$id];
						else
							$logic_operator = 'AND';
						$conditions[] = ['type' => $type, 'logic' => $logic, 'value' => $value, 'logic_operator' => $logic_operator];
					}										
				}
				if ( !empty($conditions) )
					$crosssell['conditions'] = $conditions;
				else
					$crosssell['active'] = 0;
				
				if ( self::$id != null )	
				{				
					$result = usam_edit_data( $crosssell, self::$id, 'usam_crosssell_conditions' );				
				}
				else			
				{				
					$crosssell['date_insert'] = date("Y-m-d H:i:s");	
					self::$id = usam_add_data( $crosssell, 'usam_crosssell_conditions' );	
					$result = true;
					global $wpdb;
					$wpdb->query("DELETE FROM ".USAM_TABLE_PRODUCT_META." WHERE meta_key = 'increase_sales_time'");	
				}
				if ( $result )
					usam_process_calculate_increase_sales_product( self::$id, 0 );	
				return ['id' => self::$id]; 
			break;				
		} 
	}
	
	private static function controller_publishing_rule() 
	{		
		switch( self::$action )
		{			
			case 'save':	
				$new['name'] =  sanitize_text_field(stripcslashes(self::$request_data['name']));	
				$new['terms']['category'] = isset(self::$request_data['tax_input']['usam-category'])?array_map('intval', self::$request_data['tax_input']['usam-category']):array();
				$new['terms']['category_sale'] = isset(self::$request_data['tax_input']['usam-category_sale'])?array_map('intval', self::$request_data['tax_input']['usam-category_sale']):array();
				$new['terms']['brands'] = isset(self::$request_data['tax_input']['usam-brands'])?array_map('intval', self::$request_data['tax_input']['usam-brands']):array();	
				$new['vk_users'] = isset(self::$request_data['vk_users'])?array_map('intval', self::$request_data['vk_users']):array();
				$new['vk_groups'] = isset(self::$request_data['vk_groups'])?array_map('intval', self::$request_data['vk_groups']):array();
				$new['ok_groups'] = isset(self::$request_data['ok_groups'])?array_map('intval', self::$request_data['ok_groups']):array();
				$new['active'] = !empty(self::$request_data['active'])?1:0;						
				$new['start_date'] = usam_get_datepicker('start');
				$new['end_date'] = usam_get_datepicker('end');		
				$new['pricemin'] = usam_string_to_float(self::$request_data['pricemin']);	
				$new['pricemax'] = usam_string_to_float(self::$request_data['pricemax']);
				$new['minstock'] = absint(self::$request_data['minstock']);		
				$new['quantity'] = absint(self::$request_data['quantity']);	
				$new['exclude'] = absint(self::$request_data['exclude']);						
				$new['periodicity'] = absint(self::$request_data['periodicity']);						
				$new['from_hour'] = !empty(self::$request_data['from_hour'])?absint(self::$request_data['from_hour']):'';		
				$new['to_hour'] = !empty(self::$request_data['from_hour'])?absint(self::$request_data['to_hour']):'';
				$new['campaign'] = !empty(self::$request_data['campaign'])?absint(self::$request_data['campaign']):0;
				
				if ( self::$id != null )	
				{				
					usam_edit_data( $new, self::$id, 'usam_vk_publishing_rules' );					
				}
				else			
				{							
					self::$id = usam_add_data( $new, 'usam_vk_publishing_rules' );		 
				}
				return array( 'id' => self::$id ); 
			break;				
		} 
	}
		
	private static function controller_notification() 
	{		
		switch( self::$action )
		{			
			case 'save':	
				$phone = absint(self::$request_data['phone']);				
				$new['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));
				$new['active'] = !empty(self::$request_data['active'])?1:0;		
				$new['email'] = sanitize_text_field(self::$request_data['email']);			
				$new['phone'] = sanitize_text_field(self::$request_data['phone']);		
				$new['messenger'] = sanitize_text_field(self::$request_data['messenger']);				
				$new['events'] = isset(self::$request_data['events'])?self::$request_data['events']:array();	
				$new['contacts'] = isset(self::$request_data['contacts_ids'])?self::$request_data['contacts_ids']:array();	
				
				$event_conditions = isset(self::$request_data['conditions'])?self::$request_data['conditions']:array();				
					
				if ( empty($new['email']) && empty($new['phone']) )
				{
					return array( 'error' => 1 ); 
				}				
				foreach ($event_conditions as $event => $conditions)	
				{
					$new['events'][$event]['conditions'] = array();
					foreach ($conditions['type'] as $key => $type)	
					{ 		
						$new['events'][$event]['conditions'][$type][] = $conditions['value'][$key];	
					}
				}	
				if ( self::$id != null )	
				{
					usam_edit_data( $new, self::$id, 'usam_notifications' );	
				}
				else			
				{			
					self::$id = usam_add_data( $new, 'usam_notifications' );				
				}		
				return array( 'id' => self::$id ); 
			break;				
		} 
	}
	
	private static function controller_search_engine_location() 
	{		
		$user_id = get_current_user_id(); 
		if ( user_can( $user_id, 'view_seo_setting' ) )			
		{
			switch( self::$action )
			{			
				case 'save':	
					$new['location'] = absint(self::$request_data['location']);			
					$new['search_engine'] = sanitize_text_field(self::$request_data['search_engine']);				
					if ( self::$id != null )	
					{
						usam_edit_data( $new, self::$id, 'usam_search_engine_location' );	
					}
					else			
					{			
						self::$id = usam_add_data( $new, 'usam_search_engine_location' );				
					}
					return array( 'id' => self::$id ); 
				break;				
			} 
		}
	}
	
	private static function controller_search_engine_region() 
	{		
		$user_id = get_current_user_id(); 
		if ( user_can( $user_id, 'view_seo_setting' ) )			
		{
			switch( self::$action )
			{			
				case 'save':	
					if( !empty(self::$request_data['location']) )
					{	
						global $wpdb;			
					
						$new['location_id'] = absint(self::$request_data['location']);	
						$new['name'] = sanitize_text_field(stripcslashes(self::$request_data['name']));		
						$new['code'] = sanitize_textarea_field(stripslashes(self::$request_data['code']));	
						$new['search_engine'] = sanitize_textarea_field(stripslashes(self::$request_data['search_engine']));	
						$new['sort'] = absint(self::$request_data['sort']);	
						$new['active'] = !empty(self::$request_data['active'])?1:0;			
												
						$_formats = array( 'location_id' => '%d', 'name' => '%s', 'code' => '%s', 'search_engine' => '%s', 'sort' => '%d', 'active' => '%d' );
						$formats = array();
						foreach( $new as $key => $value) 	
						{
							if ( isset($_formats[$key]) )
								$formats[] = $_formats[$key];
							else				
								unset($new[$key]);
						}					
						if ( self::$id != null )
						{
							$where  = array( 'id' => self::$id );	
							$result = $wpdb->update( USAM_TABLE_SEARCH_ENGINE_REGIONS, $new, $where, $formats );
						}
						else
						{
							$result = $wpdb->insert( USAM_TABLE_SEARCH_ENGINE_REGIONS, $new, $formats );
							self::$id = $wpdb->insert_id;
						}			
					}				
					return array( 'id' => self::$id ); 
				break;				
			} 
		}
	}
	
	private static function controller_review() 
	{	
		switch( self::$action )
		{			
			case 'save':	
				$data = array();				
				if( isset(self::$request_data['review_text']) )
					$data['review_text'] = sanitize_textarea_field(stripcslashes(self::$request_data['review_text']));
				
				if( isset(self::$request_data['name']) )
					$data['title'] = sanitize_textarea_field(stripcslashes(self::$request_data['name']));
				
				if( isset(self::$request_data['review_response']) )
					$data['review_response'] = self::$request_data['review_response'];
				
				if( isset(self::$request_data['fields']['rating']) )
					$data['rating'] = absint(self::$request_data['fields']['rating']);
				
				if( isset(self::$request_data['user_id']) )
					$data['user_id'] = absint(self::$request_data['user_id']);				
				
				if( isset(self::$request_data['status']) )
					$data['status'] = absint(self::$request_data['status']);				
				
				if( isset(self::$request_data['date_insert']) )
					$data['date_insert'] = get_gmt_from_date(date( "Y-m-d H:i:s", strtotime(self::$request_data['date_insert']) ));					
			
				if ( self::$id != null )
					usam_update_review( self::$id, $data );	
				else
					self::$id = usam_insert_review( $data );			
				
				$properties = usam_get_properties(['type' => 'webform']);
				foreach ( $properties as $property )
				{
					if ( $property->field_type == 'rating' )
						continue;
					if( isset(self::$request_data['fields'][$property->code]) )
					{
						if ( !is_array(self::$request_data['fields'][$property->code]) )
							$value = sanitize_text_field(stripcslashes(self::$request_data['fields'][$property->code]));
						else
							$value = self::$request_data['fields'][$property->code];
						usam_update_review_metadata( self::$id, 'webform_'.$property->code, $value );						
					}
					else
						usam_delete_review_metadata( self::$id, 'webform_'.$property->code );
				}
				return ['id' => self::$id];
			break;				
		} 
	}	
	
	private static function controller_loyalty_program() 
	{	
		switch( self::$action )
		{			
			case 'save':	
				$new_rule['name'] =  sanitize_text_field(stripslashes(self::$request_data['name']));
				$new_rule['description'] = sanitize_textarea_field(stripslashes(self::$request_data['description']));				
				$new_rule['active'] = !empty(self::$request_data['active'])?1:0;				
				$new_rule['value'] = (int)self::$request_data['value'];		
				$new_rule['rule_type'] = sanitize_title(self::$request_data['rule_type']);	
				$new_rule['what'] = sanitize_title(self::$request_data['what']);
				$new_rule['total_purchased'] = absint(self::$request_data['total_purchased']);				
				
				$new_rule['start_date'] = '';
				$new_rule['end_date'] = '';
				if( !empty(self::$request_data['start_date']) )
					$new_rule['start_date'] = get_gmt_from_date(self::$request_data['start_date'], "Y-m-d H:i:s");
				if( !empty(self::$request_data['end_date']) )
					$new_rule['end_date'] = get_gmt_from_date(self::$request_data['end_date'], "Y-m-d H:i:s");
				if ( self::$id != null )	
				{					
					usam_edit_data( $new_rule, self::$id, 'usam_bonuses_rules' );					
				}
				else			
				{				
					$new_rule['date_insert'] = date( "Y-m-d H:i:s" );					
					self::$id = usam_add_data( $new_rule, 'usam_bonuses_rules' );				
				}	
				return array( 'id' => self::$id ); 
			break;				
		} 
	}
	
	private static function controller_rule_coupon_order() 
	{		
		switch( self::$action )
		{			
			case 'save':	
				$new_rule['percentage_of_use'] = (float)self::$request_data['percentage_of_use'];			
				$new_rule['totalprice'] = (float)self::$request_data['totalprice'];
				$new_rule['day'] = (float)self::$request_data['day'];			
				return self::save_rule_coupon( 'order', $new_rule );	
			break;				
		} 
	}
		
	private static function controller_rule_coupon() 
	{		
		switch( self::$action )
		{			
			case 'save':					
				$rule_type = sanitize_title(self::$request_data['rule_type']);
				return self::save_rule_coupon( $rule_type );	
			break;				
		} 
	}	
	
	private static function controller_commission() 
	{	
		switch( self::$action )
		{			
			case 'save':	
				$new['status'] = sanitize_title(self::$request_data['status']);		
				$new['seller_id'] = absint(self::$request_data['seller_id']);	
				$new['sum'] = absint(self::$request_data['sum']);	
				if ( self::$id != null )	
				{
					$result = usam_update_marketplace_commission( self::$id, $new );
				}
				else			
				{			
					self::$id = usam_insert_marketplace_commission( $new );				
				}	
				return ['id' => self::$id]; 
			break;				
		} 
	}
	
	private static function controller_seller() 
	{	
		switch( self::$action )
		{			
			case 'save':	
			//	$new['status'] = sanitize_title(self::$request_data['status']);							
				$new['manager_id'] = absint(self::$request_data['manager_id']);	
				if ( self::$id != null )	
				{
					$result = usam_update_seller( self::$id, $new );
				}
				else			
				{			
			//		self::$id = usam_insert_seller( $new );				
				}	
				$locations = isset(self::$request_data['locations'])?array_map('absint', self::$request_data['locations']):[];		
				usam_update_seller_metadata( self::$id, 'locations', $locations );				
				return ['id' => self::$id]; 
			break;				
		} 
	}	
	
	private static function controller_file() 
	{	
		switch( self::$action )
		{			
			case 'save':					
				$new = [];
				if ( isset(self::$request_data['folder']) )
					$new['folder_id'] = absint(self::$request_data['folder']);					
				if ( !empty(self::$request_data['status']) )
					$new['status'] = sanitize_title(self::$request_data['status']);	
				if ( isset(self::$request_data['user_id']) )
					$new['user_id'] = absint(self::$request_data['user_id']);
				$new['date_insert'] = usam_get_datepicker('insert');
				$metas = [];
				if ( isset(self::$request_data['description']) )
					$metas['description'] = trim(sanitize_textarea_field(stripslashes(self::$request_data['description'])));
				if ( isset(self::$request_data['maximum_load']) )				
					$metas['maximum_load'] = absint(self::$request_data['maximum_load']);
				
				$metas['thumbnail_url'] = sanitize_text_field(self::$request_data['thumbnail_url']);
				$metas['thumbnail_id'] = absint(self::$request_data['thumbnail_id']);
				if ( self::$id != null )	
				{
					$new['title'] = sanitize_text_field(stripcslashes(self::$request_data['name']));					
					$result = usam_update_file( self::$id, $new );						
					foreach ( $metas as $key => $value )
					{
						if ( $value )
							usam_update_file_metadata(self::$id, $key, $value );
						else
							usam_delete_file_metadata(self::$id, $key );
					}
					$result = ['id' => self::$id];
				}
				else			
				{			
					$attachments = !empty(self::$request_data['fileupload'])?self::$request_data['fileupload']:[];					
					$new['type'] = 'loaded';
					foreach ( $attachments as $file_id )
						usam_attach_file( $file_id, $new, $metas );				
					if ( !empty($new['folder_id']) )
						$result = ['folder' => $new['folder_id']];
					else					
						$result = ['id' => self::$id];
				}					
				$new_groups = isset(self::$request_data['groups'])?array_map('intval',self::$request_data['groups']):[];								
				self::save_meta( 'file', 'group', $new_groups );
				return $result; 
			break;				
		} 
	}	
	
	private static function controller_ftp_file() 
	{	
		switch( self::$action )
		{			
			case 'save':	
				if ( self::$id != null )	
				{
					$file = sanitize_textarea_field(stripcslashes(self::$request_data['file']));
					$fd = fopen(USAM_EXCHANGE_DIR.self::$id, 'w');
					fwrite($fd, $file);
					fclose($fd);
				}							
				return ['id' => self::$id]; 
			break;				
		} 
	}	
	
	private static function controller_marking_code() 
	{	
		require_once( USAM_FILE_PATH .'/includes/product/marking_code.class.php');
		switch( self::$action )
		{			
			case 'save':	
				$new['code'] = sanitize_text_field(self::$request_data['code']);		
				$new['product_id'] = absint(self::$request_data['product_id']);	
				$new['storage_id'] = absint(self::$request_data['storage_id']);
				$new['status'] = sanitize_title(self::$request_data['status']);	
				if ( self::$id != null )	
					$result = usam_update_marking_code( self::$id, $new );
				else			
					self::$id = usam_insert_marking_code( $new );	
				return array( 'id' => self::$id ); 
			break;				
		} 
	}	
	
	private static function controller_unit() 
	{	
		switch( self::$action )
		{	
			case 'save':				
				$new['title'] =  sanitize_text_field(stripcslashes(self::$request_data['name']));
				$new['code'] = !empty(self::$request_data['code'])? sanitize_title(self::$request_data['code']):sanitize_title(self::$request_data['name']);
				$new['short'] = sanitize_text_field(self::$request_data['short']);					
				$new['accusative'] = sanitize_text_field(stripcslashes(self::$request_data['accusative']));
				$new['in'] = sanitize_text_field(stripcslashes(self::$request_data['in']));
				$new['plural'] = sanitize_text_field(stripcslashes(self::$request_data['plural']));
				$new['international_code'] = sanitize_title(self::$request_data['international_code']);
				$new['numerical'] = preg_replace("/[^0-9]/", '', self::$request_data['numerical']);				
				$new['external_code'] = sanitize_title(self::$request_data['external_code']);				
				if ( self::$id != null )	
				{
					usam_edit_data( $new, self::$id, 'usam_units_measure', false );
				}
				else			
				{			
					self::$id = usam_add_data( $new, 'usam_units_measure', false );				
				}			
			break;				
		}
		return array( 'id' => self::$id ); 			
	}	
	
	private static function controller_phone() 
	{	
		switch( self::$action )
		{	
			case 'save':				
				$new['name'] =  sanitize_text_field(stripcslashes(self::$request_data['name']));			
				$new['phone'] = preg_replace("/[^0-9]/", '', self::$request_data['phone']);				
				$new['format'] = sanitize_text_field(self::$request_data['format']);		
				$new['whatsapp'] = !empty(self::$request_data['whatsapp'])?1:0;
				$new['viber'] = !empty(self::$request_data['viber'])?1:0;	
				$new['telegram'] = !empty(self::$request_data['telegram'])?1:0;
				$new['skype'] = !empty(self::$request_data['skype'])?1:0;				
				$new['sort'] = absint(self::$request_data['sort']);	
				$new['location_id'] = absint(self::$request_data['location_id']);					
				if ( self::$id != null )
				{
					$item = usam_get_data( self::$id, 'usam_phones' );					
					$result = usam_edit_data( $new, self::$id, 'usam_phones' );					
					if ( $result && $item['phone'] === get_option( 'usam_shop_phone' ) )		
						update_option( 'usam_shop_phone', $new['phone'] );
				}
				else		
				{
					self::$id = usam_add_data( $new, 'usam_phones' );		
					if ( !get_option( 'usam_shop_phone' ) )		
						update_option( 'usam_shop_phone', $new['phone'] );
				}
			break;				
		}
		return ['id' => self::$id]; 			
	}
	
	private static function controller_product_importer() 
	{
		$metas = array();
		$metas['post_status'] = isset(self::$request_data['post_status'])?sanitize_title(self::$request_data['post_status']):'';
		$metas['product_category'] = isset(self::$request_data['product_category'])?(int)self::$request_data['product_category']:0;		
		return self::actions_import('product_import', $metas );
	}
	
	private static function controller_contact_importer() 
	{
		$metas = array();
		$metas['groups'] = isset(self::$request_data['groups'])?sanitize_title(self::$request_data['groups']):'';
		return self::actions_import('contact_import', $metas );
	}
	
	private static function controller_company_importer() 
	{
		$metas = array();
		$metas['groups'] = isset(self::$request_data['groups'])?sanitize_title(self::$request_data['groups']):'';
		return self::actions_import('company_import', $metas );
	}
	
	private static function controller_order_importer() 
	{
		return self::actions_import('order_import');
	}
	
	private static function controller_balance_information() 
	{	
		switch( self::$action )
		{	
			case 'save': 					
				$new['name'] =  sanitize_text_field(stripcslashes(self::$request_data['name']));
				$info = (array) self::$request_data['info'];
				$quantity = (array) self::$request_data['quantity'];		
				$new_layer = array();
				if ( !empty($quantity) ) 
				{					
					foreach ($quantity as $key => $q)	
					{
						if ( is_numeric($q) )
							$new_layer[$q] = $info[$key];
					}
					ksort($new_layer);
					$new['layers'] =  $new_layer;
				}			
				$new['category'] = isset(self::$request_data['tax_input']['usam-category'])?array_map('intval', self::$request_data['tax_input']['usam-category']):array();
				$new['catalogs'] = isset(self::$request_data['tax_input']['usam-catalog'])?array_map('intval', self::$request_data['tax_input']['usam-catalog']):array();
				$new['brands'] = isset(self::$request_data['tax_input']['usam-brands'])?array_map('intval', self::$request_data['tax_input']['usam-brands']):array();				
				if ( self::$id != null )								
					usam_edit_data( $new, self::$id, 'usam_balance_information', false );	
				else									
					self::$id = usam_add_data( $new, 'usam_balance_information', false );
			break;				
		}
		return array( 'id' => self::$id ); 			
	}	 
		
	private static function controller_account_transaction() 
	{	
		switch( self::$action )
		{	
			case 'save':				
				require_once( USAM_FILE_PATH . '/includes/customer/account_transaction.class.php' );
				$new['description'] =  sanitize_text_field(stripcslashes(self::$request_data['description']));	
				$new['sum'] = usam_string_to_float( self::$request_data['sum'] );					
				if ( self::$id != null )	
				{
					usam_update_account_transaction( self::$id, $new );	
				}
				else			
				{			
					$new['account_id'] = (int)self::$request_data['account_id'];
					$account = usam_get_customer_account( $new['account_id'] ); 	
					if ( !empty($account) )
					{
						$new['type_transaction'] = (int)self::$request_data['type_transaction'];	
						self::$id = usam_insert_account_transaction( $new );				
					}	
					else
					{
						usam_set_user_screen_error( sprintf(__('Счет %s не существует', 'usam'), $new['account_id']) );
						return false; 
					}
				}	
			break;				
		}
		return array( 'id' => self::$id ); 			
	}
	
	private static function controller_license() 
	{	
		switch( self::$action )
		{	
			case 'save':			
				$new['license_holder'] = isset(self::$request_data['license_holder'])?sanitize_text_field(self::$request_data['license_holder']):'';			
				if ( self::$id != null )	
					usam_update_license( self::$id, $new );	
				else			
				{								
					$new['license'] =  sanitize_text_field(self::$request_data['license']);	
					$api = new USAM_Service_API();
					$request = $api->registration( $new );		
					if ( $request )
					{ 
						$new['license_type'] = strtoupper($request['license_type']);
						$new['software_type'] = $request['software_type'];
						$new['status'] = $request['status'];
						$new['software'] = $request['software'];
						if ( !empty($request['license_start_date']) )					
							$new['license_start_date'] = date( "Y-m-d", strtotime($request['license_start_date']));
						if ( !empty($request['license_end_date']) )							
							$new['license_end_date'] = date( "Y-m-d", strtotime($request['license_end_date']));					
						usam_insert_license( $new );	
					}
				}					
			break;				
		}
		return ['id' => 0];	
	}	

	private static function controller_cart() 
	{	
		switch( self::$action )
		{	
			case 'save':			
				global $wpdb;
				$contact_id = absint(self::$request_data['user']);
				$contact = usam_get_contact( $contact_id, 'user_id' );
				if ( $contact )
				{
					$new['contact_id'] = $contact['id'];			
					$new['user_id'] = $contact['user_id'];	
					$result = $wpdb->update( USAM_TABLE_USERS_BASKET, $new, ['id' => self::$id] );	
					return ['id' => self::$id];					
				}
			break;				
		}		
	}	
	
	private static function controller_set() 
	{	
		switch( self::$action )
		{	
			case 'save':			
				global $wpdb;
				require_once(USAM_FILE_PATH.'/includes/product/set.class.php');
				require_once(USAM_FILE_PATH.'/includes/product/products_set_query.class.php');
				require_once(USAM_FILE_PATH.'/includes/product/product_set.class.php');
				
				$new['name'] =  sanitize_text_field(stripcslashes(self::$request_data['name']));
				$new['purchase_name'] = sanitize_text_field(stripcslashes(self::$request_data['purchase_name']));		
				$new['status'] = !empty(self::$request_data['active'])?'publish':'draft';	
				$new['thumbnail_id'] = absint(self::$request_data['thumbnail'] );				
				if ( self::$id != null )		
				{
					usam_update_set( self::$id, $new );
					$products = usam_get_products_sets_query(['set' => self::$id]);
				}
				else
				{
					self::$id = usam_insert_set( $new );									
					$products = [];
				}
				$new_products = !empty(self::$request_data['products'])?self::$request_data['products']:[];			
				$products_ids = [];
				foreach($new_products as $key => $product)
				{
					$product['set_id'] = self::$id;				
					$product['quantity'] = usam_string_to_float( $product['quantity'] );
					$product['product_id'] = absint($product['product_id']);
					$product['category_id'] = isset($product['category_id'])?absint($product['category_id']):0;
					$product['status'] = !empty($product['status'])?1:0;
					if ( stripos($key, '+') === false )
					{
						usam_update_product_set( $key, $product );
						$products_ids[] = $key;
					}
					else
						usam_insert_product_set( $product );
				}
				foreach($products as $product)	
				{
					if ( !in_array( $product->id, $products_ids )	)
						usam_delete_product_set( $product->id );
				}
				$metas['catalog'] = isset(self::$request_data['tax_input']['usam-catalog'])?array_map('intval', self::$request_data['tax_input']['usam-catalog']):[];	
				$metas['role'] = isset(self::$request_data['roles'])?stripslashes_deep(self::$request_data['roles']):[];	
				foreach($metas as $key => $value)	
					self::save_meta( 'set', $key, $value );
				return ['id' => self::$id];	
			break;				
		}		
	}	
}
?>
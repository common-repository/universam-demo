<?php
// Выбор статуса
function usam_get_status_dropdown( $type, $current_status, $args ) 
{
	$args = array_merge(['additional_attributes' => '', 'class' => '', 'id' => '', 'name' => 'status'], $args );		
	$statuses = usam_get_object_statuses(['type' => $type]);	
	$out = sprintf(
		'<select name="%1$s" id="%2$s" class="%3$s" %4$s>',
		/* %1$s */ esc_attr( $args['name'] ),
		/* %2$s */ esc_attr( $args['id'] ),
		/* %3$s */ esc_attr( $args['class'] ),
		/* %4$s */ $args['additional_attributes']
	);	
	foreach( $statuses as $status ) 
	{	
		$selected = $status->internalname == $current_status ? 'selected="selected"' :'';
		if ( $status->visibility || $status->internalname == $current_status )
			$out .= '<option value="'.$status->internalname.'" '.$selected.'>'.stripcslashes( $status->name ).'</option>';
	}
	$out .= '</select>';	
	return $out;
}

function usam_get_payment_gateway_dropdown( $selected = '', $attr = [] ) 
{	
	$name = !empty($attr['name'])?$attr['name']:'payment';
	$class = !empty($attr['class'])?'class="'.$attr['class'].'"':'';
	$not_selected_text = !empty($attr['not_selected_text'])?$attr['not_selected_text']:__('Не выбрано', 'usam');
	?>			
	<select <?php echo $class; ?> name = "<?php echo $name; ?>">
		<option <?php selected( '0', $selected ); ?> value="0"><?php echo $not_selected_text; ?></option>
		<?php
		$gateways = usam_get_payment_gateways();
		foreach ($gateways as $gateway )
		{
			?>
			<option value="<?php echo esc_attr( $gateway->id ); ?>" <?php selected( $gateway->id, $selected ); ?>><?php echo $gateway->name; ?></option>
			<?php
		}
		?>															
	</select>	
	<?php
}	

function usam_get_storage_dropdown( $selected = '', $attr = [], $attr_storages = [] ) 
{	
	$attr['name'] = !empty($attr['name'])?$attr['name']:'storage';
	$attr['class'] = !empty($attr['class'])?$attr['class']:'';	
	$not_selected_text = !empty($attr['not_selected_text'])?$attr['not_selected_text']:__('Не выбрано', 'usam');	
	?>			
	<select 
		<?php
			foreach ( $attr as $name => $value )
			{
				if ( $name != 'not_selected_text' )
					echo " $name='$value' ";
			}
			?>>	
		<option <?php selected( '0', $selected ); ?> value="0"><?php echo $not_selected_text; ?></option>
		<?php			
		foreach( usam_get_storages( $attr_storages ) as $storage )
		{
			?><option value="<?php echo esc_attr( $storage->id ); ?>" <?php selected( $storage->id, $selected ); ?>><?php echo $storage->title; ?></option><?php
		}
		?>															
	</select>	
	<?php
}

function usam_get_delivery_service_dropdown( $selected = '', $attr = array() ) 
{	
	$name = !empty($attr['name'])?$attr['name']:'shipping';
	$class = !empty($attr['class'])?'class="'.$attr['class'].'"':'';
	$not_selected_text = !empty($attr['not_selected_text'])?$attr['not_selected_text']:__('Не выбрано', 'usam');
	?>			
	<select <?php echo $class; ?> name = "<?php echo $name; ?>">
		<option <?php selected( '0', $selected ); ?> value="0"><?php echo $not_selected_text; ?></option>
		<?php
		$delivery_service = usam_get_delivery_services( array( 'active' => 'all' ) );
		foreach ($delivery_service as $service )
		{
			?>
			<option value="<?php echo esc_attr( $service->id ); ?>" <?php selected( $service->id, $selected ); ?>><?php echo $service->name; ?></option>
			<?php
		}
		?>															
	</select>	
	<?php
}

function usam_select_bank_accounts( $selected = '', $attr = [], $args = [] ) 
{
	require_once(USAM_FILE_PATH.'/includes/crm/bank_accounts_query.class.php');	
	if ( !$args )
	{
		static $own_companies = null, $own_companies_bank_accounts = null;
		if ( $own_companies === null )
			$own_companies = usam_get_companies(['type' => 'own']);
		
		if ( !$own_companies )
			return '';
		
		$ids = array();
		$selected_companies = array();
		foreach ( $own_companies as $company )
		{
			$ids[] = $company->id;
			$selected_companies[$company->id] = $company;
		}
		if ( $own_companies_bank_accounts === null )
			$own_companies_bank_accounts = usam_get_bank_accounts(['company' => $ids]);
		$bank_accounts = $own_companies_bank_accounts;			
	}
	else
	{
		$companies = usam_get_companies( $args );
		if ( !$companies )
			return '';
		
		$ids = array();
		$selected_companies = array();
		foreach ( $companies as $company )
		{
			$ids[] = $company->id;
			$selected_companies[$company->id] = $company;
		}
		$bank_accounts = usam_get_bank_accounts(['company' => $ids]);			
	}		
	if ( !empty($attr['not_selected_text']) )
	{
		$not_selected_text = $attr['not_selected_text'];
		unset($attr['not_selected_text']);
	}
	elseif ( !$bank_accounts )
		return '';
	?>		
	<select 
			<?php
			foreach ( $attr as $name => $value )
				echo " $name='$value' ";
			?>>		
		<?php
		if ( !empty($not_selected_text) )
		{
			?>
			<option value="0" <?php selected($selected, 0); ?> ><?php echo $not_selected_text; ?></option>
			<?php	
		}		
		foreach( $bank_accounts as $acc )
		{						
			$currency = usam_get_currency_sign( $acc->currency );
			?>             
			<option value="<?php echo $acc->id; ?>" <?php selected($selected, $acc->id); ?> ><?php echo $selected_companies[$acc->company_id]->name." - $acc->name ( $currency )"; ?></option>
			<?php
		}
		?>
	</select>	
	<?php	
}

function usam_select_companies( $selected = '', $attr = array(), $args = array() ) 
{
	$args = empty($args)?array('type' => 'own'):$args;
	$companies = usam_get_companies( $args );	
	
	if ( !empty($attr['not_selected_text']) )
	{
		$not_selected_text = $attr['not_selected_text'];
		unset($attr['not_selected_text']);
	}
	?>		
	<select 
			<?php
			foreach ( $attr as $name => $value )
				echo " $name='$value' ";
			?>>		
		<?php				
		if ( !empty($not_selected_text) )
		{
			?>
			<option value="0" <?php selected($selected, 0); ?> ><?php echo $not_selected_text; ?></option>
			<?php	
		}
		foreach( $companies as $company )
		{		
			?>             
			<option value="<?php echo $company->id; ?>" <?php selected($selected, $company->id); ?> ><?php echo $company->name; ?></option>
			<?php
		}
		?>
	</select>	
	<?php	
}

function usam_select_currencies( $selected = '', $attr = array() ) 
{
	$selected = strtoupper($selected);
	$attr['class'] = isset($attr['class'])?$attr['class']:'chzn-select';
	?>		
	<select 
			<?php
			foreach ( $attr as $name => $value )
				echo " $name='$value' ";
			?>>		
		<?php		
		$currencies = usam_get_currencies( );
		foreach( $currencies as $currency )
		{						
			?>               
			<option value="<?php echo $currency->code; ?>" <?php selected($selected, $currency->code); ?> ><?php echo $currency->code." ( $currency->name )"; ?></option>
			<?php
		}
		?>
	</select>	
	<?php
}

function usam_select_sales_area( $selected = '', $attr = array() ) 
{
	?>		
	<select 
			<?php
			foreach ( $attr as $name => $value )
				echo " $name='$value' ";
			?>>	
		<option value="" <?php selected($selected, ''); ?> ><?php _e('Все зоны', 'usam'); ?></option>			
		<?php		
		$sales_area = usam_get_sales_areas();
		foreach( $sales_area as $group )
		{						
			?><option value="<?php echo $group['id']; ?>" <?php selected($selected, $group['id']); ?> ><?php echo $group['name']; ?></option><?php
		}
		?>
	</select>	
	<?php
}

function usam_select_manager( $selected = '', $attr = array(), $args = array() ) 
{	
	$class = 'chzn-select';
	if ( isset($attr['class'] ) )
		$attr['class'] = $attr['class'].' '.$class;
	else
		$attr['class'] = $class;
	$out = "	
	<select 
			";
			foreach ( $attr as $name => $value )
				$out .= " $name='$value' ";
			$out .= '>';
		$out .= '<option value="0" '. selected($selected, 0, false).'>'.__('Нет','usam').'</option>';
		
		if ( !isset($args['orderby']) )
			$args['orderby'] = 'nicename';
		
		if ( !isset($args['role__in']) )
			$args['role__in'] = array('editor','shop_manager','administrator', 'shop_crm');
				
		$args['fields'] = array( 'ID','display_name');
		$users = get_users( $args );
		foreach( $users as $user )
		{					              
			$out .= "<option value='$user->ID' ".selected($selected, $user->ID, false)."'>$user->display_name</option>";
		}
	$out .= "</select>";
	return $out;
}

function usam_get_select_type_md( $selected = '', $attr = array() ) 
{
	$currency = usam_get_currency_sign();
	$class = 'select_type_md';
	if ( isset($attr['class'] ) )
		$attr['class'] = $attr['class'].' '.$class;
	else
		$attr['class'] = $class;	
	$out = "<select ";			
			foreach ( $attr as $name => $value )
				$out .= " $name='$value' ";
	$out .= ">";
	$out .= "	<option value='p' ".selected('p', $selected, false).">%</option>";
	$out .= "	<option value='f' ".selected('f', $selected, false).">$currency</option>";
	$out .= "	<option value='t' ".selected('t', $selected, false).">".esc_html__('Точная', 'usam')."</option>";
	$out .= "</select>";
	return $out;
}

function usam_get_select_prices( $selected = '', $attr = [], $not = false, $args = array() ) 
{			
	if ( empty($attr['name']) )
		$attr['name'] = 'type_price';	
	$out = '<select id="type_price"';
	foreach ( $attr as $name => $value )
		$out .= " $name='$value' ";
	$out .= ">";
		if ( $not )
		{	
			$out .= '<option value="" '.($selected == ''?'selected="selected"':'').'>'.esc_html__('Цены', 'usam').'</option>';
		}
		$prices = usam_get_prices( $args );						
		foreach ( $prices as $value )
		{			
			$out .= '<option value="'.$value['code'].'" '.($selected === $value['code']?'selected="selected"':'').'>'.$value['title'].'</option>';
		}	
	$out .= "</select>";
	return $out;
}

function usam_get_password_input( $password = '', $attr = array(), $echo = true ) 
{
	$password = !empty($password)?"***":'';
	$output = "<input";			
			foreach ( $attr as $name => $value )
				$output .= " $name='$value' ";				
	$output .= "value='$password' type='text'/>";
	
	if ( $echo )
		echo $output;
	else
		return $output;
}

function usam_help_tip( $text_help )
{	
	$out = "<span class = 'help_text_box'>
				<span class='dashicons dashicons-editor-help'></span>
				<span class = 'help_text'>$text_help</span>
			</span>";
	return $out;
}

function usam_get_form_send_sms( $args = array() )
{	
	$out  = "<div class='mailing'>";	
	$out .= '<form method="post" action="'.usam_url_admin_action( 'send_sms' ).'">';		
	$out .= "<div class='mailing-mailing_wrapper'>";
	$out .= "<table class ='widefat'>";
	$out .= "<tr class ='js-to-sms-row'>
				<td>".__('Кому', 'usam')."</td>
				<td class ='js-to-sms'>";
					if ( !empty($args['to_phone']) ) 
					{
						$out .= "<select name='phone' id='to_phone'>";	
							foreach ( $args['to_phone'] as $phone => $title )
							{		
								$out .= "<option value='$phone' ".selected($phone, $args['to_select'], false ).">$title</option>";
							}				
						$out .= '</select>';
					}		
					else
						$out .= "<input id='to_phone' type='text' name='phone' value=''/>";
			$out .= "</td>
			</tr>			
			<tr>
				<tr>
					<td colspan='2'><textarea rows='10' autocomplete='off' cols='40' name='message' id='message_editor' ></textarea></td>						
				</tr>				
			</tr>
		</table>";		
	$out .= "</div>";
	$out .= '<div class="modal__buttons">'.get_submit_button( __('Отправить','usam'), 'primary','action_send_sms', false, array( 'id' => 'send-sms-submit' ) ).'</div>';	
	$out .= '</form>';
	$out .= "</div>"; 
	return $out;
}

function usam_get_notification_message( $message )
{	
	return '<div class="notification_message"><p><strong>'.__('Уведомление', 'usam').': </strong>'.$message.'</p></div>';
}

function usam_notification_message( $message )
{	
	echo usam_get_notification_message( $message );
}

function usam_email_html_header( $id, $delete = true ) 
{ 	
	if ( empty($id) )
		return '';
	
	$email = usam_get_email( $id );		 
	if ( !empty($email) ) 
	{ 
		if ( $email['type'] == 'inbox_letter' )
			usam_employee_viewing_objects(['object_type' => 'email', 'object_id' => $id, 'value' => $email['from_name']." - ".$email['from_email']]);	
		
		$mailbox_id = $email['mailbox_id'];					
		$body =  preg_replace_callback("/\n>+/u", 'usam_email_replace_body', $email['body'] );
		$from = !empty($email['from_name'])?$email['from_name'].' - '.$email['from_email']:$email['from_email'];
		$to = !empty($email['to_name'])?$email['to_name'].' - '.$email['to_email']:$email['to_email'];
		$copy_email = usam_get_email_metadata( $id, 'copy_email' );
		
		$email_contact = false;
		$customers = array();		
		$emails = array( 'from_email' => $email['from_email'], 'to_email' => $email['to_email'] );
		if ( $copy_email )
			$emails['copy_email'] = $copy_email;		
		
		$properties = usam_get_properties(['type' => ['contact', 'company'], 'field_type' => 'email', 'fields' => ['code', 'type']]);
		foreach ( $emails as $key => $to_email )
		{
			$rows = array();
			$meta_query_contact = ['relation' => 'OR'];
			$meta_query_company = ['relation' => 'OR'];
			foreach( $properties as $property )
			{
				if ( $property->type == 'company' )
					$meta_query_company[] = ['value' => $to_email, 'key' => $property->code];
				else
					$meta_query_contact[] = ['value' => $to_email, 'key' => $property->code];			
			}
			$contact = usam_get_contacts(['meta_query' => $meta_query_contact, 'source' => 'all', 'number' => 1, 'cache_meta' => true]);	
			$status = '';
			if ( !empty($contact) )
			{
				if ( 'from_email' == $key )
					$email_contact = true;
				$url = usam_get_contact_url( $contact['id'] );
				$appeal = !empty($contact['appeal'])?$contact['appeal']: __('Без имени', 'usam');
				
				if ( $contact['company_id'] )
					$contact_company = usam_get_company($contact['company_id']);
				else
					$contact_company = array();
																	
				$value = usam_get_hiding_data($to_email, 'email')."<div class = 'crm_customer'><a href='$url'>$appeal</a><div class='crm_customer__info'><div class='crm_customer__info_rows'>";				
				$rows[] = array( 'name' => __('Контакт в базе', 'usam'), 'value' => human_time_diff(strtotime($contact['date_insert']), time() ) );									
				if ( $contact['manager_id'] )
				{
					if ( 'from_email' == $key )
						$manager = $contact['manager_id'];
					$rows[] = ['name' => __('Ответственный менеджер', 'usam'), 'value' => "<a href='".usam_get_contact_url($contact['manager_id'], 'user_id' )."'>".usam_get_manager_name($contact['manager_id'])."</a>"];
				}
				$location = usam_get_contact_metadata($contact['id'], 'location' ); 
				if ( !empty($location) ) 
				{
					$rows[] = ['name' => __('Город', 'usam'), 'value' => usam_get_full_locations_name($location, '%country%, %city%')];
					$rows[] = ['name' => __('Текущее время', 'usam'), 'value' => usam_get_location_time( $location )];					
				}
				if ( !empty($contact_company) )
				{
					$rows[] = array( 'name' => __('Работает в компании', 'usam'), 'value' => "<a href='".usam_get_company_url($contact['company_id'])."'>".$contact_company['name']."</a>" );
					$post = usam_get_contact_metadata($contact['id'], 'post');
					if ( $post )
						$rows[] = ['name' => __('Должность', 'usam'), 'value' => $post];
					$site = usam_get_company_metadata($contact_company['id'], 'site');
					if ( $site )
						$rows[] = ['name' => __('Сайт компании', 'usam'), 'value' => "<a href='".esc_url($site)."'>".$site."</a>"];					
					$rows[] = ['name' => __('Тип компании', 'usam'), 'value' => usam_get_name_type_company($contact_company['type'])];
					$groups = usam_get_company_groups( $contact_company['id'] );
					if ( $groups )
						$rows[] = ['name' => __('Группа', 'usam'), 'value' => implode(', ', $groups)];
				}
				$phone = usam_get_contact_metadata($contact['id'], 'mobilephone');
				if ( $phone )
				{
					$rows[] = array( 'name' => __('т.', 'usam'), 'value' => $phone );
				}
				$site = usam_get_contact_metadata($contact['id'], 'site');	
				if ( $site )
					$rows[] = array('name' => __('Сайт', 'usam'), 'value' => "<a href='".esc_url($site)."'>".$site."</a>" );
				foreach ( $rows as $key => $row )					
					$value .= "<div class='crm_customer__info_row'><div class='crm_customer__info_row_name'>".$row['name'].":</div><div class='crm_customer__info_row_option'>".$row['value']."</div></div>";
				if ( $contact['status'] != 'customer' )
					$status = "<span class='status_".$contact['status']." item_status'>".usam_get_object_status_name( $contact['status'], 'contact')."</span>";
				$value .= "</div></div></div>$status";	
				$customers[$to_email] = $value;				
			}
			else
			{ 
				$company = usam_get_companies(['meta_query' => $meta_query_company, 'number' => 1, 'cache_meta' => true]);	
				if ( !empty($company) )
				{  
					$site = usam_get_company_metadata($company['id'], 'site');											
					$value = usam_get_hiding_data($to_email, 'email')."<div class = 'crm_customer'><a href='".usam_get_company_url( $company['id'] )."'>".$company['name']."</a>
					<div class='crm_customer__info'>
					<div class='crm_customer__info_rows'>";
					$rows[] = array( 'name' => __('Компания в базе', 'usam'), 'value' => human_time_diff(strtotime($company['date_insert']), time() ) );
					$rows[] = array( 'name' => __('Тип компании', 'usam'), 'value' => usam_get_name_type_company($company['type']) );
					if ( $company['manager_id'] )
					{
						if ( 'from_email' == $key )
							$manager = $company['manager_id'];
						$rows[] = array('name' => __('Ответственный', 'usam'), 'value' => "<a href='".usam_get_contact_url($company['manager_id'], 'user_id')."'>".usam_get_manager_name($company['manager_id'])."</a>" );
					}
					$groups = usam_get_company_groups( $company['id'] );
					if ( !empty($groups) )
						$rows[] = array('name' => __('Группа', 'usam'), 'value' => implode(', ',$groups) );
					if ( $site )
						$rows[] = array('name' => __('Сайт', 'usam'), 'value' => "<a href='".esc_url($site)."'>".$site."</a>" );
					$phone = usam_get_company_metadata($company['id'], 'phone');
					if ( $phone )
						$rows[] = array( 'name' => __('т.', 'usam'), 'value' => $phone );
					foreach ( $rows as $key => $row )
						$value .= "<div class='crm_customer__info_row'><div class='crm_customer__info_row_name'>". $row['name'].":</div><div class='crm_customer__info_row_option'>".$row['value']."</div></div>";
						
					$value .= "</div></div></div>";	
					if ( $company['status'] != 'customer' )
						$status = "<span class='status_".$company['status']." item_status'>".usam_get_object_status_name( $company['status'], 'contact')."</span>";
					$customers[$to_email] = $value;
				}
			}
		}					
		if ( $email['type'] == 'inbox_letter' )
			$date =  $email['date_insert'];
		elseif ( !empty($email['sent_at']) ) 	
			$date =  $email['sent_at'];
		else
			$date =  $email['date_insert'];
		
		if ( $email['importance'] )			
			$importance = '<span class="dashicons dashicons-star-filled importance important"></span>';
		else
			$importance = '<span class="dashicons dashicons-star-empty importance"></span>';
		?>
		<div class = "letter_header__row letter_header__title"><div class = "letter_header__subject"><?php echo $email['title']; ?></div><div class = "letter_header__importance"><?php echo $importance; ?></div></div>
		<div class = "letter_header__row letter_header__from">
			<div class = "letter_header__contact letter_header__from_contact">
				<div class = "letter_header__label"><?php _e('От', 'usam') ?>:</div>
				<div class = "letter_header__email">
				<?php
				if ( !empty($customers) )
				{ 		
					foreach ( $customers as $key => $customer )
						$from = str_replace($key, $customer, $from);
				}
				echo str_replace($email['from_email'], usam_get_hiding_data($email['from_email'], 'email'), $from);
				?>
				</div>
			</div>
			<div class = "letter_header__date"><?php echo usam_local_date( $date, 'd.m.y H:i' ); ?></div>
		</div>		
		<div class = "letter_header__row letter_header__to">		
			<div class = "letter_header__contact letter_header__to_contact">
				<div class = "letter_header__label"><?php _e('Кому', 'usam') ?>:</div>
				<div class = "letter_header__email">
				<?php
				if ( !empty($customers) )
				{
					foreach ( $customers as $key => $customer )
						$to = str_replace($key, $customer, $to);					
				}
				echo str_replace($email['to_email'], usam_get_hiding_data($email['to_email'], 'email'), $to);
				?>
				</div>	
			</div>			
		</div>
		<?php 
		if ( $email['type'] == 'sent_letter' ) 	
		{
			if ( $email['user_id'] ) 	
			{
				?>
				<div class = "letter_header__row letter_header__from">
					<div class = "letter_header__contact letter_header__from_contact">
						<div class = "letter_header__label"><?php _e('Отправитель', 'usam') ?>:</div>
						<div class = "letter_header__text">
						<?php echo "<a href='".usam_get_contact_url( $email['user_id'], 'user_id' )."'>".usam_get_manager_name( $email['user_id'] )."</a>"; ?>
						</div>
					</div>		
				</div>		
				<?php 
			}
		
		} 
		elseif ( $email['type'] == 'inbox_letter' && !empty($manager) ) 
		{
			?>		
			<div class = "letter_header__row letter_header__from">
				<div class = "letter_header__contact">
					<div class = "letter_header__label"><?php _e('Для', 'usam') ?>:</div>
					<div class = "letter_header__text">
					<?php echo "<a href='".usam_get_contact_url( $manager, 'user_id' )."'>".usam_get_manager_name( $manager )."</a>"; ?>
					</div>
				</div>		
			</div>		
		<?php } ?>
		<?php if ( !empty($copy_email) ) { ?>
		<div class = "letter_header__row letter_header__to">	
			<div class = "letter_header__contact letter_header__to_contact">
				<div class = "letter_header__label"><?php _e('Копия', 'usam') ?>:</div>
				<div class = "letter_header__text">
				<?php
				if ( !empty($customers) )
				{
					foreach ( $customers as $key => $customer )
						$copy_email = str_replace($key, $customer, $copy_email);					
				}	
				echo $copy_email;
				?>
				</div>		
			</div>			
		</div>		
		<?php } ?>			
		<div id ="attached_object">	
			<?php
			$objects = usam_get_email_objects( $id );
			foreach ( $objects as $object ) 
			{
				if ( $object->object_type != 'email' )
				{ 
					$result = usam_get_object( $object );
					if ( !empty($result) ) 
					{
						?>
						<div class = "letter_header__row">
							<div class = "letter_header__label"><?php _e('Прикреплено', 'usam') ?>:</div>
							<div class = "letter_header__text"><?php echo $result['name']." - <a href='".$result['url']."'>".$result['title']."</a>"; ?></div>			
						</div>
						<?php
					}
				}
			}	
			?>		
		</div>
		<?php
		$url = add_query_arg(['page' => 'feedback', 'tab' => 'email', 'm' => $mailbox_id, 'id' => $id], admin_url('admin.php') );
		$email_attachments = usam_get_email_attachments( $id ); 
		if ( !empty($email_attachments)) 
		{ 	
			?>
			<div class='usam_attachments images'>					
				<?php			
				$totalsize = 0;
				foreach ( $email_attachments as $file ) 
				{ 
					$totalsize += $file->size;			
					$filepath = USAM_UPLOAD_DIR.$file->file_path;		
					$file_url = get_bloginfo('url').'/file/'.$file->code;
					$size = file_exists($filepath)?size_format( filesize($filepath) ):'';
					echo "<div class='usam_attachments__file' data-id='".$file->id."'>";
					if( preg_match('{image/(.*)}is', $file->mime_type, $p) )
						echo "<div class='attachment_icon' title ='".$file->title."'><img src='".usam_get_file_icon( $file->id )."'></div>";
					else
						echo "<a href='".$file_url."' title ='".$file->title."' target='_blank' rel='noopener'><div class='attachment_icon'><img src='".usam_get_file_icon( $file->id )."'></div></a>";
					echo "<div class='attachment__file_data'>
					<div class='filename'>".usam_get_formatted_filename( $file->title )."</div>
						<div class='attachment__file_data__filesize'><a download href='".$file_url."' title ='".__('Сохранить этот файл себе на компьютер','usam')."' target='_blank' rel='noopener'>".__('Скачать','usam')."</a>".$size."</div>
					</div>
					</div>";				
				}
				?>
			</div>
			<div class = "attachments_head">
				<span class="dashicons dashicons-paperclip"></span>
				<span class="attachments_head_count"><?php printf( _x('%s файл','%s файлов', $totalsize, 'usam'), count($email_attachments) ); ?></span><span class="attachments_head_size"><?php echo size_format($totalsize); ?></span>
				<span class="attachments_head_download_all"><a class="usam-download_all-link" href="<?php echo wp_nonce_url( add_query_arg(['action' => 'download_all'], $url )); ?>"><span class="dashicons dashicons-arrow-down-alt"></span><?php _e('Скачать все', 'usam') ?></a></span>
			</div>
			<?php
		} 
		if ( $email['folder'] != 'attached' ) 
		{
			$args = ['object_query' => [['object_id' => $id, 'object_type' => 'email']]];
			if ( $email['type'] == 'inbox_letter' )
				$args['folder_not_in'] = ['deleted'];
			else
				$args['sent_at'] = 'yes';			
			$related_messages = usam_get_emails( $args );			
			if ( !empty($related_messages)) 
			{ 				
				?>
				<div class = "related_messages letter_header__row">
					<div class = "letter_header__allocated">
						<h4><?php _e('Уже есть ответ на это письмо', 'usam') ?>:</h4>
						<ul>					
						<?php							
						foreach ( $related_messages as $related_message ) 
						{ 
							echo "<li><a href='".wp_nonce_url( add_query_arg(['email_id' => $related_message->id, 'f' => $related_message->folder], $url ))."'><span class='dashicons dashicons-undo'></span> <strong>&laquo;$related_message->title&raquo;</strong><br>".__('от', 'usam')." ".usam_local_date( $related_message->date_insert )." </a></li>";
						}
						?>
						</ul>
					</div>
				</div>
				<?php
			}					
			if ( $id )					
			{ 
				?>					
				<div class = "letter_buttons letter_header__row js-letter-buttons" data-letter_id="<?php echo $id; ?>">
					<ul>						
						<?php 
						if ( $email['folder'] != 'drafts' )			
						{ 						
							if ( $email['type'] == 'inbox_letter' )			
							{ 
								?>	
								<li><a class="usam-reply-link" href="<?php echo wp_nonce_url( add_query_arg(['action' => 'reply'], $url )); ?>"><span class="dashicons dashicons-undo"></span><?php _e('Ответить', 'usam') ?></a></li>
							<?php } ?>	
							<li><a class="usam-forward-link" href="<?php echo wp_nonce_url( add_query_arg(['action' => 'forward'], $url )); ?>"><span class="dashicons dashicons-undo"></span><?php _e('Переслать', 'usam') ?></a></li>
							<?php 
						}
						else
						{
							?>
							<li><a class="usam-send_message-link js-action-item" data-action="send" data-group="email" data-id="<?php echo $id; ?>" href="#"><span class="dashicons dashicons-arrow-left-alt"></span><?php _e('Отправить', 'usam') ?></a></li>
							<li><a class="usam-edit-link" href="<?php echo add_query_arg( array('id' => $id,'form' => 'edit','form_name' => 'email'), $url ); ?>"><span class="dashicons dashicons-edit"></span><?php _e('Изменить', 'usam') ?></a></li>
							<?php 
						}
						if ( $email['type'] == 'inbox_letter' )
						{ 
							if ( $email['read'] )
							{
								?><li><a class="usam-not_read-link" href=""><span class="dashicons dashicons-email-alt"></span><?php _e('Не прочитано', 'usam') ?></a></li><?php 
							}
							else
							{
								?><li><a class="usam-read-link" href=""><span class="dashicons dashicons-email-alt"></span><?php _e('Прочитано', 'usam') ?></a></li><?php 
							}						
						}
						?>
						<li><a class="usam-email_print-link" target="_blank" href="<?php echo wp_nonce_url( add_query_arg(['action' => 'email_print'], $url )); ?>"><span class="dashicons dashicons-media-text"></span><?php _e('Распечатать', 'usam') ?></a></li>
						<?php 
						if ( $delete )
						{ ?>	
						<li><a class="usam-remove-link" href=""><span class="dashicons dashicons-no-alt"></span><?php _e('Удалить', 'usam') ?></a></li>			
						<?php } ?>
						<li><a class="usam-attach-object js-modal" href="" data-modal='select_objects' data-screen='email' data-list='order' ><span class="dashicons dashicons-admin-links"></span><?php _e('Объект', 'usam') ?></a></li>
						<?php 
						if ( !$email_contact )
						{
						?>					
						<li><a class="usam-add_contact-link" href="" data-email='<?php echo $email['from_email']; ?>' data-name='<?php echo $email['from_name']; ?>' data-id='<?php echo $id; ?>'><span class="dashicons dashicons-admin-links"></span><?php _e('Добавить контакт', 'usam') ?></a></li>
						<?php 
						}
						?>	
					</ul>
				</div>
				<?php
			}
		}
		$attached_messages = usam_get_emails(['include' => [$id], 'folder' => 'attached', 'object_query' => [['object_type' => 'email']]]);
		if ( !empty($attached_messages)) 
		{ 				
			$anonymous_function = function() { 
				$html = '
				<div class="modal-body modal-scroll">
					<div class = "display_email">			
						<div class = "email_body">
							<div id = "attached_email_header" class = "letter_header"></div>	
							<div class="message"><iframe id = "attached_display_email_iframe" src=""></iframe></div>
						</div>									
					</div>
				</div>';				
				echo usam_get_modal_window( __('Просмотр письма','usam'), 'attached_message', $html );	
				return true; 
			};
			add_action('admin_footer', $anonymous_function);
			?>
			<div class = "related_messages letter_header__row">
				<div class = "letter_header__allocated">
					<h4><?php _e('Вложенные письма', 'usam') ?>:</h4>
					<ul>					
					<?php							
					foreach ( $attached_messages as $attached ) 
					{ 
						$to = !empty($attached->to_name)?$attached->to_name.' - '.$attached->to_email:$attached->to_email;
						echo "<li><a href='' class='attached_message' data-id='$attached->id'><span class='dashicons dashicons-paperclip'></span> <strong>&laquo;$attached->title&raquo;</strong></a><br>".__('Кому', 'usam').": <span class='js-copy-clipboard'>$to</span></li>";
					}
					?>
					</ul>
				</div>
			</div>
			<?php
		}			
	} 
}		

function usam_list_order_shortcode(  ) 
{	
	$order_labels = usam_get_order_shortcode();
	?>
	<div class = "detailed_description">
		<a id = "link_description" href=""><?php esc_html_e( 'Посмотрите шорт-коды которые могут быть использованы', 'usam');?> <span class="dashicons dashicons-arrow-down-alt2"></span></a> 
		<div class = "description_box shortcodes">
				<div class="shortcode">
					<div class="name js-copy-clipboard">[current_day +1]</div>
					<div class="option"><?php esc_html_e( 'Текущая дата с разницей на указанное количество дней. Например, [current_day +1] - заменить на текущую дату плюс 1 день', 'usam'); ?></div>
				</div>
				<div class="shortcode">
					<div class="name js-copy-clipboard">[if total_price=200 {Показать этот текст}]</div>
					<div class="option"><?php esc_html_e( 'Условный текст. Например, если сумма заказа равна 200 покажет текст в фигурных скобках', 'usam'); ?></div>
				</div>
				<div class="shortcode js-copy-clipboard">
					<div class="name">[if total_price>=200 {Этот текст} {Иначе этот}]</div>
					<div class="option"><?php esc_html_e( 'Условный текст с вариантом. Например, если сумма заказа равна 200 покажет текст в первых фигурных скобках, иначе во вторых', 'usam'); ?></div>
				</div>	
			<?php 						
			foreach ( $order_labels as $label => $title )
			{						
				?>
				<div class="shortcode">
					<div class="name js-copy-clipboard"><?php echo $label; ?></div>
					<div class="option"><?php echo $title; ?></div>
				</div>
				<?php 			
			}	
			?>	
		</div>
	</div>
	<?php
}

function usam_get_checklist( $name, $array, $checked_list = null )
{           
	$output = '';
	foreach ($array as $id => $title) 
	{       
		$output .= '<li id='.$name.'_'.$id.'>' ;
		$output .= '<label class="selectit">' ;
		$output .= '<input type="checkbox" name="'.$name.'[]" value="'.$id.'" ';
		if ( $checked_list !== null ) 
		{ 
			if ( is_numeric($checked_list) )	
			{ 
			  if ( $id == absint($checked_list) )
				$output  .= 'checked="checked"';    
			}
			elseif ( is_string($checked_list) )	
			{ 
			    if ( $id == $checked_list )
					$output  .= 'checked="checked"';		   
			}			
			elseif ( is_array($checked_list) && in_array($id, $checked_list) )				
			   $output  .= 'checked="checked"'; 		   
		}
		$output .= '>';
		$output .= '&nbsp;<span class="selectit_name">'.$title.'</span></label>';            
		$output .= '</li>';             
	}
	return $output;
}

function usam_display_status( $status, $type ) 
{
	$status = usam_get_object_status_by_code( $status, $type );
	if ( $status )
	{
		$attr = [];
		$attr[] = $status['color']?'background:'.$status['color']:'';	
		$attr[] = $status['text_color']?'color:'.$status['text_color']:'';
		$style = $attr?'style="'.implode('; ',$attr).'"':'';
		?><div class='status_<?php echo $status['internalname']; ?> item_status' <?php echo $style; ?>><?php echo $status['name']; ?></div><?php 
	}
}
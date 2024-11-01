<?php
new USAM_Admin_User_Profile();
class USAM_Admin_User_Profile 
{	
	private $notifications = array();
	public function __construct() 
	{			
		$this->notifications = array( 'vk' => __('вКонтакт','usam'), 'order' => __('Новый заказ','usam'), 'review' => __('Новый отзыв','usam'), 'inbox_letter' => __('Новое письмо','usam') );
		add_action('show_user_profile', array($this, 'user_profile_fields'));
		add_action('edit_user_profile', array($this, 'user_profile_fields'));
		add_action('personal_options_update', array($this, 'save_user_profile_fields'));
		add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
	}
			
	function user_profile_fields( $user ) 
	{				
		if ( usam_check_current_user_role( 'administrator' ) || usam_check_current_user_role('editor') || usam_check_current_user_role('shop_manager') || usam_check_current_user_role('shop_crm') )
		{
			$phone = get_the_author_meta('usam_phone', $user->ID);
			$phone = usam_get_phone_format( $phone );
			
			$sip = get_the_author_meta('usam_sip', $user->ID);
			?>						
			<h3><?php _e('IP телефония', 'usam'); ?></h3>	
			<table class="form-table">			
				<tr>				
					<th><?php esc_html_e( 'Телефон', 'usam'); ?></th>
					<td>
						<input type='text' value='<?php echo $phone; ?>' name='phone' />	
					</td>		
				</tr>	
				<tr>				
					<th><?php esc_html_e( 'SIP', 'usam'); ?></th>
					<td>
						<input type='text' value='<?php echo $sip; ?>' name='sip' />	
					</td>		
				</tr>				
			</table>		
			<?php 							
			$mailboxes = usam_get_mailboxes( array( 'fields' => array( 'id','name'), 'user_id' => $user->ID ) );	
			$email_default = esc_attr(get_the_author_meta('usam_email_default', $user->ID));
			?>			
			<h3><?php _e('Настройки электронной почты', 'usam'); ?></h3>
			<table class="form-table">					
				<tr>				
					<th><?php esc_html_e( 'Почта по умолчанию', 'usam'); ?></th>
					<td>
						<?php if ( !empty($mailboxes) ) { ?>						
						<select name="usam_email_default">
							<?php						
							foreach( $mailboxes as $mailbox )
							{						
								?><option value="<?php echo $mailbox->id; ?>" <?php selected($mailbox->id, $email_default) ?>><?php echo $mailbox->name; ?></option><?php
							}		
							?>				
						</select>
						<?php } else { _e('Не указана почта для вас в настройках','usam'); } ?>
					</td>		
				</tr>					
			</table>							
		<?php
		}
		if ( usam_check_current_user_role( 'administrator' ) || usam_check_current_user_role('shop_manager') )
		{
			?>						
			<h3><?php _e('Отключить уведомления в ленте событий', 'usam'); ?></h3>			
			<p><?php _e('Выберете события, при создании которых вы не хотите получать уведомления.', 'usam'); ?></p>
			<table class="form-table">					
				<?php							
				foreach( $this->notifications as $key => $title )
				{	
					$user_notification = get_the_author_meta('usam_notification_'.$key, $user->ID);				
					?>	
					<tr>				
						<th><label for="notification_<?php echo $key; ?>"><?php echo $title; ?></label></th>
						<td>
							<input type="hidden" name="notifications[<?php echo $key; ?>]" value="0">
							<input id="notification_<?php echo $key; ?>" type="checkbox" <?php checked($user_notification, 1); ?> name="notifications[<?php echo $key; ?>]" value="1">
						</td>		
					</tr>	
				<?php						
				}	
				?>					
			</table>				
		<?php
		}
		if ( usam_check_current_user_role('administrator' ) )
		{
			?>				
			<h3><?php _e('Просмотры заказов', 'usam'); ?></h3>
			<?php 	
			$option = get_site_option('usam_order_view_grouping');
			$grouping = maybe_unserialize( $option );	
			$select = esc_attr(get_the_author_meta('usam_order_view_grouping', $user->ID));
			?>		
			<table class="form-table">			
				<tr>				
					<th><?php esc_html_e( 'Группы просмотра заказов', 'usam'); ?></th>
					<td>
						<select name="usam_order_view_grouping">
							<option value="0" <?php selected(0, $select) ?>><?php esc_html_e( 'Все заказы', 'usam'); ?></option>
							<?php			
							if ( !empty($grouping) )
							{
								foreach( $grouping as $value )
								{						
									?><option value="<?php echo $value['id']; ?>" <?php selected($value['id'], $select) ?>><?php echo $value['name']; ?></option><?php
								}	
							}							
							?>				
						</select>
					</td>		
				</tr>				
			</table>
			<?php 	
		}	
return;		
		$secret = 'N6TZ';
		$query = "otpauth://totp/usam:{$user->ID}?secret={$secret}&issuer=usam";		
		?>		
		<table class="form-table">			
			<tr>				
				<th><?php esc_html_e( 'Authenticator', 'usam'); ?></th>
				<td>
					<img class="qr" src="<?php echo usam_get_qr( $query ); ?>">	
				</td>		
			</tr>				
		</table>
		<?php 	
	}

	function save_user_profile_fields( $user_id ) 
	{	
		if (!current_user_can('edit_user', $user_id))
			return FALSE; 
		
		if ( usam_check_current_user_role(['administrator', 'editor', 'shop_manager', 'shop_crm']) )
		{				
			if ( isset($_POST['phone']) )
			{		
				$phone = preg_replace("/[^0-9]/", '', $_REQUEST['phone']);				
				update_user_meta( $user_id, 'usam_phone', $phone ); 
			}		
			if ( isset($_POST['sip']) )
			{		
				$sip = preg_replace("/[^0-9]/", '', $_REQUEST['sip']);				
				update_user_meta( $user_id, 'usam_sip', $sip ); 
			}			
			if ( isset($_POST['usam_email_default']) )
			{		
				$email_default = absint($_REQUEST['usam_email_default']);
				update_user_meta( $user_id, 'usam_email_default', $email_default ); 
			}		
			foreach ( $this->notifications as $key => $title )
			{ 
				if ( !empty($_POST['notifications'][$key]) )					
					update_user_meta( $user_id, 'usam_notification_'.$key, 1 ); 
				else
					delete_user_meta( $user_id, 'usam_notification_'.$key ); 					
			}	
		}
		if ( usam_check_current_user_role('administrator' ) )
		{			
			if ( isset($_POST['usam_order_view_grouping']) )
			{		
				$order_view_grouping = absint($_REQUEST['usam_order_view_grouping']);
				update_user_meta( $user_id, 'usam_order_view_grouping', $order_view_grouping ); 
			}	
		}
	}
}
<?php
$primary_mailbox = usam_get_customer_primary_mailbox_id( );	
$signature_email = usam_get_manager_signature_email( $primary_mailbox );		

$default = ['form_url' => usam_url_admin_action( 'send_email' ), 'object_id' => '', 'object_type' => '', 'type_file' => '', 'customer_type' => '', 'customer_id' => '', 'to_email' => [], 'to_select' => '', 'title' => '', 'message' => $signature_email, 'insert_text' => array(), 'upload' => true];
$args = array_merge( $default, $args );

$args['to_email'] = is_string($args['to_email'])?array($args['to_email']):$args['to_email'];
		
$user_id = get_current_user_id();
$mailboxes = usam_get_mailboxes( array( 'fields' => array( 'id','name','email'), 'user_id' => $user_id ) );			
$signatures = usam_get_signatures( array( 'manager_id' => $user_id, 'orderby' => 'mailbox_id', 'order' => 'DESC' ) );
?>		
<div id="send_mail" class="modal fade modal-medium">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Отправить сообщение','usam'); ?></div>
	</div>	
	<div class='modal-body'>
		<form method="post" action="<?php echo $args['form_url']; ?>">		
			<div class="modal-scroll">
				<?php	
				ob_start();	
				?>
				<textarea id="email_editor" name="message"><?php echo $args['message']; ?></textarea>	
				<?php									
				if ( empty($_REQUEST['tinymce_scripts_loaded']) )
				{
					add_editor_style( USAM_URL . '/admin/assets/css/email-editor-style.css' );	
					require_once ABSPATH . '/wp-includes/class-wp-editor.php';		
					_WP_Editors::print_tinymce_scripts();	
				}
				$editor = ob_get_contents();
				ob_end_clean();		
				
				$out  = "<div class='mailing'>";				
				$out .= "<input type='hidden' id='object_id' name='object_id' value='".$args['object_id']."' />";
				$out .= "<input type='hidden' id='object_type' name='object_type' value='".$args['object_type']."' />";
				$out .= "<div class='mailing-mailing_wrapper'>";
				$out .= "<table class ='table_email_form'>";
				$out .= "<tr>
							<td class ='name'>".__('От кого', 'usam').":</td>
							<td><select name='from_email' id='from_email'>";				
							foreach ( $mailboxes as $mailbox )
							{		
								$out .= "<option value='$mailbox->id' ".selected($mailbox->id, $primary_mailbox, false ).">$mailbox->name ($mailbox->email)</option>";
							}					
						$out .= "</select></td>";
						$out .= "</tr>			
						<tr class ='js-to-email-row'>
							<td class ='name'>".__('Кому', 'usam').":</td>
							<td class ='js-to-email'>";
								if ( !empty($args['to_email']) ) 
								{
									$out .= "<select name='to' id='to_email'>";	
										foreach ( $args['to_email'] as $email => $title )
										{		
											$out .= "<option value='$email' ".selected($email, $args['to_select'], false ).">$title</option>";
										}				
									$out .= '</select>';
								}		
								else
									$out .= "<input id='to_email' type='text' name='to' value=''/>
							</td>
						</tr>";		
						$out .= "<tr>
							<td class ='name'>".__('Тема', 'usam').":</td>
							<td><input id='subject' type='text' name='title' value='".$args['title']."'/></td>
						</tr>";
						if ( !empty($args['insert_text']) )
						{ 
							$out .= "<tr><td colspan='2'><div class = 'insert_text'><ul>";
							foreach ( $args['insert_text'] as $id => $data ) 
							{ 
								$out .= "<li id = '$id' data-text='".$data['data']."'>".$data['title']."</li>";
							}
							$out .= "</ul></div></td></tr>";
						}
						if ( !empty($signatures) )
						{
							$out .= "<tr><td class='name'>".__('Подписи', 'usam').":</td><td>
										<select id='signature'>";
											foreach ( $signatures as $signature )
											{
												$out .= "<option value='$signature->id' ".selected($signature->mailbox_id, $primary_mailbox, false).">$signature->name</option>";
											}													
							$out .= "</select></td></tr>";
						}
						$out .= "<tr><td colspan='2'>$editor</td></tr>";			
						$out .= "<tr><td colspan='2'>".
							usam_get_form_attachments_files_library( array('object_id' => $args['object_id'], 'type' => $args['type_file'], 'upload' => $args['upload']) )."</td></tr>";		
				$out .= "</table>";		
				$out .= "</div>";	
				$out .= "</div>"; 
				echo $out; ?>
			</div>
			<div class="modal__buttons">
				<input type="submit" id="send-email-submit" class="button button-primary" value="<?php _e( 'Отправить', 'usam'); ?>">	
			</div>
		</form>
	</div>
</div>
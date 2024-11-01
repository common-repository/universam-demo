<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_yandex_connect_mailbox extends USAM_Edit_Form
{		
	protected $users = array();
	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить электронную почту','usam');
		else
			$title = __('Добавить электронную почту в Яндекс Коннект', 'usam');	
		return $title;
	}
	
	protected function get_data_tab() 	
	{		
		$default = ['name' => '', 'email' => '', 'user_id' => '', 'pop3server' => '', 'pop3port' => 995, 'pop3user' => '', 'pop3pass' => '', 'pop3ssl' => 1, 'smtpserver' => '', 'smtpport' => 465, 'smtpuser' => '', 'smtppass' => '', 'smtp_secure' => 'ssl', 'delete_server' => 0, 'delete_server_day' => 0, 'delete_server_deleted' => 0, 'template' => '', 'template_name' => ''];
		if ( $this->id != null )
		{
			$this->data = usam_get_mailbox( $this->id ); 	
			$this->users = usam_get_mailbox_users( $this->id );	
			$metas = usam_get_mailbox_metadata( $this->id );
			foreach($metas as $metadata )
			{
				$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
			}				
		}
		$this->data = array_merge($default, $this->data);				
		add_action( 'admin_footer', array(&$this, 'admin_footer') );				
	}	
	
	function admin_footer()
	{
		echo usam_get_modal_window( __('Проверить соединение','usam'), 'check_connection', "<div class='modal-body status_connection'></div>", 'medium' );	
		echo usam_get_modal_window( __('Ваш шаблон','usam'), 'mail_template_preview', '<div class="modal-body modal-scroll"><iframe id="mail_template_preview_iframe" style="width:100%;height:100%"></iframe></div>' );		
	}
	
	public function box_settings()
	{	
		?>		
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_name'><?php esc_html_e( 'Имя', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_name' name="name" required autocomplete="off" value="<?php echo htmlspecialchars($this->data['name']); ?>"/>
				</div>
			</div>
			<?php  if ( $this->id == null ) { ?>		
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_email'><?php esc_html_e( 'Электронная почта', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type="text" id='option_email' name="email" required autocomplete="off" value="<?php echo $this->data['email']; ?>"/>
					</div>
				</div>
			<?php  } ?>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='pop3pass'><?php esc_html_e( 'Пароль', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='pop3pass' name="pop3pass" required autocomplete="off" value="<?php echo $this->data['pop3pass']; ?>" minlength="6"/>
				</div>
			</div>
		</div>		
		<?php 
	}	
	
	function template_selection()
	{				
		$mailtemplate_list = usam_get_templates( 'mailtemplate' );	
		$class = $this->data['template']==''?'active':'';		
		?>		
		<div class ="theme-browser content-filterable rendered" >
			<div class ="themes wp-clearfix" >
			<div class="theme <?php echo $class; ?>" tabindex="0" data-template="none">
					<div class="theme-screenshot"></div>	
					<div class="theme-id-container">					
						<h3 class="theme-name"><?php _e('Без шаблон', 'usam'); ?></h3>		
						<div class="theme-actions">
							<a id="set_template" class="button button-primary" href=""><?php _e('Выбрать', 'usam'); ?></a>								
						</div>
					</div>
				</div>
			<?php					
			foreach ($mailtemplate_list as $template => $data ) 
			{
				$class = $template == $this->data['template_name']?'active':'';			
				?>				
				<div class="theme <?php echo $class; ?>" tabindex="0" data-template="<?php echo $template; ?>">
		
					<div class="theme-screenshot">
						<img src="<?php echo $data['screenshot']; ?>" alt="">
					</div>				
					<span class="more-details"><?php echo __('Автор', 'usam').": ".$data['author']; ?></span>				
					<div class="theme-id-container">					
						<h3 class="theme-name"><?php echo $template; ?></h3>
						<div class="theme-actions">
							<a id="set_template" class="button" href=""><?php _e('Выбрать', 'usam'); ?></a>			
							<a id="open_preview_template" class="button button-primary" href=""><?php _e('Посмотреть', 'usam'); ?></a>								
						</div>
					</div>
				</div>
				<?php
			}
			?>
			</div>
		</div>
		<?php
	}
	
	function template_editor()
	{			 
		?>	
		<p><?php _e('Используйте %mailcontent%, где вы хотите показать ваше содержание.','usam'); ?></p>
		<a id="open_editor_preview_mail" class="button" href='#' ><?php _e('Посмотреть шаблон письма','usam'); ?></a>
		<input type='hidden' value='<?php echo $this->data['template_name']; ?>' name='template_name' />	
		<?php
		add_editor_style( USAM_URL . '/admin/assets/css/email-editor-style.css' );					
		wp_editor( $this->data['template'], 'stylingmailtemplate', array(
			'textarea_name' => 'template',
			'media_buttons'=>false,
			'textarea_rows' => 50,	
			'wpautop' => 0,							
			'tinymce' => array(
				'theme_advanced_buttons3' => 'invoicefields,checkoutformfields',
				)
			)
		); 
	}
		
	function display_left()
	{						
		usam_add_box( 'usam_mailbox_settings', __('Ваша электронная почта','usam'), array( $this, 'box_settings' ) );
		usam_add_box( 'usam_template_selection', __('Выбор шаблона','usam'), array( $this, 'template_selection' ) );			
		usam_add_box( 'usam_template_editor', __('Редактирование шаблона','usam'), array( $this, 'template_editor' ) );		
    }	
	
	function display_right()
	{	
		$add = "<a href='' id='add_managers' data-modal='select_managers' data-screen='users' data-list='manager'  class='js-modal'>".__('Добавить','usam')."</a>";
		usam_add_box( 'usam_managers', __('Сотрудники','usam').$add, array( $this, 'display_select_users' ) );	
	}
}
?>
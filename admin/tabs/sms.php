<?php
class USAM_Tab_sms extends USAM_Tab
{	
	private $folders;
	protected $folder = 'sent';
	public function __construct()
	{						
		$this->folder = !empty($_REQUEST['f']) ? sanitize_title($_REQUEST['f']):'sent';	
		$this->folders = array( 'sent' => __('Отправленные', 'usam'), 'outbox' => __('Исходящие', 'usam'), 'drafts' => __('Черновики', 'usam'), 'deleted' => __('Удаленные', 'usam') );
		$this->views = array( 'table', 'report', 'settings' );
	}
	
	public function get_title_tab()
	{			
		return __('СМС сообщения', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return array( array('form' => 'edit', 'form_name' => 'sms', 'title' => __('Отправить СМС', 'usam') ) );
	}	
	
	public function display_mailboxes() 
	{			
		?>
		<div class = "mailboxes">
			<?php $numbers = usam_get_number_sms_messages();	?>
			<div class = "folders system">							
				<ul>					
					<?php
					$url = remove_query_arg( array( 'email_id' ) );										
					foreach ( $this->folders as $key => $name ) 
					{
						$link = add_query_arg( array( 'page' => $this->page_name, 'tab' => $this->tab, 'f' => $key ), admin_url('admin.php') );
						if ( $key == $this->folder )
							$current = "folder_current";
						else
							$current = "";
						if ( ($key == 'outbox' || $key == 'drafts') && isset($numbers[$key]) )
							$name = $name." <strong>(".$numbers[$key].")</strong>";	
						
						echo "<li class = '$current'><a href='$link' data-folder='$key' >$name</a></li>";
					}
					?>
				</ul>	
			</div>	
		</div>
		<?php
	}
	
	public function table_view() 
	{						
		if ( isset($_GET['action']) && ( $_GET['action'] != 'delete' ) && $_GET['action'] != '-1' )
		{
			$this->list_table->display_table();
		}
		else
		{
			if ( !empty($_GET['email_id']) )
			{
				$id = absint($_GET['email_id']);		
				$sms = usam_get_sms( $id );	
			}
			?>			
			<div id = "sms_list" class = "sms_form">
				<?php
				if( wp_is_mobile() )
				{					
						?>
						<div class = "menu_email">
							<span id ="menu_email_folder" class="dashicons dashicons-menu"></span>
							<span class="name_email_folder"><?php echo isset($this->folders[$this->folder])?$this->folders[$this->folder]:''; ?></span>							
						</div>
						<div class = "list_folders">
							<?php $this->display_mailboxes(); ?>
						</div>
						<div class = "list_email">
							<?php $this->list_table->display_table(); ?>
						</div>		
						<?php
				}
				else 
				{
					?>				
					<div class = "messaging_management">
						<div class = "list_folders">
							<?php $this->display_mailboxes(); ?>
						</div>
						<div class = "list_email">
							<?php $this->list_table->display_table(); ?>
						</div>		
						<div class = "display_email">									
							<div class = 'menu_fixed_right'>
								<div class = "email_body">
									<div id="sms_header" class = "letter_header">
										<div class = "letter_header__row letter_header__to">
											<div class = "letter_header__contact letter_header__to_contact">
												<div class = "letter_header__label"><?php _e('Кому', 'usam') ?>:</div>
												<div class = "letter_header__text js-sms-phone"><?php echo !empty($sms['phone']) ? $sms['phone'] :''; ?></div>
											</div>
											<div class = "letter_header__date js-sms-date"><?php echo !empty($sms['date_insert']) ? usam_local_date( $sms['date_insert'], "d.m.Y" ) :''; ?></div>
										</div>	
									</div>	
									<div class="message js-sms-text"><?php echo !empty($sms['message']) ? $sms['message'] :'';; ?></div>							
								</div>	
							</div>					
						</div>	
					</div>
					<?php
				}
				?>
			</div>			
			<?php			
		}		
	}	
	
	public function display_settings_view() 
	{
		usam_add_box( 'usam_sms_settings', __('СМС шлюз','usam'), array( $this, 'display_sms' ) );			
	}
		
	public function display_sms() 
	{		
		$gateways = usam_get_integrations( 'sms' );
		$options = array( 					
			array( 'key' => 'login', 'type' => 'input', 'title' => __('Логин', 'usam'), 'option' => 'sms_gateway_option', 'attribute' => array( 'maxlength' => "50", 'size' => "50"),  'description' => __('Логин для смс шлюза.','usam') ),
			array( 'key' => 'password', 'type' => 'input', 'title' => __('Пароль', 'usam'), 'option' => 'sms_gateway_option', 'attribute' => array( 'maxlength' => "50", 'size' => "50"),  'description' => __('Пароль для смс шлюза.','usam') ),
			array( 'type' => 'input', 'title' => __('Подпись', 'usam'), 'option' => 'sms_gateway_name', 'attribute' => array( 'maxlength' => "50", 'size' => "50"),  'description' => __('Укажите имя от, которого будут приходить сообщения.','usam') ),			
			array( 'type' => 'select', 'title' => __('Провайдер', 'usam'), 'option' => 'sms_gateway', 'options' => $gateways ),	
			array( 'type' => 'input', 'title' => __('Количество', 'usam'), 'option' => 'max_number_of_sms_month', 'attribute' => array( 'maxlength' => "50", 'size' => "50"),  'description' => __('Максимальное количество сообщение в месяц, разрешенное для отправки через шлюз. Если не указано, ограничений нет.','usam') ),	
		 );	   		
		 $this->display_table_row_option( $options );
	}	
}
<?php
require_once( USAM_FILE_PATH . '/includes/mailings/email_filter.class.php');	
require_once( USAM_FILE_PATH .'/includes/mailings/signature_query.class.php');
class USAM_Tab_email extends USAM_Tab
{	
	protected $views = ['table', 'report', 'settings'];
	
	public function load_tab() 
	{						
		if ( $this->table == 'email' )	
		{						
			$anonymous_function = function() { 
				USAM_Admin_Assets::work_email();	
				return true; 
			};
			add_action('admin_footer', $anonymous_function, 111);			
			add_action( 'admin_footer', array(&$this, 'add_folder') );				
		}
		global $email_id, $email_folder, $mailbox_id;
						
		$email_folder = !empty($_REQUEST['f']) ? sanitize_title($_REQUEST['f']):'inbox';
		$email_id = !empty($_REQUEST['email_id']) ? absint($_REQUEST['email_id']):0;	
		if ( !empty($_REQUEST['m']) )
			$mailbox_id = sanitize_title($_REQUEST['m']);
		else
			$mailbox_id = 0;
		if ( $email_folder != 'deleted' )
		{
			add_filter( 'usam_update_attachment', '__return_true' );
		}
	}
	
	public function get_title_tab()
	{			
		if ( $this->table == 'email_filters' )	
			return __('Фильтры', 'usam');	
		elseif ( $this->table == 'signatures' )	
			return __('Шаблоны писем и подписи', 'usam');	
		elseif ( $this->table == 'mailboxes' )
			return __('Почтовые ящики', 'usam');	
		return __('Почта', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'email_filters' )		
			return [['form' => 'edit', 'form_name' => 'email_filter', 'title' => __('Добавить', 'usam') ]];	
		elseif ( $this->table == 'signatures' )		
			return [['form' => 'edit', 'form_name' => 'signature', 'title' => __('Добавить', 'usam') ]];	
		elseif ( $this->table == 'mailboxes' )		
		{
			$buttons = [['form' => 'edit', 'form_name' => 'mailbox', 'title' => __('Добавить ящик', 'usam')]];
			if ( usam_yandex_is_token() )
				$buttons[] = ['form' => 'edit', 'form_name' => 'yandex_connect_mailbox', 'title' => __('Добавить ящик в Яндекс Коннект', 'usam')];	
			return $buttons;	
		}
		elseif ( $this->table == 'email' )		
			return [
				['action' => 'new', 'title' => __('Новое письмо', 'usam')],
				['action' => 'download', 'title' => __('Проверить почту', 'usam')],
				['form' => 'edit', 'form_name' => 'email_clearing', 'title' => __('Очистка', 'usam')],
			];			
		return [];
	}
	
	function add_folder( )
	{
		$html = "<div class='modal-body'>			
			<div class='action_buttons'>				
				<button type='button' class='action_confirm button-primary button' data-dismiss='modal' aria-hidden='true' id='clear_folder'>".__('Очистить', 'usam')."</button>
				<button type='button' class='button' data-dismiss='modal' aria-hidden='true'>".__('Отменить', 'usam')."</button>
			</div></div>";			
		echo usam_get_modal_window( __('Подтвердите','usam'), 'operation_confirm', $html, 'small' );	
	}	
			
	public function table_view() 
	{			
		global $email_id, $email_folder, $mailbox_id;		
		
		if ( $this->table != 'email' )	
		{
			$this->display_tab_sections();	
			$this->list_table->display_table();
		}
		else
		{
			if ( isset($_GET['action']) && ( $_GET['action'] != 'delete' ) && $_GET['action'] != '-1' )
			{
				$this->list_table->display_table();
			}
			else
			{		
				if ( isset($_GET['company']) )
				{
					$company_id = absint($_GET['company']);	
					$company = usam_get_company( $company_id );
					?>
					<h3 class="search_title"><?php printf( __('Поиск общений с компанией &#8220;%s&#8221;' ), esc_html( stripslashes( $company['name'] ) ) ); ?></h3>
					<?php 
				}
				if ( isset($_GET['contact']) )
				{
					$contact_id = absint($_GET['contact']);	
					$contact = usam_get_contact( $contact_id );
					?>
					<h3 class="search_title"><?php printf( __('Поиск общений с &#8220;%s&#8221;' ), esc_html( stripslashes( $contact['appeal'] ) ) ); ?></h3>
					<?php 
				}
				?>
				<div id = "display_email_form" class="email_form">
				<?php 					
				if( wp_is_mobile() )
				{
					$mailbox = usam_get_mailbox( $mailbox_id );	
					$folders = usam_get_system_email_folders();
					?>
					<div class = "menu_email">
						<span id ="menu_email_folder" class="dashicons dashicons-menu"></span>
						<span class="name_email_folder"><?php echo isset($folders[$email_folder])?$folders[$email_folder]:''; ?></span>
						<span class="email"><?php echo isset($mailbox['email'])?$mailbox['email']:''; ?></span>
					</div>
					<div class = "list_folders">
						<?php $this->display_mailboxes(); ?>
					</div>
					<div class = "list_email">
						<?php $this->list_table->display_table(); ?>
					</div>	
					<div class = "display_email js-view-email-form"></div>					
					<?php
				}	
				else
				{
					?>
					<div class="messaging_management">						
						<div class = "list_folders">
							<div class = "js-email-hcSticky">	
								<?php $this->display_mailboxes(); ?>
							</div>
						</div>
						<div class = "list_email">
							<?php $this->list_table->display_table(); ?>
						</div>		
						<div class = "display_email">	
							<div class = "js-email-hcSticky">	
								<div class = "view_email_full_screen">							
									<div class = "email_body">
										<span id="close_view_email_full_screen" class="dashicons dashicons-no-alt"></span>
										<div id="letter_header" class = "letter_header"><?php usam_email_html_header( $email_id ); ?></div>	
										<div class="message"><iframe id = "display_email_iframe"  data-email_id="<?php echo $email_id; ?>" src="<?php echo usam_url_admin_action( 'display_mail_body', array('email_id' => $email_id) ); ?>"></iframe></div>
									</div>
								</div>	
							</div>	
						</div>	
					</div>			
					<?php
				}
				?></div><?php
			}
		}			 
	}	
	
	function display_mailboxes( )
	{
		global $email_id, $email_folder, $mailbox_id;		
		$user_id = get_current_user_id();
		?>
		<div class = "mailboxes">
			<?php
			$mailboxes = usam_get_mailboxes(['user_id' => $user_id]);	
			if ( !empty($mailboxes) )
			{							
				$system_folders = usam_get_system_email_folders();
				$ids = array();
				foreach ( $mailboxes as $mailbox ) 
					$ids[] = $mailbox->id;
					
				$email_folders = usam_get_email_folders(['mailbox_id' => $ids]);	
				$folders = array();
				$system_folders_not_read = [];
				$system_folders_count = [];
				foreach ( $email_folders as $folder ) 
				{
					$folders[$folder->mailbox_id][] = $folder;	
					if ( isset($system_folders_count[$folder->slug]) )
						$system_folders_count[$folder->slug] += $folder->count;
					else
						$system_folders_count[$folder->slug] = $folder->count;
					if ( isset($system_folders_not_read[$folder->slug]) )
						$system_folders_not_read[$folder->slug] += $folder->not_read;
					else
						$system_folders_not_read[$folder->slug] = $folder->not_read;
				}				
				if ( count($mailboxes) > 1 )
				{
					?>
					<div class = "folders">					
						<span class='name_email'><?php _e('Общее','usam'); ?></span>
						<ul>					
							<?php						
							$url = add_query_arg(['page' => $this->page_name, 'tab' => $this->tab], admin_url('admin.php') );
							foreach ( $system_folders as $slug => $name ) 
							{ 
								$class = '';						
								if ( $slug == $email_folder && $mailbox_id == 0 )
									$class = " folder_current";
						
								if ( $slug == 'inbox' && !empty($system_folders_not_read[$slug]) )
									$title = $name." <strong class='new_email_numbers'>(<span class='numbers'>".$system_folders_not_read[$slug]."</span>)</strong>";	
								elseif ( ($slug == 'drafts' || $slug == 'outbox') && !empty($system_folders_count[$slug]) )
									$title = $name." <strong class='new_email_numbers'>(<span class='numbers'>".$system_folders_count[$slug]."</span>)</strong>";
								else
									$title = $name;
								
								echo "<li class = 'folder $class' data-folder='$folder->id'><a href='".add_query_arg(['f' => $slug], $url )."'>$title</a></li>";
							}
							?>										
						</ul>
					</div>	
					<?php 
				}			
				$system_folders = array_keys($system_folders);									
				foreach ( $mailboxes as $mailbox ) 
				{ 
					?>
					<div id ="folders_mailbox_<?php echo $mailbox->id; ?>" class = "folders" data-mailbox_id="<?php echo $mailbox->id; ?>">
						<span class='name_email'><?php echo $mailbox->email; ?></span>
						<ul>					
							<?php
							if ( isset($folders[$mailbox->id]) )
							{
							
								$url = add_query_arg( array( 'page' => $this->page_name, 'tab' => $this->tab, 'm' => $mailbox->id ), admin_url('admin.php') );
								foreach ( $folders[$mailbox->id] as $folder ) 
								{ 
									$link = add_query_arg( array( 'f' => $folder->slug ), $url );											
									$class = in_array($folder->slug, $system_folders)?'folder_system':'';
									if ( $folder->slug == $email_folder && $mailbox_id == $mailbox->id )
										$class .= " folder_current";
							
									if ( $folder->slug == 'inbox' && $folder->not_read )
										$title = $folder->name." <strong class='new_email_numbers'>(<span class='numbers'>".$folder->not_read."</span>)</strong>";	
									elseif ( ($folder->slug == 'drafts' || $folder->slug == 'outbox') && $folder->count)
										$title = $folder->name." <strong class='new_email_numbers'>(<span class='numbers'>".$folder->count."</span>)</strong>";
									else
										$title = $folder->name;
									
									echo "<li class = 'folder $class' data-folder='$folder->id'><a href='$link'>$title</a>
										<div class='usam_menu'><span class='menu_name dashicons dashicons-arrow-down-alt2'></span>
											<div class='menu_content email_menu'>	
												<div class='menu_items'>												
													<div id='read_folder' class='menu_items__item'>".__('Прочитано', 'usam')."</div>															
													<div id='add_folder' class='menu_items__item'>".__('Добавить папку', 'usam')."</div>
													<div id='delete_duplicate' class='menu_items__item'>".__('Удалить дубликаты', 'usam')."</div>
													<div id='open_clear_folder' class='menu_items__item'>".__('Очистить папку', 'usam')."</div>";															
													if ( !in_array($folder->slug, $system_folders) )
														echo "<div id='remove_folder' class='menu_items__item'>".__('Удалить папку', 'usam')."</div>";
													echo "
												</div>
											</div>
										</div>
									</li>";
								}
							}
							?>										
						</ul>
					</div>	
				<?php 
				} 		
			} 
			else 
			{
				printf(__('У вас еще не подключено ни одного почтового ящика. Пожалуйста подключите ящик на <a href="%s">этой странице</a>!','usam'), admin_url('admin.php?page=feedback&tab=email&view=settings&table=mailboxes'));					
			}
			?>						
		</div>
		<?php
	}
	
	public function get_tab_sections() 
	{ 
		$tables = array();	
		if ( $this->view == 'settings' )
		{ 
			$tables = $this->get_settings_tabs();
			array_unshift($tables, ['title' => __('Назад','usam')]);		
		}
		return $tables;
	}
	
	public function get_settings_tabs() 
	{ 
		$tables = ['mailboxes' => ['title' => __('Почтовые ящики','usam'), 'type' => 'table'], 'signatures' => ['title' => __('Шаблоны','usam'), 'type' => 'table'], 'email_filters' => [ 'title' => __('Фильтры','usam'), 'type' => 'table']];
		if ( !usam_check_current_user_role('administrator') )	
			unset($tables['mailboxes']);
		return $tables;
	}
}
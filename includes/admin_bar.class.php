<?php
class USAM_Admin_Bar_Menu
{
	public function __construct( ) 
	{			
		add_action('admin_bar_menu', array($this, "admin_bar"), 9999);		
		add_action( 'wp_head', array($this, "admin_bar_css") );				
		add_action('admin_head', array( $this,'admin_bar_css' ) );
		
		add_action('admin_footer', array(&$this, 'footer'), 1);		
		add_action('wp_footer', array(&$this, 'footer'), 1);	
	}		
			
	public function admin_bar()
	{					
		global $wp_admin_bar;		
		$contact_id = usam_get_contact_id();	
		if ( current_user_can('view_employees') )
		{
			$contact = usam_get_contact();
			$wp_admin_bar->add_menu([
				'id'    => 'contact',
				'parent' => 'user-actions', // родительский пункт
				'title' => sprintf(__("Контакт в CRM &laquo;%s&raquo;", 'usam'),$contact['appeal']),
				'href'  => admin_url("admin.php?page=site_company&tab=employees&form=view&form_name=employee&id=".$contact_id),
				'meta'  => ['title' => __('Сотрудник','usam'), 'target' => '',  'class' => 'contact-link'],
			]); 
		}	
		if( usam_check_is_employee() )
		{
			$code_price = usam_get_customer_price_code();			
			$type_price = usam_get_setting_price_by_code( $code_price );
			if ( $type_price )
			{
				$wp_admin_bar->add_menu([
					'id'    => 'type_price',
					'parent' => 'user-actions', // родительский пункт
					'title' => sprintf(__("Цена &laquo;%s&raquo;", 'usam'),$type_price['title']),
					'href'  => admin_url("admin.php?page=shop_settings&tab=directories&table=prices&view=settings&form=edit&form_name=price&id=".$type_price['id']),
					'meta'  => ['title' => __('Тип цены','usam'), 'target' => '',  'class' => 'type_price-link'],
				]); 
			}
		}
		$wp_admin_bar->add_menu([
			'id'    => 'menu-tape',
			'title' => '<span class="ab-icon"></span><span class="ab-label ab-title">'.__('События','usam').'</span>'.usam_get_style_number_message( usam_get_contact_unread_notifications() ),
			'href'  => '', 
			'meta'  => [
				'title' => __('События','usam'),            
			],
		]);
		if ( current_user_can('view_chat') )
		{
			$status_text = "<span class='selector_status_consultant ".(usam_get_contact_metadata( $contact_id, 'online_consultant' )?'active':'')." ".usam_get_contact_metadata( $contact_id, 'online_consultant' )." js-chat-switch' title='".__('Переключить статус','usam')."'></span>";
			$status_text = usam_get_style_number_message( usam_get_number_new_message_dialogues() )." $status_text";				
			$wp_admin_bar->add_menu([
				'id'    => 'online_consultant',
				'title' => '<span class="ab-icon"></span><span class="ab-label ab-title">'.__('Чат','usam').'</span>'.$status_text,
				'href'  => admin_url('admin.php?page=feedback&tab=chat'),				
			]);				
		}		
		if ( current_user_can('view_tasks') )
		{
			$wp_admin_bar->add_menu([
				'id'    => 'tasks-sub',
				'parent' => 'new-content', // родительский пункт
				'title' => '<span class="ab-icon1"></span><span class="ab-label1">'.__('Задание','usam').'</span>',
				'href'  => admin_url('admin.php?page=personnel&tab=tasks&form=edit&form_name=task'),
				'meta'  => array(
					'title' => '',             //Текст всплывающей подсказки
					'target' => '',             //_blank
					'class' => 'tasks-link' 
				),
			]);	
			$wp_admin_bar->add_menu([
				'id'    => 'crm',
				'title' => '<span class="ab-icon"></span><span class="ab-label ab-title">'.__('Задания','usam').'</span>',
				'href'  => admin_url('admin.php?page=personnel&tab=tasks'), 
				'meta'  => array(
					'title' => __('Мои задания','usam'),            
				),
			]);				
		}
		if ( usam_check_is_employee() )
		{	
			$favorite_pages = get_user_option( 'usam_favorite_pages' );					
			$wp_admin_bar->add_menu([
				'id'    => 'favorite-page',
				'title' => '<span class="ab-icon"></span><span class="ab-label ab-title">'.__('Избранное','usam').'</span>',
				'meta'  => [
					'title' => __('Избранное','usam'),            
				],
			]);			
			if ( !empty($favorite_pages) )
			{
				foreach ( $favorite_pages as $key => $menu )
				{	
					$wp_admin_bar->add_menu([
						'id'    => 'favorite-page-sub-item-'.$key,
						'parent' => 'favorite-page', 
						'title' => $menu['title'],
						'href'  => $menu['url'],
						'meta'  => array(
							'title' => '',          
							'target' => '',            
							'class' => 'favorite-page-link' 
						),
					]);
				}
			} 
			if( usam_check_current_user_role('administrator') || usam_check_current_user_role('shop_manager') )
			{ 
				$process = usam_get_system_process();	
				$number_events_message = '';
				$title = '<span class="ab-title">'.__('Диспетчер задач','usam').'</span>';
				if ( !empty($process) && count($process) > 0 )
				{ 
					$count = count($process);		
					if ( $count > 0)
					{
						$current_task = '';
						foreach( $process as $id => $task )
						{
							if( $task['status'] !== 'pause' )
							{
								$current_task = $task;
								break;
							}
							elseif( !$current_task )
								$current_task = $task;
						}						
						$p = $current_task['count']?round($current_task['done']*100/$current_task['count'], 0):0;
						$title = "<div id='usam_progress' class='progress'><div class='progress_text'>$p%</div><div class='progress_bar' style='width:$p%'></div></div>".usam_get_style_number_message( $count );
					}
				}						
				$wp_admin_bar->add_menu( array(
					'id'    => 'task_manager',
					'title' => '<span class="ab-icon"></span>'.$title,
					'href'  => '', 
					'meta'  => array(
						'title' => __('Задачи вашего магазина','usam'),      
					),
				));					
			}
			if ( current_user_can('edit_theme_options') && !is_admin() )
			{
				$user_id = get_current_user_id();		
				$wp_admin_bar->add_menu(['id' => 'edit_theme', 'title' => "<span class='edit_theme_switch js-change-theme-edit ".(get_user_meta($user_id, 'edit_theme', true)?'active':'')."'><span class='ab-icon' title='".__("Редактирование темы","usam")."'></span>".__("Тема","usam")."</span>", 'href'  => '']);
			}	
/*
			$user_id = get_current_user_id();
			$working_day = get_user_meta( $user_id, 'usam_working_day', true );
			if ( empty($working_day) )
				$status_text = "<span class='selector_status_working_day stop'>".__('Выключен','usam')."</span>";
			elseif ( $working_day == 1 )
				$status_text = "<span class='selector_status_working_day pause'>".__('Пауза','usam')."</span>";				
			else
				$status_text = "<span class='selector_status_working_day play'>".__('Включен','usam')."</span>";			
						
			$wp_admin_bar->add_menu( array(
				'id'    => 'working_day',
				'title' => '<span class="ab-icon"></span><span class="ab-label">'.__('Рабочий день','usam').$status_text.'</span>',
				'href'  => '', 
				'meta'  => array(
					'title' => __('Чат','usam'),            
				),			
			));		
			*/
	//		$classes = apply_filters( 'usam_debug_css_classes', array() );			
		}
		$show_server_load = get_user_option( 'usam_show_server_load' );	
		if ( !empty($show_server_load) )
		{
			$wp_admin_bar->add_menu( array(
				'id'    => 'performance_site',
				'parent' => 'top-secondary',			
				'title' => '<span class="ab-icon"></span><span class="ab-label ab-title">'.round(memory_get_usage()/1024/1024, 2).' MB '.' | '.get_num_queries().' SQL | '.timer_stop(0, 1).' '.__('сек','usam').'</span>',
			//	'href'  => '#', // Ваша ссылка 
				'meta'  => array( 'title' => __('Нагрузка на сервер', 'usam') ),
			));		
		}
	}
	
	public function admin_bar_css()
	{	
		?>
		<style>		
		#wpadminbar .progress{animation: works 4s ease-in-out 3; width:50px; margin:5px 5px 5px 0; background-color:#ffffff; border-radius:5px;}
		.progress{border: 1px solid #c3c4c7; overflow:hidden; display:inline-block; position:relative;}
		.progress .progress_text{text-align:center; position:absolute!important; z-index:10; left:50%; top:50%; transform:translate(-50%,-50%); color:#000}		
		#wpadminbar .progress_bar{height: 20px; background-color:#2271b1;}
		@keyframes works {		  
		  25% {
			border-color:#1d2327;
		  }		
		  75% {			
			border-color:#c3c4c7;
		  }		
		}
		#wpadminbar #wp-admin-bar-task_manager .ab-item{display:flex; align-items:center;}		
		#wp-admin-bar-menu-tape .ab-icon:before{ font-family: "dashicons" !important; content:"\f479" !important; }		
		#wp-admin-bar-crm .ab-icon:before{ font-family: "dashicons" !important; content: "\f481" !important; }
		#wp-admin-bar-edit_theme .ab-icon:before{ font-family: "dashicons" !important; content: "\f464" !important; }
		#wp-admin-bar-edit_theme .active .ab-icon:before,
		#wp-admin-bar-edit_theme .active{color:#a4286a;}
		#wp-admin-bar-favorite-page .ab-icon:before{ font-family: "dashicons" !important; content: "\f155" !important; }		
		#wp-admin-bar-task_manager .ab-icon:before{ font-family: "dashicons" !important; content: "\f107" !important; }
		#wp-admin-bar-online_consultant .ab-icon:before{ font-family: "dashicons" !important; content: "\f482" !important; }
		#wp-admin-bar-task_manager .usam_not_performed .ab-item{border-left:2px solid red; margin-left:5px;}
		#wp-admin-bar-task_manager .usam_performed .ab-item{ border-left:2px solid #32CD32; margin-left:5px; }
		.number_events.count-0{display:none;}
		.number_events{display: inline-block; background-color:#d2d7dc; color:#535c69!important; font-size:9px!important; line-height:15px!important; font-weight: 600!important; margin-left:2px!important; border-radius: 10px!important; z-index: 26!important; padding: 2px 8px!important;}	
		.number_events.important_events{background-color:#d54e21; color: #fff!important;}		
		#wpadminbar .selector_status_consultant{margin: 5px 0 0 5px; padding: 2px 10px; color: #4c6470; text-transform: uppercase; background: #d5e8f1; border-radius: 10px!important; line-height: 12px;font-size: 12px; font-family: serif; cursor:pointer}
		#wpadminbar .selector_status_consultant.active{background: #a4286a; color:#ffffff; }
		#wpadminbar .selector_status_consultant .text_status{padding: 0 5px;}
		
		#wp-admin-bar-working_day .stop:before{ font-family: "dashicons" !important; content: "\f330" !important; }
		#wp-admin-bar-working_day .pause:before{ font-family: "dashicons" !important; content: "\f523" !important; }
		#wp-admin-bar-working_day .play:before{ font-family: "dashicons" !important; content: "\f522" !important; }
		
		@media screen and (max-width:1400px) 
		{
			#wpadminbar .ab-title{display:none}
			#wpadminbar .selector_status_consultant .text_status{display:none}
		}	
		@media screen and (max-width: 1200px) 
		{
			#wp-admin-bar-site-name{display:none}
			#wp-admin-bar-customize{display:none}
			#wp-admin-bar-updates{display:none}			
			#wp-admin-bar-favorite-page{display:none}			
		}		
		@media screen and (max-width: 900px) 
		{			
			#wp-admin-bar-new-content{display:none}		
		}
		</style>
		<?php 
	} 
	
	public function footer() 
	{	
		if ( usam_check_current_user_role('administrator') || usam_check_current_user_role('shop_manager') || usam_check_current_user_role('shop_crm') )
		{ 
			echo '<audio id="chat_audio">
				<source src="'.USAM_URL .'/assets/sound/notify.wav" type="audio/wav">
				<source src="'.USAM_URL .'/assets/sound/notify.mp3" type="audio/mpeg">
				<source src="'.USAM_URL .'/assets/sound/notify.ogg" type="audio/ogg">
			</audio>';
		}		
	}	
}
$admin_menu = new USAM_Admin_Bar_Menu();	

// Получает стиль отображения чисел новых сообщений
function usam_get_style_number_message( $number, $type = 'important_events' )
{	
	if ( $number > 0 )
		return " <span class='number_events $type count-".$number."'>".$number."</span>";	
	else
		return '';	
}
?>
<?php
require_once( USAM_FILE_PATH . '/admin/includes/calendar/calendar.class.php' );
class USAM_Сalendar_Events extends USAM_Сalendar
{			
	function localize_script_tab() 
	{	
		$user_id = get_current_user_id();	
		wp_enqueue_script( 'usam-calendar' );	
		wp_enqueue_style( 'usam-calendar' );
		wp_localize_script( 'usam-calendar', 'USAM_Calendar', array(	
			'message_many_event' => __('Есть еще задание', 'usam'),			
			'save_tab_calendar_nonce' => usam_create_ajax_nonce( 'save_tab_calendar' ),			
			'tab' => get_user_meta($user_id, 'usam_tab_calendar', true ),									
		) );
	}
	
	function admin_footer() 
	{		
		require_once( USAM_FILE_PATH . "/admin/includes/modal/add_event.php" );
	}
	
	function display() 
	{					
		add_action( 'admin_footer', array(&$this, 'admin_footer') );	
		add_action( 'admin_footer', array(&$this, 'localize_script_tab') );		
		?>		
		<div class="columns-2">
			<div class = 'page_main_content'>		
				<?php $this->calendar_display(); ?>
			</div>	
			<div class = 'page_sidebar'>				
				<div class = 'menu_fixed_right'>
					<?php $this->display_right(); ?>
				</div>
			</div>	
		</div>			
		<?php
	}		
	
	public function display_right() 
	{
		$url_manage_calendars = add_query_arg( array( 'table' => 'calendars' ) );
		$url_add_calendar = add_query_arg( array( 'form' => 'edit', 'form_name' => 'calendar' ), $url_manage_calendars );
		?>		
		<div class = "categorydiv calendars_box">						
			<div id='calendars' class = "tabs-panel">			
				<h4><a href="<?php echo $url_manage_calendars; ?>" class="manage_calendars" title="<?php _e( 'Управление календарями', 'usam'); ?>"><?php _e( 'Ваши календари', 'usam'); ?></a><a href="<?php echo $url_add_calendar; ?>" class="add_calendar" title="<?php _e( 'Добавить календарь', 'usam'); ?>"><?php _e( 'Добавить', 'usam'); ?></a></h4>
				<ul class="categorychecklist form-no-clear">	
					<li v-for="(item, k) in calendars" v-if="item.user_id>0">
						<label class="selectit">
							<input type="checkbox" :value="item.id" v-model="item.checked">
							&nbsp;<span class="name" v-html="item.name"></span>
						</label>          
					</li>
				</ul>
			</div>
		</div>	
		<div class = "categorydiv calendars_box">						
			<div id='calendars' class = "tabs-panel">			
				<h4><?php _e( 'Системные календари', 'usam'); ?></h4>
				<ul class="categorychecklist form-no-clear">
					<li v-for="(item, k) in calendars" v-if="item.user_id==0">
						<label class="selectit">
							<input type="checkbox" :value="item.id" v-model="item.checked">
							&nbsp;<span class="name" v-html="item.name"></span>
						</label>          
					</li>
				</ul>
			</div>
		</div>	
		<?php 	
	}
} 
?>
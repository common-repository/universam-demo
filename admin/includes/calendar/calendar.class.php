<?php
abstract class USAM_Сalendar
{			
	protected $year;
	protected $month;
	protected $day;	
	
	protected $url;
				
	public function __construct( ) 
	{ 	
		$this->month = isset($_GET['month']) && (int)$_GET['month']<=12?(int)$_GET['month']:date('n');
		$this->day = isset($_GET['day']) && (int)$_GET['day']<=31?(int)$_GET['day']:date('j');
		$this->year = isset($_GET['year'])?(int)$_GET['year']:date('Y');
				
		$this->url = admin_url('admin.php');
		if ( !empty($_GET['page']) )
			$this->url = add_query_arg( array('page' => sanitize_title($_GET['page']) ), $this->url );
		if ( !empty($_GET['tab']) )
			$this->url = add_query_arg( array('tab' => sanitize_title($_GET['tab']) ), $this->url );
	}
	
	public function controller_month() 
	{			
		$back_day = mktime( 0,0,0, $this->month-1, 1, $this->year);
		$back_link = add_query_arg( array('day' => date('d', $back_day), 'month' => date('m', $back_day), 'year' => date('Y', $back_day)), $this->url );	
		
		$next_day = mktime( 0,0,0, $this->month+1, 1, $this->year);
		$next_link = add_query_arg( array('day' => date('d', $next_day), 'month' => date('m', $next_day), 'year' => date('Y', $next_day)), $this->url );
		?>
		<div id='tab-month' class='tab' :class="[tab=='month'?'current':'']">
			<div class='views_interval'>
				<a href="<?php echo $back_link; ?>#calendar_tab-tab-month" class="usam_month_back" title="<?php _e( 'Предыдущий месяц', 'usam'); ?>">&larr;</a>
				<?php echo date_i18n('F Y', mktime( 0,0,0, $this->month, 1, $this->year)); ?>				
				<a href="<?php echo $next_link; ?>#calendar_tab-tab-month" class="usam_next_month" title="<?php _e( 'Следующий месяц', 'usam'); ?>">&rarr;</a>
			</div>
			<div class="tab_calendar_title"><?php echo date_i18n('F Y', mktime( 0,0,0, $this->month, $this->day, $this->year)); ?></div>
			<table class="usam_table_month_calendar usam_table_calendar">
				<tbody>
					<tr class="usam_days-title">
						<td>		
							<div id="days_title" class="usam_month-title" style="visibility: visible;">
								<b id="mo" title="<?php _e( 'Понедельник', 'usam'); ?>"><i><?php _e( 'Пн', 'usam'); ?></i></b>
								<b id="tu" title="<?php _e( 'Вторник', 'usam'); ?>"><i><?php _e( 'Вт', 'usam'); ?></i></b>
								<b id="we" title="<?php _e( 'Среда', 'usam'); ?>"><i><?php _e( 'Ср', 'usam'); ?></i></b>
								<b id="th" title="<?php _e( 'Четверг', 'usam'); ?>"><i><?php _e( 'Чт', 'usam'); ?></i></b>
								<b id="fr" title="<?php _e( 'Пятница', 'usam'); ?>"><i><?php _e( 'Пт', 'usam'); ?></i></b>
								<b id="sa" title="<?php _e( 'Суббота', 'usam'); ?>"><i><?php _e( 'Сб', 'usam'); ?></i></b>
								<b id="su" title="<?php _e( 'Воскресенье', 'usam'); ?>"><i><?php _e( 'Вс', 'usam'); ?></i></b>
							</div>
						</td>
					</tr>
					<tr>
						<td class="usam_days-grid-td">
							<div class="calendar__event_grid">
								<table class="usam_days-grid-table">
									<tr v-for="week in displayCells">
										<td v-for="cell in week" class="usam_day view_add_task_window" @click="open_window_add_event_month(cell)">
											<div class="usam_day_cell" style="height: 123px;" :class="{'usam_current_day':cell.current}"><div class="usam_day-title"><a href="" class="usam_day-link" title="<?php _e( 'Посмотреть события в этот день', 'usam'); ?>">{{cell.title}}</a></div></div>
										</td>
									</tr>							
								</table>
								<div class='usam_event_holder' v-if="!loading"> 
									<div v-for="(item, k) in events" class="usam_event" v-if="selectedCalendars.includes(item.calendar) && item.show" :class="[item.active?'usam_event_hover':'']" @click="openEvent(item)" :style="{width:item.width+'px',top:item.top + 'px',left:item.left+'px'}" @mouseover="month_event_mouseover(item.id)" v-on:mouseleave="month_event_mouseleave()">{{item.title}}</div>
								</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>			
		</div>
		<?php 
	}	
	
	
	/* НЕДЕЛЬНЫЙ ГРАФИК*/	
	public function controller_week() 
	{				
		$w = date('w', mktime( 0,0,0, $this->month, $this->day, $this->year));
		if ( $w == 0 )
			$day_from = $this->day - 6;	
		else
			$day_from = $this->day - $w+1;	
		
		$date_from = mktime( 0,0,0, $this->month, $day_from, $this->year);		
		$back_day = mktime( 0,0,0, $this->month, $day_from-7, $this->year);
		$back_link = add_query_arg( array('day' => date('d', $back_day), 'month' => date('m', $back_day), 'year' => date('Y', $back_day) ), $this->url );	
		
		$date_to = mktime( 0,0,0, $this->month, $day_from+6, $this->year);		
		$next_day = mktime( 0,0,0, $this->month, $day_from+7, $this->year);
				
		$next_link = add_query_arg( array('day' => date('d', $next_day), 'month' => date('m', $next_day), 'year' => date('Y', $next_day) ), $this->url );
		?>
		<div id='tab-week' class='tab' :class="[tab=='week'?'current':'']">
			<div class='views_interval'>
				<a href="<?php echo $back_link; ?>" class="usam_month_back" title="<?php _e( 'Предыдущая неделя', 'usam'); ?>">&larr;</a>
				<?php echo date_i18n('d F', $date_from ); ?> - 			
				<?php echo date_i18n('d F', $date_to ); ?>						
				<a href="<?php echo $next_link; ?>" class="usam_next_month" title="<?php _e( 'Следующая неделя', 'usam'); ?>">&rarr;</a>
			</div>
			<div class="tab_calendar_title"><?php echo sprintf( __('%s неделя %s года','usam'), date_i18n('W', mktime( 0,0,0, $this->month, $this->day, $this->year)), $this->year ); ?></div>
			<table class="usam_table_week_calendar usam_table_calendar">
				<tbody>					
					<tr class="usam_days-title">						
						<td>		
							<div id="days_title" class="usam_month-title" style="visibility: visible;">
								<b id="mo" title="<?php _e( 'Понедельник', 'usam'); ?>"><i>Пн</i></b>							
								<b id="tu" title="<?php _e( 'Вторник', 'usam'); ?>"><i>Вт</i></b>
								<b id="we" title="<?php _e( 'Среда', 'usam'); ?>"><i>Ср</i></b>
								<b id="th" title="<?php _e( 'Четверг', 'usam'); ?>"><i>Чт</i></b>
								<b id="fr" title="<?php _e( 'Пятница', 'usam'); ?>"><i>Пт</i></b>
								<b id="sa" title="<?php _e( 'Суббота', 'usam'); ?>"><i>Сб</i></b>
								<b id="su" title="<?php _e( 'Воскресенье', 'usam'); ?>"><i>Вс</i></b>
							</div>
						</td>
					</tr>
					<tr>
						<td class="usam_days-grid-td">
							<div class="usam_time"><div class="usam_timeline_title" v-for="hour in 24">{{hour-1}}:00</div></div>
							<div class="calendar__event_grid">
								<table class="usam_days-grid-table">
									<thead>
										<tr>
											<td class="usam_day" v-for="cell in displayCells">
												<div class="usam_day-title" :class="{'usam_current_day':cell.current}"><a href="" class="usam_day-link" title="<?php _e( 'Посмотреть события в этот день', 'usam'); ?>">{{cell.title}}</a></div>
											</td>							
										</tr>
									</thead>	
									<tbody>
										<tr>
											<td class="usam_day" v-for="cell in displayCells">
												<div class="usam_day_cell">
													<table class="usam_table_day_calendar" id="usam_scel_table_day">
														<tr class="usam_days-tbl-grid" v-for="hour in 24">
															<td class="usam_hour">
																<div class="usam_tasks_cell">
																	<div class="row_1 view_add_task_window" @click="open_window_add_event_week(cell, hour, '00')"></div>
																	<div class="row_2 view_add_task_window" @click="open_window_add_event_week(cell, hour, '30')"></div>						
																</div>
															</td>
														</tr>
													</table>										
												</div>
											</td>
										</tr>
									</tbody>
								</table>
								<div class='usam_event_holder' v-if="!loading"> 
									<div v-for="(item, k) in events" v-if="selectedCalendars.includes(item.calendar) && item.show" class="usam_event" :class="[item.active?'usam_event_hover':'']" @click="openEvent(item)" :style="{width:item.width+'px',height:item.height+'px',top:item.top + 'px',left:item.left+'px'}" @mouseover="week_event_mouseover(item.id)" v-on:mouseleave="week_event_mouseleave()">{{item.title}} {{item.start}} {{item.end}}</div>
								</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>			
		</div>		
		<?php 
	}
	
	public function controller_day() 
	{				
		$back_day = mktime( 0,0,0, $this->month, $this->day-1, $this->year);
		$back_day_link = add_query_arg( array('day' => date('d', $back_day), 'month' => date('m', $back_day), 'year' => date('Y', $back_day)), $this->url );	
		
		$next_day = mktime( 0,0,0, $this->month, $this->day+1, $this->year);
		$next_day_link = add_query_arg( array('day' => date('d', $next_day), 'month' => date('m', $next_day), 'year' => date('Y', $next_day)), $this->url );
		?>
		<div id='tab-day' class='tab' :class="[tab=='day'?'current':'']">
			<div class='views_interval'>
				<a href="<?php echo $back_day_link; ?>#calendar_tab-tab-day" class="usam_month_back" title="<?php _e( 'Предыдущий месяц', 'usam'); ?>">&larr;</a>
				<?php echo date_i18n('l, d F Y', mktime( 0,0,0, $this->month, $this->day, $this->year)); ?>				
				<a href="<?php echo $next_day_link; ?>#calendar_tab-tab-day" class="usam_next_month" title="<?php _e( 'Следующий месяц', 'usam'); ?>">&rarr;</a>
			</div>	
			<div class="tab_calendar_title"><?php echo date_i18n('l, d', mktime( 0,0,0, $this->month, $this->day, $this->year)); ?></div>
			<table class="usam_table_day_calendar usam_table_calendar">
				<tbody>					
					<tr>
						<td class="usam_days-grid-td">
							<div class="calendar__event_grid">
								<table class="usam_days-grid-table usam_table_day_calendar" id="usam_scel_table_day">
									<tr class="usam_days-tbl-grid" v-for="hour in 24">
										<td class="usam_hour">
											<div class="usam_tasks_cell">
												<div class="usam_time">
													<div class="usam_timeline_title">{{hour-1}}:00</div>
												</div>
												<div class="usam_tasks_box">						
													<div class="row_1 view_add_task_window" @click="open_window_add_event_day(hour, '00')"></div>
													<div class="row_2 view_add_task_window" @click="open_window_add_event_day(hour, '30')"></div>
												</div>
											</div>
										</td>
									</tr>
								</table>
								<div class='usam_event_holder' v-if="!loading"> 
									<div v-for="(item, k) in events" class="usam_event" v-if="selectedCalendars.includes(item.calendar) && item.show" @click="openEvent(item)" :class="[item.active?'usam_event_hover':'']" :style="{width:item.width+'px',top:item.top + 'px',left:item.left+'px'}" @mouseover="item.active=1">{{item.title}}</div>
								</div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>				
		</div>
		<?php 
	}
	
	public function calendar_display() 
	{		
		?>			
		<div id='calendar_tab' class = "calendar_tab">
			<div class='header_tab'>
				<div class='tab' @click="tab='month'" :class="[tab=='month'?'current':'']"><?php _e( 'Месяц', 'usam'); ?></div>
				<div class='tab' @click="tab='week'" :class="[tab=='week'?'current':'']"><?php _e( 'Неделя', 'usam'); ?></div>
				<div class='tab' @click="tab='day'" :class="[tab=='day'?'current':'']"><?php _e( 'День', 'usam'); ?></div>
			</div>
			<div class='countent_tabs'>
				<?php 						
				$this->controller_month();
				$this->controller_week();
				$this->controller_day();
				?>
			</div>
		</div>		
		<?php 	
	}	
} 
?>
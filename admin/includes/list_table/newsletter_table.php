<?php
require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );
require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Table_Newsletter extends USAM_List_Table
{
	protected $order = 'DESC';		
	protected $type = '';		
	
	public function get_views() 
	{
		$url = remove_query_arg(['post_status', 'paged', 'action', 'action2', 'm',  'paged', 's', 'orderby','order']);	
		$query_vars = $this->get_views_query_vars();
		$results = usam_get_newsletters( $query_vars );
	
		$statuses = array();		
		$total_count = 0;	
		if ( !empty($results) )
		{			
			foreach ( $results as $status )
			{
				$statuses[(int)$status->status] = (int)$status->count;						
			}
			$total_count = array_sum( $statuses );
		}
		$all_text = sprintf(_nx('Всего <span class="count">(%s)</span>', 'Всего <span class="count">(%s)</span>', $total_count, 'events', 'usam'), number_format_i18n($total_count) );
		$all_class = $this->status === 'all' && $this->search == '' ? 'class="current"' : '';			
		$href = add_query_arg( 'status', 'all', $url );
		$views = array(	'all' => sprintf('<a href="%s" %s>%s</a>', esc_url( $href ), $all_class, $all_text ));	
		foreach ( $statuses as $key => $count )		
		{						
			$label  = '';
			switch ( $key ) 
			{			
				case 0 :
					$label = __('Черновик','usam');				
				break;				
				case 4 :
					$label = __('В паузе','usam');						
				break;
				case 5 :
					$label = __('Отправляются','usam');							
				break;
				case 6 :
					$label = __('Отправленные','usam');					
				break;
			}	
			$text = $text = sprintf( $label.' <span class="count">(%s)</span>', number_format_i18n( $count )	);
			$href = add_query_arg( 'status', $key, $url );
			$class = $this->status === $key ? 'class="current"' : '';
			$views[$key] = sprintf( '<a href="%s" %s>%s</a>', esc_url( $href ), $class, $text );			
		}
		return $views;
	}	
	
	function column_trigger( $item ) 
    {
		$event_start = usam_get_newsletter_metadata( $item->id, 'event_start' );
		echo usam_get_name_mailing_trigger_type( $event_start );
	}
	
	function get_bulk_actions_display() 
	{			
		$actions = [
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}
	
	function column_opened( $item )
    {		
		switch ( $item->status ) 
		{			
			case 0 :
			break;
			case 1 :		
			break;
			case 2 :
	
			break;
			case 4 :
			case 5 :
			case 6 :
				if ( $item->count_subscribers > 0 )
				{
					$rate_opened = round($item->number_opened*100/$item->count_subscribers,1);
					$rate_clicked = round($item->number_clicked*100/$item->count_subscribers,1);
					$rate_unsub = round($item->number_unsub*100/$item->count_subscribers,1);
				}
				else
				{
					$rate_opened = 0;	
					$rate_clicked = 0;	
					$rate_unsub = 0;	
				}
				?>
				<a href="<?php echo admin_url('admin.php?page=newsletter&tab=email_newsletters&form=view&form_name=email_newsletter&id='.$item->id); ?>" class="stats" title="<?php echo $item->number_opened.' - '.$item->number_clicked.' - '.$item->number_unsub; ?>">
					<?php echo $rate_opened . '% - ' . $rate_clicked . '% - ' . $rate_unsub . '%'; ?>
				</a>
				<?php
				if ( $item->class == 'trigger' )
					echo "<span class ='stat'><br>".sprintf( __('Отправлено %s', 'usam'), $item->count_subscribers )."</span>";
				else
				{
					if ( $item->count_subscribers > 0 )
						$p = " (".round($item->number_sent*100/$item->count_subscribers,2)."%)";
					else
						$p = '';					
					echo "<span class ='stat'>".'<br>'.sprintf( __('Отправлено %s из %s', 'usam'), $item->number_sent, $item->count_subscribers ).$p."</span>";
				}
			break;			
		}	
	}
	
	function column_lists( $item )
    {
		$lists = usam_get_mailing_lists(['newsletter_id' => $item->id]);
		foreach ( $lists as $list )
		{
			echo "<a href='".admin_url("admin.php?page=crm&tab=contacts&window=all&mailing_lists=".$list->id)."' class='action-send-editor'>$list->name</a><br>";	
		}	
	}	
	
	function column_action( $item )
    {				
		$title = '';
		$send = '';
		switch ( $item->status ) 
		{					
			case 5 :
				$title = __('Остановить','usam');
				
				if ( $item->class !== 'trigger' )
				{
					$url = add_query_arg(['action' => 'sending'], $this->item_url( $item->id ) );					
					$send = "<br><a href='".$url."' class='action-send-editor' title ='".__('Отправить партию', 'usam')."'><span class ='usam-dashicons-icon'></span></a>";	
				}
			break;
			case 4 :
				$title = __('Запустить','usam');
			break;
		}		
		echo "<span class ='status-{$item->status} js-newsletter-status usam-dashicons-icon' title ='$title' data-mail_id='$item->id'></span>";
		echo $send;
	}
		
	function column_status( $item )
    {				
		if ( $item->class == 'trigger' )
		{			
			switch ( $item->status ) 
			{
				case 0 :
					echo "<span class='status-{$item->status} item_status'>".__('Черновик','usam')."</span>";
				break;			
				case 5 :
					echo "<span class='status-{$item->status} item_status'>".__('Отправляю','usam')."</span>";					
				break;
				case 4 :
					echo "<span class='status-{$item->status} item_status'>".__('В паузе','usam')."</span>";					
				break;
			}
		}
		else
		{
			switch ( $item->status ) 
			{			
				case 0 :
					echo "<span class ='status-{$item->status} item_status'>".__('Черновик','usam')."</span>";
				break;
				case 1 :
			//		_e('Черновик','usam');
				break;
				case 4 :
					echo "<span class ='status-{$item->status} item_status'>".__('В паузе','usam')."</span>";
				break;
				case 5 :
					$start_date = strtotime($item->start_date);			
					$time = time();
					if ( $item->start_date && $start_date > $time )
						echo "<span class ='status-{$item->status} item_status'>".sprintf(__('Отправка через %s', 'usam'),human_time_diff( $start_date, $time ))."</span>";
					else
						echo "<span class ='status-{$item->status} item_status'>".__('Отправляю', 'usam')."</span>";
				break;
				case 6 :
					echo "<span class ='status-{$item->status} item_status'>".__('Отправлено','usam')."</span>";	
					if ( !empty($item->sent_at) )
						echo "<p>".usam_local_date( $item->sent_at )."</p>";					
				break;
			}
		}
    }
	
	function column_class( $item )
    { 
		switch ( $item->class ) 
		{			
			case 'trigger' :
				_e('Триггерная','usam');
			break;
			case 'simple':
				_e('Стандартная','usam');
			break;				
		}
	}
   	  
	function get_sortable_columns()
	{
		$sortable = array(
			'title'       => array('name', false),		
			'status'      => array('status', false),		
			'class'       => array('class', false),		
			'date'        => array('date_insert', false),		
			'sent_at'     => array('sent_at', false),		
			);
		return $sortable;
	}
	
	function get_newsletter_query_vars() 
	{
		$this->get_query_vars();
		$this->query_vars['cache_results'] = true;
		$this->query_vars['add_fields'] = ['count_subscribers'];	
		if ( empty($this->query_vars['include']) )
		{							
			if ( $this->status != 'all' ) 
			{			
				$this->query_vars['status'] = $this->status;
			}			
		} 
	}	
}
?>
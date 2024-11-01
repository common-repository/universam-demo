<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_export_list_table.class.php' );	
class USAM_Export_List_Table_Payment extends USAM_Export_List_Table
{
	public function column_customer( $item )
	{
		?>
		<strong>
			<?php echo esc_html( $item->firstname . ' ' . $item->lastname ); ?>
		</strong><br>
		<small><?php echo $item->email; ?></small>
		<?php
	}		

	public function column_order_id( $item ) 
	{	
		echo esc_html( $item->order_id )."<br/>";
		echo '<span class="order_status">'.usam_get_object_status_name( $item->status, 'order' ).'</span>';	
	}
	
	public function column_date_payed( $item ) 
	{
		echo date_i18n( __( get_option( 'date_format', 'Y/m/d' ) )." H:i", strtotime($item->date_payed) );
	}	

	public function column_date( $item ) 
	{
		$format = __('Y/m/d H:i:s A' );
		$timestamp = strtotime($item->date_insert);
		$full_time = date_i18n( $format, $timestamp );
		$time_diff = current_time('timestamp') - $timestamp;
		if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 )
			$h_time = $h_time = sprintf( __('%s назад' ), human_time_diff( $timestamp, current_time('timestamp') ) );
		else
			$h_time = date_i18n( __( get_option( 'date_format', 'Y/m/d' ) ), $timestamp );

		echo '<abbr title="' . $full_time . '">' . $h_time . '</abbr>';
	}

	public function column_sum( $item ) 
	{
		echo usam_get_formatted_price( $item->sum );
	}
	
	public function column_transactid( $item ) 
	{		
		echo $item->transactid;
	}
	
	public function column_document_number( $item ) 
	{		
		echo $item->number;
	}
		
	public function column_status( $item ) 
	{				
		$statuses = array( 1 => __('Не оплачено', 'usam'), 
						   2 =>	__('Отклонено', 'usam'),
						   3 =>	__('Оплачено', 'usam'),		
		);
		foreach ( $statuses as $key => $status ) 
		{	
			if (  $key == $item->status )
			{				
				echo $status;
				break;
			}			
		}	
	}
	
	public function column_name( $item )
	{
		echo $item->name;		
	}
}
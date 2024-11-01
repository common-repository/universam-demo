<?php
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
class USAM_Export_List_Table extends WP_List_Table
{	
	private $_table;
	
	public function __construct( $args = array() ) 
	{	
		parent::__construct( );				
	
		$this->_table = $args['class_table'];	
		$this->_table->prepare_items();		
		$this->items = $this->_table->items;
	}
	
	function get_print_page( ) { } 	
	
	function column_date( $item )
    {			
		if ( is_object($item) )
			$date = $item->date_insert;
		elseif ( is_array($item) )
			$date = $item['date_insert'];			
		if ( !empty($date) )
		{			
			$timestamp = strtotime( $date );	
			$full_time = usam_local_date( $date );
			$time_diff = time() - $timestamp;
			if ( $time_diff > 0 && $time_diff < 86400 ) // 24 * 60 * 60
				$h_time = sprintf( __('%s назад' ), human_time_diff( $timestamp, time() ) );
			else
				$h_time = $full_time;
			
			echo '<abbr title="'.$full_time.'">' . $h_time . '</abbr>';
		}
	}
	
	protected function column_default( $item, $column_name ) 
	{
		$method_column = "column_$column_name";	
		if ( method_exists($this, $method_column) )  		
			$this->$method_column( $item );		
		elseif ( is_array($item) && isset($item[$column_name]))
			return $item[$column_name];
		elseif ( isset($item->$column_name) )
			return $item->$column_name;
	}
	
	protected function get_column_info() 
	{		
		$columns = get_column_headers( $this->screen );
		$hidden = get_hidden_columns( $this->_table->screen );

		$primary = $this->get_primary_column_name();
		$this->_column_headers = array( $columns, $hidden, array(), $primary );

		return $this->_column_headers;
	}
	
	public function print_column_headers( $with_id = true ) 
	{
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();		

		foreach ( $columns as $column_key => $column_display_name ) 
		{
			$class = array( 'manage-column', "column-$column_key" );

			if ( in_array( $column_key, $hidden ) ) {
				continue;
			}

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			$tag = ( 'cb' === $column_key ) ? 'td' : 'th';		
			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<$tag $id $class>$column_display_name</$tag>";
		}
	}	
	
	public function get_row_columns( $item ) 
	{
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
		$row = array();
		foreach ( $columns as $column_name => $column_display_name ) 
		{					
			ob_start();		
			if ( method_exists( $this, 'column_' . $column_name ) )							
				$result =  call_user_func( array( $this, 'column_' . $column_name ), $item );					 
			else 				
				$result = $this->column_default( $item, $column_name );	
			
			if ( empty($result)  )
				$result = ob_get_contents();
			$row[$column_name] = $result;
			ob_end_clean();		
		}			
		return $row;
	}
	
	public function display_rows() 
	{
		foreach ( $this->items as $item )
			$this->single_row( $item );
	}
		
	protected function single_row_columns( $item ) 
	{
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) 
		{
			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}			
			if ( in_array( $column_name, $hidden ) ) {
				continue;
			}			
			$data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

			$attributes = "class='$classes' $data";

			echo "<td $attributes>";
			echo $this->column_default( $item, $column_name );			
			echo "</td>";
		}
	}
	
	public function column_manager( $item ) 
	{	
		$user = get_user_by('id', $item->manager_id );
		echo isset($user->display_name)?"$user->display_name ({$user->user_login})":"";		
	}	
	
	public function get_columns()
	{
		$columns = $this->_table->get_columns();
		
		unset($columns['cb']);
		return $columns;
	}
	
	public function print_column_footer() 
	{	
		if ( empty($this->_table->results_line) )
		{
			?>
			<tfoot>
				<tr>
					<?php $this->print_column_headers(false); ?>
				</tr>
			</tfoot>
			<?php	
		}
		else
		{ 
			?>
			<tfoot>
				<tr class="results_line">
			<?php	
			list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();				
			foreach ( $columns as $column_key => $column_display_name ) 
			{
				$class = array( 'manage-column', "column-$column_key" );

				if ( in_array( $column_key, $hidden ) ) {
					$class[] = 'hidden';
				}
				if ( $column_key === $primary ) {
					$class[] = 'column-primary';
				}						
				$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
				$scope = ( 'th' === $tag ) ? 'scope="col"' : '';

				if ( !empty( $class ) ) {
					$class = "class='" . join( ' ', $class ) . "'";
				}
				$display_name = isset($this->_table->results_line[$column_key])?$this->_table->results_line[$column_key]:'';			
				echo "<$tag $scope $class>$display_name</$tag>";
			}	
			?>			
				</tr>
			</tfoot>
			<?php	
		}
	}
	
	public function display() 
	{
		?>
		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tbody id="the-list">
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>	
			<?php $this->print_column_footer( ); ?>
		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}
}
?>
<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_instagram extends USAM_List_Table
{		
	function column_thumbnail( $item ) 
	{	
		?><img src="<?php echo $item['images']['thumbnail']['url']; ?>"><?php
    }	
	
	function column_user( $item ) 
	{		
		echo $item['user']['full_name'];
		
	//	$instagram = new USAM_Instagram_API();		
//		$results = $instagram->get_media_comments( $item['id'] );
    }		
		
	function column_text( $item ) 
	{
		echo $item['caption']['text'];
	}
	
	function column_online( $item ) 
	{
		echo $item['online']?__('Онлайн','usam'):'';
	}
	
	public function extra_tablenav_display( $which ) 
	{
		if ( 'top' == $which && $this->filter_box ) 
		{
			
		}
	}	
		    	
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',
			'thumbnail'      => __('Фотографии', 'usam'),				
			'user'           => __('Пользователь', 'usam'),									
			'text'           => __('Описание', 'usam'),		
        );		
        return $columns;
    }	
	
	function prepare_items() 
	{		
		global $profile_id;		
		
		$offset = ($this->get_pagenum() - 1) * $this->per_page;
		require_once( USAM_APPLICATION_PATH . '/social-networks/instagram_api.class.php' );
		$instagram = new USAM_Instagram_API();
		
		$params = array( 'count' => $this->per_page, 'max_id' => $offset );		
		$this->items = $instagram->get_user_media( );
		if ( !empty($this->items) )
			$this->set_pagination_args( array('total_items' => count($this->items), 'per_page' => $this->per_page) );
	}
}
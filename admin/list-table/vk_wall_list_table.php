<?php
require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_vk_wall extends USAM_List_Table
{		
	public function get_bulk_actions_display() 
	{		
		$actions = array(
			'delete' => __('Удалить', 'usam'),					
		);		
		return $actions;
	}	
	
	public function return_post()
	{
		return array( 'profile_id' );
	}	
	
	function column_thumbnail( $item ) 
	{	
		if ( !empty($item['attachments']) )
		{
			foreach ( $item['attachments'] as $attachment ) 
			{ 
				if (  $attachment['type'] == 'photo' )
				{ 
					foreach ( $attachment['photo']['sizes'] as $size ) 
					{
						if (  $size['width'] == 320 )
						{
							?><img src="<?php echo $size['url']; ?>"><?php
						}
					}
				}
			}
		}
    }		
		
	function column_text( $item ) 
	{
		echo $item['text'];
	}
	
	function column_online( $item ) 
	{
		echo $item['online']?__('Онлайн','usam'):'';
	}
		    	
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',
			'thumbnail'      => __('Фотографии', 'usam'),		
			'text'           => __('Описание', 'usam'),			
        );		
        return $columns;
    }	
	
	function prepare_items() 
	{		
		if ( !empty($_REQUEST['profile_id']) )
		{			
			$profile_id = isset($_REQUEST['profile_id'])?absint($_REQUEST['profile_id']):0;
			$profile = usam_get_social_network_profile( $profile_id );
		}
		else
		{
			$profile = (array)usam_get_social_network_profiles(['type_social' => ['vk_group', 'vk_user'], 'number' => 1]);	
		}	
		$offset = ($this->get_pagenum() - 1) * $this->per_page;		
		$params = ['offset' => $offset, 'count' => $this->per_page, 'fields' => 'first_name,last_name'];		
		$vkontakte = new USAM_VKontakte_API( $profile );
		$results = $vkontakte->get_wall( $params );	
		if ( $results )
		{
			$this->total_items = $results['count'];
			$this->items = $results['items']; 				
		}
		$this->set_pagination_args( array('total_items' => $this->total_items, 'per_page' => $this->per_page) );		
	}
}
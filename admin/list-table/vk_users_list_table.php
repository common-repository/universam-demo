<?php
require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_vk_users extends USAM_List_Table
{			
	public function return_post()
	{
		return array( 'profile_id' );
	}	
	
	function column_name( $item ) 
	{				
		$name = "<div class='user_block'>";
		$name .= "<a href='https://vk.com/id".$item['id']."' class ='image_container usam_foto'><img loading='lazy' src='".$item['photo_50']."'></a>";
		$name .= "<div>";
		$name .= '<a href="https://vk.com/id'.$item['id'].'" target="_blank" rel="noopener">' .$item['last_name']." ".$item['first_name'].'</a>';
		if ( $item['online'] )
			$name .= "<span class='customer_online' title='".__('Онлайн','usam')."'></span>";		
		if ( isset($user['bdate']) )
		{
			$day = explode(".",$user['bdate']);		
			$name .= "<div><strong>".__('ДР', 'usam').":</strong> ".$user['bdate']."</div>";
		}
		if ( isset($item['city']['title']) )
			$name .= "<div>".$item['city']['title']."</div>";
		$name .= "</div>";
		$name .= "</div>";
		echo $name;
    }		
		
	function column_country( $item ) 
	{
		echo !empty($item['country']['title'])?$item['country']['title']:'';
	}
	
	function column_has_mobile( $item ) 
	{
		echo !empty($item['has_mobile'])?__('Мобильное устройство','usam'):'';
	}
	
	function column_can_see_all_posts( $item ) 
	{
		echo !empty($item['can_see_all_posts'])?'<span class="yes">'.__('Видит','usam')."</span>":"<span class='no'>".__('Не видит','usam')."</span>";
	}
	
	function column_can_post( $item ) 
	{
		echo !empty($item['can_post'])?'<span class="yes">'.__('Можно оставлять','usam')."</span>":"<span class='no'>".__('Нельзя оставлять','usam')."</span>";
	}
		    	
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',
			'name'           => __('Имя', 'usam'),				
			'has_mobile'     => __('Устройство', 'usam'),	
			'can_see_all_posts' => __('Чужие записи на стене', 'usam'),	
			'can_post' => __('Записи на стене', 'usam'),
			
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
			$profile = usam_get_social_network_profiles( array( 'type_social' => array( 'vk_group', 'vk_user' ), 'number' => 1 ) );	
		}		
		$offset = ($this->get_pagenum() - 1) * $this->per_page;
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
		$vkontakte = new USAM_VKontakte_API( $profile );
		
		$params = ['fields' => 'sex, city, country, has_mobile, online, bdate, photo_50, can_see_all_posts, can_post'];		
		if ( $this->search != '' )
		{
			$search_words = explode(' ', mb_strtolower($this->search));
			$users = $vkontakte->get_group_members( $params );	
			foreach( $users as $user )
			{
				foreach( $search_words as $search )
				{
					if ( mb_strtolower($user['first_name']) == $search || mb_strtolower($user['last_name']) == $search )
						$this->items[] = $user;
				}
			}
			$this->total_items = count($this->items);
		}
		else
		{ 
			$params['offset'] = $offset;
			$params['count'] = $this->per_page;	
			$params['group_id'] = $profile['code'];
			$results = $vkontakte->send_request( $params, 'groups.getMembers' );		
			
			if ( !empty($results['items']) )
			{
				$this->items = $results['items'];
				$this->total_items = $results['count'];
			}
		}
		$this->set_pagination_args( array('total_items' => $this->total_items, 'per_page' => $this->per_page) );
	}
}
<?php
require_once( USAM_FILE_PATH .'/admin/includes/product_list_table.php' );
class USAM_List_Table_fb_products extends USAM_Product_List_Table
{
	private $profile = array();		
	function __construct( $args = array() )
	{			
		if ( !empty($_REQUEST['profile_id']) )
		{			
			$profile_id = isset($_REQUEST['profile_id'])?absint($_REQUEST['profile_id']):0;
			$this->profile = usam_get_social_network_profile( $profile_id );
		}
		else
		{
			$this->profile = (array)usam_get_social_network_profiles( array( 'type_social' => array( 'fb_group', 'fb_user' ), 'number' => 1 ) );	
		}
		parent::__construct( $args );				
	}
	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'add_post'    => __('Публиковать на стене', 'usam')
		);
		return $actions;
	}
	
	function column_fb_post( $item ) 
	{	
		if ( !empty($this->profile) )		
			$post_id = usam_get_social_post_id( $item->ID, $this->profile );
		if ( !empty($post_id) )
		{
			$fb_post_link = $this->profile['type_social'] == 'fb_group' ? $this->profile['uri'].'?w=wall-'.$this->profile['code'].'_'.$post_id : 'id'.$this->profile['code'].'?w=wall-'.$this->profile['code'].'_'.$post_id;
			?>
			<a href="http://vk.com/<?php echo $fb_post_link; ?>" target="_blank"><?php _e('Опубликован','usam'); ?></a>
			<br>
			<?php 
			echo usam_get_social_post_publish_date( $item->ID, $this->profile );
		}		
		else
			_e('Не опубликован','usam');	
    }	
	
	function column_fb_market( $item ) 
	{			
		if ( !empty($this->profile) && $this->profile['type_social'] == 'fb_group' )
		{
			$market_id = usam_get_product_meta( $item->ID, 'fb_market_id_'.$this->profile['code'] );
			if ( $market_id )
			{
				$publish_date = usam_get_product_meta( $item->ID, 'fb_market_publish_date_'.$this->profile['code'] );
				$fb_post_link = $this->profile['uri'].'?w=product-'.$this->profile['code'].'_'.$market_id;
				?>
				<a href="http://vk.com/<?php echo $fb_post_link; ?>" target="_blank"><?php _e('Опубликован','usam'); ?></a>
				<br>
				<?php 
				echo usam_local_date( $publish_date );
			}
			else
				_e('Не опубликован','usam');
		}
    }	
	
	function get_sortable_columns()
	{
		$sortable = array(
			'product_title'  => array('product_title', false),	
			'price'          => array('price', false),	
			'stock'          => array('stock', false),		
			'views'          => array('views', false),				
			'date'           => array('date', false),
			'fb_post'        => array('fb_post', false),	
			'fb_market'      => array('fb_market', false),		
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',
			'product_title'  => __('Имя', 'usam'),			
			'price'          => __('Цена', 'usam'),		
			'fb_market'      => __('Публикация товара', 'usam'),					
			'fb_post'        => __('На стене', 'usam'),								
			'stock'          => __('Запас', 'usam'),			
			'date'           => __('Дата', 'usam')			
        );		
        return $columns;
    }			

	function query_vars( $query_vars ) 
	{	
		if ( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] == 'fb_market' )
		{
			$query_vars['productmeta_key'] = 'fb_market_publish_date_'.$this->profile['code'];
			$query_vars['orderby'] = 'productmeta_value';
		}
		if ( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] == 'fb_post' )
		{
			$query_vars['postmeta_key'] = 'publish_date_'.$this->profile['type_social'].'_'.$this->profile['code'];
			$query_vars['orderby'] = 'postmeta_value';
		}
		return $query_vars;
	}
}
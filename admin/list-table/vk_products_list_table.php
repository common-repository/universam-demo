<?php
require_once( USAM_FILE_PATH .'/admin/includes/product_list_table.php' );
class USAM_List_Table_vk_products extends USAM_Product_List_Table
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
			$this->profile = (array)usam_get_social_network_profiles(['type_social' => ['vk_group', 'vk_user'], 'number' => 1]);	
		parent::__construct( $args );				
	}
	
	public function get_views() {}
	
	protected function bulk_actions( $which = '' ) 
	{ 		
		if ( empty($this->profile) )
			return false;
	
		if ( !$this->bulk_actions )
			return false;
					
		static $count = 0;
		$count++;		
		$actions = $this->get_bulk_actions();
		$actions = apply_filters( "bulk_actions-{$this->screen->id}", $actions );		
		if ( $count == 1 ) 
			$two = '';
		else 
			$two = '2';			
		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __('Select bulk action' ) . '</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
			echo '<option value="-1">' . __('Массовые действия', 'usam') . "</option>\n";			
			echo '<optgroup label="' . __('Товары Вконтакте', 'usam') . '">';				
				echo '<option value="add_product">'.__('Публиковать товары', 'usam')."</option>\n";		
				echo '<option value="update">' . __('Обновить товары', 'usam') . "</option>\n";				
				echo '<option value="delete">' . __('Удалить товары', 'usam') . "</option>\n";				
			echo '</optgroup>';		
			echo '<optgroup label="' . __('Работа со стеной', 'usam') . '">';
				echo "\t" . '<option value="add_post">'.__('Публиковать на стене', 'usam')."</option>\n";
			echo '</optgroup>';			
			echo "\t" . '<option value="add_image">'.__('Фото в альбом группы', 'usam')."</option>\n";
		echo "</select>\n";

		submit_button( __('Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}		
			
	function column_vk_post( $item ) 
	{	
		if ( !empty($this->profile) )		
			$post_id = usam_get_social_post_id( $item->ID, $this->profile );
		if ( !empty($post_id) )
		{
			$vk_post_link = $this->profile['type_social'] == 'vk_group' ? $this->profile['uri'].'?w=wall-'.$this->profile['code'].'_'.$post_id : 'id'.$this->profile['code'].'?w=wall-'.$this->profile['code'].'_'.$post_id;
			?>
			<a href="http://vk.com/<?php echo $vk_post_link; ?>" class="item_status item_status_valid" target="_blank" rel="noopener"><?php _e('Опубликован','usam'); ?></a>
			<br>
			<?php 
			echo usam_get_social_post_publish_date( $item->ID, $this->profile );
		}		
		else
			_e('Не опубликован','usam');	
    }	
	
	function column_vk_market( $item ) 
	{			
		if ( !empty($this->profile) && $this->profile['type_social'] == 'vk_group' )
		{
			$market_id = usam_get_product_meta( $item->ID, 'vk_market_id_'.$this->profile['code'] );
			if ( $market_id )
			{
				$publish_date = usam_get_product_meta( $item->ID, 'vk_market_publish_date_'.$this->profile['code'] );
				$vk_post_link = $this->profile['uri'].'?w=product-'.$this->profile['code'].'_'.$market_id;
				?>
				<a href="http://vk.com/<?php echo $vk_post_link; ?>" class="item_status item_status_valid" target="_blank" rel="noopener"><?php _e('Опубликован','usam'); ?></a>
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
			'vk_post'        => array('vk_post', false),	
			'vk_market'      => array('vk_market', false),		
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',	
			'product_title'  => __('Имя', 'usam'),				
			'price'          => __('Цена', 'usam'),		
			'vk_market'      => __('Публикация товара', 'usam'),					
			'vk_post'        => __('На стене', 'usam'),								
			'stock'          => __('Запас', 'usam'),			
			'date'           => __('Дата', 'usam')			
        );		
        return $columns;
    }			

	function query_vars( $query_vars ) 
	{	
		$query_vars['post_status'] = 'publish';
		if ( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] == 'vk_market' )
		{
			$query_vars['productmeta_key'] = 'vk_market_publish_date_'.$this->profile['code'];
			$query_vars['orderby'] = 'productmeta_value';
		}
		if ( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] == 'vk_post' )
		{
			$query_vars['postmeta_key'] = 'publish_date_'.$this->profile['type_social'].'_'.$this->profile['code'];
			$query_vars['orderby'] = 'postmeta_value';
		}
		return $query_vars;
	}
}
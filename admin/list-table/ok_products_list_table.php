<?php
require_once( USAM_FILE_PATH .'/admin/includes/product_list_table.php' );
class USAM_List_Table_ok_products extends USAM_Product_List_Table
{		
	private $profile = array();		
	function __construct( $args = array() )
	{				
		if ( !empty($_REQUEST['profile_id']) )
		{
			$profile_id = absint($_REQUEST['profile_id']);
			$this->profile = usam_get_social_network_profile( $this->profile_id );
		}
		else
			$this->profile = (array)usam_get_social_network_profiles( array( 'type_social' => array( 'ok_group', 'ok_user' ), 'number' => 1 ) );		
		parent::__construct( $args );				
	}
	
	public function get_views() {}
	
	protected function bulk_actions( $which = '' ) 
	{ 	
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
			echo '<option value="delete">' . __('Удалить', 'usam') . "</option>\n";				
			echo '<optgroup label="' . __('Работа со стеной', 'usam') . '">';
				echo "\t" . '<option value="add_post">'.__('Публиковать на стене', 'usam')."</option>\n";
			echo '</optgroup>';					
		echo "</select>\n";

		submit_button( __('Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}	
				
	function column_ok_post( $item ) 
	{	
		if ( !empty($this->profile['id']) )		
			$post_id = usam_get_social_post_id( $item->ID, $this->profile );
		if ( !empty($post_id) )
		{	
			$ok_post_link = $this->profile['type_social'] == 'ok_group' ? $this->profile['code'].'/topic/'.$post_id : 'id'.$this->profile['code'].'/topic/'.$post_id;
			?>
			<a href="https://ok.ru/group/<?php echo $ok_post_link; ?>" target="_blank" rel="noopener"><?php _e('Опубликован','usam'); ?></a>
			<br>
			<?php 
			echo usam_get_social_post_publish_date( $item->ID, $this->profile );
		}		
		else
			_e('Не опубликован','usam');	
    }	

	function get_sortable_columns()
	{
		$sortable = array(
			'product_title'  => array('product_title', false),	
			'price'          => array('price', false),	
			'stock'          => array('stock', false),		
			'views'          => array('views', false),				
			'date'           => array('date', false),
			'ok_post'        => array('ok_post', false),		
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',
			'product_title'  => __('Имя', 'usam'),			
			'price'          => __('Цена', 'usam'),						
			'ok_post'        => __('На стене', 'usam'),								
			'stock'          => __('Запас', 'usam'),			
			'date'           => __('Дата', 'usam')			
        );		
        return $columns;
    }			

	function query_vars( $query_vars ) 
	{	
		$query_vars['post_status'] = 'publish';
		if ( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] == 'ok_post' )
		{
			$query_vars['postmeta_key'] = 'publish_date_'.$this->profile['type_social'].'_'.$this->profile['code'];
			$query_vars['orderby'] = 'postmeta_value';
		}
		return $query_vars;
	}
}
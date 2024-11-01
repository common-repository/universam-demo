<?php	
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_vk_posts extends USAM_List_Table
{
	private $profile = array();			
	protected $pimary_id = 'ID';	
		
	function __construct( $args = array() )
	{	
		if ( !empty($_REQUEST['profile_id']) )
		{			
			$profile_id = isset($_REQUEST['profile_id'])?absint($_REQUEST['profile_id']):0;
			$this->profile = usam_get_social_network_profile( $profile_id );
		}
		else
		{
			$this->profile = (array)usam_get_social_network_profiles( array( 'type_social' => array( 'vk_group', 'vk_user' ), 'number' => 1 ) );	
		}
		parent::__construct( $args );				
    }	
	
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
			if ( empty( $actions ) )
			{					
				foreach ( $actions as $name => $title ) 
				{
					$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';
					echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
				}	
			}	
			echo '<optgroup label="' . __('Работа со стеной', 'usam') . '">';
				echo "\t" . '<option value="add_post">'.__('Публиковать на стене', 'usam')."</option>\n";
			echo '</optgroup>';				
		echo "</select>\n";

		submit_button( __('Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}	
	
	function column_post_title( $item ) 
	{
		echo "<a href='".get_edit_post_link( $item->ID )."' class='product_title_link'>".$item->post_title."</a>"; 
	}
				
	function column_vk_post( $item ) 
	{	
		if ( !empty($this->profile['type_social']))
			$post_id = usam_get_social_post_id( $item->ID, $this->profile );
		if ( !empty($post_id) )
		{
			$vk_post_link = $this->profile['type_social'] == 'group' ? $this->profile['screen_name'].'?w=wall-'.$this->profile['code'].'_'.$post_id : 'id'.$this->profile['code'].'?w=wall-'.$this->profile['code'].'_'.$post_id;
			?>
			<a href="http://vk.com/<?php echo $vk_post_link; ?>" target="_blank" rel="noopener"><?php _e('Опубликована','usam'); ?></a>
			<br>
			<?php 
			echo usam_get_social_post_publish_date( $item->ID, $this->profile );
		}
		else
			_e('Не опубликована','usam');
    }
		
	function get_sortable_columns()
	{
		$sortable = array(
			'post_title'     => array('post_title', false),				
			'publish_date'   => array('vk_publish_date', false),	
			'vk_post_id'     => array('vk_post_id', false),				
			'views'          => array('views', false),				
			'post_date_gmt'  => array('post_date_gmt', false)
			);
		return $sortable;
	}

	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',			
			'post_title'     => __('Имя', 'usam'),
			'vk_post'        => __('На стене', 'usam'),			
			'views'          => '<span class = "usam-dashicons-icon" title="' . esc_attr__('Просмотры' ) . '"></span>',					
			'post_date_gmt'  => __('Дата', 'usam')			
        );		
        return $columns;
    }
	
	public function has_items() 
	{
		return have_posts();
	}
		
	public function display_rows( $posts = array(), $level = 0 ) 
	{
		global $wp_query;

		if ( empty( $posts ) )
			$posts = $wp_query->posts;	
		$this->_display_rows( $posts, $level );
	}


	private function _display_rows( $posts, $level = 0 ) 
	{
		$post_ids = array();

		foreach ( $posts as $a_post )
			$post_ids[] = $a_post->ID;
		
		foreach ( $posts as $post )
			$this->single_row( $post, $level );
	}

	function prepare_items() 
	{			
		global $wp_query;				
	
		$offset = ($this->get_pagenum() - 1) * $this->per_page;
		$query_vars = array(
			'post_status'    => 'publish',
			'post_type'      => 'post',			
			'posts_per_page' => $this->per_page,
			'offset'         => $offset,		
			's'              => $this->search,	
			'post_parent'    => 0				
		);					
		if ( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] == 'vk_post' )
		{
			$query_vars['postmeta_key'] = 'publish_date_'.$this->profile['type_social'].'_'.$this->profile['code'];
			$query_vars['orderby'] = 'postmeta_value';
		}		
		if ( !empty($this->records) )
			$query_vars['post__in'] = $this->records;
		
		$wp_query = new WP_Query( $query_vars );	
		$this->_column_headers = $this->get_column_info();		
		$this->set_pagination_args(['total_items' => $wp_query->found_posts, 'total_pages' => $wp_query->max_num_pages, 'per_page' => $this->per_page]);
	}	
}
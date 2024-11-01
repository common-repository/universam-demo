<?php
require_once( USAM_FILE_PATH . '/admin/includes/filter_processing.class.php' );
abstract class USAM_Grid_View 
{				
	protected $footer_bar = true;
	protected $search_box = true;	
	protected $filter_box = true;	
	protected $sortable = true;
	protected $views = true;	
	
	protected $js_args = [];
	protected $tab = '';	
	
	function __construct( $tab )
	{			
		$this->tab = $tab;		
		$this->prepare_items();
		add_action('admin_footer', [&$this, 'footer']);		
    }
	
	public function footer( ) 
	{
		if ( $this->js_args )
			printf( "<script>USAM_Grid = %s;</script>\n", wp_json_encode( $this->js_args ) );
	}
	
	protected function prepare_items( ) {}
	
	public function codes_interface_filters(  ) 
	{ 
		return [ $this->tab.'_grid', $this->tab ];
	}
		
	public function display_interface_filters(  ) 
	{ 
		$codes = $this->codes_interface_filters();
		require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
		$class = "USAM_Interface_Filters";
		foreach ( $codes as $code ) 
		{
			if ( $code )
			{
				$file = USAM_FILE_PATH . "/admin/interface-filters/{$code}_interface_filters.class.php";	
				if ( file_exists($file) )
				{
					require_once( $file );
					$class = "{$code}_Interface_Filters";
					break;					
				}				
			}
		}
		$interface_filters = new $class();
		?>
		<div class='action_panel' v-if="numberSelectedItems">
			<div class="action_panel_selected">
				<span class="action_panel_selected_label"><?php _e('Выбранные','usam'); ?>:</span>
				<span class="action_panel_selected_counter">{{numberSelectedItems}}</span>
				<span class="dashicons dashicons-no-alt" @click="cancelSelected"></span>
			</div>
		</div>
		<div class='toolbar_filters' v-else>
			<?php $interface_filters->display(); ?>
		</div>
		<?php
	}
	
	protected function counter_panel() 
	{ 
		printf( __('Всего: %s элементов', 'usam'), '<span class="counter_panel__total_items">{{items.length}}</span>' );
	}
	
	protected function class_grid() 
	{ 
		return $this->tab.'_grid';
	}
	
	public function display() 
	{
		?>				
		<div id="grid_view" class="<?php echo $this->class_grid(); ?>" v-cloak>		
			<?php $this->display_interface_filters( ); ?>
			<?php $this->display_grid( ); ?>
		</div>	
		<?php 
	}	
	
	public function display_grid() 
	{  		
		?>
		<div class="counter_panel">					
			<div class="counter_panel__values"><?php $this->counter_panel(); ?></div>	
		</div>
		<div class="grid_view_wrapper">			
			<?php $this->display_grid_items( ); ?>
		</div>
		<?php 
	}
	
	public function display_grid_items() 
	{  	
		
	}		
}
?>
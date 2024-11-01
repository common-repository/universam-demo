<?php
class USAM_Tab_html_blocks extends USAM_Page_Tab
{		
	protected $vue = true;	
	protected $json = true;
	protected $views = ['simple'];
		
	public function __construct() 
	{					
		add_action('admin_footer', [$this, 'display_footer']);
		add_action('admin_enqueue_scripts', function() { 				
			wp_enqueue_media();	
			wp_enqueue_script( 'v-color' ); 
		});
	}
	
	public function get_title_tab()
	{			
		return __('HTML блоки', 'usam');	
	}
	
	public function display_footer(  ) 
	{			
		$blocks = usam_get_html_blocks(['active' => 'all']);
		$register_blocks = usam_get_register_html_blocks();		
		$option_sections = [
			'style' => [
				['field_type' => 'text', 'name' => __('Внешние отступы', 'usam'), 'code' => 'margin', 'value' => '20px 0'],
				['field_type' => 'text', 'name' => __('Внутренние отступы', 'usam'), 'code' => 'padding', 'value' => ''],
				['field_type' => 'text', 'name' => __('Ширина', 'usam'), 'code' => 'max-width', 'value' => ''],
				['field_type' => 'text', 'name' => __('Высота', 'usam'), 'code' => 'height', 'value' => ''],
				['field_type' => 'color', 'name' => __('Цвет блока', 'usam'), 'code' => 'background-color', 'value' => ''],
			],
			'html' => [
				['field_type' => 'text', 'name' => __('Классы для блока', 'usam'), 'code' => 'classes', 'value' => ''],
				['field_type' => 'text', 'name' => __('Классы для контейнера', 'usam'), 'code' => 'classes_container', 'value' => ''],				
			],
			'data' => [],
			'content_style' => []			
		];
		foreach( $register_blocks as $j => $block )
		{
			foreach( $option_sections as $i => $section )
			{
				if( empty($block[$i]) )
					$register_blocks[$j][$i] = $section;
				else
					$register_blocks[$j][$i] = array_merge( $register_blocks[$j][$i], $section );
			}
		}	
		foreach( $blocks as $k => $block_db )
		{		
			$html_block = [];
			foreach( $register_blocks as $j => $block )
			{
				if( $block_db['template'] == $block['template'] )
				{
					foreach( $option_sections as $i => $section )
					{						
						$array = [];
						foreach( $block[$i] as $item )
						{							
							if( isset($block_db[$i][$item['code']]) )
								$item['value'] = $block_db[$i][$item['code']];
							elseif( !isset($item['value']) )
								$item['value'] = '';					
							$array[] = $item;
						}
						$block_db[$i] = $array;						
					}
					$html_block = $block;						
					$block_db['code'] = $block['code'];
					break;			
				}
			}
			if( empty($html_block ) )
			{
				unset($blocks[$k]);
				continue;
			}			
			if ( !empty($block['options']) )
			{				
				$options = [];
				foreach($block['options'] as $item )
				{
					$item['value'] = !empty($item['value']) ?$item['value']:'';		
					$item['value'] = isset($block_db['options'][$item['code']])?$block_db['options'][$item['code']]:$item['value'];
					if( $item['field_type'] == 'autocomplete' )
					{ 
						$item['search'] = '';
						if( $item['value'] )
						{							
							if( $item['request'] == 'pages' )
								$item['search'] = get_the_title( $item['value'] );
						}
					}
					$options[] = $item;
				}
				$block_db['options'] = $options;
			}				
			$blocks[$k] = $block_db;
		}	
		$blocks = array_values($blocks);
		?>
		<script>			
			var blocks = <?php echo json_encode( $blocks ); ?>;		
			var registerBlocks = <?php echo json_encode( $register_blocks ); ?>;
			var hooks = <?php echo json_encode( usam_get_hooks() ); ?>;			
		</script>
		<?php
	}

	public function display() 
	{		
		?>
		<div class="hertical_section">			
			<div class="hertical_section_content">			
				<usam-box id="html_blocks" :handle="false">
					<template v-slot:title>
						<?php _e('HTML блоки','usam'); ?><a @click="sidebar('blocks')"><?php _e('Добавить','usam'); ?></a>
					</template>
					<template v-slot:body>
						<html-blocks :lists="data" inline-template>
							<?php include( usam_get_filepath_admin('templates/template-parts/html-blocks.php') ); ?>
						</html-blocks>	
					</template>
				</usam-box>	
			</div>
		</div>		
		<teleport to="body">			
			<?php include( usam_get_filepath_admin('templates/template-parts/modal-panel/modal-panel-html-blocks.php') ); ?>
		</teleport>
		<?php	
		usam_vue_module('list-table');
	}	
		
}
?>
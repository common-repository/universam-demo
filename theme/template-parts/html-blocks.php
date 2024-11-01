<?php
/*
Вывод HTML блока 
Блоки загружаются через API, когда пользователь прокручивает до него
screen_loading - нанимированная загрузка
*/ 
$style = '';
if( !empty($block['style']) )
{
	foreach( $block['style'] as $k => $value )	
	{
		if( $value !== '' )
			$style .= "$k:$value;";
	}
	$style = "style='$style'";
}
$classes = "html_block html_block_{$block['code']}";
if( !empty($block['html']['classes']) )
	$classes .= ' '.$block['html']['classes'];
if(!empty($block['loading']) && $block['loading'] == 'lazy' )
	$classes .= ' js-html-blocks';

$classes_container = "html_block_container";
if( !empty($block['html']['classes_container']) )
	$classes_container .= ' '.$block['html']['classes_container'];
if( !empty($block['options']['columns']) )
	$classes_container .= ' grid_columns_'.$block['options']['columns'];
?>
<div id="html_block_<?php echo $block['id']; ?>" class="<?php echo $classes; ?>" data-id="<?php echo $block['id']; ?>" <?php echo $style; ?>>
	<div class="<?php echo $classes_container; ?>">
		<?php		
		if( !empty($block['loading']) && $block['loading'] == 'lazy' )
		{
			?>
			<div class="screen_loading">
				<div class="screen_loading__post">
					<div class="screen_loading__avatar"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
				</div>
				<div class="screen_loading__post">
					<div class="screen_loading__avatar"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
				</div>
				<div class="screen_loading__post">
					<div class="screen_loading__avatar"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
				</div>
				<div class="screen_loading__post">
					<div class="screen_loading__avatar"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
					<div class="screen_loading__line"></div>
				</div>
			</div>	
			<?php 
			//products_for_buyers
			//prodtitles
		}
		else
		{
			$file_name = usam_get_template_file_path( $block['template'].'/index' );
			if ( file_exists($file_name) )
				include($file_name);					
		}
		?>
	</div>
</div>
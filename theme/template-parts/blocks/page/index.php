<?php 
// Name: Вывод содержимого выбранной страницы
?>
<div class="html_block_page">
	<?php 
	if( !empty($block['name']) )
	{ 
		?><<?php echo $block['options']['tag_name'] ?> class='html_block__name'><?php echo $block['name'] ?></<?php echo $block['options']['tag_name'] ?>><?php
	}
	if( !empty($block['options']['description']) )
	{ 
		?><div class='html_block__description'><?php echo $block['options']['description'] ?></div><?php
	}
	if ( !empty($block['options']['page_id']) )
	{
		echo apply_filters( 'the_content', get_the_content( null, null, $block['options']['page_id'] ) );	
	}	
	?>	
</div>
<?php 
// Name: Вывод html блоков во вкладках

$main_block = $block;
$blocks = [];
foreach ($main_block['options']['ids'] as $id)
{			
	$block = usam_get_html_block( $id );
	if( !empty($block) )
	{		
		$block['tabname'] = $block['name'];
		$block['name'] = '';
		$block['description'] = '';
		ob_start();		
		include( usam_get_template_file_path( 'html-blocks', 'template-parts' ) );
		$block['html'] = ob_get_clean();
		$blocks[] = $block;
	}
}	
			
if( !empty($blocks) )
{
	if( !empty($main_block['name']) )
	{ 
		?><<?php echo $main_block['options']['tag_name'] ?> class='html_block__name'><?php echo $main_block['name'] ?></<?php echo $main_block['options']['tag_name'] ?>><?php
	}
	if( !empty($main_block['options']['description']) )
	{ 
		?><div class='html_block__description'><?php echo $main_block['options']['description'] ?></div><?php
	}
	?>		
	<div class = "usam_tabs html_block__tabs">
		<div class = "header_tab">										
			<?php
			foreach( $blocks as $item ) 
			{	
				?><a href="#htmlblock_<?php echo $item['id']; ?>" class='tab'><?php echo $item['tabname']; ?></a><?php
			}
			?>
		</div>	
		<div class = "countent_tabs">						
			<?php
			foreach( $blocks as $item ) 
			{	
				?>
				<div id="htmlblock_<?php echo $item['id']; ?>" class="tab current"><?php  echo $item['html'];  ?></div>	
				<?php
			}
			?>						
		</div>
	</div>
	<?php
}
?>

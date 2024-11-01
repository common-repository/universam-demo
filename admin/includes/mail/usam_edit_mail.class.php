<?php
class USAM_Edit_Newsletter
{	
	protected $data = [];		
	function __construct( $id )
	{				
		if ( is_array($id) )
			$this->data = $id;		
		else	
			$this->data = usam_get_newsletter( $id );
		$metas = usam_get_newsletter_metadata( $id );
		foreach( $metas as $metadata )
			$this->data[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);	
	}
	
	private function css( $args )
	{
		$css = '';
		foreach( $args as $key => $value )
			if ( $value !== '' )
				$css .= "$key:$value;";
		echo $css;				
	}
	
	private function blocks( $blocks )
	{		
		foreach( $blocks as $block )
		{
			$this->display_block( $block );
		}		
	}
		
	private function display_block( $block )
	{			
		if ( empty($block['type']) )
			return;
		if ( $block['type'] == 'columns' )
		{
			?>			
			<table style="border-width:0;padding:0;width:100%;">	
				<tr>											
					<?php
					foreach( $block['columns'] as $column )
					{
						?><td><?php $this->display_block( $column ); ?></td><?php
					}
					?>
				</tr>
			</table>
			<?php 
		}
		elseif ( $block['type'] == 'product' )
		{ 
			?>
			<a class="usam_product" style = "<?php $this->css( $block['css'] ); ?>;text-decoration: none;" href="<?php echo usam_product_url( $block['product_id'] ); ?>">
				<img src="<?php echo usam_get_product_thumbnail_src( $block['product_id'], 'thumbnail' ); ?>" style="width:160px;height:160px;">
				<div class="ptitle">								
					<div class="title" style = "<?php $this->css( $block['contentCSS'] ); ?>"><?php echo get_the_title( $block['product_id'] ); ?></div>				
					<div class="all_price">
						<span class="price" style = "<?php $this->css( $block['priceCSS'] ); ?>"><?php echo usam_get_product_price_currency( $block['product_id'], false ); ?></span>
						<span class="sale" style = "<?php $this->css( $block['oldpriceCSS'] ); ?>"><?php echo usam_get_product_price_currency( $block['product_id'], true ); ?></span>
					</div>						
				</div>
			</a>
			<?php 
		}
		elseif ( $block['type'] == 'content' )
		{
			?>
			<div class="block_text" style = "<?php $this->css( $block['css'] ); ?>">						
				<div style = "<?php $this->css( $block['contentCSS'] ); ?>"><?php echo $block['text']; ?></div>
			</div>
		<?php 
		}
		elseif ( $block['type'] == 'image' )
		{
			?>
			<div class="block_image" style = "<?php $this->css( $block['css'] ); ?>">
				<a href="<?php echo $block['url']; ?>"><img src="<?php echo $block['object_url']; ?>" style = "<?php $this->css( $block['contentCSS'] ); ?>"></a>
			</div>
			<?php 
		}
		elseif ( $block['type'] == 'divider' )
		{
			?>
			<div class="usam_divider" style = "<?php $this->css( $block['css'] ); ?>">
				<img src="<?php echo $block['src']; ?>" style = "<?php $this->css( $block['contentCSS'] ); ?>">
			</div>
			<?php 
		}
		elseif ( $block['type'] == 'button' )
		{
			?>
			<div class="block_button" style = "<?php $this->css( $block['css'] ); ?>">
				<a href="<?php echo $block['url']; ?>" style = "<?php $this->css( $block['contentCSS'] ); ?>display:inline-block;"><?php echo $block['text']; ?></a>
			</div>
		<?php 
		}
		elseif ( $block['type'] == 'indentation' )
		{
			?>
			<div class="block_indentation" style = "<?php $this->css( $block['css'] ); ?>"></div>	
		<?php	
		}
	}
	
	public function get_mailcontent()
	{	
		ob_start();	
		?>
		<!DOCTYPE HTML PUBLIC '-//W3C//DTD XHTML 1.0 Transitional //EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
		<head>
		<title></title>
		<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
		</head>
		<body>
		<table border='0' cellpadding='0' cellspacing='0' width='100%' style = "<?php echo $this->data['settings']['fon']?'background-color:'.$this->data['settings']['fon'].';':''; ?>">
			<tbody>
				<tr>
				<td align='center'>
					<table id ="usam_newsletter" border='0' cellpadding='0' cellspacing='0' width='640' style = "<?php $this->css( $this->data['settings']['css'] ); ?>overflow:hidden;">
						<tbody>
							<tr>
								<td>
									<?php $this->blocks( $this->data['settings']['blocks'] ); ?>
								</td>
							</tr>				
						</tbody>
					</table>
				</td>
				</tr>
			</tbody>
		</table>
		</body>
		</html>
		<?php
		$body = ob_get_clean();
		$body = wp_encode_emoji($body);
		return $body;
	}
	
	public function save_mailcontent()
	{
		$body = $this->get_mailcontent(); 				
		usam_update_newsletter_metadata( $this->data['id'], 'body', $body );	
	}
}
?>
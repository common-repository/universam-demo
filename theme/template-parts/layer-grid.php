<?php
/*
Шаблон вывода слоев в баннерах и слайдерах
*/
?>
<div class="layer_grid" <?php echo !empty($slide_settings['video']) ? 'id="'.$slide_settings['video'].'"' :''; ?> <?php echo !empty($slide_settings['video']) ? 'style="cursor:pointer;"':''; ?>>
	<?php			
	if (!empty($slide_settings['actions']['type']))
	{
		switch( $slide_settings['actions']['type'] ) 
		{		
			case 'link' :
				echo "<a href='".$slide_settings['actions']['value']."' class='layer_grid_action'></a>";
			break;	
			case 'webform' :
				echo "<a href='#webform_".$slide_settings['actions']['value']."' class = 'js-feedback usam_modal_feedback banner_action layer_grid_action'></a>";
			break;	
			case 'modal' : 
				echo "<a href='' class='usam_modal banner_action layer_grid_action' data-modal='".$slide_settings['actions']['value']."'></a>";
			break;
		}						
	}
	foreach( $slide_settings['layers'] as $i => $layer )		
	{ 
		if( !$layer['group'] )
		{					
			$layer_id = $side_id."-layer-$i";
			?>
			<div id="<?php echo $layer_id; ?>" class="banner_layer slide_layer <?php echo !empty($layer['classes'])?$layer['classes']:''; ?>">
				<?php
				if (!empty($layer['actions']['type']) )
				{ 
					switch( $layer['actions']['type'] ) 
					{		
						case 'link' :
							echo "<a href='".$layer['actions']['value'] ."' class='slide_layer__link'>";
						break;	
						case 'webform' :
							echo "<a href='#webform_".$layer['actions']['value'] ."' class = 'js-feedback usam_modal_feedback banner_action slide_layer__link'>";
						break;	
						case 'modal' : 
							echo "<a class='usam_modal banner_action slide_layer__link' data-modal='".$layer['actions']['value']."'>";
						break;
					}
				}	
				if( $layer['type'] !== 'group' )
				{
					if ( $layer['type'] == 'element' )
						usam_system_svg_icon($layer['element'], ["class" => "slide_layer_content"]);		
					elseif ( $layer['type'] == 'product-addtocart' )
						usam_addtocart_button( $layer['product_id'], $layer['content'], "slide_layer_content" );		
					elseif ( $layer['type'] == 'product-day-addtocart' )
					{
						$ids = usam_get_active_products_day_id_by_codeprice();
						if( $ids )
							usam_addtocart_button( $ids[0], $layer['content'], "slide_layer_content" );
					}
					else
					{
						ob_start();	
						switch( $layer['type'] ) 
						{		
							case 'image' :
								?><img src="<?php echo $layer['object_url']; ?>"><?php					
							break;						
							case 'product-title' :
								echo get_the_title( $layer['product_id'] );	
							break;
							case 'product-price' :
								echo usam_get_product_price_currency( $layer['product_id'] );	
							break;
							case 'product-oldprice' :
								echo usam_get_product_price_currency( $layer['product_id'], true );			
							break;	
							case 'product-description' :
								echo get_the_excerpt( $layer['product_id'] );
							break;
							case 'product-thumbnail' :
								?><img src="<?php echo usam_get_product_thumbnail_src( $layer['product_id'], 'full' ); ?>"><?php					
							break;							
							case 'product-day-title' :
								$ids = usam_get_active_products_day_id_by_codeprice();
								if( $ids )
									echo get_the_title( $ids[0] );	
							break;
							case 'product-day-thumbnail' :
								$ids = usam_get_active_products_day_id_by_codeprice();
								if( $ids )
								{
									?><img src="<?php echo usam_get_product_thumbnail_src( $ids[0], 'full' ); ?>"><?php
								}								
							break;							
							case 'product-day-price' :
								$ids = usam_get_active_products_day_id_by_codeprice();
								if( $ids )
									echo usam_get_product_price_currency( $ids[0], true );
							break;		
							case 'product-day-oldprice' :
								$ids = usam_get_active_products_day_id_by_codeprice();
								if( $ids )
									echo usam_get_product_price_currency( $ids[0], true );
							break;	
							case 'product-day-description' :
								$ids = usam_get_active_products_day_id_by_codeprice();
								if( $ids )
									echo get_the_excerpt( $ids[0] );
							break;			
							default:
								echo $layer['content'];
							break;					
						}
						$out = ob_get_clean();
						if( $out )
						{
							?><div class="slide_layer_content"><?php echo $out; ?></div><?php		
						}
					}
				}
				else
				{
					foreach( $slide_settings['layers'] as $j => $glayer )		
					{
						if( $glayer['group'] == $layer['id'] )
						{
							$layer_id = $side_id."-layer-$j";
							?>
							<div id="<?php echo $layer_id; ?>" class="slide_layer_group  <?php echo !empty($glayer['classes'])?$glayer['classes']:''; ?>">
								<?php
								switch( $glayer['actions']['type'] ) 
								{		
									case 'link' :
										echo "<a href='".$glayer['actions']['value'] ."' class='slide_layer__link'>";
									break;	
									case 'webform' :
										echo "<a href='#webform_".$glayer['actions']['value'] ."' class = 'js-feedback usam_modal_feedback banner_action slide_layer__link'>";
									break;	
									case 'modal' : 
										echo "<a href='' class='usam_modal banner_action slide_layer__link' data-modal='".$glayer['actions']['value'] ."'>";
									break;
								}
								switch( $glayer['type'] ) 
								{		
									case 'image' :
										?><div class="slide_layer_content"><img src="<?php echo $glayer['object_url']; ?>"></div><?php					
									break;									
									case 'element' :
										usam_system_svg_icon($glayer['element'], ["class" => "slide_layer_content"]);
									break;
									case 'product-price' :
										?><div class="slide_layer_content"><?php echo usam_get_product_price( $glayer['product_id'] ); ?></div><?php					
									break;
									case 'product-oldprice' :
										?><div class="slide_layer_content"><?php echo usam_get_product_price_currency( $glayer['product_id'], true ); ?></div><?php					
									break;
									default:
										?><div class="slide_layer_content"><?php echo $glayer['content']; ?></div><?php
									break;
								}
								if (!empty($glayer['actions']['type']) )
								{
									?></a><?php
								}
								?>
							</div>
							<?php
						}
					}
				}
				if (!empty($layer['actions']['type']) )
				{
					?></a><?php
				}
				?>							
			</div>
			<?php
		}
	}
	?>							
	</div>
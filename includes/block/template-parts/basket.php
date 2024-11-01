<?php
$title = __("Корзина", 'usam');		
?>
<div id="widget_basket" class="widget_basket widget_basket_<?php echo $attributes['basket_view']; ?>" v-if="basket!==null" v-cloak>
	<?php
	if ( $attributes['basket_view'] == 'icon' )
	{
		?>
		<a href="<?php echo \usam_get_url_system_page( 'basket' ); ?>" class="widget_basket__link">	
			<span :class="[basket.products.length?'widget_basket__icon widget_basket__icon_basket':'widget_basket__icon_basket_none']"><?php \usam_svg_icon("basket"); ?></span>
			<div class="widget_basket__text">
				<?php
				if ( !empty($attributes['signature']) )
				{
					echo "<div class='widget_basket__title' v-if='basket.products.length==0'>". $title."</div>";
				}
				?>
				<div class="widget_basket__item_count" v-html="basket.subtotal.currency" v-if='basket.products.length'></div>
			</div>						
		</a>
		<div class="widget_basket_content">				
			<?php include( \usam_get_template_file_path( 'widget-basket' ) ); ?>
		</div>	
		<?php
	}
	else
	{ 
		if ( !empty($attributes['signature']) )
		{
			echo "<div class='cart_widget_title'>". $title ."</div>";
		}
		include( \usam_get_template_file_path( 'widget-basket' ) ); 
	}		
	?>								
</div>	
<?php
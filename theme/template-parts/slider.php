<?php
/*
Описание: Для вывода слайдера
*/
?>
<div class="slides">				
	<?php	
	foreach ($slides as $slide_number => $slide)
	{		
		$slide_settings =& $slide['settings'];
		$side_id = "slider-".$slider['id']."-slide-".$slide['id'];
		$css = '';
		if ( !empty($slide['settings']['background-color']) )
			$css .= 'background-color:'.$slide['settings']['background-color'].';';	

		$class = !empty($slide['settings']['classes'])?$slide['settings']['classes']:'';		
		?>			
		<div id="<?php echo $side_id; ?>" class="slider_slide <?php echo $class; ?>" style="<?php echo $css; ?>">
			<?php
			if ( !empty($slide['settings']['filter']) )
			{
				?><div class="slide_filter filter_<?php echo $slide['settings']['filter']; ?>" style="opacity:<?php echo $slide['settings']['filter_opacity']; ?>"></div><?php
			}
			if ( !empty($slide_settings['layers']) )	
				include( usam_get_template_file_path('layer-grid', 'template-parts') );	
			?>			
			<div class="slide_image_container">
				<?php
				$class = !empty($slide['settings']['effect'])?'effect_'.$slide['settings']['effect']:'';			
				$css = '';		
				if( !empty($slide['settings']['css']) )
				{
					foreach( $slide['settings']['css'] as $name => $key )
						$css .= "$name:$key;";
				}
				if ( !empty($slide['settings']['layout-image-size']) && !empty($slide['settings']['object_size']['width']) )
					$css .= "height:auto;padding:0 0 ".$slide['settings']['object_size']['height']*100/$slide['settings']['object_size']['width'].'% 0;';
				if (!empty($slide_settings['actions']['type']))
				{
					switch( $slide_settings['actions']['type'] ) 
					{		
						case 'link' :
							echo "<a href='".$slide_settings['actions']['value']."' class='slide_slide_link'></a>";
						break;	
						case 'webform' :
							echo "<a href='#webform_".$slide_settings['actions']['value']."' class = 'js-feedback usam_modal_feedback banner_action slide_slide_link'></a>";
						break;	
						case 'modal' : 
							echo "<a href='' class='usam_modal banner_action slide_slide_link' data-modal='".$slide_settings['actions']['value']."'></a>";
						break;							
					}						
				}				
				if ( $slide['type'] == 'vimeo' )
				{
					$url_args = [];
					if( !empty($slide_settings['autoplay']) )
						$url_args[] = 'autoplay=1&loop=1&autopause=0&muted='.(!empty($slide_settings['muted'])?0:1);
					if( !empty($slide_settings['quality']) )
						$url_args[] = 'quality='.$slide_settings['quality'];
					if( empty($slide_settings['controls']) )
						$url_args[] = 'controls=0';
					$url = implode('&', $url_args);
					?>
					<div class="slide_image <?php echo $class; ?> slide_image_number_<?php echo $slide_number; ?>" style="<?php echo $css; ?>">
						<iframe src="https://player.vimeo.com/video/<?php echo $slide_settings['video_id']; ?>?<?php echo $url; ?>" width="100%" height="100%" frameborder="0" allow="<?php echo !empty($slide_settings['autoplay'])?'autoplay':''; ?>;picture-in-picture;<?php echo !empty($slide_settings['muted'])?'muted':''; ?>" allowfullscreen></iframe>
					</div>
					<?php						
				}
				elseif ( $slide['type'] == 'youtube' )
				{
					$url_args = [];
					if( !empty($slide_settings['autoplay']) )
						$url_args[] = 'autoplay=1&muted='.(!empty($slide_settings['muted'])?0:1);
					if( !empty($slide_settings['quality']) )
						$url_args[] = 'quality='.$slide_settings['quality'];
					$url = implode('&', $url_args);
					?>
					<div class="slide_image owl-lazy <?php echo $class; ?> slide_image_number_<?php echo $slide_number; ?>" style="<?php echo $css; ?>">
						<iframe src="https://www.youtube.com/embed/<?php echo $slide_settings['video_id']; ?>?<?php echo $url; ?>" width="100%" height="100%" frameborder="0" allow="autoplay=<?php echo !empty($slide_settings['autoplay'])?1:0; ?>;<?php echo !empty($slide_settings['muted'])?'muted':''; ?>;"></iframe>
					</div>					
					<?php						
				}	
				elseif ( $slide['type'] == 'video' )
				{
					?>
					<div class="slide_image <?php echo $class; ?> slide_image_number_<?php echo $slide_number; ?>" style="<?php echo $css; ?>">
						<video playsinline <?php echo !empty($slide_settings['autoplay'])?'autoplay':''; ?> loop <?php echo empty($slide_settings['muted'])?'muted':''; ?> poster="<?php echo $slide['object_url']; ?>">
							<?php if ( !empty($slide_settings['video_mp4']) ) { ?>
								<source src="<?php echo $slide_settings['video_mp4']; ?>" type="video/mp4">
							<?php } ?>
							<?php if ( !empty($slide_settings['video_webm']) ) { ?>
								<source src="<?php echo $slide_settings['video_webm']; ?>" type="video/webm">
							<?php } ?>
						</video>
					</div>					
					<?php						
				}			
				else
				{
					if ( $slide_number )
					{
						?><div class="slide_image owl-lazy <?php echo $class; ?> slide_image_number_<?php echo $slide_number; ?>" data-src="<?php echo $slide['object_url']; ?>" style="<?php echo $css; ?>"></div><?php	
					}
					else
					{
						?><div class="slide_image <?php echo $class; ?> slide_image_number_<?php echo $slide_number; ?>" style="background-image: url('<?php echo $slide['object_url']; ?>'); opacity: 1;<?php echo $css; ?>"></div><?php	
					}
				}				
				?>				
			</div> 				
		</div>
		<?php						
	} /*.slider_wide_horizon .slides .active .slides__slide_description_title {
    animation-name: slide_right_title;
    -webkit-animation-name: slide_right_title;
    animation-duration: 2s;
    -webkit-animation-duration: 2s;
    animation-timing-function: ease-in-out;
    -webkit-animation-timing-function: ease-in-out;
    visibility: visible !important; */
	?>	
</div>
<?php
if ( !empty($slider['settings']['button']['show']) )
{		
	$design = !empty($slider['settings']['button']['design']) ? $slider['settings']['button']['design'] : '';
	?>
	<div class="slider_buttons slider_buttons_<?php echo $design; ?>">
		<?php
		foreach ($slides as $key => $slide) 			
		{
			if( !$design )
			{
				?><div class="slider_buttons__button js-slider-button"></div><?php
			}
			elseif ( $design === 'description' )
			{
				?>					
				<div class="slider_buttons__button js-slider-button">						
					<div class="slider_buttons__text">
						<div class="slider_buttons__title"><?php echo $slide['title']; ?></div>
						<div class="slider_buttons__description"><?php echo $slide['description']; ?></div>
					</div>
					<img src="<?php echo $slide['object_url']; ?>">
				</div>
				<?php	
			}
			elseif ( $design === 'indicator' )
			{ 
				$key++;
				?>									
				<div class="slider_buttons_indicator__button js-slider-button">						
					<div class="slider_buttons_indicator__label">
						<div class="slider_buttons_indicator__number"><?php echo $key; ?></div>
						<div class="slider_buttons_indicator__scale"></div>
					</div>
					<div class="slider_buttons_indicator__title"><?php echo $slide['title']; ?></div>
					<div class="slider_buttons_indicator__button_mobile"></div>
				</div>
				<?php	
			}
		}
		?>	
	</div>
	<?php
}
?>
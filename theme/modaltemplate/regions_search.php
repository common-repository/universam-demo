<?php 
$location_id = usam_get_customer_location( );		
$locations = usam_get_locations(['code' => 'city', 'orderby' => 'name']);	
$col = round(count($locations)/3,0);				
?>
<div id="regions_search" class="modal fade modal-large">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Выбор региона','usam'); ?></div>
	</div>
	<div class="modal-body region_selection">
		<div class="region_selection__autocomplete">				
			<autocomplete @change="change_location" :request="'locations'" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Ваш город','usam'); ?>'" :query="{code:'city'}"></autocomplete>
		</div>
		<div class="modal-scroll letter_columns">
			<div class="letter_columns__column">	
			<?php 
			$i = 1;
			$l = '';
			$new_column = false;
			foreach ( $locations as $location ) 	
			{		
				$letter = mb_substr($location->name,0,1,'UTF-8');
				if ( $letter != $l )
				{
					$l = $letter;
					if ( $new_column )
					{
						$new_column = false;
						$i = 1;
						?>
						</div>
						<div class="letter_columns__column">			
						<?php
					}
					?>
					<div class="letter_columns__letter">
						<?php echo $letter; ?>
					</div>
					<?php 
				}
				if ( $col < $i )
					$new_column = true;
				?>
				<div class="letter_columns__item <?php echo $location->id==$location_id?'region_selection__active':''; ?>">
					<a @click="location_id='<?php echo $location->id; ?>'"><?php echo $location->name; ?></a>
				</div>
				<?php 
				$i++;			
			}
			?>
			</div>
		</div>
	</div>
</div>
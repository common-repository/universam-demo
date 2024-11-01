<script type="x-template" id="block-newsletter">
	<div class="usam_block">							
		<div class="block_controls">
			<div class="block_controls__left">
				<?php usam_system_svg_icon("delete", ["@click" => '$emit(`delete`, block)']); ?>
			</div>	
			<div class="block_controls__right">
				<?php usam_system_svg_icon("arrow", ["class" => "move_up", "@click" => '$emit(`move-up`, block)', "v-if" => "index>0"]); ?>
				<?php usam_system_svg_icon("arrow", ["class" => "move_down", "@click" => '$emit(`move-down`, block)']); ?>
			</div>	
		</div>				
		<div ref="block" class="block_columns" :style="blockCSS(block.css)" v-if="block.type=='columns'">
			<div class="select_columns" v-if="block.columns.length == 0">											
				<div class="select_columns_option">
					<?php usam_system_svg_icon("column50x50", ["@click" => "selectBlock(`50x50`)"]); ?>
					<span class="select_columns_option__label">50 / 50</span>
				</div>
				<div class="select_columns_option">
					<?php usam_system_svg_icon("column33x66", ["@click" => "selectBlock(`33x66`)"]); ?>
					<span class="select_columns_option__label">33 / 66</span>
				</div>
				<div class="select_columns_option">
					<?php usam_system_svg_icon("column66x33", ["@click" => "selectBlock(`66x33`)"]); ?>
					<span class="select_columns_option__label">66 / 33</span>
				</div>
				<div class="select_columns_option">
					<?php usam_system_svg_icon("column33x33x33", ["@click" => "selectBlock(`33x33x33`)"]); ?>
					<span class="select_columns_option__label">33 / 33 / 33</span>
				</div>
				<div class="select_columns_option">
					<?php usam_system_svg_icon("column25x50x25", ["@click" => "selectBlock(`25x50x25`)"]); ?>
					<span class="select_columns_option__label">25 / 50 / 25</span>
				</div>			
			</div>
			<table class='block_grid_products' style='position: relative;width:100%;table-layout: fixed;' v-else>	
				<tr>											
					<td v-for="(column, i) in block.columns">
						<block-newsletter :block="column" v-if="Object.keys(column).length" @delete="column={}"></block-newsletter>
						<div v-else class="block_placeholder" @drop="dropWidget(i)" @dragover="allowDrop"><?php _e('Вставьте блок здесь', 'usam') ?></div>
					</td>
				</tr>
			</table>
		</div>
		<div ref="block" class="usam_product" :style="blockCSS(block.css)" v-if="block.type=='product'" @click="$root.$emit(`active`, block)">
			<img v-if="block.image.url" :src="block.image.url" :height="block.image.height" :width="block.image.width">
			<img v-else src="<?php echo usam_get_no_image_uploaded_file( 'small-product-thumbnail' ) ?>" :height="block.image.height" :width="block.image.width">
			<div class="ptitle">								
				<div class="title" :style="blockCSS(block.contentCSS)" v-html="block.text"></div>				
				<div class="all_price">
					<span class="price" :style="blockCSS(block.priceCSS)" v-html="block.price_currency"></span>
					<span class="sale" :style="blockCSS(block.oldpriceCSS)" v-html="block.oldprice_currency"></span>
				</div>						
			</div>
		</div>
		<div ref="block" class="block_text" :style="blockCSS(block.css)" v-if="block.type=='content'" @click="$root.$emit(`active`, block)">			
			<div v-show="edit">
				<tinymce v-model="block.text"></tinymce>
			</div>
			<div class="" v-show="!edit" @click="edit=!edit" v-html="block.text" :style="blockCSS(block.contentCSS)"></div>
		</div>
		<div ref="block" class="block_image" :style="blockCSS(block.css)" v-if="block.type=='image'" @click="$root.$emit(`active`, block)">
			<img :src="block.object_url" :style="blockCSS(block.contentCSS)">
		</div>
		<div ref="block" class="usam_divider" :style="blockCSS(block.css)" v-if="block.type=='divider'" @click="$root.$emit(`active`, block)">
			<img :src="block.src" :style="blockCSS(block.contentCSS)">
		</div>
		<div ref="block" class="block_button" :style="blockCSS(block.css)" v-if="block.type=='button'" @click="$root.$emit(`active`, block)">
			<span :href="block.url" :style="blockCSS(block.contentCSS)+'display:inline-block;'" v-html="block.text"></span>
		</div>
		<div ref="block" class="block_indentation usam_indentation" :style="blockCSS(block.css)" v-if="block.type=='indentation'" @click="$root.$emit(`active`, block)">
			<div class="usam_resize_handle" @mousedown="mousedown">
				<span class="usam_resize_handle_text">{{block.css.height}}</span>
				<span class="usam_resize_handle_icon"><span class="usam_plus"></span><span class="usam_minus"></span></span>
			</div>
		</div>
	</div>	
</script>
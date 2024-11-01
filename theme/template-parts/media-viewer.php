<div id="media-viewer" class="full-gallery" :class="{'is-active':open}">	
	<div class="full-gallery__topbar">
		<div class="full-gallery__topbar_title" v-html="title"></div>
		<div class="full-gallery__topbar_buttons">
			<?php usam_svg_icon("fullscreen", ["@click" => "fullScreenChange", "v-if" => "!fullScreen"]); ?>
			<?php usam_svg_icon("fullscreen-exit", ["@click" => "fullScreenChange", "v-if" => "fullScreen"]); ?>
			<?php usam_svg_icon("close", ["class" => "full-gallery__topbar_buttons_close", "@click" => "open=!open"]); ?>	
		</div>		
	</div>
	<site-slider :items='images' :number='image_key' :amount="1"  :classes="'full-gallery__image'">
		<template v-slot:body="sliderProps">
			<div class="full-gallery__nav full-gallery__nav_prev" @click="sliderProps.prev" v-if="sliderProps.n!=0"><?php usam_svg_icon("chevron_left"); ?></div>
			<div class="full-gallery__nav full-gallery__nav_next" @click="sliderProps.next" v-if="sliderProps.n!=sliderProps.items.length-1"><?php usam_svg_icon("chevron_right"); ?></div>
			<div class='full-gallery__image_zoom' v-for="(image, i) in sliderProps.items" v-if="sliderProps.n==i" @click="sliderProps.changeZoom">
				<image-zoom :img-normal="image.full" :disabled="!sliderProps.zoom" @change="sliderProps.enable=!$event"></image-zoom>
			</div>
		</template>
	</site-slider>
	<site-slider :items="images" :number="image_key" :classes="'full-gallery__small_images'">
		<template v-slot:body="sliderProps">
			<div class='full-gallery__small_image' v-for="(image, i) in sliderProps.items" :class="{'active':sliderProps.n==i}">	
				<img :src='image.small' @click="image_key=i">		
			</div>	
		</template>
	</site-slider>
</div>
<div class="screen_size_editor type_editor">
	<div id="slider_editor" @mouseup="handleUp" @mousemove="mousemove" :style="screenSizeCSS">
		<div id="ruler_hor_marker" style="display: block; height: 15px;"></div>
		<div id="ruler_ver_marker" style="display: block; width: 15px;"></div>
		<div class="ruler_top"><canvas id="ruler_top_offset" width="3600" height="15" :style="'transform:translate('+rulerTop+'px, 0px)'"></canvas></div>
		<div class="ruler_left"><canvas id="ruler_left_offset" width="15" height="3600" style="top: -1200px; transform: translate(0px, 0px);"></canvas></div>			
		<div class="slides" ref="slides" @contextmenu="openMenu">
			<div ref="menu" class="menu_content menu_content_left">			
				<div class="menu_items">	
					<div class="menu_items__item" @click="pasteLayer" :class="[copy!==null?'active':'disabled']"><?php _e('Вставить','usam'); ?></div>
				</div>			
			</div>
			<div ref="menu-layer" class="menu_content menu_content_left">			
				<div class="menu_items">	
					<div class="menu_items__item" @click="copyLayer(menuLayer)" :class="{'active':copy===null}"><?php _e('Копировать','usam'); ?></div>
					<div class="menu_items__item" @click="pasteLayer" :class="[copy!==null?'active':'disabled']"><?php _e('Вставить','usam'); ?></div>
					<div class="menu_items__item" @click="duplicateLayer($event, menuLayer)"><?php _e('Дублировать','usam'); ?></div>						
					<div class="menu_items__item" @click="deleteLayer(menuLayer)"><?php _e('Удалить','usam'); ?></div>
				</div>			
			</div>
			<div class="slide" :style="slideFonCSS" @mouseenter="editorFocus=true" @mouseleave="editorFocus=false">
				<div class="slide_filter" v-if="slide.settings.filter" :class="'filter_'+slide.settings.filter" :style="'opacity:'+slide.settings.filter_opacity"></div>					
				<?php require_once( USAM_FILE_PATH.'/admin/templates/template-parts/display-layers-editor.php' ); ?>
				<div class ="video_editor slide_image_container" :style="slideContainerCSS" v-if="slide.type=='vimeo'">
					<div class="slide_image" v-if="slide.object_url" :style="slideCSS" :class="slideAnimation"></div>
					<div class ="video_slide" v-else-if="slide.settings.video_id">			
						<iframe :src="'https://player.vimeo.com/video/'+slide.settings.video_id" width="100%" height="100%" frameborder="0" :allow="slide.settings.autoplay?'autoplay':''+'picture-in-picture'" allowfullscreen></iframe>
					</div>						
				</div>
				<div class ="video_editor slide_image_container" :style="slideContainerCSS" v-else-if="slide.type=='youtube'">
					<div class="slide_image" v-if="slide.object_url" :style="slideCSS" :class="slideAnimation"></div>
					<div class ="video_slide" v-else-if="slide.settings.video_id">		
						<iframe :src="'https://www.youtube.com/embed/'+slide.settings.video_id" width="100%" height="100%" frameborder="0" :allow="'autoplay='+slide.settings.autoplay?1:0+'&mute=1&autohide=1&rel=0'"></iframe>		
					</div>						
				</div>	
				<div class ="video_editor slide_image_container" :style="slideContainerCSS" v-else-if="slide.type=='video'">					
					<div class ="video_slide">		
						<video playsinline="" muted="" autoplay="" loop="" :poster="slide.object_url">
							<source :src="slide.settings.video_mp4" v-if="slide.settings.video_mp4" type="video/mp4">
							<source :src="slide.settings.video_webm" v-if="slide.settings.video_webm" type="video/webm">
						</video>
					</div>						
				</div>					
				<div class="slide_image_container" :style="slideContainerCSS" v-else>
					<div class="slide_image" :style="slideCSS" :class="slideAnimation"></div>
				</div>										
			</div>
		</div>	
	</div>
</div> 	
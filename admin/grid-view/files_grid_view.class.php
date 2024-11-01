<?php
require_once( USAM_FILE_PATH . '/admin/includes/grid_view.class.php' );
class USAM_Files_Grid_View extends USAM_Grid_View
{		
	public function breadcrumbs() 
	{ 
		?>
		<span @click="goo_folder(0)" class="breadcrumb" @drop="drop($event, 0)" @dragover="allowDrop"><?php echo usam_get_icon( 'empty_folder', 20 ); ?><span class="breadcrumb__name"><?php _e("Все файлы","usam"); ?></span></span>
		<span class="breadcrumb" v-if="trash"><span class="dashicons dashicons-controls-play"></span><?php _e("Корзина","usam"); ?></span>
		<span @click="goo_folder(breadcrumb.id)" v-for="(breadcrumb, k) in breadcrumbs" class="breadcrumb" v-if="trash==false" @drop="drop($event, breadcrumb.id)" @dragover="allowDrop">
			<span class="dashicons dashicons-controls-play"></span>
			<span class="breadcrumb__name">{{breadcrumb.name}}</span>
		</span>
		<?php 
	}
		
	public function display_grid() 
	{  
		?>
		<div class="counter_panel">	
			<div class="counter_panel__breadcrumbs"><?php $this->breadcrumbs(); ?></div>
			<div class="counter_panel__values"><span class="counter_panel__total_items"><?php _e('Всего','usam'); ?>: {{total_items}} <?php _e('элементов','usam'); ?></span></div>				
		</div>
		<div class="js-file-drop" @drop="fileDrop" @dragover="allowAddingFiles">	
			<div class="grid_view_icons">			
				<?php $this->display_grid_items( ); ?>						
			</div>	
		</div>		
		<?php 
	}
	
	public function display_grid_items() 
	{  
		?>
		<div class="grid_item grid_item_folder" :class="{'open_folder':folder.status=='open', 'grid_item_checked':folder.checked}" v-for="(folder, k) in folders" draggable='true' @drop="drop($event, folder.id)" @dragover="allowDrop" @dragstart="dragFolder($event, k)" @contextmenu="open_folder_menu($event, k)" @dragend="dragEnd($event, k)">
			<div class="grid_item__icon" @click="open_folder(k, $event)">
				<?php usam_system_svg_icon("folder", ["v-if" => "folder.count"]); ?>
				<?php usam_system_svg_icon("empty_folder", ["v-else" => ""]); ?>
			</div>
			<div class="grid_item__name">
				<span :ref="'foldername'+folder.id" href='#' @click="folderClick( k, $event )">{{folder.name}}</span>
				<input :ref="'foldername_editor'+folder.id" type="text" v-model="folder.name" v-on:keyup.enter="folderSave(k)" @blur="folderSave(k)" hidden />				
			</div>					
		</div>
		<div class="grid_item grid_item_file" :class="{'open_file':file.status=='open', 'grid_item_checked':file.checked}" v-for="(file, k) in files" draggable='true' @dragstart="dragFile($event, k)" @contextmenu="open_file_menu($event, k)" @dragend="dragEnd($event, k)">
			<progress-circle v-show="file.load" :percent="file.percent"></progress-circle>
			<div class="grid_item__icon" v-show="!file.load" @click="open_file(k, $event)">
				<img v-if="file.lzy" :src="file.lzy" width = "120" height="120" loading='lazy'/>
				<img v-else :src="file.icon" width = "120" height="120"/>
			</div>
			<div class="grid_item__name">
				<span :ref="'filename'+file.id" class='filename' @click="fileClick(k, $event)">{{file.title}}</span>
				<input :ref="'filename_editor'+file.id" type="text" v-model="file.title" v-on:keyup.enter="fileSave(k)" @blur="fileSave(k)" hidden />
			</div>					
		</div>
		<div class="menu_content menu_content_left folder_menu" v-show="folder_menu!==false">
			<div class="menu_items" v-if="trash">	
				<div class="menu_items__item" @click="restoreFolder"><?php _e('Восстановить','usam'); ?></div>
			</div>
			<div class="menu_items" v-else>		
				<div class="menu_items__item" @click="downloadFolder"><?php _e('Скачать','usam'); ?></div>
				<div class="menu_items__item" @click="renameFolder"><?php _e('Переименовать','usam'); ?></div>
				<div class="menu_items__item" @click="folderDelete"><?php _e('Удалить','usam'); ?></div>				
			</div>
		</div>			
		<div class="menu_content menu_content_left file_menu" v-show="file_menu!==false">
			<div class="menu_items" v-if="trash">	
				<div class="menu_items__item" @click="restoreFile"><?php _e('Восстановить','usam'); ?></div>
			</div>
			<div class="menu_items" v-else>	
				<div class="menu_items__item" @click="fileDownload"><?php _e('Скачать','usam'); ?></div>
				<div class="menu_items__item" @click="fileSend"><?php _e('Отправить почтой','usam'); ?></div>
				<div class="menu_items__item" @click="renameFile"><?php _e('Переименовать','usam'); ?></div>
				<div class="menu_items__item" @click="fileDelete"><?php _e('Удалить','usam'); ?></div>	
			</div>			
		</div>		
		<div class="trash" v-if="trash==false"><a href='#' @click="open_basket()"><img src='<?php echo USAM_SVG_ICON_URL."#trash-usage"; ?>' width = '60' height='60'/></a></div>
		<input type='file' @change="fileChange" hidden />
		<teleport to="body">
			<modal-panel ref="modalsendemail" :size="'85%'" :backdrop="true">
				<template v-slot:title><?php _e('Написать письмо', 'usam'); ?></template>
				<template v-slot:body="modalProps">
					<send-email :object_id="object_id" :object_type="object_type" :attachments="attachments" @add="addEmail" inline-template>
						<?php include( usam_get_filepath_admin('templates/template-parts/table_send_email.php') ); ?>
					</send-email>
				</template>
			</modal-panel>
		</teleport>
		<?php 
	}
}
?>
<?php 
if ( !current_user_can('edit_pages') || !current_user_can('edit_posts') ) 
	wp_die( __('Вы должны иметь разрешение, чтобы сделать это!', 'usam') );

//global $current_screen;
//if ( empty( $current_screen ) )
//	set_current_screen();
?>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php _e('Добавить шорткод', 'usam'); ?></title>
		<?php		
	
		do_action('admin_enqueue_scripts');		
		do_action('admin_print_styles');	
		do_action('admin_print_scripts');	
		do_action('admin_head');

		wp_enqueue_script( 'jquery-ui-autocomplete' );	
		
		wp_enqueue_style( 'buttons' );	
		wp_enqueue_style( 'forms' );			
		?>			
		<script src="<?php echo includes_url(); ?>js/tinymce/tiny_mce_popup.js"></script>
		<script src="<?php echo includes_url(); ?>js/tinymce/utils/mctabs.js"></script>
		<script src="<?php echo includes_url(); ?>js/tinymce/utils/form_utils.js"></script>		
		<base target="_self" />
		<style type='text/css'>
			body{font-family: "Times New Roman" !important;}
			.usam_tabs_style1 .header_tab .tab {width: 90px!important;}	
			label{width: 100px}
			table{width: 100%}	
			h3{font-size: 16px;}
			table td{font-size: 16px !important; padding: 10px}			
			div.current{overflow-y: auto !important; height: 390px !important;}					
			.description{color:grey	!important;	font-style: italic !important; font-size: 12px !important;}	
			.countent_tabs #slider a{color: blue	!important;}			
			.shortcode{border: 1px solid #919B9C; padding: 10px}
			
			.mceActionPanel{margin-top:20px}			
		</style>
		<script>		
		function insert_shortcode() 
		{
			var shortcode;
			jQuery('#usam_scortcode input[type="checkbox"]').each(function () 
			{				
				if( jQuery(this).prop('checked') )
				{	
					shortcode = jQuery(this).data('shortcode'); 
					tinyMCEPopup.editor.execCommand('mceInsertContent', false, shortcode);
				}			
			});	        
			tinyMCEPopup.close();
		}	
		</script>	
	</head>
	<body id="link" >
		<div class = "wp-core-ui" >
			<div id = "usam_scortcode" class = "usam_tabs usam_tabs_style1" >
				<div class = "header_tab">
					<a class="current tab" href="#subscriber"><?php _e("Подписчик", 'usam'); ?></a>			
					<a class = "tab" href="#order"><?php _e("Заказ", 'usam'); ?></a>					
					<a class = "tab" href="#product"><?php _e("Товары", 'usam'); ?></a>
					<a class = "tab" href="#webform"><?php _e("Веб-форма", 'usam'); ?></a>
				</div>	
				<div class = "countent_tabs">	
					<div id = "subscriber" class = "tab">						
						<h3><?php _e("Подписчик", 'usam'); ?></h3>
						<table border="0" cellpadding="4" cellspacing="0">									
							<?php
							$shortcode = array( 'name' => __('Обращение', 'usam') );
							foreach ( $shortcode as $key => $name ) 
							{								
								?>
								<tr valign="top">
									<td>
										<input type="checkbox" value="1" id="<?php echo $key; ?>" data-shortcode="%<?php echo $key; ?>%"/>		
										<label for="<?php echo $key; ?>"><?php echo $name; ?></label>
									</td>
								</tr>							
								<?php
							}
							?>	
						</table>
						<h3><?php _e("Контакт", 'usam'); ?></h3>
						<table border="0" cellpadding="4" cellspacing="0">									
							<?php
							$shortcode = array( 'lastname' => __('Имя', 'usam'), 'firstname' => __('Фамилия', 'usam'), 'patronymic' => __('Отчество', 'usam'));
							foreach ( $shortcode as $key => $name ) 
							{								
								?>
								<tr valign="top">
									<td>
										<input type="checkbox" value="1" id="<?php echo $key; ?>" data-shortcode="%<?php echo $key; ?>%"/>		
										<label for="<?php echo $key; ?>"><?php echo $name; ?></label>
									</td>
								</tr>							
								<?php
							}
							?>	
						</table>
						<h3><?php _e("Компания", 'usam'); ?></h3>
						<table border="0" cellpadding="4" cellspacing="0">									
							<?php
							$shortcode = array( 'company_name' => __('Название', 'usam') );
							foreach ( $shortcode as $key => $name ) 
							{
								?>
								<tr valign="top">
									<td>
										<input type="checkbox" value="1" id="<?php echo $key; ?>" data-shortcode="%<?php echo $key; ?>%"/>		
										<label for="<?php echo $key; ?>"><?php echo $name; ?></label>
									</td>
								</tr>							
								<?php
							}
							?>	
						</table>
					</div>					
					<div id = "order" class = "tab">						
						<table border="0" cellpadding="4" cellspacing="0">					
							<?php
							$shortcode = usam_get_order_shortcode();
							foreach ( $shortcode as $key => $name ) 
							{
								$id = 'shortcode-'.str_replace('%', '', $key);
								?>
								<tr valign="top">
									<td>
										<input type="checkbox" value="1" id="<?php echo $id; ?>" data-shortcode="<?php echo $key; ?>"/>		
										<label for="<?php echo $id; ?>"><?php echo $name; ?></label>
									</td>
								</tr>							
								<?php
							}
							?>
						</table>
					</div>		
					<div id = "product" class = "tab">						
						<table border="0" cellpadding="4" cellspacing="0">					
							<?php
							$shortcode = array( 'products' => __('Список товаров', 'usam') );
							foreach ( $shortcode as $key => $name ) 
							{
								?>
								<tr valign="top">
									<td>
										<input type="checkbox" value="1" id="<?php echo $key; ?>" data-shortcode="%<?php echo $key; ?>%"/>		
										<label for="<?php echo $key; ?>"><?php echo $name; ?></label>
									</td>
								</tr>							
								<?php
							}
							?>	
						</table>
					</div>		
					<div id = "webform" class = "tab">						
						<table border="0" cellpadding="4" cellspacing="0">					
							<?php
							$shortcode = array( 'webform_customer' => __('Имя клиента', 'usam'), 'webform_company' => __('Имя компании', 'usam'),'webform_phone' => __('Телефон', 'usam'),'webform_address' => __('Адрес', 'usam'),'webform_mail' => __('Электронная почта', 'usam') );
							foreach ( $shortcode as $key => $name ) 
							{
								?>
								<tr valign="top">
									<td>
										<input type="checkbox" value="1" id="<?php echo $key; ?>" data-shortcode="%<?php echo $key; ?>%"/>		
										<label for="<?php echo $key; ?>"><?php echo $name; ?></label>
									</td>
								</tr>							
								<?php
							}
							?>	
						</table>
					</div>							
				</div>	
			</div>		
		<div class="mceActionPanel">
			<div style="float: left">
				<input type="button" id="cancel_button" class="secondary button" onclick="tinyMCEPopup.close();" value="<?php _e("Отмена", 'usam'); ?>"/>
			</div>			
			<div style="float: right">
				<input type="button" id="insert_button" class="button-primary button" onclick="insert_shortcode();" value="<?php _e("Вставить", 'usam'); ?>"/>
			</div>
		</div>	
		<?php	
		do_action('admin_print_footer_scripts');	
		?>
		</div>
	</body>	
</html>
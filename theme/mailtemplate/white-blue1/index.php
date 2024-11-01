<?php
/*
Theme Name:mail
Author:universam
Version:1.0
*/
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
<title></title>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
</head>
<?php
$style_footer_a = 'style = "color: #eee; text-decoration: none; font-weight: bold; font-size:12px"';
?>
<body>
<table id='usam_newsletter_fon' border='0' cellpadding='0' cellspacing='0' width='100%' style = "background-color: #dedede;">
<tbody>
	<tr>
	<td align='center'>
	<table id ="usam_newsletter" style='margin:20px 10px;' border='0' cellpadding='0' cellspacing='0' width='640' style="table-layout: fixed;">
		<tbody>			
			<tr><td style="height:30px; border-radius:6px 6px 0px 0px; -moz-border-radius:6px 6px 0px 0px; -webkit-border-radius:6px 6px 0px 0px; -webkit-font-smoothing:antialiased; background-color:#2C3547; color:#ededed;"></td></tr>			
			<tr id='usam_mailtemplate_content' class='usam_newsletter_background' style="background-color: #ffffff;"><td style = "padding:30px 20px">%mailcontent%</td></tr>			
			<tr>
				<td>
					<table id='footer' border='0' cellpadding='0' cellspacing='0' width='100%' style = "background-color:#2C3547; color:#ededed; border-radius:0px 0px 6px 6px; padding:20px;">
						<tbody>												
							<tr>
								<td class='w20' width='20'></td>
								<td class='w600' valign='top' width='360'>	
									<a <?php echo $style_footer_a; ?> href='<?php echo usam_get_url_system_page('products-list'); ?>'><?php echo __('Каталог','usam'); ?></a><br/>
									<a <?php echo $style_footer_a; ?> href='<?php echo usam_get_url_system_page('sale'); ?>'><?php echo __('Распродажи','usam'); ?></a>						
								</td>
								<td class='w0' width='60'></td>
								<td class='w0' valign='top' width='160'>
									<a <?php echo $style_footer_a; ?> href='<?php echo usam_get_url_system_page('your-account'); ?>'><?php echo __('Личный кабинет','usam'); ?></a><br/>
									<a <?php echo $style_footer_a; ?> href='<?php echo usam_get_url_system_page('reviews'); ?>'><?php echo __('Отзывы','usam'); ?></a>
								</td>
							</tr>							
						</tbody>
					</table>
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
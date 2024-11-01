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
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
</head>
<?php
$style_footer_a = 'style = "color: #ffffff; text-decoration: none; font-weight: bold; font-size:12px"';
?>
<body>
<table id='usam_newsletter_fon' border='0' cellpadding='0' cellspacing='0' width='100%' style = "background-color:#dedede;">
<tbody>
	<tr>
	<td align='center'>
	<table id ="usam_newsletter" style='margin:20px 10px;' border='0' cellpadding='0' cellspacing='0' width='640' style="table-layout:fixed;">
		<tbody>			
			<tr>
				<td id='header' align='center' width='640' style="background-color: #2C3547; padding:20px; border-radius:6px 6px 0px 0px;">
					<p style = "margin:0; color: #ffffff; font-family: 'Georgia', Arial, sans-serif; text-align: center;">
						<a href='<?php echo home_url(); ?>' style = "font-size:36px;font-weight:700;text-transform:uppercase;color:#ffffff;text-decoration: none;"><singleline label='Title'><?php echo get_bloginfo('name'); ?></singleline></a>
					</p>
					<p style = "font-size:16px; font-style:italic; margin:0; color: #ffffff; font-family: 'Georgia', Arial, sans-serif; text-align: center;"><?php echo get_bloginfo('description'); ?></p>
				</td>
			</tr>			
			<tr id='usam_mailtemplate_content' class='usam_newsletter_background' style="background-color: #ffffff;"><td style = "padding:30px 20px">%mailcontent%</td></tr>			
			<tr><td style="height:30px; border-radius:0px 0px 6px 6px; -moz-border-radius:0px 0px 6px 6px; -webkit-border-radius:0px 0px 6px 6px; -webkit-font-smoothing:antialiased; background-color:#2C3547; color:#ededed;"></td></tr>			
		</tbody>
	</table>
	</td>
	</tr>
</tbody>
</table>
</body>
</html>
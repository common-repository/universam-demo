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
<table id='usam_newsletter_fon' border='0' cellpadding='0' cellspacing='0' width='100%' style = "background-color: #dedede;">
<tbody>
	<tr>
	<td align='center'>
	<table id ="usam_newsletter" style='margin:0 10px;' border='0' cellpadding='0' cellspacing='0' width='640' style="table-layout: fixed;">
		<tbody>
			<tr>
				<td class='w640' height='20' width='640'></td>
			</tr>
			<tr>
				<td style="height:30px; border-radius:6px 6px 0px 0px; -moz-border-radius:6px 6px 0px 0px; -webkit-border-radius:6px 6px 0px 0px; -webkit-font-smoothing:antialiased; background-color:#2C3547; color:#ededed;"></td>
			</tr>			
			<tr id='usam_mailtemplate_content' class='usam_newsletter_background' style = "background-color: #ffffff;">				
				<td style = "padding:30px 0">
					<table class='w640' border='0' cellpadding='0' cellspacing='0' width='640'><tbody><tr><td class='w20' width='20'></td><td class='w600' width='600'>%mailcontent%</td><td class='w20' width='20'></td></tr></tbody>
					</table>
				</td>
			</tr>			
			<tr>
				<td style="height:30px; border-radius:0px 0px 6px 6px; -moz-border-radius:0px 0px 6px 6px; -webkit-border-radius:0px 0px 6px 6px; -webkit-font-smoothing:antialiased; background-color:#2C3547; color:#ededed;"></td>
			</tr>
			<tr>
				<td class='w640' height='20' width='640'></td>
			</tr>
		</tbody>
	</table>
	</td>
	</tr>
</tbody>
</table>
</body>
</html>
<?php
/*
Theme Name:mail
Author:universam
Version:1.0
*/
?>
<!DOCTYPE HTML PUBLIC '-//W3C//DTD XHTML 1.0 Transitional //EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
<title></title>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
</head>
<body>
<table id='usam_newsletter_fon' border='0' cellpadding='0' cellspacing='0' width='100%' style = "background-color: #e5e5e5;">
<tbody>
	<tr>
	<td align='center'>
	<table id ="usam_newsletter" style='margin:0 10px;' border='0' cellpadding='0' cellspacing='0' width='640' style="table-layout: fixed;">
		<tbody>
			<tr>
				<td class='w640' height='20' width='640'></td>
			</tr>
			<tr>
				<td style="height:30px; border-radius:6px 6px 0px 0px; background-color:#ffcc00;"></td>
			</tr>
			<tr style="background-color:#ffcc00;">			
				<td class='w600' width='600' align='center'>
					<strong style = "margin:0; color: #ffffff; font-family: 'Georgia', Arial, sans-serif; text-align: center;">
						<a href='<?php echo home_url(); ?>' style = "font-size: 36px; text-transform:uppercase; color: #000000; text-decoration: none;"><singleline label='Title'><?php echo get_bloginfo('name'); ?></singleline></a>
					</strong>
					<p style = "font-size:16px; font-style:italic; margin:0; color: #000000; font-family: 'Georgia', Arial, sans-serif; text-align: center;"><?php echo get_bloginfo('description'); ?></p>
				</td>				
			</tr>
			<tr style="background-color:#ffcc00; height:20px;">		
				<td class='w20' width='20'></td>
			</tr>
			<tr>
				<td style="height:2px; background-color:#000000; color:#ededed;"></td>
			</tr>			
			<tr id='usam_mailtemplate_content' class='usam_newsletter_background' style = "background-color: #ffffff;">				
				<td style = "padding:30px 0">
					<table class='w640' border='0' cellpadding='0' cellspacing='0' width='640'><tbody><tr><td class='w20' width='20'></td><td class='w600' width='600'>%mailcontent%</td><td class='w20' width='20'></td></tr></tbody>
					</table>
				</td>
			</tr>	
			<tr>
				<td style="height:2px; background-color:#000000; color:#ededed;"></td>
			</tr>				
			<tr>
				<td>
					<table id='footer' class='w640' border='0' cellpadding='0' cellspacing='0' width='640' style = "background-color: #ffcc00; color: #ededed; border-radius:0px 0px 6px 6px;">
						<tbody>
							<tr>
								<td class='w20' width='20'></td>
								<td class='w600 h0' height='30' width='360'></td>
								<td class='w0' width='60'></td>
								<td class='w0' width='160'></td>
								<td class='w20' width='20'></td>
							</tr>												
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td class='w640' height='60' width='640'></td>
			</tr>
		</tbody>
	</table>
	</td>
	</tr>
</tbody>
</table>
</body>
</html>
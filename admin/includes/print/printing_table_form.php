<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>		
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php echo $title_page; ?></title>
	<style>
		<?php $print_list_table->get_print_page(); ?>		
		body {font-family:"Helvetica Neue", Helvetica, Arial, Verdana, sans-serif;}
		h1 {font-size:14px;}
		h2 {color: #333; font-size:14px;}
		*(font-size:10px;)
		#wrapper {margin:0 auto; width:95%;}
		#header {	}
		#customer {	overflow:hidden;}
		#customer .shipping, #customer .billing {float: left; width: 50%;}
		table {border:1px solid #000; border-collapse:collapse;	margin-top:10px; width:100%;	}
		th {background-color:#efefef; text-align:center;}
		th, td { padding:5px; font-size:10px;}
		th,td {text-align:left;}
		.column-name, .column-title, .column-description{text-align:left;}
		#print-items td.amount {text-align:right; }
		td, tbody th { border-top:1px solid #ccc; }
		th.column-total { width:90px;}
		th.column-shipping { width:120px;}
		th.column-price { width:100px;}
		tfoot{background-color:#efefef;}
	</style>
</head>
<body onload="window.print()">
	<div id="wrapper">
		<div id="header">
			<h1><?php echo $title_page; ?></h1>
		</div>		
		<?php $print_list_table->display(); ?>
	</div>
</body>
</html>
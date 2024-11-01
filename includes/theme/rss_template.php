<?php echo "<?xml version='1.0'?>";?>
<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom' xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title><![CDATA[<?php bloginfo('name'); ?> - <?php echo single_cat_title(); ?>]]></title>
        <link><![CDATA[<?php echo usam_this_page_url(); ?>]]></link>
        <description></description>
        <generator>Universam</generator>
        <atom:link href='<?php echo usam_this_page_url(); ?>' rel='self' type='application/rss+xml' />
<?php while (usam_have_products()) :  usam_the_product(); ?>
          <item>
            <title><![CDATA[<?php echo usam_the_product_title(); ?>]]></title>
            <link><![CDATA[<?php echo usam_product_url(); ?>]]></link>
            <description><![CDATA[<?php echo usam_category_description(); ?>]]></description>
            <pubDate><![CDATA[<?php echo the_date('D, d M Y H:i:s +0000'); ?>]]></pubDate>
            <guid><![CDATA[<?php echo usam_product_url(); ?>]]></guid>
            <g:price><![CDATA[<?php echo usam_get_product_price_currency( ); ?>]]></g:price>
            <g:image_link><![CDATA[<?php echo usam_get_product_thumbnail_src(); ?>]]></g:image_link>
          </item>          
<?php endwhile; ?>
      </channel>
    </rss>
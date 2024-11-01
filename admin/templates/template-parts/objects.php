<a class='user_block' :href="'<?php echo add_query_arg(['page' => 'crm','form' => 'view','form' => 'view', 'form_name' => 'company'], remove_query_arg(['id','form_name'])); ?>&id='+item.id" v-if="item.object_type=='company'">	
	<div class='image_container usam_foto'><img :src='item.logo'></div>
	<div class='user_name' v-html="item.name"></div>					
</a>
<a class='user_block' :href="'<?php echo add_query_arg(['page' => 'crm', 'form' => 'view', 'form_name' => 'contact'], remove_query_arg(['id','form_name'])); ?>&id='+item.id" v-else-if="item.object_type=='contact'">	
	<div class='image_container usam_foto'><img :src='item.foto'></div>
	<div class='user_name' v-html="item.appeal"></div>						
</a>
<div class='user_block' v-else-if="item.object_type=='product'">	
	<a class='image_container usam_foto' :href="'<?php echo admin_url('/wp-admin/post.php?action=edit'); ?>&post='+item.ID"><img :src='item.medium_image'></a>
	<div class='product'>
		<a class='product_name' v-html="item.post_title" :href="'<?php echo admin_url('/wp-admin/post.php?action=edit'); ?>&post='+item.ID"></a>
		<div class="product_sku"><?php _e('Артикул', 'usam'); ?>: <span class="js-copy-clipboard">{{item.sku}}</span></div>
	</div>		
</div>
<div class='user_block' v-else-if="item.object_type=='page'">		
	<a class='product_name' v-html="item.post_title" :href="item.url"></a>
</div>
<div class='user_block' v-else-if="item.object_type=='review'">	
	<div class='document_name'>
		<div class='document_number'>№ {{item.id}}</div>
	</div>
	<div class='document_date'><?php _e( 'от', 'usam'); ?> {{localDate(item.date_insert,'d.m.Y')}}</div>
</div>
<a class='document_block' :href="'<?php echo add_query_arg(['form' => 'view'], remove_query_arg(['id','form_name'])); ?>&id='+item.id+'&form_name='+item.object_type" v-else>						
	<div class='document_name'>
		<div class='document_number'>№ {{item.number}}</div>
		<div class='document_totalprice' v-html="item.totalprice_currency"></div>	
	</div>
	<div class='document_date'><?php _e( 'от', 'usam'); ?> {{localDate(item.date_insert,'d.m.Y')}}</div>							
</a>
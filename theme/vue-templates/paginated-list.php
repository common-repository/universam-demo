<script type="x-template" id="paginated-list">
	<div ref="pagination" class ="pagination" v-show="pageCount>1">
		<span class="pagination__item page-prev" title="<?php _e('Предыдущая страница','usam'); ?>" v-if="page>1" @click="prevPage">&lt;</span>
		<span class="pagination__item pagination__number" v-if="page>3" @click="page=1">1</span>
		<span class="pagination__item pagination__points" v-if="page>4">...</span>	
		<span class="pagination__item pagination__number" v-for="p in paginatedData" :class="{'current':page==p}" @click="page=p">{{p}}</span>
		<span class="pagination__item pagination__points" v-if="pageCount>3 && pageCount-3 > page">...</span>		
		<span class="pagination__item pagination__number" v-if="pageCount>1" @click="page=pageCount" :class="{'current':page==pageCount}">{{pageCount}}</span>
		<span class="pagination__item page-next" title="<?php _e('Следующая страница','usam'); ?>" v-if="pageCount>3" @click="nextPage">&gt;</span>
	</div>
</script>
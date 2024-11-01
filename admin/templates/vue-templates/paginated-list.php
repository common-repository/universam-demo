<script type="x-template" id="paginated-list">
	<div class ="tablenav-pages one-page" v-show="pageCount>1">
		<span class="displaying-num">{{count}} элементов</span>
		<span class="tablenav-pages-navspan button" :class="{'disabled':pageCount===1}" aria-hidden="true" @click="page=1" v-show="page!=1">«</span>
		<span class="tablenav-pages-navspan button" title="<?php _e('Предыдущая страница','usam'); ?>" v-show="page>1" @click="prevPage">‹</span>
		<span class="paging-input">
			<input class="current-page" id="current-page-selector" v-model="page" type="text" size="1" aria-describedby="table-paging">
			<span class="tablenav-paging-text"> из <span class="total-pages">{{pageCount}}</span></span>
		</span>
		<span class="tablenav-pages-navspan button" title="<?php _e('Следующая страница','usam'); ?>" v-show="pageCount>2&&pageCount!=page" @click="nextPage">›</span>
		<span class="tablenav-pages-navspan button" :class="{'disabled':pageCount===1}" aria-hidden="true" @click="page=pageCount" v-show="pageCount>2&&pageCount!=page">»</span>
	</div>
</script>
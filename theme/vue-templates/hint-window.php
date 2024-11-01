<script type="x-template" id="hint-window">
	<div class="hint" ref="hint" :class="{'open':show}">		
		<div class='hint_content'>
			<slot name="content" :show="show"></slot>
		</div>
		<div class='hint_arrow'></div>
	</div>
</script>
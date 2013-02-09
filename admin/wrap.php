<div class="wrap">
	
	<?php echo $this->admin_tabs($current_tab); ?>
	
	<?php do_action('admin_notices'); ?>
	
	<?php
	$include = BMSC_PATH.'admin/'.$current_tab.'.php';
	
	if(file_exists($include)) require_once($include);
	?>
	
	
</div>
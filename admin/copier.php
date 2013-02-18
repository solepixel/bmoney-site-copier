<form id="bmsc-copy" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="block-form">
	<h3>Where do you want to copy this site?</h3>
	
	<fieldset>
		<label class="new-url">
			<span class="label">URL of <strong>New</strong> Site</span>
			<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>new_url" value="<?php if(isset($_POST[BMSC_OPT_PREFIX.'new_url'])){ echo sanitize_text_field($_POST[BMSC_OPT_PREFIX.'new_url']); } else { echo 'http://'; } ?>" /></span>
			<span class="description">Don't forget the "http://" part</span>
		</label>
		
		<label class="site-url">
			<span class="label">URL of This Site</span>
			<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>site_url" value="<?php if(isset($_POST[BMSC_OPT_PREFIX.'site_url'])){ echo sanitize_text_field($_POST[BMSC_OPT_PREFIX.'site_url']); } else { echo site_url(); } ?>" /></span>
			<span class="description">Don't forget the "http://" part</span>
		</label>
		
		<label class="directory">
			<span class="label">Directory to copy</span>
			<span class="before"><?php echo trailingslashit($_SERVER['DOCUMENT_ROOT']); ?></span>
			<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>directory" value="<?php if(isset($_POST[BMSC_OPT_PREFIX.'directory'])) echo sanitize_text_field($_POST[BMSC_OPT_PREFIX.'directory']); ?>" /></span>
			<span class="description">Leave empty to copy the root of this site</span>
		</label>
		
	</fieldset>
	
	<fieldset>
		<?php include(BMSC_PATH.'admin/ftp-config.php'); ?>
		
		<label class="ftp-path">
			<span class="label">Path to install directory</span>
			<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>ftp_path" value="<?php if(isset($_POST[BMSC_OPT_PREFIX.'ftp_path'])) echo sanitize_text_field($_POST[BMSC_OPT_PREFIX.'ftp_path']); ?>" /></span>
		</label>
		
		<hr />
		
		<?php include(BMSC_PATH.'admin/db-config.php'); ?>
		
	</fieldset>
	
	<div class="buttons">
		<button class="button" id="test-connections">Test Connections</button> &nbsp; 
		<?php submit_button(__('Copy!', 'bmsc'), 'primary', '', false); ?>
	</div>
</form>
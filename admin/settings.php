<form id="bmsc-settings" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" class="block-form">
	<h3>Defaults</h3>
	<p>These values will serve as the defaults for copying the site.</p>
	
	<fieldset>
		<?php include(BMSC_PATH.'admin/ftp-config.php'); ?>
		<hr />
		<?php include(BMSC_PATH.'admin/db-config.php'); ?>
	</fieldset>
	
	<h3>Filters</h3>
	<fieldset>
		<label>
			<span class="label">Exclude Files/Folders</span>
			<span class="field"><textarea name="<?php echo BMSC_OPT_PREFIX; ?>excludes" rows="6"><?php echo implode(PHP_EOL, $this->settings['excludes']); ?></textarea></span>
			<span class="description">Separate excluded files/folders on new lines. Example:<br />
			wp-content/infinitewp<br />
			wp-content/backups</span>
		</label>
	</fieldset>
	
	<h3>cPanel Settings</h3>
	
	<div class="buttons">
		<?php submit_button(__('Save Settings', 'bmsc')); ?>
	</div>
</form>
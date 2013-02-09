<label>
	<span class="label">Database Host</span>
	<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>db_host" value="<?php echo $this->settings['db_host']; ?>" /></span>
</label>
<label>
	<span class="label">Database Remote Host</span>
	<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>db_remote_host" value="<?php echo $this->settings['db_remote_host']; ?>" /></span>
	<span class="description">This is used remotely, so enter the hostname of the MySQL server. If it's the same server, use 'localhost'.</span>
</label>
<label>
	<span class="label">Database Name</span>
	<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>db_name" value="<?php echo $this->settings['db_name']; ?>" /></span>
</label>
<label>
	<span class="label">Database Username</span>
	<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>db_user" value="<?php echo $this->settings['db_user']; ?>" /></span>
</label>
<label>
	<span class="label">Database Password</span>
	<span class="field"><input type="password" name="<?php echo BMSC_OPT_PREFIX; ?>db_pass" value="<?php echo $this->settings['db_pass']; ?>" /></span>
</label>
<label class="db-prefix">
	<span class="label">Database Table Prefix</span>
	<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>db_prefix" value="<?php echo $this->settings['db_prefix']; ?>" /></span>
</label>
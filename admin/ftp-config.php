<label>
	<span class="label">FTP Host</span>
	<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>ftp_host" value="<?php echo $this->settings['ftp_host']; ?>" /></span>
</label>
<label>
	<span class="label">FTP Username</span>
	<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>ftp_user" value="<?php echo $this->settings['ftp_user']; ?>" /></span>
</label>
<label>
	<span class="label">FTP Password</span>
	<span class="field"><input type="password" name="<?php echo BMSC_OPT_PREFIX; ?>ftp_pass" value="<?php echo $this->settings['ftp_pass']; ?>" /></span>
</label>
<label class="ftp-port">
	<span class="label">FTP Port</span>
	<span class="field"><input type="text" name="<?php echo BMSC_OPT_PREFIX; ?>ftp_port" value="<?php echo $this->settings['ftp_port']; ?>" /></span>
</label>
<div class="label ftp-mode">
	<span class="label">FTP Mode</span>
	<span class="field inline">
		<label><input type="radio" name="<?php echo BMSC_OPT_PREFIX; ?>ftp_mode" value="active"<?php if($this->settings['ftp_mode'] == 'active') echo ' checked="checked"'; ?> /> Active</label>
		<label><input type="radio" name="<?php echo BMSC_OPT_PREFIX; ?>ftp_mode" value="passive"<?php if($this->settings['ftp_mode'] == 'passive') echo ' checked="checked"'; ?> /> Passive</label>
	</span>
</label>
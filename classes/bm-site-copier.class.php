<?php

if(class_exists('BM_Site_Copier')) return;
	
class BM_Site_Copier {
	
	var $debug = false;
	
	var $admin_page = 'bmoney-site-copier';
	
	var $settings = array();
	var $vars = array();
	
	var $error;
	
	var $copy_root;
	var $wd;
	var $ftp_root;
	
	var $fails = array();
	
	/**
	 * BM_Site_Copier::__construct()
	 * 
	 * @return void
	 */
	function __construct(){
		$defaults = array(
			'site_url' => '',
			
			'ftp_host' => '',
			'ftp_user' => '',
			'ftp_pass' => '',
			'ftp_port' => '22',
			'ftp_mode' => 'active',
			'excludes' => array(),
			
			'db_host' => 'localhost',
			'db_remote_host' => '',
			'db_name' => '',
			'db_user' => '',
			'db_pass' => '',
			'db_prefix' => 'wp_'
		);
		$this->settings = get_option(BMSC_OPT_PREFIX.'settings', $defaults);
		$this->decrypt_passwords();
	}
	
	/**
	 * BM_Site_Copier::initialize()
	 * 
	 * @return void
	 */
	function initialize(){
		ini_set('memory_limit', '-1');
		set_time_limit(600); // 10 minutes
		
		add_action( 'init', array($this, '_init'));
	}
	
	/**
	 * BM_Site_Copier::_init()
	 * 
	 * @return void
	 */
	function _init(){
		if(is_admin()){
			
			if(class_exists('WP_GitHub_Updater')){
				$config = array(
					'slug' => 'bmoney-site-copier/bmoney-site-copier.plugin.php', // this is the slug of your plugin
					'proper_folder_name' => 'bmoney-site-copier', // this is the name of the folder your plugin lives in
					'api_url' => 'https://api.github.com/solepixel/bmoney-site-copier', // the github API url of your github repo
					'raw_url' => 'https://raw.github.com/solepixel/bmoney-site-copier/master/', // the github raw url of your github repo
					'github_url' => 'https://github.com/solepixel/bmoney-site-copier', // the github url of your github repo
					'zip_url' => 'https://github.com/solepixel/bmoney-site-copier/archive/master.zip', // the zip url of the github repo
					'sslverify' => false, // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
					'requires' => '3.0', // which version of WordPress does your plugin require?
					'tested' => '3.5', // which version of WordPress is your plugin tested up to?
					'readme' => 'README.md', // which file to use as the readme for the version number
					'access_token' => '', // Access private repositories by authorizing under Appearance > Github Updates when this example plugin is installed
				);
				new WP_GitHub_Updater($config);
			}
			
			wp_register_style(BMSC_OPT_PREFIX.'admin', BMSC_DIR.'css/admin.css', array(), BMSC_VERSION);
			add_action('admin_menu', array($this, '_admin_menu'));
			
			add_action('bmsc_copy_site', array($this, 'copy_database'));
			add_action('bmsc_copy_site', array($this, 'change_urls'));
			add_action('bmsc_copy_site', array($this, 'ftp_files'));
		}
		
	}
	
	/**
	 * BM_Site_Copier::enqueue_admin_scripts()
	 * 
	 * @return void
	 */
	function enqueue_admin_scripts(){
		#wp_enqueue_style('bmsc-styles');
	}
	
	/**
	 * BM_Site_Copier::_admin_menu()
	 * 
	 * @return void
	 */
	function _admin_menu(){
		add_submenu_page('tools.php', BMSC_PI_NAME, BMSC_PI_NAME, 8, $this->admin_page, array($this, '_admin_page'));
	}
	
	
	/**
	 * BM_Ultimate_CSV_Importer::admin_tabs()
	 * 
	 * @param string $current
	 * @return
	 */
	function admin_tabs($current='copier'){
		$tabs = array(
			'copier' => 'Site Copier',
			'settings' => 'Settings'
		);
		
		$tabs_html = get_screen_icon('tools');

		$tabs_html .= '<h2 class="nav-tab-wrapper">';
		
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';
			$tabs_html .= '<a class="nav-tab'.$class.'" href="?page='.$this->admin_page.'&tab='.$tab.'">'.$name.'</a>';
		}
		$tabs_html .= '</h2>';
		
		return $tabs_html;
	}
	
	/**
	 * BM_Site_Copier::_settings_updated()
	 * 
	 * @return void
	 */
	function _settings_updated(){
		 echo '<div class="updated"><p>Your settings have been updated.</p></div>';
	}
	
	/**
	 * BM_Site_Copier::_site_copied()
	 * 
	 * @return void
	 */
	function _site_copied(){
		 if(count($this->fails)){
		 	echo '<div class="error"><p>There were errors when copying this site:</p>
			 <ul>';
			 foreach($this->fails as $file){
			 	echo '<li>'.$file.'</li>';
			 }
			 echo '</ul></div>';
		 } else {
		 	echo '<div class="updated"><p>Your site has been copied. <a href="'.$this->vars['new_url'].'" target="_blank">Visit New Site</a> | <a href="'.$this->vars['new_url'].'/wp-login.php" target="_blank">Login to Admin</a></p></div>';
		 }
	}
	
	/**
	 * BM_Site_Copier::_display_error()
	 * 
	 * @return void
	 */
	function _display_error(){
		 echo '<div class="error"><p>'.$this->error.'</p></div>';
	}
	
	/**
	 * BM_Site_Copier::_admin_page()
	 * 
	 * @return void
	 */
	function _admin_page(){
		wp_enqueue_style(BMSC_OPT_PREFIX.'admin');
		#wp_enqueue_script(BMSC_OPT_PREFIX.'admin');
		
		$current_tab = (isset($_GET['tab']) && $_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'copier';
		
		if(method_exists($this, $current_tab)){
			extract($this->$current_tab());
		}
		
		include(BMSC_PATH.'/admin/wrap.php');
	}
	
	
	/**
	 * BM_Site_Copier::settings()
	 * 
	 * @return
	 */
	function settings(){
		if(isset($_POST) && count($_POST) > 0){
			foreach($_POST as $k => $v){
				if(substr($k, 0, strlen(BMSC_OPT_PREFIX)) == BMSC_OPT_PREFIX){
					$k = str_replace(BMSC_OPT_PREFIX, '', $k);
					if(($k == 'db_pass' || $k == 'ftp_pass') && $v){
						$v = $this->encrypt_pass($v);
					} elseif($k == 'excludes'){
						$v = array_map('trim', explode(PHP_EOL, $v));
					}
					
					if(is_array($v)){
						$this->settings[$k] = $v;
					} else {
						$this->settings[$k] = sanitize_text_field($v);
					}
				}
			}
			update_option(BMSC_OPT_PREFIX.'settings', $this->settings);
			$this->decrypt_passwords();
			add_action('admin_notices', array($this, '_settings_updated'));
		}
		return array();
	}
	
	/**
	 * BM_Site_Copier::encrypt_pass()
	 * 
	 * @param mixed $pass
	 * @return
	 */
	function encrypt_pass($pass){
		if(function_exists('mcrypt_encrypt')){
			$encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(SECURE_AUTH_SALT), $pass, MCRYPT_MODE_CBC, md5(md5(SECURE_AUTH_SALT))));
			if(strlen($encrypted)) return $encrypted;
		}
		return $pass;
	}
	
	/**
	 * BM_Site_Copier::decrypt_passwords()
	 * 
	 * @return void
	 */
	function decrypt_passwords(){
		foreach($this->settings as $k => $v){
			if($k == 'db_pass' || $k == 'ftp_pass'){
				$this->settings[$k] = $this->decrypt_pass($v);
			}
		}
	}
	
	/**
	 * BM_Site_Copier::decrypt_pass()
	 * 
	 * @param mixed $pass
	 * @return
	 */
	function decrypt_pass($pass=NULL){
		if($pass && function_exists('mcrypt_encrypt')){
			$decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(SECURE_AUTH_SALT), base64_decode($pass), MCRYPT_MODE_CBC, md5(md5(SECURE_AUTH_SALT))), "\0");
			if(strlen($decrypted)) return $decrypted;
		}
		
		return $pass;
	}
	
	/**
	 * BM_Site_Copier::copier()
	 * 
	 * @return
	 */
	function copier(){
		if(isset($_POST) && count($_POST) > 0){
			foreach($_POST as $k => $v){
				if(substr($k, 0, strlen(BMSC_OPT_PREFIX)) == BMSC_OPT_PREFIX){
					$k = str_replace(BMSC_OPT_PREFIX, '', $k);
					if(is_array($v)){
						$this->vars[$k] = $v;
					} else {
						$this->vars[$k] = sanitize_text_field($v);
					}
				}
			}
			
			do_action('bmsc_copy_site', $this);
			
			add_action('admin_notices', array($this, '_site_copied'));
			
		}
		
		return array();
	}
	
	/**
	 * BM_Site_Copier::ftp_files()
	 * 
	 * @return void
	 */
	function ftp_files(){
		$conn = ftp_connect($this->vars['ftp_host']);
		$login = ftp_login($conn, $this->vars['ftp_user'], $this->vars['ftp_pass']);
		$passive = $this->vars['ftp_mode'] == 'passive';
		ftp_pasv($conn, $passive);
		if (!$conn || !$login) {
			$this->error = 'FTP Connection failed.';
			add_action('admin_notices', array($this, '_display_error'));
		} else {
			ftp_chdir($conn, $this->vars['ftp_path']);
			$this->wd = '';
			$source = $_SERVER['DOCUMENT_ROOT'].trailingslashit($this->vars['directory']);
			
			if($this->debug) echo '<div style="overflow:auto;height:500px; margin:25px 25px 25px 0;">';
			
				if($this->debug) echo '<div><strong>Opening Folder</strong> '.$source.'</div>';
				chdir($source);
				$this->copy_root = $source;
				$this->recursive_ftp_copy($conn, $source);
			
			if($this->debug) echo '</div>';
		}
		ftp_close($conn);
	}
	
	
	/**
	 * BM_Site_Copier::recursive_ftp_copy()
	 * 
	 * @param mixed $conn
	 * @param mixed $src
	 * @return void
	 */
	function recursive_ftp_copy($conn, $src){
		if ($handle = opendir($src)){
			while (false !== ($file = readdir($handle))){
				if ($file != '.' && $file != '..')   {
					if($this->is_excluded($file, $this->wd)){
						if($this->debug) echo '<div><strong>SKIPPED!</strong></div>';
						continue;
					}
					
					if (!is_dir($file)){
						if($this->debug) echo '<div><strong>Copying</strong> '.$this->wd.$file.' to '.$this->vars['ftp_path'].'/'.$this->wd.'</div>';
						
						$copy = $this->modifications($file);
						$result = ftp_put($conn, basename($file), $copy, FTP_BINARY);
						if(!$result){
							$this->fails[] = $file;
							if($this->debug) echo ' &nbsp; <span style="color:#C00;">FAIL</span>';
						} else {
							if($this->debug) echo ' &nbsp; <span style="color:#33FF00;">Copied!</span>';
						}
						if($copy != $file){
							unlink($this->copy_root.$copy);
						}
					} else {
						if($this->debug) echo '<div><strong>Changing working directory to '.$this->copy_root.$this->wd.$file.'</strong></div>';
						if($this->debug) echo '<div><strong>Changing remote working directory to '.$this->vars['ftp_path'].'/'.$this->wd.$file.'</strong></div>';
						
						$prev = $this->wd;
						$this->wd .= $file.'/';
						chdir($this->copy_root.$this->wd);
						$this->ftp_change_dir($conn, $file);
						
						if($this->debug) echo '<div><strong>Current working directory: '.getcwd().'</strong></div>';
						if($this->debug) echo '<div><strong>Current remote working directory: '.ftp_pwd($conn).'</strong></div>';
						
						$this->recursive_ftp_copy($conn, $this->copy_root.$this->wd);
						ftp_chdir($conn, '../');
						chdir('../');
						$this->wd = $prev;
						
						if($this->debug) echo '<div><strong>Current working directory: '.getcwd().'</strong></div>';
						if($this->debug) echo '<div><strong>Current remote working directory: '.ftp_pwd($conn).'</strong></div>';
						if($this->debug) echo '<div><strong>Restoring working directory to '.$this->wd.'</strong></div>';
					}
				}
			}
			closedir($handle);
		}   
	}
	
	/**
	 * BM_Site_Copier::ftp_change_dir()
	 * 
	 * @param mixed $conn
	 * @param mixed $dir
	 * @return
	 */
	function ftp_change_dir($conn, $dir){
		$pushd = ftp_pwd($conn);
		
		if ($pushd !== false && @ftp_chdir($conn, $dir)){
			ftp_chdir($conn, $pushd);   
			return true;
		}
		
		if($this->debug) echo '<div><strong>Creating ftp directory: '.$dir.'</strong></div>';
		ftp_mkdir($conn, $dir);
		ftp_chdir($conn, $dir);
		
		return true;
	}
	
	/**
	 * BM_Site_Copier::is_excluded()
	 * 
	 * @param mixed $name
	 * @param string $working_dir
	 * @return
	 */
	function is_excluded($name, $working_dir=''){
		$excluded = false;
		foreach($this->settings['excludes'] as $exclude){
			
			if($name == $exclude) $excluded = true;
			if($excluded){
				if($this->debug) echo '<div style="color:#666;">EC1: '.$name. ' | '.$exclude.'</div>';
				return $excluded;
			}
			
			if(rtrim($working_dir.$name, '/') == rtrim($exclude, '/')) $excluded = true;
			if($excluded){
				if($this->debug) echo '<div style="color:#666;">EC2: '.rtrim($working_dir.$name, '/'). ' | '.rtrim($exclude, '/').'</div>';
				return $excluded;
			}
			
			if($working_dir.$name == $exclude) $excluded = true;
			if($excluded){
				if($this->debug) echo '<div style="color:#666;">EC3: '.$working_dir.$name. ' | '.$exclude.'</div>';
				return $excluded;
			}
		}
		
		return $excluded;
	}
	
	/**
	 * BM_Site_Copier::modifications()
	 * 
	 * @param mixed $file
	 * @return
	 */
	function modifications($file){
		if($file == 'wp-config.php'){
			$copy = 'wp-config-bmcopy.php';
			$source = $this->copy_root.$file;
			$target = $this->copy_root.$copy;
			
			if(file_exists($source)){
				$sh=fopen($source, 'r');
				$th=fopen($target, 'w');
				while (!feof($sh)) {
					$line = fgets($sh);
					if(substr($line, 0, 1) != '#' && substr($line, 0, 2) != '//'){
						if (strpos($line, 'DB_NAME') !== false){
							$line = 'define(\'DB_NAME\', \''.$this->vars['db_name'].'\');' . PHP_EOL;
						} elseif(strpos($line, 'DB_USER') !== false){
							$line = 'define(\'DB_USER\', \''.$this->vars['db_user'].'\');' . PHP_EOL;
						} elseif(strpos($line, 'DB_PASSWORD') !== false){
							$line = 'define(\'DB_PASSWORD\', \''.$this->vars['db_pass'].'\');' . PHP_EOL;
						} elseif(strpos($line, 'DB_HOST') !== false){
							$line = 'define(\'DB_HOST\', \''.$this->vars['db_host'].'\');' . PHP_EOL;
						} elseif(strpos($line, '$table_prefix') !== false){
							$line = '$table_prefix  = \''.$this->vars['db_prefix'].'\';' . PHP_EOL;
						} elseif(strpos($line, 'ini_set(\'display_errors\'') !== false){
							$line = 'ini_set(\'display_errors\',\'0\');' . PHP_EOL;
						} else {
							$auth_keys = array(
								'AUTH_KEY' => 3,			'SECURE_AUTH_KEY' => 1,
								'LOGGED_IN_KEY' => 2,		'NONCE_KEY' => 3,
								'AUTH_SALT' => 3,			'SECURE_AUTH_SALT' => 1,
								'LOGGED_IN_SALT' => 1,		'NONCE_SALT' => 2
							);
							foreach($auth_keys as $ak => $tabs){
								if(strpos($line, '\''.$ak.'\'') !== false){
									$line = 'define(\''.$ak.'\','.str_repeat("\t",$tabs).'\''.$this->generate_key().'\');' . PHP_EOL;
								}
							}
						}
					}
					fwrite($th, $line);
				}
				fclose($sh);
				fclose($th);
			}
			$file = $copy;
		}
		
		return $file;
	}
	
	/**
	 * BM_Site_Copier::generate_key()
	 * 
	 * @param integer $length
	 * @return
	 */
	function generate_key($length=64) {
		$random = '';
		for ($i = 0; $i < $length; $i++) {
			$char = chr(mt_rand(33, 126));
			if($char != '\''){ // single quotes break stuff.
				$random .= $char;
			} else {
				$i--;
			}
		}
		return $random;
	}
	
	
	/**
	 * BM_Site_Copier::copy_database()
	 * 
	 * @return
	 */
	function copy_database(){
		$sql_file = $_SERVER['DOCUMENT_ROOT'].'/bm-db-backup.sql';
		exec('mysqldump --user='.DB_USER.' --password='.DB_PASSWORD.' --host='.DB_HOST.' '.DB_NAME.' > '.$sql_file);
		$host = $this->vars['db_remote_host'];
		
		$conn = "mysql:host=$host;dbname=".$this->vars['db_name'];
		$db = new PDO($conn, $this->vars['db_user'], $this->vars['db_pass']);
		
		if($db->query(file_get_contents($sql_file))){
			unlink($sql_file);
			return true;
		}
		return false;
	}
	
	
	/**
	 * BM_Site_Copier::change_urls()
	 * 
	 * @return void
	 */
	function change_urls(){
		$params = array(
			'search_for' => $this->vars['site_url'],
			'replace_with' => $this->vars['new_url'],
			
			'db_host' => $this->vars['db_host'],
			'db_name' => $this->vars['db_name'],
			'db_user' => $this->vars['db_user'],
			'db_pass' => $this->vars['db_pass']
		);
		
		$srdb = new Str_replace_db($params);
		$srdb->search_replace();
		
		#$srdb->get_report();
	}
}

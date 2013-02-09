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
		 echo '<div class="updated"><p>Your site has been copied.</p></div>';
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
	 * BM_Site_Copier::_settings_page()
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
	
	function encrypt_pass($pass){
		if(function_exists('mcrypt_encrypt')){
			$encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(SECURE_AUTH_SALT), $pass, MCRYPT_MODE_CBC, md5(md5(SECURE_AUTH_SALT))));
			if(strlen($encrypted)) return $encrypted;
		}
		return $pass;
	}
	
	function decrypt_passwords(){
		foreach($this->settings as $k => $v){
			if($k == 'db_pass' || $k == 'ftp_pass'){
				$this->settings[$k] = $this->decrypt_pass($v);
			}
		}
	}
	
	function decrypt_pass($pass=NULL){
		if($pass && function_exists('mcrypt_encrypt')){
			$decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(SECURE_AUTH_SALT), base64_decode($pass), MCRYPT_MODE_CBC, md5(md5(SECURE_AUTH_SALT))), "\0");
			if(strlen($decrypted)) return $decrypted;
		}
		
		return $pass;
	}
	
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
			
			$this->copy_database();
			$this->change_urls();
			$this->ftp_files();
			
			add_action('admin_notices', array($this, '_site_copied'));
			
		}
		
		return array();
	}
	
	function ftp_files(){
		$conn = ftp_connect($this->vars['ftp_host']);
		$login = ftp_login($conn, $this->vars['ftp_user'], $this->vars['ftp_pass']);
		$passive = $this->vars['ftp_mode'] == 'passive';
		ftp_pasv($conn, $passive);
		if (!$conn || !$login) {
			$this->error = 'FTP Connection failed.';
			add_action('admin_notices', array($this, '_display_error'));
		} else {
			$chdir = ftp_chdir($conn, $this->vars['ftp_path']);
			$source = $_SERVER['DOCUMENT_ROOT'].trailingslashit($this->vars['directory']);
			$this->recursive_ftp_copy($conn, $source, true);
		}
		ftp_close($conn);
	}
	
	
	function recursive_ftp_copy($conn, $src, $base=false){
		if($base) $this->copy_root = $src;
		
		chdir($src);
		if ($handle = opendir($src)){
			while (false !== ($file = readdir($handle))){
				if ($file != '.' && $file != '..')   {
					// check if it's a file or directory
					if (!is_dir($file)){
						if(!$this->is_excluded($file, 'file', $this->wd)){
							$copy = $this->modifications($file);
							ftp_put($conn, basename($file), $copy, FTP_BINARY);
							if($copy != $file){
								unlink($this->copy_root.$copy);
							}
						}
					} else {
						#if(!$this->is_excluded($file, 'dir', $this->wd)){ // this isn't working for some reason....
							$this->ftp_change_dir($conn, $file);
							$this->wd .= trailingslashit($file);
							$this->recursive_ftp_copy($conn, $this->copy_root.$this->wd);
							$this->wd = $this->str_lreplace($file.'/', '', $this->wd);
							ftp_chdir($conn, '../');
							chdir('../');
						#}
					}
				}
			}
			closedir($handle);
		}   
	}
	
	function ftp_change_dir($conn, $dir){
		$pushd = ftp_pwd($conn);
		
		if ($pushd !== false && @ftp_chdir($conn, $dir)){
			ftp_chdir($conn, $pushd);   
			return true;
		}
		
		ftp_mkdir($conn, $dir);
		ftp_chdir($conn, $dir);
		
		return true;
	} 
	
	function str_lreplace($search, $replace, $subject){
	    $pos = strrpos($subject, $search);
	
	    if($pos !== false){
	        $subject = substr_replace($subject, $replace, $pos, strlen($search));
	    }
	
	    return $subject;
	}
	
	function is_excluded($name, $type='file', $working_dir=''){
		foreach($this->settings['excludes'] as $exclude){
			if($name == $exclude) return true;
			
			if($type == 'dir'){
				if(rtrim($working_dir.$name, '/') == rtrim($exclude, '/')) return true;
			} elseif($type == 'file'){
				if($working_dir.$name == $exclude) return true;
			}
		}
		
		return false;
	}
	
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

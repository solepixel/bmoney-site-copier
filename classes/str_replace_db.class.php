<?php

if(class_exists('Str_replace_db')) return;

class Str_replace_db {
	
	private $params = array();
	
	private $debug;
	private $search_only;
	private $reporting;
	
	private $init = false;
	
	private $errs = array();
	private $warns = array();
	private $msgs = array();
	private $changes = array();
	
	private $db;
	private $db_config;
	private $cache = array();
	
	private $search = '';
	private $replace = '';
	
	private $start_time;
	private $queries_run = 0;
	private $items_changed = 0;
	private $items_checked = 0;
	
	function __construct($params=array()){
		
		$this->message('Initializing.');
		
		$defaults = array(
			'search_for'	=> '',
			'replace_with'	=> '',
			'undo'			=> false,
			
			'db_scheme'		=> 'mysql',
			'db_host'		=> 'localhost',
			'db_name'		=> '',
			'db_user'		=> '',
			'db_pass'		=> '',
			
			'debug'			=> false,
			'reporting'		=> 'basic',
			'search_only'	=> false
		);
		
		$this->params = array_merge($defaults, $params);
		
		$this->debug = $this->params['debug'];
		if($this->debug){
			$this->message('DEBUG MODE IS: <strong>ON</strong>.');
		}
		
		$this->search_only = $this->params['search_only'];
		if($this->search_only){
			$this->message('SEARCH MODE ONLY: <strong>TRUE</strong>.');
		}
		
		$this->_prevent_timeout();
		$this->_setup_search_replace();
		$this->_setup_db();
		
		if($reporting){
			$this->reporting = $reporting;
		}
		
		$this->start_time = $this->get_time(false);
		
		$this->init = true;
	}
	
	function _prevent_timeout(){
		$hrs = 1; // allow this script to run at 1 hour max
		set_time_limit(60*60*$hrs);
		
		$msg = 'Max Execution Time set to '.$hrs.' hour';
		if($hrs != 1){
			$msg .= 's';
		}
		$this->message($msg.'.');
	}
	
	function _setup_search_replace($search=NULL, $replace=NULL){
		
		if($this->search && $this->replace) return;
		
		$this->search = $this->params['search_for'] ? $this->params['search_for'] : $search;
		$this->replace = $this->params['replace_with'] ? $this->params['replace_with'] : $replace;
		
		if($this->params['undo']){
			$this->search = $this->params['replace_with'];
			$this->replace = $this->params['search_for'];
		}
		
		
		if(is_array($this->search)){
			$this->search = implode(', ', $this->search);
		}
		$this->message('USING Search Term(s): <strong>'.$this->search.'</strong>.');
		
		
		if(is_array($this->replace)){
			$this->replace = implode(', ', $this->replace);
		}
		
		$this->message('USING Replace Term(s): <strong>'.$this->replace.'</strong>.');
	}
	
	
	function get_time($string=true){
		if($string){
			$time = microtime();
			$time = ltrim(substr($time, 0, strpos($time, ' ')), '0');
			$time = date('Y-m-d H:i:s').$time;
			return $time;
		}
		return microtime(true);
	}
	
	function warning($warning){
		if($warning){
			$this->warns[$this->get_time()] = $warning;
		}
	}
	
	function error($error){
		if($error){
			$this->errs[$this->get_time()] = $error;
		}
	}
	
	function message($message){
		if($message){
			$this->msgs[$this->get_time()] = $message;
		}
	}
	
	function change($change){
		if($change){
			$this->changes[$this->get_time()] = htmlspecialchars($change);
		}
	}
	
	function _setup_db(){
		$conn = $this->params['db_scheme'].':host='.$this->parmas['db_host'].';dbname='.$this->params['db_name'];
		$this->db = new PDO($conn, $this->params['db_user'], $this->params['db_pass']);
	}
	
	function db_query($sql){
		$obj = new stdClass();
		
		if(!$this->db){
			$this->error('Unable to connect to database');
		} else {
			$obj->sql = trim($sql);
			$obj->result = $this->db->query($obj->sql);
			
			if($obj->result){
				$obj->rows = $obj->result->rowCount();
				$this->queries_run++;
			} else {
				$err = $this->db->errorInfo();
				$this->error($err[2].'<br /><br />'.$sql);
			}
		}
		
		return $obj;
	}
	
	function get_tables(){
		
		global $tables;
		
		if(isset($this->cache['db'][$this->params['db_name']]['db_tables'])){
			return $this->cache['db'][$this->params['db_name']]['db_tables'];
		}
		
		if(count($tables) <= 0){
			
			$tables = array();
			
			$sql = "SHOW TABLES;";
			$res = $this->db_query($sql);
				
			while($row = $res->result->fetchObject()){
				$tables[] = $row->{'Tables_in_'.$this->params['db_name']};
			}
			
		}
		
		$this->cache['db'][$this->params['db_name']]['db_tables'] = $tables;
		
		return $tables;
	}
	
	function get_columns($table){
		
		if(isset($this->cache['db'][$this->params['db_name']][$table]['cols'])){
			return $this->cache['db'][$this->params['db_name']][$table]['cols'];
		}
		
		$cols = array();
		
		$sql = 'SHOW FULL FIELDS FROM `'.$table.'`;';
		$res = $this->db_query($sql);
		
		while($row = $res->result->fetchObject()){
			$cols[] = $row;
		}
		
		$this->cache['db'][$this->params['db_name']][$table]['cols'] = $cols;
		
		return $cols;
	}
	
	function get_index($var=NULL){
		if(is_string($var)){
			$cols = $this->get_columns($var);
		} elseif(is_array($var)){
			$cols = $var;
		} else {
			$this->error('Invalid argument for get_index() method.');
			return false;
		}
		
		$index = false;
		
		foreach($cols as $col){
			if(isset($col->Key) && $col->Key == 'PRI'){
				$index = $col;
				break;
			}
		}
		
		return $index;
	}
	
	function get_data($table){
		if(!$table){
			$this->error('Cannot get data without table name.');
			return false;
		}
		
		$sql = 'SELECT * FROM `'.$table.'`;';
		return $this->db_query($sql);
	}
	
	function search_replace($search='', $replace=''){
		
		if(!$this->init){
			$this->__construct();
		}
		
		$this->_setup_search_replace($search, $replace);
		
		if(!$this->search){
			$this->error('Search value is blank. Nothing to search for.');
			$this->finish();
			return;
		}
		
		$tables = $this->get_tables();
		$num_tables = count($tables);
		
		$this->message('<strong>'.$num_tables.'</strong> Tables Found in <em>'.$params['database'].'</em>');
		
		foreach($tables as $table){
			$data = $this->get_data($table);
			if($data->rows <= 0){
				$this->message('SKIPPED table <em>'.$table.'</em>: No Data.');
				continue;
			}
			
			$columns = $this->get_columns($table);
			$index = $this->get_index($columns);
			
			if(!$index){
				$this->warning('WARNING: Table <em>'.$table.'</em> has No Identifiable Key Column.');
				continue;
			}
			
			$this->message('ANALYZING table <strong>'.$table.'</strong>: '.$data->rows.' total rows.');
			
			$table_changes = 0;
			
			while($row = $data->result->fetchObject()){
				
				$set = array();
				$where = array();
				
				if($index){
					$where[$index->Field] = $row->{$index->Field};
				}
				
				$row_change = false;
				
				foreach ($columns as $column) {
					$col_change = false;
					
					$this->items_checked++;
					
					$orig_value = $row->{$column->Field};
					$new_value = $orig_value;
					
					if(strpos($orig_value, $this->search) !== false){
						
						$unserialized = unserialize($orig_value); // unserialise - if false returned we don't try to process it as serialised
						
						if ($unserialized === false) {
							
							if (is_string($orig_value)){
								$new_value = str_replace($this->search, $this->replace, $orig_value);
							}
						
						} else {
							$this->recursive_array_replace($this->search, $this->replace, $unserialized);
							
							$new_value = serialize($unserialized);
						}
					}
					
					if ($orig_value != $new_value) {   // If they're not the same, we need to add them to the update string
						$this->items_changed++;
						$table_changes++;
						
						$col_change = true;
						$row_change = true;
					}
						
					if($col_change){
						$set[$column->Field] = $new_value;
					}
					if(!$index){
						$where[$column->Field] = $orig_value;
					}
				}
					
				if ($row_change) {
					
					if(count($set) > 0 && count($where) > 0){
						$updateSQL = 'UPDATE `'.$table.'`';
						$setSQL = '';
						$whereSQL = '';
						$db_vars = array();
						$i = 1;
						
						foreach($set as $k => $item){
							if($setSQL){
								$setSQL .= ', ';
							}
							$setSQL .= "`$k` = :$k$i";
							$db_vars[':'.$k.$i] = $item;
							$i++;
						}
						
						foreach($where as $k => $item){
							if($whereSQL){
								$whereSQL .= ', ';
							}
							$whereSQL .= "`$k` = :$k$i";
							$db_vars[':'.$k.$i] = $item;
							$i++;
						}
						
						if($this->search_only){
							$this->change('INSTANCE FOUND in Table `'.$table.'` WHERE '.$whereSQL);
							continue;
						} else {
							
							$updateSQL .= ' SET '.$setSQL . ' WHERE '. $whereSQL.';';
							$this->change($updateSQL);
						}
					
						if(!$this->debug){
							####
							#
							# The following line performs the actual database update.
							# This is the only than that changes data in the database.
							# It is recommend you run first run in debug mode to test.
							#
							####
							$query = $this->db->prepare($updateSQL);
							$query->execute($db_vars);
						}
					}
					
				}
			
			} // end rows while
			
			$this->message($table_changes.' total updates for <strong>'.$table.'</strong>');
		}
		
		$this->finish();
	}
	
	// Credits:  moz667 at gmail dot com for his recursive_array_replace posted at
	//           uk.php.net which saved me a little time - a perfect sample for me
	//           and seems to work in all cases.
	
	function recursive_array_replace($find, $replace, &$data){
		
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$this->recursive_array_replace($find, $replace, $data[$key]);
				} else {
					if (is_string($value)){
						$data[$key] = str_replace($find, $replace, $value);
					}
				}
			}
		} else {
			if (is_string($data)){
				$data = str_replace($find, $replace, $data);
			}
		}
		
		return $data;
	}
	
	function finish(){
		$this->message('<strong>Finished.</strong>');
		$end_time = $this->get_time(false);
		$diff = $end_time - $this->start_time;
		$this->message('Execution Time: <strong>'.$diff.'ms</strong>');
		$this->message('Queries Executed: <strong>'.number_format($this->queries_run).'</strong>');
		$this->message('Items Checked: <strong>'.number_format($this->items_checked).'</strong>');
		$this->message('Items Changed: <strong>'.number_format($this->items_changed).'</strong>');
	}
	
	function get_report(){
		$messages = $this->errs + $this->msgs + $this->changes + $this->warns;
		
		ksort($messages);
		
		if(count($messages) > 0){
			foreach($messages as $k => $msg){
				$class = 'general';
				
				if(isset($this->msgs[$k])){
					$class = 'message';
				} elseif(isset($this->errs[$k])){
					$class = 'error';
				} elseif(isset($this->changes[$k])){
					$class = 'change';
				} elseif(isset($this->warns[$k])){
					$class = 'warning';
				}
				
				echo '<div class="'.$class.'"><span class="time">['.$k.']</span> '.$msg.'</div>';
			}
		}
	}
}
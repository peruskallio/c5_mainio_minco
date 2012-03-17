<?php 
/**
 * The purpose of this class is just to ease up remembering what
 * to call when something is needed to be wrapped into a minco
 * block.
 * 
 */
class MincoBlock {
	
	private static $_mainCacheID = "";
	private static $_blockQueue = array();
	
	private $_cacheID = "";
	
	private $_startFile;
	private $_startLine;
	private $_endFile;
	private $_endLine;
	
	private $_blockStarted = false;
	
	private $_combineOptions = array();
	
	public static function start($cacheID = null, $fileVersion = null) {
		$b =& self::_getNewBlock($cacheID, $fileVersion);
		$b->_startBlock();
	}
	
	public static function end($writeTemplate = false) {
		if (is_object($b =& self::_getCurrentBlock())) {
			$b->_endBlock($writeTemplate);
		}
	}
	
	private static function &_getCurrentBlock() {
		if (sizeof(self::$_blockQueue) > 0) {
			return array_shift(self::$_blockQueue);
		}
		return null;
	}
	
	private static function &_getNewBlock($cacheID = null, $fileVersion = null) {
		if (strlen(self::$_mainCacheID) < 1) {
			global $c, $u;
			Loader::library("minco", "mainio_minco");
			self::$_mainCacheID = $c->getCollectionID() . ':' . ($u->isLoggedIn() ? '1' : '0');
		}
		$b = new MincoBlock($cacheID, $fileVersion);
		array_push(self::$_blockQueue, $b);
		return $b;
	}
	
	protected function __construct($cacheID = null, $fileVersion = null) {
		$this->_cacheID = self::$_mainCacheID;
		if ($cacheID === null) {
			// Generate random cache ID
			$cacheID = "";
			$str = "abcdefghijklmnopqrstuvwxyz0123456789";
			for ($i=0; $i<32; $i++) {
				$char = substr($str, rand(0, strlen($str)-1), 1);
				$cacheID .= (rand(0,1) == 1 ? strtoupper($char) : $char);
			}
		}
		$this->_cacheID .= ':' . $cacheID;
		if ($fileVersion !== null) {
			$this->_cacheID .= ':' . $fileVersion;
			$this->_combineOptions['resourceVersion'] = $fileVersion;
		}
	}
	
	public function __destruct() {
		if ($this->_blockStarted) {
			// End and flush output buffer if it has not been ended
			$content = ob_get_contents();
			ob_end_clean();
			echo $content;
		}
	}
	
	protected function _startBlock() {
		if ($this->_blockStarted) {
			throw new Exception(t("Block already started!"));
		}
		$bt = debug_backtrace();
		$file = $bt[1]['file'];
		$line = $bt[1]['line'];
		$this->_startFile = $file;
		$this->_startLine = $line;
		
		ob_start();
		$this->_blockStarted = true;
	}
	
	protected function _endBlock($writeTemplate = false) {
		if (!$this->_blockStarted) {
			throw new Exception(t("Cannot end block before starting it!"));
		}
		$bt = debug_backtrace();
		$file = $bt[1]['file'];
		$line = $bt[1]['line'];
		$this->_endFile = $file;
		$this->_endLine = $line;
		
		$content = ob_get_contents();
		ob_end_clean();
		$this->_blockStarted = false;
		
		$cachedContent = false;
		if (!MINCO_BYPASS_CACHE) {
			$cachedContent = Cache::get('minco_combined_content', $this->_cacheID);
		}
		if ($cachedContent === false) {
			// Run the combine
			if ($writeTemplate) {
				$this->_combineOptions['writeFiles'] = true;
			}
			$m = Minco::getInstance();
			$content = $m->combine($content, $this->_combineOptions);
			Cache::set('minco_combined_content', $this->_cacheID, $content);
		} else {
			$content = $cachedContent;
		}
		
		if ($writeTemplate) {
			$this->_rewriteTemplates($content);
		}
		
		echo $content . PHP_EOL;
	}

	protected function _rewriteTemplates($content) {
		$cls = get_class($this);
		
		$sl = $this->_startLine-1;
		$sf = file($this->_startFile);
		$el = $this->_endLine-1;
		$ef = file($this->_endFile);
		
		$line = $sf[$sl];
		if (($pos = strpos($line, $cls . "::start(")) !== false) {
			$sf[$sl] = substr($line, 0, $pos) . "if(false): " . substr($line, $pos);
			
			$line = $ef[$el];
			if (($pos = strpos($line, $cls . "::end(")) !== false) {
				if (($pos = strpos($line, ")", $pos)) !== false) {
					$pos++;
					if (substr($line, $pos, 1) == ";") {
						// Remove double semicolon
						$line = substr($line, 0, $pos) . substr($line, $pos+1);
					}
					$line = substr($line, 0, $pos) . "; endif;" . substr($line, $pos);
					if ($this->_startFile != $this->_endFile) {
						$ef[$el] = $line;
					} else {
						$sf[$el] = $line;
					}
					
					// Write files
					$lines = "";
					$i = 0;
					foreach ($sf as $l) {
						if ($i == $sl) {
							// Append the minified content on top of the block starting
							// function call
							$lines .= $content . PHP_EOL;
						}
						$lines .= $l;
						$i++;
					}
					
					if ($fh = @fopen($this->_startFile, 'w')) {
						fwrite($fh, $lines);
						fclose($fh);
						if ($this->_startFile != $this->_endFile) {
							// Only write the end file separately if the
							// start() and end() functions are called in
							// different files
							$lines = "";
							foreach ($ef as $l) {
								$lines .= $l;
							}
							if ($fh = @fopen($this->_endFile, 'w')) {
								fwrite($fh, $lines);
								fclose($fh);
							}
						}
					}
				}
			}
		}
	}
	
}

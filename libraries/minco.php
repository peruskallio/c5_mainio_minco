<?php
class Minco {
	
	protected $_tags = array();
	
	protected $_comjs;
	protected $_comcss;
	
	public function __construct($options = array()) {
		if (isset($options['tags'])) {
			$this->_tags['start'] = $options['tags'][0];
			$this->_tags['end'] = $options['tags'][1];
		}
		Loader::library("resource/combiner_js", "mainio_minco");
		Loader::library("resource/combiner_css", "mainio_minco");
		$this->_comjs = new MincoResourceCombinerJs();
		$this->_comcss = new MincoResourceCombinerCss();
	}
	
	public function __destruct() {
		// Save the resources file before ending the process
		Loader::helper("minco_file_list", "mainio_minco")->save();
	}
	
	public static function getInstance() {
		static $m;
		if (!isset($m)) {
			$m = new Minco(array(
				'tags' => array('<!--[minco]-->', '<!--[/minco]-->')
			));
		}
		return $m;
	}
	
	/**
	 * Minifies the passed HTML source.
	 */
	public function minify($pageContent) {
		Loader::library("3rdparty/minify-2.1.5/min/lib/Minify/HTML", "mainio_minco");
		
		Loader::library("3rdparty/minify-2.1.5/min/lib/Minify/CSS", "mainio_minco");
		Loader::library("3rdparty/minify-2.1.5/min/lib/JSMin", "mainio_minco");
		$opts = array(
			'cssMinifier' => "Minify_CSS::minify",
			'jsMinifier' => "JSMin::minify"
		);
		
		return Minify_HTML::minify($pageContent, $opts);
	}
	
	public function combine($pageContent, $opts = array()) {
		$pageContent = $this->_runCombine($pageContent, $opts);
		return $pageContent;
	}
	
	public function combineTags($pageContent, $opts = array()) {
		if (isset($this->_tags['start']) && isset($this->_tags['end'])) {
			// Find tags and combine in-between
			$ts = $this->_tags['start'];
			$te = $this->_tags['end'];
			
			$offset = 0;
			while (($pos = strpos($pageContent, $ts)) !== false) {
				if (($pose = strpos($pageContent, $te, $pos)) !== false) {
					$start = $pos+strlen($ts);
					$pageStart = substr($pageContent, 0, $pos);
					$target = substr($pageContent, $start, $pose-$start);
					$pageEnd = substr($pageContent, $pose+strlen($te));
					
					$target = $this->_runCombine($target, $opts);
					
					$pageContent = $pageStart;
					$pageContent .= trim($target);
					$pageContent .= $pageEnd;
				} else {
					break;
				}
			}
		}
		
		return $pageContent;
	}
	
	/**
	 * Parse whole source and combine everything
	 * !! NOT RECOMMENDED !!
	 * This is very inefficient and might also produce very
	 * large js/css files that take forever to load
	 */
	public function combinePage($pageContent, $opts = array()) {
		// Find head
		preg_match_all('/(.*<\s*head[^>\S]*>)(.*?)(<\s*\/head.+)/is', $pageContent, $matches);
		if (sizeof($matches[0]) < 1) {
			// No <head> specified in the DOM
			return $pageContent;
		}
		$start = $matches[1][0];
		$head = $matches[2][0];
		$end = $matches[3][0];
	
		// Find and remove head scripts
		$head = $this->_runCombine($head, $opts);
		
		// Find body
		preg_match_all('/(.*<\s*body[^>]*>)(.*)(<\s*\/body.+)/is', $end, $matches);
		if (sizeof($matches[0]) < 1) {
			// No <body> specified in the DOM
			return $start . $head . $end;
		}
		$endHead = $matches[1][0];
		$body = $matches[2][0];
		$end = $matches[3][0];
		
		// Find and remove body scripts
		$body = $this->_runCombine($body, $opts);
		
		return $start . $head . $endHead . $body . $end;
	}

	private function _runCombine($content, $opts = array()) {
		// Parse all conditional statements out of the $content
		$regex = '<\!--\[if[^\]]*IE( [\d]+)?\]>([^\!]*)<\!\[endif\]-->';
		preg_match_all('/[\t]*' . $regex . '/i', $content, $matches);
		$conditional = $matches[0];
		if (sizeof($conditional) > 0) {
			$content = preg_replace('/[\n\r\t]*' . $regex . '[\n\r\t]*/i', "", $content);
		}

		$content = $this->_comjs->combine($content, $opts);
		$content = $this->_comcss->combine($content, $opts);
		
		// Append all conditional statements in the end of the $content
		foreach ($conditional as $cond) {
			$content .= "\n" . $cond;
		}
		
		//var_dump($content);
		//exit;
		
		return $content;
	}

	public function minifyAndCombine($pageContent, $opts = array()) {
		$pageContent = $this->combine($pageContent, $opts);
		return $this->minify($pageContent);
	}
	
}

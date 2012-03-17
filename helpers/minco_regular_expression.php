<?php
class MincoRegularExpressionHelper {
	
	private $_replaces = array();
	
	public function __construct() {
		$arr = array("/", "$", "^", "[", "]", "(", ")", ".", "?", "+");
		foreach ($arr as $str) {
			$this->_replaces[$str] = "\\".$str;
		}
	}
	
	public function prepareString($string) {
		return str_replace(
			array_keys($this->_replaces), 
			array_values($this->_replaces), 
			$string
		);
	}
	
}

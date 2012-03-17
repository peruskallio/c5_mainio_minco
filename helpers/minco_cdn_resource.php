<?php
class MincoCdnResourceHelper {
	
	private $_resources = array(
		'/concrete/js/jquery.js' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js',
		'/concrete/css/jquery.ui.js' => 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js'
	);
	
	public function getLink($resource) {
		if (MINCO_USE_CDN_RESOURCES) {
			if (strlen(DIR_REL) > 0 && ($pos = strpos($resource, DIR_REL)) !== false) {
				$resource = substr($resource, 0, $pos) . substr($resource, $pos+strlen(DIR_REL));
			}
			if (array_key_exists($resource, $this->_resources)) {
				return $this->_resources[$resource];
			}
		}
		return null;
	}
	
}

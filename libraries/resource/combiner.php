<?php
class MincoResourceCombiner {
	
	protected $_resourceFileExtension = '';
	
	public function __construct($ext) {
		$this->_resourceFileExtension = $ext;
	}
	
	/**
	 * Should return the resource link for the specified resource key.
	 * 
	 */
	protected function _resourceLink($key, $v = false, $saveFile = false) {
		return '<!-- implement resource link: ' . $key . ' -->';
	}
	
	protected function _resourceLinkExternal($link) {
		return $this->_resourceLink($link);
	}
	
	/**
	 * Should return inline resource tag with the specified inline resources.
	 * 
	 */
	protected function _inlineResources($resources) {
		return '<!-- implement inline resources for type: ' . $this->_resourceFileExtension . ' -->';
	}
	
	/**
	 * The returned array should contain the following:
	 * Index 0: contains an array of tag matches for the linked resources
	 * Index 1: contains an array of the original resource links to the linked resources
	 * 
	 */
	protected function _getLinkedResourceMatches($content) {
		return array(
			array(), array()
		);
	}
	
	/**
	 * The returned array should contain the following:
	 * Index 0: contains an array of tag matches for the inline resources
	 * Index 1: contains an array of the inline contents inside the specified tags
	 * 
	 * Appended before the linked and combined resource
	 */
	protected function _getInlineResourceMatchesStart($content) {
		return array(
			array(), array()
		);
	}
	/**
	 * Appended after the linked and combined resource
	 */
	protected function _getInlineResourceMatchesEnd($content) {
		return array(
			array(), array()
		);
	}
	
	private function _replaceOriginalResource($content, $str) {
		$regstr = Loader::helper("minco_regular_expression", "mainio_minco")->prepareString($str);
		$reg = '/[\n\r\t]*' . $regstr . '[\n\r\t]*/si';
		return preg_replace($reg, "", $content, 1);
	}
	
	final public function combine($content, $opts = array()) {
		$matches = $this->_getLinkedResourceMatches($content);
		if (sizeof($matches) > 1) {
			$srcs = $matches[1];
			foreach ($matches[0] as $k => $ma) {
				$src = str_replace(BASE_URL, "", $srcs[$k]);
				$replace = false;
				if (strpos($src, "http://") !== false || strpos($src, "." . $this->_resourceFileExtension) === false) {
					unset($srcs[$k]);
				} else if (strpos($src, "index.php") !== false) {
					unset($srcs[$k]);
					$tools = DIR_REL . '/' . DISPATCHER_FILENAME . '/' . DIRNAME_TOOLS;
					
					if (strpos($src, $tools) !== false) {
						// This file is run through concrete5 tools functionality
						// so we need to load it manually
						if (strlen(DIR_REL) > 0 && ($pos = strpos($src, DIR_REL)) !== false) {
							// Replace only first occurence of DIR_REL
							$s = $pos+strlen(DIR_REL);
							$src = substr($src, 0, $pos) . substr($src, $s);
						}
						$saveFile = md5($src) . "." . $this->_resourceFileExtension;
						
						$exists = true;
						if (!file_exists($saveFile)) {
							$exists = false;
							
							$url = BASE_URL . DIR_REL . $src;
							$cont = @file_get_contents($url);
							
							if ($cont !== false) {
								if ($fh = @fopen(MINCO_RESOURCES_SAVE_DIR . '/' . $saveFile, 'w')) {
									fwrite($fh, $cont);
									fclose($fh);
									$exists = true;
								} else {
									throw new Exception(t("Mainio MinCo resources save dir cannot be written in! Plese check file permissions."));
								}
							}
						}
						if ($exists) {
							$srcs[$k] = MINCO_RESOURCES_SAVE_DIR_REL . '/' . $saveFile;
							$replace = true;
						}
					}
				} else {
					$srcs[$k] = $src;
					$replace = true;
				}
				if ($replace) {
					$content = $this->_replaceOriginalResource($content, $ma);
				}
			}
		}

		$inlineMatchesStart = $this->_getInlineResourceMatchesStart($content);
		$inlineResourcesStart = $inlineMatchesStart[1];
		$inlineMatchesEnd = $this->_getInlineResourceMatchesEnd($content);
		$inlineResourcesEnd = $inlineMatchesEnd[1];
		// Remove all the original inline resources
		foreach ($inlineMatchesStart[0] as $k => $ma) {
			$content = $this->_replaceOriginalResource($content, $ma);
		}
		foreach ($inlineMatchesEnd[0] as $k => $ma) {
			$content = $this->_replaceOriginalResource($content, $ma);
		}
		
		// Clean out the external resource strings
		foreach ($srcs as $i => $src) {
			// Cut to question mark
			if (($pos = strpos($src, "?")) !== false) {
				$src = substr($src, 0, $pos);
			}
			$srcs[$i] = $src;
		}
		
		// Append the combined resources
		if (sizeof($inlineResourcesStart) > 0) {
			// Put all the available inline resources above the external resources
			$content .= $this->_inlineResources($inlineResourcesStart);
		}
		if (sizeof($srcs) > 0) {
			$cdn = Loader::helper("minco_cdn_resource", "mainio_minco");
			foreach ($srcs as $k => $src) {
				if (($res = $cdn->getLink($src))) {
					unset($srcs[$k]);
					$content .= $this->_resourceLinkExternal($res);
				}
			}
			$key =  md5(implode(',', $srcs));
			Loader::helper("minco_file_list", "mainio_minco")->setFileResourceArray($key, $srcs);
			
			$v = isset($opts['resourceVersion']) ? trim($opts['resourceVersion']) : false;
			$saveResourceFile = isset($opts['writeFiles']) ? $opts['writeFiles'] : false;
			$content .= $this->_resourceLink($key, $v, $saveResourceFile);
		}
		if (sizeof($inlineResourcesEnd) > 0) {
			// Put all the available inline resources below the external resources
			$content .= $this->_inlineResources($inlineResourcesEnd);
		}
		
		return $content;
	}
	
}

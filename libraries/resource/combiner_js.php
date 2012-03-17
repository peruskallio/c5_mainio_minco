<?php
Loader::library("resource/combiner", "mainio_minco");
class MincoResourceCombinerJs extends MincoResourceCombiner {
	
	public function __construct() {
		parent::__construct("js");
	}
	
	protected function _resourceLink($key, $v = false, $saveFile = false) {
		$mr = Loader::helper("minco_resource", "mainio_minco");
		$url = null;
		if ($saveFile) {
			$url = $mr->save($key, $v, $this->_resourceFileExtension);
		} else {
			$url = $mr->url($key, $v);
		}
		return $mr->javascript($url);
	}
	
	protected function _resourceLinkExternal($link) {
		return Loader::helper("minco_resource", "mainio_minco")->javascript($link);
	}
	
	protected function _inlineResources($resources) {
		$res = "";
		foreach ($resources as $is) {
			$res .= $is;
		}
		$res = trim($res);
		
		$content = "";
		if (strlen($res) > 0) {
			if (MINCO_MINIFY_INLINE) {
				Loader::library("3rdparty/minify-2.1.5/min/lib/JSMin", "mainio_minco");
				$res = JSMin::minify($res);
			}
			$content = '<script type="text/javascript">';
			$content .= $res;
			$content .= '</script>';
		}
		return $content;
	}
	
	protected function _getLinkedResourceMatches($content) {
		preg_match_all('/<script[^>]*src=["\']([^"\'>]+)["\'][^>]*><\/script>/i', $content, $matches);
		return $matches;
	}
	
	/**
	 * This cannot be straight/easily with a single regular expression
	 * so it's just easier to parse through the content manually.
	 * This is basically because negative matches in regular expressions
	 * are somewhat hard to do in matching pattern which makes finding
	 * the <script> tag inline content </script> quite hard without
	 * just matching a single character at the end of it.
	 * 
	 */
	protected function _findInlineJs($content) {
		$tags = array();
		$scripts = array();
		
		$offset = 0;
		while (($s = strpos($content, '<script', $offset)) !== false) {
			$ss = strpos($content, '>', $s);
			if ($ss === false) {
				break;
			}
			$ss++;
			
			$es = strpos($content, '</script>', $ss);
			if ($es === false) {
				break;
			}
			$e = $es+strlen('</script>');
			
			$src = strpos($content, 'src=', $s);
			if ($src === false || $src > $ss) {
				$script = substr($content, $ss, $es-$ss);
				if (strlen(trim($script)) > 0) {
					$tags[] = substr($content, $s, $e-$s);
					$scripts[] = $script;
				}
			}
			
			$offset = $es;
		}
		
		return array(
			$tags,$scripts
		);
	}
	
	protected function _getInlineResourceMatchesStart($content) {
		$matches = $this->_findInlineJs($content);
		foreach ($matches[1] as $key => $ma) {
			if (!preg_match('/.*var CCM_.*/is', $ma)) {
				foreach ($matches as $k => $m) {
					unset($matches[$k][$key]);
				}
			}
		}
		return $matches;
	}
	
	protected function _getInlineResourceMatchesEnd($content) {
		$matches = $this->_findInlineJs($content);
		foreach ($matches[1] as $key => $ma) {
			if (preg_match('/.*var CCM_.*/is', $ma)) {
				foreach ($matches as $k => $m) {
					unset($matches[$k][$key]);
				}
			}
		}
		return $matches;
	}
	
}

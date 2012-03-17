<?php
Loader::library("resource/combiner", "mainio_minco");
class MincoResourceCombinerCss extends MincoResourceCombiner {
	
	public function __construct() {
		parent::__construct("css");
	}
	
	protected function _resourceLink($key, $v = false, $saveFile = false) {
		$mr = Loader::helper("minco_resource", "mainio_minco");
		$url = null;
		if ($saveFile) {
			$url = $mr->save($key, $v, $this->_resourceFileExtension);
		} else {
			$url = $mr->url($key, $v);
		}
		return $mr->css($url);
	}
	
	protected function _resourceLinkExternal($link) {
		return Loader::helper("minco_resource", "mainio_minco")->css($link);
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
				Loader::library("3rdparty/minify-2.1.5/min/lib/Minify/CSS", "mainio_minco");
				$res = Minify_CSS::minify($res);
			}
			$content = '<style type="text/css">';
			$content .= $res;
			$content .= '</style>';
		}
		return $content;
	}
	
	protected function _getLinkedResourceMatches($content) {
		preg_match_all('/<link[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
		foreach ($matches[0] as $key => $link) {
			if (!preg_match('/rel=[\'"](stylesheet|STYLESHEET|StyleSheet|styleSheet)[\'" ]/', $link)) {
				foreach ($matches as $k => $m) {
					unset($matches[$k][$key]);
				}
			}
		}
		return $matches;
	}
	
	protected function _getInlineResourceMatchesStart($content) {
		preg_match_all('/(<style[^>]*type=["\']?text\/css["\']?[^>]*>)([^>]+)(<\/style>)/i', $content, $matches);
		return array(
			$matches[0],$matches[2]
		);
	}
	
}

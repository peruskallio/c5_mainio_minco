<?php
class MincoResourceHelper {
	
	public function url($resourceKey, $resourceVersion = false) {
		$url = MINCO_TOOL_URL . '?k=' . $resourceKey;
		if (is_string($resourceVersion) && strlen($resourceVersion) > 0) {
			$url .= "&v=" . $resourceVersion;
		}
		return $url;
	}
	
	public function save($resourceKey, $resourceVersion = false, $fileExtension = '') {
		$dirname = 'mainio_minco_combined_assets';
		$url = MINCO_RESOURCES_SAVE_DIR_REL . '/' . $dirname;
		$saveDir = MINCO_RESOURCES_SAVE_DIR . '/' . $dirname;
		
		Loader::library("minco_file_writer", "mainio_minco");
		$file = MincoFileWriter::writeStatic($resourceKey, $resourceVersion, $fileExtension, $saveDir);
		
		return $url . '/' . $file;
	}
	
	public function javascript($url) {
		return '<script type="text/javascript" src="' . $url . '"></script>';
	}
	
	public function css($url, $media = false) {
		$output = '<link rel="stylesheet" type="text/css"';
		if (strlen($media) > 0) {
			$output .= ' media="' . $media . '"';
		}
		$output .= ' href="' . $url . '" />';
		return $output;
	}
	
}

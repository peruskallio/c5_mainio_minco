<?php
class MincoFileListHelper {
	
	private static $_resources;
	
	public function __construct() {
		$minifyFilesList = array();
		if (file_exists(MINCO_LISTS_FILE)) {
			require MINCO_LISTS_FILE;
		}
		self::$_resources = $minifyFilesList;
	}
	
	public function addFileResource($key, $f) {
		if (!is_array(self::$_resources[$key])) {
			self::$_resources[$key] = array();
		}
		self::$_resources[$key][] = $f;
	}
	
	public function setFileResourceArray($key, $arr) {
		if (is_array($arr)) {
			self::$_resources[$key] = $arr;
		}
	}
	
	public function save() {
		$minifyFilesList = array();
		if (file_exists(MINCO_LISTS_FILE)) {
			require MINCO_LISTS_FILE;
		}
		// Only save if new keys are found
		$save = false;
		foreach (self::$_resources as $key => $list) {
			if (!array_key_exists($key, $minifyFilesList)) {
				$save = true;
				$minifyFilesList[$key] = $list;
			}
		}
		if ($save && is_array($minifyFilesList) && sizeof($minifyFilesList) > 0) {
			if ($fh = @fopen(MINCO_LISTS_FILE, 'w')) {
				$content = "<?php " . PHP_EOL . "\$minifyFilesList = array(";
				$i = 0;
				foreach ($minifyFilesList as $key => $list) {
					if ($i > 0) {
						$content .= ",";
					}
					$content .= PHP_EOL . "\t'".$key."' => array(";
					$j = 0;
					foreach ($list as $file) {
						if ($j > 0) {
							$content .= ",";
						}
						$content .= '"'.$file.'"';
						$j++;
					}
					$content .= ")";
					$i++;
				}
				$content .= PHP_EOL . ");";
				@fwrite($fh, $content);
				@fclose($fh);
			} else {
				throw new Exeption(t("Cannot write to the Mainio MinCo file lists file! Please check file permissions."));
			}
		}
	}
	
}

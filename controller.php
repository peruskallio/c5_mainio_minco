<?php
defined('C5_EXECUTE') or die(_("Access Denied."));

class MainioMincoPackage extends Package {

	protected $pkgHandle = 'mainio_minco';
	protected $appVersionRequired = '5.5.0';
	protected $pkgVersion = '0.5';
	
	public function getPackageName() {
		return t("Mainio MinCo");
	}
	
	public function getPackageDescription() {
		return t("Minify & Combine static assets to optimize your site's performance.");
	}
	
	public function install() {
		$pkg = parent::install();
	}
	
	public function on_start() {
		// Minifier enabled, start the process
		$uh = Loader::helper("concrete/urls");
		define('MINCO_TOOL_URL', $uh->getToolsUrl('min', $this->pkgHandle));
		define('MINCO_LISTS_FILE', DIR_CONFIG_SITE . '/' . $this->pkgHandle . '.php');
		define('MINCO_MINIFY_LIB_DIR', DIR_PACKAGES . '/mainio_minco/' . DIRNAME_LIBRARIES . '/3rdparty/minify-2.1.5/min/lib');
		// Minifier requires this one because of its internal inclusions
		set_include_path(MINCO_MINIFY_LIB_DIR . PATH_SEPARATOR . get_include_path());
		
		if (!defined('DIR_TMP')) {
			define('DIR_TMP', Loader::helper('file')->getTemporaryDirectory());
		}
		if (!defined('MINCO_RESOURCES_SAVE_DIR')) {
			define('MINCO_RESOURCES_SAVE_DIR', DIR_TMP);
		}
		if (!defined('MINCO_RESOURCES_SAVE_DIR_REL')) {
			define('MINCO_RESOURCES_SAVE_DIR_REL', str_replace(DIR_BASE, DIR_REL, MINCO_RESOURCES_SAVE_DIR));
		}
		if (!defined('MINCO_USE_CDN_RESOURCES')) {
			define('MINCO_USE_CDN_RESOURCES', true);
		}
		
		if (!defined('MINCO_BYPASS_CACHE')) {
			define('MINCO_BYPASS_CACHE', false);
		}
		if (!defined('MINCO_CLIENT_CACHE')) {
			if (MINCO_BYPASS_CACHE) {
				define('MINCO_CLIENT_CACHE', false);
			} else {
				define('MINCO_CLIENT_CACHE', true);
			}
		}
		
		if (!defined('MINCO_MINIFY_HTML')) {
			define('MINCO_MINIFY_HTML', false);
		}
		if (!defined('MINCO_MINIFY_INLINE')) {
			if (MINCO_MINIFY_HTML) {
				define('MINCO_MINIFY_INLINE', false);
			} else {
				define('MINCO_MINIFY_INLINE', true);
			}
		}
		
		// To enable users calling the block start and end functions
		// inside the templates
		Loader::library("minco_block", "mainio_minco");
		
		$req = Request::get();
		if (MINCO_MINIFY_HTML && strpos($req->getRequestPath(), "dashboard") === false) {
			// Bind events to minify output HTML
			Events::extend('on_before_render', get_class(), 'min_html_start', DIR_PACKAGES . '/' . $this->pkgHandle . '/controller.php');
			Events::extend('on_render_complete', get_class(), 'min_html_end', DIR_PACKAGES . '/' . $this->pkgHandle . '/controller.php');
		}
	}
	
	public static function min_html_start() {
		ob_start();
	}
	
	public static function min_html_end() {
		$pageContent = ob_get_contents();
		ob_end_clean();
		
		Loader::library("minco", "mainio_minco");
		$m = Minco::getInstance();
		echo $m->minify($pageContent);
	}

}
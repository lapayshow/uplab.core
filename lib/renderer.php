<?

namespace Uplab\Core;


use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Loader_Filesystem;


/**
 * @property Twig_Environment twig
 * @property array            loaderPaths
 * @property array            namespacedPaths
 */
class Renderer
{
	use Traits\SingletonTrait;

	public  $twig;
	private $cacheFolder     = "";
	private $loaderPaths     = [];
	private $namespacedPaths = [];
	private $renderParams    = [];

	/**
	 * Инициализируются дефолтные параметры, которые могут быть в любой момент изменены через Singleton
	 * примерно так:
	 * Renderer::getInstance()->setRenderParams(...) // Переменные, которые будут переданы в шаблон
	 * Renderer::getInstance()->setLoaderPaths(...)  // Пути, которых будут искаться подключаемые файлы
	 */
	public function __construct()
	{
		$this->cacheFolder = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/cache/twig_cache";

		$this->setRenderParams([
			"assetsRoot" => SITE_TEMPLATE_PATH . "/frontend/src/",
		]);

		$this->setLoaderPaths([
			Application::getDocumentRoot() . SITE_TEMPLATE_PATH . "/frontend/src/",
			Application::getDocumentRoot(),
		]);
	}

	public static function bindEvents()
	{
		self::addCustomTemplateEngine();
	}

	public static function render($name, $params = [])
	{
		$twig = self::getInstance()->getTwig();

		// d($params, $name);
		return $twig->render($name, array_merge(
			(array)self::getInstance()->getRenderParams(),
			(array)$params
		));
	}

	public static function renderString($template, $params = [])
	{
		return self::getInstance()->getTwig()
			->createTemplate($template)
			->render($params);
	}

	public static function clearCache()
	{
		$dir = self::getInstance()->getCacheFolder();
		if (is_dir($dir)) {
			Directory::deleteDirectory($dir);
		}
	}

	public static function addCustomTemplateEngine()
	{
		global $arCustomTemplateEngines;

		$arCustomTemplateEngines["twig"] = [
			"templateExt" => ["twig"],
			"function"    => "renderUplabCoreTemplateFile",
		];
	}

	/**
	 * @return string
	 */
	public function getCacheFolder()
	{
		return $this->cacheFolder;
	}

	/**
	 * @return Twig_Environment
	 */
	public function getTwig()
	{
		if (!is_object($this->twig)) $this->initTwig();

		return $this->twig;
	}

	/**
	 * @return Twig_Environment
	 */
	public function initTwig()
	{
		unset($this->twig);

		$loader = new Twig_Loader_Filesystem();

		foreach ($this->loaderPaths as $namespace => $path) {
			if (file_exists($path)) {
				if (is_numeric($namespace)) {
					$loader->addPath($path);
				} else {
					$loader->addPath($path, $namespace);
				}
			}
		}

		$this->twig = new Twig_Environment($loader, [
			"cache"      => Helper::isDevMode()
				? false
				: $this->cacheFolder,
			"debug"      => Helper::isDevMode(),
			"autoescape" => false,
		]);

		$this->twig->addExtension(new Twig_Extension_Debug);
		$this->twig->addExtension(new Renderer\RendererExtension);

		return $this->twig;
	}

	/**
	 * @param array $loaderPaths
	 */
	public function setLoaderPaths($loaderPaths)
	{
		$this->loaderPaths = $loaderPaths;
		$this->initTwig();
	}

	/**
	 * @param array $renderParams
	 *
	 * @return Renderer
	 */
	public function setRenderParams(array $renderParams)
	{
		$this->renderParams = array_merge(
			(array)$this->renderParams,
			(array)$renderParams
		);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getRenderParams()
	{
		return $this->renderParams;
	}

}

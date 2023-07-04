<?

namespace Uplab\Core\Renderer;


use Bitrix\Main\Localization\Loc;
use Twig_Extension;
use Twig_Extension_GlobalsInterface;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Uplab\Core\Data\StringUtils;


class RendererExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface
{
	public function getName()
	{
		return 'bitrix';
	}

	/**
	 * Возвращает список глобльных переменных, которые будут доступны в шаблоне после добавления данного расширения
	 *
	 * @return array
	 */
	public function getGlobals()
	{
		global $APPLICATION;

		return [
			'application'       => $APPLICATION,
			'postFormActionUri' => POST_FORM_ACTION_URI,
			'__request'         => $_REQUEST,
			'siteServerName'    => SITE_SERVER_NAME,
			"languageId"        => LANGUAGE_ID,
			"siteDir"           => SITE_DIR,
			'siteId'            => SITE_ID,
			"siteTemplatePath"  => SITE_TEMPLATE_PATH,
			"curPage"           => $APPLICATION->GetCurPage(false),
			"curDir"            => $APPLICATION->GetCurDir(),
		];
	}

	/**
	 * Возвращает список функций, которые будут доступны в шаблоне после добавления данного расширения
	 *
	 * @return array
	 */
	public function getFunctions()
	{
		return [
			new Twig_SimpleFunction('showMessage', [$this, 'showMessage'], ['message']),
			new Twig_SimpleFunction('bitrixSessidPost', [$this, 'bitrixSessidPost']),
			new Twig_SimpleFunction('bitrixSessidGet', [$this, 'bitrixSessidGet']),
			new Twig_SimpleFunction('showError', [$this, 'showError'], ['message', 'css_class']),
			new Twig_SimpleFunction('showNote', [$this, 'showNote'], ['message', 'css_class']),
			new Twig_SimpleFunction('isUserAdmin', [$this, 'isUserAdmin']),
			new Twig_SimpleFunction('isUserAuthorized', [$this, 'isUserAuthorized']),
			new Twig_SimpleFunction('getLocMessage', [$this, 'getLocMessage']),
			new Twig_SimpleFunction('includeFile', [$this, 'includeFile']),
			new Twig_SimpleFunction('includeComponent', [$this, 'includeComponent']),
			new Twig_SimpleFunction('d', 'd'),
		];
	}

	/**
	 * Возвращает список фильтров, которые будут доступны в шаблоне после добавления данного расширения
	 *
	 * @return array
	 */
	public function getFilters()
	{
		return [
			new Twig_SimpleFilter('formatDate', [$this, 'formatDate'], ['rawDate', 'format']),
			new Twig_SimpleFilter('clearPhone', [StringUtils::class, 'clearPhone'], ['phone']),
			new Twig_SimpleFilter('arrayFilter', "array_filter", ['input']),
			// new \Twig_SimpleFilter('russianPluralForm', [$this, 'russianPluralForm'], ['string', 'count', 'delimiter']),
		];
	}

	public function getTokenParsers()
	{
		return [
			new View\ViewTokenParser(),
			new Svg\SvgTokenParser(),
		];
	}

	//функции, которые используются как функции в твиге
	public function showMessage($message)
	{
		ShowMessage($message);
	}

	public function showError($message, $css_class = "errortext")
	{
		ShowError($message, $css_class);
	}

	public function showNote($message, $css_class = "notetext")
	{
		ShowNote($message, $css_class);
	}

	public function bitrixSessidPost()
	{
		return bitrix_sessid_post();
	}

	public function bitrixSessidGet()
	{
		return bitrix_sessid_get();
	}

	public function getLocMessage($code, $replace = null, $language = null)
	{
		return Loc::getMessage($code, $replace, $language);
	}

	public function includeFile(...$args)
	{
		global $APPLICATION;

		call_user_func_array([$APPLICATION, "IncludeFile"], $args);
	}
	public function includeComponent(...$args)
	{
		global $APPLICATION;

		call_user_func_array([$APPLICATION, "IncludeComponent"], $args);
	}

	public function isUserAdmin()
	{
		global $USER;

		return $USER->IsAdmin();
	}

	public function isUserAuthorized()
	{
		global $USER;

		return $USER->IsAuthorized();
	}

	//функции, которые используются как фильтры в твиге
	public function formatDate($rawDate, $format = 'FULL')
	{
		return FormatDateFromDB($rawDate, $format);
	}
}
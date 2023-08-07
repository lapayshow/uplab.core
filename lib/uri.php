<?

namespace Uplab\Core;


use Bitrix\Main\HttpRequest;
use Bitrix\Main\Web\Uri as BxUri;


/**
 * Наследует класс Bitrix\Main\Web\Uri, расширяя его возможности
 * Главое отличие в способе работы с параметрами. Оригинальный класс
 * работает с параметрами как со строкой. Данный класс сохраняет параметры
 * в массив, за счет чего работа с ними становится более гибкой
 *
 * Class Uri
 *
 * @package Uplab\Core
 */
class Uri extends BxUri
{
    protected $queryArray = [];

    /**
     * Если true, то после построения ссылки, будет произведена попытка исправить
     * некоторые закодированные символы, например # (это нужно для заменяемых значений в ссылке)
     *
     * @var bool
     */
    protected $fixEncodedCharactersInUri = false;

    public function __construct($url)
    {
        parent::__construct($url);

        if (!empty($this->query)) {
            parse_str($this->query, $this->queryArray);
        }

        $this->path = preg_replace("~&$~", "", $this->path);
    }

    public static function init($url = null)
    {
        global $APPLICATION;

        if (!isset($url)) {
            $url = $APPLICATION->GetCurPage(false);
        }

        return new  static($url);
    }

    public static function initWithRequestUri()
    {
        return self::init($_SERVER["REQUEST_URI"]);
    }

    public static function initWithCurPage($getIndex = false)
    {
        global $APPLICATION;

        return self::init($APPLICATION->GetCurPage($getIndex));
    }

    public function getQueryArray()
    {
        return $this->queryArray;
    }

    /**
     * В отличие от BxUri::addParams(),
     * не добавляет новые параметры, а заменяет значения для тех,
     * которые уже есть у объекта. Те параметры из массива, которых
     * в объекте не было, будут проигнорированы.
     *
     * @param array $params
     *
     * @return self
     */
    public function updateParams(array $params)
    {
        if (!empty($params) && !empty($this->queryArray)) {
            $params = (array)$params;

            foreach ($params as $key => $param) {
                if (array_key_exists($key, $this->queryArray)) {
                    $this->queryArray[$key] = $param;
                }
            }
        }

        // $this->buildQuery();

        return $this;
    }

    /**
     * @param array $params
     * @param bool $preserveDots Special treatment of dots and spaces in the parameters names.
     * @return self
     */
    public function addParams(array $params, $preserveDots = false)
    {
        $this->queryArray = (array)$this->queryArray;
        if (!empty($params)) {
            $params = (array)$params;

            $this->queryArray = array_replace($this->queryArray, $params);
        }

        // $this->buildQuery();

        return $this;
    }

    public function addSessId($key = "sessid")
    {
        $this->addParams([$key => bitrix_sessid()]);

        return $this;
    }

    /**
     * @param array $params
     * @param bool $preserveDots Special treatment of dots and spaces in the parameters names.
     * @return self
     */
    public function deleteParams(array $params, $preserveDots = false)
    {
        if (!empty($params) && !empty($this->queryArray)) {
            $params = (array)$params;

            foreach ($params as $param) {
                unset($this->queryArray[$param]);
            }
        }

        return $this;
    }

    public function removeParams(array $params)
    {
        return $this->deleteParams($params);
    }

    public function deleteSystemParams()
    {
        $this->deleteParams(HttpRequest::getSystemParameters());

        return $this;
    }

    public function filterParams($diffValues = null)
    {
        if (empty($diffValues) || !is_array($diffValues)) {
            $diffValues = ["", "*"];
        }

        if (!empty($this->queryArray)) {
            $this->queryArray = array_diff($this->queryArray, $diffValues);
        }

        // $this->buildQuery();

        return $this;
    }

    /**
     * Остаются только параметры, перечисленные в массиве.
     * Остальные параметры удаляются.
     *
     * @param array $params
     *
     * @return self
     */
    public function whiteListParams(array $params)
    {
        if (!empty($params) && !empty($this->queryArray)) {
            $params = (array)$params;

            $this->queryArray = array_intersect_key(
                (array)$this->queryArray,
                array_flip((array)$params)
            );
        }

        return $this;
    }


    /**
     * По умолчанию значнения фильтруются перед построением ссылки.
     * Подробнее о фильтрации в описаниии к методу filterParams()
     * $filterParams может быть массивом, в этом случае можно указать значения,
     * которые следует удалить из ссылки.
     *
     * @param bool|array $filterParams
     *
     * @return string
     */
    public function getUri($filterParams = true)
    {
        if (!empty($filterParams)) {
            $this->filterParams($filterParams);
        }

        $this->buildQuery();

        $uri = parent::getUri();

        if ($this->fixEncodedCharactersInUri) {
            $uri = str_replace(
                ["%23"],
                ["#"],
                $uri
            );
        }

        return $uri;
    }

    /**
     * @param array $queryArray
     *
     * @return Uri
     */
    public function setQueryArray($queryArray)
    {
        $this->queryArray = $queryArray;

        return $this;
    }

    /**
     * Метод нужен, чтобы скорректировать тип возвращаемого значения в return
     *
     * @param string $path
     *
     * @return $this|BxUri
     */
    public function setPath($path)
    {
        return parent::setPath($path);
    }

    /**
     * @return Uri
     */
    public function setFixEncodedCharactersInUri()
    {
        $this->fixEncodedCharactersInUri = true;

        return $this;
    }

    private function buildQuery()
    {
        $this->query = http_build_query($this->queryArray, "", "&");
    }

}
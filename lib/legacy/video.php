<?

namespace Uplab\Core\Legacy;


use Uplab\Core\Data\StringUtils;


/**
 * Порядок действий: инициализировать объект ссылкой, получить на основе этой ссылки данные
 *
 * Пример:
 * $videoData = \Uplab\Core\Legacy\Video::init("...")->getVideoData();
 */
class Video
{
	protected $src;
	protected $data;
	private   $id;

	/**
	 * Video constructor.
	 *
	 * @param $src
	 */
	public function __construct($src)
	{
		$this->src = $src;
		$this->prepareVideoInfo();
	}

	/**
	 * @param $src
	 *
	 * @return static
	 */
	public static function init($src)
	{
		return new static($src);
	}

	/**
	 * @return mixed
	 * @noinspection PhpUnused
	 */
	public function getVideoData()
	{
		switch ($this->data["service"]) {
			case "youtube":
				$this->getYoutubeData();
				break;

			case "vimeo":
				$this->getVimeoData();
				break;

			default:
				break;
		}

		return $this->data;
	}

	public function getData()
	{
		return $this->data;
	}

	/**
	 * @noinspection PhpUnused
	 */
	public function printData()
	{
		print_r($this->data);
	}

	protected function getService()
	{
		$data = array(
			"youtube" => array("youtube", "youtu.be"),
			"vimeo"   => array("vimeo"),
		);
		foreach ($data as $service => $subStrings) {
			foreach ($subStrings as $str) {
				if (stripos($this->src, $str) !== false) {
					return $service;
				}
			}
		}

		return false;
	}

	protected function prepareVideoInfo()
	{
		$service = $this->getService();

		if (!$service) return false;

		preg_match("~[/=]([^/=&]+)$~", $this->src, $match);

		$id = $match[1];
		$res = compact("service", "id");
		$this->data = $res;
		$this->id = $id;

		return $id;
	}

	protected function getYoutubeData()
	{
		$id = $this->data["id"];
		if (!$id) return false;

		$this->data["picture"] = $this->getYoutubePreview();
		$url = "http://www.youtube.com/get_video_info?video_id={$this->data["id"]}&el=embedded";

		parse_str(file_get_contents($url), $data);

		$this->data["time"] = gmdate("H:i:s", $data["length_seconds"]);

		$this->data["title"] = $data["title"];
		$this->data["code"] = StringUtils::translit($data["title"]);
		$this->data["tags"] = $data["keywords"];

		// $this->data["src"] = $data;

		$this->getYoutubeVideoUrl($data["url_encoded_fmt_stream_map"]);

		return true;
	}

	protected function getYoutubeVideoUrl($streamMap, $quality = false, $videoFormat = false)
	{
		$arStreams = explode(",", $streamMap);

		foreach ($arStreams as $stream) {
			parse_str($stream, $streamData);

			preg_match("~video\/(.+)\;~", $streamData["type"], $format);
			$format = $format[1];

			if ($quality !== false && $streamData["quality"] != $quality) continue;
			if ($videoFormat !== false && $format != $videoFormat) continue;

			$this->data["format"] = $format;

			return ($this->data["url"] = $streamData["url"]);
		}

		return false;
	}

	/**
	 * returns largest avalaible video preview url
	 */
	protected function getYoutubePreview()
	{
		$id = $this->id;
		if (!$id) return false;

		$url = "";

		$resolution = array(
			'maxresdefault',
			'sddefault',
			'0',
			'mqdefault',
			'hqdefault',
			'default',
		);

		foreach ($resolution as $res) {
			$url = "http://img.youtube.com/vi/{$id}/{$res}.jpg";
			if (get_headers($url)[0] == 'HTTP/1.0 200 OK') return $url;
		}

		return $url;
	}

	/**
	 * returns largest avalaible video preview url
	 */
	protected function getVimeoData()
	{
		$id = $this->id;
		if (!$id) return false;

		$url = "http://vimeo.com/api/v2/video/{$id}.php";
		$data = unserialize(file_get_contents($url))[0];
		$resolution = array(
			"thumbnail_large",
			"thumbnail_medium",
			"thumbnail_small",
		);
		foreach ($resolution as $res) {
			if ($picture = $data[$res]) break;
		}

		if (!empty($picture)) {
			$this->data["picture"] = $picture;
		}

		$this->data["time"] = gmdate("H:i:s", $data["duration"]);

		$this->data["title"] = $data["title"];
		$this->data["code"] = StringUtils::translit($data["title"]);
		$this->data["tags"] = $data["tags"];
		$this->data["description"] = $data["description"];

		// $this->data["src"] = $data;

		return true;
	}

}
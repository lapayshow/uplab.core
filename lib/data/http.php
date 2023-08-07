<?php

namespace Uplab\Core\Data;


class Http
{

	/**
	 * Осуществляет POST-запрос
	 * Чтобы переопределить параметры, нужно передать в массив $options
	 * значения с ключами CURLOPT_*
	 *
	 * @param       $url
	 * @param array $options
	 *
	 * @return array
	 */
	public static function makePostRequest($url, $options = [])
	{
		$defaultOptions = array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,   // return web page
			CURLOPT_HEADER         => false,  // don't return headers
			CURLOPT_FOLLOWLOCATION => true,   // follow redirects
			CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
			CURLOPT_ENCODING       => "",     // handle compressed
			CURLOPT_USERAGENT      => "", // name of client
			CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
			CURLOPT_TIMEOUT        => 120,    // time-out on response
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => [],
			CURLOPT_HTTPHEADER     => [],
			// CURLOPT_USERPWD        => "upl:upl",
		);

		foreach ($options as $key => $value) $defaultOptions[$key] = $value;

		$ch = curl_init($url);
		curl_setopt_array($ch, $defaultOptions);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		return compact("response", "httpCode");
	}

}
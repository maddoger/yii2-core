<?php

namespace maddoger\core;

use Yii;
use yii\web\UrlRule;
use yii\web\UrlManager;

class LanguageUrlManager extends UrlManager
{
	public $avaliableLanguages = [];

	public function parseRequest($request)
	{
		if (count($this->avaliableLanguages)>0) {
			if (preg_match('/\/('.implode('|', $this->avaliableLanguages).')(.*)/si', $request->url, $matches)) {
				Yii::$app->language = $matches[1];
			}
		}

		$res = parent::parseRequest($request);
		if (is_array($res)) {
			if (isset($res[1]['language']) && in_array($res[1]['language'], $this->avaliableLanguages)) {
				Yii::$app->language = $res[1]['language'];
			}
		}
		return $res;
	}

	public function createUrl($params)
	{
		$langUnset = false;
		if (!isset($params['language'])) {
			$params['language'] = Yii::$app->language;
			$langUnset = true;
		}

		$params = (array)$params;
		$anchor = isset($params['#']) ? '#' . $params['#'] : '';
		unset($params['#'], $params[$this->routeParam]);

		$route = trim($params[0], '/');
		unset($params[0]);
		$baseUrl = $this->getBaseUrl();

		if ($this->enablePrettyUrl) {
			/** @var UrlRule $rule */
			foreach ($this->rules as $rule) {
				if (($url = $rule->createUrl($this, $route, $params)) !== false) {
					if ($rule->host !== null) {
						if ($baseUrl !== '' && ($pos = strpos($url, '/', 8)) !== false) {
							return substr($url, 0, $pos) . $baseUrl . substr($url, $pos);
						} else {
							return $url . $baseUrl . $anchor;
						}
					} else {
						return "$baseUrl/{$url}{$anchor}";
					}
				}
			}

			if ($this->suffix !== null) {
				$route .= $this->suffix;
			}
			if (!empty($params)) {
				$route .= '?' . http_build_query($params);
			}
			return "$baseUrl/{$route}{$anchor}";
		} else {
			$url = "$baseUrl?{$this->routeParam}=$route";
			if (!empty($params)) {
				if ($langUnset) unset($params['language']);
				$url .= '&' . http_build_query($params);
			}
			return $url . $anchor;
		}
	}
}
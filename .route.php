<?php
require '.router.php';

/**
 * route v.1.3
 */
class Router {
	public $api;

	public function __construct() {
		$str = $_SERVER['REQUEST_URI'];
		$off = strlen($_SERVER['DOCUMENT_URI']);
		$len = null;
		if ($_SERVER['QUERY_STRING']) {
			$len = strlen($str) - $off - strlen($_SERVER['QUERY_STRING']) - 1;
		}
		$_SERVER['SCRIPT_NAME'] = $str = substr($str, $off, $len);

		if (str_ends_with($str, '/')) {
			$ext = 'index.php';
			if (is_file(".$str$ext")) {
				$arg = '';
			}
		}
		if (!isset($arg)) {
			$arg = '';
			$try = 0;
			$max = defined('MAX_PATH_ARGS') ? MAX_PATH_ARGS : 3;
			while (!empty($str)) {
				if (!str_ends_with($str, '/') && strpos($str, '.') === false) {
					if (is_file(".$str.php")) {
						$ext = '.php';
						$_SERVER['SCRIPT_NAME'] = $str;
						break;
					}
					if (++$try > $max) {
						$str = '';
						break;
					}
				}
				$pos = strrpos($str, '/');
				$arg = substr($str, $pos).$arg;
				$str = substr($str, 0, $pos);
			}
			if (empty($str)) {
				$arg = '';
				$ext ??= '.php';
			}
		}
		$_SERVER['PATH_INFO'] = $arg;
		$_SERVER['PHP_SELF'] = $_SERVER['DOCUMENT_URI'].$_SERVER['SCRIPT_NAME'].$_SERVER['PATH_INFO'];
		$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME'].$ext;
		$_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['REQUEST_METHOD']);
		if (empty($str)) {
			$this->error(404);
		}
	}

	public function error($code, $msg = null) {
		http_response_code($code);
		if (!$msg) {
			$msg = require '.error/status.php';
		}
		if ($this->api) {
			echo type_encode(['error' => $msg], $this->api);
		} else {
			if (is_file(($file = ".error/$code").'.html')
				|| is_file(($file = substr($file, 0, -1).'x').'.html')
				|| is_file(($file = substr($file, 0, -2).'xx').'.html')) {
				include "$file.html";
			} else {
				echo "$code. $msg";
			}
		}
		exit;
	}

	private function checkPath($path) {
		if ($path === $_SERVER['PATH_INFO']) return;
		if (str_starts_with($path, '/')) {
			$path = explode('/', $path);
			$args = explode('/', $_SERVER['PATH_INFO']);
			if (count($path) >= count($args)) try {
				for ($i=1; $i<count($path); $i++) {
					$this->assignPath($path[$i], $args[$i] ?? NULL);
				}
				return;
			} catch (Exception) {}
		}
		$this->error(404);
	}

	private function assignPath($name, $value) {
		if (strlen($name) > 1 && $name[0] == '$') {
			$GLOBALS[substr($name, 1)] = $value ? urldecode($value) : $value;
		} else if ($name !== $value) {
			throw new InvalidArgumentException();
		}
	}

	private function checkMethod(&$methods) {
		if (!is_array($methods)) {
			$methods = preg_split("/[\s,]+/", $methods);
		}
		$methods = array_map('strtoupper', $methods);
		if (!in_array('HEAD', $methods) && in_array('GET', $methods)) {
			array_push($methods, 'HEAD');
		}
		if (!in_array('OPTIONS', $methods)) {
			array_push($methods, 'OPTIONS');
		}
		if (in_array($_SERVER['REQUEST_METHOD'], $methods)) {
			return;
		}
		$this->error(405);
	}

	private function checkOrigin($methods, $origins) {
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			if (is_array($origins)) {
				foreach($origins as &$origin) {
					$origin['methods'] = implode(',', $methods);
				}
			} else {
				$origins = [];
			}
			$origins = array_merge($origins, defined('API_ORIGINS') ? API_ORIGINS : []);
			foreach($origins as $origin) {
				if (isset($origin['host']) && ($origin['host'] == '*' || $origin['host'] == $_SERVER['HTTP_ORIGIN'])) {
					header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
					if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
						header('Access-Control-Allow-Methods: '.($origin['methods'] ?? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] ?? ''));
						header('Access-Control-Allow-Headers: '.($origin['headers'] ?? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? ''));
						header('Access-Control-Max-Age: '.($origin['maxAge'] ?? 86400));
						if ($origin['credentials'] ?? false) header('Access-Control-Allow-Credentials: true');
					}
					return;
				}
			}
		}
	}

	private function checkOptions($methods) {
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
			header('Allow: '.implode(',', $methods));
			exit;
		}
	}

	private function checkAccept() {
		$accepts = preg_split("/[\s,]+/", $_SERVER['HTTP_ACCEPT']);
		foreach ($accepts as $accept) {
			$accept = strtolower(trim($accept));
			foreach (['json', 'xml'] as $api) {
				if ($accept == 'application/'.$api) {
					$this->api = $api;
					header('Content-Type: application/'.$api.'; charset=utf-8');
					return;
				}
			}
		}
		$this->error(406);
	}

	public function use($methods = 'GET', string $path = '') {
		$this->checkPath($path);
		$this->checkMethod($methods);
		$this->checkOptions($methods);
	}

	public function api($methods = 'GET', string $path = '', array $origins = []) {
		$this->checkPath($path);
		$this->checkMethod($methods);
		$this->checkOrigin($methods, $origins);
		$this->checkOptions($methods);
		$this->checkAccept();
		$GLOBALS['API'] = new stdClass();
	}

	private function isContentType($type) {
		return in_array($type, preg_split("/[\s;]+/", strtolower($_SERVER['CONTENT_TYPE'])));
	}

	public function getJson() {
		try {
			if ($this->isContentType('application/json')) {
				return json_decode($this->getBody(), $associative=false, $depth=512, JSON_THROW_ON_ERROR);
			}
		} catch (Exception) {}
		$this->error(415);
	}

	public function getXml() {
		try {
			if ($this->isContentType('application/xml')) {
				return xml_decode($this->getBody());
			}
		} catch (Exception) {}
		$this->error(415);
	}

	public function getBody() {
		return file_get_contents('php://input');
	}
}

function type_encode($data, $type = 'json') {
	if (function_exists($func = $type.'_encode')) {
		return $func($data, JSON_UNESCAPED_UNICODE);
	}
	throw new UnexpectedValueException("'$type' is not suppoted.");
}

function xml_encode($data, $flags = 0) {
	$root = new DOMDocument('1.0', 'utf-8');
	$name = defined('XML_ROOT_NAME') ? XML_ROOT_NAME : NULL;
	xml_encoder($data, $root, !$name && count((array) $data) === 1 && (is_object($data) || is_array($data))
		? $root
		: $root->appendChild(
			$root->createElement($name ?: 'root')
		)
	);
	return $root->saveXML();
}

function xml_encoder($data, $root, $node) {
	if (is_object($data)) {
		$data = get_object_vars($data);
	}
	if (is_array($data)) {
		foreach ($data as $key => $value) {
			if (is_int($key)) {
				xml_encoder($value, $root, $key === 0 ? $node : $node->parentNode->appendChild(
					$root->createElement(
						$node->tagName
					)
				));
			} else {
				xml_encoder($value, $root, $node->appendChild(
					$root->createElement(
						$key
					)
				));
			}
		}
	} else {
		$node->appendChild(
			$root->createTextNode(
				is_bool($data) ? ($data ? 'true' : 'false') : $data
			)
		);
	}
}

function xml_decode($str) {
	$xml = new DOMDocument;
	$xml->preserveWhiteSpace = false;
	set_error_handler(function($number, $error){});
	if (!$xml->loadXML($str))
		throw new UnexpectedValueException();
	restore_error_handler();
	$root = $xml->documentElement;
	$data[$root->nodeName] = xml_decoder($root);
	return (object) $data;
}

function xml_decoder($node) {
	if ($node->hasChildNodes()) {
		$children = $node->childNodes;
		foreach ($children as $child) {
			if ($child->nodeType == XML_ELEMENT_NODE) {
				$item = xml_decoder($child);
				if (!isset($data[$child->nodeName]))
					$data[$child->nodeName] = $item;
				else {
					if (!is_array($data[$child->nodeName]))
						$data[$child->nodeName] = [$data[$child->nodeName]];
					array_push($data[$child->nodeName], xml_decoder($child));
				}
			}
		}
	}
	return isset($data) ? (object) $data : $node->nodeValue;
}

const router = new Router();
include $_SERVER['SCRIPT_FILENAME'];
if (router->api && isset($API)) {
	echo type_encode($API, router->api);
}
?>
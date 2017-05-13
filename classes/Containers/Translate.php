<?php
namespace Containers;
use Interop\Container\ContainerInterface as Container;
use Dflydev\FigCookies\FigRequestCookies;


class Translate extends \core {
	private $lang;
	private $langs;
	private $trans;
	public function __construct(Container $ci) {
		parent::__construct($ci);
		$this->langs = array('fr-FR', 'it-IT', 'de-DE', 'en-US');
		$a = array_intersect(explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']), $this->langs);
		$this->lang = 'en-US';
		if (!empty($a))
			$this->lang = $a[0];
		$cookie = FigRequestCookies::get($ci['request'], 'lang', $this->lang);
		$this->lang = $cookie->getValue();
		if(!in_array($this->lang, $this->langs))
			$this->lang = 'en-US';
		$f = __DIR__.'/../../public/langs/'.$this->lang.'.json';
		if (file_exists($f)) {
			$j = file_get_contents($f);
			$this->trans = json_decode($j, true);
			if ($this->trans == null)
				$this->trans = array();
		} else	$this->trans = array();
	}
	
	public function __invoke($str) {
		if(is_string($str)) {
			if (array_key_exists($str, $this->trans))
				return $this->trans[$str];
			return $str;
		}
		if(is_array($str)) {
			if (array_key_exists($str[0], $this->trans))
				return $this->trans[$str[0]];
			return $str[0];
		}
		$this->logger->addWarning('Translator() arg#1 is not a string but '.gettype($str)."$str");
		return "";
		//return _($str);
	}
	
	public function getLang() {
		return $this->lang;
	}
}


?>

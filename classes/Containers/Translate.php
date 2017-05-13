<?php
namespace Containers;
use Interop\Container\ContainerInterface as Container;

class Translate extends \core {
	private $lang;
	private $trans;
	public function __construct(Container $ci) {
		parent::__construct($ci);
		$this->lang = 'en-US';
		$a = array_intersect(explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']), array('fr-FR', 'it-IT', 'de-DE', 'en-US'));
		if (empty($a)) {
			$this->trans = array();
		} else {
			$f = __DIR__.'/../../public/langs/'.$a[0].'.json';
			if (file_exists($f)) {
				$j = file_get_contents($f);
				$this->trans = json_decode($j, true);
				if ($this->trans == null)
					$this->trans = array();
				else
					$this->lang = $a[0];
			} else	$this->trans = array();
		}
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

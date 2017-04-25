<?php
use Interop\Container\ContainerInterface;

class core {
	protected $ci;

	public function __construct(ContainerInterface $ci) { 
		$this->ci = $ci;
	}

	public function __get($property) {
		if($this->ci->{$property})
			return $this->ci->{$property};
	}

}
?>

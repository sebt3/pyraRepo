<?php
namespace Containers;
use Interop\Container\ContainerInterface as Container;
use \PDO as PDO;

class MenuObject extends \core {
	//menu view interfaces
	public $breadcrumb;
	public $isAuth;
	public $isMaintainer;
	public $username;

	public function __construct(Container $ci) {
		parent::__construct($ci);
		$this->isAuth		= $ci->auth->authenticated();
		$this->isMaintainer	= false;
		$this->username		= $ci->auth->getUserName();
	}
}
?>

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
	public $categories;

	private function getCategories() {
		$ret = [];
		$s = $this->db->prepare('select c.id, c.name, count(*) as app_cnt
  from apps_categories a, categories c
 where a.cat_id=c.id
 group by c.name
 order by app_cnt desc');
		$s->execute();
		while($r = $s->fetch()) {
			$r['url'] = $this->router->pathFor('apps.byCat', array('id'=> $r['id']));
			$ret[] = $r;
		}
		return $ret;
	}

	public function __construct(Container $ci) {
		parent::__construct($ci);
		$this->isAuth		= $ci->auth->authenticated();
		$this->isMaintainer	= false;
		$this->username		= $ci->auth->getUserName();
		$this->categories	= $this->getCategories();
	}
}
?>

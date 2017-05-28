<?php
namespace Containers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Interop\Container\ContainerInterface as Container;
use \PDO as PDO;

class MenuObject extends \core {
	//menu view interfaces
	public $breadcrumb;
	public $isAuth;
	public $isMaintainer;
	public $username;
	public $disabled;
	public $categories;
	public $url;

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

	public function __invoke(Request $request, Response $response, callable $next) {
		$uri = $request->getUri();
		if($GLOBALS['repo_base']=="")
			$this->url = $uri->getPath();
		else
			$this->url = $GLOBALS['repo_base'].'/'.$uri->getPath();
		return $response = $next($request, $response);
	}
}

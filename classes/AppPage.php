<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


class AppPage extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function searchApp($q) {
		$_ = $this->trans;
		$match = "%$q%";
		$ret = [];
		$ret['title'] = $_('Searching apps: '.$q);
		$ret['body'] = [];
		$s = $this->db->prepare('select a.id, a.name, a.comments, a.icon, p.id as dbp_id, p.str_id as dbp_str_id, p.name as dbp_name, v.version, v.path, ar.name as arch, u.username, v.timestamp  * 1000.0 as timestamp, ifnull(a.infos,p.infos) as infos
  from	apps a, dbpackages p, package_versions v, archs ar, users u
 where a.dbp_id=p.id
   and p.last_vers=v.id
   and p.arch_id=ar.id
   and u.id=v.by_user
   and a.enabled!=0
   and p.enabled!=0
   and v.enabled!=0
   and a.name like :match
order by timestamp desc');
		$s->bindParam(':match',	$match,		PDO::PARAM_STR);
		$s->execute();
		while($r = $s->fetch()) {
			$r['dbp_url'] = $this->router->pathFor('packages.byStr', array('str'=> $r['dbp_str_id']));
			$r['url'] = $this->router->pathFor('apps.byId', array('id'=> $r['id']));
			$ret['body'][] = $r;
		}
		return $ret;
	}

	private function getApps() {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('All apps');
		$ret['body'] = [];
		$s = $this->db->prepare('select a.id, a.name, a.comments, a.icon, p.id as dbp_id, p.str_id as dbp_str_id, p.name as dbp_name, v.version, v.path, ar.name as arch, u.username, v.timestamp  * 1000.0 as timestamp, ifnull(a.infos,p.infos) as infos
  from	apps a, dbpackages p, package_versions v, archs ar, users u
 where a.dbp_id=p.id
   and p.last_vers=v.id
   and p.arch_id=ar.id
   and u.id=v.by_user
   and a.enabled!=0
   and p.enabled!=0
   and v.enabled!=0
order by timestamp desc');
		$s->execute();
		while($r = $s->fetch()) {
			$r['dbp_url'] = $this->router->pathFor('packages.byStr', array('str'=> $r['dbp_str_id']));
			$r['url'] = $this->router->pathFor('apps.byId', array('id'=> $r['id']));
			$ret['body'][] = $r;
		}
		return $ret;
	}

	private function getApp($id) {
		$s = $this->db->prepare('select a.id, a.name, a.comments, a.icon, p.id as dbp_id, p.str_id as dbp_str_id, p.name as dbp_name, v.version, v.path, ar.name as arch, u.username, v.timestamp * 1000.0 as timestamp, ifnull(a.infos,p.infos) as infos
  from	apps a, dbpackages p, package_versions v, archs ar, users u
 where a.dbp_id=p.id
   and p.last_vers=v.id
   and p.arch_id=ar.id
   and u.id=v.by_user
   and a.enabled!=0
   and p.enabled!=0
   and v.enabled!=0
   and a.id=:id');
		$s->bindParam(':id',	$id,		PDO::PARAM_INT);
		$s->execute();
		$r=$s->fetch();
		$r['dbp_url'] = $this->router->pathFor('packages.byStr', array('str'=> $r['dbp_str_id']));
		$r['url'] = $this->router->pathFor('apps.byId', array('id'=> $r['id']));
		return $r;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Page Controlers

	public function appsPage (Request $request, Response $response) {
		$q = $request->getQueryParam('q');
		if (empty($q))
			$apps = $this->getApps();
		else
			$apps = $this->searchApp($q);
		$this->menu->breadcrumb = array(
			array('name' => 'Apps', 'icon' => 'fa fa-rocket', 'url' => $this->router->pathFor('apps.list'))
		);
 		return $this->view->render($response, 'apps.twig', [
			'apps'	=> $apps
 		]);
	}
	public function appByIdPage (Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$a  = $this->getApp($id);
		if (!is_array($a)) {
			$this->flash->addMessage('error', 'No app '.$id.' found');
			return $response->withRedirect($this->router->pathFor('apps.list'));
		}
		if ($this->isPackageMaintainer($a['dbp_id']))
			$this->menu->isMaintainer  = true;
		$this->menu->breadcrumb = array(
			array('name' => 'Apps', 'icon' => 'fa fa-rocket', 'url' => $this->router->pathFor('apps.list')),
			array('name' => $a['name'], 'url' => $this->router->pathFor('apps.byId', array('id'=> $id)))
		);
 		return $this->view->render($response, 'app.twig', [
			'a'	 => $a
 		]);
	}
	public function appEditPage (Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$a  = $this->getApp($id);
		if (!is_array($a)) {
			$this->flash->addMessage('error', 'No app '.$id.' found');
			return $response->withRedirect($this->router->pathFor('apps.list'));
		}
		$this->menu->breadcrumb = array(
			array('name' => 'Apps', 'icon' => 'fa fa-rocket', 'url' => $this->router->pathFor('apps.list')),
			array('name' => $a['name'], 'url' => $this->router->pathFor('apps.byId', array('id'=> $id)))
		);
 		return $this->view->render($response, 'app.twig', [
			'a'	 => $a
 		]);
	}
}

?>

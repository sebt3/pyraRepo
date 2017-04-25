<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


class PackagePage extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getPackages() {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('All packages');
		$ret['body'] = [];
		$s = $this->db->prepare('select p.id as dbp_id, p.str_id as dbp_str_id, p.name, p.icon, v.version, ar.name as arch, u.username, v.timestamp * 1000.0 as timestamp, p.infos
  from	dbpackages p, package_versions v, archs ar, users u
 where p.last_vers=v.id
   and p.arch_id=ar.id
   and u.id=v.by_user
   and p.enabled!=0
   and v.enabled!=0
 order by timestamp desc');
		$s->execute();
		while($r = $s->fetch()) {
			$r['url'] = $this->router->pathFor('packages.byStr', array('str'=> $r['dbp_str_id']));
			$ret['body'][] = $r;
		}
		return $ret;
	}
	private function getPackage($id) {
		$s = $this->db->prepare('select p.id as dbp_id, p.str_id as dbp_str_id, p.name, p.icon, v.version, v.path, ar.name as arch, u.username, v.timestamp * 1000.0 as timestamp, p.infos
  from	dbpackages p, package_versions v, archs ar, users u
 where p.last_vers=v.id
   and p.arch_id=ar.id
   and u.id=v.by_user
   and p.enabled!=0
   and v.enabled!=0
   and p.id=:id');
		$s->bindParam(':id',	$id,		PDO::PARAM_INT);
		$s->execute();
		return $s->fetch();
	}
	private function getPackageApps($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('All packages');
		$ret['body'] = [];
		$s = $this->db->prepare('select a.id, a.name, a.comments, a.icon, p.id as dbp_id, p.str_id as dbp_str_id, p.name as dbp_name, v.version, v.path, ar.name as arch, u.username, v.timestamp * 1000.0 as timestamp, ifnull(a.infos,p.infos) as infos
  from	apps a, dbpackages p, package_versions v, archs ar, users u
 where a.dbp_id=p.id
   and p.last_vers=v.id
   and p.arch_id=ar.id
   and u.id=v.by_user
   and a.enabled!=0
   and p.enabled!=0
   and v.enabled!=0
   and p.id=:id');
		$s->bindParam(':id',	$id,		PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r['dbp_url'] = $this->router->pathFor('packages.byStr', array('str'=> $r['dbp_str_id']));
			$r['url'] = $this->router->pathFor('apps.byId', array('id'=> $r['id']));
			$ret['body'][] = $r;
		}
		return $ret;
	}
	private function getPackageId($str) {
		$s = $this->db->prepare('select id from	dbpackages where str_id=:str');
		$s->bindParam(':str',	$str,		PDO::PARAM_STR);
		$s->execute();
		$r = $s->fetch();
		if (!is_array($r)) return 0;
		return $r['id'];
	}
	
/////////////////////////////////////////////////////////////////////////////////////////////
// Page Controlers

	public function packagesPage (Request $request, Response $response) {
		$this->menu->breadcrumb = array(
			array('name' => 'packages', 'icon' => 'fa fa-archive', 'url' => $this->router->pathFor('packages.list'))
		);
 		return $this->view->render($response, 'packages.twig', [
			'packages'	 => $this->getPackages()
 		]);
	}

	public function packageByIdPage (Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', 'No package '.$id.' found');
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		$this->menu->breadcrumb = array(
			array('name' => 'packages', 'icon' => 'fa fa-archive', 'url' => $this->router->pathFor('packages.list')),
			array('name' => $p['name'], 'url' => $this->router->pathFor('packages.byId', array('id'=> $id)))
		);
 		return $this->view->render($response, 'package.twig', [
			'p'	 => $p,
			'apps'	=> $this->getPackageApps($id)
 		]);
	}
	public function packageByStrPage (Request $request, Response $response) {
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', 'No package '.$str.' found');
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if ($this->isPackageMaintainer($id))
			$this->menu->isMaintainer  = true;
		$this->menu->breadcrumb = array(
			array('name' => 'packages', 'icon' => 'fa fa-archive', 'url' => $this->router->pathFor('packages.list')),
			array('name' => $p['name'], 'url' => $this->router->pathFor('packages.byStr', array('str'=> $str)))
		);
 		return $this->view->render($response, 'package.twig', [
			'p'	=> $p,
			'apps'	=> $this->getPackageApps($id)
 		]);
	}
	public function packageEditPage (Request $request, Response $response) {
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', 'No package '.$str.' found');
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', 'You cannot edit this package');
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $str)));
		}
		$this->menu->breadcrumb = array(
			array('name' => 'packages', 'icon' => 'fa fa-archive', 'url' => $this->router->pathFor('packages.list')),
			array('name' => $p['name'], 'url' => $this->router->pathFor('packages.byStr', array('str'=> $str))),
			array('name' => 'edit', 'icon' => 'fa fa-pencil', 'url' => $this->router->pathFor('packages.edit', array('str'=> $str)))
		);
 		return $this->view->render($response, 'packageEdit.twig', [
			'p'	=> $p,
			'apps'	=> $this->getPackageApps($id)
 		]);
	}
	public function packageDownload (Request $request, Response $response) {
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', 'No package '.$str.' found');
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		$this->flash->addMessage('error', 'No Download yet');
		return $response->withRedirect($this->router->pathFor('packages.list'));
	}
}

?>

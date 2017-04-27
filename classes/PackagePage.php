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
		$s = $this->db->prepare('select p.id as dbp_id, p.str_id as dbp_str_id, p.name, p.icon, v.version, v.path, ar.name as arch, u.username, v.timestamp * 1000.0 as timestamp, p.infos, p.forumurl, p.upurl, p.upsrcurl, p.srcurl
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
	private function getPackageVersion($str, $id) {
		$s = $this->db->prepare('select v.version, v.path, p.str_id as dbp_str_id
  from package_versions v, dbpackages p
 where v.dbp_id=p.id
   and p.enabled=1
   and v.enabled=1
   and p.str_id=:str
   and v.id=:id');
		$s->bindParam(':str',	$str,		PDO::PARAM_STR);
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

	private function getScreenShots($id) {
		$ret = [];
		$s = $this->db->prepare('select s.path as url, s.timestamp as alt
  from app_shoots s, packages_maintainers m, apps a
 where s.app_id=a.id
   and s.user_id=m.user_id
   and m.dbp_id=a.dbp_id
   and a.dbp_id=:id
 order by s.timestamp desc
 limit 0,10');
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret[] = $r;
		}
		return $ret;
	}

	private function getCommunityScreenShots($id) {
		$ret = [];
		$s = $this->db->prepare('select s.path as url, s.timestamp as alt
  from app_shoots s, packages_maintainers m, apps a
 where s.app_id=a.id
   and s.user_id!=m.user_id
   and m.dbp_id=a.dbp_id
   and a.dbp_id=:id
 order by s.timestamp desc
 limit 0,10');
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret[] = $r;
		}
		return $ret;
	}

	private function getVersionHistory($id) {
		$ret = [];
		$s = $this->db->prepare('select v.timestamp  * 1000.0 as timestamp, v.version, u.username as uploader, p.str_id as dbp_str_id
  from package_versions v, users u, dbpackages p
 where v.dbp_id = :id
   and p.id = v.dbp_id
   and u.id = v.by_user
   and v.enabled=1
 order by v.timestamp desc');
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret[] = array(
				'ts'	=> array( 'text' => $this->formatTimestamp($r['timestamp'])),
				'ver'	=> array( 'text' => $r['version']),
				'maint'	=> array( 'text' => $r['uploader']),
				'downl'	=> array( 'icon' => 'fa fa-download', 'text' => 'Download', 'url' => $this->router->pathFor('packages.download.version', array('id'=> $r['id'], 'str' => $r['dbp_str_id'])))
			);
		}
		return $ret;
	}
	private function updateDesc($id, $desc) {
		$ret = [];
		$s = $this->db->prepare('update dbpackages set infos=:desc where id=:id');
		if ($desc=="") $desc=null;
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->bindParam(':desc',	$desc,	PDO::PARAM_STR);
		$s->execute();
	}
	private function updateUrls($id, $f, $u, $us, $sr) {
		$ret = [];
		$s = $this->db->prepare('update dbpackages set forumurl=:f,upurl=:u,upsrcurl=:us,srcurl=:s  where id=:id');
		if ($f=="") $f=null;
		if ($u=="") $u=null;
		if ($us=="") $us=null;
		if ($sr=="") $sr=null;
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->bindParam(':f',	$f,	PDO::PARAM_STR);
		$s->bindParam(':u',	$u,	PDO::PARAM_STR);
		$s->bindParam(':us',	$us,	PDO::PARAM_STR);
		$s->bindParam(':s',	$sr,	PDO::PARAM_STR);
		$s->execute();
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
		return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $p['dbp_str_id'])));
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
			'p'		=> $p,
			'apps'		=> $this->getPackageApps($id),
			'offshot'	=> $this->getScreenShots($id),
			'comshot'	=> $this->getCommunityScreenShots($id),
			'vers'		=> $this->getVersionHistory($id)
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
        $fh = fopen($p['path'], 'rb');

        $stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body

        return $response->withHeader('Content-Type', 'application/force-download')
                        ->withHeader('Content-Type', 'application/octet-stream')
                        ->withHeader('Content-Type', 'application/download')
                        ->withHeader('Content-Description', 'File Transfer')
                        ->withHeader('Content-Transfer-Encoding', 'binary')
                        ->withHeader('Content-Disposition', 'attachment; filename="' . $p['dbp_str_id'].'-'.$p['version'].'.dbp' . '"')
                        ->withHeader('Expires', '0')
                        ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                        ->withHeader('Pragma', 'public')
                        ->withBody($stream);
	}
	public function packageVersionDownload (Request $request, Response $response) {
		$str= $request->getAttribute('str');
		$id = $request->getAttribute('id');
		$v  = $this->getPackageVersion($str, $id);
		if (!is_array($v)) {
			$this->flash->addMessage('error', 'No version '.$id.' for package '.$str.' found');
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
        $fh = fopen($v['path'], 'rb');

        $stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body

        return $response->withHeader('Content-Type', 'application/force-download')
                        ->withHeader('Content-Type', 'application/octet-stream')
                        ->withHeader('Content-Type', 'application/download')
                        ->withHeader('Content-Description', 'File Transfer')
                        ->withHeader('Content-Transfer-Encoding', 'binary')
                        ->withHeader('Content-Disposition', 'attachment; filename="' . $v['dbp_str_id'].'-'.$v['version'].'.dbp' . '"')
                        ->withHeader('Expires', '0')
                        ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                        ->withHeader('Pragma', 'public')
                        ->withBody($stream);
	}
	public function descriptionPost (Request $request, Response $response) {
		$this->auth->assertAuth($request, $response);
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', 'No package '.$id.' found');
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', "You're not a maintainer");
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $p['dbp_str_id'])));
		}
		$this->updateDesc($id, $request->getParam('desc'));
		$this->flash->addMessage('info', "Description updated");
		return $response->withRedirect($this->router->pathFor('packages.edit', array('str'=> $p['dbp_str_id'])));
	}
	public function urlsPost (Request $request, Response $response) {
		$this->auth->assertAuth($request, $response);
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', 'No package '.$id.' found');
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', "You're not a maintainer");
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $p['dbp_str_id'])));
		}
		$this->updateUrls($id, $request->getParam('forum'), $request->getParam('up'), $request->getParam('upsrc'), $request->getParam('src'));
		$this->flash->addMessage('info', "URLs updated");
		return $response->withRedirect($this->router->pathFor('packages.edit', array('str'=> $p['dbp_str_id'])));
	}
/*	public function licensePost (Request $request, Response $response) {
		$this->auth->assertAuth($request, $response);
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', 'No package '.$id.' found');
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', "You're not a maintainer");
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $p['dbp_str_id'])));
		}
		$this->updateDesc($id, $request->getParam('desc'));
		$this->flash->addMessage('info', "URLs updated");
		return $response->withRedirect($this->router->pathFor('packages.edit', array('str'=> $p['dbp_str_id'])));
	}*/
}

?>

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
		$s = $this->db->prepare('select p.id as dbp_id, p.str_id as dbp_str_id, p.name, p.icon, v.version, ar.name as arch, u.username, v.timestamp * 1000.0 as timestamp, p.infos, v.md5sum, v.sha1sum, v.filesize
  from	dbpackages p, package_versions v, archs ar, users u
 where p.last_vers=v.id
   and p.arch_id=ar.id
   and u.id=v.by_user
   and p.enabled!=0
   and v.enabled!=0
 order by timestamp desc');
		$s->execute();
		while($r = $s->fetch()) {
			$r['icon'] = $GLOBALS['repo_base'].$r['icon'];
			$r['url'] = $this->router->pathFor('packages.byStr', array('str'=> $r['dbp_str_id']));
			$ret['body'][] = $r;
		}
		return $ret;
	}
	private function getPackage($id) {
		$s = $this->db->prepare('select p.id as dbp_id, p.str_id as dbp_str_id, p.name, p.icon, v.version, v.path, ar.name as arch, u.username, v.timestamp * 1000.0 as timestamp, p.infos, p.forumurl, p.upurl, p.upsrcurl, p.srcurl, p.licenseurl, p.last_vers, p.lic_id, p.lic_detail, v.md5sum, v.sha1sum, v.filesize
  from	dbpackages p, package_versions v, archs ar, users u
 where p.last_vers=v.id
   and p.arch_id=ar.id
   and u.id=v.by_user
   and p.enabled!=0
   and v.enabled!=0
   and p.id=:id');
		$s->bindParam(':id',	$id,		PDO::PARAM_INT);
		$s->execute();
		$r = $s->fetch();
		$r['icon'] = $GLOBALS['repo_base'].$r['icon'];
		return $r;
	}
	private function getPackageVersion($str, $id) {
		$s = $this->db->prepare('select v.version, v.path, p.str_id as dbp_str_id, v.md5sum, v.sha1sum, v.filesize
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
		$s = $this->db->prepare('select a.id, a.name, a.comments, a.icon, p.id as dbp_id, p.str_id as dbp_str_id, p.name as dbp_name, v.version, v.path, ar.name as arch, u.username, v.timestamp * 1000.0 as timestamp, ifnull(a.infos,p.infos) as infos, v.md5sum, v.sha1sum, v.filesize
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
			$r['icon'] = $GLOBALS['repo_base'].$r['icon'];
			$r['dbp_url'] = $this->router->pathFor('packages.byStr', array('str'=> $r['dbp_str_id']));
			$r['url'] = $this->router->pathFor('apps.byId', array('id'=> $r['id']));
			$ret['body'][] = $r;
		}
		return $ret;
	}
	private function getPackageMaintainers($id, $str) {
		$_ = $this->trans;
		$ret = [];
		$s = $this->db->prepare('select u.username, p.user_id
  from users u, packages_maintainers p
 where u.id=p.user_id
   and dbp_id=:id
 order by username asc');
		$s->bindParam(':id',	$id,		PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret[] = array(
				'user'	=> array( 'text' => $r['username']),
				'actions'=> array(array( 'icon' => 'fa fa-trash-o', 'url' => $this->router->pathFor('packages.maintainer.delete', array('uid'=> $r['user_id'], 'str' => $str))))
			);
		}
		return $ret;
	}
	private function getUsers($id) {
		$_ = $this->trans;
		$ret = [];
		$s = $this->db->prepare('select u.username, u.id as user_id
  from users u
 where u.id not in (select user_id from packages_maintainers where dbp_id=:id)
   and u.isReal=1
 order by username asc');
		$s->bindParam(':id',	$id,		PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret[] = $r;
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
			$r['url'] = $GLOBALS['repo_base'].$r['url'];
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
			$r['url'] = $GLOBALS['repo_base'].$r['url'];
			$ret[] = $r;
		}
		return $ret;
	}

	private function getVersionHistory($id) {
		$ret = [];
		$s = $this->db->prepare('select v.id, v.timestamp  * 1000.0 as timestamp, v.version, u.username as uploader, p.str_id as dbp_str_id, v.md5sum, v.sha1sum, v.filesize
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
				'size'	=> array( 'text' => $r['filesize']),
				'md5'	=> array( 'text' => $r['md5sum']),
				'sha1'	=> array( 'text' => $r['sha1sum']),
				'actions'	=> array(array( 'icon' => 'fa fa-download', 'url' => $this->router->pathFor('packages.download.version', array('id'=> $r['id'], 'str' => $r['dbp_str_id']))))
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
	private function updateUrls($id, $f, $u, $us, $sr, $lr) {
		$ret = [];
		$s = $this->db->prepare('update dbpackages set forumurl=:f, upurl=:u, upsrcurl=:us, srcurl=:s, licenseurl=:l  where id=:id');
		if ($f=="") $f=null;
		if ($u=="") $u=null;
		if ($us=="") $us=null;
		if ($sr=="") $sr=null;
		if ($lr=="") $lr=null;
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->bindParam(':f',	$f,	PDO::PARAM_STR);
		$s->bindParam(':u',	$u,	PDO::PARAM_STR);
		$s->bindParam(':us',	$us,	PDO::PARAM_STR);
		$s->bindParam(':s',	$sr,	PDO::PARAM_STR);
		$s->bindParam(':l',	$lr,	PDO::PARAM_STR);
		$s->execute();
	}
	private function updateLicence($id, $ltype, $name) {
		$s = $this->db->prepare('update dbpackages set lic_id=:lic, lic_detail=:name where id=:id');
		if ($ltype=="" || $ltype==0 ) $ltype=null;
		if ($name=="") $name=null;
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->bindParam(':lic',	$ltype,	PDO::PARAM_INT);
		$s->bindParam(':name',	$name,	PDO::PARAM_STR);
		$s->execute();
	}

	private function addDownload($id) {
		$date	= new DateTime();
		$ts	= $date->getTimestamp();
		$u	= 0;
		if ($this->auth->authenticated())
			$u = $this->auth->getUserId();
		else
			$u = $this->auth->getAnonymousId();
		$i	= $this->db->prepare('insert into package_downloads(vers_id, timestamp, user_id) values (:id, :ts, :u) on duplicate key update timestamp=:ts');
		$i->bindParam(':id', $id, PDO::PARAM_INT);
		$i->bindParam(':ts', $ts, PDO::PARAM_INT);
		$i->bindParam(':u',  $u,  PDO::PARAM_INT);
		$i->execute();
	}

	private function getComments($id) {
		$ret = [];
		$ret['body'] = [];
		$s = $this->db->prepare('select u.username as author, c.timestamp * 1000.0 as timestamp, c.text
  from package_comments c, users u 
 where c.user_id=u.id
   and c.dbp_id=:id 
 order by c.timestamp asc');
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = $r;
		}
		return $ret;
	}

	private function addComment($id, $comm) {
		$ret	= [];
		$date	= new DateTime();
		$ts	= $date->getTimestamp();
		$u = $this->auth->getUserId();
		if ($comm=="") return;
		$s = $this->db->prepare('insert into package_comments(dbp_id, timestamp, user_id, text) values(:id, :ts, :uid, :comm)');
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->bindParam(':ts',	$ts,	PDO::PARAM_INT);
		$s->bindParam(':uid',	$u,	PDO::PARAM_INT);
		$s->bindParam(':comm',	$comm,	PDO::PARAM_STR);
		$s->execute();
	}
	
	private function removeMaintainer($id, $uid) {
		$ret	= [];
		$s = $this->db->prepare('delete from packages_maintainers where dbp_id=:id and user_id=:uid');
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->bindParam(':uid',	$uid,	PDO::PARAM_INT);
		$s->execute();
	}

	private function addMaintainer($id, $uid) {
		$ret	= [];
		$s = $this->db->prepare('insert into packages_maintainers(dbp_id,user_id) values(:id, :uid) on duplicate key update user_id=:uid');
		$s->bindParam(':id',	$id,	PDO::PARAM_INT);
		$s->bindParam(':uid',	$uid,	PDO::PARAM_INT);
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
		$_ = $this->trans;
		$id = $request->getAttribute('id');
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$id.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $p['dbp_str_id'])));
	}
	public function packageByStrPage (Request $request, Response $response) {
		$_ = $this->trans;
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$str.$_(' found'));
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
			'vers'		=> $this->getVersionHistory($id),
			'comments'	=> $this->getComments($id)
 		]);
	}

	public function commentPost (Request $request, Response $response) {
		$_ = $this->trans;
		$this->auth->assertAuth($request, $response);
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$str.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		$this->addComment($id, $request->getParam('comment'));
		$this->flash->addMessage('info', $_("Comment added"));
		return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $str)));
	}

	public function packageEditPage (Request $request, Response $response) {
		$_ = $this->trans;
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$str.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', $_('You cannot edit this package'));
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $str)));
		}
		$this->menu->breadcrumb = array(
			array('name' => 'packages', 'icon' => 'fa fa-archive', 'url' => $this->router->pathFor('packages.list')),
			array('name' => $p['name'], 'url' => $this->router->pathFor('packages.byStr', array('str'=> $str))),
			array('name' => 'edit', 'icon' => 'fa fa-pencil', 'url' => $this->router->pathFor('packages.edit', array('str'=> $str)))
		);
 		return $this->view->render($response, 'packageEdit.twig', [
			'p'	=> $p,
			'apps'	=> $this->getPackageApps($id),
			'lics'	=> $this->getLicenses(),
			'maintainers' => $this->getPackageMaintainers($id, $str)
 		]);
	}
	public function maintainerAdd (Request $request, Response $response) {
		$_ = $this->trans;
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$str.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', $_('You cannot edit this package'));
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $str)));
		}
		$this->menu->breadcrumb = array(
			array('name' => 'packages', 'icon' => 'fa fa-archive', 'url' => $this->router->pathFor('packages.list')),
			array('name' => $p['name'], 'url' => $this->router->pathFor('packages.byStr', array('str'=> $str))),
			array('name' => 'edit', 'icon' => 'fa fa-pencil', 'url' => $this->router->pathFor('packages.edit', array('str'=> $str))),
			array('name' => 'maintainer', 'icon' => 'fa fa-plus', 'url' => $this->router->pathFor('packages.maintainer.add', array('str'=> $str)))
		);
 		return $this->view->render($response, 'packageMaintainerAdd.twig', [
			'p'	=> $p,
			'users' => $this->getUsers($id)
 		]);
	}
	public function packageDownload (Request $request, Response $response) {
		$_ = $this->trans;
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$str.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		$this->addDownload($p['last_vers']);
		$fh = fopen($p['path'], 'rb');

		$stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body

		return $response->withBody($stream)
				->withHeader('Content-Type', 'application/octet-stream')
				->withHeader('Content-Description', 'File Transfer')
				->withHeader('Content-Transfer-Encoding', 'binary')
				->withHeader('Content-Disposition', 'attachment; filename="' . $p['dbp_str_id'].'-'.$p['version'].'.dbp' . '"')
				->withHeader('Expires', '0')
				->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
				->withHeader('Pragma', 'public');
	}
	public function packageVersionDownload (Request $request, Response $response) {
		$_ = $this->trans;
		$str= $request->getAttribute('str');
		$id = $request->getAttribute('id');
		$v  = $this->getPackageVersion($str, $id);
		if (!is_array($v)) {
			$this->flash->addMessage('error', $_('No version ').$id.$_(' for package ').$str.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		$this->addDownload($id);
		$fh = fopen($v['path'], 'rb');

		$stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body

		return $response->withBody($stream)
				->withHeader('Content-Type', 'application/octet-stream')
				->withHeader('Content-Description', 'File Transfer')
				->withHeader('Content-Transfer-Encoding', 'binary')
				->withHeader('Content-Disposition', 'attachment; filename="' . $v['dbp_str_id'].'-'.$v['version'].'.dbp' . '"')
				->withHeader('Expires', '0')
				->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
				->withHeader('Pragma', 'public');
	}
	public function descriptionPost (Request $request, Response $response) {
		$_ = $this->trans;
		$this->auth->assertAuth($request, $response);
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$id.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', $_("You're not a maintainer"));
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $p['dbp_str_id'])));
		}
		$this->updateDesc($id, $request->getParam('desc'));
		$this->flash->addMessage('info', $_("Description updated"));
		return $response->withRedirect($this->router->pathFor('packages.edit', array('str'=> $p['dbp_str_id'])));
	}
	public function urlsPost (Request $request, Response $response) {
		$_ = $this->trans;
		$this->auth->assertAuth($request, $response);
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$id.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', $_("You're not a maintainer"));
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $p['dbp_str_id'])));
		}
		$this->updateUrls($id, $request->getParam('forum'), $request->getParam('up'), $request->getParam('upsrc'), $request->getParam('src'), $request->getParam('license'));
		$this->flash->addMessage('info', $_("URLs updated"));
		return $response->withRedirect($this->router->pathFor('packages.edit', array('str'=> $p['dbp_str_id'])));
	}
	public function licensePost (Request $request, Response $response) {
		$_ = $this->trans;
		$this->auth->assertAuth($request, $response);
		$str = $request->getAttribute('str');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$id.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', $_("You're not a maintainer"));
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $p['dbp_str_id'])));
		}
		$this->updateLicence($id, $request->getParam('ltype'), $request->getParam('name'));
		$this->flash->addMessage('info', $_("Licence updated"));
		return $response->withRedirect($this->router->pathFor('packages.edit', array('str'=> $p['dbp_str_id'])));
	}
	public function maintainerDeletePost (Request $request, Response $response) {
		$_ = $this->trans;
		$this->auth->assertAuth($request, $response);
		$str = $request->getAttribute('str');
		$uid = $request->getAttribute('uid');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$id.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', $_("You're not a maintainer"));
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $p['dbp_str_id'])));
		}
		if($uid == $this->auth->getUserId()) {
			$this->flash->addMessage('error', $_("You cannot remove yourself from the list of maintainers"));
			return $response->withRedirect($this->router->pathFor('packages.edit', array('str'=> $p['dbp_str_id'])));
		}
		$this->removeMaintainer($id, $uid);
		$this->flash->addMessage('info', $_("Permissions updated"));
		return $response->withRedirect($this->router->pathFor('packages.edit', array('str'=> $p['dbp_str_id'])));
	}
	public function maintainerPost (Request $request, Response $response) {
		$_ = $this->trans;
		$this->auth->assertAuth($request, $response);
		$str = $request->getAttribute('str');
		$uid = $request->getAttribute('uid');
		$id = $this->getPackageId($str);
		$p  = $this->getPackage($id);
		if (!is_array($p)) {
			$this->flash->addMessage('error', $_('No package ').$id.$_(' found'));
			return $response->withRedirect($this->router->pathFor('packages.list'));
		}
		if(!$this->isPackageMaintainer($id)) {
			$this->flash->addMessage('error', $_("You're not a maintainer"));
			return $response->withRedirect($this->router->pathFor('packages.byStr', array('str'=> $p['dbp_str_id'])));
		}
		$this->addMaintainer($id, $request->getParam('uid'));
		$this->flash->addMessage('info', $_("Maintainer added"));
		return $response->withRedirect($this->router->pathFor('packages.edit', array('str'=> $p['dbp_str_id'])));
	}

}

?>

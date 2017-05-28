<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;

class UserPage extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}
/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function setLang($lang) {
		//TODO...
		return $lang;
	}

	private function getMyPackage() {
		$_ = $this->trans;
		$u = $this->auth->getUserId();
		$s = $this->db->prepare('select p.id, p.str_id, p.name
  from dbpackages p, packages_maintainers m
 where p.id=m.dbp_id
   and m.user_id=:user
 order by str_id asc');
		$s->bindParam(':user', $u,  PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret[] = array(
				'pck'	=> array( 'text' => $r['name'], 'url' => $this->router->pathFor('packages.byStr', array('str' => $r['str_id']))),
				'actions'=> array(array( 'icon' => 'fa fa-pencil', 'url' => $this->router->pathFor('packages.edit', array('str' => $r['str_id']))))
			);
		}
		return $ret;
	}

	private function getMyDownload() {
		$_ = $this->trans;
		$u = $this->auth->getUserId();
		$s = $this->db->prepare('select p.str_id, p.name, max(d.timestamp) as timestamp
  from package_downloads d, package_versions v, dbpackages p
 where d.user_id=:user
   and d.vers_id=v.id
   and p.id = v.dbp_id
   and p.enabled=1
   and v.enabled=1
 group by p.str_id, p.name
 order by timestamp desc');
		$s->bindParam(':user', $u,  PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret[] = array(
				'pck'	=> array( 'text' => $r['name'], 'url' => $this->router->pathFor('packages.byStr', array('str' => $r['str_id'])))
			);
		}
		return $ret;
	}

	private function getMyLike() {
		$_ = $this->trans;
		$u = $this->auth->getUserId();
		$s = $this->db->prepare('select a.id, a.name
  from app_likes l, apps a, dbpackages p, package_versions v
 where l.app_id=a.id
   and a.enabled=1
   and p.id = a.dbp_id
   and p.enabled=1
   and v.id=p.last_vers
   and v.enabled=1
   and l.user_id=:user
 order by l.timestamp desc');
		$s->bindParam(':user', $u,  PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret[] = array(
				'app'	=> array( 'text' => $r['name'], 'url' => $this->router->pathFor('apps.byId', array('id' => $r['id'])))
			);
		}
		return $ret;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Page Controlers

	public function settingsPage (Request $request, Response $response) {
		$this->auth->assertAuth($request, $response);
		$this->menu->disabled = true;
		$this->menu->breadcrumb = array(
			array('name' => 'user', 'icon' => 'fa fa-user', 'url' => $this->router->pathFor('user.settings'))
		);
 		return $this->view->render($response, 'settings.twig', [
			'pck'	=> $this->getMyPackage(),
			'lks'	=> $this->getMyLike(),
			'dwn'	=> $this->getMyDownload()
 		]);
	}

	public function setLangGet(Request $request, Response $response) {
		$lang = $request->getQueryParam('lang');
		$response = FigResponseCookies::set($response, SetCookie::create('lang')->withPath('/')->rememberForever()
->withValue($lang));
		if ($this->auth->authenticated())
			$this->setLang($lang);
		$ref  = $request->getQueryParam('referer');
		if ($ref != null && $ref != '')
			return $response->withRedirect($ref);
		return $response->withRedirect($this->router->pathFor('user.settings'));
	}

	public function passwordPost(Request $request, Response $response) {
		$_ = $this->trans;
		if (isset($GLOBALS['use_xf']) && $GLOBALS['use_xf']) {
			$this->flash->addMessage('error', $_('Not permitted'));
			return $response->withRedirect($this->router->pathFor('home'));
		}
		$this->auth->assertAuth($request, $response);
		$cu = $request->getParam('current');
		$p1 = $request->getParam('password');
		$p2 = $request->getParam('again');
		if (!$this->auth->checkPassword($cu))
			$this->flash->addMessage('error', $_('Old password missmatch...'));
		else if ($p1!=$p2) {
			$this->flash->addMessage('error', $_('Password missmatch'));
		} else {
			$this->flash->addMessage('info', $_('Password changed'));
			$this->auth->setPassword($p1);
		}
		return $response->withRedirect($this->router->pathFor('user.settings'));
	}
}

?>

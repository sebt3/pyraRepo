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
		return $lang;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Page Controlers

	public function settingsPage (Request $request, Response $response) {
		$this->menu->breadcrumb = array(
			array('name' => 'user', 'icon' => 'fa fa-user', 'url' => $this->router->pathFor('user.settings'))
		);
 		return $this->view->render($response, 'settings.twig', []);
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

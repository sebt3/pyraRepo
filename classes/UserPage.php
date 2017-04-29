<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


class UserPage extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Page Controlers

	public function settingsPage (Request $request, Response $response) {
		$this->menu->breadcrumb = array(
			array('name' => 'user', 'icon' => 'fa fa-user', 'url' => $this->router->pathFor('user.settings'))
		);
 		return $this->view->render($response, 'settings.twig', []);
	}
	public function passwordPost(Request $request, Response $response) {
		$this->auth->assertAuth($request, $response);
		$cu = $request->getParam('current');
		$p1 = $request->getParam('password');
		$p2 = $request->getParam('again');
		if (!$this->auth->checkPassword($cu))
			$this->flash->addMessage('error', 'Old password missmatch...');
		else if ($p1!=$p2) {
			$this->flash->addMessage('error', 'Password missmatch');
		} else {
			$this->flash->addMessage('info', 'Password changed');
			$this->auth->setPassword($p1);
		}
		return $response->withRedirect($this->router->pathFor('user.settings'));
	}
}

?>

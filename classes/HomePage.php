<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


class HomePage extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

	public function homePage (Request $request, Response $response) {
		//$this->logger->addInfo("Dashboard");
		//$this->flash->addMessage('error', 'Could not change password with those details.');
 		//return $this->view->render($response, 'home.twig', []);
 		return $response->withRedirect($this->router->pathFor('apps.list'));
	}
}

?>

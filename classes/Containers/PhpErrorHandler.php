<?php
namespace Containers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Handlers\PhpError;
use \Slim\Views\Twig; 
use \Slim\Http\Body;

class PhpErrorHandler extends \Slim\Handlers\PhpError {
	private $view;

	public function __construct(Twig $view) { 
		$this->view = $view; 
	}

	protected function renderArrayException(\Throwable $error) {
		return ['type'  => get_class($error),
			'code'  => $error->getCode(),
			'msg'   => $error->getMessage(),
			'file'  => $error->getFile(),
			'line'  => $error->getLine(),
			'trace' => $error->getTraceAsString()
		];
	}

	public function __invoke(Request $request, Response $response, \Throwable $e) { 
		$contentType = $this->determineContentType($request);
		switch ($contentType) {
		case 'application/json':
			$output = $this->renderJsonErrorMessage($error);
			break;

		case 'text/xml':
		case 'application/xml':
			$output = $this->renderXmlErrorMessage($error);
			break;

		case 'text/html':
		default:
			$err = array();
			$err[] = $this->renderArrayException($e);
			while ($e = $e->getPrevious())
				$err[] = $this->renderArrayException($e);
			$this->view->render($response, 'errors/500.twig', [ 'errors' => $err ]);
			return $response->withStatus(500)->withHeader('Content-Type', 'text/html');
		}

		$this->writeToErrorLog($error);

		$body = new Body(fopen('php://temp', 'r+'));
		$body->write($output);

		return $response
			->withStatus(500)
			->withHeader('Content-type', $contentType)
			->withBody($body);
	}

}

<?php
namespace Containers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Handlers\Error;
use \Slim\Views\Twig; 

class ErrorHandler extends Error {
	private $view;

	public function __construct(Twig $view) { 
		$this->view = $view; 
	}

	protected function renderArrayException(\Exception $exception) {
		return ['type'  => get_class($exception),
			'code'  => $exception->getCode(),
			'msg'   => $exception->getMessage(),
			'file'  => $exception->getFile(),
			'line'  => $exception->getLine(),
			'trace' => $exception->getTraceAsString()
		];
	}

	public function __invoke(Request $request, Response $response, \Exception $e) { 
		$contentType = $this->determineContentType($request);
		switch ($contentType) {
		case 'application/json':
			$output = $this->renderJsonErrorMessage($exception);
			break;

		case 'text/xml':
		case 'application/xml':
			$output = $this->renderXmlErrorMessage($exception);
			break;

/*		case 'text/html':
			$output = $this->renderHtmlErrorMessage($exception);
			break;*/
		default:
			$response->getBody()->rewind();
			$err = array();
			$err[] = $this->renderArrayException($e);
			while ($e = $e->getPrevious())
				$err[] = $this->renderArrayException($e);
			$this->view->render($response, 'errors/500.twig', [ 'excepts' => $err ]);
			return $response->withStatus(500)->withHeader('Content-Type', 'text/html');
		}

		$this->writeToErrorLog($exception);

		$body = new Body(fopen('php://temp', 'r+'));
		$body->write($output);

		return $response
			->withStatus(500)
			->withHeader('Content-type', $contentType)
			->withBody($body);
	}

}

?>


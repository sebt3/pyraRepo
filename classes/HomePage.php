<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


class HomePage extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// WidgetData
/*	private function getFailedServices() {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Failed services');
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('host'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('service'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('since'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('status'), 'class'=> 'sortable');
		$ret['body'] = [];
		$s = $this->db->prepare('select (UNIX_TIMESTAMP()*1000-f.timestamp)/1000 as late_sec, h.id as host_id, s.id as serv_id, f.status, s.name as service, h.name as host, f.timestamp 
  from s$failed f, s$services s, h$hosts h 
 where f.serv_id = s.id and s.host_id = h.id order by status, late_sec desc');
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = array(
				'rowProperties'	=> array(
					'color'	=> $this->getStatusColor($r['status'],$r['late_sec']),
					'url'	=> $this->router->pathFor('service', [ 'sid' => $r['serv_id'], 'hid'=> $r['host_id']])
				),
				'host'	=> array('text'	=> $r['host']),
				'serv'	=> array('text'	=> $r['service']),
				'time'	=> array('text'	=> $this->formatTimestamp($r['timestamp'])),
				'status'=> array('text'	=> $r['status'])
			);
		}
		return $ret;
	}*/

/////////////////////////////////////////////////////////////////////////////////////////////
// Widget Controlers

/*	public function widgetTableEvent (Request $request, Response $response) {
		$response->getBody()->write(json_encode($this->getActivesEvents()));
		return $response->withHeader('Content-type', 'application/json');
	}*/

/////////////////////////////////////////////////////////////////////////////////////////////
// Page Controlers

	public function homePage (Request $request, Response $response) {
		//$this->logger->addInfo("Dashboard");
		//$this->flash->addMessage('error', 'Could not change password with those details.');
 		return $this->view->render($response, 'home.twig', []);
	}
}

?>

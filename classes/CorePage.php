<?php
use Interop\Container\ContainerInterface;

class CorePage extends core {
	protected function getArchId($name) {
		$i = $this->db->prepare('insert into archs(name) values (:name) on duplicate key update name=:name');
		$i->bindParam(':name', $name,  PDO::PARAM_STR);
		$i->execute();
		$s = $this->db->prepare('select id from archs where name=:name');
		$s->bindParam(':name', $name,  PDO::PARAM_STR);
		$s->execute();
		$r = $s->fetch();
		return $r['id'];
	}
	protected function isPackageMaintainer($id) {
		return $this->auth->isPackageMaintainer($id);
	}
}

?>

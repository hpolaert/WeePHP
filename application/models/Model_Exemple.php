<?php
class Model_Exemple extends RedBean_SimpleModel {
	public function update() {
		if(count($this->bean->ownAnotherObject) > 10 )
		throw new Exception('Too many objects!');
	}
}
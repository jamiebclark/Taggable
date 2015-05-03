<?php
App::uses('Component', 'Controller');
App::uses('Utility', 'Inflector');

class TaggableComponent extends Component {
	public $name = 'Taggable';

	public $controller;
	public $request;

	public function initialize(Controller $controller) {
		$this->controller = $controller;
		$this->request = $controller->request;

		return parent::initialize($controller);
	}

/**
 * Checks for passed tag parameters and adds them to a query array
 *
 * @param array $query The query
 * @return $query;
 **/
	public function filterQuery($query = array()) {
		$tags = array();
		if (!empty($this->request->named['tag'])) {
			$tags = array($this->request->named['tag']);
			foreach ($tags as $k => $tag) {
				$tags[$k] = Inflector::humanize($tag);
			}
			$query['tags'] = $tags;
		}
		$this->controller->set(array('taggableTags' => $tags));
		return $query;
	}
}
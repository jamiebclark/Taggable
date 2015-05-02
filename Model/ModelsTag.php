<?php
App::uses('TaggableAppModel', 'Taggable.Model');
class ModelsTag extends TaggableAppModel {
	public $name = 'ModelsTag';
	public $hasMany = array(
		'Taggable.Tag'
	);

	public $recursive = -1;
}
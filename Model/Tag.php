<?php
App::uses('TaggableAppModel', 'Taggable.Model');
class Tag extends TaggableAppModel {
	public $name = 'Tag';
	public $displayField = 'tag';
	
	public $recursive = -1;
	
	public function beforeSave($options = array()) {
		$data =& $this->getData();
		// Formats the tag before saving
		if (isset($data['tag'])) {
			$data['tag'] = $this->formatTag($data['tag']);
		}
		return parent::beforeSave($options);		
	}
	
/**
 * Returns an array of all the Models linked to the Tag model
 *
 * @return array;
 **/
	public function getTaggableModels() {
		return array_keys($this->hasAndBelongsToMany);
	}
	
	public function findIdsFromTag($tags) {
		$result = $this->find('list', array(
			'conditions' => array(
				$this->escapeField('tag') => $this->formatTag($tags)
			)
		));
		return array_keys($result);
	}
	
/**
 * Formats a tag for saving
 *
 * @param string|array $tag A single tag or an array of tags
 * @return string|array The formatted tag(s)
 **/
	public function formatTag($tag) {
		if (is_array($tag)) {
			foreach ($tag as $k => $t) {
				$tag[$k] = $this->formatTag($t);
			}
		} else {
			$tag = trim(strtolower(preg_replace('/[^\sa-zA-Z0-9]/','',$tag)));
		}
		return $tag;
	}
	
	public function newTags($tagStr = null) {
		return tagStrToArray($tagStr);
	}
	
/**
 * Remove any tags that aren't related to any models
 *
 * @return void;
 **/
	public function deleteEmpty() {
		$models = $this->getTaggableModels();
		$options = array(
			'fields' => array($this->escapeField($this->primaryKey)),
			'link' => $models,
		);
		foreach ($models as $model) {
			$options['conditions'][] = "$model.id IS NULL";
		}
		$tags = $this->find('all', $options);
		$ids = array();
		foreach ($tags as $tag) {
			$ids[] = $tag['Tag'][$this->primaryKey];
		}
		if (!empty($ids)) {
			return $this->deleteAll(array($this->escapeField($this->primaryKey) => $ids));
		} else {
			return null;
		}
	}
}
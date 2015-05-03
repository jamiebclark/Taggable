<?php
App::uses('ModelBehavior', 'Model');
Configure::load('Taggable.config');

class TaggableBehavior extends ModelBehavior {
	public $name = 'Taggable';

/**
 * The Tag model object
 * @var Model
 **/
	protected $Tag;
	
	protected $saveTagIds = array();


	public function setup(Model $Model, $settings = array()) {
		$this->Tag = ClassRegistry::init('Taggable.Tag');
		$this->bindModel($Model);
		return parent::setup($Model, $settings);
	}

	public function beforeFind(Model $Model, $query = array()) {
		if (!empty($query['tags'])) {
			$tags = $query['tags'];
			unset($query['tags']);
			$query = $this->filterTagQuery($Model, $query, $query['tags']);
		}
		return $query;
	}

	public function afterFind(Model $Model, $results, $primary = false) {
		if (!empty($results[0][$Model->alias])) {
			$resultField = Configure::read('Taggable.resultField');
			foreach ($results as $k => $row) {
				$results[$k][$Model->alias][$resultField] = array();
				if (!empty($row['Tag'])) {
					// Adds an ID => TAG array to the model's result
					foreach ($row['Tag'] as $tag) {
						$results[$k][$Model->alias][$resultField][$tag['id']] = $tag['tag'];
					}
				}
			}
			return $results;
		}
		return parent::afterFind($Model, $results, $primary);
	}

	public function beforeSave(Model $Model, $options = array()) {
		$data =& $Model->data;
		$saveField = Configure::read('Taggable.saveField');

		$tags = array();
		if (!empty($data['Tag']['Tag'])) {
			foreach ($data['Tag']['Tag'] as $k => $tagId) {
				if (empty($tagId)) {
					continue;
				} else if (!is_numeric($tagId)) {
					$tagId = array_pop($this->translateTagStrToArray($tagId));
				}
				$tags[] = $tagId;
			}
		}

		if (!empty($data[$Model->alias][$saveField])) {
			$newTags = $this->translateTagStrToArray($data[$Model->alias][$saveField]);
			if (empty($tags)) {
				$tags = $newTags;
			} else {
				foreach ($newTags as $tagId) {
					$tags[] = $tagId;
				}
				// Makes sure the new tags are not already existing tags
				$tags = array_keys(array_flip($tags));
			}
		}
		$this->saveTagIds[$Model->alias] = $tags;

		return true;
	}

	public function afterSave(Model $Model, $created, $options = array()) {
		$id = $Model->id;
		if (!empty($this->saveTagIds[$Model->alias])) {
			$this->saveTagIds($Model, $id, $this->saveTagIds[$Model->alias]);
			$this->saveTagIds[$Model->alias] = array();
		}
		return parent::afterSave($Model, $created, $options);
	}
	
	protected function saveTagIds(Model $Model, $id, $tagIds = array()) {
		$ModelsTag = CLassRegistry::init('Taggable.ModelsTag');
		$modelTags = $ModelsTag->find('all', array(
			'fields' => array(
				$ModelsTag->escapeField('tag_id'),
				$ModelsTag->escapeField('id'),
			),
			'conditions' => array(
				'ModelsTag.model_name' => $Model->name,
				'ModelsTag.model_id' => $id,
			)
		));
		$modelTagIds = Hash::combine($modelTags, '{n}.ModelsTag.tag_id', '{n}.ModelsTag.id');

		$data = array();
		foreach ($tagIds as $tagId) {
			$data[] = array(
				'id' => !empty($modelTagIds[$tagId]) ? $modelTagIds[$tagId] : null,
				'model_name' => $Model->name,
				'model_id' => $id,
				'tag_id' => $tagId
			);
			unset($modelTagIds[$tagId]);
		}
		if (!empty($modelTagIds)) {
			$ModelsTag->deleteAll(array('ModelsTag.id' => $modelTagIds));
		}
		if (!empty($data)) {
			$ModelsTag->saveAll($data, array('callbacks' => false));
		}
		return count($data);
	}

/**
 * Filters an existing query by a set of tags
 *
 * @param Model $Model The referenced model object
 * @param array $query The existing query values
 * @param array $tags The tags used to filter the query
 * @return array;
 **/
	public function filterTagQuery(Model $Model, $query = array(), $tags = array()) {
		if (!empty($tags)) {
			if (!is_array($tags)) {
				$tags = $this->translateTagStrToArray($tags);
			}
			foreach ($tags as $k => $tagId) {
				$joinAlias = 'TagFilterJoin' . $k;
				$tagAlias = 'TagFilter' . $k;
				$type = 'INNER';
				$conditions = array(
					$this->Tag->escapeField(null, $tagAlias) => $tagId
				);
				$query = $this->joinTags($Model, $query, compact('joinAlias', 'tagAlias', 'conditions', 'type'));
			}
		}
		return $query;
	}
	
/**
 * Finds a list of tags along with how many times they appear
 *
 * @param Model $Model The current Model object
 * @param array $query The query to filter the result
 * @return array;
 **/
	public function findTagCount(Model $Model, $query = array()) {
		$query['fields'] = array(
			'Tag.id',
			'Tag.tag',
			'COUNT(Tag.id) AS tag_count',
		);
		if (!isset($query['link']['Tag'])) {
			$query['link']['Tag'] = array();
		}
		$query['conditions'][]['Tag.tag <>'] = '';
		if ($blockTags) {
			$query['conditions'][]['NOT']['Tag.id'] = $this->translateTagStrToArray($blockTags);
		}
		$query['group'] = 'Tag.id';
		$query['order'] = 'Tag.tag';
		unset($query['limit']);
		
		$result = $Model->find('all', $query);
		$tags = array();
		foreach ($result as $row) {
			$tags[$row['Tag']['tag']] = $row[0]['tag_count'];
		}
		return $tags;
	}

/**
 * Converts an array of tags to a comma-separated string
 *
 * @param array $tags The array of the tags
 * @return string;
 **/
	protected function translateTagArrayToStr($tags = null) {
		if (empty($tags)) {
			return null;
		} else if (!is_array($tags)) {
			return $tags;
		}
		$return = '';
		$count = count($tags);
		for ($i = 0; $i < $count; $i++) {
			if ($i > 0) {
				$return .= ', ';
			}
			$return .= is_array($tags[$i]) ? $tags[$i]['tag'] : $tags[$i];
		}
		return $return;
	}
	
/**
 * Converts a comma-separated string of tags into an array of the tag ids
 * If any tags don't exist, it will save them and record their new id
 * 
 * @param string $tagStr The comma-separated list of tags
 * @return array;
 **/
	protected function translateTagStrToArray($tagStr = null) {
		if (empty($tagStr)) {
			return null;
		}
		$tags = explode(',', $tagStr);
		$insert = array();					// New Tags to be saved
		foreach ($tags as $k => $tag) {
			$tag = $this->Tag->formatTag($tag);
			$tags[$k] = $tag;
			$insert[$tag]['tag'] = $tag;
		}
		// Finds the ids of the new tags
		$result = $this->Tag->find('list', array(
			'conditions' => array(
				$this->Tag->escapeField('tag') => $tags
			)
		));
		
		// Removes existing tags
		if (!empty($result)) {
			foreach ($result as $id => $tag) {
				unset($insert[$tag]);
			}
		}
		
		// Inserts any non-existent tags
		if (!empty($insert)) {
			$insert = array_values($insert);
			$this->Tag->create();
			$this->Tag->saveAll($insert);
			
			// Finds the ids of the new tags
			$result = $this->Tag->find('list', array(
				'conditions' => array(
					$this->Tag->escapeField('tag') => $tags
				)
			));
		}
		return !empty($result) ? array_keys($result) : null;
	}

/**
 * Binds the model to the Tag table
 *
 * @param Model $Model The model object being linked
 * @return void;
 **/
	protected function bindModel(Model $Model) {
		$Model->bindModel(array(
			'hasAndBelongsToMany' => array(
				'Tag' => array(
					'className' => 'Taggable.Tag',
					'with' => 'ModelsTag',
					'foreignKey' => 'model_id',
					'associationForeignKey' => 'tag_id',
					'conditions' => array(
						'ModelsTag.model_name' => $Model->name,
					)
				)
			),
			'hasMany' => array(
				'ModelsTag' => array(
					'className' => 'Taggable.ModelsTag',
					'foreignKey' => 'model_id',
					'conditions' => array(
						'ModelsTag.model_name' => $Model->name,
					)
				)
			),
		), false);
		$this->Tag->bindModel(array(
			'hasAndBelongsToMany' => array(
				$Model->name => array(
					'className' => $Model->name,
					'with' => 'ModelsTag',
					'foreignKey' => 'tag_id',
					'associationForeignKey' => 'model_id',
					'conditions' => array(
						'ModelsTag.model_name' => $Model->name,
					)
				)
			)
		), false);
	}

/**
 * Joins the Model with the Tags table
 *
 * @param Model $Model The current model object
 * @param array $query The existing query
 * @param array $options Additional parameters
 * 	- string $joinAlias The alias of the TagJoin model
 * 	- string $tagAlias The alias of the Tag model
 * 	- string $type The type of JOIN to use
 * 	- array $conditions Additional conditions to filter the tag
 * @return array;
 **/
	protected function joinTags(Model $Model, $query = array(), $options = array()) {
		$default = array(
			'joinAlias' => 'ModelsTag',
			'tagAlias' => $this->Tag->alias,
			'type' => 'INNER',
			'conditions' => array()
		);
		$options = array_merge($default, $options);
		extract($options);

		$tagDb = $this->Tag->getDataSource();
		$conditions[] = $this->Tag->escapeField(null, $tagAlias) . ' = ' . $this->Tag->ModelsTag->escapeField('tag_id', $joinAlias);

		$query['joins'][] = array(
			'table' => $tagDb->fullTableName($this->Tag->ModelsTag),
			'alias' => $joinAlias,
			'type' => $type,
			'conditions' => array(
				$this->Tag->ModelsTag->escapeField('model_id', $joinAlias) . ' = ' . $Model->escapeField(),
				$this->Tag->ModelsTag->escapeField('model_name', $joinAlias) => $Model->name,
			),
		);

		$query['joins'][] = array(
			'table' => $tagDb->fullTableName($this->Tag),
			'alias' => $tagAlias,
			'type' => $type,
			'conditions' => $conditions,
		);
		return $query;
	}
}
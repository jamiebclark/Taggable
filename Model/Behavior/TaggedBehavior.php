<?php
class TaggableBehavior extends ModelBehavior {
	var $name = 'Taggable';
	var $saveTags = array();
	var $tagTable = 'Tag';		function beforeSave(&$Model, $options) {
		$data =& $Model->data;
		$tags = array();
		if (!empty($data[$this->tagTable][$this->tagTable])) {
			foreach ($data[$this->tagTable][$this->tagTable] as $k => $tagId) {
				if (empty($tagId)) {
					continue;
				} else if (!is_numeric($tagId)) {
					$tagId = array_pop($this->_tagStrToArray($tagId));
				}
				$tags[] = $tagId;
			}
		}
		if (!empty($data[$Model->alias]['new_tags'])) {
			$newTags = $this->_tagStrToArray($data[$Model->alias]['new_tags']);
			if (empty($tags)) {
				$tags = $newTags;
			} else {
				foreach ($newTags as $tagId) {
					$tags[] = $tagId;
				}
				//Makes sure the new tags are not already existing tags
				$tags = array_keys(array_flip($tags));
			}
			//$this->saveTags[$Model->alias] = $tags;
		}
		if (!empty($tags)) {
			$data[$this->tagTable][$this->tagTable] = $tags;
		}

		return true;
	}
	/*
	function afterSave(&$Model, $created) {
		$id = $Model->id;
		if (!empty($this->saveTags[$Model->alias])) {
			$data = array();
			foreach ($this->saveTags[$Model->alias] as $tagId) {
				$data[] = array(
					$Model->alias => array('id' => $Model->id),
					$this->tagTable => array('id' => $tagId)
				);
			}			$Model->create();
			$Model->saveAll($data, array('callbacks' => false));
			unset($this->saveTags[$Model->alias]);
			$Model->read(null, $id);
		}
		return true;
	}
	*/
	
	function filterTagOptions(Model $Model, $options = array(), $tags = array()) {
		$tagJoin = $Model->hasAndBelongsToMany[$this->tagTable];
		
		if (!empty($tags)) {
			if (!is_array($tags)) {
				$tags = $this->_tagStrToArray($tags);
			}
			foreach ($tags as $k => $tagId) {
				$alias = 'TagFilterJoin' . $k;
				$conditions = array(
					sprintf('%s.%s = %s.%s', $alias, $tagJoin['foreignKey'],$Model->alias,$Model->primaryKey),
					$alias . '.' . $tagJoin['associationForeignKey'] => $tagId,
				);
				$options['joins'][] = array('table'=>$tagJoin['joinTable'],'type'=>'INNER') + compact('alias', 'conditions');
			}
			/*
			$options['joins'][] = array(
				'table' => $tagJoin['joinTable'],
				'alias' => 'TagFilterJoin',
				'type' => 'INNER',
				'conditions' => sprintf('TagFilterJoin.%s = %s.%s', $tagJoin['foreignKey'],$Model->alias,$Model->primaryKey),
			);
			foreach ($tags as $k => $tagId) {
				$alias = 'TagFilter' . $k;
				$conditions = array(
					sprintf('TagFilterJoin.%s = %s.id', $tagJoin['associationForeignKey'], $alias),
					$alias . '.id' => $tagId,
				);
				$options['joins'][] = array('table' => 'tags','type' => 'INNER') + compact('alias', 'conditions');
			}
			*/
		}
		return $options;
	}
	
	function findTagCount(Model $Model, $options = array(), $blockTags = false) {
		$options['fields'] = array(
			'Tag.id',
			'Tag.tag',
			'COUNT(Tag.id) AS tag_count',
		);
		if (!isset($options['link'][$this->tagTable])) {
			$options['link'][$this->tagTable] = array();
		}
		$options['conditions'][]['Tag.tag <>'] = '';
		if ($blockTags) {
			$options['conditions'][]['NOT']['Tag.id'] = $this->_tagStrToArray($blockTags);
		}
		$options['group'] = 'Tag.id';
		$options['order'] = 'Tag.tag';
		unset($options['limit']);
		
		$result = $Model->find('all', $options);
		$tags = array();
		foreach ($result as $row) {
			$tags[$row[$this->tagTable]['tag']] = $row[0]['tag_count'];
		}
		return $tags;
	}

	function _tagArrayToStr($tags = null) {
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
			$return .= $tags[$i];
		}
		return $return;
	}
	
	function _tagStrToArray($tagStr = null) {
		if (empty($tagStr)) {
			return null;
		}
		
		App::import('Model', $this->tagTable);
		$Tag = new Tag();
		
		$tags = explode(',', $tagStr);
		
		$insert = array();
		foreach ($tags as $k => $tag) {
			$tag = $Tag->formatTag($tag);
			$tags[$k] = $tag;
			$insert[$tag]['tag'] = $tag;
		}
		
		//Finds the ids of the new tags
		$result = $Tag->find('list', array('conditions' => array($Tag->alias . '.tag' => $tags)));
		
		//Removes existing tags
		if (!empty($result)) {
			foreach ($result as $id => $tag) {
				unset($insert[$tag]);
			}
		}
		
		//Inserts any non-existent tags
		if (!empty($insert)) {
			$insert = array_values($insert);
			$Tag->create();
			$Tag->saveAll($insert);
			
			//Finds the ids of the new tags
			$result = $Tag->find('list', array('conditions' => array($Tag->alias . '.tag' => $tags)));
		}
		
		return !empty($result) ? array_keys($result) : null;
	}
}
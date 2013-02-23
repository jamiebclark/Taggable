<?php
class TagHelper extends AppHelper {
	var $name = 'Tag';
	var $helpers = array('Html', 'Form');
		
	function tag($text, $options = array()) {
		$options = array_merge(array(
			'tag' => 'span',
			'x' => null,
			'url' => null,
		), $options);
		extract($options);
		$out = $text;
		if ($url) {
			$out = $this->Html->link($out, $this->__getTagUrl($text, $url));
		}
		if ($x) {
			$out .= $this->__getXUrl($x);
		}
		return $this->Html->tag($tag, $out, array('class' => 'tag'));
	}
			function tagList($tags = array(), $options = array()) {		$options = array_merge(array(			'controller' => $this->request->params['controller'],			'action' => 'index',			'separator' => ' ',		), $options);		extract($vars);		if (empty($url) || !is_array($url)) {			$url = compact('controller', 'action');		}		$out = '';		if (!empty($tags)) {			$count = count($tags);			foreach ($tags as $k => $tag) {				if ($k > 0) {					$out .= $separator;				}				if (!empty($tag['Tag'])) {					$tag = $tag['Tag'];				}				$out .= $this->tag($tag['tag'], compact('url'));			}		}		return $this->Html->div('tags list', $out);	}	/***	 * Outputs tags, adjusting font-size by how frequently they are in the system	 * Requires a $tagCount array, formatted like:		$tagCount = array(			'dog' => 50,			'cat' => 3,			'lizard' => 10,		);	***/	function cloud($tagCount = array(), $options = array()) {		if (empty($tagCount)) {			return false;		}		$options = array_merge(array(			'minFontSize' => 10,			'maxFontSize' => 36,			'fontUnit' => 'px',		), $options);		extract($options);		$min = min($tagCount);		$max = max($tagCount);		$passTag = !empty($this->request->named['tag']) ? $this->request->named['tag'] . ',' : '';				$out = '';		foreach ($tagCount as $tag => $count) {			if ($max == $min || $count == $min) {				$fontSize = $minFontSize;			} else {				$fontSize = ((($maxFontSize - $minFontSize) * ($count - $min)) / ($max - $min)) + $minFontSize;			}			$out .= $this->Html->link($tag, array('tag' => $passTag . $tag), array(				'class' => 'tag',				'style' => 'font-size:' . $fontSize . $fontUnit,				'title' => 'Found ' . $count . ' Tagged as "' . $tag . '"',			));		}		return $this->Html->div('tags-cloud', $out);	}	//Displays tags editing in a form	//Model must have HABTM relationship with Tags table	//Must set a $tags list value of all available tags	function input($options = array()) {		$options = array_merge(array(			'tags' => array(),			'class' => 'tags clearfix fullFormWidth',			'label' => 'Add Tags:',			'prefix' => ''.		), $options);		extract($options);		if (empty($tags) && $this->Html->value($prefix . 'Tag')) {			$tags = array();			if (!empty($this->request->data['Tag']['Tag'])) {				$tags = $this->request->data['Tag']['Tag'];			} else {				foreach ($this->request->data['Tag'] as $k => $tag) {					$tags[$tag['id']] = $tag['tag'];				}			}		}		$out = '';		$out .= $this->Html->tag('h3', 'Tags');		if (!empty($tags)) {			if (isset($tags[0]['id'])) {				$newTags = array();				foreach ($tags as $tag) {					$newTags[$tag['id']] = $tag['tag'];				}				$tags = $newTags;			}			$out .= $this->Html->div('tagsList', $this->Form->input(				$prefix . 'Tag.Tag', array(					'options' => $tags,					'multiple' => 'checkbox', 					'label' => false, 					'value' => array_keys($tags)	//selected				)			));		}		$out .= $this->Form->input($prefix . 'new_tags', compact('label') + array(			'type' => 'text', 			'placeholder' => 'Separate tags with commas (ie: youth, service, food bank)',		));		return $this->Html->div($class, $out);	}		
	function __getTagUrl($tag, $url = true) {
		if ($url === true) {
			$url = $this->__currentUrl();
		}
		$slug = Inflector::slug($tag);
		if (is_array($url)) {
			$url['tag'] = $slug;
		} else {
			$url .= '/tag:' . $slug;
		}
		return $url;
	}
	
	function __getXUrl($url = array()) {
		if (!$url) {
			return '';
		}
		if ($url === true || $url == 'remove') {
			$url = array('tag' => false) + $this->__currentUrl();			
		}
		return $this->Html->tag('span', $this->Html->link('X', $url), array('class' => 'x'));
	}
	
	function __currentUrl() {
		$url = Router::parse($this->request->url);
		if (!empty($url['pass'])) {
			$url['?'] = $url['pass'];
			unset($url['pass']);
		}
		if (!empty($url['named'])) {
			$url = $url['named'] + $url;
			unset($url['named']);
		}
		return $url;
	}
}
<?php
class TagHelper extends AppHelper {
	var $name = 'Tag';
	var $helpers = array(
		'Html', 
		'Form', 
		'Layout',
		'Iconic',
	);
	
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
	
	function tagList($tags = null, $options = array()) {
		if (empty($tags)) {
			return '';
		}
		$list = array();
		foreach ($tags as $tag) {
			if (isset($tag['tag'])) {
				$tag = $tag['tag'];
			}
			$list[] = $this->tag($tag, $options);
		}
		return $this->Html->div('tags', $this->Layout->menu($list));
	}
	
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
		return $this->Iconic->icon('x', array('class' => 'close') + compact('url'));
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
?>
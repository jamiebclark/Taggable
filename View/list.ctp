<?php
$vars = array_merge(compact('action'), array(
	'controller' => $this->request->params['controller'],
	'action' => 'index',
	'separator' => ' ',
));
extract($vars);

if (empty($ury) || !is_array($url)) {
	$url = compact('controller', 'action');
}
?>
<div class="tags list">
<?php
if (!empty($tags)) {
	$count = count($tags);
	foreach ($tags as $k => $tag) {
		if ($k > 0) {
			echo $separator;
		}
		if (!empty($tag['Tag'])) {
			$tag = $tag['Tag'];
		}
		echo $this->Html->link($tag['tag'], $url + array('tag' => $tag['tag']));		
	}
}
?>
</div>
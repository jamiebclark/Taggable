<?php
if (empty($tagCount)) {
	return false;
}
$minFontSize = 10;
$maxFontSize = 36;
$fontUnit = 'px';

$min = min($tagCount);
$max = max($tagCount);
$passTag = !empty($this->request->named['tag']) ? $this->request->named['tag'] . ',' : '';
?>
<div class="tag-cloud">
<?php 
foreach ($tagCount as $tag => $count) {
	if ($max == $min || $count == $min) {
		$fontSize = $minFontSize;
	} else {
		$fontSize = ((($maxFontSize - $minFontSize) * ($count - $min)) / ($max - $min)) + $minFontSize;
	}
	echo $this->Html->link($tag, array('tag' => $passTag . $tag), array(
		'class' => 'tag',
		'style' => 'font-size:' . $fontSize . $fontUnit,
		'title' => 'Found ' . $count . ' Tagged as "' . $tag . '"',
	));
}
?>
</div>
<?php
//Displays tags editing in a form
//Model must have HABTM relationship with Tags table
//Must set a $tags list value of all available tags

if (empty($tags) && $this->Html->value('Tag')) {
	$tags = array();
	if (!empty($this->request->data['Tag']['Tag'])) {
		$tags = $this->request->data['Tag']['Tag'];
	} else {
		foreach ($this->request->data['Tag'] as $k => $tag) {
			$tags[$tag['id']] = $tag['tag'];
		}
	}
}
?>
<div class="tags clearfix fullFormWidth">
<h3>Tags</h3>
<?php
if (!empty($tags)) {
	if (isset($tags[0]['id'])) {
		$newTags = array();
		foreach ($tags as $tag) {
			$newTags[$tag['id']] = $tag['tag'];
		}
		$tags = $newTags;
	}
	echo $this->Html->div('tagsList', $this->Form->input(
		'Tag.Tag', array(
			'options' => $tags,
			'multiple' => 'checkbox', 
			'label' => false, 
			'value' => array_keys($tags)	//selected
		)
	));
}
echo $this->Form->input('new_tags',array(
	'type' => 'text', 
	'label' => 'Add Tags:',
	'placeholder' => 'Separate tags with commas (ie: youth, service, food bank)',
));
?>
</div>
(function($) {
	$(document).ready(function() {
		$('.taggable-tags-input-checkboxes > label').each(function() {
			var $label = $(this);
			$label
				.on('toggleSelectedClass', function() {
					if ($('input', $label).is(':checked')) {
						$label.addClass('selected');
					} else {
						$label.removeClass('selected');
					}
				})
				.click(function(e) {
					$label.trigger('toggleSelectedClass');
					return $(this);
				})
				.trigger('toggleSelectedClass');
		});
	});
})(jQuery);
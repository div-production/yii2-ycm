$(function () {
	var config = $(window).data('multiFieldConfig');
	if(!config) return;
	
	var removeButtons = $('.js-field-remove'),
		addButtons = $('.js-field-add'),
		template = config.template;

	removeButtons.click(removeField);

	addButtons.click(function () {
		var el = $(this);
		$(template).insertBefore(el).find('.js-field-remove').click(removeField);
	});
	
	function removeField() {
		$(this).parent().slideUp(400, function () {
			$(this).remove();
		});
	}
});
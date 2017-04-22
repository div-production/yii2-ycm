(function () {
	var blocks = $('.file-input');
	if(blocks.length == 0) return;

	blocks.each(function (key, el) {
		var block = $(el);
		block.find('input[type="file"]').on('change', function () {
			var caption = $('.input-caption', block);
			caption.text('Выбрано файлов: ' + this.files.length);
		});

		var previews = blocks.find('.file-preview-frame');
		previews.each(function (key, el) {
			var preview = $(el);
			preview.find('.kv-file-remove').click(function () {
				var id = preview.data('id');
				var removeInput = $('<input type="hidden">').
						attr('name', block.data('remove-name')).
						attr('value', id);
				preview.remove();
				block.append(removeInput);
			});
		});
	});
})();
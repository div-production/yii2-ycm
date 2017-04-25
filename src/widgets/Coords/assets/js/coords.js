$(function () {
	var button = $('.js-coords-button');
	if(!button.length) return;
	
	button.click(function (e) {
		var el = $(this);
		var address = $('#' + coordsWidgetAttributeId);
		
		$.ajax({
			url: 'https://geocode-maps.yandex.ru/1.x/?geocode='+ address.val() +'&results=1',
			success: function (data) {
				var coords = data.getElementsByTagName('pos')[0].innerHTML;
				el.parent().parent().find('input').val(coords.split(' ').reverse().join(','));
			}
		});
	});
});
$(function () {
    var config = $(window).data('multiFieldConfig');
    if(!config) return;

    var removeButtons = $('.js-field-remove'),
        addButtons = $('.js-field-add'),
        templates = config;

    removeButtons.click(removeField);

    addButtons.click(function () {
        var el = $(this);
        var widget_attr = el.attr('widget_attr');
        $(templates[widget_attr]).insertBefore(el).find('.js-field-remove').click(removeField);
    });

    function removeField() {
        $(this).parent().slideUp(400, function () {
            $(this).remove();
        });
    }
});
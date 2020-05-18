(function() {
    $(document).ready(function() {
        $('body').on('click', '.selection-update', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var button = $(this);
            var url = button.attr('data-url');
            $.ajax(url).done(function(data) {
                button.replaceWith(data.content);
            });
        });
    });
})();

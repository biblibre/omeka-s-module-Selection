(function() {
    $(document).ready(function() {
        $('body').on('click', '.selection-update', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var button = $(this);
            var url = button.attr('data-url');
            $.ajax(url)
            .done(function(data) {
                if (data.status === 'success') {
                    let selectionItem = data.data.selection_item;
                    if (selectionItem.status === 'success') {
                        button.replaceWith(selectionItem.content);
                    }
                }
            });
        });
    });
})();

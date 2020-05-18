(function() {
    $(document).ready(function() {
        $('body').on('click', '.selection-update', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Check if the main omeka js is loaded to get translations.
            var isOmeka = typeof Omeka !== 'undefined' && typeof Omeka.jsTranslate !== 'undefined';
            var button = $(this);
            var url = button.attr('data-url');
            $.ajax(url)
            .done(function(data) {
                if (data.status === 'success') {
                    let selectionItem = data.data.selection_item;
                    if (selectionItem.status === 'success') {
                        // See template selection-button.phtml.
                        let selectionText = selectionItem.inside ? 'Unselect' : 'Select';
                        selectionText = isOmeka ? Omeka.jsTranslate(selectionText) : selectionText;
                        button
                            .prop('class', 'selection-update ' + (selectionItem.inside ? 'selection-delete btn-danger' : 'selection-add btn-primary'))
                            .html('<span class="fas fa-bookmark"></span> ' + selectionText);
                    }
                }
            });
        });
    });
})();

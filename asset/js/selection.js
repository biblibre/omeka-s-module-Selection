(function() {
    $(document).ready(function() {

        $('body').on('click', '.selection-update, .selection-delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var button = $(this);
            var url = button.attr('data-url');
            $.ajax(url)
            .done(function(data) {
                if (data.status === 'success') {
                    let selectionItem = data.data.selection_item;
                    if (selectionItem.status === 'success') {
                        updateSelectionButton(selectionItem);
                        updateSelectionList(selectionItem);
                    }
                }
            });
        });

        $('body').on('click', '.selection-list-toggle', function() {
            $(this).toggleClass('active');
            $('.selection-list').toggle().toggleClass('active');
           return false;
        });

        var updateSelectionButton = function(selectionItem) {
            let selectionButton = $('.selection-update[data-id=' + selectionItem.id + ']');
            if (!selectionButton.length) {
                return;
            }
            // Check if the main omeka js is loaded to get translations.
            var isOmeka = typeof Omeka !== 'undefined' && typeof Omeka.jsTranslate !== 'undefined';
            // See template selection-button.phtml.
            let selectionText = selectionItem.inside ? 'Unselect' : 'Select';
            selectionText = isOmeka ? Omeka.jsTranslate(selectionText) : selectionText;
            selectionButton
                .prop('class', 'selection-update ' + (selectionItem.inside ? 'selection-delete btn-danger' : 'selection-add btn-primary'))
                .html('<span class="fas fa-bookmark"></span> ' + selectionText);
        }

        var updateSelectionList = function(selectionItem) {
            let selectionList = $('.selection-list .selection-items');
            if (!selectionList.length) {
                return;
            }
            if (selectionItem.inside) {
                if (!selectionList.find('li[data-id=' + selectionItem.id + ']').length) {
                    selectionList.append(
                        $('<li>').attr('data-id', selectionItem.id)
                            .append(
                                $('<a>').prop('href', selectionItem.url).append(selectionItem.title)
                            )
                            .append(
                                $('<span class="selection-delete">')
                                    .attr('data-id', selectionItem.id)
                                    .attr('data-url', selectionItem.url_remove)
                                    .attr('title', selectionList.attr('data-text-remove'))
                                    .attr('aria-label', selectionList.attr('data-text-remove'))
                            )
                    );
                }
            } else {
                selectionList.find('li[data-id=' + selectionItem.id + ']').remove();
            }
            if (selectionList.find('li').length) {
                $('.selection-empty').removeClass('active');
            } else {
                $('.selection-empty').addClass('active');
            }
        }

    });
})();

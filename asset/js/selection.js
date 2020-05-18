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
            let button = $('.selection-update[data-id=' + selectionItem.id + ']');
            if (!button.length) {
                return;
            }
            button
                .prop('title', button.attr('data-title-' + selectionItem.value))
                .removeClass('selected unselected')
                .addClass(selectionItem.value);
        }

        var updateSelectionList = function(selectionItem) {
            let list = $('.selection-list .selection-items');
            if (!list.length) {
                return;
            }
            if (selectionItem.value === 'selected') {
                if (!list.find('li[data-id=' + selectionItem.id + ']').length) {
                    list.append(
                        $('<li>').attr('data-id', selectionItem.id)
                            .append(
                                $('<a>').prop('href', selectionItem.url).append(selectionItem.title)
                            )
                            .append(
                                $('<span class="selection-delete">')
                                    .attr('data-id', selectionItem.id)
                                    .attr('data-url', selectionItem.url_remove)
                                    .attr('title', list.attr('data-text-remove'))
                                    .attr('aria-label', list.attr('data-text-remove'))
                            )
                    );
                }
            } else {
                list.find('li[data-id=' + selectionItem.id + ']').remove();
            }
            if (list.find('li').length) {
                $('.selection-empty').removeClass('active');
            } else {
                $('.selection-empty').addClass('active');
            }
        }

    });
})();

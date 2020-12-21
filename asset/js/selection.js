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
                    let selectionResource = data.data.selection_resource;
                    if (selectionResource.status === 'success') {
                        updateSelectionButton(selectionResource);
                        updateSelectionList(selectionResource);
                    }
                }
            });
        });

        $('body').on('click', '.selection-list-toggle', function() {
            $(this).toggleClass('active');
            $('.selection-list').toggle().toggleClass('active');
           return false;
        });

        var updateSelectionButton = function(selectionResource) {
            let button = $('.selection-update[data-id=' + selectionResource.id + ']');
            if (!button.length) {
                return;
            }
            button
                .prop('title', button.attr('data-title-' + selectionResource.value))
                .removeClass('selected unselected')
                .addClass(selectionResource.value);
        }

        var updateSelectionList = function(selectionResource) {
            let list = $('.selection-list .selection-resources');
            if (!list.length) {
                return;
            }
            if (selectionResource.value === 'selected') {
                if (!list.find('li[data-id=' + selectionResource.id + ']').length) {
                    list.append(
                        $('<li>').attr('data-id', selectionResource.id)
                            .append(
                                $('<a>').prop('href', selectionResource.url).append(selectionResource.title)
                            )
                            .append(
                                $('<span class="selection-delete">')
                                    .attr('data-id', selectionResource.id)
                                    .attr('data-url', selectionResource.url_remove)
                                    .attr('title', list.attr('data-text-remove'))
                                    .attr('aria-label', list.attr('data-text-remove'))
                            )
                    );
                }
            } else {
                list.find('li[data-id=' + selectionResource.id + ']').remove();
            }
            if (list.find('li').length) {
                $('.selection-empty').removeClass('active');
            } else {
                $('.selection-empty').addClass('active');
            }
        }

    });
})();

(function() {
    $(document).ready(function() {

        /**
         * Manage selection for multiple resource.
         */
        $('body').on('click', '.selection-update, .selection-delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const button = $(this);
            const url = button.attr('data-url');
            $.ajax(url)
            .done(function(data) {
                if (data.status === 'success') {
                    const selectionResource = data.data.selection_resource;
                    if (selectionResource.status === 'success') {
                        updateSelectionButton(selectionResource);
                        updateSelectionList(selectionResource);
                    }
                }
            });
        });

        /**
         * Toggle selected/unselected for a resource.
         */
        $('body').on('click', '.selection-list-toggle', function() {
            $(this).toggleClass('active');
            $('.selection-list').toggle().toggleClass('active');
            return false;
        });

        /**
         * Remove a resource from the list of selected resources.
         */
        $('body').on('click', '.selection-resources .actions .delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const button = $(this);
            const url = button.attr('data-url');
            $.ajax(url)
            .done(function(data) {
                if (data.status === 'success') {
                    const selectionResource = data.data.selection_resource;
                    if (selectionResource.status === 'success' && selectionResource.value === 'unselected' ) {
                        deleteSelectionFromList(selectionResource);
                    }
                }
            });
        });

        /**
         * Add a group.
         */
        $('body').on('click', '.selection-list-actions .add-group', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const button = $(this);
            const msg = button.data('msg-group-name') ? button.data('msg-group-name') : button.text();
            var group = prompt(msg);
            if (!group || !group.length) {
                return;
            }
            const url = button.attr('data-url');
            $.ajax({
                url: url,
                data: {
                    group: group,
                },
            })
            .done(function(data) {
                if (data.status === 'success') {
                    $('.selection-structure').append(`
                    <li class="selection-group">
                        <div>
                            <span class="group-name">${data.data.group.id}</span>
                        </div>
                    </li>`);
                } else if (data.status === 'fail') {
                    alert(data.data.message ? data.data.message : 'An error occurred.');
                } else {
                    alert(data.message ? data.message : 'An error occurred.');
                }
            });
        });

        const updateSelectionButton = function(selectionResource) {
            const button = $('.selection-update[data-id=' + selectionResource.id + ']');
            if (!button.length) {
                return;
            }
            button
                .prop('title', button.attr('data-title-' + selectionResource.value))
                .removeClass('selected unselected')
                .addClass(selectionResource.value);
        }

        const updateSelectionList = function(selectionResource) {
            const list = $('.selection-resources');
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

        const deleteSelectionFromList = function(selectionResource) {
            const list = $('.selection-resources');
            if (!list.length) {
                return;
            }
            list.find('li[data-id=' + selectionResource.id + ']').remove();
            if (!list.find('li').length) {
                $('.selection-list.selections').addClass('inactive').hide();
                $('.selection-list.selection-empty').removeClass('inactive').show();
            }
        }

    });
})();

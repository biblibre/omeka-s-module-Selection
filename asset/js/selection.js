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
        $('body').on('click', '.selection-list .add-group', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const button = $(this);
            const msg = button.closest('.selection-structure').data('msg-group-name') ? button.closest('.selection-structure').data('msg-group-name') : button.text();
            var group = prompt(msg);
            if (!group || !group.length) {
                return;
            }
            const path = button.closest('.selection-group').data('path') ? button.closest('.selection-group').data('path') : null;
            const url = button.attr('data-url');
            $.ajax({
                url: url,
                data: {
                    group: group,
                    path: path,
                },
            })
            .done(function(data) {
                if (data.status === 'success') {
                    // Path is checked and does not contain forbidden characters.
                    const parent = data.data.group.path
                        ? $('.selection-structure .selection-group[data-path="' + data.data.group.path + '"]')
                        : $('.selection-structure');
                    parent.append($('.selection-structure').data('template-group')
                        .replace('__GROUP_PATH__', data.data.group.path + '/' + data.data.group.id)
                        .replace('__GROUP_NAME__',
                            (data.data.group.path && data.data.group.path.length ? '<span>' + data.data.group.path.substring(1).replaceAll('/', '</span><span>') + '</span>' : '')
                            + '<span>' + data.data.group.id + '</span>'
                        )
                    );
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

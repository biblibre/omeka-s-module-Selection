function updateBasket(link,id) {
  idname = '#update_basket'+id;
  $.ajax({
    'url' : link,
    'type': 'get',
    'success': function(data) {
      $(idname).html(data['content']);
    }
  });
}


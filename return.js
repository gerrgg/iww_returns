jQuery(document).ready(function( $ ){
  $('.item-button').click( function(){
    var id = $(this).attr('id');
    var order = $(this).attr('order');
      $('.' + id + '_type').toggle('fast');
      $('.' + id + '_qty').toggle('fast');
      $('.' + id + '_reason').toggle('fast');
      var valid = $('#' + id + '_valid');
      if( ! valid.length ){
        $(this).append( '<input id="' + id + '_valid" type="hidden" name="returns['+ order +'][valid]" value=true />' );
      } else {
        valid.remove();
      }


    } );
  // $('input[type="tel"]').blur(function(){
  //   var value = $(this).val();
  //   var min = $(this).attr('min');
  //   var max = $(this).attr('max');
  //   console.log( value, max );
  //   if( value > +max ){
  //     $(this).val( +max );
  //   }
  // });


});

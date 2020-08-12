$(function () {
  // ensuring data-mask's applied (somehow it wasn't beng applied automagically)
  $('[data-mask]').each(function () {
    var input = $(this);
    input.mask(input.data('mask'));
  });
});

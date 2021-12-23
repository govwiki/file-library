(function ($) {
  $(document).ready(function () {
    $('.btn-user-delete').on('click', function (e) {
      e.preventDefault();

      let $form = $(this).closest('form');
      $form.submit();
    })
  });
})(jQuery);

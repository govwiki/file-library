(function ($) {
  $(document).ready(function () {
    $(document).on('change', 'input[type=checkbox]', function () {
      const $this = $(this);
      this.value  = $this.prop('checked') ? 1 : 0;
    }).change();
  });
})(jQuery);

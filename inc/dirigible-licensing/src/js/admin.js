jQuery(document).ready(function ($) {
  let slug = $('[data-slug]').attr('data-slug');

  $(`#ds_${slug}_activate`).on('click', function () {
    var $this = $(this);

    var $section = $this.parent('.ds_plugin_section');
    var $license = $section.find('.license-input').val();

    var data = {
      action: `ds_${slug}_validate_plugin`,
      licenseKey: $license,
    };

    $.post(ajaxurl, data, function (response) {
      console.log(response);
      var json = JSON.parse(response);
      $section.append(json[0]);
    });
  });
});

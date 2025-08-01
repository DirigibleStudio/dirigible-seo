jQuery(document).ready(function ($) {
  // handle Dirigible SEO
  var $seoPreview = $('#ds-editor-seo-preview');
  if ($seoPreview.length) {
    getPreviewSEO($seoPreview);
    var $seoTitle = $('#ds-editor-seo-title').find('input');
    var $seoDescription = $('#ds-editor-seo-description').find('textarea');
    var $seoNoIndex = $('#ds-editor-seo-no-index').find('input');

    $seoTitle.on('input', function () {
      getPreviewSEO($seoPreview);
    });
    $seoDescription.on('input', function () {
      getPreviewSEO($seoPreview);
    });
    $seoNoIndex.on('change', function () {
      getPreviewSEO($seoPreview);
    });
  }

  function getPreviewSEO($box) {
    var title = $('#ds-editor-seo-title').find('input').val();
    var url = window.location.href;
    var description = $('#ds-editor-seo-description').find('textarea').val();
    var id = getUrlParam('post', '-1');
    var noIndex = $('#ds-editor-seo-no-index').find('input').is(':checked');

    var data = {
      action: 'dsGetPreviewSEO',
      page_id: id,
      seo_title: title,
      seo_description: description,
      editing_URL: url,
    };
    $.post(ajaxurl, data, function (response) {
      var json = JSON.parse(response);
      let title = json.title.replace(/\\/g, '');
      let link = json.permalink;
      let desc = json.excerpt.replace(/\\/g, '');
      $box.html(
        `<div class="ds-seo-preview ${noIndex ? 'no-index' : ''}">
          <span class="title">${title}</span>
          <span class="link">${link}</span>
          <span class="description">${desc}</span>
        </div>`
      );
    });
  }

  function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(
      /[?&]+([^=&]+)=([^&]*)/gi,
      function (m, key, value) {
        vars[key] = value;
      }
    );
    return vars;
  }

  function getUrlParam(parameter, defaultvalue) {
    var urlparameter = defaultvalue;
    if (window.location.href.indexOf(parameter) > -1) {
      urlparameter = getUrlVars()[parameter];
    }
    return urlparameter;
  }

  $('#ds-migrate-yoast').on('click', function () {
    var $this = $(this);
    var $section = $this.parent('.tool');
    var $button = $section.find('.button');
    $section.find('.notification').remove();
    var data = {
      action: 'ds_migrate_yoast',
    };
    $.post(ajaxurl, data, function (response) {
      console.log(response);
      var json = JSON.parse(response);
      json.forEach(function (entry) {
        $section.append(entry);
      });
    });
  });

  // LLMs.txt functionality
  $('#ds-save-llms-txt').on('click', function () {
    var $button = $(this);
    var $status = $('#llms-txt-status');
    var content = $('#llms-txt-content').val();

    $button.prop('disabled', true).text('Saving...');
    $status.html('');

    var data = {
      action: 'ds_save_llms_txt',
      content: content,
      nonce: ds_seo_ajax.nonce,
    };

    $.post(ajaxurl, data, function (response) {
      var json = JSON.parse(response);
      var statusClass = json.success ? 'notice-success' : 'notice-error';
      $status.html(
        '<div style="margin-top: 8px;" class="notice ' +
          statusClass +
          '"><p>' +
          json.message +
          '</p></div>'
      );
      $button.prop('disabled', false).text('Save llms.txt');
    }).fail(function () {
      $status.html(
        '<div class="notice notice-error"><p>Error saving file. Please try again.</p></div>'
      );
      $button.prop('disabled', false).text('Save llms.txt');
    });
  });

  $('#ds-load-llms-txt').on('click', function () {
    var $button = $(this);
    var $status = $('#llms-txt-status');
    var $textarea = $('#llms-txt-content');

    $button.prop('disabled', true).text('Loading...');
    $status.html('');

    var data = {
      action: 'ds_load_llms_txt',
      nonce: ds_seo_ajax.nonce,
    };

    $.post(ajaxurl, data, function (response) {
      var json = JSON.parse(response);
      if (json.success) {
        $textarea.val(json.content);
        $status.html(
          '<div class="notice notice-success"><p>Content reloaded from file.</p></div>'
        );
      } else {
        $status.html(
          '<div class="notice notice-error"><p>Error loading file.</p></div>'
        );
      }
      $button.prop('disabled', false).text('Reload from File');
    }).fail(function () {
      $status.html(
        '<div class="notice notice-error"><p>Error loading file. Please try again.</p></div>'
      );
      $button.prop('disabled', false).text('Reload from File');
    });
  });

  $('#ds-delete-llms-txt').on('click', function () {
    var $button = $(this);
    var $status = $('#llms-txt-status');
    var $textarea = $('#llms-txt-content');

    if (
      !confirm(
        'Are you sure you want to delete the llms.txt file? This action cannot be undone.'
      )
    ) {
      return;
    }

    $button.prop('disabled', true).text('Deleting...');
    $status.html('');

    var data = {
      action: 'ds_delete_llms_txt',
      nonce: ds_seo_ajax.nonce,
    };

    $.post(ajaxurl, data, function (response) {
      var json = JSON.parse(response);
      var statusClass = json.success ? 'notice-success' : 'notice-error';
      $status.html(
        '<div style="margin-top: 8px;" class="notice ' +
          statusClass +
          '"><p>' +
          json.message +
          '</p></div>'
      );
      if (json.success) {
        $textarea.val(''); // Clear the textarea when file is deleted
      }
      $button.prop('disabled', false).text('Delete llms.txt');
    }).fail(function () {
      $status.html(
        '<div class="notice notice-error"><p>Error deleting file. Please try again.</p></div>'
      );
      $button.prop('disabled', false).text('Delete llms.txt');
    });
  });
});

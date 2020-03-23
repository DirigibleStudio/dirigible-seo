jQuery(document).ready(function( $ ) {
  // handle Dirigible SEO
  var $seoPreview = $('#ds-editor-seo-preview');
  if($seoPreview.length) {
    getPreviewSEO($seoPreview);
    var $seoTitle = $("#ds-editor-seo-title").find('input');
    var $seoDescription = $('#ds-editor-seo-description').find('textarea');


    $seoTitle.on('input', function(){
      getPreviewSEO($seoPreview);
    });
    $seoDescription.on('input', function(){
      getPreviewSEO($seoPreview);
    });
  }

  function getPreviewSEO($box) {
    var $preview = $('<div class="ds-seo-preview"></div>');
    var title = $('#ds-editor-seo-title').find('input').val();
    var description = $('#ds-editor-seo-description').find('textarea').val();
    var id = getUrlParam('post','-1');
    var data = {
	    'action': 'dsGetPreviewSEO',
      'page_id': id,
	    'seo_title':  title,
      'seo_description':  description
		};
    $.post(ajaxurl, data, function(response) {
			var json = JSON.parse(response);
      $preview.append($('<span class="title">' + json.title + '</span>'));
      $preview.append($('<span class="link">' + json.permalink + '</span>'));
      $preview.append($('<span class="description">' + json.excerpt + '</span>'));
      $box.html($preview);
		});
  }

  function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
  }

  function getUrlParam(parameter, defaultvalue){
    var urlparameter = defaultvalue;
    if(window.location.href.indexOf(parameter) > -1){
        urlparameter = getUrlVars()[parameter];
        }
    return urlparameter;
  }


  $('#ds-migrate-yoast').on( "click", function() {
		var $this = $(this);
		var $section = $this.parent('.tool');
		var $button = $section.find('.button');
		$section.find('.notification').remove();
		var data = {
	    'action': 'ds_migrate_yoast',
		};
		$.post(ajaxurl, data, function(response) {
      console.log(response);
			var json = JSON.parse(response);
			json.forEach(function(entry) {
			 	$section.append(entry);
			});
		});
	});



});

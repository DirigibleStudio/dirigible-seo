jQuery(document).ready(function ($) {
	// handle Dirigible SEO
	var $seoPreview = $("#ds-editor-seo-preview");
	if ($seoPreview.length) {
		getPreviewSEO($seoPreview);
		var $seoTitle = $("#ds-editor-seo-title").find("input");
		var $seoDescription = $("#ds-editor-seo-description").find("textarea");

		$seoTitle.on("input", function () {
			getPreviewSEO($seoPreview);
		});
		$seoDescription.on("input", function () {
			getPreviewSEO($seoPreview);
		});
	}

	function getPreviewSEO($box) {
		var title = $("#ds-editor-seo-title").find("input").val();
		var url = window.location.href;
		var description = $("#ds-editor-seo-description").find("textarea").val();
		var id = getUrlParam("post", "-1");
		var data = {
			action: "dsGetPreviewSEO",
			page_id: id,
			seo_title: title,
			seo_description: description,
			editing_URL: url,
		};
		$.post(ajaxurl, data, function (response) {
			var json = JSON.parse(response);
			let title = json.title.replace(/\\/g, "");
			let link = json.permalink;
			let desc = json.excerpt.replace(/\\/g, "");
			$box.html(
				`<div class="ds-seo-preview">
          <span class="title">${title}</span>
          <span class="link">${link}</span>
          <span class="description">${desc}</span>
        </div>`
			);
		});
	}

	function getUrlVars() {
		var vars = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
			vars[key] = value;
		});
		return vars;
	}

	function getUrlParam(parameter, defaultvalue) {
		var urlparameter = defaultvalue;
		if (window.location.href.indexOf(parameter) > -1) {
			urlparameter = getUrlVars()[parameter];
		}
		return urlparameter;
	}

	$("#ds-migrate-yoast").on("click", function () {
		var $this = $(this);
		var $section = $this.parent(".tool");
		var $button = $section.find(".button");
		$section.find(".notification").remove();
		var data = {
			action: "ds_migrate_yoast",
		};
		$.post(ajaxurl, data, function (response) {
			console.log(response);
			var json = JSON.parse(response);
			json.forEach(function (entry) {
				$section.append(entry);
			});
		});
	});
});

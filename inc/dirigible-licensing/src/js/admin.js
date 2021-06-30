jQuery(document).ready(function ($) {
	$(".ds-activate-plugin-license").on("click", function () {
		var $this = $(this);
		let slug = $this.data("slug");
		let id = $this.data("id");
		var $section = $this.closest(".license-section");
		var license = $section.find(".license-input").val();
		var data = {
			action: `ds_${id}_validate_plugin`,
			licenseKey: license,
		};
		$.post(ajaxurl, data, function (response) {
			var json = JSON.parse(response);
			var $notification = json[0];
			if (json[1] === "failure") {
				$section.removeClass("valid");
				$section.addClass("invalid");
			} else {
				$section.removeClass("invalid");
				$section.addClass("valid");
			}
			$section.append($notification);
		});
	});
});

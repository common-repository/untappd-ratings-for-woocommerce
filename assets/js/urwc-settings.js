jQuery(function ($) {
	$('#mainform').on('submit', function () {
		$('[id^=urwc_ratings]:not(#urwc_ratings_enabled)').prop(
			'disabled',
			false
		);
	});

	const selector = $('input#urwc_ratings_enabled');

	$('[id^=urwc_ratings]:not(#urwc_ratings_enabled)').prop(
		'disabled',
		!selector.prop('checked')
	);

	selector.on('change', function () {
		$('[id^=urwc_ratings]:not(#urwc_ratings_enabled)').prop(
			'disabled',
			!this.checked
		);
	});
});

"use strict";

$(document).ready(function () {
	$('.add-combination').on('click', function () {
		$('.combinations-container').before($('#combination-template').html());
	});

	$('.combination-rows').on('click', '.remove-combination', function () {
		$(this).parent().parent().remove();
	});

	$('#key').val(window.submittedForm.key);

	if (window.submittedForm.combinationValues) {
		for (var i = 0; i < window.submittedForm.combinationValues.length; i++) {
			if (window.submittedForm.combinationValues[i] === '') {
				continue;
			}

			$('.combinations-container').before($('#combination-template').html());

			$('.combination .values').last().val(window.submittedForm.combinationValues[i]);
		}
	}
});

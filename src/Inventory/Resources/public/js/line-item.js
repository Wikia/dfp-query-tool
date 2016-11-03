"use strict";

$(document).ready(function () {
	var $typeLevel = $('.type-level');

	$('.add-pair').on('click', function () {
		$('.add-pair-container').before($('#pair-template').html());
	});

	$('.key-value-pairs').on('click', '.remove-pair', function () {
		$(this).parent().parent().remove();
	});

	$('#type').on('change', function () {
		if ($(this).val() === 'STANDARD') {
			$typeLevel.show();
		} else {
			$typeLevel.hide();
		}
	});

	$('#orderId').val(window.submittedForm.orderId);
	$('#lineItemName').val(window.submittedForm.lineItemName);
	$('#sizes').val(window.submittedForm.sizes);
	$('#sameAdvertiser').prop('checked', window.submittedForm.sameAdvertiser === 'on');
	$('#type').val(window.submittedForm.type || 'PRICE_PRIORITY');
	$('#typeLevel').val(window.submittedForm.typeLevel || 'NORMAL');
	$('#priority').val(window.submittedForm.priority);
	$('#start').val(window.submittedForm.start);
	$('#end').val(window.submittedForm.end);
	$('#rate').val(window.submittedForm.rate);

	$('#type').trigger('change');

	if (window.submittedForm.keys) {
		for (var i = 0; i < window.submittedForm.keys.length; i++) {
			if (window.submittedForm.keys[i] === '') {
				continue;
			}

			$('.add-pair-container').before($('#pair-template').html());

			$('.key-value-pairs .key').last().val(window.submittedForm.keys[i]);
			$('.key-value-pairs .value').last().val(window.submittedForm.values[i]);
		}
	}
});
"use strict";

$(document).ready(function () {
	$('.add-pair').on('click', function () {
		$('.add-pair-container').before($('#pair-template').html());
	});

	$('.key-value-pairs').on('click', '.remove-pair', function () {
		$(this).parent().parent().remove();
	});

	$('#orderId').val(window.submittedForm.orderId);
	$('#lineItemName').val(window.submittedForm.lineItemName);
	$('#sizes').val(window.submittedForm.sizes);
	$('#sameAdvertiser').prop('checked', window.submittedForm.sameAdvertiser === 'on');
	$('#type').val(window.submittedForm.type || 'PRICE_PRIORITY');
	$('#priority').val(window.submittedForm.priority);
	$('#start').val(window.submittedForm.start);
	$('#end').val(window.submittedForm.end);
	$('#rate').val(window.submittedForm.rate);

	if (window.submittedForm.keys) {
		for (var i = 0; i < window.submittedForm.keys.length; i++) {
			if (window.submittedForm.keys[i] === '') {
				continue;
			}

			$('.add-pair-container').before($('#pair-template').html());

			$('.key-value-pairs .key').last().val(window.submittedForm.keys[i]);
			$('.key-value-pairs .operator').last().val(window.submittedForm.operators[i]);
			$('.key-value-pairs .value').last().val(window.submittedForm.values[i]);
		}
	}
});
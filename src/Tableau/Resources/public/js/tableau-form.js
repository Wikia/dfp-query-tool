window.typesMap = {
	TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS: 'int',
	TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS: 'int',
	TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS_RATE: 'float',
	TOTAL_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS: 'int',
	TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS_RATE: 'float',
	CREATIVE_ID: 'int',
	LINE_ITEM_ID: 'int',
	ORDER_ID: 'int',
	DATE: 'date'
};

(function() {
	var connector = tableau.makeConnector(),
		response;

	function getResponse(data) {
		var fieldColumns = [],
			fieldTypes = [];

		response = data;
		Object.keys(response[0]).forEach(function (key) {
			fieldColumns.push(key);
			fieldTypes.push(typesMap[key] || 'string');
		});

		tableau.headersCallback(fieldColumns, fieldTypes);
	}

	connector.getColumnHeaders = function() {

		var data = JSON.parse(tableau.connectionData);

		if (data.reportId) {
			$.get('api/reports/' + data.reportId, function (data) {
				getResponse(data);
			});
		}

		if (data.queryData) {
			$.post('api/query', data.queryData, function (data) {
				getResponse(data);
			});
		}
	};

	connector.getTableData = function(lastRecordToken) {
		tableau.dataCallback(response, lastRecordToken, false);
	};

	tableau.registerConnector(connector);

	$('#report-submit').on('click', function () {
		var reportId = $('#report-id').val();

		if (reportId === '') {
			$('#report-id').addClass('has-error');
			return;
		}

		tableau.connectionData = JSON.stringify({
			reportId: reportId
		});
		tableau.submit();
	});

	$('#query-submit').on('click', function () {
		tableau.connectionData = JSON.stringify({
			queryData: $('#query-form').serialize()
		});
		tableau.connectionName = 'DFP Query';
		tableau.submit();
	});
})();

$(document).ready(function () {
	$('.tabs-group').click(function (e) {
		e.preventDefault();
		$(this).tab('show')
	});

	$('#startDate').val('2016-06-07');
	$('#endDate').val('2016-06-09');

	$('#filter-add').on('click', function () {
		$('.form-filters').append($('#filter-template').html());
	});
	$('.form-filters').append($('#filter-template').html());

	$('.form-filters').on('click', '.filter-remove', function () {
		$(this).parent().remove();
	});

	$('#query-form').on('click', '.checkbox', function () {
		var checkbox = $(this).find('input');

		if (checkbox.prop('checked')) {
			$(this).addClass('active');
		} else {
			$(this).removeClass('active');
		}
	});

	$('.checkbox input').each(function () {
		if ($(this).prop('checked')) {
			$(this).parent().parent().addClass('active');
		}
	});
});
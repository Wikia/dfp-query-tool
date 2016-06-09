<?php

namespace Report\Api;

class ReportService
{
	public function generate() {
		$id = 386164332;
		$user = Authenticator::getUser();

		try {
			$reportService = $user->GetService('ReportService', 'v201605');
			// Create report query.
			$reportQuery = new \ReportQuery();
			$reportQuery->dimensions = array('ORDER_ID', 'ORDER_NAME');
			$reportQuery->dimensionAttributes = array('ORDER_TRAFFICKER',
				'ORDER_START_DATE_TIME', 'ORDER_END_DATE_TIME');
			$reportQuery->columns = array('AD_SERVER_IMPRESSIONS', 'AD_SERVER_CLICKS',
				'AD_SERVER_CTR', 'AD_SERVER_CPM_AND_CPC_REVENUE',
				'AD_SERVER_WITHOUT_CPD_AVERAGE_ECPM');
			// Create statement to filter for an order.
			$statementBuilder = new \StatementBuilder();
			$statementBuilder->Where('order_id = :orderId')->WithBindVariableValue(
				'orderId', intval($id));
			// Set the filter statement.
			$reportQuery->statement = $statementBuilder->ToStatement();
			// Set the start and end dates or choose a dynamic date range type.
			$reportQuery->dateRangeType = 'CUSTOM_DATE';
			$reportQuery->startDate = \DateTimeUtils::ToDfpDateTime(
				new \DateTime('-10 days', new \DateTimeZone('America/New_York')))->date;
			$reportQuery->endDate = \DateTimeUtils::ToDfpDateTime(
				new \DateTime('now', new \DateTimeZone('America/New_York')))->date;
			// Create report job.
			$reportJob = new \ReportJob();
			$reportJob->reportQuery = $reportQuery;
			// Run report job.
			$reportJob = $reportService->runReportJob($reportJob);

			// Create report downloader.
			$reportDownloader = new \ReportDownloader($reportService, $reportJob->id);
			// Wait for the report to be ready.
			$reportDownloader->waitForReportReady();

			return $this->parseCsvData(gzdecode($reportDownloader->downloadReport('CSV_DUMP')));
		} catch (\OAuth2Exception $e) {
			return sprintf("%s\n", $e->getMessage());
		} catch (\ValidationException $e) {
			return sprintf("%s\n", $e->getMessage());
		} catch (\Exception $e) {
			return sprintf("%s\n", $e->getMessage());
		}
	}

	public function get($id) {
		try {
			$user = Authenticator::getUser();
			$reportService = $user->GetService('ReportService', 'v201605');
			$reportDownloader = new \ReportDownloader($reportService, $id);
			$reportDownloader->waitForReportReady();

			return $this->parseCsvData(gzdecode($reportDownloader->downloadReport('CSV_DUMP')));
		} catch (\OAuth2Exception $e) {
			return sprintf("%s\n", $e->getMessage());
		} catch (\ValidationException $e) {
			return sprintf("%s\n", $e->getMessage());
		} catch (\Exception $e) {
			return sprintf("%s\n", $e->getMessage());
		}
	}

	private function parseCsvData($csv) {
		$columns = [];
		$data = [];
		$header = true;

		foreach(preg_split("/((\r?\n)|(\r\n?))/", $csv) as $line) {
			if ($line === "") {
				continue;
			}

			$values = explode(',', $line);
			if ($header) {
				foreach ($values as $value) {
					list($key, $enum) = explode('.', $value);
					$columns[] = $enum;
				}
				$header = false;
			} else {
				$row = [];
				foreach ($values as $key => $value) {
					$row[$columns[$key]] = $value;
				}
				$data[] = $row;
			}
		}

		return $data;
	}
}
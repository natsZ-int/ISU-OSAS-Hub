<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(protected AnalyticsService $analytics) {}

    public function index(Request $request)
    {
        $filters = $this->analytics->validateFilters($request->all());
        $data = $this->analytics->getAllAnalytics($filters);
        $options = $this->analytics->getFilterOptions();

        return view('admin.analytics.index', array_merge($data, [
            'filterOptions' => $options,
        ]));
    }

    public function scholarships(Request $request)
    {
        $filters = $this->analytics->validateFilters($request->all());
        $data = $this->analytics->getScholarshipAnalytics($filters);
        $options = $this->analytics->getFilterOptions();

        return view('admin.analytics.scholarships', [
            'data' => $data,
            'filters' => $filters,
            'filterOptions' => $options,
        ]);
    }

    public function guidance(Request $request)
    {
        $filters = $this->analytics->validateFilters($request->all());
        $data = $this->analytics->getGuidanceAnalytics($filters);
        $options = $this->analytics->getFilterOptions();

        return view('admin.analytics.guidance', [
            'data' => $data,
            'filters' => $filters,
            'filterOptions' => $options,
        ]);
    }

    public function services(Request $request)
    {
        $filters = $this->analytics->validateFilters($request->all());
        $data = $this->analytics->getServiceAnalytics($filters);
        $options = $this->analytics->getFilterOptions();

        return view('admin.analytics.services', [
            'data' => $data,
            'filters' => $filters,
            'filterOptions' => $options,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->analytics->validateFilters($request->all());
        $data = $this->analytics->getExportData($filters);

        $dateLabel = ! empty($filters['date_from']) || ! empty($filters['date_to'])
            ? ($filters['date_from'] ?? 'start').'_to_'.($filters['date_to'] ?? 'now')
            : 'all-time';

        $filename = 'isu-osas-analytics-'.$dateLabel.'.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ISU OSAS Hub — Analytics Export']);
            fputcsv($handle, ['Generated', now()->toDateTimeString()]);
            fputcsv($handle, []);

            // Summary
            fputcsv($handle, ['--- SUMMARY ---']);
            foreach ($data['summary'] as $key => $value) {
                fputcsv($handle, [ucfirst(str_replace('_', ' ', $key)), $value ?? 'N/A']);
            }
            fputcsv($handle, []);

            // Scholarship by scholarship
            fputcsv($handle, ['--- APPLICATIONS BY SCHOLARSHIP ---']);
            fputcsv($handle, ['Scholarship', 'Total', 'Approved', 'Rejected', 'Pending']);
            foreach ($data['scholarships']['byScholarship'] as $row) {
                fputcsv($handle, [$row->title, $row->total, $row->approved, $row->rejected, $row->pending]);
            }
            fputcsv($handle, []);

            // Guidance by type
            fputcsv($handle, ['--- APPOINTMENTS BY TYPE ---']);
            fputcsv($handle, ['Type', 'Total', 'Completed', 'Cancelled', 'Pending']);
            foreach ($data['guidance']['byType'] as $row) {
                fputcsv($handle, [$row->type_name, $row->total, $row->completed, $row->cancelled, $row->pending]);
            }
            fputcsv($handle, []);

            // Services by type
            fputcsv($handle, ['--- SERVICE REQUESTS BY TYPE ---']);
            fputcsv($handle, ['Type', 'Total', 'Submitted', 'Processing', 'Requires Info', 'Completed', 'Cancelled']);
            foreach ($data['services']['byType'] as $row) {
                fputcsv($handle, [$row->type_name, $row->total, $row->submitted, $row->processing, $row->requires_info, $row->completed, $row->cancelled]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

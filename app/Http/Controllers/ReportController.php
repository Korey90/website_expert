<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    // -------------------------------------------------------
    // Leads Report
    // -------------------------------------------------------

    public function leads(Request $request, string $format = 'html'): mixed
    {
        $leads = Lead::with('client', 'stage')
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->get();

        return match ($format) {
            'pdf'  => $this->pdfResponse('reports.leads', compact('leads'), 'leads-report'),
            'xlsx' => $this->xlsxResponse($this->leadsSpreadsheet($leads), 'leads-report'),
            'csv'  => $this->csvResponse($this->leadsSpreadsheet($leads), 'leads-report'),
            default => view('reports.leads', compact('leads')),
        };
    }

    // -------------------------------------------------------
    // Invoices Report
    // -------------------------------------------------------

    public function invoices(Request $request, string $format = 'html'): mixed
    {
        $invoices = Invoice::with('client', 'project')
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->get();

        $totals = [
            'draft'     => $invoices->where('status', 'draft')->sum('total'),
            'sent'      => $invoices->where('status', 'sent')->sum('total'),
            'paid'      => $invoices->where('status', 'paid')->sum('total'),
            'overdue'   => $invoices->where('status', 'overdue')->sum('total'),
            'cancelled' => $invoices->where('status', 'cancelled')->sum('total'),
        ];

        return match ($format) {
            'pdf'  => $this->pdfResponse('reports.invoices', compact('invoices', 'totals'), 'invoices-report'),
            'xlsx' => $this->xlsxResponse($this->invoicesSpreadsheet($invoices), 'invoices-report'),
            'csv'  => $this->csvResponse($this->invoicesSpreadsheet($invoices), 'invoices-report'),
            default => view('reports.invoices', compact('invoices', 'totals')),
        };
    }

    // -------------------------------------------------------
    // Projects Report
    // -------------------------------------------------------

    public function projects(Request $request, string $format = 'html'): mixed
    {
        $projects = Project::with('client')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->get();

        return match ($format) {
            'pdf'  => $this->pdfResponse('reports.projects', compact('projects'), 'projects-report'),
            'xlsx' => $this->xlsxResponse($this->projectsSpreadsheet($projects), 'projects-report'),
            'csv'  => $this->csvResponse($this->projectsSpreadsheet($projects), 'projects-report'),
            default => view('reports.projects', compact('projects')),
        };
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function pdfResponse(string $view, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}-" . now()->format('Y-m-d') . '.pdf"',
        ]);
    }

    private function xlsxResponse(Spreadsheet $spreadsheet, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, "{$filename}-" . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function csvResponse(Spreadsheet $spreadsheet, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $writer = new Csv($spreadsheet);
        $writer->setUseBOM(true);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, "{$filename}-" . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function leadsSpreadsheet(\Illuminate\Support\Collection $leads): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Leads');

        $headers = ['ID', 'Name', 'Email', 'Phone', 'Company', 'Source', 'Status', 'Stage', 'Value', 'Created'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }

        foreach ($leads as $row => $lead) {
            $sheet->setCellValueByColumnAndRow(1, $row + 2, $lead->id);
            $sheet->setCellValueByColumnAndRow(2, $row + 2, $lead->name ?? ($lead->client?->primary_contact_name ?? ''));
            $sheet->setCellValueByColumnAndRow(3, $row + 2, $lead->email ?? ($lead->client?->primary_contact_email ?? ''));
            $sheet->setCellValueByColumnAndRow(4, $row + 2, $lead->phone ?? '');
            $sheet->setCellValueByColumnAndRow(5, $row + 2, $lead->company ?? ($lead->client?->company_name ?? ''));
            $sheet->setCellValueByColumnAndRow(6, $row + 2, $lead->source ?? '');
            $sheet->setCellValueByColumnAndRow(7, $row + 2, $lead->status ?? '');
            $sheet->setCellValueByColumnAndRow(8, $row + 2, $lead->stage?->name ?? '');
            $sheet->setCellValueByColumnAndRow(9, $row + 2, $lead->value ?? '');
            $sheet->setCellValueByColumnAndRow(10, $row + 2, $lead->created_at?->format('Y-m-d') ?? '');
        }

        return $spreadsheet;
    }

    private function invoicesSpreadsheet(\Illuminate\Support\Collection $invoices): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Invoices');

        $headers = ['ID', 'Invoice #', 'Client', 'Project', 'Status', 'Currency', 'Subtotal', 'VAT', 'Total', 'Due Date', 'Created'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }

        foreach ($invoices as $row => $invoice) {
            $sheet->setCellValueByColumnAndRow(1,  $row + 2, $invoice->id);
            $sheet->setCellValueByColumnAndRow(2,  $row + 2, $invoice->number);
            $sheet->setCellValueByColumnAndRow(3,  $row + 2, $invoice->client?->company_name ?? $invoice->client?->primary_contact_name ?? '');
            $sheet->setCellValueByColumnAndRow(4,  $row + 2, $invoice->project?->title ?? '');
            $sheet->setCellValueByColumnAndRow(5,  $row + 2, $invoice->status);
            $sheet->setCellValueByColumnAndRow(6,  $row + 2, $invoice->currency ?? 'GBP');
            $sheet->setCellValueByColumnAndRow(7,  $row + 2, $invoice->subtotal ?? 0);
            $sheet->setCellValueByColumnAndRow(8,  $row + 2, $invoice->tax_amount ?? 0);
            $sheet->setCellValueByColumnAndRow(9,  $row + 2, $invoice->total ?? 0);
            $sheet->setCellValueByColumnAndRow(10, $row + 2, $invoice->due_date?->format('Y-m-d') ?? '');
            $sheet->setCellValueByColumnAndRow(11, $row + 2, $invoice->created_at?->format('Y-m-d') ?? '');
        }

        return $spreadsheet;
    }

    private function projectsSpreadsheet(\Illuminate\Support\Collection $projects): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Projects');

        $headers = ['ID', 'Title', 'Client', 'Service Type', 'Status', 'Budget', 'Currency', 'Start Date', 'Due Date', 'Created'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }

        foreach ($projects as $row => $project) {
            $sheet->setCellValueByColumnAndRow(1,  $row + 2, $project->id);
            $sheet->setCellValueByColumnAndRow(2,  $row + 2, $project->title);
            $sheet->setCellValueByColumnAndRow(3,  $row + 2, $project->client?->company_name ?? $project->client?->primary_contact_name ?? '');
            $sheet->setCellValueByColumnAndRow(4,  $row + 2, $project->service_type ?? '');
            $sheet->setCellValueByColumnAndRow(5,  $row + 2, $project->status);
            $sheet->setCellValueByColumnAndRow(6,  $row + 2, $project->budget ?? 0);
            $sheet->setCellValueByColumnAndRow(7,  $row + 2, $project->currency ?? 'GBP');
            $sheet->setCellValueByColumnAndRow(8,  $row + 2, $project->start_date?->format('Y-m-d') ?? '');
            $sheet->setCellValueByColumnAndRow(9,  $row + 2, $project->due_date?->format('Y-m-d') ?? '');
            $sheet->setCellValueByColumnAndRow(10, $row + 2, $project->created_at?->format('Y-m-d') ?? '');
        }

        return $spreadsheet;
    }
}

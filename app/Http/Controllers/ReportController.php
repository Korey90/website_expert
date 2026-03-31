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
            ->when($request->stage_id,  fn ($q) => $q->where('pipeline_stage_id', $request->stage_id))
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

        $sheet->fromArray(
            ['ID', 'Name', 'Email', 'Phone', 'Company', 'Source', 'Stage ID', 'Stage', 'Value', 'Created'],
            null,
            'A1'
        );

        foreach ($leads as $index => $lead) {
            $sheet->fromArray([
                $lead->id,
                $lead->name ?? ($lead->client?->primary_contact_name ?? ''),
                $lead->email ?? ($lead->client?->primary_contact_email ?? ''),
                $lead->phone ?? '',
                $lead->company ?? ($lead->client?->company_name ?? ''),
                $lead->source ?? '',
                $lead->pipeline_stage_id ?? '',
                $lead->stage?->name ?? '',
                $lead->value ?? '',
                $lead->created_at?->format('Y-m-d') ?? '',
            ], null, 'A' . ($index + 2));
        }

        return $spreadsheet;
    }

    private function invoicesSpreadsheet(\Illuminate\Support\Collection $invoices): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Invoices');

        $sheet->fromArray(
            ['ID', 'Invoice #', 'Client', 'Project', 'Status', 'Currency', 'Subtotal', 'VAT', 'Total', 'Due Date', 'Created'],
            null,
            'A1'
        );

        foreach ($invoices as $index => $invoice) {
            $sheet->fromArray([
                $invoice->id,
                $invoice->number,
                $invoice->client?->company_name ?? $invoice->client?->primary_contact_name ?? '',
                $invoice->project?->title ?? '',
                $invoice->status,
                $invoice->currency ?? 'GBP',
                $invoice->subtotal ?? 0,
                $invoice->vat_amount ?? 0,
                $invoice->total ?? 0,
                $invoice->due_date?->format('Y-m-d') ?? '',
                $invoice->created_at?->format('Y-m-d') ?? '',
            ], null, 'A' . ($index + 2));
        }

        return $spreadsheet;
    }

    private function projectsSpreadsheet(\Illuminate\Support\Collection $projects): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Projects');

        $sheet->fromArray(
            ['ID', 'Title', 'Client', 'Service Type', 'Status', 'Budget', 'Currency', 'Start Date', 'Due Date', 'Created'],
            null,
            'A1'
        );

        foreach ($projects as $index => $project) {
            $sheet->fromArray([
                $project->id,
                $project->title,
                $project->client?->company_name ?? $project->client?->primary_contact_name ?? '',
                $project->service_type ?? '',
                $project->status,
                $project->budget ?? 0,
                $project->currency ?? 'GBP',
                $project->start_date?->format('Y-m-d') ?? '',
                $project->due_date?->format('Y-m-d') ?? '',
                $project->created_at?->format('Y-m-d') ?? '',
            ], null, 'A' . ($index + 2));
        }

        return $spreadsheet;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $activeProjects = Project::whereIn('status', ['active', 'in_progress'])->count();

        $openLeads = Lead::whereHas('stage', fn ($q) => $q->where('name', '!=', 'Closed'))->count();

        $unpaidInvoices = Invoice::whereIn('status', ['sent', 'overdue'])->count();

        $upcomingDeadlines = Project::whereIn('status', ['active', 'in_progress'])
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->where('deadline', '<=', now()->addDays(7))
            ->orderBy('deadline')
            ->get(['id', 'title', 'status', 'deadline', 'client_id'])
            ->map(fn ($p) => [
                'id'       => $p->id,
                'title'    => $p->title,
                'status'   => $p->status,
                'deadline' => $p->deadline?->toDateString(),
            ]);

        return Inertia::render('Dashboard', [
            'activeProjects'    => $activeProjects,
            'openLeads'         => $openLeads,
            'unpaidInvoices'    => $unpaidInvoices,
            'upcomingDeadlines' => $upcomingDeadlines,
        ]);
    }
}

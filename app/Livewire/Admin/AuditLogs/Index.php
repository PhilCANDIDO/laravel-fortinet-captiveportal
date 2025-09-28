<?php

namespace App\Livewire\Admin\AuditLogs;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AuditLog;
use App\Exports\AuditLogExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $eventTypeFilter = '';
    public $eventCategoryFilter = '';
    public $statusFilter = '';
    public $startDate = '';
    public $endDate = '';
    public $ipAddressFilter = '';
    public $perPage = 25;
    public $showDetailModal = false;
    public $selectedLog = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'eventTypeFilter' => ['except' => ''],
        'eventCategoryFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 25]
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingEventTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingEventCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingStartDate()
    {
        $this->resetPage();
    }

    public function updatingEndDate()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset([
            'search',
            'eventTypeFilter',
            'eventCategoryFilter',
            'statusFilter',
            'startDate',
            'endDate',
            'ipAddressFilter'
        ]);
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLog::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('user_email', 'like', '%' . $this->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                  ->orWhere('action', 'like', '%' . $this->search . '%')
                  ->orWhere('message', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->eventTypeFilter) {
            $query->where('event_type', $this->eventTypeFilter);
        }

        if ($this->eventCategoryFilter) {
            $query->where('event_category', $this->eventCategoryFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        if ($this->ipAddressFilter) {
            $query->where('ip_address', $this->ipAddressFilter);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($this->perPage);

        $eventTypes = AuditLog::EVENT_TYPES;
        $eventCategories = AuditLog::EVENT_CATEGORIES;
        $statuses = AuditLog::STATUS;

        return view('livewire.admin.audit-logs.index', [
            'logs' => $logs,
            'eventTypes' => $eventTypes,
            'eventCategories' => $eventCategories,
            'statuses' => $statuses,
        ]);
    }

    public function viewDetails($logId)
    {
        $this->selectedLog = AuditLog::findOrFail($logId);
        $this->showDetailModal = true;
    }

    public function exportToExcel()
    {
        $filters = [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'event_type' => $this->eventTypeFilter,
            'event_category' => $this->eventCategoryFilter,
            'user_email' => $this->search,
            'ip_address' => $this->ipAddressFilter,
            'status' => $this->statusFilter,
        ];

        AuditLog::log([
            'event_type' => AuditLog::EVENT_TYPES['DATA_EXPORTED'],
            'event_category' => AuditLog::EVENT_CATEGORIES['DATA_ACCESS'],
            'user_type' => 'admin',
            'user_id' => auth()->guard('admin')->id(),
            'user_email' => auth()->guard('admin')->user()->email,
            'action' => 'export',
            'resource_type' => 'audit_logs',
            'status' => AuditLog::STATUS['SUCCESS'],
            'message' => 'Audit logs exported to Excel',
            'metadata' => $filters,
        ]);

        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new AuditLogExport($filters), $filename);
    }
}
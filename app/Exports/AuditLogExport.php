<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class AuditLogExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;
    
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }
    
    public function query()
    {
        $query = AuditLog::query();
        
        if (!empty($this->filters['start_date'])) {
            $query->where('created_at', '>=', $this->filters['start_date']);
        }
        
        if (!empty($this->filters['end_date'])) {
            $query->where('created_at', '<=', $this->filters['end_date']);
        }
        
        if (!empty($this->filters['event_type'])) {
            $query->where('event_type', $this->filters['event_type']);
        }
        
        if (!empty($this->filters['event_category'])) {
            $query->where('event_category', $this->filters['event_category']);
        }
        
        if (!empty($this->filters['user_email'])) {
            $query->where('user_email', 'like', '%' . $this->filters['user_email'] . '%');
        }
        
        if (!empty($this->filters['ip_address'])) {
            $query->where('ip_address', $this->filters['ip_address']);
        }
        
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        
        return $query->orderBy('created_at', 'desc');
    }
    
    public function headings(): array
    {
        return [
            'ID',
            'Date/Time',
            'Event Type',
            'Category',
            'User Type',
            'User ID',
            'User Email',
            'IP Address',
            'User Agent',
            'Action',
            'Resource Type',
            'Resource ID',
            'Status',
            'Message',
            'Session ID',
            'Metadata',
        ];
    }
    
    public function map($log): array
    {
        return [
            $log->id,
            Carbon::parse($log->created_at)->format('Y-m-d H:i:s'),
            $log->event_type,
            $log->event_category,
            $log->user_type,
            $log->user_id,
            $log->user_email,
            $log->ip_address,
            $log->user_agent,
            $log->action,
            $log->resource_type,
            $log->resource_id,
            $log->status,
            $log->message,
            $log->session_id,
            json_encode($log->metadata),
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A1:P1' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
                ],
            ],
        ];
    }
}
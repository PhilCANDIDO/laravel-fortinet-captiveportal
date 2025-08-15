<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\AuditLog;
use App\Services\UserService;
use App\Services\NotificationService;
use App\Services\FortiGateService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeManagement extends Component
{
    use WithPagination;
    
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showImportModal = false;
    
    public $search = '';
    public $statusFilter = '';
    public $departmentFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // Form fields
    public $employeeId;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $department;
    public $employee_id;
    public $notes;
    public $send_credentials = true;
    
    // Import fields
    public $importFile;
    
    protected $rules = [
        'first_name' => 'required|string|max:100',
        'last_name' => 'required|string|max:100',
        'email' => 'required|email',
        'employee_id' => 'nullable|string|max:50',
        'department' => 'nullable|string|max:100',
        'phone' => 'nullable|string|max:20',
        'notes' => 'nullable|string|max:500',
    ];
    
    protected $userService;
    protected $notificationService;
    protected $fortiGateService;
    
    public function boot(
        UserService $userService,
        NotificationService $notificationService,
        FortiGateService $fortiGateService
    ) {
        $this->userService = $userService;
        $this->notificationService = $notificationService;
        $this->fortiGateService = $fortiGateService;
    }
    
    public function render()
    {
        $employees = User::where('user_type', User::TYPE_EMPLOYEE)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('email', 'like', '%' . $this->search . '%')
                      ->orWhere('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('employee_id', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->departmentFilter, function ($query) {
                $query->where('department', $this->departmentFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
        
        $departments = User::where('user_type', User::TYPE_EMPLOYEE)
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');
        
        return view('livewire.admin.employee-management', [
            'employees' => $employees,
            'departments' => $departments,
        ])->layout('layouts.admin');
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }
    
    public function create()
    {
        $this->validate();
        
        DB::beginTransaction();
        
        try {
            // Create employee
            $employee = $this->userService->createEmployee([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'employee_id' => $this->employee_id,
                'department' => $this->department,
                'notes' => $this->notes,
            ]);
            
            // Generate password
            $password = $this->userService->generateSecurePassword();
            $employee->password = bcrypt($password);
            $employee->save();
            
            // Sync with FortiGate
            $this->fortiGateService->createUser($employee);
            
            // Send credentials if requested
            if ($this->send_credentials) {
                $this->notificationService->sendWelcomeEmail($employee, $password);
            }
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'employee.created',
                'details' => [
                    'employee_id' => $employee->id,
                    'email' => $employee->email,
                    'department' => $employee->department,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            $this->showCreateModal = false;
            $this->resetForm();
            
            session()->flash('success', __('messages.employee_created'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create employee', [
                'error' => $e->getMessage(),
                'data' => $this->all(),
            ]);
            
            session()->flash('error', __('messages.employee_creation_failed'));
        }
    }
    
    public function edit($id)
    {
        $employee = User::findOrFail($id);
        
        $this->employeeId = $employee->id;
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->email = $employee->email;
        $this->phone = $employee->phone;
        $this->employee_id = $employee->employee_id;
        $this->department = $employee->department;
        $this->notes = $employee->notes;
        
        $this->showEditModal = true;
    }
    
    public function update()
    {
        $this->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $this->employeeId,
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id,' . $this->employeeId,
            'department' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);
        
        DB::beginTransaction();
        
        try {
            $employee = User::findOrFail($this->employeeId);
            
            $employee->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'employee_id' => $this->employee_id,
                'department' => $this->department,
                'notes' => $this->notes,
            ]);
            
            // Sync with FortiGate
            $this->fortiGateService->updateUser($employee);
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'employee.updated',
                'details' => [
                    'employee_id' => $employee->id,
                    'changes' => $employee->getChanges(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            $this->showEditModal = false;
            $this->resetForm();
            
            session()->flash('success', __('messages.employee_updated'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update employee', [
                'error' => $e->getMessage(),
                'employee_id' => $this->employeeId,
            ]);
            
            session()->flash('error', __('messages.employee_update_failed'));
        }
    }
    
    public function confirmDelete($id)
    {
        $this->employeeId = $id;
        $this->showDeleteModal = true;
    }
    
    public function delete()
    {
        DB::beginTransaction();
        
        try {
            $employee = User::findOrFail($this->employeeId);
            
            // Delete from FortiGate
            $this->fortiGateService->deleteUser($employee);
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'employee.deleted',
                'details' => [
                    'employee_id' => $employee->id,
                    'email' => $employee->email,
                    'department' => $employee->department,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            // Delete the user
            $employee->delete();
            
            DB::commit();
            
            $this->showDeleteModal = false;
            
            session()->flash('success', __('messages.employee_deleted'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete employee', [
                'error' => $e->getMessage(),
                'employee_id' => $this->employeeId,
            ]);
            
            session()->flash('error', __('messages.employee_deletion_failed'));
        }
    }
    
    public function resetPassword($id)
    {
        DB::beginTransaction();
        
        try {
            $employee = User::findOrFail($id);
            
            // Generate new password
            $password = $this->userService->generateSecurePassword();
            $employee->password = bcrypt($password);
            $employee->save();
            
            // Sync with FortiGate
            $this->fortiGateService->updateUser($employee);
            
            // Send new credentials
            $this->notificationService->sendWelcomeEmail($employee, $password);
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'employee.password_reset',
                'details' => [
                    'employee_id' => $employee->id,
                    'email' => $employee->email,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            session()->flash('success', __('messages.password_reset_sent'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to reset employee password', [
                'error' => $e->getMessage(),
                'employee_id' => $id,
            ]);
            
            session()->flash('error', __('messages.password_reset_failed'));
        }
    }
    
    public function toggleStatus($id)
    {
        DB::beginTransaction();
        
        try {
            $employee = User::findOrFail($id);
            
            $newStatus = $employee->status === User::STATUS_ACTIVE 
                ? User::STATUS_SUSPENDED 
                : User::STATUS_ACTIVE;
            
            $employee->status = $newStatus;
            $employee->save();
            
            // Sync with FortiGate
            if ($newStatus === User::STATUS_SUSPENDED) {
                $this->fortiGateService->disableUser($employee);
                $this->notificationService->sendAccountSuspendedEmail($employee);
            } else {
                $this->fortiGateService->enableUser($employee);
                $this->notificationService->sendAccountReactivatedEmail($employee);
            }
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'employee.status_changed',
                'details' => [
                    'employee_id' => $employee->id,
                    'old_status' => $employee->getOriginal('status'),
                    'new_status' => $newStatus,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            $message = $newStatus === User::STATUS_SUSPENDED 
                ? __('messages.employee_suspended')
                : __('messages.employee_activated');
            
            session()->flash('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to toggle employee status', [
                'error' => $e->getMessage(),
                'employee_id' => $id,
            ]);
            
            session()->flash('error', __('messages.status_change_failed'));
        }
    }
    
    public function openImportModal()
    {
        $this->showImportModal = true;
    }
    
    public function importEmployees()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:2048',
        ]);
        
        // This would be implemented with a CSV import service
        session()->flash('info', __('messages.import_feature_coming_soon'));
        
        $this->showImportModal = false;
    }
    
    public function exportEmployees()
    {
        // This will be implemented in the Excel export task
        session()->flash('info', __('messages.export_feature_coming_soon'));
    }
    
    protected function resetForm()
    {
        $this->employeeId = null;
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
        $this->employee_id = '';
        $this->department = '';
        $this->notes = '';
        $this->send_credentials = true;
    }
}
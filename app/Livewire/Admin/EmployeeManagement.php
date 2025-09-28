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
    public $showCredentialsModal = false;
    
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

    // Credentials display
    public $displayUsername;
    public $displayPassword;
    public $captivePortalUrl;

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
                'name' => trim($this->first_name . ' ' . $this->last_name),
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'employee_id' => $this->employee_id,
                'department' => $this->department,
                'admin_notes' => $this->notes,
            ]);
            
            // Generate password
            $password = $this->userService->generateSecurePassword();
            $employee->password = bcrypt($password);
            $employee->fortigate_password = $password;

            // Generate FortiGate username
            $employee->fortigate_username = 'employee-' . $employee->id;
            $employee->save();

            // Sync with FortiGate
            $this->fortiGateService->createUser([
                'username' => $employee->fortigate_username,
                'password' => $password,
                'email' => $employee->email,
                'status' => 'enable',
            ]);
            
            // Send credentials if requested
            if ($this->send_credentials) {
                $this->notificationService->sendWelcomeEmail($employee, $password);
            }
            
            // Log the action
            AuditLog::logUserManagement(
                'created',
                'employee',
                $employee->id,
                null,
                [
                    'email' => $employee->email,
                    'name' => $employee->name,
                    'department' => $employee->department,
                ]
            );
            
            DB::commit();

            $this->showCreateModal = false;
            $this->resetForm();

            session()->flash('success', __('messages.employee_created') . ' | ' . __('employee.fortigate_username') . ': ' . $employee->fortigate_username . ' | ' . __('employee.fortigate_password') . ': ' . $password);
            
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
                'name' => trim($this->first_name . ' ' . $this->last_name),
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'employee_id' => $this->employee_id,
                'department' => $this->department,
                'admin_notes' => $this->notes,
            ]);

            // Sync with FortiGate
            if ($employee->fortigate_username) {
                $this->fortiGateService->updateUser(
                    $employee->fortigate_username,
                    [
                        'email' => $employee->email,
                        'status' => $employee->status === User::STATUS_ACTIVE ? 'enable' : 'disable',
                    ]
                );
            }
            
            // Log the action
            $changes = $employee->getChanges();
            AuditLog::logUserManagement(
                'updated',
                'employee',
                $employee->id,
                $employee->getOriginal(),
                $changes
            );
            
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
            if ($employee->fortigate_username) {
                $this->fortiGateService->deleteUser($employee->fortigate_username);
            }
            
            // Log the action
            AuditLog::logUserManagement(
                'deleted',
                'employee',
                $employee->id,
                [
                    'email' => $employee->email,
                    'name' => $employee->name,
                    'department' => $employee->department,
                ],
                null
            );
            
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
    
    public function showCredentials($id)
    {
        $employee = User::findOrFail($id);
        $this->displayUsername = $employee->fortigate_username;
        $this->displayPassword = $employee->fortigate_password;
        $this->captivePortalUrl = \App\Models\FortiGateSettings::current()->captive_portal_url ?? 'https://192.168.1.1/captive-portal';
        $this->showCredentialsModal = true;
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
            if ($employee->fortigate_username) {
                $this->fortiGateService->updateUser(
                    $employee->fortigate_username,
                    [
                        'status' => $newStatus === User::STATUS_ACTIVE ? 'enable' : 'disable',
                    ]
                );

                // Deauthenticate if suspending
                if ($newStatus === User::STATUS_SUSPENDED) {
                    $this->fortiGateService->deauthenticateUser($employee->fortigate_username);
                    $this->notificationService->sendAccountSuspendedEmail($employee);
                } else {
                    $this->notificationService->sendAccountReactivatedEmail($employee);
                }
            }
            
            // Log the action
            AuditLog::log([
                'event_type' => AuditLog::EVENT_TYPES['USER_UPDATED'],
                'event_category' => AuditLog::EVENT_CATEGORIES['USER_MANAGEMENT'],
                'user_type' => 'admin',
                'user_id' => auth('admin')->id(),
                'user_email' => auth('admin')->user()->email,
                'action' => 'status_changed',
                'resource_type' => 'employee',
                'resource_id' => $employee->id,
                'old_values' => [
                    'status' => $employee->getOriginal('status'),
                ],
                'new_values' => [
                    'status' => $newStatus,
                ],
                'status' => AuditLog::STATUS['SUCCESS'],
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
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

class ConsultantManagement extends Component
{
    use WithPagination;
    
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    
    public $search = '';
    public $statusFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // Form fields
    public $consultantId;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $company_name;
    public $expires_at;
    public $notes;
    public $send_credentials = true;
    
    protected $rules = [
        'first_name' => 'required|string|max:100',
        'last_name' => 'required|string|max:100',
        'email' => 'required|email',
        'company_name' => 'required|string|max:200',
        'expires_at' => 'required|date|after:today',
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
        $consultants = User::where('user_type', User::TYPE_CONSULTANT)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('email', 'like', '%' . $this->search . '%')
                      ->orWhere('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('company_name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
        
        return view('livewire.admin.consultant-management', [
            'consultants' => $consultants,
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
            // Create consultant
            $consultant = $this->userService->createConsultant([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'company_name' => $this->company_name,
                'expires_at' => $this->expires_at,
                'notes' => $this->notes,
            ]);
            
            // Generate password
            $password = $this->userService->generateSecurePassword();
            $consultant->password = bcrypt($password);
            $consultant->save();
            
            // Sync with FortiGate
            $this->fortiGateService->createUser($consultant);
            
            // Send credentials if requested
            if ($this->send_credentials) {
                $this->notificationService->sendWelcomeEmail($consultant, $password);
            }
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'consultant.created',
                'details' => [
                    'consultant_id' => $consultant->id,
                    'email' => $consultant->email,
                    'expires_at' => $consultant->expires_at,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            $this->showCreateModal = false;
            $this->resetForm();
            
            session()->flash('success', __('messages.consultant_created'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create consultant', [
                'error' => $e->getMessage(),
                'data' => $this->all(),
            ]);
            
            session()->flash('error', __('messages.consultant_creation_failed'));
        }
    }
    
    public function edit($id)
    {
        $consultant = User::findOrFail($id);
        
        $this->consultantId = $consultant->id;
        $this->first_name = $consultant->first_name;
        $this->last_name = $consultant->last_name;
        $this->email = $consultant->email;
        $this->phone = $consultant->phone;
        $this->company_name = $consultant->company_name;
        $this->expires_at = $consultant->expires_at->format('Y-m-d');
        $this->notes = $consultant->notes;
        
        $this->showEditModal = true;
    }
    
    public function update()
    {
        $this->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $this->consultantId,
            'company_name' => 'required|string|max:200',
            'expires_at' => 'required|date|after:today',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);
        
        DB::beginTransaction();
        
        try {
            $consultant = User::findOrFail($this->consultantId);
            
            $consultant->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'company_name' => $this->company_name,
                'expires_at' => $this->expires_at,
                'notes' => $this->notes,
            ]);
            
            // Sync with FortiGate
            $this->fortiGateService->updateUser($consultant);
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'consultant.updated',
                'details' => [
                    'consultant_id' => $consultant->id,
                    'changes' => $consultant->getChanges(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            $this->showEditModal = false;
            $this->resetForm();
            
            session()->flash('success', __('messages.consultant_updated'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update consultant', [
                'error' => $e->getMessage(),
                'consultant_id' => $this->consultantId,
            ]);
            
            session()->flash('error', __('messages.consultant_update_failed'));
        }
    }
    
    public function confirmDelete($id)
    {
        $this->consultantId = $id;
        $this->showDeleteModal = true;
    }
    
    public function delete()
    {
        DB::beginTransaction();
        
        try {
            $consultant = User::findOrFail($this->consultantId);
            
            // Delete from FortiGate
            $this->fortiGateService->deleteUser($consultant);
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'consultant.deleted',
                'details' => [
                    'consultant_id' => $consultant->id,
                    'email' => $consultant->email,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            // Delete the user
            $consultant->delete();
            
            DB::commit();
            
            $this->showDeleteModal = false;
            
            session()->flash('success', __('messages.consultant_deleted'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete consultant', [
                'error' => $e->getMessage(),
                'consultant_id' => $this->consultantId,
            ]);
            
            session()->flash('error', __('messages.consultant_deletion_failed'));
        }
    }
    
    public function extend($id, $days = 30)
    {
        DB::beginTransaction();
        
        try {
            $consultant = User::findOrFail($id);
            
            $newExpiry = $consultant->expires_at->addDays($days);
            $consultant->expires_at = $newExpiry;
            $consultant->save();
            
            // Sync with FortiGate
            $this->fortiGateService->updateUser($consultant);
            
            // Send notification
            $this->notificationService->sendExpirationReminder($consultant, $days);
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'consultant.extended',
                'details' => [
                    'consultant_id' => $consultant->id,
                    'days_extended' => $days,
                    'new_expiry' => $newExpiry,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            session()->flash('success', __('messages.consultant_extended'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to extend consultant', [
                'error' => $e->getMessage(),
                'consultant_id' => $id,
            ]);
            
            session()->flash('error', __('messages.consultant_extension_failed'));
        }
    }
    
    public function resetPassword($id)
    {
        DB::beginTransaction();
        
        try {
            $consultant = User::findOrFail($id);
            
            // Generate new password
            $password = $this->userService->generateSecurePassword();
            $consultant->password = bcrypt($password);
            $consultant->save();
            
            // Sync with FortiGate
            $this->fortiGateService->updateUser($consultant);
            
            // Send new credentials
            $this->notificationService->sendWelcomeEmail($consultant, $password);
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'consultant.password_reset',
                'details' => [
                    'consultant_id' => $consultant->id,
                    'email' => $consultant->email,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            session()->flash('success', __('messages.password_reset_sent'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to reset consultant password', [
                'error' => $e->getMessage(),
                'consultant_id' => $id,
            ]);
            
            session()->flash('error', __('messages.password_reset_failed'));
        }
    }
    
    public function toggleStatus($id)
    {
        DB::beginTransaction();
        
        try {
            $consultant = User::findOrFail($id);
            
            $newStatus = $consultant->status === User::STATUS_ACTIVE 
                ? User::STATUS_SUSPENDED 
                : User::STATUS_ACTIVE;
            
            $consultant->status = $newStatus;
            $consultant->save();
            
            // Sync with FortiGate
            if ($newStatus === User::STATUS_SUSPENDED) {
                $this->fortiGateService->disableUser($consultant);
                $this->notificationService->sendAccountSuspendedEmail($consultant);
            } else {
                $this->fortiGateService->enableUser($consultant);
                $this->notificationService->sendAccountReactivatedEmail($consultant);
            }
            
            // Log the action
            AuditLog::create([
                'user_id' => auth('admin')->id(),
                'action' => 'consultant.status_changed',
                'details' => [
                    'consultant_id' => $consultant->id,
                    'old_status' => $consultant->getOriginal('status'),
                    'new_status' => $newStatus,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            DB::commit();
            
            $message = $newStatus === User::STATUS_SUSPENDED 
                ? __('messages.consultant_suspended')
                : __('messages.consultant_activated');
            
            session()->flash('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to toggle consultant status', [
                'error' => $e->getMessage(),
                'consultant_id' => $id,
            ]);
            
            session()->flash('error', __('messages.status_change_failed'));
        }
    }
    
    protected function resetForm()
    {
        $this->consultantId = null;
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
        $this->company_name = '';
        $this->expires_at = '';
        $this->notes = '';
        $this->send_credentials = true;
    }
}
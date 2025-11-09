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
    public $showCredentialsModal = false;
    
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

    // Credentials display
    public $displayUsername;
    public $displayPassword;
    public $captivePortalUrl;
    
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
                'name' => trim($this->first_name . ' ' . $this->last_name),
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'company_name' => $this->company_name,
                'expires_at' => $this->expires_at,
                'admin_notes' => $this->notes,
            ]);
            
            // Generate password
            $password = $this->userService->generateSecurePassword();
            $consultant->password = bcrypt($password);
            $consultant->fortigate_password = $password;

            // Generate FortiGate username
            $consultant->fortigate_username = 'consultant-' . $consultant->id;
            $consultant->save();

            // Sync with FortiGate
            $this->fortiGateService->createUser([
                'username' => $consultant->fortigate_username,
                'password' => $password,
                'email' => $consultant->email,
                'status' => 'enable',
                'expires_at' => $consultant->expires_at ? $consultant->expires_at->format('Y-m-d') : null,
            ]);
            
            // Send credentials if requested
            if ($this->send_credentials) {
                $this->notificationService->sendWelcomeEmail($consultant, $password);
            }
            
            // Log the action
            AuditLog::logUserManagement(
                'created',
                'consultant',
                $consultant->id,
                null,
                [
                    'email' => $consultant->email,
                    'name' => $consultant->name,
                    'company_name' => $consultant->company_name,
                    'expires_at' => $consultant->expires_at?->format('Y-m-d'),
                ]
            );
            
            DB::commit();

            $this->showCreateModal = false;
            $this->resetForm();

            session()->flash('success', __('messages.consultant_created') . ' | ' . __('consultant.fortigate_username') . ': ' . $consultant->fortigate_username . ' | ' . __('consultant.fortigate_password') . ': ' . $password);
            
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
                'name' => trim($this->first_name . ' ' . $this->last_name),
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'company_name' => $this->company_name,
                'expires_at' => $this->expires_at,
                'admin_notes' => $this->notes,
            ]);

            // Sync with FortiGate
            if ($consultant->fortigate_username) {
                $this->fortiGateService->updateUser(
                    $consultant->fortigate_username,
                    [
                        'email' => $consultant->email,
                        'expires_at' => $consultant->expires_at ? $consultant->expires_at->format('Y-m-d') : null,
                        'status' => $consultant->status === User::STATUS_ACTIVE ? 'enable' : 'disable',
                    ]
                );
            }
            
            // Log the action
            $changes = $consultant->getChanges();
            AuditLog::logUserManagement(
                'updated',
                'consultant',
                $consultant->id,
                $consultant->getOriginal(),
                $changes
            );
            
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
            if ($consultant->fortigate_username) {
                $this->fortiGateService->deleteUser($consultant->fortigate_username);
            }
            
            // Log the action
            AuditLog::logUserManagement(
                'deleted',
                'consultant',
                $consultant->id,
                [
                    'email' => $consultant->email,
                    'name' => $consultant->name,
                ],
                null
            );
            
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
            if ($consultant->fortigate_username) {
                $this->fortiGateService->updateUser(
                    $consultant->fortigate_username,
                    [
                        'expires_at' => $consultant->expires_at->format('Y-m-d'),
                    ]
                );
            }
            
            // Send notification
            $this->notificationService->sendExpirationReminder($consultant, $days);
            
            // Log the action
            AuditLog::log([
                'event_type' => AuditLog::EVENT_TYPES['USER_UPDATED'],
                'event_category' => AuditLog::EVENT_CATEGORIES['USER_MANAGEMENT'],
                'user_type' => 'admin',
                'user_id' => auth('admin')->id(),
                'user_email' => auth('admin')->user()->email,
                'action' => 'extended',
                'resource_type' => 'consultant',
                'resource_id' => $consultant->id,
                'new_values' => [
                    'days_extended' => $days,
                    'new_expiry' => $newExpiry->format('Y-m-d'),
                ],
                'status' => AuditLog::STATUS['SUCCESS'],
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
    
    public function showCredentials($id)
    {
        $consultant = User::findOrFail($id);
        $this->displayUsername = $consultant->fortigate_username;
        $this->displayPassword = $consultant->fortigate_password;
        $this->captivePortalUrl = \App\Models\FortiGateSettings::current()->captive_portal_url ?? 'https://192.168.1.1/captive-portal';
        $this->showCredentialsModal = true;
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
            if ($consultant->fortigate_username) {
                $this->fortiGateService->updateUser(
                    $consultant->fortigate_username,
                    [
                        'status' => $newStatus === User::STATUS_ACTIVE ? 'enable' : 'disable',
                    ]
                );

                // Deauthenticate if suspending
                if ($newStatus === User::STATUS_SUSPENDED) {
                    $this->fortiGateService->deauthenticateUser($consultant->fortigate_username);
                    $this->notificationService->sendAccountSuspendedEmail($consultant);
                } else {
                    $this->notificationService->sendAccountReactivatedEmail($consultant);
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
                'resource_type' => 'consultant',
                'resource_id' => $consultant->id,
                'old_values' => [
                    'status' => $consultant->getOriginal('status'),
                ],
                'new_values' => [
                    'status' => $newStatus,
                ],
                'status' => AuditLog::STATUS['SUCCESS'],
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

    public function recreateOnFortiGate($id)
    {
        DB::beginTransaction();

        try {
            $consultant = User::findOrFail($id);

            // Check if consultant is expired
            if ($consultant->isExpired()) {
                session()->flash('error', __('messages.cannot_recreate_expired_user'));
                return;
            }

            // Generate new password if not exists
            if (!$consultant->fortigate_password) {
                $password = $this->userService->generateSecurePassword();
                $consultant->fortigate_password = $password;
                $consultant->save();
            } else {
                $password = $consultant->fortigate_password;
            }

            // Try to create user on FortiGate
            // If user already exists, FortiGate will return an error
            try {
                $this->fortiGateService->createUser([
                    'username' => $consultant->fortigate_username,
                    'password' => $password,
                    'email' => $consultant->email,
                    'status' => $consultant->status === User::STATUS_ACTIVE ? 'enable' : 'disable',
                    'expires_at' => $consultant->expires_at ? $consultant->expires_at->format('Y-m-d') : null,
                ]);

                // Update sync status
                $consultant->updateFortiGateSync(User::SYNC_SYNCED);

                // Log the action
                AuditLog::log([
                    'event_type' => AuditLog::EVENT_TYPES['USER_UPDATED'],
                    'event_category' => AuditLog::EVENT_CATEGORIES['USER_MANAGEMENT'],
                    'user_type' => 'admin',
                    'user_id' => auth('admin')->id(),
                    'user_email' => auth('admin')->user()->email,
                    'action' => 'recreated_on_fortigate',
                    'resource_type' => 'consultant',
                    'resource_id' => $consultant->id,
                    'new_values' => [
                        'fortigate_username' => $consultant->fortigate_username,
                    ],
                    'status' => AuditLog::STATUS['SUCCESS'],
                ]);

                DB::commit();

                session()->flash('success', __('messages.consultant_recreated_on_fortigate'));

            } catch (\App\Exceptions\FortiGateApiException $e) {
                DB::rollBack();

                // Check if error is because user already exists
                $errorMessage = $e->getMessage();
                $apiResponse = $e->getApiResponse();

                // FortiGate returns error when user already exists
                // Check for common error messages or status codes
                if (stripos($errorMessage, 'already exist') !== false ||
                    stripos($errorMessage, 'duplicate') !== false ||
                    (isset($apiResponse['status']) && $apiResponse['status'] === 'error' &&
                     isset($apiResponse['error']) && $apiResponse['error'] == -2)) {

                    session()->flash('error', __('messages.user_already_exists_on_fortigate'));
                } else {
                    Log::error('Failed to recreate consultant on FortiGate', [
                        'error' => $e->getMessage(),
                        'consultant_id' => $id,
                        'api_response' => $apiResponse,
                    ]);

                    session()->flash('error', __('messages.consultant_recreation_failed') . ': ' . $errorMessage);
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to recreate consultant on FortiGate', [
                'error' => $e->getMessage(),
                'consultant_id' => $id,
            ]);

            session()->flash('error', __('messages.consultant_recreation_failed') . ': ' . $e->getMessage());
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
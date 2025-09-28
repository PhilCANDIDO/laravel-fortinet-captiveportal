<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Services\UserService;
use App\Services\FortiGateService;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuestManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $showDeleteModal = false;
    public $userToDelete = null;
    public $showDetailModal = false;
    public $userDetail = null;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10]
    ];
    
    protected $listeners = ['refreshList' => 'render'];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingPerPage()
    {
        $this->resetPage();
    }
    
    public function render()
    {
        $query = User::where('user_type', 'guest')
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('company_name', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc');
            
        $guests = $query->paginate($this->perPage);
        
        return view('livewire.admin.guest-management', [
            'guests' => $guests
        ])->layout('layouts.admin');
    }
    
    public function confirmDelete($userId)
    {
        $this->userToDelete = User::find($userId);
        $this->showDeleteModal = true;
    }
    
    public function deleteUser()
    {
        try {
            DB::beginTransaction();
            
            $user = User::find($this->userToDelete->id);
            
            if (!$user) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Utilisateur non trouvé']);
                return;
            }
            
            // Try to delete from FortiGate (deauthenticate first, then delete)
            try {
                $fortiGateService = app(FortiGateService::class);
                // The deleteUser method now handles deauthentication automatically
                $fortiGateService->deleteUser($user->fortigate_username ?? $user->email);
            } catch (\Exception $fortiGateException) {
                // Log the FortiGate error but continue with database deletion
                Log::warning('Could not delete user from FortiGate: ' . $fortiGateException->getMessage());
            }
            
            // Log the action
            AuditLog::logUserManagement(
                'deleted',
                'guest',
                $user->id,
                [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                null
            );
            
            // Delete from database
            $user->delete();
            
            DB::commit();
            
            $this->showDeleteModal = false;
            $this->userToDelete = null;
            
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Invité supprimé avec succès']);
            $this->dispatch('refreshList');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete guest user: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->showDeleteModal = false;
            $this->userToDelete = null;
            
            // Show more detailed error in development
            $errorMessage = config('app.debug') 
                ? 'Erreur: ' . $e->getMessage() 
                : 'Erreur lors de la suppression de l\'invité';
            
            $this->dispatch('notify', ['type' => 'error', 'message' => $errorMessage]);
        }
    }
    
    public function showUserDetail($userId)
    {
        $this->userDetail = User::find($userId);
        $this->showDetailModal = true;
    }
    
    public function toggleUserStatus($userId)
    {
        try {
            DB::beginTransaction();
            
            $user = User::find($userId);
            
            if (!$user) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Utilisateur non trouvé']);
                return;
            }
            
            // Toggle status between active and suspended
            $newStatus = $user->status === User::STATUS_ACTIVE ? User::STATUS_SUSPENDED : User::STATUS_ACTIVE;
            $user->status = $newStatus;
            $user->save();
            
            // Update in FortiGate
            try {
                $fortiGateService = app(FortiGateService::class);
                
                if ($newStatus === User::STATUS_SUSPENDED) {
                    // Deauthenticate user before disabling
                    $fortiGateService->deauthenticateUser($user->fortigate_username ?? $user->email);
                    $fortiGateService->disableUser($user->fortigate_username ?? $user->email);
                } else {
                    // Enable user
                    $fortiGateService->enableUser($user->fortigate_username ?? $user->email);
                }
            } catch (\Exception $fortiGateException) {
                // Log the FortiGate error but continue
                Log::warning('Could not update user status in FortiGate: ' . $fortiGateException->getMessage());
            }
            
            // Log the action
            AuditLog::log([
                'event_type' => AuditLog::EVENT_TYPES['USER_UPDATED'],
                'event_category' => AuditLog::EVENT_CATEGORIES['USER_MANAGEMENT'],
                'user_type' => 'admin',
                'user_id' => auth('admin')->id(),
                'user_email' => auth('admin')->user()->email,
                'action' => $newStatus === User::STATUS_ACTIVE ? 'enabled' : 'disabled',
                'resource_type' => 'guest',
                'resource_id' => $user->id,
                'old_values' => [
                    'status' => $oldStatus,
                ],
                'new_values' => [
                    'status' => $newStatus,
                ],
                'status' => AuditLog::STATUS['SUCCESS'],
            ]);
            
            DB::commit();
            
            $message = $newStatus === User::STATUS_ACTIVE ? 'Invité activé avec succès' : 'Invité suspendu avec succès';
            $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
            $this->dispatch('refreshList');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to toggle guest user status: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = config('app.debug') 
                ? 'Erreur: ' . $e->getMessage() 
                : 'Erreur lors de la modification du statut de l\'invité';
            
            $this->dispatch('notify', ['type' => 'error', 'message' => $errorMessage]);
        }
    }
    
    public function resendValidationEmail($userId)
    {
        Log::info('resendValidationEmail called for user: ' . $userId);
        
        try {
            $user = User::find($userId);
            
            if (!$user) {
                Log::error('User not found: ' . $userId);
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Utilisateur non trouvé']);
                return;
            }
            
            if ($user->validated_at) {
                $this->dispatch('notify', ['type' => 'info', 'message' => 'Cet utilisateur a déjà validé son email']);
                return;
            }
            
            // Generate new password and update user
            $userService = app(UserService::class);
            $password = $userService->generateSecurePassword();
            $user->password = \Illuminate\Support\Facades\Hash::make($password);
            
            // Regenerate validation token if expired
            if (!$user->validation_token || $user->validation_expires_at->isPast()) {
                $user->validation_token = \Illuminate\Support\Str::random(64);
                $user->validation_expires_at = now()->addMinutes(30);
            }
            $user->save();
            
            // Update in FortiGate if configured
            $fortiGateService = app(\App\Services\FortiGateService::class);
            if ($fortiGateService->isConfigured()) {
                try {
                    $fortiGateService->updateUser($user->fortigate_username ?? $user->email, [
                        'password' => $password,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to update FortiGate password: ' . $e->getMessage());
                }
            }
            
            // Resend validation email
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendGuestValidationEmail($user, $password);
            
            // Log the action
            AuditLog::log([
                'event_type' => AuditLog::EVENT_TYPES['USER_UPDATED'],
                'event_category' => AuditLog::EVENT_CATEGORIES['USER_MANAGEMENT'],
                'user_type' => 'admin',
                'user_id' => auth('admin')->id(),
                'user_email' => auth('admin')->user()->email,
                'action' => 'resend_validation_email',
                'resource_type' => 'guest',
                'resource_id' => $user->id,
                'new_values' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'status' => AuditLog::STATUS['SUCCESS'],
            ]);
            
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Email de validation renvoyé']);
            
        } catch (\Exception $e) {
            Log::error('Failed to resend validation email: ' . $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Erreur lors de l\'envoi de l\'email']);
        }
    }
    
}
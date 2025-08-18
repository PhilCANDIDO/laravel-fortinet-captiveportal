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
            
            // Try to delete from FortiGate (skip if not exists)
            try {
                $fortiGateService = app(FortiGateService::class);
                $fortiGateService->deleteUser($user->fortigate_username ?? $user->email);
            } catch (\Exception $fortiGateException) {
                // Log the FortiGate error but continue with database deletion
                Log::warning('Could not delete user from FortiGate: ' . $fortiGateException->getMessage());
            }
            
            // Log the action
            AuditLog::create([
                'admin_user_id' => auth('admin')->id(),
                'action' => 'delete_guest',
                'model_type' => 'User',
                'model_id' => $user->id,
                'details' => [
                    'email' => $user->email,
                    'name' => $user->name
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'event_type' => 'user_management',
                'event_category' => 'delete',
                'status' => 'success'
            ]);
            
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
            
            // Toggle status
            $newStatus = $user->status === 'active' ? 'disabled' : 'active';
            $user->status = $newStatus;
            $user->save();
            
            // Update in FortiGate
            try {
                $fortiGateService = app(FortiGateService::class);
                $fortiGateService->updateUser($user->fortigate_username ?? $user->email, [
                    'status' => $newStatus === 'active' ? 'enable' : 'disable'
                ]);
            } catch (\Exception $fortiGateException) {
                // Log the FortiGate error but continue
                Log::warning('Could not update user status in FortiGate: ' . $fortiGateException->getMessage());
            }
            
            // Log the action
            AuditLog::create([
                'admin_user_id' => auth('admin')->id(),
                'action' => $newStatus === 'active' ? 'enable_guest' : 'disable_guest',
                'model_type' => 'User',
                'model_id' => $user->id,
                'details' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'new_status' => $newStatus
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'event_type' => 'user_management',
                'event_category' => 'status_change',
                'status' => 'success'
            ]);
            
            DB::commit();
            
            $message = $newStatus === 'active' ? 'Invité activé avec succès' : 'Invité désactivé avec succès';
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
            AuditLog::create([
                'admin_user_id' => auth('admin')->id(),
                'action' => 'resend_validation_email',
                'model_type' => 'User',
                'model_id' => $user->id,
                'details' => [
                    'email' => $user->email,
                    'name' => $user->name
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'event_type' => 'user_management',
                'event_category' => 'email',
                'status' => 'success'
            ]);
            
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Email de validation renvoyé']);
            
        } catch (\Exception $e) {
            Log::error('Failed to resend validation email: ' . $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Erreur lors de l\'envoi de l\'email']);
        }
    }
    
}
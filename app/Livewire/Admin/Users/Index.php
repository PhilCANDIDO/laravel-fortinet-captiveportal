<?php

namespace App\Livewire\Admin\Users;

use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Services\AuditService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $confirmingUserDeletion = false;
    
    // Form fields
    public $userId;
    public $name = '';
    public $email = '';
    public $role = AdminUser::ROLE_ADMIN;
    public $is_active = true;
    public $password = '';
    public $password_confirmation = '';
    public $google2fa_enabled = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'role' => 'required|in:admin,super_admin',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = AdminUser::query();
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->roleFilter) {
            $query->where('role', $this->roleFilter);
        }
        
        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter);
        }
        
        $users = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return view('livewire.admin.users.index', [
            'users' => $users
        ]);
    }

    public function createUser()
    {
        $this->resetFields();
        $this->showCreateModal = true;
    }

    public function editUser($userId)
    {
        $user = AdminUser::findOrFail($userId);
        
        // Only super admins can edit other users
        if (!auth()->guard('admin')->user()->isSuperAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires.');
            return;
        }
        
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->is_active = $user->is_active;
        $this->google2fa_enabled = $user->google2fa_enabled;

        $this->showEditModal = true;
    }

    public function saveUser()
    {
        // Only super admins can create/edit users
        if (!auth()->guard('admin')->user()->isSuperAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires.');
            return;
        }

        if ($this->userId) {
            // Update existing user
            $this->validate();

            $user = AdminUser::findOrFail($this->userId);

            // Check for email uniqueness excluding current user
            $emailExists = AdminUser::where('email', $this->email)
                ->where('id', '!=', $this->userId)
                ->exists();

            if ($emailExists) {
                $this->addError('email', 'Cet email est déjà utilisé.');
                return;
            }

            $updateData = [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'is_active' => $this->is_active,
                'google2fa_enabled' => $this->google2fa_enabled,
            ];

            if ($this->password) {
                $updateData['password'] = $this->password;
            }

            $user->update($updateData);

            AuditService::log('admin_user_updated', 'admin', [
                'admin_id' => $user->id,
                'changes' => $user->getChanges(),
            ]);

            session()->flash('message', 'Utilisateur mis à jour avec succès.');
        } else {
            // Create new user - validate password is required
            $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:admin_users,email',
                'role' => 'required|in:admin,super_admin',
                'password' => 'required|min:12|confirmed',
            ]);

            $user = AdminUser::create([
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'password' => $this->password,
                'is_active' => $this->is_active,
                'google2fa_enabled' => $this->google2fa_enabled,
                'email_verified_at' => now(),
            ]);

            AuditService::log('admin_user_created', 'admin', [
                'admin_id' => $user->id,
                'email' => $user->email,
            ]);

            session()->flash('message', 'Utilisateur créé avec succès.');
        }

        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->resetFields();
    }

    public function toggleUserStatus($userId)
    {
        // Only super admins can toggle user status
        if (!auth()->guard('admin')->user()->isSuperAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires.');
            return;
        }
        
        $user = AdminUser::findOrFail($userId);
        
        // Cannot deactivate yourself
        if ($user->id === auth()->guard('admin')->id()) {
            session()->flash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            return;
        }
        
        if ($user->is_active) {
            $user->deactivate();
            $message = 'Utilisateur désactivé avec succès.';
        } else {
            $user->activate();
            $message = 'Utilisateur activé avec succès.';
        }
        
        AuditService::log('admin_user_status_changed', 'admin', [
            'admin_id' => $user->id,
            'new_status' => $user->is_active ? 'active' : 'inactive',
        ]);
        
        session()->flash('message', $message);
    }

    public function resetMfa($userId)
    {
        $currentUser = auth()->guard('admin')->user();
        $user = AdminUser::findOrFail($userId);
        
        if (!$currentUser->canResetMfaFor($user)) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires.');
            return;
        }
        
        $user->resetMfa();
        
        AuditService::log('admin_mfa_reset', 'security', [
            'admin_id' => $user->id,
            'reset_by' => $currentUser->id,
        ]);
        
        session()->flash('message', 'MFA réinitialisé avec succès pour ' . $user->name);
    }

    public function unlockUser($userId)
    {
        // Only super admins can unlock users
        if (!auth()->guard('admin')->user()->isSuperAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires.');
            return;
        }
        
        $user = AdminUser::findOrFail($userId);
        $user->unlockAccount();
        
        AuditService::log('admin_account_unlocked', 'security', [
            'admin_id' => $user->id,
            'unlocked_by' => auth()->guard('admin')->id(),
        ]);
        
        session()->flash('message', 'Compte débloqué avec succès.');
    }

    public function confirmUserDeletion($userId)
    {
        // Only super admins can delete users
        if (!auth()->guard('admin')->user()->isSuperAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires.');
            return;
        }
        
        $this->userId = $userId;
        $this->confirmingUserDeletion = true;
    }

    public function deleteUser()
    {
        // Only super admins can delete users
        if (!auth()->guard('admin')->user()->isSuperAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires.');
            return;
        }
        
        $user = AdminUser::findOrFail($this->userId);
        
        // Cannot delete yourself
        if ($user->id === auth()->guard('admin')->id()) {
            session()->flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return;
        }
        
        // Cannot delete the last super admin
        if ($user->isSuperAdmin() && AdminUser::where('role', AdminUser::ROLE_SUPER_ADMIN)->count() === 1) {
            session()->flash('error', 'Impossible de supprimer le dernier super administrateur.');
            return;
        }
        
        $userEmail = $user->email;
        $userId = $user->id;
        
        $user->delete();
        
        AuditService::log('admin_user_deleted', 'admin', [
            'deleted_admin_id' => $userId,
            'deleted_email' => $userEmail,
        ]);
        
        session()->flash('message', 'Utilisateur supprimé avec succès.');
        $this->confirmingUserDeletion = false;
    }

    public function resetFields()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->role = AdminUser::ROLE_ADMIN;
        $this->is_active = true;
        $this->password = '';
        $this->password_confirmation = '';
        $this->google2fa_enabled = false;
        $this->resetErrorBag();
    }
}
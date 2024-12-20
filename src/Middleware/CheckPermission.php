<?php

namespace Codersgarden\RoleAssign\Middleware;

use Illuminate\Http\Request;
use Closure;
use Codersgarden\RoleAssign\Models\Permission;
use Codersgarden\RoleAssign\Models\RolePermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckPermission
{
    public function handle(Request $request, Closure $next)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Redirect to login if not authenticated
        if (!$user) {
            return redirect()->route('login')->with([
                'status' => 'warn',
                'message' => __('auth.loginFailed'),
            ]);
        }

        // Get the current route name
        $currentRouteName = $request->route()->getName();

        // Step 1: Check if the user email is in ACL and exists in the users table
        $aclEmails = explode(',', config('custom.acl_users'));

        // Check if user email is in ACL list
        if (in_array($user->email, $aclEmails)) {
            // Check if the user exists in the users table (optional, as we're checking the ACL)
            $userExistsInAcl = DB::table('users')->whereIn('email', $aclEmails)->exists();

            // Step 2: Define route permissions for authorized users
            $allowedRoutes = [
                'roles.index',
                'roles.store',
                'roles.update',
                'roles.create',
                'roles.edit',
                'roles.destroy',
                'roles.permissions',
                'permissions.index',
                'permissions.create',
                'permissions.update',
                'permissions.store',
                'permissions.edit',
                'permissions.destroy',
                'permission-groups.index',
                'permission-groups.create',
                'permission-groups.update',
                'permission-groups.store',
                'permission-groups.edit',
                'permission-groups.destroy',
                'api.permissions.assign-or-remove',
            ];

            // Step 3: Allow access to authorized users if they are on allowed routes
            if ($userExistsInAcl && in_array($currentRouteName, $allowedRoutes)) {
                return $next($request);
            }
        }



        $permissions = $this->getAllPermissionsForActiveUser();

        $permissionExists = Permission::where('route', $currentRouteName)->exists();


        // If the route doesn't require permissions, allow access
        if (!$permissionExists) {
            return $next($request);
        }

        // Redirect if the user does not have permission for the current route
        if (!in_array($currentRouteName, $permissions)) {
            abort(403, 'Access Denied');
        }

        // Allow access for users with valid permissions
        return $next($request);

        // If none of the conditions are met, deny access
        abort(403, 'Access Denied');
    }


    private function getAllPermissionsForActiveUser(): array
    {

        $permissions = [];

        if (Auth::check()) {
            $user = Auth::user()->load('role');

            $roleID = $user->role->id;


            $permissions = RolePermission::where('role_permissions.role_id', $roleID)
                ->leftJoin('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->pluck('permissions.route')
                ->toArray();
        }

        return $permissions;
    }
}

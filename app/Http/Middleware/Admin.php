<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        
        // get all roles using model and use foreach loop to check in array condition
        $excludedRoles = ['rider', 'driver'];
        $allowedRoles = Role::where('status', 1)->whereNotIn('name', $excludedRoles)->pluck('name')->toArray();

        if (Auth::check() && auth()->user()->hasAnyRole($allowedRoles) && auth()->user()->status == 'active') {
            return $next($request);
        }else {
            Auth::logout();
            abort(403, __('message.access_denied'));
        }
    }
}

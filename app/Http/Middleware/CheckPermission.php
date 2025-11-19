<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
 

   public function handle($request, Closure $next, $permission = null)
{
    $hr = auth('hr-api')->user();

        if (!$hr) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Super Admin دايمًا مسموح
        if ($hr->is_super_admin) {
            return $next($request);
        }

        // لو ما فيش permission محدد، مرّ
        if (!$permission) {
            return $next($request);
        }

        // تحقق من صلاحيات HR
        if (!$hr->permissions->contains('name', $permission)) {
            return response()->json(['error' => 'Forbidden - No Permission'], 403);
        }

        return $next($request);
}

}



    


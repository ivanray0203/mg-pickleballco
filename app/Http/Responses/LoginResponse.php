<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Send administrators to the admin dashboard and everyone else to the
     * client dashboard after a successful login.
     */
    public function toResponse($request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        return redirect()->intended(
            $user && $user->isAdmin()
                ? route('admin.dashboard')
                : route('dashboard'),
        );
    }
}

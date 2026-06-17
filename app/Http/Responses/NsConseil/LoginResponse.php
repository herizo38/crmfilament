<?php

namespace App\Http\Responses\NsConseil;

use App\Services\Crm\CrmProfileService;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();
        $profiles = app(CrmProfileService::class);

        $url = $profiles->landingPathFor($user, 'ns-conseil') ?? '/ns-conseil';

        if ($request->wantsJson()) {
            return response()->json(['redirect' => $url]);
        }

        return redirect()->to($url);
    }
}

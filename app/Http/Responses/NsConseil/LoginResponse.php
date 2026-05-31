<?php

namespace App\Http\Responses\NsConseil;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        $user = auth()->user();

        $url = match (true) {
            $user->hasRole('commercial') => '/ns-conseil/partenaires',
            $user->hasRole('teleprospecteur') => '/ns-conseil/prospects',
            default => '/ns-conseil',
        };

        // En mode SPA Livewire, il faut forcer une vraie réponse HTTP
        return new \Illuminate\Http\RedirectResponse($url);
    }
}
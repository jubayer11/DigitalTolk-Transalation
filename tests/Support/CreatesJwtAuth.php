<?php

namespace Tests\Support;

use App\Models\User;

trait CreatesJwtAuth
{
    protected function authHeaders(User $user): array
    {
        $token = auth('api')->login($user);

        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}

<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\PaymentRequest;
use App\Policies\PaymentRequestPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // ...existing policies...
        PaymentRequest::class => PaymentRequestPolicy::class,
    ];

    // ...existing code...
}

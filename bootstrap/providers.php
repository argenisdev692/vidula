<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,

    Src\Providers\CoreServiceProvider::class,

    // ── Bounded Context Providers ──
    Modules\Auth\Providers\AuthServiceProvider::class,
    Modules\Permissions\Providers\PermissionsServiceProvider::class,
    Modules\Roles\Providers\RolesServiceProvider::class,
    Modules\Users\Providers\UsersServiceProvider::class,
    Modules\Clients\Providers\ClientServiceProvider::class,
    Modules\Products\Providers\ProductServiceProvider::class,
    Modules\Students\Providers\StudentServiceProvider::class,
    Modules\CompanyData\Providers\CompanyDataServiceProvider::class,
    Modules\Blog\Providers\BlogServiceProvider::class,
];

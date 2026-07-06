<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Modules\ActivityLog\Providers\ActivityLogServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Backup\Providers\BackupServiceProvider;
use Modules\Company\Providers\CompanyServiceProvider;
use Modules\Users\Providers\UsersServiceProvider;
use Shared\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    TelescopeServiceProvider::class,
    SharedServiceProvider::class,
    AuthServiceProvider::class,
    UsersServiceProvider::class,
    CompanyServiceProvider::class,
    ActivityLogServiceProvider::class,
    BackupServiceProvider::class,
];

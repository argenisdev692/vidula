<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Modules\ActivityLog\Providers\ActivityLogServiceProvider;
use Modules\Appointment\Providers\AppointmentServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Authorization\Providers\AuthorizationServiceProvider;
use Modules\Availability\Providers\AvailabilityServiceProvider;
use Modules\Backup\Providers\BackupServiceProvider;
use Modules\Blog\Providers\BlogServiceProvider;
use Modules\Company\Providers\CompanyServiceProvider;
use Modules\ContactSupport\Providers\ContactSupportServiceProvider;
use Modules\Portfolio\Providers\PortfolioServiceProvider;
use Modules\Post\Providers\PostServiceProvider;
use Modules\Services\Providers\ServicesServiceProvider;
use Modules\Users\Providers\UsersServiceProvider;
use Shared\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    TelescopeServiceProvider::class,
    SharedServiceProvider::class,
    AuthServiceProvider::class,
    AuthorizationServiceProvider::class,
    UsersServiceProvider::class,
    CompanyServiceProvider::class,
    ActivityLogServiceProvider::class,
    BackupServiceProvider::class,
    BlogServiceProvider::class,
    ContactSupportServiceProvider::class,
    AvailabilityServiceProvider::class,
    PortfolioServiceProvider::class,
    ServicesServiceProvider::class,
    AppointmentServiceProvider::class,
    PostServiceProvider::class,
];

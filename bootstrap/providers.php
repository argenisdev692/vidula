<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    Modules\ActivityLog\Providers\ActivityLogServiceProvider::class,
    Modules\Appointment\Providers\AppointmentServiceProvider::class,
    Modules\Auth\Providers\AuthServiceProvider::class,
    Modules\Authorization\Providers\AuthorizationServiceProvider::class,
    Modules\Availability\Providers\AvailabilityServiceProvider::class,
    Modules\Backup\Providers\BackupServiceProvider::class,
    Modules\Blog\Providers\BlogServiceProvider::class,
    Modules\Company\Providers\CompanyServiceProvider::class,
    Modules\ContactSupport\Providers\ContactSupportServiceProvider::class,
    Modules\Portfolio\Providers\PortfolioServiceProvider::class,
    Modules\Post\Providers\PostServiceProvider::class,
    Modules\Services\Providers\ServicesServiceProvider::class,
    Modules\Users\Providers\UsersServiceProvider::class,
    Shared\Providers\SharedServiceProvider::class,
];

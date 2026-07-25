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
use Modules\Campaigns\Providers\CampaignServiceProvider;
use Modules\Clients\Providers\ClientsServiceProvider;
use Modules\Company\Providers\CompanyServiceProvider;
use Modules\ContactSupport\Providers\ContactSupportServiceProvider;
use Modules\Invoices\Providers\InvoicesServiceProvider;
use Modules\Meeting\Providers\MeetingServiceProvider;
use Modules\Portfolio\Providers\PortfolioServiceProvider;
use Modules\Post\Providers\PostServiceProvider;
use Modules\Services\Providers\ServicesServiceProvider;
use Modules\SocialMedia\Providers\SocialMediaServiceProvider;
use Modules\Students\Providers\StudentsServiceProvider;
use Modules\Users\Providers\UsersServiceProvider;
use Shared\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    TelescopeServiceProvider::class,
    ActivityLogServiceProvider::class,
    AppointmentServiceProvider::class,
    AuthServiceProvider::class,
    AuthorizationServiceProvider::class,
    AvailabilityServiceProvider::class,
    BackupServiceProvider::class,
    BlogServiceProvider::class,
    CampaignServiceProvider::class,
    ClientsServiceProvider::class,
    CompanyServiceProvider::class,
    ContactSupportServiceProvider::class,
    InvoicesServiceProvider::class,
    MeetingServiceProvider::class,
    PortfolioServiceProvider::class,
    PostServiceProvider::class,
    ServicesServiceProvider::class,
    SocialMediaServiceProvider::class,
    StudentsServiceProvider::class,
    UsersServiceProvider::class,
    SharedServiceProvider::class,
];

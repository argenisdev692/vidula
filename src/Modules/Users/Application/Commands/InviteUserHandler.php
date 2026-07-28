<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Modules\Authorization\Domain\SystemRoles;
use Modules\Users\Application\DTOs\InviteUserData;
use Modules\Users\Domain\AssignableAccess;
use Modules\Users\Domain\Events\UserInvited;
use Modules\Users\Domain\Ports\InvitationLinkPort;
use Modules\Users\Domain\Ports\UserInvitationNotifierPort;
use Modules\Users\Domain\Ports\UserRepositoryPort;

/**
 * Creates a Pending user (no password) and emails a signed activation link.
 * The new account cannot authenticate until it is activated.
 */
final readonly class InviteUserHandler
{
    private const LINK_TTL_HOURS = 72;

    public function __construct(
        private UserRepositoryPort $users,
        private InvitationLinkPort $links,
        private UserInvitationNotifierPort $notifier,
        private Dispatcher $events,
    ) {}

    #[\NoDiscard]
    public function handle(InviteUserData $data, ?User $actor): string
    {
        // Defence-in-depth: an actor can only seed roles they themselves hold.
        if ($actor !== null) {
            AssignableAccess::assertRolesAllowed(
                $actor->hasRole(SystemRoles::SUPER_ADMIN),
                array_values($actor->getRoleNames()->all()),
                $data->roles,
            );
        }

        $invitedByUuid = $actor?->uuid;

        $user = DB::transaction(function () use ($data, $invitedByUuid): User {
            $user = $this->users->createPending([
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'email' => $data->email,
                'username' => $data->username,
                'phone' => $data->phone,
                'date_of_birth' => $data->dateOfBirth,
                'gender' => $data->gender,
                'address' => $data->address,
                'address_2' => $data->address2,
                'zip_code' => $data->zipCode,
                'city' => $data->city,
                'state' => $data->state,
                'country' => $data->country,
                'country_code' => $data->countryCode,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
                'invited_at' => now(),
                'invited_by' => $invitedByUuid,
            ]);

            if ($data->roles !== []) {
                $user->syncRoles($data->roles);
            }

            return $user;
        });

        $this->dispatchInvitation($user, $invitedByUuid);

        return $user->uuid;
    }

    private function dispatchInvitation(User $user, ?string $invitedByUuid): void
    {
        $link = $this->links->generate($user, self::LINK_TTL_HOURS);

        $this->notifier->send($user, $link, self::LINK_TTL_HOURS);

        $this->events->dispatch(new UserInvited($user->uuid, $user->email, $invitedByUuid));
    }
}

<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Navbar notification bells: shared per-module channels (not per-user) since
// any staff member holding the matching VIEW_ANY_* permission should see a
// new public submission live, not just whoever caused it.
Broadcast::channel('notifications.contact-supports', function ($user) {
    return (bool) $user->hasPermissionTo('VIEW_ANY_CONTACT_SUPPORTS');
});

Broadcast::channel('notifications.appointments', function ($user) {
    return (bool) $user->hasPermissionTo('VIEW_ANY_APPOINTMENTS');
});

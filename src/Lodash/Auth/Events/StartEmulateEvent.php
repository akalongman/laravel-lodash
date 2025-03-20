<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Longman\LaravelLodash\Auth\Contracts\UserContract;

class StartEmulateEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public UserContract $user,
        public UserContract $emulatedUser,
    ) {
        //
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('channel-name');
    }
}

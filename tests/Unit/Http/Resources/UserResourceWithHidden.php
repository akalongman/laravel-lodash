<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

class UserResourceWithHidden extends UserResource
{
    protected static array $hideInOutput = [
        'mail',
    ];
}

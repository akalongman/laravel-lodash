<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Contracts;

use Laravel\Passport\Client;

interface ClientRepositoryContract
{
    public function find($id);

    public function findActive($id);

    public function findForUser($clientId, $userId);

    public function forUser($userId);

    public function activeForUser($userId);

    public function personalAccessClient();

    public function create($userId, $name, $redirect, $provider = null, $personalAccess = false, $password = false, $confidential = true);

    public function createPersonalAccessClient($userId, $name, $redirect);

    public function createPasswordGrantClient($userId, $name, $redirect, $provider = null);

    public function update(Client $client, $name, $redirect);

    public function regenerateSecret(Client $client);

    public function revoked($id);

    public function delete(Client $client);

    public function getPersonalAccessClientId();

    public function getPersonalAccessClientSecret();
}

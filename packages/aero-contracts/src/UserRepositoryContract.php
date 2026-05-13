<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Support\Collection;

interface UserRepositoryContract
{
    public function find(int $id): ?UserContract;
    public function findByEmail(string $email): ?UserContract;
    public function findActive(): Collection;
    public function findByRoles(array $roles): Collection;
    public function findByDepartment(int $departmentId): Collection;
    public function findManagers(): Collection;
    public function findHRUsers(): Collection;
    public function findSafetyTeam(): Collection;
    public function getModelClass(): string;
    public function query(): mixed;
}

<?php

declare(strict_types=1);

namespace App\Infrastructures\Queries\User;

interface EloquentUserQueryServiceInterface
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByDeletedAtNull(): \Illuminate\Database\Eloquent\Collection;

    /**
     * @param integer $id
     * @param string $emailVerifyToken
     * @return \App\Infrastructures\Models\Eloquent\User
     */
    public function firstByIdEmailVerifyToken(
        int $id,
        string $emailVerifyToken
    ): \App\Infrastructures\Models\Eloquent\User;
}

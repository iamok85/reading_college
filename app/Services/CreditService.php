<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public const COST_CORRECTION_ANALYSIS = 5;
    public const COST_IMAGES = 10;
    public const COST_VIDEO = 20;
    public const COST_READING_RECOMMENDATIONS = 20;

    public function hasEnough(User $user, int $cost): bool
    {
        return (int) ($user->credits ?? 0) >= $cost;
    }

    public function charge(User $user, int $cost): bool
    {
        if ($cost <= 0) {
            return true;
        }

        return DB::transaction(function () use ($user, $cost) {
            $updated = User::whereKey($user->id)
                ->where('credits', '>=', $cost)
                ->decrement('credits', $cost);

            if ($updated) {
                $user->refresh();
                return true;
            }

            return false;
        });
    }
}

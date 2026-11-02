<?php
namespace App\Policies;
use App\Models\{LoanRequest, User};

class LoanRequestPolicy
{
    public function view(User $u, LoanRequest $l): bool { return $u->isAdmin() || $l->user_id === $u->id; }
    public function create(User $u): bool { return ! $u->isAdmin(); }
    public function decide(User $u, LoanRequest $l): bool { return $u->isAdmin(); }
    public function repay(User $u, LoanRequest $l): bool { return $u->isAdmin(); }
    public function upload(User $u, LoanRequest $l): bool { return $l->user_id === $u->id; }
    public function dashboard(User $u): bool { return $u->isAdmin(); }
}

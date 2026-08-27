<?php

namespace App\ClientOps\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'contact_email',
        'contact_phone',
        'notes',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ClientAssignment::class);
    }

    /**
     * The account manager currently responsible for this client — the
     * assignment row with no ended_at. Null if nobody is assigned.
     */
    public function currentAccountManager(): ?User
    {
        return $this->assignments()->whereNull('ended_at')->latest('started_at')->first()?->user;
    }

    /**
     * Close out whoever currently holds this client (if anyone) and open a
     * new assignment for $user. History is preserved — nothing is deleted
     * or overwritten, so cycles/invoices created under the old manager keep
     * their snapshot of who was responsible at the time.
     */
    public function reassignAccountManager(User $user, ?\DateTimeInterface $at = null): ClientAssignment
    {
        $at ??= now();

        $this->assignments()->whereNull('ended_at')->update(['ended_at' => $at]);

        return $this->assignments()->create([
            'user_id' => $user->id,
            'started_at' => $at,
        ]);
    }

    /**
     * True if $user is the account manager currently assigned to this
     * client — a plain Account Manager can only view/edit clients they
     * themselves own, not a colleague's. Access-level roles (Finance,
     * HR Admin, Super Admin) and the Account Manager department's Head
     * are handled separately (Gate::define('manage-client', ...)), since
     * ownership is only meaningful for rank-and-file account managers.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->currentAccountManager()?->id === $user->id;
    }
}

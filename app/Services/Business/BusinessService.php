<?php

namespace App\Services\Business;

use App\Events\BusinessCreated;
use App\Models\Business;
use App\Models\BusinessProfile;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessService
{
    /**
     * Create a new Business for the given user and make them the owner.
     */
    public function createForUser(User $user, array $data): Business
    {
        $slug = $this->generateUniqueSlug($data['name']);

        $business = Business::create([
            'name'     => $data['name'],
            'slug'     => $slug,
            'locale'   => $data['locale'] ?? 'en',
            'timezone' => $data['timezone'] ?? 'Europe/London',
            'plan'     => 'free',
            'is_active' => true,
        ]);

        BusinessUser::create([
            'business_id' => $business->id,
            'user_id'     => $user->id,
            'role'        => 'owner',
            'is_active'   => true,
            'joined_at'   => now(),
        ]);

        // Always create an empty profile so other modules can rely on it existing
        BusinessProfile::create(['business_id' => $business->id]);

        event(new BusinessCreated($business));

        return $business;
    }

    /**
     * Update editable settings of a Business.
     */
    public function update(Business $business, array $data): Business
    {
        $updateData = array_filter([
            'locale'        => $data['locale'] ?? null,
            'timezone'      => $data['timezone'] ?? null,
            'primary_color' => $data['primary_color'] ?? null,
        ], fn ($v) => $v !== null);

        if (isset($data['name']) && $data['name'] !== $business->name) {
            $updateData['name'] = $data['name'];
            $updateData['slug'] = $this->generateUniqueSlug($data['name'], $business->id);
        }

        if (! empty($updateData)) {
            $business->update($updateData);
        }

        if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
            $this->uploadLogo($business, $data['logo']);
        }

        return $business->fresh();
    }

    /**
     * Store a logo file and update the business record.
     */
    public function uploadLogo(Business $business, UploadedFile $file): string
    {
        // Remove old logo if present
        if ($business->logo_path) {
            Storage::disk('public')->delete($business->logo_path);
        }

        $path = $file->storeAs(
            "businesses/{$business->id}",
            'logo.' . $file->getClientOriginalExtension(),
            'public'
        );

        $business->update(['logo_path' => $path]);

        return $path;
    }

    /**
     * Delete the business logo.
     */
    public function deleteLogo(Business $business): void
    {
        if ($business->logo_path) {
            Storage::disk('public')->delete($business->logo_path);
            $business->update(['logo_path' => null]);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function generateUniqueSlug(string $name, ?string $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (
            Business::where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}

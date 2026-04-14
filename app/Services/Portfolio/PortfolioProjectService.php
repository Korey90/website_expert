<?php

namespace App\Services\Portfolio;

use App\Models\PortfolioProject;
use Illuminate\Database\Eloquent\Collection;

class PortfolioProjectService
{
    public function getFeatured(int $limit = 3): Collection
    {
        return PortfolioProject::featured()
            ->ordered()
            ->limit($limit)
            ->get();
    }

    public function getAll(): Collection
    {
        return PortfolioProject::featured()
            ->ordered()
            ->get();
    }

    public function findBySlug(string $slug): ?PortfolioProject
    {
        return PortfolioProject::where('slug', $slug)->first();
    }

    public function create(array $data): PortfolioProject
    {
        return PortfolioProject::create($data);
    }

    public function update(PortfolioProject $project, array $data): PortfolioProject
    {
        $project->update($data);

        return $project->fresh();
    }

    public function delete(PortfolioProject $project): void
    {
        $project->delete();
    }

    public function reorder(array $items): void
    {
        foreach ($items as $item) {
            PortfolioProject::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }
    }
}

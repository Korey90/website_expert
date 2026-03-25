<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Setting;

class ContractInterpolationService
{
    /**
     * All supported placeholder keys grouped by namespace.
     */
    private const LEGAL_KEYS = [
        'company_name', 'company_number', 'vat_number', 'company_address',
        'company_email', 'company_phone', 'deposit_percent', 'payment_terms_days',
        'ico_number', 'privacy_email', 'dpo_name', 'data_retention_years',
        'complaints_email', 'complaints_phone', 'response_days',
    ];

    /**
     * Replace all placeholders in contract content with real values.
     */
    public function interpolate(
        string $content,
        ?Client $client = null,
        ?Project $project = null,
        ?Contract $contract = null
    ): string {
        $content = $this->replaceLegal($content);

        if ($client) {
            $content = $this->replaceClient($content, $client);
        }

        if ($project) {
            $content = $this->replaceProject($content, $project);
        }

        if ($contract) {
            $content = $this->replaceContract($content, $contract);
        }

        return $content;
    }

    private function replaceLegal(string $content): string
    {
        foreach (self::LEGAL_KEYS as $key) {
            $value = Setting::get("legal.{$key}", '');
            $content = str_replace("{{legal.{$key}}}", $value, $content);
        }

        return $content;
    }

    private function replaceClient(string $content, Client $client): string
    {
        $address = implode(', ', array_filter([
            $client->address_line1,
            $client->address_line2,
            $client->city,
            $client->county,
            $client->postcode,
            $client->country,
        ]));

        $map = [
            '{{client.company_name}}'           => $client->company_name ?? '',
            '{{client.trading_name}}'           => $client->trading_name ?? $client->company_name ?? '',
            '{{client.companies_house_number}}' => $client->companies_house_number ?? '',
            '{{client.vat_number}}'             => $client->vat_number ?? '',
            '{{client.address}}'                => $address,
            '{{client.city}}'                   => $client->city ?? '',
            '{{client.postcode}}'               => $client->postcode ?? '',
            '{{client.country}}'                => $client->country ?? '',
            '{{client.primary_contact_name}}'   => $client->primary_contact_name ?? '',
            '{{client.primary_contact_email}}'  => $client->primary_contact_email ?? '',
            '{{client.primary_contact_phone}}'  => $client->primary_contact_phone ?? '',
            '{{client.website}}'                => $client->website ?? '',
        ];

        return str_replace(array_keys($map), array_values($map), $content);
    }

    private function replaceProject(string $content, Project $project): string
    {
        $map = [
            '{{project.title}}'    => $project->title ?? '',
            '{{project.budget}}'   => $project->budget ? number_format($project->budget, 2) : '',
            '{{project.currency}}' => $project->currency ?? '',
            '{{project.deadline}}' => $project->deadline?->format('d/m/Y') ?? '',
            '{{project.start_date}}' => $project->start_date?->format('d/m/Y') ?? '',
        ];

        return str_replace(array_keys($map), array_values($map), $content);
    }

    private function replaceContract(string $content, Contract $contract): string
    {
        $map = [
            '{{contract.number}}' => $contract->number ?? '',
            '{{contract.title}}'  => $contract->title ?? '',
            '{{contract.date}}'   => $contract->starts_at?->format('d/m/Y') ?? now()->format('d/m/Y'),
            '{{contract.value}}'  => $contract->value ? number_format($contract->value, 2) : '',
            '{{contract.currency}}' => $contract->currency ?? '',
        ];

        return str_replace(array_keys($map), array_values($map), $content);
    }
}

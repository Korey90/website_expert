<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = ['name', 'content', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Replace {{variable}} placeholders in content with provided values.
     * Unknown placeholders are left as-is.
     */
    public function render(array $vars): string
    {
        $search  = array_map(fn ($k) => '{{' . $k . '}}', array_keys($vars));
        $replace = array_values($vars);

        return str_replace($search, $replace, $this->content);
    }

    /**
     * Variables supported in templates.
     *
     * @return array<string, string>
     */
    public static function availableVariables(): array
    {
        return [
            'client_name'   => 'Client first/contact name',
            'company_name'  => 'Company name',
            'lead_title'    => 'Lead title',
            'stage_name'    => 'Current pipeline stage',
            'project_name'  => 'Project name (if exists)',
            'assigned_name' => 'Assigned staff member name',
            'today'         => 'Today\'s date (d M Y)',
        ];
    }
}

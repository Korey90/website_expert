<?php

return [

    'industries' => [
        'marketing_agency' => 'Marketing Agency',
        'web_design'       => 'Web Design & Development',
        'ecommerce'        => 'E-commerce',
        'consulting'       => 'Business Consulting',
        'real_estate'      => 'Real Estate',
        'healthcare'       => 'Healthcare',
        'education'        => 'Education',
        'legal'            => 'Legal Services',
        'finance'          => 'Finance & Accounting',
        'hospitality'      => 'Hospitality & Travel',
        'fitness'          => 'Fitness & Wellness',
        'other'            => 'Other',
    ],

    'tones_of_voice' => [
        'professional' => 'Professional & Formal',
        'friendly'     => 'Friendly & Approachable',
        'bold'         => 'Bold & Confident',
        'minimalist'   => 'Minimalist & Clean',
    ],

    'plan_limits' => [
        'free'    => ['landing_pages' => 1,  'leads_per_month' => 50],
        'starter' => ['landing_pages' => 5,  'leads_per_month' => 200],
        'pro'     => ['landing_pages' => 20, 'leads_per_month' => 1000],
        'agency'  => ['landing_pages' => -1, 'leads_per_month' => -1], // -1 = unlimited
    ],

];

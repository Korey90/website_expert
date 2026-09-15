<?php

return [

    'meta' => [
        'title' => 'Free Google Rank Checker — Website Expert',
        'description' => 'Check where your website ranks on Google for any keyword and location. Free, instant, no sign-up required.',
    ],

    'h1' => 'Free Google Rank Checker',
    'intro' => 'Check where your website ranks on Google for any keyword and location.',

    'form' => [
        'keyword_label' => 'Keyword',
        'keyword_placeholder' => 'e.g. web design belfast',
        'website_label' => 'Website',
        'website_placeholder' => 'e.g. yourcompany.co.uk',
        'location_label' => 'Location',
        'location_custom_option' => 'Other location…',
        'location_custom_placeholder' => 'e.g. Manchester, United Kingdom',
        'device_label' => 'Device',
        'device_desktop' => 'Desktop',
        'device_mobile' => 'Mobile',
        'submit_label' => 'Check Ranking',
        'loading_label' => 'Checking Google results…',
    ],

    'result' => [
        'position_label' => 'Position',
        'ranking_page_label' => 'Ranking page',
        'checked_for' => 'Results for ":keyword" — :location, :device',
        'first_page_message' => 'Your website appears on the first page of Google for this keyword.',
        'interpretation' => [
            'top3' => 'Excellent visibility',
            'top10' => 'First page of Google',
            'top20' => 'Close to page one',
            'top50' => 'Your website is ranking, but visibility is limited',
            'top100' => 'Your website is ranking outside the main visible results',
        ],
        'not_found_title' => 'Not found in the top 100 results',
        'not_found_message' => "We couldn't find :domain in the top 100 organic Google results for \":keyword\" from the selected location.",
        'error_title' => "We couldn't complete the ranking check",
        'error_message' => "We couldn't complete the ranking check right now. Please try again shortly.",
        'disclaimer' => 'Google rankings vary by location, device, language and search history. This result reflects the selected search location and device, not a guaranteed position for every visitor.',
    ],

    'cta' => [
        'good_title' => "You're already ranking well. Find out what could help you move even higher.",
        'good_button' => 'Request an SEO review',
        'bad_title' => 'Want to improve your Google visibility?',
        'bad_body' => 'Get a free SEO audit and find out what is holding your website back.',
        'bad_button' => 'Get a free SEO audit',
        'contact_button' => 'Contact us',
    ],

    'faq' => [
        ['q' => 'Is this Google rank checker free?', 'a' => 'Yes. You can check your Google ranking for any keyword, domain, location and device for free, with no sign-up required.'],
        ['q' => 'Why does my ranking differ from what I see in Google?', 'a' => 'Google results depend on your location, device, language, search history and the data centre you are connected to. This tool shows a representative result for the location and device you select, not a position guaranteed for every visitor.'],
        ['q' => 'How many results does the checker search?', 'a' => 'The tool searches the top 100 organic Google results for your keyword and location.'],
        ['q' => "What if my website isn't found?", 'a' => "If your website doesn't appear in the top 100 organic results, we'll let you know — it's a normal, valid result, not an error."],
        ['q' => 'Does this check subdomains?', 'a' => 'If you enter a root domain (e.g. example.com), matching subdomains (e.g. shop.example.com) are included. If you enter a specific subdomain, only that exact subdomain is checked.'],
        ['q' => 'How often can I check a keyword?', 'a' => 'To keep the tool free for everyone, checks are rate-limited and identical checks are cached for a few hours.'],
    ],

    'errors' => [
        'keyword_required' => 'Please enter a keyword to check.',
        'website_required' => 'Please enter your website address.',
        'location_required' => 'Please select a search location.',
        'invalid_domain' => 'Please enter a valid website address (e.g. example.co.uk).',
        'recaptcha_failed' => 'We could not verify your request. Please try again.',
        'rate_limited' => "You've reached the check limit. Please try again in a while.",
    ],

    'contact' => [
        'ctaFormTitle' => 'Tell us about your needs',
        'labelName' => 'Full name',
        'labelContact' => 'Email or phone number',
        'labelMessage' => 'Message',
        'labelWebsite' => 'Website',
        'placeholderName' => 'John Smith',
        'placeholderContact' => 'email or phone',
        'placeholderWebsite' => 'yourcompany.co.uk',
        'gdprText' => 'I agree to the processing of my personal data in accordance with the',
        'gdprLink' => 'privacy policy',
        'submitBtn' => 'Send enquiry',
        'sending' => 'Sending…',
        'successTitle' => "We'll be in touch!",
        'successMsg' => "Thanks for reaching out. We'll get back to you within 24 business hours.",
        'errorMsg' => 'Something went wrong. Please check your details and try again.',
        'validationMsg' => 'Please fill in your name, contact details and message.',
        'clearMessage' => 'Clear message',
    ],

];

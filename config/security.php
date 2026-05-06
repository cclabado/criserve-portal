<?php

return [
    'uploads' => [
        'disk' => env('SECURE_DOCUMENT_DISK', 'local'),
        'allowed_extensions' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SECURE_UPLOAD_ALLOWED_EXTENSIONS', 'pdf,jpg,jpeg,png,doc,docx'))
        ))),
        'allowed_mime_types' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SECURE_UPLOAD_ALLOWED_MIME_TYPES', implode(',', [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])))
        ))),
        'scan_enabled' => (bool) env('DOCUMENT_SCAN_ENABLED', false),
        'scan_command' => env('DOCUMENT_SCAN_COMMAND'),
    ],
    'mfa' => [
        'code_length' => (int) env('MFA_CODE_LENGTH', 6),
        'expires_minutes' => (int) env('MFA_EXPIRES_MINUTES', 10),
        'remember_days' => (int) env('MFA_REMEMBER_DAYS', 30),
        'cookie_name' => env('MFA_REMEMBER_COOKIE', 'criserve_mfa_remember'),
        'required_roles' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MFA_REQUIRED_ROLES', 'admin,social_worker,approving_officer,service_provider'))
        ))),
    ],
];

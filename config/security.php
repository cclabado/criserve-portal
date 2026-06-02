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
        'scan_mode' => env('DOCUMENT_SCAN_MODE', 'inline'),
        'scan_command' => env('DOCUMENT_SCAN_COMMAND'),
        'scan_queue' => env('DOCUMENT_SCAN_QUEUE', 'documents'),
    ],
    'workflow_uploads' => [
        'disk' => env('WORKFLOW_UPLOAD_DISK', env('FILESYSTEM_DISK', 'local')),
    ],
    'payout_proofs' => [
        'disk' => env('PAYOUT_PROOF_DISK', env('SECURE_DOCUMENT_DISK', env('FILESYSTEM_DISK', 'local'))),
    ],
    'mfa' => [
        'code_length' => (int) env('MFA_CODE_LENGTH', 6),
        'default_code' => env('MFA_DEFAULT_CODE'),
        'expires_minutes' => (int) env('MFA_EXPIRES_MINUTES', 10),
        'remember_days' => (int) env('MFA_REMEMBER_DAYS', 30),
        'cookie_name' => env('MFA_REMEMBER_COOKIE', 'criserve_mfa_remember'),
        'required_roles' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MFA_REQUIRED_ROLES', 'admin,social_worker,approving_officer,reporting_officer,technical_staff,admin_staff,budget_officer,budget_approver,accounting_officer,accounting_approver,cash_officer,cash_approver,finance_director,service_provider,gl_payment_processor,referral_institution,referral_officer'))
        ))),
    ],
    'audit_logs' => [
        'mode' => env('AUDIT_LOG_MODE', 'after_response'),
        'queue' => env('AUDIT_LOG_QUEUE', 'audit'),
    ],
];

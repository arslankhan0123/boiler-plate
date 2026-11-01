<?php

return [
    // 0 = Sunday … 6 = Saturday. Weekend days are not working days.
    'weekend_days' => [0],

    // Office start time; check-in after start + grace counts as "late".
    'office_start' => '09:00',
    'late_grace_minutes' => 15,

    // A day with less than this many worked minutes is a half day.
    'half_day_minutes' => 240,

    'currency' => 'PKR',

    'employment_types' => [
        'full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract',
        'internship' => 'Internship', 'remote' => 'Remote',
    ],

    'application_statuses' => [
        'new' => ['New', 'primary'], 'shortlisted' => ['Shortlisted', 'info'], 'interview' => ['Interview', 'warning'],
        'offered' => ['Offered', 'warning'], 'hired' => ['Hired', 'success'], 'rejected' => ['Rejected', 'danger'],
    ],

    // Starter roles created in every tenant. Permissions of modules the tenant
    // doesn't have are skipped, and missing ones are added when modules are enabled.
    'default_roles' => [
        'Employee' => ['hrm.self_service', 'projects.view'],
        'HR Manager' => [
            'hrm.self_service', 'hrm.view_employees', 'hrm.manage_employees', 'hrm.manage_org', 'hrm.view_attendance',
            'hrm.manage_attendance', 'hrm.approve_leave', 'hrm.manage_leave', 'hrm.process_payroll',
            'hrm.manage_recruitment', 'hrm.reports', 'projects.view', 'projects.manage_tasks',
        ],
    ],
];

<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'gradingform/passfailrubric:view_grade_history' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ]
    ],
    'gradingform/passfailrubric:view_grade_overrides' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
        ]
    ]
];


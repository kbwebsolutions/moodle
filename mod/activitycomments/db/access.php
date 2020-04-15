<?php

$capabilities = array(
    'mod/activitycomments:addinstance' => array(
            'riskbitmask'  => RISK_SPAM | RISK_PERSONAL | RISK_XSS | RISK_CONFIG,
            'captype'      => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes'   => array(
                'editingteacher' => CAP_ALLOW,
                'manager'          => CAP_ALLOW
            )
    )
);
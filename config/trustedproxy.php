<?php

return [
    // Read through config so proxy trust survives `artisan config:cache`.
    // Empty means no trusted proxies; list only actual ingress IPs/CIDRs.
    'proxies' => array_values(array_filter(array_map(
        'trim', explode(',', (string) env('TRUSTED_PROXIES', ''))
    ))),
];

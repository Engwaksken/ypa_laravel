<?php

return [
    // Additional exact hostnames, for deployments with more than one domain.
    // The APP_URL hostname is always included. Wildcards are not interpreted.
    'hosts' => array_values(array_filter(array_map(
        'trim', explode(',', (string) env('TRUSTED_HOSTS', ''))
    ))),
];

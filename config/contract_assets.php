<?php

return [
    // Absolute path to the old application's uploads directory, if not migrated.
    'legacy_uploads_path' => env('LEGACY_UPLOADS_PATH'),
    // Old URL hostnames whose paths may be mapped to local files. Never fetched remotely.
    'legacy_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('LEGACY_ASSET_HOSTS', ''))))),
];

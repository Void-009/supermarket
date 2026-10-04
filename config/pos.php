<?php

return [
    /*
    |--------------------------------------------------------------------------
    | POS Export Path
    |--------------------------------------------------------------------------
    |
    | Path to the POS Excel export file. Uses base_path() so the value is
    | always an absolute string — safe with config:cache and realpath() issues.
    |
    */
    'export_path' => base_path(env('POS_EXPORT_PATH', 'items_export.xlsx')),
];

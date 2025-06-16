<?php

return [
    //
    'use_scout' => env('SCOUT_ENABLED', false),
    //
    'use_full_text_query' => env('FULL_TEXT_SEARCH', true),
    'use_full_text_relevancy' => true,
    //
    'full_text_mode' => 'boolean',
    //
    'search_in_descriptions' => false,

    'search_disable_metadata' => env('SEARCH_DISABLE_METADATA', 'search_disabled'),
];

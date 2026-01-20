<?php

return [
    'user_model' => \App\Models\User::class,
    'results_per_page' => 15,
    'min_search_length' => 2,
    'rate_limit' => '30,1',
    'route_prefix' => 'api',
    'route_middleware' => ['api', 'auth:sanctum'],
];

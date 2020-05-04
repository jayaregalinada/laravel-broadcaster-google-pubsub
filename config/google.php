<?php

return [
    'driver' => 'google',
    'projectId' => env('GOOGLE_PUBSUB_PROJECT_ID', env('GOOGLE_PROJECT_ID', env('GCLOUD_PROJECT'))),
    'keyFilePath' => env('GOOGLE_PUBSUB_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS'))
];

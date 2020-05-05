<?php

return [
    'driver' => 'google',
    'project_id' => env('GOOGLE_PUBSUB_BROADCASTER_PROJECT_ID', env('GOOGLE_PROJECT_ID', env('GCLOUD_PROJECT'))),
    'credentials_path' => env('GOOGLE_PUBSUB_BROADCASTER_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS')),
    'auto_create_topic' => env('GOOGLE_PUBSUB_BROADCASTER_AUTO_CREATE_TOPIC', false),
    'payload_class' => null,
    'override_config' => [],
];

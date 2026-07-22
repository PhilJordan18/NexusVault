<?php

return [
    'admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => mb_strtolower(trim($email)),
        explode(',', env('NEXUSVAULT_ADMIN_EMAILS', ''))
    ))),
];

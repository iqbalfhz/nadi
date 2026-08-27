<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Destination Hosts
    |--------------------------------------------------------------------------
    |
    | A short link makes an arbitrary destination wear this office's own
    | domain, which is exactly what makes it useful for sharing — and exactly
    | what would make it useful for phishing a colleague. Links pointing at
    | one of these hosts (plus the app's own) redirect straight through;
    | everything else shows the destination first and asks for confirmation,
    | so nobody gets walked somewhere unexpected by a link that looked
    | internal.
    |
    | Matching includes subdomains, so "google.com" also covers
    | "drive.google.com" — but only on a dot boundary, so a lookalike like
    | "google.com.evil.test" is NOT trusted. Keep entries specific:
    | a whole TLD here would hand trust to anyone who can register in it.
    |
    */

    'trusted_hosts' => [
        'google.com',
        'forms.gle',
        'goo.gle',
        'office.com',
        'sharepoint.com',
    ],

];

<?php

return [
    'services' => [
        [
            'id' => 'kickbox.io',
            'name' => 'Kickbox',
            'uri' => 'https://api.kickbox.io/v2/verify?email={EMAIL}&apikey={API_KEY}',
            'request_type' => 'GET',
            'fields' => [ 'api_key' ],
            'result_xpath' => '$.result',
            'result_map' => [ 'deliverable' => 'deliverable', 'undeliverable' => 'undeliverable', 'risky' => 'risky', 'unknown' => 'unknown' ]
        ], [
            'id' => 'thechecker.co',
            'name' => 'TheChecker',
            'uri' => 'https://api.thechecker.co/v1/verify?email={EMAIL}&api_key={API_KEY}',
            'request_type' => 'GET',
            'fields' => [ 'api_key' ],
            'result_xpath' => '$.result',
            'result_map' => [ 'deliverable' => 'deliverable', 'undeliverable' => 'undeliverable', 'risky' => 'risky', 'unknown' => 'unknown' ]
        ], [
            'id' => 'verify-email.org',
            'name' => 'verify-email.org',
            'uri' => 'http://api.verify-email.org/api.php?usr={USERNAME}&pwd={PASSWORD}&check={EMAIL}',
            'request_type' => 'GET',
            'fields' => [ 'username', 'password' ],
            'result_xpath' => '$.authentication_status',
            'result_map' => [ '1' => 'deliverable', '0' => 'undeliverable' ]
        ], [
            'id' => 'proofy.io',
            'name' => 'proofy.io',
            'uri' => 'https://api.proofy.io/check?aid={USERNAME}&key={API_KEY}&mail={EMAIL}',
            'request_type' => 'GET',
            'fields' => [ 'username', 'api_key' ],
            'result_xpath' => '$.mail.statusName',
            'result_map' => [ 'deliverable' => 'deliverable', 'undeliverable' => 'undeliverable', 'risky' => 'risky' ]
        ], [
            'id' => 'everifier.org',
            'name' => 'everifier.org',
            'uri' => 'https://api.everifier.org/v1/{API_KEY}/verify/{EMAIL}',
            'request_type' => 'GET',
            'fields' => [ 'api_key' ],
            'result_xpath' => '$.*.status',
            'result_map' => [ '1' => 'deliverable', '0' => 'undeliverable', '-1' => 'risky' ]
        ]
    ]
];

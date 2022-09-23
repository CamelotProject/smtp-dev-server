<?php

declare(strict_types=1);

$packet = [
    'GET / HTTP/1.1',
    'Host: 127.0.0.1:2580',
    'Connection: keep-alive',
    'Pragma: no-cache',
    'Cache-Control: no-cache',
    'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/jxl,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
    'Accept-Encoding: gzip, deflate, br',
    'Accept-Language: en-GB,en;q=0.9,de-DE;q=0.8,de;q=0.7,nl-NL;q=0.6,nl;q=0.5,es-US;q=0.4,es;q=0.3,pt-PT;q=0.2,pt;q=0.1,en-US;q=0.1',
    'Cookie: PHPSESSID=abc123',
    '',
    null,
];

return implode("\r\n", $packet);

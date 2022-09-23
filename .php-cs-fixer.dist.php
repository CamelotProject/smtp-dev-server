<?php

declare(strict_types=1);

return Camelot\CsFixer\Config::create()
    ->addRules(
        Camelot\CsFixer\Rules::create()
            ->risky()
            ->php81()
            ->phpUnit84()
    )
    ->addRules([
        '@PhpCsFixer:risky' => true,
    ])
    ->in('src')
    ->in('tests')
;

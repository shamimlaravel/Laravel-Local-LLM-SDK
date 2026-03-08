<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Facade;
use Pest\Illuminate\Support\Facades\AliasLoaded;
use Tests\TestCase;

AliasLoaded::setFacadeApplication(app());

Facade::setFacadeApplication(app());

return TestCase::configure()
    ->runsIn('./tests')
    ->useRealTime();

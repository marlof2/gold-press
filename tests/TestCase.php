<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $compiledViews = sys_get_temp_dir().'/gold-press-tests-'.getmypid().'/views';
        $_SERVER['VIEW_COMPILED_PATH'] = $compiledViews;
        $_ENV['VIEW_COMPILED_PATH'] = $compiledViews;

        return parent::createApplication();
    }
}

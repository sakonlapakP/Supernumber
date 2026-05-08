<?php

namespace Tests\Feature;

use Tests\TestCase;

class DebugDatabaseTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    public function test_check_database_connection()
    {
        $this->assertEquals('sqlite', config('database.default'));
    }
}

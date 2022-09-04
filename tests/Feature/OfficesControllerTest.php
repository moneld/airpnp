<?php

namespace Tests\Feature;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OfficesControllerTest extends TestCase
{
    /**
     * @test
     */
    public function getAllOffices()
    {
        $offices = Office::factory(3)->create();

        $response = $this->get('/api/offices');

        $response->assertOk();

        $response->assertJsonCount(15,'data');
    }

}

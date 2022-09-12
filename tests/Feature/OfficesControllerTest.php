<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OfficesControllerTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @test
     */
    public function itListsAllOfficesInPaginatedWay()
    {
       Office::factory(3)->create();

        $response = $this->get('/api/offices');

        $response->assertOk();

        $response->assertJsonCount(3,'data');

        $this->assertNotNull($response->json('meta'));
        $this->assertNotNull($response->json('links'));
    }

    /**
    *@test
     */
    public function itOnlyListsOffciesThatAreNotHiddenAndApproved()
    {
        Office::factory(3)->create();
        Office::factory()->create(['hidden' => true]);
        Office::factory()->create(['approval_status' => Office::APPROVAL_PENDING]);

        $response = $this->get('/api/offices');

        $response->assertOk();

        $response->assertJsonCount(3,'data');
    }

    /**
     *@test
     */
    public function itFilterByHostId()
    {
        Office::factory(3)->create();
       $host = User::factory()->create();

       Office::factory()->for($host)->create();

        $response = $this->get('/api/offices?host_id='.$host->id);

        $response->assertOk();

        $response->assertJsonCount(1,'data');
    }

    /**
     *@test
     */
    public function itFilterByUserId()
    {

        $user = User::factory()->create();

        $office = Office::factory()->create();

        Reservation::factory()->for($office)->for($user)->create();

        $response = $this->get('/api/offices?user_id='.$user->id);

        $response->assertOk();

        $response->assertJsonCount(1,'data');
    }


    /**
     * @test
     */
    public function itIncludeImagesTagsUser()
    {

       $tag = Tag::factory()->create();
       $user = User::factory()->create();
       $office = Office::factory()->for($user)->create();

        $office->tags()->attach($tag);
        $office->images()->create(['path'=>'image.jpg']);

        $response = $this->get('/api/offices');
        $response->assertOk();

        $this->assertIsArray($response->json('data')[0]['tags']);
        $this->assertIsArray($response->json('data')[0]['images']);
        $this->assertEquals($response->json('data')[0]['user']['id'],$user->id);

    }


    /**
     * @test
     */
    public function itReturnsTheNumberOfActiveReservations()
    {
        $office = Office::factory()->create();
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_CANCELLED]);

        $response = $this->get('/api/offices');
        $response->assertOk();
        $this->assertEquals($response->json('data')[0]['reservations_count'],1);

    }

    /**
     * @test
     */
    public function itOrdersByDistanceWhenCoordinatesAreProvided ()
    {
        $this->withoutExceptionHandling();
        /*
         * Cotonou
         * lat = 7.934327726169804
         * lng = 1.975135952890811
         * */
        $office = Office::factory()->create(
            [
                'lat' => '6.370246273189285',
                'lng' => '2.3930874928228523',
                'title' => 'Cotonou Office'
            ]
        );

        $office2 = Office::factory()->create(
            [
                'lat' => '9.329142401738267',
                'lng' => '2.633971881784387',
                'title' => 'Parakou Office'
            ]
        );

        $response = $this->get('/api/offices?lat=7.934327726169804&lng=1.975135952890811');
        $response->assertOk();
        $this->assertEquals('Parakou Office',$response->json('data')[0]['title']);
        $this->assertEquals('Cotonou Office',$response->json('data')[1]['title']);
    }

    /**
     * @test
    */
    public function isShowTheOffice()
    {
        $tag = Tag::factory()->create();
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $office->tags()->attach($tag);
        $office->images()->create(['path'=>'image.jpg']);

        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_CANCELLED]);

        $response = $this->get('/api/offices/'.$office->id);

        $this->assertEquals($response->json('data')['reservations_count'],1);
        $this->assertIsArray($response->json('data')['tags']);
        $this->assertIsArray($response->json('data')['images']);
        $this->assertEquals($response->json('data')['user']['id'],$user->id);
    }
}

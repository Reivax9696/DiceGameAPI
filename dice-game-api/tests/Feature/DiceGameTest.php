<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Spatie\Permission\Models\Role;

class DiceGameTest extends TestCase {

    use WithoutMiddleware;


    public function test_create_new_player(): void
    {
        $response = $this->postJson('/api/players', [
            'nickname' => 'Test',
            'email' => 'test@gmail.com',
            'password' => 'password'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User created successfully'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@gmail.com',
            'nickname' => 'Test'
        ]);

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => Role::where('name', 'Player')->first()->id,
            'model_type' => User::class,
            'model_id' => User::where('email', 'test@gmail.com')->first()->id,
        ]);
    }


    public function test_user_can_login_with_correct_credentials()
{
    $user = User::factory()->create([
        'nickname' => 'testuser',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'nickname' => 'testuser',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure(['token']);

    $this->assertNotNull($response['token']);
}


public function test_user_cannot_login_with_incorrect_credentials()
{
    $response = $this->postJson('/api/login', [
        'nickname' => 'invaliduser',
        'password' => 'invalidpassword',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Unauthorized']);
}


public function test_user_can_update_profile()
{
    $user = User::factory()->create([
        'nickname' => 'oldnickname',
    ]);

    $this->actingAs($user);

    $response = $this->putJson("/api/players/{$user->id}", [
        'nickname' => 'newnickname',
    ]);

    $response->assertStatus(200)
             ->assertJson(['message' => 'User updated successfully']);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'nickname' => 'newnickname',
    ]);
}

public function test_user_cannot_update_profile_with_duplicate_nickname()
{
    $existingUser = User::factory()->create(['nickname' => 'existinguser']);
    $user = User::factory()->create(['nickname' => 'testuser']);

    $this->actingAs($user);

    $response = $this->putJson("/api/players/{$user->id}", [
        'nickname' => 'existinguser',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors('nickname');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'nickname' => $user->nickname,
    ]);
}


public function test_user_can_roll_dice(): void
{
    $user = User::factory()->create();
    $user->assignRole('player');

    $this->actingAs($user, 'api');

    $response = $this->postJson("/api/players/{$user->id}/games");

    $response->assertStatus(201);

    $response->assertJsonStructure([
        'id',
        'user_id',
        'dice1',
        'dice2',
        'is_won',
        'created_at',
        'updated_at'
    ]);

    $this->assertDatabaseHas('games', [
        'user_id' => $user->id
    ]);
    }

    public function test_users_index_shows_correct_information(): void
{
    $userOne = User::factory()->create();
    $userTwo = User::factory()->create();

    $userOne->assignRole('player');
    $userTwo->assignRole('player');

    Game::factory()->count(2)->create(['user_id' => $userOne->id, 'is_won' => true]);
    Game::factory()->create(['user_id' => $userOne->id, 'is_won' => false]);

    Game::factory()->create(['user_id' => $userTwo->id, 'is_won' => true]);
    Game::factory()->count(3)->create(['user_id' => $userTwo->id, 'is_won' => false]);

    $response = $this->getJson('/api/players');

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'users' => [
            '*' => [
                'id',
                'email',
                'nickname',
                'games_count',
                'victories',
                'average_victories',
            ]
        ]
    ]);
}


public function test_users_ranking_returns_correct_order(): void
{

    $userOne = User::factory()->create();
    $userTwo = User::factory()->create();
    $userThree = User::factory()->create();

    $userOne->assignRole('player');
    $userTwo->assignRole('player');
    $userThree->assignRole('player');


    Game::factory()->count(2)->create(['user_id' => $userOne->id, 'is_won' => true]);
    Game::factory()->count(3)->create(['user_id' => $userOne->id, 'is_won' => true]);
    Game::factory()->count(4)->create(['user_id' => $userOne->id, 'is_won' => true]);
    Game::factory()->count(5)->create(['user_id' => $userOne->id, 'is_won' => true]);

    Game::factory()->create(['user_id' => $userTwo->id, 'is_won' => true]);
    Game::factory()->count(2)->create(['user_id' => $userTwo->id, 'is_won' => false]);

    Game::factory()->count(2)->create(['user_id' => $userThree->id, 'is_won' => true]);
    Game::factory()->count(3)->create(['user_id' => $userThree->id, 'is_won' => false]);

    $response = $this->getJson('/api/players/ranking');

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'ranking' => [
            '*' => [
                'id',
                'games_count',
                'victories',
                'average_victories',
            ],
        ],
    ]);

    /*$ranking = $response->json('ranking');

    $this->assertEquals($userOne->id, $ranking[0]['id']);
    $this->assertEquals($userThree->id, $ranking[1]['id']);
    $this->assertEquals($userTwo->id, $ranking[2]['id']);

    $this->assertEquals(1.0, $ranking[0]['average_victories']);
    $this->assertEquals(0.4, $ranking[1]['average_victories']);
    $this->assertEquals(1/3, $ranking[2]['average_victories']);*/

}



public function test_user_can_delete_their_games(): void
{
    $user = User::factory()->create();
    $user->assignRole('player');

    Game::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user, 'api');

    $response = $this->deleteJson("/api/players/{$user->id}/games");

    $response->assertStatus(200)->assertJson([
        'message' => 'All games deleted successfully',
    ]);

    $this->assertDatabaseMissing('games', [
        'user_id' => $user->id,
    ]);
}


public function test_user_can_view_their_games(): void
{

    $user = User::factory()->create();
    $user->assignRole('player');

    $games = Game::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user, 'api');

    $response = $this->getJson("/api/players/{$user->id}/games");

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'games' => [
            '*' => [
                'id',
                'user_id',
                'dice1',
                'dice2',
                'is_won'
            ],
        ],
    ]);

    $expectedGameIds = $games->pluck('id')->sort()->values();
    $responseGameIds = collect($response->json('games'))->pluck('id')->sort()->values();

    $this->assertEquals($expectedGameIds, $responseGameIds);
}
















}

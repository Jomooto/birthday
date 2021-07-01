<?php

namespace Tests\Feature;

use App\User;
use Carbon\Carbon;
use http\Url;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use App\Contact;

class ContactsTest extends TestCase
{


    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
    }

    public function test_an_unauthenticated_user_should_be_redirected_to_login(){

        $response = $this->post('/api/contacts',
            array_merge($this->data(), ['api_token' => '']));

        $response->assertRedirect('/login');
        $this->assertCount(0, Contact::all());

    }

    public function test_an_authenticated_user_can_add_a_contact(){

        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

         $response = $this->post('/api/contacts', $this->data());

         $contact =  Contact::first();

//         dd(json_decode($response->getContent()));

         $this->assertEquals('Test Name', $contact->name);
         $this->assertEquals('email@email.com', $contact->email);
         $this->assertEquals('05/04/1990', $contact->birthday->format('m/d/Y'));
         $this->assertEquals('ABC Television', $contact->company);
//         $response->assertStatus(201);
         $response->assertStatus(Response::HTTP_CREATED);
         $response->assertJson([
             'data' => [
                 'contact_id' => $contact->id
             ],
             'links' => [
                 'self' => $contact->path()
             ]
         ]);
    }

    public function test_fields_are_required(){
        collect(['name', 'email', 'birthday', 'company'])
            ->each( function ($field){
                $response = $this->post('/api/contacts',
                    array_merge($this->data(), [
                        $field => '',
                    ]));

                $response->assertSessionHasErrors($field);
                $this->assertCount(0, Contact::all());
            });
    }

    public function test_email_must_be_a_valid_email(){
        $response = $this->post('/api/contacts',
            array_merge($this->data(), [
                'email' => 'NOT AN EMAIL',
            ]));

        $response->assertSessionHasErrors('email');
        $this->assertCount(0, Contact::all());

    }

    public function test_birthdays_are_properly_store(){
        $response = $this->post('/api/contacts', $this->data());

        $Contact = Contact::first();
        $this->assertCount(1, Contact::all());
        $this->assertInstanceOf(Carbon::class, $Contact->birthday);
        $this->assertEquals('05/04/1990', $Contact->birthday->format('m/d/Y'));
    }

    public function test_a_contact_can_be_retrieved(){

        $contact = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $response = $this->get('/api/contacts/' . $contact->id . '?api_token=' . $this->user->api_token);

//        dd(json_decode($response->getContent()));

        $response->assertJson([
            'data' => [
                'contact_id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'birthday' => $contact->birthday->format('m/d/Y'),
                'company' => $contact->company,
                'last_update' => $contact->updated_at->diffForHumans(),
                ],
        ]);
    }

    public function test_only_the_users_contacts_can_be_retrieved(){

        $contact = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $user1 = factory(User::class)->create();

        $response = $this->get('/api/contacts/' . $contact->id . '?api_token=' . $user1->api_token);

        $response->assertStatus(403);

    }

    public function test_a_contact_can_be_patched(){

        $this->withoutExceptionHandling();

        $contact = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $response = $this->patch('/api/contacts/' . $contact->id, $this->data());

        $contact = $contact->fresh();

        $this->assertEquals('Test Name', $contact->name);
        $this->assertEquals('email@email.com', $contact->email);
        $this->assertEquals('05/04/1990', $contact->birthday->format('m/d/Y'));
        $this->assertEquals('ABC Television', $contact->company);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                'contact_id' => $contact->id,
            ],
            'links' => [
                'self' => $contact->path(),
            ]
        ]);

    }

    public function test_only_the_owner_of_the_contact_can_patch_the_contact(){

        $contact = factory(Contact::class)->create();

        $anotherUser = factory(User::class)->create();

        $response = $this->patch('/api/contacts/' . $contact->id,
        array_merge($this->data(), ['api_token' => $anotherUser->api_token]));

        $response->assertStatus(403);

    }

    public function test_a_contact_can_be_deleted(){

        $this->withoutExceptionHandling();

        $contact = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $response = $this->delete('/api/contacts/' . $contact->id,
            ['api_token' => $this->user->api_token]);

        $this->assertCount(0, Contact::all());
        $response->assertStatus(Response::HTTP_NO_CONTENT);

    }

    public function test_only_the_owner_can_delete_the_contact(){
        $contact = factory(Contact::class)->create();
        $anotherUser = factory(User::class)->create();

        $response = $this->delete('/api/contacts/' . $contact->id,
            ['api_token' => $this->user->api_token]);

        $response->assertStatus(403);
    }

    public function test_a_list_of_contacts_can_be_fetched_by_an_authenticated_user(){

        $user = factory(User::class)->create();
        $user1 = factory(User::class)->create();

        $contact = factory(Contact::class)->create([
            'user_id' => $user->id
        ]);

        $response = $this->get('/api/contacts?api_token='.$user->api_token);

//        dd(json_decode($response->getContent()));

        $response->assertJsonCount(1)
        ->assertJson([
                'data' => [
                    [
                        'data' => [
                            'contact_id' => $contact->id
                        ]
                    ]
                ]
            ]);
    }


    private function data(){
        return [
            'name' => 'Test Name',
            'email' => 'email@email.com',
            'birthday' => '05/04/1990',
            'company' => 'ABC Television',
            'api_token' => $this->user->api_token
        ];
    }
}

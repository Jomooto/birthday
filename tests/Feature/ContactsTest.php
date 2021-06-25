<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Contact;

class ContactsTest extends TestCase
{


    use RefreshDatabase;

    public function test_a_contact_can_be_added (){

         $this->post('/api/contacts', $this->data());

         $contact =  Contact::first();

         $this->assertEquals('Test Name', $contact->name);
         $this->assertEquals('email@email.com', $contact->email);
         $this->assertEquals('05/04/1990', $contact->birthday);
         $this->assertEquals('ABC Television', $contact->company);
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

        $this->assertCount(1, Contact::all());
        $this->assertInstanceOf(Carbon::class, Contact::first()->birthday);
        $this->assertEquals('05-04-1990', Contact::first()->birthday->format('m-d-Y'));
    }

    public function test_a_contact_can_be_retrieve(){

        $contact = factory(Contact::class)->create();

        $response = $this->get('/api/contacts/' . $contact->id);

        $response->assertJson([
            'name' => $contact->name,
            'email' => $contact->email,
            'birthday' => $contact->birthday->format('Y-m-d\TH:i:s.\0\0\0\0\0\0\Z'),
            'company' => $contact->company,
        ]);
    }

    public function test_a_contact_can_be_patched(){

        $this->withoutExceptionHandling();

        $contact = factory(Contact::class)->create();

        $response = $this->patch('/api/contacts/' . $contact->id, $this->data());

        $contact = $contact->fresh();

        $this->assertEquals('Test Name', $contact->name);
        $this->assertEquals('email@email.com', $contact->email);
        $this->assertEquals('05/04/1990', $contact->birthday->format('m/d/Y'));
        $this->assertEquals('ABC Television', $contact->company);

    }

    public function test_a_contact_can_be_deleted(){

        $this->withoutExceptionHandling();

        $contact = factory(Contact::class)->create();

        $response = $this->delete('/api/contacts/' . $contact->id);

        $this->assertCount(0, Contact::all());

    }


    private function data(){
        return [
            'name' => 'Test Name',
            'email' => 'email@email.com',
            'birthday' => '05/04/1990',
            'company' => 'ABC Television'
        ];
    }
}

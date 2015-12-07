<?php

use App\BookingStrategy;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BookingStrategyUnitTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @covers \App\BookingStrategy::generateAppointment
     * @covers \App\BookingDateslotStrategy::generateAppointment
     * @test
     */
    public function it_generates_a_dateslot_appointment()
    {
        $user = $this->makeUser();
        $user->save();

        $contact = $this->makeContact($user);
        $contact->save();

        $business = $this->makeBusiness($user, ['strategy' => 'dateslot']);
        $business->save();

        $service = factory(Service::class)->make();
        $business->services()->save($service);

        $bookingStrategy = new BookingStrategy($business->strategy);

        $dateTime = Carbon::now()->addDays(5);

        $appointment = $bookingStrategy->generateAppointment(
            $user,
            $business,
            $contact,
            $service,
            $dateTime,
            'test comments'
        );

        $this->assertInstanceOf(\App\Models\Appointment::class, $appointment);
        $this->assertEquals($appointment->issuer->id, $user->id);
        $this->assertEquals($appointment->contact->name, $contact->name);
        $this->assertEquals($appointment->service->name, $service->name);
        $this->assertEquals($appointment->date, $dateTime->toDateString());
        $this->assertEquals($appointment->comments, 'test comments');
        $this->assertEquals(strlen($appointment->hash), 32);
    }

    /////////////
    // HELPERS //
    /////////////

    private function makeUser()
    {
        $user = factory(User::class)->make();
        $user->email = 'guest@example.org';
        $user->password = bcrypt('demoguest');

        return $user;
    }

    private function makeContact(User $user = null)
    {
        $contact = factory(Contact::class)->make();
        if ($user) {
            $contact->user()->associate($user);
        }

        return $contact;
    }

    private function makeBusiness(User $owner, $overrides = [])
    {
        $business = factory(Business::class)->make($overrides);
        $business->save();
        $business->owners()->attach($owner);

        return $business;
    }
}
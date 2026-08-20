<?php

namespace Database\Factories;

use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormSubmission>
 */
class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'form_type' => FormSubmission::TYPE_CONTACT,
            'name' => "{$first} {$last}",
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->numerify('+1##########'),
            'company' => null,
            'country' => fake()->country(),
            'subject' => 'General Inquiry',
            'message' => fake()->paragraph(),
            'payload' => [
                'first_name' => $first,
                'last_name' => $last,
                'referral' => null,
            ],
            'status' => FormSubmission::STATUS_NEW,
            'admin_notes' => null,
            'submitted_at' => now(),
        ];
    }

    public function investor(): static
    {
        return $this->state(fn () => [
            'form_type' => FormSubmission::TYPE_INVESTOR,
            'subject' => 'Investor enquiry',
            'payload' => [
                'budget' => '$250k – $500k',
                'strategy' => 'Cash-flow rental',
                'timeline' => '1–3 months',
            ],
        ]);
    }

    public function mortgage(): static
    {
        return $this->state(fn () => [
            'form_type' => FormSubmission::TYPE_MORTGAGE,
            'subject' => 'Mortgage Programme',
        ]);
    }
}

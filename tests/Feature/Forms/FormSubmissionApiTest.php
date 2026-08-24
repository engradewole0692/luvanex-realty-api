<?php

namespace Tests\Feature\Forms;

use App\Mail\FormSubmissionAdminNotification;
use App\Mail\FormSubmissionVisitorConfirmation;
use App\Models\BlogPost;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormSubmissionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('Origin', 'http://localhost:8080');
        $this->withHeader('Referer', 'http://localhost:8080/contact');
        Mail::fake();

        if ($this->name() !== 'test_form_rate_limiting_rejects_excessive_submissions') {
            $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ada',
            'last_name' => 'Okeke',
            'email' => 'ada@example.com',
            'phone' => '+15551234567',
            'country' => 'Nigeria',
            'message' => 'I would like to learn more about Atlanta investment opportunities.',
            'topic' => 'Investing in U.S. Property',
            'referral' => 'Google',
            'company_website' => '',
        ], $overrides);
    }

    public function test_contact_form_submission_is_stored_and_emails_are_queued(): void
    {
        $response = $this->postJson('/api/v1/forms/contact', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('message', 'Thank you. Your enquiry has been received.')
            ->assertJsonMissingPath('mail_password')
            ->assertJsonMissingPath('MAIL_PASSWORD');

        $this->assertDatabaseHas('form_submissions', [
            'form_type' => FormSubmission::TYPE_CONTACT,
            'email' => 'ada@example.com',
            'name' => 'Ada Okeke',
            'status' => FormSubmission::STATUS_NEW,
            'subject' => 'Investing in U.S. Property',
        ]);

        Mail::assertQueued(FormSubmissionAdminNotification::class);
        Mail::assertQueued(FormSubmissionVisitorConfirmation::class);
    }

    public function test_investor_and_mortgage_forms_are_accepted(): void
    {
        $this->postJson('/api/v1/forms/investor', $this->validPayload([
            'topic' => null,
            'budget' => '$250k – $500k',
            'strategy' => 'Cash-flow rental',
            'timeline' => '1–3 months',
        ]))->assertCreated();

        $this->postJson('/api/v1/forms/mortgage', $this->validPayload([
            'topic' => 'Mortgage Programme',
        ]))->assertCreated();

        $this->assertDatabaseHas('form_submissions', ['form_type' => FormSubmission::TYPE_INVESTOR]);
        $this->assertDatabaseHas('form_submissions', ['form_type' => FormSubmission::TYPE_MORTGAGE]);
    }

    public function test_validation_rejects_invalid_email_and_missing_fields(): void
    {
        $this->postJson('/api/v1/forms/contact', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'country', 'message', 'topic']);

        $this->postJson('/api/v1/forms/contact', $this->validPayload([
            'email' => 'not-an-email',
            'message' => str_repeat('x', 5000),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'message']);
    }

    public function test_honeypot_returns_success_without_persisting(): void
    {
        $this->postJson('/api/v1/forms/contact', $this->validPayload([
            'company_website' => 'https://spam.example',
        ]))->assertOk();

        $this->assertDatabaseCount('form_submissions', 0);
        Mail::assertNothingQueued();
    }

    public function test_unknown_form_type_is_not_routable(): void
    {
        $this->postJson('/api/v1/forms/newsletter', $this->validPayload())
            ->assertNotFound();
    }

    public function test_form_rate_limiting_rejects_excessive_submissions(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/forms/contact', $this->validPayload([
                'email' => "user{$i}@example.com",
            ]))->assertCreated();
        }

        $this->postJson('/api/v1/forms/contact', $this->validPayload([
            'email' => 'overflow@example.com',
        ]))->assertStatus(429);
    }

    public function test_unauthenticated_admin_access_is_rejected(): void
    {
        $submission = FormSubmission::factory()->create();

        $this->getJson('/api/v1/admin/forms/submissions')->assertUnauthorized();
        $this->getJson('/api/v1/admin/forms/submissions/'.$submission->id)->assertUnauthorized();
        $this->patchJson('/api/v1/admin/forms/submissions/'.$submission->id, [
            'status' => FormSubmission::STATUS_READ,
        ])->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_submissions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => User::ROLE_USER]);
        $submission = FormSubmission::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/admin/forms/submissions')
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson('/api/v1/admin/forms/submissions/'.$submission->id)
            ->assertForbidden();
    }

    public function test_admin_can_list_filter_view_and_update_submissions(): void
    {
        $admin = User::factory()->admin()->create();
        $contact = FormSubmission::factory()->create([
            'form_type' => FormSubmission::TYPE_CONTACT,
            'name' => 'Contact Person',
            'email' => 'contact@example.com',
            'status' => FormSubmission::STATUS_NEW,
        ]);
        FormSubmission::factory()->investor()->create([
            'name' => 'Investor Person',
            'email' => 'investor@example.com',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/forms/submissions?form_type=contact&status=new&search=contact')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'contact@example.com');

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/forms/submissions/'.$contact->id)
            ->assertOk()
            ->assertJsonPath('data.id', $contact->id);

        $this->actingAs($admin)
            ->patchJson('/api/v1/admin/forms/submissions/'.$contact->id, [
                'status' => FormSubmission::STATUS_REPLIED,
                'admin_notes' => 'Called back on Monday.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', FormSubmission::STATUS_REPLIED)
            ->assertJsonPath('data.admin_notes', 'Called back on Monday.');

        $this->actingAs($admin)
            ->patchJson('/api/v1/admin/forms/submissions/'.$contact->id, [
                'status' => FormSubmission::STATUS_ARCHIVED,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', FormSubmission::STATUS_ARCHIVED);
    }

    public function test_dashboard_stats_require_admin_and_return_counts(): void
    {
        $admin = User::factory()->admin()->create();
        FormSubmission::factory()->count(2)->create(['status' => FormSubmission::STATUS_NEW]);
        FormSubmission::factory()->count(3)->create(['status' => FormSubmission::STATUS_ARCHIVED]);
        BlogPost::factory()->published()->count(3)->create();
        BlogPost::factory()->count(1)->create(['status' => BlogPost::STATUS_DRAFT]);

        $this->getJson('/api/v1/admin/dashboard/stats')->assertUnauthorized();

        $member = User::factory()->create([
            'is_admin' => false,
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($member)
            ->getJson('/api/v1/admin/dashboard/stats')
            ->assertForbidden();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.new_form_submissions', 2)
            ->assertJsonPath('data.total_form_submissions', 5)
            ->assertJsonPath('data.total_blog_posts', 4)
            ->assertJsonPath('data.published_posts', 3)
            ->assertJsonPath('data.draft_posts', 1)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.database')
            ->assertJsonMissingPath('data.connection');
    }
}

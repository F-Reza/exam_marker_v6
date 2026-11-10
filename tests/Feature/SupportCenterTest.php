<?php

namespace Tests\Feature;

use App\Models\{SupportTicket, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_every_customer_role_can_create_and_find_its_own_ticket_while_admin_sees_them_all(): void
    {
        $admin = $this->account('admin@example.com');
        $created = [];
        foreach ([
            'free.teacher@example.com', 'mode1@example.com', 'mode2@example.com',
            'mode3@example.com', 'mode3.teacher@example.com', 'student@example.com',
        ] as $email) {
            $user = $this->account($email);
            $subject = 'Support request from account '.$user->id;
            $this->actingAs($user)->get(route('help.index'))->assertOk();
            $this->post(route('help.store'), [
                'subject' => $subject, 'category' => 'general', 'priority' => 'normal',
                'message' => 'Please help me understand my uploaded paper.',
                'user_id' => $admin->id, 'status' => 'closed', 'admin_reply' => 'Forged reply',
            ])->assertRedirect()->assertSessionHasNoErrors();

            $ticket = SupportTicket::where('subject', $subject)->firstOrFail();
            $this->assertSame($user->id, $ticket->user_id);
            $this->assertSame('open', $ticket->status);
            $this->assertNull($ticket->admin_reply);
            $this->get(route('help.show', $ticket))->assertOk()->assertSee($subject);
            $created[$email] = $ticket;
        }

        foreach ($created as $email => $ticket) {
            $response = $this->actingAs($this->account($email))->get(route('help.index'))->assertOk();
            $response->assertSee($ticket->subject);
            foreach ($created as $other) {
                if ($other->id !== $ticket->id) $response->assertDontSee($other->subject);
            }
        }
        $response = $this->actingAs($admin)->get(route('admin.support.index'))->assertOk();
        foreach ($created as $ticket) $response->assertSee($ticket->subject);
        $response->assertViewHas('tickets', fn($tickets) => $tickets->total() === 6);
    }

    public function test_ticket_detail_replies_and_status_changes_are_private_even_inside_the_same_coaching_account(): void
    {
        $coach = $this->account('mode3@example.com');
        $teacher = $this->account('mode3.teacher@example.com');
        $coachTicket = $this->ticket($coach, ['subject' => 'Private coaching account concern']);
        $teacherTicket = $this->ticket($teacher, ['subject' => 'Private staff account concern']);

        foreach ([[$coach, $teacherTicket], [$teacher, $coachTicket]] as [$intruder, $otherTicket]) {
            $this->actingAs($intruder)->get(route('help.show', $otherTicket))->assertForbidden();
            $this->post(route('help.reply', $otherTicket), ['message' => 'Unauthorized follow-up'])->assertForbidden();
            $this->patch(route('help.status', $otherTicket), ['status' => 'closed'])->assertForbidden();
            $this->get(route('admin.support.index'))->assertForbidden();
            $this->get(route('admin.support.show', $otherTicket))->assertForbidden();
            $this->put(route('admin.support.reply', $otherTicket), ['message' => 'Unauthorized support reply'])->assertForbidden();
            $this->assertSame('open', $otherTicket->fresh()->status);
            $this->assertSame(0, $otherTicket->messages()->count());
        }
    }

    public function test_admin_and_customer_can_have_a_conversation_without_overwriting_original_messages(): void
    {
        $owner = $this->account('free.teacher@example.com');
        $admin = $this->account('admin@example.com');
        $ticket = $this->ticket($owner);

        $this->actingAs($admin)->get(route('admin.support.show', $ticket))->assertOk()->assertSee($ticket->message);
        $this->put(route('admin.support.reply', $ticket), ['message' => 'Please try selecting the paper again.'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('answered', $ticket->fresh()->status);
        $this->assertSame('Please try selecting the paper again.', $ticket->fresh()->admin_reply);

        $this->actingAs($owner)->get(route('help.show', $ticket))->assertOk()
            ->assertSee($ticket->message)->assertSee('Please try selecting the paper again.');
        $this->post(route('help.reply', $ticket), [
            'message' => 'I tried again and still need help with the file.',
            'user_id' => $admin->id, 'is_admin' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('open', $ticket->fresh()->status);

        $this->actingAs($admin)->put(route('admin.support.reply', $ticket), [
            'message' => 'The issue has been corrected. Please upload once more.', 'status' => 'resolved',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $messages = $ticket->messages()->where('type', 'message')->orderBy('id')->get();
        $this->assertSame([
            'Please try selecting the paper again.',
            'I tried again and still need help with the file.',
            'The issue has been corrected. Please upload once more.',
        ], $messages->pluck('message')->all());
        $this->assertSame([$admin->id, $owner->id, $admin->id], $messages->pluck('user_id')->all());
        $this->assertFalse((bool)$messages[1]->is_admin);
        $this->assertSame('Please help me with this assessment upload.', $ticket->fresh()->message);
        $this->assertSame('resolved', $ticket->fresh()->status);

        $this->actingAs($owner)->get(route('help.show', $ticket))->assertOk()
            ->assertSee('Please try selecting the paper again.')
            ->assertSee('I tried again and still need help with the file.')
            ->assertSee('The issue has been corrected. Please upload once more.');
        $this->actingAs($admin)->get(route('admin.support.index', ['tab' => 'solutions']))->assertOk()->assertSee($ticket->subject);
    }

    public function test_closed_and_resolved_tickets_require_reopening_before_a_customer_can_reply(): void
    {
        $owner = $this->account('student@example.com');
        $ticket = $this->ticket($owner);
        $this->actingAs($owner)->patch(route('help.status', $ticket), ['status' => 'closed'])->assertRedirect();
        $this->assertSame('closed', $ticket->fresh()->status);
        $this->postJson(route('help.reply', $ticket), ['message' => 'A message while closed.'])->assertUnprocessable();
        $this->patch(route('help.status', $ticket), ['status' => 'open'])->assertRedirect();
        $this->post(route('help.reply', $ticket), ['message' => 'Now I can provide more information.'])->assertRedirect();

        $admin = $this->account('admin@example.com');
        $this->actingAs($admin)->put(route('admin.support.reply', $ticket), ['status' => 'resolved'])->assertRedirect();
        $this->actingAs($owner)->postJson(route('help.reply', $ticket), ['message' => 'A message while resolved.'])->assertUnprocessable();
        $this->patch(route('help.status', $ticket), ['status' => 'open'])->assertRedirect();
        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertGreaterThanOrEqual(4, $ticket->messages()->where('type', 'status_change')->count());
        $this->assertSame(1, $ticket->messages()->where('type', 'message')->count());
    }

    public function test_legacy_admin_reply_stays_visible_after_later_replies_and_status_changes(): void
    {
        $owner = $this->account('mode1@example.com');
        $ticket = $this->ticket($owner, ['admin_reply' => 'Legacy answer that must remain in the conversation.', 'status' => 'answered']);
        $this->actingAs($owner)->get(route('help.show', $ticket))->assertOk()
            ->assertSee('Legacy answer that must remain in the conversation.');

        $admin = $this->account('admin@example.com');
        $this->actingAs($admin)->put(route('admin.support.reply', $ticket), [
            'message' => 'A newer support answer with additional details.', 'status' => 'in_progress',
        ])->assertRedirect();
        $this->put(route('admin.support.reply', $ticket), ['status' => 'resolved'])->assertRedirect();
        $this->assertSame('A newer support answer with additional details.', $ticket->fresh()->admin_reply);
        $this->actingAs($owner)->get(route('help.show', $ticket))->assertOk()
            ->assertSee('Legacy answer that must remain in the conversation.')
            ->assertSee('A newer support answer with additional details.')
            ->assertSee($ticket->message);
    }

    public function test_feedback_and_solutions_tabs_show_real_records_and_apply_filters(): void
    {
        $owner = $this->account('mode2@example.com');
        $feedback = $this->ticket($owner, ['subject' => 'Feedback about marking screens', 'category' => 'feedback', 'priority' => 'high']);
        $resolved = $this->ticket($owner, ['subject' => 'Resolved billing receipt', 'category' => 'billing', 'status' => 'resolved', 'admin_reply' => 'Receipt was corrected.']);
        $unreplied = $this->ticket($owner, ['subject' => 'Closed without a support solution', 'status' => 'closed']);
        $open = $this->ticket($owner, ['subject' => 'Open account login issue', 'category' => 'account']);
        $this->actingAs($this->account('admin@example.com'));

        $this->get(route('admin.support.index'))->assertOk()->assertViewHas('tickets', fn($tickets) => $tickets->total() === 4);
        $this->get(route('admin.support.index', ['tab' => 'feedback']))->assertOk()->assertSee($feedback->subject)
            ->assertDontSee($resolved->subject)->assertDontSee($open->subject);
        $this->get(route('admin.support.index', ['tab' => 'solutions']))->assertOk()->assertSee($resolved->subject)
            ->assertDontSee($unreplied->subject)->assertDontSee($open->subject);
        $this->get(route('admin.support.index', ['q' => 'billing receipt', 'status' => 'resolved', 'category' => 'billing']))
            ->assertOk()->assertSee($resolved->subject)->assertViewHas('tickets', fn($tickets) => $tickets->total() === 1);
        $this->get(route('admin.support.index', ['priority' => 'high']))->assertOk()->assertSee($feedback->subject)
            ->assertViewHas('tickets', fn($tickets) => $tickets->total() === 1);
    }

    public function test_customer_lists_are_paginated_and_search_never_includes_another_customers_ticket(): void
    {
        $owner = $this->account('free.teacher@example.com');
        foreach (range(1, 25) as $number) $this->ticket($owner, ['subject' => sprintf('My numbered support case %02d', $number)]);
        $other = $this->ticket($this->account('mode1@example.com'), ['subject' => 'My numbered support case hidden']);
        $this->actingAs($owner)->get(route('help.index'))->assertOk()
            ->assertViewHas('tickets', fn($tickets) => $tickets->total() === 25 && $tickets->count() === 20)
            ->assertDontSee($other->subject);
        $this->get(route('help.index', ['page' => 2]))->assertOk()
            ->assertViewHas('tickets', fn($tickets) => $tickets->total() === 25 && $tickets->count() === 5);
        $this->get(route('help.index', ['q' => 'support case 07']))->assertOk()->assertSee('My numbered support case 07')
            ->assertViewHas('tickets', fn($tickets) => $tickets->total() === 1);
        $this->get(route('help.index', ['q' => 'hidden']))->assertOk()->assertViewHas('tickets', fn($tickets) => $tickets->total() === 0);
    }

    public function test_invalid_ticket_reply_status_and_filter_input_does_not_change_stored_data(): void
    {
        $owner = $this->account('free.teacher@example.com');
        $this->actingAs($owner)->postJson(route('help.store'), [
            'subject' => ['not a string'], 'category' => 'invalid category', 'priority' => 'urgent', 'message' => 'Too short',
        ])->assertUnprocessable()->assertJsonValidationErrors(['subject', 'category', 'priority', 'message']);
        $this->assertDatabaseCount('support_tickets', 0);

        $ticket = $this->ticket($owner);
        $this->postJson(route('help.reply', $ticket), ['message' => ['not a string']])->assertUnprocessable();
        $this->postJson(route('help.reply', $ticket), ['message' => str_repeat('x', 10001)])->assertUnprocessable();
        $this->patchJson(route('help.status', $ticket), ['status' => 'answered'])->assertUnprocessable();
        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertSame(0, $ticket->messages()->count());

        $this->actingAs($this->account('admin@example.com'));
        $this->putJson(route('admin.support.reply', $ticket), [])->assertUnprocessable();
        $this->putJson(route('admin.support.reply', $ticket), ['status' => 'invalid'])->assertUnprocessable();
        $this->getJson(route('admin.support.index', ['q' => str_repeat('x', 101)]))->assertUnprocessable();
        $this->assertSame('open', $ticket->fresh()->status);
    }

    public function test_validation_error_roundtrip_keeps_the_ticket_readable_and_previous_conversation_intact(): void
    {
        $owner = $this->account('free.teacher@example.com');
        $ticket = $this->ticket($owner);
        $this->actingAs($owner)->post(route('help.reply', $ticket), [
            'message' => 'A valid follow-up that must not disappear.',
        ])->assertRedirect();
        $this->from(route('help.show', $ticket))->post(route('help.reply', $ticket), [
            'message' => ['malformed message input'],
        ])->assertRedirect(route('help.show', $ticket))->assertSessionHasErrors('message');
        $this->get(route('help.show', $ticket))->assertOk()
            ->assertSee($ticket->message)->assertSee('A valid follow-up that must not disappear.');
        $this->assertSame(1, $ticket->messages()->where('type', 'message')->count());
    }

    public function test_ticket_messages_and_replies_are_escaped_in_both_customer_and_admin_views(): void
    {
        $owner = $this->account('mode1@example.com');
        $ticket = $this->ticket($owner, [
            'subject' => '<script>badSubject()</script>', 'message' => '<script>badOriginal()</script>',
            'admin_reply' => '<script>badLegacy()</script>',
        ]);
        $this->actingAs($owner)->post(route('help.reply', $ticket), ['message' => '<script>badReply()</script>'])->assertRedirect();
        foreach ([[$owner, 'help.show'], [$this->account('admin@example.com'), 'admin.support.show']] as [$viewer, $route]) {
            $response = $this->actingAs($viewer)->get(route($route, $ticket))->assertOk();
            foreach (['badSubject', 'badOriginal', 'badLegacy', 'badReply'] as $name) {
                $response->assertSee('&lt;script&gt;'.$name.'()&lt;/script&gt;', false)
                    ->assertDontSee('<script>'.$name.'()</script>', false);
            }
        }
    }

    public function test_support_activity_notifies_only_the_appropriate_admins_or_ticket_owner(): void
    {
        $owner = $this->account('free.teacher@example.com');
        $admin = $this->account('admin@example.com');
        $unrelated = $this->account('mode1@example.com');
        $secondAdmin = User::create([
            'name' => 'Secondary Administrator', 'email' => 'secondary.admin@example.com', 'mobile' => '9000000018',
            'password' => 'password', 'user_type' => 'platform_admin', 'plan_id' => $owner->plan_id, 'account_status' => 'active',
        ]);
        $unrelated->update(['role_title' => 'platform_admin']);

        $this->actingAs($owner)->post(route('help.store'), [
            'subject' => 'Notifications support case', 'category' => 'account', 'priority' => 'normal',
            'message' => 'Please help me with this account notification.',
        ])->assertRedirect();
        $ticket = SupportTicket::where('subject', 'Notifications support case')->firstOrFail();
        foreach ([$admin, $secondAdmin] as $recipient) {
            $notification = $recipient->notifications()->firstOrFail();
            $this->assertSame($ticket->id, $notification->data['ticket_id']);
            $this->assertSame(route('admin.support.show', $ticket, false), parse_url($notification->data['url'], PHP_URL_PATH));
        }
        $this->assertSame(0, $owner->notifications()->count());
        $this->assertSame(0, $unrelated->notifications()->count());

        $this->actingAs($admin)->put(route('admin.support.reply', $ticket), ['message' => 'Your support request has been reviewed.'])->assertRedirect();
        $ownerNotification = $owner->notifications()->firstOrFail();
        $this->assertSame(route('help.show', $ticket, false), parse_url($ownerNotification->data['url'], PHP_URL_PATH));
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $secondAdmin->notifications()->count());

        $this->actingAs($owner)->post(route('help.reply', $ticket), ['message' => 'Thank you, I have one more question.'])->assertRedirect();
        $this->assertSame(2, $admin->notifications()->count());
        $this->assertSame(2, $secondAdmin->notifications()->count());
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(0, $unrelated->notifications()->count());

        $this->actingAs($admin)->put(route('admin.support.reply', $ticket), ['status' => 'resolved'])->assertRedirect();
        $this->assertSame(2, $owner->notifications()->count());
        $this->actingAs($owner)->post(route('notifications.read'))->assertRedirect();
        $this->assertSame(0, $owner->unreadNotifications()->count());
        $this->assertSame(2, $admin->unreadNotifications()->count());
    }

    public function test_opening_one_ticket_marks_only_its_notifications_as_read(): void
    {
        $owner = $this->account('mode2@example.com');
        $admin = $this->account('admin@example.com');
        $otherUser = $this->account('mode1@example.com');
        foreach (['First notification case', 'Second notification case'] as $subject) {
            $this->actingAs($owner)->post(route('help.store'), [
                'subject' => $subject, 'category' => 'general', 'priority' => 'normal',
                'message' => 'Please help me with this separate support question.',
            ])->assertRedirect();
        }
        $first = SupportTicket::where('subject', 'First notification case')->firstOrFail();
        $second = SupportTicket::where('subject', 'Second notification case')->firstOrFail();
        $this->assertSame(2, $admin->unreadNotifications()->count());
        $this->actingAs($admin)->get(route('admin.support.show', $first))->assertOk();
        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->assertSame($second->id, $admin->unreadNotifications()->firstOrFail()->data['ticket_id']);

        foreach ([$first, $second] as $ticket) {
            $this->put(route('admin.support.reply', $ticket), ['message' => 'A reply for this particular support question.'])->assertRedirect();
        }
        $this->assertSame(2, $owner->unreadNotifications()->count());
        $this->actingAs($owner)->get(route('help.show', $first))->assertOk();
        $this->assertSame(1, $owner->unreadNotifications()->count());
        $this->assertSame($second->id, $owner->unreadNotifications()->firstOrFail()->data['ticket_id']);
        $this->actingAs($otherUser)->get(route('help.show', $second))->assertForbidden();
        $this->assertSame(1, $owner->unreadNotifications()->count());
    }

    public function test_guest_and_non_admin_cannot_reach_admin_ticket_actions(): void
    {
        $owner = $this->account('student@example.com');
        $ticket = $this->ticket($owner);
        $this->get(route('help.index'))->assertRedirect(route('login'));
        $this->get(route('help.show', $ticket))->assertRedirect(route('login'));
        $this->get(route('admin.support.index'))->assertRedirect(route('login'));
        $this->actingAs($owner)->get(route('admin.support.show', $ticket))->assertForbidden();
        $this->put(route('admin.support.reply', $ticket), ['message' => 'A forged support answer.'])->assertForbidden();
        $this->assertNull($ticket->fresh()->admin_reply);
    }

    private function account(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function ticket(User $owner, array $attributes = []): SupportTicket
    {
        return SupportTicket::create(array_merge([
            'user_id' => $owner->id, 'subject' => 'Support ticket for user '.$owner->id,
            'category' => 'general', 'priority' => 'normal', 'status' => 'open',
            'message' => 'Please help me with this assessment upload.',
        ], $attributes));
    }
}

<?php

namespace Tests\Feature;

use App\Jobs\ProcessAssessment;
use App\Models\{Assessment, PaymentGatewaySetting, Plan, Student, User};
use App\Services\PaperMarker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Bus, DB};
use Tests\TestCase;

class PlanAccessFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_free_teacher_can_open_support_and_submit_a_ticket(): void
    {
        $teacher = $this->account('free.teacher@example.com');
        $this->actingAs($teacher)->get(route('help.index'))->assertOk();
        $this->post(route('help.store'), [
            'subject' => 'Help with uploading a paper',
            'category' => 'upload',
            'priority' => 'normal',
            'message' => 'Please help me select the correct paper file.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $teacher->id,
            'subject' => 'Help with uploading a paper',
        ]);
        $this->get(route('admin.support.index'))->assertForbidden();
    }

    public function test_each_account_sees_only_its_accessible_sidebar_and_every_link_opens(): void
    {
        $common = ['dashboard', 'assessments.create', 'assessments.index', 'notifications.index', 'help.index', 'settings.index', 'billing.index'];
        $professional = ['students.index', 'assessments.bulk.create', 'reports.index', 'analytics'];
        $cases = [
            'free.teacher@example.com' => $common,
            'mode1@example.com' => [...$common, 'students.index'],
            'mode2@example.com' => [...$common, ...$professional],
            'mode3@example.com' => [...$common, ...$professional, 'communications.index', 'team.index'],
            'mode3.teacher@example.com' => [...$common, ...$professional, 'communications.index'],
            'student@example.com' => $common,
            'admin@example.com' => [
                'dashboard', 'assessments.create', 'assessments.index', 'notifications.index', 'settings.index',
                ...$professional, 'communications.index', 'team.index', 'admin.support.index', 'admin.plans.index',
                'admin.payment-gateways.index', 'admin.transactions.index', 'admin.users.index',
                'admin.config.index', 'admin.ai-processing.index', 'admin.ai-settings.index', 'admin.audit.index',
            ],
        ];

        foreach ($cases as $email => $routes) {
            $user = $this->account($email);
            $page = $this->actingAs($user)->get(route('help.index'))->assertOk();
            $actual = $this->sidebarPaths($page->getContent());
            $expected = array_map(fn($name) => parse_url(route($name), PHP_URL_PATH), $routes);
            sort($actual);
            sort($expected);
            $this->assertSame($expected, $actual, 'Unexpected sidebar for '.$email);

            foreach ($routes as $name) {
                $response = $this->get(route($name));
                if ($name === 'dashboard' && !$user->isPlatformAdmin() && ($user->isTeacher() || $user->isStudent())) {
                    $portal = $user->isStudent() ? 'portal.student' : 'portal.teacher';
                    $response->assertRedirect(route($portal));
                    $this->get(route($portal))->assertOk();
                } else {
                    $response->assertOk();
                }
            }
        }
    }

    public function test_restricted_plan_pages_redirect_but_json_and_mutations_are_forbidden(): void
    {
        $teacher = $this->account('free.teacher@example.com');
        $paper = $this->paper($teacher);
        $result = $this->question($paper);
        $this->actingAs($teacher);

        foreach (['analytics', 'reports.index', 'assessments.bulk.create', 'students.index'] as $name) {
            $this->get(route($name))->assertRedirect(route('billing.index'))->assertSessionHas('error');
            $this->getJson(route($name))->assertForbidden();
        }
        $this->post(route('assessments.bulk.store'), [])->assertForbidden();
        $this->patch(route('results.update', $result), ['teacher_marks' => 2])->assertForbidden();
        $this->post(route('results.recheck-question', $result))->assertForbidden();
    }

    public function test_mode_two_cannot_use_parent_corrected_paper_or_teacher_management(): void
    {
        $teacher = $this->account('mode2@example.com');
        $paper = $this->paper($teacher, ['status' => 'finalised']);
        $this->actingAs($teacher);

        $this->get(route('communications.index'))->assertRedirect(route('billing.index'));
        $this->get(route('results.corrected', $paper))->assertRedirect(route('billing.index'));
        $this->getJson(route('results.corrected', $paper))->assertForbidden();
        $this->post(route('parent-report.send', $paper))->assertForbidden();
        $this->post(route('parent-report.mobile', $paper))->assertForbidden();
        $this->get(route('team.index'))->assertRedirect(route('dashboard'));
        $this->post(route('team.store'), [])->assertForbidden();
    }

    public function test_upgraded_student_remains_read_only_for_teaching_actions(): void
    {
        $student = $this->account('student@example.com');
        $student->update(['plan_id' => Plan::where('slug', 'mode-3')->value('id')]);
        $paper = $this->paper($student, ['status' => 'review_required']);
        $result = $this->question($paper);
        $this->actingAs($student->fresh());

        foreach (['students.index', 'analytics', 'assessments.bulk.create', 'communications.index', 'team.index'] as $name) {
            $this->get(route($name))->assertRedirect(route('dashboard'));
            $this->getJson(route($name))->assertForbidden();
        }
        $this->patch(route('results.update', $result), ['teacher_marks' => 2])->assertForbidden();
        $this->post(route('results.recheck-question', $result))->assertForbidden();
        $this->post(route('results.finalise', $paper))->assertForbidden();
        $this->post(route('results.unfinalise', $paper))->assertForbidden();
        $this->post(route('parent-report.send', $paper))->assertForbidden();
        $this->post(route('parent-report.mobile', $paper))->assertForbidden();
        $this->get(route('results.show', $paper))->assertOk()->assertDontSee('name="teacher_marks"', false);
    }

    public function test_staff_uses_current_owner_plan_even_when_its_own_plan_is_stale(): void
    {
        $coach = $this->account('mode3@example.com');
        $teacher = $this->account('mode3.teacher@example.com');
        $teacher->update(['plan_id' => Plan::where('slug', 'free')->value('id')]);
        $teacher = $teacher->fresh();
        $this->assertTrue($teacher->canAccessModule('analytics'));
        $this->assertSame(200, $teacher->limit('paper_check_limit'));
        $this->actingAs($teacher)->get(route('analytics'))->assertOk();

        $coach->update(['plan_id' => Plan::where('slug', 'mode-1')->value('id')]);
        $teacher = $teacher->fresh();
        $this->assertFalse($teacher->canAccessModule('analytics'));
        $this->assertSame(10, $teacher->limit('paper_check_limit'));
        $this->actingAs($teacher)->get(route('analytics'))->assertRedirect(route('billing.index'));
    }

    public function test_staff_only_sees_own_or_assigned_papers_and_cannot_delete_coach_papers(): void
    {
        $coach = $this->account('mode3@example.com');
        $teacher = $this->account('mode3.teacher@example.com');
        $assigned = $this->paper($coach, ['title' => 'Assigned visible paper', 'assigned_teacher_id' => $teacher->id]);
        $unassigned = $this->paper($coach, ['title' => 'Private unassigned paper']);
        $own = $this->paper($teacher, ['title' => 'Own teacher paper']);
        $this->assertEqualsCanonicalizing([$assigned->id, $own->id], $teacher->accessibleAssessments()->pluck('id')->all());

        $this->actingAs($teacher)->get(route('assessments.index'))
            ->assertOk()->assertSee($assigned->title)->assertSee($own->title)->assertDontSee($unassigned->title);
        $this->get(route('assessments.show', $assigned))->assertOk();
        $this->get(route('assessments.show', $unassigned))->assertForbidden();
        $this->delete(route('assessments.destroy', $assigned))->assertForbidden();
        $this->assertDatabaseHas('assessments', ['id' => $assigned->id]);
    }

    public function test_linked_student_only_sees_published_results_and_cannot_start_teacher_work(): void
    {
        Bus::fake([ProcessAssessment::class]);
        config(['exam-marker.async' => true]);
        $student = $this->account('student@example.com');
        $coach = $this->account('mode3@example.com');
        $profile = Student::where('login_user_id', $student->id)->firstOrFail();
        $draft = $this->paper($coach, ['title' => 'Unpublished teacher result', 'student_id' => $profile->id, 'status' => 'review_required']);
        $published = $this->paper($coach, ['title' => 'Published student result', 'student_id' => $profile->id, 'status' => 'finalised']);
        $this->question($published);
        $own = $this->paper($student, ['title' => 'Own uploaded student paper']);
        $this->assertEqualsCanonicalizing([$published->id, $own->id], $student->accessibleAssessments()->pluck('id')->all());

        $this->actingAs($student)->get(route('portal.student'))
            ->assertOk()->assertSee($published->title)->assertSee($own->title)->assertDontSee($draft->title);
        $this->get(route('results.show', $draft))->assertForbidden();
        $this->get(route('results.show', $published))->assertOk();
        $this->post(route('assessments.start', $published))->assertForbidden();
        $this->post(route('assessments.start', $own))->assertRedirect(route('assessments.processing', $own));
        Bus::assertDispatched(ProcessAssessment::class, fn($job) => $job->assessmentId === $own->id);
        $this->assertSame('queued', $own->fresh()->status);
    }

    public function test_feedback_only_plan_cannot_change_marks_or_finalise(): void
    {
        $teacher = $this->account('mode2@example.com');
        $this->giveFeatures($teacher, ['feedback_editing' => true]);
        $paper = $this->paper($teacher, ['status' => 'review_required']);
        $result = $this->question($paper);
        $this->actingAs($teacher->fresh())->get(route('results.show', $paper))
            ->assertOk()->assertSee('name="teacher_comment"', false)->assertDontSee('name="teacher_marks"', false);

        $this->patch(route('results.update', $result), ['teacher_comment' => 'Explain the final step.'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Explain the final step.', $result->fresh()->teacher_comment);
        $this->assertNull($result->fresh()->teacher_marks);
        $this->patch(route('results.update', $result), ['teacher_marks' => 9])->assertForbidden();
        $this->post(route('results.finalise', $paper))->assertForbidden();
    }

    public function test_recheck_only_plan_can_recheck_without_editing_or_finalising(): void
    {
        $teacher = $this->account('mode2@example.com');
        $this->giveFeatures($teacher, ['question_recheck' => true]);
        $paper = $this->paper($teacher, ['status' => 'review_required']);
        $result = $this->question($paper);
        $marker = \Mockery::mock(PaperMarker::class, \App\Services\QuestionScopedMarker::class);
        $marker->shouldNotReceive('mark');
        $marker->shouldReceive('markQuestion')->once()->andReturn([
            'question_number' => '1', 'max_marks' => 10, 'ai_marks' => 7, 'confidence' => 'high', 'feedback' => 'Rechecked calculation.',
        ]);
        $this->app->instance(PaperMarker::class, $marker);

        $this->actingAs($teacher->fresh())->get(route('results.show', $paper))
            ->assertOk()->assertSee(route('results.recheck-question', $result), false)
            ->assertDontSee('name="teacher_marks"', false)->assertDontSee('name="teacher_comment"', false);
        $this->post(route('results.recheck-question', $result))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('7.00', $result->fresh()->ai_marks);
        $this->assertSame(1, $result->fresh()->recheck_count);
        $this->patch(route('results.update', $result), ['teacher_marks' => 9])->assertForbidden();
        $this->post(route('results.finalise', $paper))->assertForbidden();
    }

    public function test_display_role_title_cannot_grant_administrator_access(): void
    {
        $teacher = $this->account('free.teacher@example.com');
        $teacher->update(['role_title' => 'platform_admin']);
        $this->assertFalse($teacher->isPlatformAdmin());
        $this->assertFalse($teacher->canAccessModule('admin'));
        $this->actingAs($teacher)->get(route('admin.plans.index'))->assertForbidden();

        $teacher->update(['user_type' => 'platform_admin', 'role_title' => null]);
        $this->assertTrue($teacher->isPlatformAdmin());
        $this->actingAs($teacher)->get(route('admin.plans.index'))->assertOk();
    }

    public function test_finalised_results_must_be_reopened_before_teacher_changes(): void
    {
        $teacher = $this->account('mode2@example.com');
        $paper = $this->paper($teacher, ['status' => 'review_required']);
        $result = $this->question($paper);
        $this->actingAs($teacher)->post(route('results.finalise', $paper))->assertRedirect();
        $this->assertSame('finalised', $paper->fresh()->status);
        $this->patchJson(route('results.update', $result), ['teacher_marks' => 9])->assertUnprocessable();
        $this->postJson(route('results.recheck-question', $result))->assertUnprocessable();
        $this->post(route('results.unfinalise', $paper))->assertRedirect();
        $this->assertSame('review_required', $paper->fresh()->status);
        $this->patch(route('results.update', $result), ['teacher_marks' => 9])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('9.00', $result->fresh()->teacher_marks);
    }

    public function test_gateway_page_accepts_legacy_empty_credentials_and_preserves_encryption(): void
    {
        $gateway = PaymentGatewaySetting::where('provider', 'paypal')->firstOrFail();
        foreach ([null, '', '[]', '{}'] as $empty) {
            DB::table('payment_gateway_settings')->where('id', $gateway->id)->update(['credentials' => $empty]);
            $this->assertSame([], $gateway->fresh()->credentials);
        }

        $credentials = ['client_id' => 'test-client', 'client_secret' => 'test-only-secret'];
        $gateway->update(['credentials' => $credentials]);
        $stored = $gateway->fresh();
        $this->assertSame($credentials, $stored->credentials);
        $this->assertNotSame(json_encode($credentials), $stored->getRawOriginal('credentials'));
        $this->assertStringNotContainsString('test-only-secret', $stored->getRawOriginal('credentials'));
        $this->actingAs($this->account('admin@example.com'))->get(route('admin.payment-gateways.index'))->assertOk();
    }

    public function test_recognized_admin_variants_can_manage_another_owners_student(): void
    {
        $admin = $this->account('free.teacher@example.com');
        $profile = Student::where('login_user_id', $this->account('student@example.com')->id)->firstOrFail();
        $variants = [
            ['user_type' => 'platform_admin', 'is_admin' => false],
            ['user_type' => 'student', 'is_admin' => true],
        ];

        foreach ($variants as $index => $attributes) {
            $admin->update($attributes);
            $this->actingAs($admin->fresh())->get(route('students.index'))->assertOk();
            $this->get(route('students.edit', $profile))->assertOk();
            $this->put(route('students.update', $profile), ['name' => 'Administrator update '.$index])
                ->assertRedirect(route('students.index'))->assertSessionHasNoErrors();
            $this->assertDatabaseHas('students', ['id' => $profile->id, 'name' => 'Administrator update '.$index]);
        }
    }

    public function test_custom_metric_flags_hide_unavailable_data_and_graph_changes_apply_dynamically(): void
    {
        $teacher = $this->account('mode2@example.com');
        $plan = $teacher->plan;
        $plan->update(['features' => array_merge($plan->features, [
            'graphs' => true, 'result_report_download' => true,
            'percentage' => false, 'grade' => false, 'topic_analysis' => false,
        ])]);
        $paper = $this->paper($teacher, [
            'status' => 'finalised', 'percentage' => 73.25, 'grade_awarded' => 'HIDDEN_GRADE',
        ]);
        $result = $this->question($paper);
        $result->update(['topic' => 'HIDDEN_TOPIC']);
        $this->actingAs($teacher->fresh())->get(route('results.show', $paper))->assertOk()
            ->assertSee('Obtained Marks')->assertDontSee('<small>Percentage</small>', false)
            ->assertDontSee('<small>Grade</small>', false)->assertDontSee('HIDDEN_GRADE');
        $this->get(route('reports.index'))->assertOk()->assertSee('5 / 10')
            ->assertDontSee('73.25%')->assertDontSee('HIDDEN_GRADE');
        $analytics = $this->get(route('analytics'))->assertOk()->assertSee('5 / 10')
            ->assertDontSee('Topics Tracked')->assertDontSee('Topic performance')->assertDontSee('<th>Grade</th>', false)
            ->assertDontSee('HIDDEN_TOPIC')->assertDontSee('HIDDEN_GRADE')->assertDontSee('73.25%');
        $this->assertContains('/analytics', $this->sidebarPaths($analytics->getContent()));

        $plan->update(['features' => array_merge($plan->features, ['graphs' => false])]);
        $page = $this->actingAs($teacher->fresh())->get(route('help.index'))->assertOk();
        $this->assertNotContains('/analytics', $this->sidebarPaths($page->getContent()));
        $this->get(route('analytics'))->assertRedirect(route('billing.index'));
        $this->getJson(route('analytics'))->assertForbidden();
    }

    private function giveFeatures(User $user, array $features): void
    {
        $base = Plan::where('slug', 'free')->firstOrFail()->features;
        $plan = Plan::create([
            'name' => 'Custom permission plan', 'slug' => 'custom-'.$user->id,
            'price' => 0, 'features' => array_merge($base, $features),
            'limits' => ['paper_check_limit' => 10, 'recheck_limit' => 10], 'active' => true,
        ]);
        $user->update(['plan_id' => $plan->id]);
    }

    private function account(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function paper(User $owner, array $attributes = []): Assessment
    {
        return Assessment::create(array_merge([
            'user_id' => $owner->id,
            'title' => 'Access regression paper',
            'subject' => 'Mathematics',
            'total_marks' => 10,
            'status' => 'uploaded',
            'question_paper_path' => 'access-tests/question.pdf',
            'written_answer_path' => 'access-tests/answer.pdf',
        ], $attributes));
    }

    private function question(Assessment $assessment)
    {
        return $assessment->results()->create([
            'question_number' => '1', 'max_marks' => 10, 'ai_marks' => 5,
            'confidence' => 'medium', 'feedback' => 'Review the calculation.',
        ]);
    }

    private function sidebarPaths(string $html): array
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $nodes = (new \DOMXPath($dom))->query('//aside[@id="sidebar"]//nav//a[@href]');

        return array_map(fn($node) => parse_url($node->getAttribute('href'), PHP_URL_PATH), iterator_to_array($nodes));
    }
}

<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Plan,User};
class PlanRulesTest extends TestCase {
 use RefreshDatabase;
 public function test_mode_2_cannot_download_corrected_paper(): void {
  $this->seed(); $plan=Plan::where('slug','mode-2')->firstOrFail();
  $this->assertFalse((bool)$plan->features['corrected_paper_download']);
  $this->assertFalse((bool)$plan->features['parent_email']);
  $this->assertFalse((bool)$plan->features['multiple_teachers']);
 }
 public function test_mode_3_has_parent_and_corrected_paper_features(): void {
  $this->seed(); $plan=Plan::where('slug','mode-3')->firstOrFail();
  $this->assertTrue((bool)$plan->features['corrected_paper_download']);
  $this->assertTrue((bool)$plan->features['parent_email']);
  $this->assertTrue((bool)$plan->features['multiple_teachers']);
 }
}

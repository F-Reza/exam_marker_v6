<?php
namespace App\Services;
use App\Models\Assessment;
interface PaperMarker { public function mark(Assessment $assessment): array; }

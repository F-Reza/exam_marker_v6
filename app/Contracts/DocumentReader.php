<?php
namespace App\Contracts;
use App\Models\Assessment;
interface DocumentReader { public function read(Assessment $assessment): array; }

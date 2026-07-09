<?php

use Illuminate\Support\Facades\Schedule;

// Nightly safety net — one laptop is one power surge away from losing everything.
Schedule::command('pos:backup')->dailyAt('21:30');

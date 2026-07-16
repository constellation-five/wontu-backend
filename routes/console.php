<?php

// Register closure based console commands here.

use Illuminate\Support\Facades\Schedule;

Schedule::command('offers:process-lifecycle')->everyMinute();

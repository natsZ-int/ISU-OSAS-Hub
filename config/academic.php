<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Current Academic Term
    |--------------------------------------------------------------------------
    |
    | Used by the student enrollment verification workflow. Verification
    | records are scoped to an academic year and semester so that enrollment
    | confirmed for a previous term is not treated as proof of current
    | enrollment. Update these values at the start of each term.
    |
    */

    'current_year'     => env('ACADEMIC_CURRENT_YEAR', '2025-2026'),
    'current_semester' => env('ACADEMIC_CURRENT_SEMESTER', 'first'),

    'semesters' => ['first', 'second', 'summer'],

];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tag type limits
    |--------------------------------------------------------------------------
    | Maximum number of tags per tag_type to include when building match
    | vectors. Prevents one type (e.g. many skills) from dominating.
    | Omit or null = no limit for that type.
    */
    'tag_type_limits' => [
        'skill' => 6,
        'industry' => 5,
        'major' => 5,
        'trait' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tag types used for match scoring
    |--------------------------------------------------------------------------
    | Only these tag types contribute to the overall cosine score. Major and
    | industry are used for filtering only, not for the algorithm.
    */
    'match_tag_types' => ['skill', 'trait'],

    /*
    |--------------------------------------------------------------------------
    | Default weights when not set
    |--------------------------------------------------------------------------
    */
    'default_student_weight' => 3,
    'default_vacancy_importance' => 3,

    /*
    |--------------------------------------------------------------------------
    | Subscore categories (skill and trait only; major/industry for filtering)
    |--------------------------------------------------------------------------
    */
    'subscore_categories' => ['skill', 'trait'],
];

<?php

use App\class\Group;
use App\Test\CustomUser;

$groups['Groups'] = [
    new Group(1, 'IPZ', 'Инженерия програмного обеспечения'),
    new Group(2, 'PM', 'Product Management'),
    new Group(3, 'SS', 'Super Group'),
];

$groups['Teachers'] = $groups['Groups'][0];
$groups['Students'] = [$groups['Groups'][1], $groups['Groups'][2]];

return [
    'users' => [
        'Guests' => [
            [
                new CustomUser(0, 'Test'),
                [
                    'first_name' => "TestName",
                    'middle_name' => "TestMiddle",
                    'second_name' => "TestSecond",
                    'birthday' => "2005-04-01",
                    'email' => "test@gmail.com",
                    'phone' => "+3800000000"
                ]
            ]
        ],
        'Teachers' => [
            [
                new CustomUser(1, 'Pavlova'),
                [
                    'first_name' => "Pavlova",
                    'middle_name' => "Fucture",
                    'second_name' => "Sergeevna",
                    'birthday' => "2005-04-12",
                    'email' => "pavlova@gmail.com",
                    'phone' => "+38077777777"
                ]
            ]
        ],
        'Students' => [
            [
                new CustomUser(2, 'Petrov'),
                [
                    'first_name' => "Petrov",
                    'middle_name' => "Loken",
                    'second_name' => "Moken",
                    'birthday' => "2001-02-10",
                    'email' => "petrovthebest@gmail.com",
                    'phone' => "+38099999999"
                ]
            ],
            [
                new CustomUser(3, 'Petrov'),
                [
                    'first_name' => "Wolf",
                    'middle_name' => "Soken",
                    'second_name' => "Moken",
                    'birthday' => "2001-02-10",
                    'email' => "petrovthebest@gmail.com",
                    'phone' => "+38099999999"
                ]
            ],
            [
                new CustomUser(4, 'Petrov'),
                [
                    'first_name' => "Popo",
                    'middle_name' => "Papa",
                    'second_name' => "Nano",
                    'birthday' => "2001-02-10",
                    'email' => "petrovthebest@gmail.com",
                    'phone' => "+38099999999"
                ]
            ]
        ]
    ],

    'groups' => $groups,

    'subjects' => [
        ['name' => 'Физкультура'],
        ['name' => 'Информатика']
    ],

    // TODO PAIR
    'pairs' => [
        [
            'start' => '12:00',
            'end' => '13:00',
            'week' => 1
        ],

        [
            'start' => '10:00',
            'end' => '15:00',
            'week' => '2',
        ]
    ],

    'works' => [
        1 =>
        [
            [
                'task' => 'Work true 1',
                'subject_id' => 'Физкультура',
                'teacher_id' => 1,
                'group_id' => 'PM',
                'start' => '2020-01-01 20:15',
                'end' => '2020-01-03 20:10'
            ],
            [
                'task' => 'Work true 2',
                'subject_id' => 'Информатика',
                'teacher_id' => 1,
                'group_id' => 'SS',
                'start' => '2024-05-05 20:10',
                'end' => '2024-05-07 22:10'
            ],
            [
                'task' => 'Work true 3',
                'subject_id' => 'Физкультура',
                'teacher_id' => 1,
                'group_id' => 'SS',
                'start' => '2024-05-02 20:10',
                'end' => '2024-06-05 22:10'
            ],
            [
                'task' => 'Work true 4',
                'subject_id' => 'Информатика',
                'teacher_id' => 1,
                'group_id' => 'PM',
                'start' => '2024-06-08 20:10',
                'end' => '2024-11-05 22:10'
            ],
            [
                'task' => 'Work true 5',
                'subject_id' => 'Информатика',
                'teacher_id' => 1,
                'group_id' => 'SS',
                'start' => '2024-05-03 20:10',
                'end' => '2024-11-10 22:10'
            ]
        ],

        0 =>
        [
            [
                'task' => 'Work false 1',
                'subject_id' => 'Информатика',
                'teacher_id' => 1,
                'group_id' => 'IPZ',
                'start' => '2020-01-07 22:10',
                'end' => '2020-01-05 10:10'
            ], // Future date
            [
                'task' => 'Work false 2',
                'subject_id' => 'Информатика',
                'teacher_id' => 100000,
                'group_id' => 'IPZ',
                'start' => '2020-01-05 20:10',
                'end' => '2020-01-05 22:10'
            ], // Teacher absence
            [
                'task' => 'Work false 3',
                'subject_id' => '',
                'teacher_id' => 1,
                'group_id' => 'IPZ',
                'start' => '2020-01-05 20:10',
                'end' => '2020-01-05 22:10'
            ], // subject_id absence
            [
                'task' => 'Work false 4',
                'subject_id' => 'Информатика',
                'teacher_id' => 1,
                'group_id' => '',
                'start' => '2024-05-01 20:10',
                'end' => '2024-05-07 22:10'
            ], // group_id absence
            [
                'task' => "",
                'subject_id' => 'Информатика',
                'teacher_id' => 1,
                'group_id' => 'IPZ',
                'start' => '2020-01-05 20:10',
                'end' => '2020-01-05 22:10'
            ] // Task absence
        ]
    ],

    'completed_works' =>
    [
        [
            'student_id' => 3,
            'grade' => 2,
        ],
        [
            'student_id' => 2,
            'grade' => 3,
        ],
        [
            'student_id' => 4,
            'grade' => 5,
        ],
        [
            'student_id' => 3,
            'grade' => 1,
        ],
        [
            'student_id' => 2,
            'grade' => 4,
        ]
    ]
];

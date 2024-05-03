<?php

use App\class\Group;
use App\class\User;
use App\Test\CustomUser;

$groups['Groups'] = [
    new Group(1, 'IPZ', 'Инженерия програмного обеспечения'),
    new Group(2, 'PM', 'Product Management'),
    new Group(3, 'SS', 'Super Group'),
];

$groups['Teachers'] = $groups['Groups'][0];
$groups['Students'] = [$groups['Groups'][1], $groups['Groups'][2]];

$accounts['Teachers'] = [
    new User(
        '1', 
        'PavlovaGosha',
        "Pavlova",
        "Fucture",
        "Sergeevna",
        "2005-04-12",
        "+38077777777",
        "pavlova@gmail.com",
    ),

    new User(
        '2', 
        'VolodimirGoChat',
        "Volodimir",
        "Pososo",
        "Logoso",
        "1985-02-10",
        "+38099999999",
        "Volodi@gmail.com",
    ),
];

$accounts['Students'] = [
    new User(
        '3', 
        'PetrovMoj',
        "Petrov",
        "Loken",
        "Moken",
        "2001-02-10",
        "+38099999999",
        "petrovthebest@gmail.com"
    ),
    new User(
        '4', 
        'OlexeyBog',
        "Wolf",
        "Soken",
        "Moken",
        "2001-02-10",
        "+38099999999",
        "petrovthe@gmail.com",
    ),

    new User(
        '5', 
        'MogalogBogenka',
        "Popo",
        "Papa",
        "Nano",
        "2001-02-10",
        "+38099999999",
        "petebest@gmail.com"
    ),
];

$accounts['Guests'] = [
    new User(
        '6', 
        'UserTest',
        "Test",
        "User",
        "Account",
        "2001-02-10",
        "+38099999999",
        "test@gmail.com",
    ),
    new User(
        '7',
        "TESTUSER",
        "User",
        "Tests",
        "Accounts",
        "2001-02-10",
        "+38099999999",
        "peest@gmail.com"
    )
];

return [
    'users' => $accounts,

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

<?php

use App\class\Group;
use App\class\Subject;
use App\class\User;
use App\class\Work;

$groups['Groups'] = [
    new Group(1, 'IPZ', 'Инженерия програмного обеспечения'),
    new Group(2, 'PM', 'Product Management'),
    new Group(3, 'SS', 'Super Group'),
];

$groups['Teachers'] = [$groups['Groups'][0]];
$groups['Students'] = [$groups['Groups'][1], $groups['Groups'][2]];

$users['Teachers'] = [
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

$users['Students'] = [
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

$users['Guests'] = [
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
        "users",
        "2001-02-10",
        "+38099999999",
        "peest@gmail.com"
    )
];

$subjects = [
    new Subject(1, 'Физкультура'),
    new Subject(2, 'Матиматика'),
    new Subject(3, 'Информатика'),
];

$works = [
        [
            new Work(
                'Work true 1',
                $subjects[0]->name,
                $users['Teachers'][0]->telegram_id,
                $groups["Groups"][1]->name,
                '2020-01-01 20:15',
                '2020-01-03 20:10'
            ),
            new Work(
                'Work true 2',
                $subjects[1]->name,
                $users['Teachers'][0]->telegram_id,
                $groups["Groups"][2]->name,
                '2024-05-05 20:10',
                '2024-05-07 22:10'
            ),
            new Work(
                'Work true 3',
                $subjects[0]->name,
                $users['Teachers'][1]->telegram_id,
                $groups["Groups"][2]->name,
                '2024-05-02 20:10',
                '2024-06-05 22:10'
            ),
            new Work(
                'Work true 4',
                $subjects[1]->name,
                $users['Teachers'][1]->telegram_id,
                $groups["Groups"][1]->name,
                '2024-06-08 20:10',
                '2024-11-05 22:10'
            ),
            new Work(
                'Work true 5',
                $subjects[1]->name,
                $users['Teachers'][0]->telegram_id,
                $groups["Groups"][2]->name,
                '2024-05-03 20:10',
                '2024-11-10 22:10'
            )
        ],

        [
            new Work(
                'Work false 1',
                $subjects[1]->name,
                $users['Teachers'][0]->telegram_id,
                $groups["Groups"][0]->name,
                '2020-01-07 22:10',
                '2020-01-05 10:10'
            ), // Future date
            new Work(
                'Work false 2',
                $subjects[1]->name,
                100000,
                $groups["Groups"][0]->name,
                '2020-01-05 20:10',
                '2020-01-05 22:10'
            ), // Teacher absence
            new Work(
                'Work false 3',
                '',
                $users['Teachers'][1]->telegram_id,
                $groups["Groups"][0]->name,
                '2020-01-05 20:10',
                '2020-01-05 22:10'
            ), // subject_id absence
            new Work(
                'Work false 4',
                $subjects[1]->name,
                $users['Teachers'][0]->telegram_id,
                '',
                '2024-05-01 20:10',
                '2024-05-07 22:10'
            ), // group_id absence
            new Work(
                "",
                $subjects[1]->name,
                $users['Teachers'][1]->telegram_id,
                $groups["Groups"][0]->name,
                '2020-01-05 20:10',
                '2020-01-05 22:10'
            ) // Task absence
        ]
];

return [
    'users' => $users,

    'groups' => $groups,

    'subjects' => $subjects,



    'works' => $works,

    'completed_works' =>
    [
        [
            'student_id' => 3,
            'grade' => 2,
        ],
        [
            'student_id' => 3,
            'grade' => 3,
        ],
        [
            'student_id' => 4,
            'grade' => 5,
        ],
        [
            'student_id' => 5,
            'grade' => 1,
        ],
        [
            'student_id' => 4,
            'grade' => 4,
        ]
        ],

        // // TODO PAIR
    // 'pairs' => [
    //     [
    //         'start' => '12:00',
    //         'end' => '13:00',
    //         'week' => 1
    //     ],

    //     [
    //         'start' => '10:00',
    //         'end' => '15:00',
    //         'week' => '2',
    //     ]
    // ],
];

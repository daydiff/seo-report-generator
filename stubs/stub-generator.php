<?php

$projects = [
    [
        'id' => '1',
        'url' => 'td-komandor.ru',
    ],
    [
        'id' => '2',
        'url' => 'atabakoff.com',
    ],
];

echo "== PROJECTS ==\n";
echo htmlentities(serialize($projects)) . PHP_EOL;
echo "== END PROJECTS ==\n";

$regions = [
    66 => 'Ekaterinburg',
    77 => 'Moscow',
];

echo "== REGIONS ==\n";
echo htmlentities(serialize($regions)) . PHP_EOL;
echo "== END REGIONS ==\n";

$positions = [
    'Keyword' => [
        1 => [
            'php' => [2, 3],
            'go' => [4, 2],
        ],
        2 => [
            'php' => [2, 3],
            'go' => [4, 2],
        ],
    ],
    'php' => [
        // day => position
        2 => 4,
        3 => 4,
    ],
    'go' => [
        2 => 9,
        3 => 8,
    ],
    'top10' => [
        2 => [
            'php' => [2, 3],
            'go' => [4, 2],
        ],
        3 => [
            'php' => [2, 3],
            'go' => [4, 2],
        ],
    ],
    'top50' => [
        1 => [
            'php' => [2, 3],
            'go' => [4, 2],
        ],
        3 => [
            'php' => [2, 3],
            'go' => [4, 2],
        ],
    ],
    'topAbove' => [
        1 => [],
        2 => [],
    ],
];

echo "== POSITIONS ==\n";
echo htmlentities(serialize($positions)) . PHP_EOL;
echo "== END POSITIONS ==\n";

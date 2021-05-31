<h1 align="center">🚀 CATI Tree</h1>

**CATI Tree** *(Category Identification Tree)* - library for realization datasets identification in PHP 7.4+

This data structure and the algorithm implemented in it were invented by me so they certainly will work like pieces of shit. More useful information (in Russian) you can read [here](https://twitter.com/krypt0nn/status/1394701165238046724?s=20)

## Installation

```
composer require krypt0nn/cati-tree
```

## Example of work

### Tree

```php
$tree = CATI\Tree::train ([
    'a' => [
        [1, 2, 3],
        [1, 2, 4],
        [5, 6, 7],
        [6, 7, 8],
        [2, 3, 6]
    ],

    'b' => [
        [2, 3, 1]
    ]
]);

echo 'Training accuracy: '. $tree->acuracy();

file_put_contents ('tree.json', json_encode ($tree->export ()));
```

```php
$tree = CATI\Tree::load (json_decode (file_get_contents ('tree.json'), true));

echo $tree->predict ([6, 7, 8]) ?: 'unknown'; // a
```

### Random forest

```php
$forest = CATI\RandomForest::create ([
    'a' => [
        [1, 2, 3],
        [1, 2, 4],
        [5, 6, 7],
        [6, 7, 8],
        [2, 3, 6]
    ],

    'b' => [
        [2, 3, 1]
    ]
], forestSize: 5);

echo 'Training accuracy: '. $forest->acuracy();

file_put_contents ('forest.json', json_encode ($forest->export ()));
```

```php
$forest = CATI\RandomForest::load (json_decode (file_get_contents ('forest.json'), true));

print_r ($forest->probability ([6, 7, 8]));
```

Author: [Nikita Podvirnyy](https://vk.com/technomindlp)

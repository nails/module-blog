<?php

return [
    'models' => [
        'Blog' => function () {
            if (class_exists('\App\Blog\Model\Blog')) {
                return new \App\Blog\Model\Blog();
            } else {
                return new \Nails\Blog\Model\Blog();
            }
        },
        'Category' => function () {
            if (class_exists('\App\Blog\Model\Category')) {
                return new \App\Blog\Model\Category();
            } else {
                return new \Nails\Blog\Model\Category();
            }
        },
        'Post' => function () {
            if (class_exists('\App\Blog\Model\Post')) {
                return new \App\Blog\Model\Post();
            } else {
                return new \Nails\Blog\Model\Post();
            }
        },
        'Skin' => function () {
            if (class_exists('\App\Blog\Model\Skin')) {
                return new \App\Blog\Model\Skin();
            } else {
                return new \Nails\Blog\Model\Skin();
            }
        },
        'Tag' => function () {
            if (class_exists('\App\Blog\Model\Tag')) {
                return new \App\Blog\Model\Tag();
            } else {
                return new \Nails\Blog\Model\Tag();
            }
        },
    ],
];

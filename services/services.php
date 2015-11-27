<?php

return array(
    'models' => array(
        'Skin' => function () {
            if (class_exists('\App\Blog\Model\Skin')) {
                return new \App\Blog\Model\Skin();
            } else {
                return new \Nails\Blog\Model\Skin();
            }
        }
    )
);

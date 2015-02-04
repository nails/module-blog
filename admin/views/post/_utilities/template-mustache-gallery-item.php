<?php

    if (!empty($objectId)) {

        echo '<li class="gallery-item">';
            echo img(array('src' => cdn_thumb($objectId, 100, 100), 'width' => 100, 'height' => 100));
            echo '<a href="#" class="delete" data-object_id="' . $objectId . '"></a>';
            echo form_hidden('gallery[]', $objectId);
        echo '</li>';

    } else {

        echo '<li class="gallery-item crunching">';
            echo '<div class="crunching"></div>';
            echo form_hidden('gallery[]');
        echo '</li>';

    }

<?php
return array(

    'number_participants' => 3,
    'time_activity' => time()-300,
    'number_places_on_map' => 15,
    'map_empty_place_lives' => 1,
    'map_capital_place_lives' => 3,
    'question_time_seconds' => 10,

    'adjacent_places' => array(
        1 => array(2, 3), 2 => array(1, 3, 4, 5), 3 => array(1, 2, 4), 4 => array(3, 2, 5, 6),
        5 => array(2, 4, 6, 7), 6 => array(4, 5, 7, 8, 9), 7 => array(5, 6, 8, 9, 12),
        8 => array(6, 7, 9, 11, 12), 9 => array(6, 7, 8, 10, 11, 12), 10 => array(9, 11, 15),
        11 => array(9, 10, 12, 13, 14, 15), 12 => array(7, 8, 9, 11, 13),
        13 => array(11, 12, 14), 14 => array(11, 13, 15), 15 => array(10, 11, 14),
    ),
    'colors' => array('red','green','blue'),
);

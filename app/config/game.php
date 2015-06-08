<?php
return array(

    'make_log' => TRUE,
    'new_game_log' => TRUE,
    'make_log_stage' => 0, // 0 - всех этапов, 1-только первого, 2 - только втогоро

    'number_participants' => 3,
    'time_activity' => time()-(1000*3*60),
    'number_places_on_map' => 15,
    'map_empty_place_lives' => 1,
    'map_capital_place_lives' => 3,
    'question_time_seconds' => 10,
    'normal_question_answers_count' => 4,

    'adjacent_places' => array(
        1 => array(2, 14), 2 => array(1, 14, 15, 4), 3 => array(4, 13, 12, 10), 4 => array(2, 15, 13, 3),
        5 => array(9, 7, 8), 6 => array(10, 9, 12, 7), 7 => array(6, 9, 5, 12, 8),
        8 => array(7, 12, 11), 9 => array(10, 6, 7, 5), 10 => array(3, 12, 6, 9),
        11 => array(8, 12), 12 => array(13, 3, 10, 6, 7, 8, 11),
        13 => array(15, 4, 3, 12), 14 => array(1, 2, 15), 15 => array(14, 2, 4, 13),
    ),
    'colors' => array('red','green','blue'),
    'bots_ids' => array(3,4),

    'disconnect_user_timeout' => 120,
    'remove_user_timeout_in_game_wait' => 15
);

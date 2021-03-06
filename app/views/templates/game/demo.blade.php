<?
/**
 * TEMPLATE_IS_NOT_SETTABLE
 */
$game_id = is_object($game) ? $game->id : 0;
?>

@extends('templates/demo')
@section('style')
@stop
@section('content')
    <p xmlns="http://www.w3.org/1999/html">{{ Auth::user()->name }}, добро пожаловать в игру.</p>
    <p id="js-bot"></p>
    <ul>
        @if(!$game_id)
            <li><a id="js-start-game" href="javascript:void(0);">Начать игру</a></li>
        @endif
        <li {{ !$game_id ? 'style="display: none;"' : '';  }}><a id="js-update-game" href="javascript:void(0);">Обновить игру</a></li>
        <li {{ !$game_id ? 'style="display: none;"' : '';  }}><a id="js-question-quiz-game" href="javascript:void(0);">Запросить квиз вопрос</a></li>
        <li {{ !$game_id ? 'style="display: none;"' : '';  }}><a id="js-question-result" href="javascript:void(0);">Запросить результат вопроса</a></li>

        <li {{ !$game_id ? 'style="display: none;"' : '';  }}><a id="js-question-normal-game" href="javascript:void(0);">Запросить нормальный вопрос</a></li>

        <li {{ !$game_id ? 'style="display: none;"' : '';  }}><a id="js-users-questions-result" href="javascript:void(0);">Запросить результаты ответов пользователей</a></li>

        <li {{ !$game_id ? 'style="display: none;"' : '';  }}><a id="js-over-game" href="javascript:void(0);">Завершить игру</a></li>
        <li><a href="{{ URL::route('logout') }}">Выйти</a></li>
    </ul>
    <div id="russia-map" style="display: none;">
        <div id="map-block-template" class="territory-block" style="width: 90px;border: 1px solid; float: left;"></div>
        <div id="russia-map-blocks"></div>
    </div>
    <div style="clear: both;"></div>
@stop
@section('game')
    <div id="game-info" style="display: none">
        <game id="game-id">{{ $game_id }}</game>
    </div>
    <div id="quiz-question-block" style="display: none;">
        <hr>
        <p id="quiz-question-timer">Осталось секунд: <span></span></p>
        Квиз вопрос: <div id="quiz-question-text"></div>
        <form id="quiz-question-form">
            <input type="text" id="quiz-question-answer" autocomplete="off" value="" maxlength="6" />
            <button type="submit">OK</button>
        </form>
    </div>
    <div id="normal-question-block" style="display: none;">
        <hr>
        <p id="normal-question-timer">Осталось секунд: <span></span></p>
        Нормальный вопрос: <div id="normal-question-text"></div>
        <div id="normal-question-answers"></div>
        <form id="normal-question-form">
            <button type="submit">OK</button>
        </form>
    </div>
@stop
@section('json_response')
    <hr>
    <p style="clear: both;">ИГРА</p>

    <p style="clear: both;">Текущая игра: <span id="game-number">{{ $game_id }}</span></p>
    <p style="clear: both;">Мой цвет: <span id="user-color"></span></p>
    <p style="clear: both;">Сейчас ходит: <span id="next-step-id"></span></p>
    <p style="clear: both;">Текущий статус: <span id="game-status"></span></p>
    <p style="clear: both;">Текущий этап: <span id="game-stage"></span></p>
    <p style="clear: both;">Доступно ходов: <span id="user-available-steps"></span></p>
    <p style="clear: both;">Сделано ходов: <span id="user-make-steps"></span></p>

    <p style="clear: both;">Игрок</p>
    <textarea id="js-user-response" style="width: 800px; height: 100px;"></textarea>

    <p style="clear: both;">Ответ сервера</p>
    <textarea id="js-server-response" style="width: 800px; height: 150px;"></textarea>
    <p style="clear: both;">Уведомление от сервера</p>
    <textarea id="js-server-notification" style="width: 800px; height: 50px;"></textarea>
@stop
@section('scripts')
    {{ HTML::script('private/js/vendor/jquery.min.js') }}
    {{ HTML::script('theme/js/game.js') }}
@stop
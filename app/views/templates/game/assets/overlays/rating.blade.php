<div id="help-stage-2" class="popup with-tabs" style="width:440px;">
    <a href="#close" class="close"></a>
    <div class="tabs-btn">
        <a href="" class="active tab">Этап 2. Захват земель</a>
    </div>
    <div class="tabs">
        <div class="tab active">
            <p>На этом этапе участники распределяют между собой территории.</p>
            
            <p>Все территории распределены между игроками, начинается этап захвата земель.</p>
            <p>
                <img src="{{ asset(Config::get('site.theme_path').'/images/ico-coins.png') }}">
                Каждой территории назначается стоимость 200 очков, она увеличивается при захвате.
            </p>
            <p>
                <img src="{{ asset(Config::get('site.theme_path').'/images/ico-attack.png') }}">
                Эти очки получит противник при захвате территории.
            </p>
            <p>
                <img src="{{ asset(Config::get('site.theme_path').'/images/ico-capital.png') }}">
                У каждого игрока есть своя столица.
                Когда игрок захватывает столицу другого игрока, то получает все земли проигравшего.
            </p>
            <p>
                <img src="{{ asset(Config::get('site.theme_path').'/images/ico-1st-place.png') }}">
                Победителем станет игрок, набравший больше всех очков.
            </p>
        </div>
    </div>
</div>

<div id="help-stage-1" class="popup with-tabs" style="width:440px;">
    <a href="#close" class="close"></a>
    <div class="tabs-btn">
        <a href="" class="active tab">Этап 1. Распределение земель</a>
    </div>
    <div class="tabs">
        <div class="tab active">
            <p>На этом этапе участники распределяют между собой территории.</p>
            
            <p>
                <img src="{{ asset(Config::get('site.theme_path').'/images/ico-question.png') }}">
                Всем игрокам задаются одинаковые вопросы
            </p>
            <p>
                <img src="{{ asset(Config::get('site.theme_path').'/images/ico-flag.png') }}">
                Выигравший раунд игрок получает на выбор 2 территории. Занявший второе место — 1 территорию.
            </p>
            <p>
                <img src="{{ asset(Config::get('site.theme_path').'/images/ico-manometer.png') }}">
                Больше территорий захватит игрок, который будет отвечать точнее и быстрее других
            </p>
        </div>
    </div>
</div>

<div id="rating" class="popup with-tabs">
    <a href="#close" class="close"></a>
    <div class="tabs-btn">
        <a href="" class="active tab">Текущий рейтинг</a>
        <a href="" class="tab">Общий рейтинг</a>
    </div>
    <div class="tabs">
        <div class="tab active">
            <table>
                <tr>
                    <td class="n"></td>
                    <td class="name">Имя игрока</td>
                    <td class="wind">Побед</td>
                    <td class="score">Баллов</td>
                </tr>
            @foreach($month_rating as $index => $rating)
                <tr>
                    <td class="n">{{ $index+1 }}</td>
                    <td class="name">{{ $rating['user_name'] }}</td>
                    <td class="wind">{{ $rating['wins'] }}</td>
                    <td class="score">{{ $rating['rating'] }}</td>
                </tr>
            @endforeach
            </table>
        </div>
        <div class="tab">
            <table>
                <tr>
                    <td class="n"></td>
                    <td class="name">Имя игрока</td>
                    <td class="wind">Побед</td>
                    <td class="score">Баллов</td>
                </tr>
            @foreach($total_rating as $index => $rating)
                <tr>
                    <td class="n">{{ $index+1 }}</td>
                    <td class="name">{{ $rating['user_name'] }}</td>
                    <td class="wind">{{ $rating['wins'] }}</td>
                    <td class="score">{{ $rating['rating'] }}</td>
                </tr>
            @endforeach
            </table>
        </div>
    </div>
</div>
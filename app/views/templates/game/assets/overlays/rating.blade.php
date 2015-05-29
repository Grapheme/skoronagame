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
<div id="winer" class="popup">
    <a href="#menu" class="close"></a>
    <div class="title">Поздравляем победителя!</div>
    <div class="places">
        <div class="second">
            <div class="ava">
                <div class="mask"></div>
                <div class="img" style="background-image: url({{ asset(Config::get('site.theme_path').'/images/ava.png') }}); width:86px; height:98px;"></div>
            </div>
            <div class="name">Николай</div>
            <div class="place">занял 2 место</div>
        </div>
        <div class="first">
            <div class="ava">
                <div class="mask"></div>
                <div class="img" style="background-image: url({{ asset(Config::get('site.theme_path').'/images/ava.png') }}); width:86px; height:98px;"></div>
            </div>
            <div class="name">Наполеон</div>
        </div>
        <div class="third">
            <div class="ava">
                <div class="mask"></div>
                <div class="img" style="background-image: url({{ asset(Config::get('site.theme_path').'/images/ava.png') }}); width:86px; height:98px;"></div>
            </div>
            <div class="name">Максим</div>
            <div class="place">занял 3 место</div>
        </div>
    </div><img src="{{ asset(Config::get('site.theme_path').'/images/podium.png') }}" style="margin-top: -40px;"><br>
    <center><a href="" class="btn">Закрыть</a></center>
</div>
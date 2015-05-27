<?php
$badges = GameBadges::orderBy('name')->get();
$profile = Accounts::where('id', Auth::user()->id)->with('games.game','badges','social')->first();
$winners = GameUser::where('user_id',Auth::user()->id)->where('place',1)->count();
$tours = GameUser::where('user_id',Auth::user()->id)->count();
$points = GameUserRating::where('user_id',Auth::user()->id)->sum('rating');
$user_badges = array();
if (count($profile->games)):
    foreach ($profile->games as $games):
        if (isset($games->game->status_over) && $games->game->status_over):
            $points += $games->points;
            $tours++;
            if ($games->place == 1):
                $winners++;
            endif;
        endif;
    endforeach;
endif;
if (count($profile->badges)):
    foreach ($profile->badges as $badge):
        $user_badges[$badge->badge_id] = $badge->badge_id;
    endforeach;
endif;
?>
<div id="profile" class="popup">
    <a href="#close" class="close"></a>
    <section class="n1">
        <div class="center">
            <div class="ava">
                <div class="mask"></div>
            @if(!empty($profile->social) && !empty($profile->social->photo_big))
                <div class="img" style="background-image: url({{ $profile->social->photo_big }}); width:86px; height:98px;"></div>
            @else
                <div class="img" style="background-image: url({{ asset(Config::get('site.theme_path').'/images/ava.png') }}); width:86px; height:98px;"></div>
            @endif
            </div>
            <div class="info">
                <div class="name">{{ $profile->name }}</div>
                <div class="row">
                    <div class="ico cup"></div>
                    Число побед: <strong>{{ $winners }}</strong>
                    <span class="grey">| {{ $tours }} {{ Lang::choice('турнир|турнира|турниров',$tours) }}</span>
                </div>
                <div class="row">
                    <div class="ico ruby"></div>Всего баллов: <strong>{{ number_format($points,0,'.',' ') }}</strong>
                </div>
            </div>
        </div>
    </section>
    <hr>
    @if($badges->count())
    <section class="n2">
        <div class="achivments">
            @foreach($badges as $badge)
            <div class="unit {{ isset($user_badges[$badge->id]) ? ' earned' : '' }}">
                <div class="shild"></div>
                <div class="label">{{ $badge->name }}</div>
            </div>
            @endforeach
        </div>
    </section>
    @endif
    <hr>
    <section class="n3">
        <div class="small-title">ПРИГЛАСИТЬ ДРУЗЕЙ В ИГРУ</div>
        <div class="socials">
            <a href=""><img src="{{ asset(Config::get('site.theme_path').'/images/ico-vk.png') }}"></a>
            <a href=""><img src="{{ asset(Config::get('site.theme_path').'/images/ico-ok.png') }}"></a>
            <a href=""><img src="{{ asset(Config::get('site.theme_path').'/images/ico-fb.png') }}"></a>
        </div>
    </section><br>
    <div class="buttons">
        <div class="left">
            <a href="#rating">Рейтинг</a>
            <a href="#help">Помощь</a>
            <a href="#new-password">Сменить пароль</a>
        </div>
        <div class="right">
            <a href="" class="music"></a>
            <a href="" class="sfx"></a>
        </div>
    </div>
</div>
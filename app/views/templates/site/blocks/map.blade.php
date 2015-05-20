<div id="user-list">
  <div class="user red">
    <div class="ava">
      <div class="mask"></div><img src="http://lorempixel.com/70/80/">
    </div>
    <div class="name">Вы</div>
    <div class="points">00000</div>
  </div>
  <div class="user blue">
    <div class="ava">
      <div class="mask"></div><img src="http://lorempixel.com/70/80/">
    </div>
    <div class="name">Николай Бульбулятор</div>
    <div class="points">00000</div>
  </div>
  <div class="user green">
    <div class="ava">
      <div class="mask"></div><img src="http://lorempixel.com/70/80/">
    </div>
    <div class="name">Максим Фаггот</div>
    <div class="points">00000</div>
  </div>
</div>
<div id="map">
    <div class="areas">
        @for ($i = 15; $i > 0; $i--)
        <div id="area-{{ $i }}" class="area">
            <div class="undeground"></div>
            <div class="ground"></div>
            <div class="countur">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/'.$i.'_contyr.svg')) }}
            </div>
            <div class="glow">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/'.$i.'_glow.svg')) }}
            </div>
            <div class="objects"></div>
        </div>
        @endfor
    </div>
    <img src="{{ asset(Config::get('site.theme_path').'/images/bg.jpg') }}" class="bg">
</div>
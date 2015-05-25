<div id="user-list">
  <div class="user red">
    <div class="ava">
      <div class="mask"></div><img width="70" height="80" src="{{ asset(Config::get('site.theme_path').'/images/ava.png') }}">
    </div>
    <div class="name"></div>
    <div class="points"></div>
  </div>
  <div class="user blue">
    <div class="ava">
      <div class="mask"></div><img width="70" height="80" src="{{ asset(Config::get('site.theme_path').'/images/ava.png') }}">
    </div>
    <div class="name"></div>
    <div class="points"></div>
  </div>
  <div class="user green">
    <div class="ava">
      <div class="mask"></div><img width="70" height="80" src="{{ asset(Config::get('site.theme_path').'/images/ava.png') }}">
    </div>
    <div class="name"></div>
    <div class="points"></div>
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
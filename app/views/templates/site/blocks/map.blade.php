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
<div class="infowindow-holder">
    <div class="infowindow-small">
        
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
    <div class="infowindow tour1">
        <div class="top">Этап 1: Распределение земель</div>
    </div>
    <div class="infowindow tour2">
        <div class="top">Этап 2: Захват земель</div>
        <div class="middle">
          <div class="text">Ход игрока</div>
          <div class="tour n1">
            <div class="flags">
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
            </div>
            <div class="title">1 тур</div>
          </div>
          <div class="tour n2">
            <div class="flags">
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
            </div>
            <div class="title">2 тур</div>
          </div>
          <div class="tour n3">
            <div class="flags">
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
            </div>
            <div class="title">3 тур</div>
          </div>
          <div class="tour n4">
            <div class="flags">
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
              <div class="flag">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/flag.svg')) }}
              </div>
            </div>
            <div class="title">4 тур</div>
          </div>
        </div>
        <div class="bottom"></div>
    </div>
</div>

<div class="temp-map"></div>
<div id="map">
    <div class="areas">
        @for ($i = 1; $i < 15; $i++)
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
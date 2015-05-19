<div class="temp-map"></div>
<div id="map">
    <div class="areas">
        <div id="area-12" class="area">
            <div class="undeground"></div>
            <div class="ground"></div>
            <div class="countur">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/12_contyr.svg')) }}
            </div>
            <div class="glow">
                {{ File::get(public_path(Config::get('site.theme_path').'/images/12_glow.svg')) }}
            </div>
            <div class="objects"></div>
        </div>
    </div>
    <img src="{{ asset(Config::get('site.theme_path').'/images/bg.jpg') }}" class="bg">
</div>
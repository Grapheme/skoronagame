<div class="temp-map"></div>
<div id="map">
    <div class="areas">
        <div id="area-12" class="area">
            <div class="undeground"></div>
            <div class="ground"></div>
            <div class="countur">
                {{ File::get(app_path('views/templates/site/assets/svg/first.xml')) }}
            </div>
            <div class="glow">
                {{ File::get(app_path('views/templates/site/assets/svg/second.xml')) }}
            </div>
            <div class="objects"></div>
        </div>
    </div>
    <img src="{{ asset(Config::get('site.theme_path').'/images/bg.jpg') }}" class="bg">
</div>
<?
use yii\helpers\Html;
?>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>

    <style>
        #map-canvas {
            height: 100%;
            margin: 0px;
            padding: 0px;
            width: <?=$conf['width']?>;
            height: <?=$conf['height']?>;
        }
    </style>

<div id="map">
    <div id="panel">
<!--        <input id="address" type="textbox" value="Sydney, NSW">-->
        <input type="button" value="Найти на карте по адресу" onclick="codeAddress()">
    </div>
    <div id="map-canvas"></div>
    <br/>
    <div style="display: none;">
        <?= Html::activeLabel($model, 'lat') ?>
        <?= Html::activeInput('text',$model, 'lat',['class'=>'ggl_lat','readonly'=>true]); ?>
        <br/>
        <?= Html::activeLabel($model, 'lon') ?>
        <?= Html::activeInput('text',$model, 'lon',['class'=>'ggl_lon','readonly'=>true]);?>
    </div>
</div>

<script>
    var geocoder;
    var map;
    var marker = null;
    var markers =[];
    var lat_input = document.getElementsByClassName('ggl_lat')[0];
    var lon_input = document.getElementsByClassName('ggl_lon')[0];

    function initialize() {
        geocoder = new google.maps.Geocoder();
        var latlng = new google.maps.LatLng(55.755826, 37.6173);
        var mapOptions = {
            zoom: 8,
            center: latlng
        }
        map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

        var loc = new google.maps.LatLng(lat_input.value,lon_input.value);
        addMarker(loc,true);

        google.maps.event.addListener(map, 'click', function(event) {
            addMarker(event.latLng,true);
        });
    }

    function addMarker(location,addCoord) {

        if(typeof(addCoord)==='undefined') addCoord = false;

        if(addCoord && marker) marker.setMap(null);

        marker = new google.maps.Marker({
            map: map,
            draggable:true,
            animation: google.maps.Animation.DROP,
            position: location
        });

        if(addCoord) {
            setCoordinate(location);}
        else
            markers.push(marker);
    }

    function setCoordinate(location) {
        lat_input.value = location.k;
        lon_input.value = location.B;
    }

    function codeAddress() {
        var address = document.getElementsByClassName('ggl_address')[0].value;

        geocoder.geocode( { 'address': address}, function(results, status) {

            if (status == google.maps.GeocoderStatus.OK) {


                map.setCenter(results[0].geometry.location);

                addMarker(results[0].geometry.location,true);


            } else {
                alert('Не удалось найти точку на карте, укажите точку вручную');
            }
        });
    }

    google.maps.event.addDomListener(window, 'load', initialize);

</script>
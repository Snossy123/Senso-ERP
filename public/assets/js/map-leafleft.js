$(function() {
	'use strict';

	var mapboxToken = window.config && window.config.mapboxToken;

	if (!mapboxToken) {
		console.warn('Mapbox token not configured. Set MAPBOX_TOKEN in your .env file.');
		return;
	}

	var tileUrl = 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=' + mapboxToken;
	var attribution = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
		'<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
		'Imagery © <a href="http://mapbox.com">Mapbox</a>';

	function createMap(id, center, zoom, extras) {
		var map = L.map(id).setView(center, zoom);
		L.tileLayer(tileUrl, {
			maxZoom: 18,
			attribution: attribution,
			id: 'mapbox.streets'
		}).addTo(map);

		if (extras) {
			extras(map);
		}

		return map;
	}

	createMap('leaflet1', [51.505, -0.09], 13);

	createMap('leaflet2', [51.505, -0.09], 13, function(map) {
		L.marker([51.5, -0.09]).addTo(map).bindPopup('<b>Hello world!</b><br />I am a popup.').openPopup();
	});

	createMap('leaflet3', [51.505, -0.09], 13, function(map) {
		L.circle([51.508, -0.11], {
			color: 'red',
			fillColor: '#f03',
			fillOpacity: 0.5,
			radius: 500
		}).addTo(map);
	});
});

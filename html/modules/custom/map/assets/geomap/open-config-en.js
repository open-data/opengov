var wet_boew_geomap = {
	basemap : {
		title: 'Basic Map',
		type: 'esri',
		url: 'https://geoappext.nrcan.gc.ca/arcgis/rest/services/BaseMaps/provinces1c/MapServer/export',
		options: { singleTile: false, ratio: 1.0, projection: 'EPSG:3978', fractionalZoom: true },
		mapOptions: {
			maxExtent: '-3000000.0, -800000.0, 4000000.0, 3900000.0',
			maxResolution: 'auto',
			projection: 'EPSG:3978',
			restrictedExtent: '-3000000.0, -800000.0, 4000000.0, 3900000.0',
			units: 'm',
			displayProjection: 'EPSG:4269',
			numZoomLevels: 5
		}
	},
	overlays : [
		{
			title: 'Open Municipalities',
			caption: 'A layer showing open municipalities across Canada.',
			type: 'geojson',
			url: '/modules/custom/map/assets/geomap/open-cities-en.geojson',
			visible: true,
			datatable: true,
			tab: true,
			attributes: {
				name: 'Name',
				city: 'Municipality',
				province: 'Province',
				url: 'Website',
			},
			popups: true,
			popupsInfo: {
				id: "cities",
				height: 200,
				width: 300,
				close: true,
				content: "<p><strong style=\"font-size: 1.1em;\">_Name</strong><br/>" +
					"<em>_Municipality, _Province</em></p>" +
					"<p>_Website</p>"
			},
      tooltips: true,
      tooltipText: 'Name',
			style: {
					'pointRadius': '6',
					'strokeColor': '#ffffff',
					'fillColor': '#df7e68',
					'fillOpacity': '.75',
					'strokeWidth': '1.0'
			}
		},
		{
			title: 'Open Initiatives',
			caption: 'A layer showing national open data initiatives across Canada.',
			type: 'geojson',
			url: '/modules/custom/map/assets/geomap/open-national-en.geojson',
			visible: true,
			datatable: true,
			tab: true,
			attributes: {
				name: 'Name',
				city: 'City',
				province: 'Province',
				url: 'Website',
			},
			popups: true,
			popupsInfo: {
				id: "other",
				height: 200,
				width: 300,
				close: true,
				content: "<p><strong style=\"font-size: 1.1em;\">_Name</strong><br/>" +
					"<em>_City, _Province</em></p>" +
					"<p>_Website</p>"
			},
      tooltips: true,
      tooltipText: 'Name',
			style: {
					'pointRadius': '6',
					'strokeColor': '#ffffff',
					'fillColor': '#e1a61a',
					'fillOpacity': '1.0',
					'strokeWidth': '1.0'
			}
		},{
			title: 'Open Provinces',
			caption: 'A layer showing open provinces across Canada.',
			type: 'geojson',
			url: '/modules/custom/map/assets/geomap/open-provinces-en.geojson',
			visible: true,
			datatable: true,
			tab: true,
			attributes: {
				name: 'Name',
				url: 'Website',
			},
			popups: true,
			popupsInfo: {
				id: "provinces",
				height: 200,
				width: 300,
				close: true,
				content: "<p><strong style=\"font-size: 1.1em;\">_Name</strong></p>" +
					"<p>_Website</p>"
			},
      tooltips: true,
      tooltipText: 'Name',
			style: {
				strokeColor: '#ffffff',
				fillColor: '#466b72',
				fillOpacity: .75,
				strokeWidth: 0.5
			}
		}
	]
};

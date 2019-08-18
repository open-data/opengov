var wet_boew_geomap = {
  basemap : {
    title: 'Carte de base',
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
      numZoomLevels: 2
    }
  },
  overlays : [
    {
      title: 'Municipalités ouvertes',
      caption: 'Une couche montrant les municipalités ouvertes à travers le Canada.',
      type: 'geojson',
      url: '/modules/custom/map/assets/geomap/open-cities-fr.geojson',
      visible: true,
      datatable: true,
      tab: true,
      attributes: {
        name: 'Nom',
        city: 'Municipalité',
        province: 'Province',
        url: 'Site Internet',
      },
      popups: true,
      popupsInfo: {
        id: "cities",
        height: 200,
        width: 300,
        close: true,
        content: "<p><strong style=\"font-size: 1.1em;\">_Nom</strong><br/>" +
          "<em>_Municipalité, _Province</em></p>" +
          "<p>_Site Internet</p>"
      },
      tooltips: true,
      tooltipText: 'Nom',
      style: {
        'pointRadius': '6',
        'strokeColor': '#ffffff',
        'fillColor': '#df7e68',
        'fillOpacity': '.75',
        'strokeWidth': '1.0'
      }
    },
    {
      title: 'Initiatives ouvertes',
      caption: 'Une couche montrant les initiatives ouvertes à travers le Canada.',
      type: 'geojson',
      url: '/modules/custom/map/assets/geomap/open-national-fr.geojson',
      visible: true,
      datatable: true,
      tab: true,
      attributes: {
        name: 'Nom',
        city: 'Ville',
        province: 'Province',
        url: 'Site Internet',
      },
      popups: true,
      popupsInfo: {
        id: "other",
        height: 200,
        width: 300,
        close: true,
        content: "<p><strong style=\"font-size: 1.1em;\">_Nom</strong><br/>" +
          "<em>_Ville, _Province</em></p>" +
          "<p>_Site Internet</p>"
      },
      tooltips: true,
      tooltipText: 'Nom',
      style: {
        'pointRadius': '6',
        'strokeColor': '#ffffff',
        'fillColor': '#e1a61a',
        'fillOpacity': '1.0',
        'strokeWidth': '1.0'
      }
    },{
      title: 'Provinces ouvertes',
      caption: 'Une couche montrant les provinces ouverts à travers le Canada.',
      type: 'geojson',
      url: '/modules/custom/map/assets/geomap/open-provinces-fr.geojson',
      visible: true,
      datatable: true,
      tab: true,
      attributes: {
        name: 'Nom',
        url: 'Site Internet',
      },
      popups: true,
      popupsInfo: {
        id: "provinces",
        height: 200,
        width: 300,
        close: true,
        content: "<p><strong style=\"font-size: 1.1em;\">_Nom</strong></p>" +
          "<p>_Site Internet</p>"
      },
      tooltips: true,
      tooltipText: 'Nom',
      style: {
        strokeColor: '#ffffff',
        fillColor: '#466b72',
        fillOpacity: .75,
        strokeWidth: 0.5
      }
    }
  ]
};

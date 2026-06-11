<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch border-radius-10">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <h5 class="font-weight-bold">{{ $pageTitle }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card border-radius-10">
                    <div class="card-body">
                        <div class="col-md-3 p-2">
                            {{ Form::select('region_id', [], request('region_id'), [
                                'data-ajax--url' => route('ajax-list', ['type' => 'region']),
                                'data-ajax--cache' => true,
                                'data-ajax--delay' => 250,
                                'class' => 'form-control select2js',
                                'data-placeholder' => __('message.select_field', ['name' => __('message.region')]),
                                'data-allow-clear' => 'true',
                                'id' => 'regionSearch',
                            ]) }}
                        </div>
                        <div class="border-radius-10" id="map" style="height: 600px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    @section('bottom_script')
    <script src="https://maps.googleapis.com/maps/api/js?key={{env('GOOGLE_MAP_KEY')}}&v=3.64&libraries=visualization"></script>
    <script>
        let map;
        let heatmap;
        let polygonShape;
        //let allPoints = @json($heatmapPoints);

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 5,
                center: { lat: 20.947940, lng: 72.955786 },
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });

            // Load initial data
            fetchHeatmapData(); // by default: all
        }

        function fetchHeatmapData(regionId = '') {
            fetch(`{{ route('heatmap') }}?region_id=${regionId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                drawHeatmap(data.points);

                // if (polygonShape) {
                //     polygonShape.setMap(null);
                //     polygonShape = null;
                // }

                // if (data.polygon && data.polygon.length > 0) {
                //     polygonShape = new google.maps.Polygon({
                //         paths: data.polygon,
                //         strokeColor: '#FF0000',
                //         strokeOpacity: 0.8,
                //         strokeWeight: 2,
                //         fillColor: '#FF0000',
                //         fillOpacity: 0.2
                //     });
                //     polygonShape.setMap(map);
                // }

                if (data.center && data.center.lat && data.center.lng) {
                    map.setZoom(10);
                    map.setCenter({ lat: data.center.lat, lng: data.center.lng });
                } else {
                    map.setZoom(5);
                    map.setCenter({ lat: 20.947940, lng: 72.955786 });
                }
            });
        }

        function drawHeatmap(points) {
            const latLngs = points.map(p => new google.maps.LatLng(parseFloat(p.lat), parseFloat(p.lng)));

            if (heatmap) heatmap.setMap(null); // remove old heatmap

            heatmap = new google.maps.visualization.HeatmapLayer({
                data: latLngs,
                radius: 40,
                gradient: [
                    'rgba(255, 0, 0, 0)',
                    'rgba(255, 0, 0, 0.4)',
                    'rgba(255, 0, 0, 0.6)',
                    'rgba(255, 0, 0, 0.8)',
                    'rgba(255, 0, 0, 1)'
                ]
            });

            heatmap.setMap(map);
        }

        $(document).ready(function () {
            initMap();

            $('#regionSearch').on('change', function () {
                const selectedRegion = $(this).val() || '';
                fetchHeatmapData(selectedRegion);
            });
        });
    </script>
    @endsection

</x-master-layout>
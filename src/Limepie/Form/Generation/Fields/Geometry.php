<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Geometry extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        //$value = \htmlspecialchars((string) $value);

        $result = \json_decode($value, true);

        $geometry = '';
        //\pr($value);

        if ($result) {
            $geometry = '(' . $result[0]['geometry']['location']['lat'] . ' ' . $result[0]['geometry']['location']['lng'] . ')';
        } else {
            if (0 === \strlen($value) && true === isset($property['default'])) {
                $geometry = (string) $property['default'];
            }
        }
        $callback = '';

        $default = $property['default'] ?? '';
        $keyName = \addcslashes($key, '[]');

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        $disabled = '';

        if (isset($property['disabled']) && $property['disabled']) {
            $disabled = ' disabled="disabled"';
        }

        $prepend = '';

        if (isset($property['prepend']) && $property['prepend']) {
            $prepend = <<<EOD
<div class="input-group-prepend">
<span class="input-group-text">{$property['prepend']}</span>
</div>
EOD;
        }

        $placeholder = '';

        if (isset($property['placeholder']) && $property['placeholder']) {
            $placeholder = ' placeholder="' . $property['placeholder'] . '"';
        }

        $id = 'f' . \uniqid();

        if ($geometry) {
            $callback = $id . '_initMap';
        }

        $value1 = $value2 = $value3 = $value4 = '';
        $html   = <<<EOT
        <div class='input-group input-group-postcode col-md-12'>
            {$prepend}
            <input type="text" class="form-control " readonly="readonly" value="{$geometry}" id="{$id}_geometry" placeholder="" />
            <span class="btn-group input-group-btn">
                <button class="btn btn-primary" type="button" onclick="{$id}_initMap(this)"><span data-feather="search"></span></button>
            </span>
        </div>

        <div class="input-group input-group-postcode mt-1" id="{$id}_map">
        </div>

        <input type="hidden" name="{$key}" value='{$value}' data-default="{$default}" />

        <input id="{$id}-searchbox" class="controls" type="text" placeholder="Enter a location" style="width: 400px; font-size: 0.85rem;z-index: 0;position: absolute; background: white; padding: 5px 10px; margin: 2px; border: 1px solid gray; border-radius: 0.5rem; visibility: hidden" onkeypress="return event.keyCode != 13;">

<script>

var map;
var service;
var infowindow;
var marker;
var geocoder;

function {$id}_initMap(s) {
    if(!s) {
        s = $('#{$id}_geometry');
    }
    var address = $(s).closest('.fieldset').find('.address_road').val();
    console.log('address', $(s).closest('.fieldset'));
    address = address || $(s).closest('.fieldset').find('.typing').val()
    $('.message_{$id}').remove();
    if(!address) {
        // var parent = $(s);
        // parent.closest('.wrap-element').append('<div class="message message_{$id}" style="color:red">주소를 먼저 입력해주세요.</div>');
        // return;
    }
    if(address) {
        $('.{$id}-searchbox').val(address);
    }
    if(!map) {
        var sydney = new google.maps.LatLng(-33.867, 151.195);

        map = new google.maps.Map(
            document.getElementById('{$id}_map'),
            {
                center: sydney,
                zoom: 15,
                mapTypeId: 'roadmap',
                mapTypeControl: false,
                streetViewControl: false
            }
        );

        infowindow = new google.maps.InfoWindow();

        // Create the search box and link it to the UI element.
        var input = document.getElementById('{$id}-searchbox');
        var searchBox = new google.maps.places.SearchBox(input);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

        // Bias the SearchBox results towards current map's viewport.
        map.addListener('bounds_changed', function() {
          searchBox.setBounds(map.getBounds());
        });
        var markers = [];

        // Listen for the event fired when the user selects a prediction and retrieve
        // more details for that place.
        searchBox.addListener('places_changed', function() {
          var places = searchBox.getPlaces();

          if (places.length == 0) {
            return;
          }

          // Clear out the old markers.
        //   markers.forEach(function(marker) {
        //     marker.setMap(null);
        //   });
        //   markers = [];

          // For each place, get the icon, name and location.
          var bounds = new google.maps.LatLngBounds();
          places.forEach(function(place) {
            if (!place.geometry) {
              console.log("Returned place contains no geometry");
              return;
            }
            var icon = {
              url: place.icon,
              size: new google.maps.Size(71, 71),
              origin: new google.maps.Point(0, 0),
              anchor: new google.maps.Point(17, 34),
              scaledSize: new google.maps.Size(25, 25)
            };

            // Create a marker for each place.
            // markers.push(new google.maps.Marker({
            //   map: map,
            //   icon: icon,
            //   title: place.name,
            //   position: place.geometry.location
            // }));

            if(marker) {
                marker.setPosition(place.geometry.location)
            } else {
                createMarker(place);
            }


            if (place.geometry.viewport) {
              // Only geocodes have viewport.
              bounds.union(place.geometry.viewport);
            } else {
              bounds.extend(place.geometry.location);
            }
          });
          map.fitBounds(bounds);
        });


        // var naviDiv = document.getElementById('{$id}_status');
        // map.controls[google.maps.ControlPosition.BOTTOM_LEFT].push(naviDiv);
    }
    // google.maps.event.addListener(map, 'click', function(event) {
    //     console.log('drag end', event.latLng.toString(), event.latLng, event);

    //     placeMarker(event.latLng);
    // });

    if(address) {

        var request = {
            query: address,
            fields: ['name', 'geometry'],
        };

        geocoder = new google.maps.Geocoder();

        service = new google.maps.places.PlacesService(map);

        service.findPlaceFromQuery(request, function(results, status) {
        if (status === google.maps.places.PlacesServiceStatus.OK) {
            // for (var i = 0; i < results.length; i++) {
            // createMarker(results[i]);
            // }

            if(!marker) {
                createMarker(results[0]);
            }
            map.setCenter(results[0].geometry.location);
            $('#{$id}_geometry').val(results[0].geometry.location);
            console.log('geometry13', results);
            $('[name="{$keyName}"]').val(JSON.stringify(results));

            var form = $('[name="{$keyName}"]').closest( "form" )[ 0 ];
            //alert(form);
            var validator = $.data( form, "validator" );
            validator.isFocus = true;
            validator.elementValid2($('[name="{$keyName}"]'));
        }
        });
    }
        document.getElementById('{$id}_map').style.height = "200px";

}
function searchByAddress(address){ //주소검색
    if(address.length<1){return false;}
    geocoder.geocode( {'address': address}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
            map.setCenter(results[0].geometry.location);
        } else {
            alert("Geocode was not successful for the following reason: " + status);
        }
    });
}
function searchByLatLng(latLng){
    geocoder.geocode( {'latLng': latLng}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
            map.setCenter(results[0].geometry.location);
        } else {
            alert("Geocode was not successful for the following reason: " + status);
        }
    });
}
function placeMarker(location) {
    marker = new google.maps.Marker({
        position: location,
        draggable:true,
        map: map
    });

    map.setCenter(location);
}
function createMarker(place) {

    marker = new google.maps.Marker({
        map: map,
        draggable: true,
        position: place.geometry.location
    });
    google.maps.event.addListener(infowindow,'closeclick',function() {
        //map.setCenter(marker.getPosition());
    });


    google.maps.event.addListener(marker, 'click', function(event) {
        var value = $('[name="{$keyName}"]').val();

        var name = place.name;
        if(value) {
            var json = JSON.parse(value);
            console.log('asdf', json);
            name = json[0].formatted_address;
        }
        // infowindow.setContent(name);
        // infowindow.open(map, marker);

        var latitude = event.latLng.lat();
        var longitude = event.latLng.lng();
        console.log( latitude + ', ' + longitude );
    });
    google.maps.event.addListener(marker, 'dragend', function() {
		//map.setCenter(marker.getPosition());

        var request = {
            query: map.center.toString(),
            fields: ['name', 'geometry'],
        };

        geocoder = new google.maps.Geocoder();

        geocoder.geocode( {'latLng': marker.getPosition()}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                //map.setCenter(results[0].geometry.location);
                console.log('geometry11', results);

                // for (var i = 0; i < results.length; i++) {
                //     createMarker(results[i]);
                //     }

                    //createMarker(results[0]);

                //infowindow.setContent(results[0].formatted_address);

                //$('.{$id}_statustext').html(results[0].formatted_address);
                $('#{$id}-searchbox').val(results[0].formatted_address);

                //infowindow.open(map, marker);

                //map.setCenter(results[0].geometry.location);
                $('#{$id}_geometry').val(results[0].geometry.location);
                console.log('geometry12', results);
                $('[name="{$keyName}"]').val(JSON.stringify(results));
                var form = $('[name="{$keyName}"]').closest( "form" )[ 0 ];
                //alert(form);
                var validator = $.data( form, "validator" );
                validator.isFocus = true;
                validator.elementValid2($('[name="{$keyName}"]'));

            } else {
                alert("Geocode was not successful for the following reason: " + status);
            }
        });
    });
    google.maps.event.addListener(map, 'center_changed', function() {
        //syncInfo();
    });

    // google.maps.event.addListener(map, 'drag', function(event){
    //     var bounds =  map.getBounds();
    //     var endLo = bounds.getNorthEast();
    //     var startLo = bounds.getSouthWest();
    //     console.log(map.center);

    //     marker.setPosition(map.center)

    //     $('#{$id}_geometry').val(map.center.toString());
    // });


    // google.maps.event.addListener(map, 'dragstart', function(event){
    //     marker.setPosition(map.center)
    //     infowindow.close();
    // });
    // google.maps.event.addListener(map, 'dragend', function(event){

    //     var request = {
    //         query: map.center.toString(),
    //         fields: ['name', 'geometry'],
    //     };

    //     geocoder = new google.maps.Geocoder();

    //     geocoder.geocode( {'latLng': marker.getPosition()}, function(results, status) {
    //         if (status == google.maps.GeocoderStatus.OK) {
    //             //map.setCenter(results[0].geometry.location);
    //             console.log('geometry11', results);

    //             // for (var i = 0; i < results.length; i++) {
    //             //     createMarker(results[i]);
    //             //     }

    //              //createMarker(results[0]);

    //             infowindow.setContent(results[0].formatted_address);
    //             infowindow.open(map, marker);

    //             map.setCenter(results[0].geometry.location);
    //             $('#{$id}_geometry').val(results[0].geometry.location);
    //             console.log('geometry12', results);
    //             $('[name="{$keyName}"]').val(JSON.stringify(results));

    //         } else {
    //             alert("Geocode was not successful for the following reason: " + status);
    //         }
    //     });

    //     // service = new google.maps.places.PlacesService(map);

    //     // service.findPlaceFromQuery(request, function(results, status) {
    //     // if (status === google.maps.places.PlacesServiceStatus.OK) {
    //     //     for (var i = 0; i < results.length; i++) {
    //     //     createMarker(results[i]);
    //     //     }

    //     //     map.setCenter(results[0].geometry.location);
    //     //     $('#{$id}_geometry').val(results[0].geometry.location);
    //     //     console.log('geometry12', results);
    //     //     $('[name="{$keyName}"]').val(JSON.stringify(results));

    //     // }
    //     // });

    // });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBlMmLIXhf24iAAXMeXGllYsZOTkc9bgtM&libraries=places&callback={$callback}" async defer></script>
EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = (string) $value;
        $html  = <<<EOT
        {$value}

EOT;

        return $html;
    }
}

(function ($) {
    console.log("Javascript file 'ajax-shops.js' loaded...");
    console.log("Admin_url: " + ajax_object.ajax_url);
    console.log("Nonce: " + ajax_object.nonce);


    let map;
    let selectedMarker = null;
    let selectedParcelShop;

    const geographicalCenterOfDenmark = { lat: 55.963, lng: 11.764 };

    map = L.map('map-container', {
        center: geographicalCenterOfDenmark,
        zoom: 6
    });

    // Leaflet map with default marker icon
    var selectedMarkerIcon = L.icon({
        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34]
    });


    // Initialize and add the map
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // Initial setup based on the selected shipping method
    function setupShippingMethod() {
        const dhlShippingMethod = "dhl_pickup_method";
        const selectedShippingMethod = $("input[name^=shipping_method]:checked").val();

        if (selectedShippingMethod !== undefined) {
            const isDhlShipping = selectedShippingMethod.indexOf(dhlShippingMethod) >= 0;

            if (isDhlShipping) {
                $("#dhl-search-field-table").show();
                $("#ship-to-different-address-checkbox").click();
                $(".woocommerce-shipping-fields").hide();
            } else {
                $("#dhl-search-field-table").hide();
                $(".woocommerce-shipping-fields").show();
            }
        } else {
            $("#dhl-search-field-table").hide();
            $(".woocommerce-shipping-fields").show();
        }

        $(document.body).on("change", 'input[name^="shipping_method"]', function () {
            console.log("Shipping Method Changed");
            setupShippingMethod();
        });

    }

    // Set up based on the initial shipping method
    setupShippingMethod();

    // Handle change in shipping method
    $(document.body).on("change", 'input[name^="shipping_method"]', function () {
        setupShippingMethod();
    });

    $("#pluginsdk-dhl-shipping-modal, #pluginsdk-dhl-shipping-close-button-modal, #pluginsdk-dhl-shipping-confirm-button-modal").click(function (e) {
        console.log("Modal Clicked");

        // Check if a location is selected
        const selectedRadioButton = $("input[name='location']:checked");

        if (selectedRadioButton.length > 0) {
            // Log the selected radio button value to inspect it
            console.log("Selected Radio Button Value:", selectedRadioButton.val());

            // Parse the selected location data
            const locationData = JSON.parse(selectedRadioButton.val());

            // Log the locationData to inspect its structure
            console.log("Parsed Location Data:", locationData);

            // Uncomment the following line to update the hidden input fields
            updateHiddenInputFields(locationData);

        } else {
            // Handle the case where no location is selected
            console.error("No location selected");
            
        }

        if (e.target !== e.currentTarget) return;
        $("#pluginsdk-dhl-shipping-modal").hide();
        cleanUp();
    });







    // Clean up function
    function cleanUp() {
        $("#pluginsdk-dhl-shipping-shops, pluginsdk-dhl-shipping-selected-pakkeshop").empty();
        removeMarkers();
    }

    // ---- PAKKESHOPS ---- //

    // Click search-button to search
    $("#dhl-pickup-points-search").click(function () {
        pakkeshopCall(".dhl-input-zipcode");
    });

    // Press ENTER (keycode 13) to search
    $(".dhl-input-zipcode").keydown(function (e) {
        if (e.which == 13) {
            e.preventDefault(); 
            pakkeshopCall(".dhl-input-zipcode");
        }
    });





    // ---- LISTE AF PAKKESHOPS ---- //
    function createRadioButtons(locations) {
        const container = document.getElementById("pluginsdk-dhl-shipping-shops");

        // Clear existing content before adding new locations
        $("#pluginsdk-dhl-shipping-shops").empty();

        // Separate locations based on provider
        const parcelLocations = locations.filter(location => location.location.ids[0].provider === "parcel");
        const expressLocations = locations.filter(location => location.location.ids[0].provider === "express");

        // Display Parcel locations
        displayLocations(container, parcelLocations, "Normal Levering");

        // Display Express locations
        displayLocations(container, expressLocations, "Express Levering");

        selectRadioButton();

    }

    function scrollToElement(elementId) {
        const element = document.getElementById(elementId);

        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }
    }


    function displayLocations(container, locations, headerText) {
        if (locations.length > 0) {
            const header = document.createElement("h6");
            header.textContent = headerText;
            container.appendChild(header);

            locations.forEach(location => {
                displayLocation(container, location);
            });
        }
    }

    function displayLocation(container, location) {
        const wrapperDiv = document.createElement("div"); 
        wrapperDiv.classList.add("wrapper-container"); 

        const radioContainer = document.createElement("div"); 
        radioContainer.classList.add("radio-container"); 

        const radioButton = document.createElement("input");
        radioButton.type = "radio";
        radioButton.id = location.location.ids[0].locationId;
        radioButton.name = "location";
        radioButton.value = JSON.stringify(location);
        radioButton.classList.add("locations");
        radioButton.style.marginRight = "30px";
        radioButton.style.alignContent = "start"

        const label = document.createElement("label");
        label.classList.add("locations");
        label.htmlFor = location.location.ids[0].locationId;

        const nameDiv = document.createElement("div");
        nameDiv.classList.add("location-name");
        const nameValue = document.createTextNode(location.name);
        nameDiv.appendChild(nameValue);

        const addressDiv = document.createElement("div");
        addressDiv.classList.add("location-address");
        const addressValue = document.createTextNode(
            `${location.place.address.streetAddress}, ${location.place.address.postalCode} ${location.place.address.addressLocality}`
        );
        addressDiv.appendChild(addressValue);

        label.appendChild(nameDiv);
        label.appendChild(addressDiv);

        radioContainer.appendChild(radioButton);
        radioContainer.appendChild(label);

        wrapperDiv.appendChild(radioContainer); 

        container.appendChild(wrapperDiv); 
    }

    function formatOpeningHours(openingHours) {
        let formattedHours = "";
        openingHours.forEach(day => {
            formattedHours += `${day.dayOfWeek.slice(18)}: ${day.opens} - ${day.closes}` + "<br>";
        });

        return formattedHours;
    }

    function setParcelshopDataInModal(locationData) {
        // Check if the element exists before accessing it
        const selectedParcelshopModalElement = $("#selected-parcelshop-modal");
        const selectedParcelshopModalDisplayElement = $("#selected-parcelshop-modal-display");

        if (selectedParcelshopModalElement.length > 0) {
            selectedParcelshopModalElement.text(locationData.name);
            selectedParcelshopModalElement.append("<br>");
            selectedParcelshopModalElement.append(locationData.place.address.streetAddress);
            selectedParcelshopModalElement.append("<br>");
            selectedParcelshopModalElement.append(locationData.place.address.postalCode + " " + locationData.place.address.addressLocality);
        }

        if (selectedParcelshopModalDisplayElement.length > 0) {
            selectedParcelshopModalDisplayElement.text(locationData.name);
            selectedParcelshopModalDisplayElement.append("<br>");
            selectedParcelshopModalDisplayElement.append(locationData.place.address.streetAddress);
            selectedParcelshopModalDisplayElement.append("<br>");
            selectedParcelshopModalDisplayElement.append(locationData.place.address.postalCode + " " + locationData.place.address.addressLocality);
        }
    }

    // Function to select radio button
    function selectRadioButton() {
        const radioButtons = document.getElementsByName("location");

        radioButtons.forEach((radioButton) => {
            radioButton.onclick = function () {
               
                unselectMarkers();

                const locationData = JSON.parse(radioButton.value); // Parse the stored JSON string

               
                setParcelshopDataInFields(locationData);
                showSelectedParcelshop(locationData);

                const radiobuttonId = radioButton.id;
                selectMarkerWhenRadio(radiobuttonId);

                setParcelshopDataInModal(locationData);

                zoomToLocation(locationData.place.geo.latitude, locationData.place.geo.longitude);
            };
        });
    }

    // Function to unselect all markers except the selected one
    function unselectMarkers() {
        markers.forEach(marker => {
            marker.setIcon(selectedMarkerIcon); 
        });
    }




    // Function to zoom to a specific location on the map
    function zoomToLocation(latitude, longitude) {
        map.setView([latitude, longitude], 15); 
    }




    /*******API KALD *********/

    function pakkeshopCall(inputField) {
        $.ajax({
            type: "GET",
            dataType: "JSON", selectedParcelShop,
            url: ajax_object.ajax_url,
            data: {
                action: "retrieve_parcelshops",
                zipcode: $(inputField).val(),
                countryCode: 'DK', // Hardcoded countryCode
                radius: 2500    // Hardcoded radius
            },
            success: function (response) {
                console.log("AJAX Success:" + response);

                if (response.status.error) {
                    console.error("Error in AJAX response:", response.return_error);
                    alert(response.return_error);
                } else {
                    console.log("RESPONSE: ", response.body);

                    $("#pluginsdk-dhl-shipping-modal").show();
                    setTimeout(250);
                    map.invalidateSize();


                    createRadioButtons(response.body.locations);
                    setMarkersAndBounds(response.body.locations);
                }
            },


            error: function (jqXHR, textStatus, errorThrown) {
                console.log("AJAX Error:");
                console.log(jqXHR.responseText);
                console.log(textStatus);
                console.log(errorThrown);
                alert("There was some error performing the AJAX call. Please check the console for more details.");
            },



        });
    }

    function setParcelshopDataInFields(locationData) {
        // Check if the element exists before accessing it
        const selectedLocationNameElement = document.getElementById("selected-location-name");
        const selectedLocationAddressElement = document.getElementById("selected-location-address");
        const selectedLocationOpeningHoursElement = document.getElementById("selected-location-opening-hours");

        if (selectedLocationNameElement && selectedLocationAddressElement && selectedLocationOpeningHoursElement) {
            selectedLocationNameElement.textContent = locationData.name;
            selectedLocationAddressElement.textContent = `${locationData.place.address.streetAddress}, ${locationData.place.address.postalCode} ${locationData.place.address.addressLocality}`;

            
            if (typeof formatOpeningHours === 'function') {
                selectedLocationOpeningHoursElement.textContent = formatOpeningHours(locationData.openingHours);
            }
        }
    }

    function showSelectedParcelshop(locationData) {
        
        if (typeof showMarkerOnMap === 'function') {
            showMarkerOnMap(locationData, locationData.place.geo.latitude, locationData.place.geo.longitude);
        }
    }

    // Function to show marker on the map
    function showMarkerOnMap(locationData, latitude, longitude) {
        
        var marker = L.marker([latitude, longitude], { icon: selectedMarkerIcon }).addTo(map);

        marker.bindPopup(`
        <h1>${locationData.name}</h1>
        <div class="opening-hours">${formatOpeningHours(locationData.openingHours)}</div>`
        ).openPopup();
    }

    // Function to set up markers and bounds
    function setMarkersAndBounds(locations) {
        markers = []; // Clear existing markers
        const bounds = new L.LatLngBounds();

        locations.forEach(location => {
            const latitude = location.place.geo.latitude;
            const longitude = location.place.geo.longitude;

            
            const marker = L.marker([latitude, longitude]);
            marker.push(marker);

            bounds.extend([latitude, longitude]);

            const popupContent = `<strong>${location.name}</strong><br>${location.place.address.streetAddress},
             ${location.place.address.postalCode} ${location.place.address.addressLocality}`;
            marker.bindPopup(popupContent);

            
            marker.on('click', function () {
                if (selectedMarker) {
                    selectedMarker.setIcon(L.divIcon({ className: 'leaflet-div-icon' }));
                }
                selectedMarker = marker;
                marker.setIcon(selectedMarkerIcon);
               
                const radiobuttonId = location.location.ids[0].locationId;
                const radiobutton = document.getElementById(radiobuttonId);
                if (radiobutton) {
                    radiobutton.click();

                    scrollToElement(radiobuttonId);
                }
            });
            map.addLayer(marker);
        });
        map.fitBounds(bounds);
    }


    // Function to remove markers from the map
    function removeMarkers() {
        markers.forEach(marker => {
            map.removeLayer(marker);
        });
        markers = [];
    }

    function selectMarkerWhenRadio(radiobuttonId) {
        markers.forEach(function (marker) {
            let markerId = marker.options.id;

            if (markerId == radiobuttonId) {
               
                marker.setIcon(selectedMarkerIcon);
            } else {
                
                marker.setIcon(selectedMarkerIcon)
            }
        });
    }

    function updateHiddenInputFields(locationData) {
        // Set the values of the hidden input fields
        $("#shipping_parcelshop_number_dhl").val(locationData.location.ids[0].locationId);
        $("#shipping_parcelshop_name_dhl").val(locationData.name);
        $("#shipping_parcelshop_adress_dhl").val(locationData.place.address.streetAddress);
        $("#shipping_parcelshop_zipcode_dhl").val(locationData.place.address.postalCode);
        $("#shipping_parcelshop_city_dhl").val(locationData.place.address.addressLocality);

        // Trigger change event for each field
        $("#shipping_parcelshop_number_dhl").change();
        $("#shipping_parcelshop_name_dhl").change();
        $("#shipping_parcelshop_adress_dhl").change();
        $("#shipping_parcelshop_zipcode_dhl").change();
        $("#shipping_parcelshop_city_dhl").change();
    }



})(jQuery);



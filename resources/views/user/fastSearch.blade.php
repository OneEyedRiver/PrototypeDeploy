    <x-userLayout>

    <!-- search -->
    <div class="max-w-3xl mx-auto my-6 p-4 border border-gray-300 rounded-2xl shadow-md bg-white">
    <div class="flex items-center gap-3">
        <!-- Search Form -->
        <form action="" method="GET" class="flex-1">
            <label for="search" class="sr-only">Search</label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       id="search"
                       value="{{ request('search') }}"  
                       class="block w-full p-3 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 
                              focus:ring-blue-500 focus:border-blue-500" 
                       placeholder="Search Product Location" />
                <button type="submit" 
                        class="absolute end-2.5 bottom-1.5 bg-blue-700 hover:bg-blue-800 text-white 
                               focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium 
                               rounded-lg text-sm px-4 py-2 transition ">
                    Search
                </button>
            </div>
        </form>

        <!-- Image Upload as Button -->
        <form id="uploadForm" action="/api/upload-image" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="imageInput" 
                   class="cursor-pointer flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-medium shadow-md transition">
                <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 4v16m8-8H4" />
                </svg>
                Image
            </label>
            <input type="file" id="imageInput" name="image" class="hidden" required>
        </form>
         <form id="uploadForm1" action="/api/describe-audio" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="audioInput" 
                   class="cursor-pointer flex items-center gap-2 bg-purple-500 hover:bg-purple-600 text-white px-4 py-3 rounded-lg font-medium shadow-md transition">
                <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 4v16m8-8H4" />
                </svg>
                Voice
            </label>
            <input type="file" name="audio" id="audioInput" accept="audio/*" class="hidden"  required>
        </form>
    </div>
</div>

    

 <!-- Map -->
 <div class="flex justify-center items-center m-10">
    <div id="map" style="height: 500px; width: 75%;" class="rounded-lg my-4"></div>


</div>

<div class="flex justify-center my-4">
    <button id="btn-set-location" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        Set My Location
    </button>
</div>


<div class="overflow-x-auto  flex justify-center">
    <table class="w-[full] text-lg text-left text-gray-600 border-collapse ">
         <thead class="bg-gray-100 text-gray-800 text-sm uppercase tracking-wide">
            <tr>
                <th scope="col" class="px-6 py-3">Store Name</th>
                <th scope="col" class="px-6 py-3">Address</th>
                <th scope="col" class="px-6 py-3">Price</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach ($stores as $store)
                @foreach ($store->matched_products as $item)
                    <tr class="hover:bg-gray-50 transition cursor-pointer"  
                        data-lat="{{ $store->latitude }}"  
                        data-lng="{{ $store->longitude }}">
                        
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $store->store_name }}</td>
                        <td class="px-6 py-4">{{ $store->store_address }}</td>
                        <td class="px-6 py-4">₱{{ $item->product_price }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>





{{-- Modal --}}
<body>
  <div id="map"></div>

  <!-- your modal should be here, not inside map -->
  <div id="storeModal" class=" hidden fixed inset-0  bg-opacity-50 flex justify-center items-center z-1000">
      <div class="w-[600px] bg-white p-6 rounded-lg w-96">
          <h2 id="modalStoreName" class="text-xl font-bold mb-4"></h2>
              
        <ul id="modalProductList" class="space-y-4 max-h-80 overflow-y-auto pr-2"></ul>
          <button onclick="document.getElementById('storeModal').classList.add('hidden')" 
                  class="mt-4 bg-red-500 text-white px-4 py-2 rounded">
              Close
          </button>
      </div>
  </div>
</body>






   <script>
   
  var map = L.map('map').setView([12.8797, 121.7740], 6); // Default PH view

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // ✅ Add green marker for user location
    var userMarker = null;
    var userLat = sessionStorage.getItem('userLat') ? parseFloat(sessionStorage.getItem('userLat')) : null;
    var userLng = sessionStorage.getItem('userLng') ? parseFloat(sessionStorage.getItem('userLng')) : null;

    function requestUserLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;

                // Save in sessionStorage
                sessionStorage.setItem('userLat', userLat);
                sessionStorage.setItem('userLng', userLng);

                if (!userMarker) {
                    userMarker = L.marker([userLat, userLng], {
                        icon: L.icon({
                            iconUrl: "https://maps.google.com/mapfiles/ms/icons/green-dot.png",
                            iconSize: [32, 32]
                        })
                    }).addTo(map).bindPopup("📍 You are here");
                } else {
                    userMarker.setLatLng([userLat, userLng]);
                }

                userMarker.openPopup();
                map.setView([userLat, userLng], 13);
            }, function() {
                console.warn("Geolocation blocked or unavailable.");
            });
        } else {
            console.warn("Geolocation not supported by browser.");
        }
    }

    // ✅ Use stored session location if available
    if (userLat && userLng) {
        userMarker = L.marker([userLat, userLng], {
            icon: L.icon({
                iconUrl: "https://maps.google.com/mapfiles/ms/icons/green-dot.png",
                iconSize: [32, 32]
            })
        }).addTo(map).bindPopup("📍 You are here").openPopup();
        map.setView([userLat, userLng], 13);
    } else {
        requestUserLocation();
    }

    // ✅ Add store markers from backend
    var stores = @json($stores);
    if (stores.length > 0) {
        stores.forEach(function(store) {
            if (store.latitude && store.longitude) {
                var marker = L.marker([store.latitude, store.longitude]).addTo(map);
                marker.bindPopup("<b>" + store.store_name + "</b><br>" + (store.store_address ?? ""));
            }
        });
    }

    // ✅ Add "Locate Me" button
     var locateControl = L.control({position: 'topleft'});
    locateControl.onAdd = function(map) {
        var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        div.innerHTML = '<img src="https://maps.google.com/mapfiles/ms/icons/green-dot.png" style="width:24px;height:24px;cursor:pointer;" title="Go to my location">';
        div.onclick = function() {
            if (userLat && userLng) {
                map.setView([userLat, userLng], 13);
                if (userMarker) userMarker.openPopup();
            } else {
                requestUserLocation();
            }
        };
        return div;
    };
    locateControl.addTo(map);

// Flag to enable "pick location mode"
var pickingLocation = false;

// Button to start picking
document.getElementById('btn-set-location').addEventListener('click', function() {
    pickingLocation = true;
    alert("Click anywhere on the map to set your location.");

    // Turn marker yellow immediately
    if (!userMarker) {
        userMarker = L.marker([userLat || 12.8797, userLng || 121.7740], {
            icon: L.icon({
                iconUrl: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
                iconSize: [32, 32]
            })
        }).addTo(map).bindPopup("📍 You are here");
    } else {
        userMarker.setIcon(L.icon({
            iconUrl: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
            iconSize: [32, 32]
        }));
    }
});

// Map click to set location
map.on('click', function(e) {
    if (!pickingLocation) return;

    // Update coordinates
    userLat = e.latlng.lat;
    userLng = e.latlng.lng;

    // Save to sessionStorage
    sessionStorage.setItem('userLat', userLat);
    sessionStorage.setItem('userLng', userLng);

    // Update marker position
    if (!userMarker) {
        userMarker = L.marker([userLat, userLng], {
            icon: L.icon({
                iconUrl: "https://maps.google.com/mapfiles/ms/icons/green-dot.png",
                iconSize: [32, 32]
            })
        }).addTo(map).bindPopup("📍 You are here");
    } else {
        userMarker.setLatLng([userLat, userLng]);

        // Turn back to green
        userMarker.setIcon(L.icon({
            iconUrl: "https://maps.google.com/mapfiles/ms/icons/green-dot.png",
            iconSize: [32, 32]
        }));
    }

    userMarker.openPopup();
    map.setView([userLat, userLng], 13);

    pickingLocation = false; // Exit pick mode
    alert("Location updated!");
});





if (stores.length > 0) {
    var bounds = [];
    stores.forEach(function(store) {
        if (store.latitude && store.longitude) {
            var marker = L.marker([store.latitude, store.longitude]).addTo(map);

            // Bind popup (optional)
            marker.bindPopup("<b>" + store.store_name + "</b><br>" + (store.address ?? ''));

            // ✅ Click event for this marker
            marker.on("click", function() {
                document.getElementById("modalStoreName").innerText = store.store_name;

                var productList = document.getElementById("modalProductList");
                productList.innerHTML = "";
if (store.matched_products && store.matched_products.length > 0) {
    store.matched_products.forEach(function(product) {
        // Create container
        var li = document.createElement("li");
        li.classList.add("mb-4", "p-3", "border", "rounded", "bg-gray-50");

        // Product name
        var name = document.createElement("h3");
        name.textContent = product.product_name;
        name.classList.add("font-bold", "text-gray-800", "text-2xl" );

        // Product image
        if (product.product_image) {
            var img = document.createElement("img");
            img.src = "/storage/" + product.product_image;

            img.alt = product.product_name;
            img.classList.add("w-[80%]", "h-60", "object-cover", "rounded", "my-2");
            li.appendChild(img);
        }

        // Product qty
        if (product.product_price) {
             if (product.product_unit == "kg") {
            var qty = document.createElement("p");
            qty.textContent = "₱" + product.product_price+ " per "+product.product_unit;
            qty.classList.add("text-xl", "text-gray-600");
            li.appendChild(qty);
        }else{
             var qty = document.createElement("p");
            qty.textContent ="₱" + product.product_price + " per pc";
            qty.classList.add("text-xl", "text-gray-600");
            li.appendChild(qty);
        }
        }

            

   
        // Product description
        if (product.product_description) {
            var desc = document.createElement("p");
            desc.textContent = product.product_description ?? "No description available";
            desc.classList.add("text-sm", "text-gray-600", "p-2", "border", "rounded", "max-h-30", "overflow-y-auto");

            li.appendChild(desc);
        }

         


        // Append name last so it's always shown on top
        li.insertBefore(name, li.firstChild);

        // Add this product card to list
        productList.appendChild(li);

        // Add to Cart button
var addBtn = document.createElement("button");
addBtn.textContent = "Add to Cart";
addBtn.classList.add("bg-blue-600", "text-white", "px-4", "py-2", "rounded", "mt-2");

// On click, send product + store info
addBtn.onclick = function () {
    fetch("/cart/add", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
        },
        body: JSON.stringify({
            product_id: product.id,
            store_id: store.id,
            product_name: product.product_name,
            product_price: product.product_price,
            product_unit: product.product_unit,
            product_description: product.product_description,
            latitude: store.latitude,
            longitude: store.longitude,
            quantity: 1
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(product.product_name + " added to cart!");
        }
    });
};

li.appendChild(addBtn);

    });
                } else {
                    productList.innerHTML = "<li>No matching product found</li>";
                }

                document.getElementById("storeModal").classList.remove("hidden");
            });

            bounds.push([store.latitude, store.longitude]);
        }
    });
    if (bounds.length > 0) map.fitBounds(bounds);
}







//ZOOM LOcation on table
document.querySelectorAll("tbody tr").forEach(function(row) {
    row.addEventListener("click", function() {
        // remove highlight from others
        document.querySelectorAll("tbody tr").forEach(r => r.classList.remove("bg-blue-100"));

        // highlight clicked
        this.classList.add("bg-blue-100");

        var lat = this.getAttribute("data-lat");
        var lng = this.getAttribute("data-lng");

        if (lat && lng) {
            map.setView([lat, lng], 15);
        }
    });
});









//image recognition

document.getElementById('imageInput').addEventListener('change', function() {
    let form = document.getElementById('uploadForm');
    let formData = new FormData(form);

    fetch('/api/upload-image', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // ✅ Put recognized product in the search input
        document.getElementById('search').value = data.object;

        // ✅ Auto-submit the search form
        document.querySelector('form[method="GET"]').submit();
    })
    .catch(err => {
        console.error("Error:", err);
    });
});

        



//Audio
document.getElementById('audioInput').addEventListener('change', async function () {
    const file = this.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('audio', file);
    formData.append('_token', document.querySelector('input[name="_token"]').value);

    try {
        const response = await fetch('/api/describe-audio', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        // If GPT responded with choices (standard OpenAI API response)
        let dishName = "Not recognized";
        if (data.choices && data.choices[0].message) {
            try {
                const parsed = JSON.parse(data.choices[0].message.content);
                dishName = parsed.dish || "Not recognized";
            } catch (err) {
                console.error("JSON parse failed:", err, data);
            }
        }

        document.getElementById('search').value = dishName;
        console.log(dishName)
    } catch (error) {
        console.error("Upload failed:", error);
        document.getElementById('dishName').textContent = "Error recognizing audio";
    }
});









    </script>

    </x-userLayout>
<x-userLayout>
    <p>Cart</p>


    


    <div class="w-full  flex justify-center items-center sm:m-a  "> 

 
<div class="w-[90%] grid  gap-3 grid-cols-2 sm:w-[70%]  sm:gap-4 sm:grid-cols-3 flex items-center sm:m-a   sm:pl-12  "> 
@foreach ($products as $product)
<div class="w-full max-w-3xs h-[380px] border border-gray-700 p-3 rounded-md shadow-lg hover:shadow/2xl transition duration-300 ease-in-out hover:-translate-y-2 relative cursor-pointer product-card"
     data-id="{{ $product->id }}"
     data-lat="{{ $product->latitude }}"
     data-lng="{{ $product->longitude }}">

    @if ($product->product && $product->product->product_image)
        <img src="/storage/{{ $product->product->product_image }}" 
             alt="{{ $product->product->product_name }}"
             class="w-full h-[60%]">
    @endif

    <div class="grid gap-0 grid-cols-1 mt-1">
        <div class="flex justify-between items-center">
            <h1 class="font-semibold">{{$product->product_name}}</h1> 
            <button class="delete-btn bg-red-500 text-white px-2 py-1 rounded">X</button>
        </div>

        <h1 class="font-medium">₱{{$product->product_price}}</h1> 

        <div class="flex items-center mt-1">
            <label class="mr-2">Qty:</label>
            <input type="number" min="1" value="{{ $product->quantity }}" 
                   class="qty-input border p-1 w-16 text-center rounded">
        </div>

        <h1 class="font-medium mt-1 total-price">
            Total: ₱{{ number_format($product->product_price * $product->quantity, 2) }}
        </h1>
    </div>
</div>
@endforeach








</div>

</div>

<div class="max-w-md mx-auto my-2 mt-5">
    
{{$products->links()}}


</div>
 <!-- Map -->
 <div class="flex justify-center items-center m-10">
    <div id="map" style="height: 500px; width: 75%;" class="rounded-lg my-4"></div>
<script>
    var map = L.map('map').setView([12.8797, 121.7740], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // ✅ User marker setup
    var userMarker = null;
    var userLat = sessionStorage.getItem('userLat') ? parseFloat(sessionStorage.getItem('userLat')) : null;
    var userLng = sessionStorage.getItem('userLng') ? parseFloat(sessionStorage.getItem('userLng')) : null;

    function requestUserLocation(callback) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;

                sessionStorage.setItem('userLat', userLat);
                sessionStorage.setItem('userLng', userLng);

                if (!userMarker) {
                    userMarker = L.marker([userLat, userLng], {
                        icon: L.icon({
                            iconUrl: "https://maps.google.com/mapfiles/ms/icons/green-dot.png",
                            iconSize: [32, 32]
                        })
                    }).addTo(map).bindPopup("📍 You are here").openPopup();
                } else {
                    userMarker.setLatLng([userLat, userLng]);
                }

                map.setView([userLat, userLng], 13);
                if (callback) callback();
            }, function() {
                alert("Geolocation blocked or unavailable.");
            });
        }
    }

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

    // ✅ Polyline for route
    var routeLine = null;

  // ✅ Marker for destination
var destinationMarker = null;

// Function to get shortest path using OSRM
function showRoute(destLat, destLng) {
    if (!userLat || !userLng) {
        requestUserLocation(function() {
            showRoute(destLat, destLng);
        });
        return;
    }

    var url = `https://router.project-osrm.org/route/v1/driving/${userLng},${userLat};${destLng},${destLat}?overview=full&geometries=geojson`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.routes && data.routes.length > 0) {
                var coords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);

                // ✅ Clear old route
                if (routeLine) {
                    map.removeLayer(routeLine);
                }

                // ✅ Draw new route
                routeLine = L.polyline(coords, { color: 'blue', weight: 5 }).addTo(map);

                // ✅ Add / update destination marker
                if (destinationMarker) {
                    map.removeLayer(destinationMarker);
                }
                destinationMarker = L.marker([destLat, destLng], {
                    icon: L.icon({
                        iconUrl: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
                        iconSize: [32, 32]
                    })
                }).addTo(map).bindPopup("📍 Destination").openPopup();

                // ✅ Fit map to show full route
                map.fitBounds(routeLine.getBounds());
            } else {
                alert("No route found.");
            }
        })
        .catch(err => console.error(err));
}


    // ✅ Click event for each product card
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', function() {
            var lat = this.getAttribute('data-lat');
            var lng = this.getAttribute('data-lng');
            if (lat && lng) {
                showRoute(lat, lng);
            } else {
                alert("This product does not have location data.");
            }
        });
    });













//Updating or Deleting Cart

document.addEventListener("DOMContentLoaded", function () {
    let token = document.querySelector('meta[name="csrf-token"]').getAttribute("content");

    // Handle quantity change
    document.querySelectorAll(".qty-input").forEach(input => {
        input.addEventListener("change", function () {
            let parent = this.closest(".product-card");
            let id = parent.dataset.id;
            let qty = this.value;

            fetch(`/cart/update/${id}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token
                },
                body: JSON.stringify({ quantity: qty })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    parent.querySelector(".total-price").textContent = "Total: ₱" + data.total.toFixed(2);
                }
            });
        });
    });

    // Handle delete
    document.querySelectorAll(".delete-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            if (!confirm("Remove this item from cart?")) return;

            let parent = this.closest(".product-card");
            let id = parent.dataset.id;

            fetch(`/cart/delete/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": token
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    parent.remove();
                }
            });
        });
    });
});


</script>










</x-userLayout>
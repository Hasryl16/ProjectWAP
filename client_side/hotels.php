<!-- Hotels Section -->
    <section id="hotels" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Explore Best Hotel</h2>
            <?php
            $conn = getConnection();
            $query = "SELECT h.hotel_id, h.hotel_name, h.address, h.star_rating, h.image_url, MIN(r.price) as min_price
                      FROM hotel h
                      LEFT JOIN room r ON h.hotel_id = r.hotel_id AND r.availability = 1
                      GROUP BY h.hotel_id
                      ORDER BY h.hotel_id";
            $result = $conn->query($query);
            $hotels = [];
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $hotels[] = $row;
                }
            }
            $conn->close();
            ?>
            <div class="row g-4">
                <?php foreach($hotels as $index => $hotel): ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-img-top position-relative">
                                <img src="<?php echo htmlspecialchars($hotel['image_url']); ?>" alt="Hotel Image" style="height: 200px; width: 100%; object-fit: cover;">
                                <span class="badge bg-white text-dark position-absolute top-0 end-0 m-2">
                                <?php echo htmlspecialchars(trim(explode(',', $hotel['address'])[count(explode(',', $hotel['address']))-1])); ?></span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($hotel['address']); ?></p>
                                <div class="mb-2">
                                    <?php
                                    $rating = $hotel['star_rating'];
                                    $full_stars = floor($rating);
                                    $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
                                    $empty_stars = 5 - $full_stars - $half_star;
                                    echo str_repeat('★', $full_stars);
                                    if ($half_star) echo '☆';
                                    echo str_repeat('☆', $empty_stars);
                                    echo ' ' . $rating;
                                    ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                    <span class="h5 text-dark fw-bold">Rp <?php echo number_format($hotel['min_price'], 0, ',', '.'); ?>/night</span>
                                    <a href="detailed.php?hotel_id=<?php echo htmlspecialchars($hotel['hotel_id']); ?>" class="btn btn-dark rounded-pill">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

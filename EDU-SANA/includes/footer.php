<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <!-- College Info -->
            <div class="col-md-4 mb-4">
                <h5 class="text-primary mb-3">🎓 Alagappa Arts College</h5>
                <p class="mb-3">
                    <strong>Department of Computer Science</strong><br>
                    Karaikudi, Tamil Nadu<br>
                    Phone: 04565-225555 | Email: cs@alagappa.edu
                </p>
                <div class="mb-3">
                    <span class="badge bg-success me-2">Established 1955</span>
                    <span class="badge bg-info">NAAC A+</span>
                </div>
            </div>

            <!-- Quick Links -->
         

            <!-- Contact & Social -->
            <div class="col-md-4 mb-4">
                <h6 class="text-primary mb-3">📞 Contact Info</h6>
                <p>
                    <i class="fas fa-clock me-2"></i>
                    Mon-Sat: 9:00 AM - 5:00 PM
                </p>
                <p>
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Karaikudi, Tamil Nadu 630003
                </p>
                <div class="mt-3">
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <hr class="my-4">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Alagappa Arts College. 
                All rights reserved | Developed by Computer Science Dept</p>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-muted">
                    Session: <?php echo date('Y').'-'.(date('Y')+1); ?> | v2.0
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- Essential Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script>
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
</script>

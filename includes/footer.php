    </main>
    
    <footer class="footer">
        <div class="container">
            <p> All rights reserved &copy; <?= date('Y') ?> </p>
        </div>
    </footer>
    
    <script src="assets/js/security.js"></script>
    <script src="assets/js/app.js"></script>
    
    <script>
        // Generate anti-recording watermark
        (function() {
            const watermarkContainer = document.getElementById('watermark');
            if (!watermarkContainer) return;
            
            const userId = '<?= htmlspecialchars($currentUser['id'] ?? '0') ?>';
            const userName = '<?= htmlspecialchars($currentUser['name'] ?? 'بەکارهێنەر') ?>';
            const timestamp = new Date().toISOString();
            const watermarkText = `${userName} - ${timestamp}`;
            
            // Create grid of watermarks
            for (let y = -100; y < window.innerHeight + 200; y += 150) {
                for (let x = -200; x < window.innerWidth + 400; x += 300) {
                    const span = document.createElement('span');
                    span.className = 'watermark-text';
                    span.textContent = watermarkText;
                    span.style.left = x + 'px';
                    span.style.top = y + 'px';
                    watermarkContainer.appendChild(span);
                }
            }
        })();
    </script>
</body>
</html>

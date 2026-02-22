    </main>
    <?php
    $securityJsVersion = @filemtime(__DIR__ . '/../assets/js/security.js') ?: time();
    $appJsVersion = @filemtime(__DIR__ . '/../assets/js/app.js') ?: time();
    ?>
    
    <footer class="footer">
        <div class="container">
            <p> هەمی ماف پاراستینە -  پروژێ نامە </p>
            <p><?= date('Y') ?>&copy;</p>
        </div>
    </footer>
    
    <!-- Defer heavy scripts for faster page load -->
    <script src="assets/js/security.js?v=<?= $securityJsVersion ?>" defer></script>
    <script src="assets/js/app.js?v=<?= $appJsVersion ?>" defer></script>
    
    <script>
        // Generate anti-recording watermark (optimized - fewer elements)
        (function() {
            const watermarkContainer = document.getElementById('watermark');
            if (!watermarkContainer) return;
            
            const userId = '<?= htmlspecialchars($currentUser['id'] ?? '0') ?>';
            const userName = '<?= htmlspecialchars($currentUser['name'] ?? 'بەکارهێنەر') ?>';
            const timestamp = new Date().toISOString();
            const watermarkText = `${userName} - ${timestamp}`;
            
            // Create fewer watermarks for better performance (reduced by 50%)
            const fragment = document.createDocumentFragment();
            for (let y = -100; y < window.innerHeight + 200; y += 200) {
                for (let x = -200; x < window.innerWidth + 400; x += 400) {
                    const span = document.createElement('span');
                    span.className = 'watermark-text';
                    span.textContent = watermarkText;
                    span.style.left = x + 'px';
                    span.style.top = y + 'px';
                    fragment.appendChild(span);
                }
            }
            watermarkContainer.appendChild(fragment);
        })();
    </script>
</body>
</html>

<?php // includes/footer.php ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ---- Theme toggle ----
document.getElementById('themeToggle')?.addEventListener('click', function() {
    const html   = document.documentElement;
    const icon   = document.getElementById('themeIcon');
    const isDark = html.getAttribute('data-bs-theme') === 'dark';
    const next   = isDark ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', next);
    icon.className = isDark ? 'bi bi-moon-fill' : 'bi bi-sun-fill';

    // Persist via AJAX
    fetch('api.php?action=set_theme&theme=' + next);
});
</script>
<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>

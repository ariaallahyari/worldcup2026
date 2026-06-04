</main>

<footer style="
    border-top: 1px solid var(--border);
    padding: 2rem 1.5rem;
    text-align: center;
    color: var(--text3);
    font-size: 0.85rem;
    position: relative; z-index: 1;
    margin-top: 3rem;
">
    <p>⚽ <?= e(getSetting(isRTL() ? 'site_name_fa' : 'site_name_en', 'WC 2026')) ?>
        &nbsp;|&nbsp;
        <?= t('پیش‌بینی کن، سکه بگیر، قهرمان شو!', 'Predict. Earn. Win.') ?>
    </p>
</footer>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
function showToast(msg, type='info') {
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.textContent = msg;
    document.getElementById('toastContainer').appendChild(t);
    setTimeout(() => t.remove(), 4000);
}
</script>
</body>
</html>

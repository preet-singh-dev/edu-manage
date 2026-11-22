<?php
/**
 * includes/footer.php
 * Shared closing layout tags, copyright bar, and Bootstrap/Chart/App JS scripts.
 */
?>
        </main>

        <footer class="app-footer no-print">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small text-muted">
                <span>&copy; <?= date('Y') ?> <strong><?= e(APP_NAME) ?></strong> &mdash; Student Management System</span>
                <span>Academic Administration Suite</span>
            </div>
        </footer>
    </div><!-- /.main-content-wrapper -->
</div><!-- /.app-wrapper -->

<!-- Bootstrap 5.3.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($include_chart_js)): ?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?php endif; ?>

<!-- Custom Application Script -->
<script src="<?= e(url('assets/js/script.js')) ?>"></script>
</body>
</html>

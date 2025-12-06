</div> <!-- Zatvara page-wrapper iz header.php -->

<!-- Footer -->
<footer style="
    text-align: center;
    padding: 15px 0;
    background-color: #FF411C;
    color: #FFFFFF;
    font-weight: 600;
">
    Mr Auto Expert DOO
</footer>

<!-- Footer scripts -->
<script src="<?php echo $base_url ?? ''; ?>assets/js/header.js"></script>

<?php if (isset($include_camera_js) && $include_camera_js): ?>
    <script src="<?php echo $base_url ?? ''; ?>assets/js/camera.js"></script>
<?php endif; ?>

</body>
</html>

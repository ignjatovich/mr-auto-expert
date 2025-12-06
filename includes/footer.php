</div>
<!-- Page Content End -->

<script src="<?php echo $base_url ?? ''; ?>assets/js/header.js"></script>
<?php if (isset($include_camera_js) && $include_camera_js): ?>
    <script src="<?php echo $base_url ?? ''; ?>assets/js/camera.js"></script>
<?php endif; ?>
</body>
</html>
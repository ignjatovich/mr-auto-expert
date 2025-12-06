// ============================================
// KAMERA I UPLOAD SLIKA
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Elementi
    const slikaInput = document.getElementById('slika_vozila');
    const slikaPreview = document.getElementById('slika-preview');
    const cameraBtn = document.getElementById('camera-btn');
    const uploadBtn = document.getElementById('upload-btn');
    const cameraModal = document.getElementById('camera-modal');
    const cameraVideo = document.getElementById('camera-video');
    const cameraCanvas = document.getElementById('camera-canvas');
    const captureBtn = document.getElementById('capture-btn');
    const closeCameraBtn = document.getElementById('close-camera');
    const switchCameraBtn = document.getElementById('switch-camera');

    let stream = null;
    let currentFacingMode = 'environment'; // 'environment' = zadnja kamera, 'user' = prednja

    // ============================================
    // UPLOAD SLIKE - Postojeća funkcionalnost
    // ============================================
    if (slikaInput && slikaPreview) {
        slikaInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                displayImagePreview(file);
            }
        });
    }

    // ============================================
    // OTVORI KAMERU
    // ============================================
    if (cameraBtn) {
        cameraBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openCamera();
        });
    }

    // ============================================
    // UPLOADUJ SLIKU - Klik na dugme
    // ============================================
    if (uploadBtn && slikaInput) {
        uploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            slikaInput.click();
        });
    }

    // ============================================
    // ZATVORI KAMERU
    // ============================================
    if (closeCameraBtn) {
        closeCameraBtn.addEventListener('click', function() {
            closeCamera();
        });
    }

    // ============================================
    // PROMENI KAMERU (prednja/zadnja)
    // ============================================
    if (switchCameraBtn) {
        switchCameraBtn.addEventListener('click', function() {
            currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';

            // Zatvori trenutni stream i otvori novi sa drugom kamerom
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            startCamera();
        });
    }

    // ============================================
    // USLIKAJ
    // ============================================
    if (captureBtn) {
        captureBtn.addEventListener('click', function() {
            capturePhoto();
        });
    }

    // ============================================
    // FUNKCIJE
    // ============================================

    function openCamera() {
        if (cameraModal) {
            cameraModal.style.display = 'flex';
            startCamera();
        }
    }

    function closeCamera() {
        if (cameraModal) {
            cameraModal.style.display = 'none';
        }
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    }

    function startCamera() {
        const constraints = {
            video: {
                facingMode: currentFacingMode,
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(function(mediaStream) {
                stream = mediaStream;
                cameraVideo.srcObject = stream;
                cameraVideo.play();
            })
            .catch(function(error) {
                console.error('Greška pri pristupu kameri:', error);
                alert('Ne mogu da pristupim kameri. Molimo proverite dozvole u podešavanjima.');
                closeCamera();
            });
    }

    function capturePhoto() {
        if (!cameraVideo || !cameraCanvas) return;

        // Postavi dimenzije canvas-a
        cameraCanvas.width = cameraVideo.videoWidth;
        cameraCanvas.height = cameraVideo.videoHeight;

        // Nacrtaj trenutni frame iz videa na canvas
        const context = cameraCanvas.getContext('2d');
        context.drawImage(cameraVideo, 0, 0, cameraCanvas.width, cameraCanvas.height);

        // Konvertuj canvas u Blob
        cameraCanvas.toBlob(function(blob) {
            // Kreiraj File objekat
            const file = new File([blob], 'camera-photo.jpg', { type: 'image/jpeg' });

            // Kreiraj DataTransfer objekat da dodamo fajl u input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            slikaInput.files = dataTransfer.files;

            // Prikaži preview
            displayImagePreview(file);

            // Zatvori kameru
            closeCamera();
        }, 'image/jpeg', 0.95);
    }

    function displayImagePreview(file) {
        const reader = new FileReader();

        reader.onload = function(e) {
            slikaPreview.innerHTML = `
                <div style="margin-top: 15px; position: relative;">
                    <img src="${e.target.result}" 
                         alt="Preview" 
                         style="max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <button type="button" onclick="removeImage()" 
                            style="position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 18px; line-height: 1;">
                        ×
                    </button>
                    <div style="margin-top: 10px; color: #28a745; font-size: 14px;">
                        ✓ Slika je spremna za upload
                    </div>
                </div>
            `;
        };

        reader.readAsDataURL(file);
    }

    // Globalna funkcija za uklanjanje slike
    window.removeImage = function() {
        slikaPreview.innerHTML = '';
        slikaInput.value = '';
    };
});
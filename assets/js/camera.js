// Camera functionality za snimanje slika vozila
let stream = null;
let videoElement = null;
let canvasElement = null;

// Otvori kameru
function openCamera() {
    const modal = document.getElementById('camera-modal');
    videoElement = document.getElementById('camera-video');
    canvasElement = document.getElementById('camera-canvas');

    // Prikaži modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Pokreni kameru
    startCamera();
}

// Zatvori kameru
function closeCamera() {
    const modal = document.getElementById('camera-modal');

    // Zaustavi stream
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }

    // Sakrij modal
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Pokreni kameru
async function startCamera() {
    try {
        // Prioritet za zadnju kameru (environment) na mobilnim uređajima
        const constraints = {
            video: {
                facingMode: 'environment', // Zadnja kamera
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        };

        stream = await navigator.mediaDevices.getUserMedia(constraints);
        videoElement.srcObject = stream;
        videoElement.play();

    } catch (error) {
        console.error('Greška pri pokretanju kamere:', error);

        // Pokušaj sa osnovnim constraintima ako environment ne radi
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            videoElement.srcObject = stream;
            videoElement.play();
        } catch (err) {
            alert('Ne mogu pristupiti kameri. Molimo proverite dozvole.');
            closeCamera();
        }
    }
}

// Uslikaj fotografiju
function capturePhoto() {
    if (!videoElement || !canvasElement) {
        alert('Kamera nije pokrenuta!');
        return;
    }

    // Postavi dimenzije canvas-a
    canvasElement.width = videoElement.videoWidth;
    canvasElement.height = videoElement.videoHeight;

    // Crtaj video frame na canvas
    const context = canvasElement.getContext('2d');
    context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);

    // Konvertuj canvas u blob
    canvasElement.toBlob(blob => {
        if (!blob) {
            alert('Greška pri kreiranju slike!');
            return;
        }

        // Kreiraj File objekat
        const file = new File([blob], 'camera_photo.jpg', { type: 'image/jpeg' });

        // Postavi u file input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('slika_vozila').files = dataTransfer.files;

        // Prikaži preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('slika-preview');
            preview.innerHTML = `
                <div style="margin-top: 15px;">
                    <p style="color: #28a745; font-weight: 600; margin-bottom: 10px;">
                        ✅ Slika snimljena uspešno!
                    </p>
                    <img src="${e.target.result}" 
                         alt="Preview" 
                         style="max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <button type="button" onclick="removePhoto()" class="btn btn-secondary" style="margin-top: 10px;">
                        🗑️ Ukloni sliku
                    </button>
                </div>
            `;
        };
        reader.readAsDataURL(file);

        // Zatvori kameru
        closeCamera();

    }, 'image/jpeg', 0.95);
}

// Ukloni fotografiju
function removePhoto() {
    document.getElementById('slika_vozila').value = '';
    document.getElementById('slika-preview').innerHTML = '';
}

// Preview slike sa file input-a
document.addEventListener('DOMContentLoaded', function() {
    const slikaInput = document.getElementById('slika_vozila');
    const slikaPreview = document.getElementById('slika-preview');

    if (slikaInput && slikaPreview) {
        slikaInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    slikaPreview.innerHTML = `
                        <div style="margin-top: 15px;">
                            <p style="color: #28a745; font-weight: 600; margin-bottom: 10px;">
                                ✅ Slika odabrana uspešno!
                            </p>
                            <img src="${e.target.result}" 
                                 alt="Preview" 
                                 style="max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <button type="button" onclick="removePhoto()" class="btn btn-secondary" style="margin-top: 10px;">
                                🗑️ Ukloni sliku
                            </button>
                        </div>
                    `;
                };

                reader.readAsDataURL(file);
            } else {
                slikaPreview.innerHTML = '';
            }
        });
    }
});

// Zatvori modal klikom na overlay
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('camera-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeCamera();
            }
        });
    }
});
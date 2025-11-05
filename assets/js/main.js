// Preview slike pre uploada
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
                            <img src="${e.target.result}" 
                                 alt="Preview" 
                                 style="max-width: 300px; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        </div>
                    `;
                }

                reader.readAsDataURL(file);
            } else {
                slikaPreview.innerHTML = '';
            }
        });
    }
});
```

---

## **KORAK 7: Napravi folder za slike**

Ručno napravi folder:
```
mr-auto-expert/uploads/vozila/
```

I stavi `.htaccess` fajl u `uploads` folder sa ovim sadržajem:
```
Options -Indexes
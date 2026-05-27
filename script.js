document.addEventListener('DOMContentLoaded', () => {
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file-input');
    const uploadContent = document.getElementById('upload-content');
    const previewContainer = document.getElementById('preview-container');
    const imagePreview = document.getElementById('image-preview');
    const removeBtn = document.getElementById('remove-btn');
    const controlsArea = document.getElementById('controls-area');
    const widthInput = document.getElementById('width');
    const heightInput = document.getElementById('height');
    const maintainRatioCheck = document.getElementById('maintain-ratio');
    const qualityInput = document.getElementById('quality');
    const qualityValue = document.getElementById('quality-value');
    const outputFormatSelect = document.getElementById('output-format');
    const presetWPIcon = document.getElementById('preset-wp-icon');
    const presetWPLogo = document.getElementById('preset-wp-logo');
    const presetWPHeader = document.getElementById('preset-wp-header');
    const presetWPHero = document.getElementById('preset-wp-hero');
    const processBtn = document.getElementById('process-btn');
    const btnText = processBtn.querySelector('span');
    const btnLoader = document.getElementById('btn-loader');
    
    const resultArea = document.getElementById('result-area');
    const downloadLink = document.getElementById('download-link');
    const toast = document.getElementById('toast');

    let currentFile = null;
    let originalWidth = 0;
    let originalHeight = 0;

    uploadArea.addEventListener('click', () => {
        if (!currentFile) fileInput.click();
    });

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (!currentFile) uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });

    function handleFile(file) {
        const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            showToast('Por favor, sube un formato válido (JPG, PNG, WebP, GIF).');
            return;
        }

        currentFile = file;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.src = e.target.result;
            
            const img = new Image();
            img.onload = () => {
                originalWidth = img.width;
                originalHeight = img.height;
                widthInput.value = originalWidth;
                heightInput.value = originalHeight;
                
                uploadContent.classList.add('hidden');
                previewContainer.classList.remove('hidden');
                controlsArea.classList.remove('disabled');
                resultArea.classList.add('hidden');
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    removeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        currentFile = null;
        fileInput.value = '';
        imagePreview.src = '';
        
        uploadContent.classList.remove('hidden');
        previewContainer.classList.add('hidden');
        controlsArea.classList.add('disabled');
        resultArea.classList.add('hidden');
        
        widthInput.value = '';
        heightInput.value = '';
        qualityInput.value = '80';
        qualityValue.textContent = '80%';
        outputFormatSelect.value = 'original';
    });

    presetWPIcon.addEventListener('click', () => {
        maintainRatioCheck.checked = false;
        widthInput.value = 512;
        heightInput.value = 512;
        outputFormatSelect.value = 'png';
        showToast('Site Icon WP aplicado (512x512, PNG)', 'success');
    });

    presetWPLogo.addEventListener('click', () => {
        maintainRatioCheck.checked = false;
        widthInput.value = 250;
        heightInput.value = 100;
        outputFormatSelect.value = 'png';
        showToast('Logo WP aplicado (250x100, PNG)', 'success');
    });

    presetWPHeader.addEventListener('click', () => {
        maintainRatioCheck.checked = false;
        widthInput.value = 1200;
        heightInput.value = 628;
        outputFormatSelect.value = 'jpeg';
        qualityInput.value = 85;
        qualityValue.textContent = '85%';
        showToast('Cabecera WP aplicada (1200x628, JPG)', 'success');
    });

    presetWPHero.addEventListener('click', () => {
        maintainRatioCheck.checked = false;
        widthInput.value = 1920;
        heightInput.value = 1080;
        outputFormatSelect.value = 'jpeg';
        qualityInput.value = 80;
        qualityValue.textContent = '80%';
        showToast('Hero Banner WP aplicado (1920x1080, JPG)', 'success');
    });

    widthInput.addEventListener('input', () => {
        if (maintainRatioCheck.checked && originalWidth > 0 && widthInput.value > 0) {
            const ratio = originalHeight / originalWidth;
            heightInput.value = Math.round(widthInput.value * ratio);
        }
    });

    heightInput.addEventListener('input', () => {
        if (maintainRatioCheck.checked && originalHeight > 0 && heightInput.value > 0) {
            const ratio = originalWidth / originalHeight;
            widthInput.value = Math.round(heightInput.value * ratio);
        }
    });

    qualityInput.addEventListener('input', (e) => {
        qualityValue.textContent = `${e.target.value}%`;
    });

    processBtn.addEventListener('click', async () => {
        if (!currentFile) return;

        processBtn.classList.add('loading');
        btnText.style.opacity = '0';
        btnLoader.classList.remove('hidden');
        processBtn.disabled = true;
        resultArea.classList.add('hidden');

        const formData = new FormData();
        formData.append('image', currentFile);
        formData.append('width', widthInput.value);
        formData.append('height', heightInput.value);
        formData.append('quality', qualityInput.value);
        formData.append('maintainRatio', maintainRatioCheck.checked);
        formData.append('format', outputFormatSelect.value);

        try {
            const response = await fetch('process.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('¡Imagen procesada con éxito!', 'success');
                downloadLink.href = data.url;
                downloadLink.setAttribute('download', data.filename);
                resultArea.classList.remove('hidden');
                
                resultArea.scrollIntoView({ behavior: 'smooth', block: 'end' });
            } else {
                showToast(data.message || 'Error al procesar la imagen.');
            }
        } catch (error) {
            showToast('Error de conexión con el servidor.');
            console.error(error);
        } finally {
            processBtn.classList.remove('loading');
            btnText.style.opacity = '1';
            btnLoader.classList.add('hidden');
            processBtn.disabled = false;
        }
    });

    function showToast(message, type = 'error') {
        toast.textContent = message;
        toast.className = 'toast';
        toast.classList.add(type);
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
});

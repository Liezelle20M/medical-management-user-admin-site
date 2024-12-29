// File upload handling and progress simulation
const uploadArea = document.getElementById('upload-area');
const fileUpload = document.getElementById('file-upload');
const browseFiles = document.getElementById('browse-files');
const uploadProgress = document.getElementById('upload-progress');

// Clicking on 'Browse' link opens file browser
browseFiles.addEventListener('click', function(e) {
    e.preventDefault();
    fileUpload.click();
});

// Drag-and-drop behavior
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = '#003366';
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.style.borderColor = '#8ca8c3';
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    fileUpload.files = e.dataTransfer.files;
    handleFilesUpload(fileUpload.files);
    uploadArea.style.borderColor = '#8ca8c3';
});

// Open file browser when clicking on the entire upload box
uploadArea.addEventListener('click', function() {
    fileUpload.click();
});

//file uploads and simulate progress
fileUpload.addEventListener('change', function() {
    handleFilesUpload(this.files);
});

function handleFilesUpload(files) {
    if (files.length > 0) {
        let progress = 0;
        uploadProgress.style.display = 'block';
        const interval = setInterval(() => {
            if (progress >= 100) {
                clearInterval(interval);
            } else {
                progress += 10;
                uploadProgress.value = progress;
            }
        }, 200); // Simulate a smooth upload over 2 seconds
    }
}

// Form submit handling : 
document.getElementById('clinical-code-form').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent actual form submission for now
    alert('Form Submitted!');
});

        const navLinks = document.querySelectorAll('.nav-links a');
        const menuToggle = document.getElementById('menu-toggle');

        navLinks.forEach((link) => {
            link.addEventListener('click', () => {
                if (menuToggle.checked) {
                    menuToggle.checked = false;
                }
            });
        });
   

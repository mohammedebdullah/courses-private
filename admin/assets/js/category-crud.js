/**
 * Category CRUD Operations
 * JavaScript functions for edit and delete category functionality with SweetAlert2
 */

// Store current category ID for delete operation
let currentDeleteCategoryId = null;
let currentEditCategoryId = null;

/**
 * Edit Category Function
 * Stores the category ID and opens modal
 */
function editCategory(categoryId) {
    currentEditCategoryId = categoryId;
    console.log('Edit category called with ID:', categoryId);
}

/**
 * Load Category Data
 * Fetches and populates category data into the edit form
 */
function loadCategoryData(categoryId) {
    console.log('Loading category data for ID:', categoryId);
    
    // Fetch category data
    fetch(`backend/category/edit_category.php?id=${categoryId}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            
            if (data.success) {
                const category = data.data;
                
                // Small delay to ensure modal is fully rendered
                setTimeout(() => {
                    // Populate form with category data
                    const nameInput = document.querySelector('#edit-category input[name="category_name"]');
                    const idInput = document.querySelector('#edit-category input[name="category_id"]');
                    const statusCheckbox = document.querySelector('#edit-category input[name="status"]');
                    
                    console.log('Name input found:', nameInput !== null);
                    console.log('ID input found:', idInput !== null);
                    console.log('Status checkbox found:', statusCheckbox !== null);
                    
                    if (nameInput) {
                        nameInput.value = category.category_name || '';
                        console.log('Set name to:', category.category_name);
                    }
                    if (idInput) {
                        idInput.value = category.id || '';
                        console.log('Set ID to:', category.id);
                    }
                    if (statusCheckbox) {
                        statusCheckbox.checked = category.status == 1;
                        console.log('Set status to:', category.status == 1);
                    }

                    // Display current icon if exists
                    const iconPreview = document.querySelector('#edit-category .icon-preview');
                    if (iconPreview) {
                        if (category.category_icon) {
                            iconPreview.innerHTML = `<img src="${category.category_icon}" alt="Category Icon" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">`;
                        } else {
                            iconPreview.innerHTML = '';
                        }
                    }
                }, 100);
            } else {
                console.error('Error loading category:', data.message);
                Swal.fire({
                    icon: 'error',
                    title: 'خەلەتی!',
                    text: data.message,
                    confirmButtonColor: '#dc3545'
                });
                $('#edit-category').modal('hide');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'بارکرن سەرنەکەفت',
                text: 'نەشیان بارکرنا پێزانینێن جۆرێ. تکایە دووبارە هەول بدە.',
                confirmButtonColor: '#dc3545'
            });
            $('#edit-category').modal('hide');
        });
}

/**
 * Delete Category Function
 * Sets the category ID for deletion
 */
function deleteCategory(categoryId) {
    currentDeleteCategoryId = categoryId;
}

/**
 * Handle Edit Form Submission
 */
document.addEventListener('DOMContentLoaded', function() {
    // Listen for modal shown event
    $('#edit-category').on('shown.bs.modal', function() {
        console.log('Edit modal shown, current ID:', currentEditCategoryId);
        if (currentEditCategoryId) {
            loadCategoryData(currentEditCategoryId);
        }
    });

    // Reset when modal is hidden
    $('#edit-category').on('hidden.bs.modal', function() {
        currentEditCategoryId = null;
        // Clear form
        const form = document.querySelector('#edit-category form');
        if (form) {
            form.reset();
        }
        const iconPreview = document.querySelector('#edit-category .icon-preview');
        if (iconPreview) {
            iconPreview.innerHTML = '';
        }
    });

    const editForm = document.querySelector('#edit-category form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>دهێتە پاراستن...';

            fetch('backend/category/edit_category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    $('#edit-category').modal('hide');
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەفتن!',
                        text: 'جۆر ب سەرکەفتی هاتە نووکرن',
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        // Reload page to show updated data
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خەلەتی!',
                        text: data.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'سەرنەکەفت!',
                    text: 'نووکرنا جۆرێ سەرنەکەفت. تکایە دووبارە هەول بدە.',
                    confirmButtonColor: '#dc3545'
                });
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }

    // Handle Delete Confirmation
    const deleteBtn = document.querySelector('#delete-modal button[type="submit"]');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();

            if (!currentDeleteCategoryId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ئەگەهدارى!',
                    text: 'چ جۆر نەهاتینە هەلبژارتن بۆ ژێبرنێ',
                    confirmButtonColor: '#ffc107'
                });
                return;
            }

            // Close the bootstrap modal
            $('#delete-modal').modal('hide');

            // Show loading
            Swal.fire({
                title: 'دهێتە ژێبرن...',
                text: 'تکایە چاڤەڕێ بە',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('category_id', currentDeleteCategoryId);

            fetch('backend/category/delete_category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'هاتە ژێبرن!',
                        text: 'جۆر ب سەرکەفتی هاتە ژێبرن',
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خەلەتی!',
                        text: data.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'سەرنەکەفت!',
                    text: 'ژێبرنا جۆرێ سەرنەکەفت. تکایە دووبارە هەول بدە.',
                    confirmButtonColor: '#dc3545'
                });
            })
            .finally(() => {
                currentDeleteCategoryId = null;
            });
        });
    }

    // Reset delete category ID when modal is closed
    $('#delete-modal').on('hidden.bs.modal', function() {
        currentDeleteCategoryId = null;
    });

    // Preview image on file selection in edit modal
    const editFileInput = document.querySelector('#edit-category input[name="category_icon"]');
    if (editFileInput) {
        editFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let iconPreview = document.querySelector('#edit-category .icon-preview');
                    if (!iconPreview) {
                        iconPreview = document.createElement('div');
                        iconPreview.className = 'icon-preview mt-2';
                        editFileInput.parentElement.appendChild(iconPreview);
                    }
                    iconPreview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

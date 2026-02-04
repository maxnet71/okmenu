$(document).ready(function() {
    initTooltips();
    initPopovers();
    initFormValidation();
    initAjaxForms();
});

function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function initPopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

function initAjaxForms() {
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Caricamento...');
        
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method') || 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showToast('Successo', response.message, 'success');
                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                    if (response.reload) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    showToast('Errore', response.message, 'danger');
                }
            },
            error: function(xhr) {
                showToast('Errore', 'Si è verificato un errore. Riprova.', 'danger');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
}

function showToast(title, message, type = 'info') {
    const bgClass = {
        'success': 'bg-success',
        'danger': 'bg-danger',
        'warning': 'bg-warning',
        'info': 'bg-info'
    }[type] || 'bg-info';
    
    const toastHtml = `
        <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    const toastContainer = $('#toast-container');
    if (toastContainer.length === 0) {
        $('body').append('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>');
    }
    
    $('#toast-container').append(toastHtml);
    const toastElement = $('#toast-container .toast').last()[0];
    const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
    toast.show();
    
    $(toastElement).on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

function confirmDelete(message, callback) {
    if (confirm(message || 'Sei sicuro di voler eliminare questo elemento?')) {
        callback();
    }
}

function previewImage(input, previewElement) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $(previewElement).attr('src', e.target.result).show();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function formatPrice(price) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function initDragAndDrop(containerSelector, onReorder) {
    const container = document.querySelector(containerSelector);
    if (!container) return;
    
    let draggedElement = null;
    
    const items = container.querySelectorAll('.draggable-item');
    items.forEach(item => {
        item.draggable = true;
        
        item.addEventListener('dragstart', function(e) {
            draggedElement = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        
        item.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
        });
        
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            if (draggedElement !== this) {
                const rect = this.getBoundingClientRect();
                const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
                container.insertBefore(draggedElement, next ? this.nextSibling : this);
            }
        });
    });
    
    container.addEventListener('drop', function(e) {
        e.preventDefault();
        if (onReorder) {
            const newOrder = Array.from(container.querySelectorAll('.draggable-item')).map(el => el.dataset.id);
            onReorder(newOrder);
        }
    });
}

function loadMenuPreview(localeSlug, containerId) {
    $.ajax({
        url: '/api/menu/preview.php',
        type: 'GET',
        data: { locale: localeSlug },
        success: function(response) {
            if (response.success) {
                $(`#${containerId}`).html(renderMenuPreview(response.data));
            }
        }
    });
}

function renderMenuPreview(menuData) {
    return 'Dati non disponibili';
}

function initSearchFilter() {
    $('#searchInput').on('keyup', debounce(function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.searchable-item').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(searchTerm) > -1);
        });
    }, 300));
}

function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    showToast('Copiato', 'Testo copiato negli appunti', 'success');
}

function downloadQRCode(qrId) {
    window.location.href = `/api/qrcode/download.php?id=${qrId}`;
}

function printQRCode(qrId) {
    const printWindow = window.open(`/api/qrcode/print.php?id=${qrId}`, '_blank');
    printWindow.onload = function() {
        printWindow.print();
    };
}

$(document).on('change', '.image-upload', function() {
    previewImage(this, $(this).data('preview'));
});

$(document).on('click', '.copy-link', function() {
    const link = $(this).data('link');
    copyToClipboard(link);
});

$(document).on('click', '.toggle-visibility', function() {
    const id = $(this).data('id');
    const type = $(this).data('type');
    
    $.ajax({
        url: `/api/${type}/toggle-visibility.php`,
        type: 'POST',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                showToast('Successo', response.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            }
        }
    });
});

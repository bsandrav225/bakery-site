document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar на мобильных
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    
    // Закрытие сайдбара при клике вне его на мобильных
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
    
    // Подтверждение удаления
    document.querySelectorAll('.delete-confirm').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите удалить этот элемент?')) {
                e.preventDefault();
            }
        });
    });
    
    // Авто-закрытие уведомлений
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 4000);
    });
    
    // Валидация форм
    document.querySelectorAll('form[data-validate]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const required = form.querySelectorAll('[required]');
            
            required.forEach(function(field) {
                if (!field.value.trim()) {
                    field.style.borderColor = '#E74C3C';
                    isValid = false;
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Пожалуйста, заполните все обязательные поля.');
            }
        });
    });
    
    // Поддержка Enter для поиска
    document.querySelectorAll('.search-input').forEach(function(input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    });
});
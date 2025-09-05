function showLoading(element) {
    element.classList.add('loading');
    element.disabled = true;
}

function hideLoading(element) {
    element.classList.remove('loading');
    element.disabled = false;
}

function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type} fade-in`;
    messageDiv.textContent = message;
    
    const existingMessages = document.querySelectorAll('.message');
    existingMessages.forEach(msg => msg.remove());
    
    const content = document.querySelector('.content, .form-container');
    if (content) {
        content.insertBefore(messageDiv, content.firstChild);
        
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

async function makeAjaxRequest(url, data, method = 'POST') {
    try {
        let formData;
        
        if (data instanceof FormData) {
            formData = data;
        } else {
            formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }
        }

        const response = await fetch(url, {
            method: method,
            body: formData
        });

        const rawText = await response.text();

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${rawText.slice(0, 200)}`);
        }

        try {
            const json = JSON.parse(rawText);
            console.log('AJAX Response:', json);
            return json;
        } catch (parseErr) {
            console.error('Non-JSON response:', rawText);
            return { success: false, message: 'Server returned invalid response.', raw: rawText };
        }
    } catch (error) {
        console.error('AJAX Error:', error);
        showMessage('An error occurred. Please try again.', 'error');
        return { success: false, error: error.message };
    }
}

function toggleAddForm() {
    const form = document.getElementById('addAttendanceForm');
    if (form) {
        form.classList.toggle('hidden');
        if (!form.classList.contains('hidden')) {
            form.scrollIntoView({ behavior: 'smooth' });
        }
    }
}

function toggleEditForm(attendanceId) {
    const form = document.getElementById('editForm-' + attendanceId);
    if (form) {
        form.classList.toggle('hidden');
        if (!form.classList.contains('hidden')) {
            form.scrollIntoView({ behavior: 'smooth' });
        }
    }
}

function toggleStudentFields() {
    const roleSelect = document.getElementById('role');
    const studentFields = document.getElementById('studentFields');
    const courseSelect = document.getElementById('course_id');
    const yearSelect = document.getElementById('year_level');
    
    if (roleSelect && studentFields && courseSelect && yearSelect) {
        if (roleSelect.value === 'student') {
            studentFields.style.display = 'block';
            studentFields.classList.remove('hidden');
            courseSelect.required = true;
            yearSelect.required = true;
        } else {
            studentFields.style.display = 'none';
            studentFields.classList.add('hidden');
            courseSelect.required = false;
            yearSelect.required = false;
            courseSelect.value = '';
            yearSelect.value = '';
        }
    }
}

async function handleAttendanceSubmit(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    showLoading(submitBtn);
    
    try {
        const formData = new FormData(form);
        const result = await makeAjaxRequest('CORE/handleforms.php', formData);
        
        if (result.success) {
            showMessage(result.message || 'Attendance saved successfully!', 'success');
            form.reset();
            toggleAddForm();
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showMessage(result.message || 'Failed to save attendance.', 'error');
        }
    } catch (error) {
        showMessage('An error occurred while saving attendance.', 'error');
    } finally {
        hideLoading(submitBtn);
    }
}

async function handleCourseSubmit(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    showLoading(submitBtn);
    
    try {
        const formData = new FormData(form);
        const result = await makeAjaxRequest('CORE/handleforms.php', formData);
        
        if (result.success) {
            showMessage(result.message || 'Course saved successfully!', 'success');
            form.reset();
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showMessage(result.message || 'Failed to save course.', 'error');
        }
    } catch (error) {
        showMessage('An error occurred while saving course.', 'error');
    } finally {
        hideLoading(submitBtn);
    }
}

async function handleRegistrationSubmit(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    showLoading(submitBtn);
    
    try {
        const formData = new FormData(form);
        formData.append('action', 'register');
        const result = await makeAjaxRequest('CORE/handleforms.php', formData);
        
        if (result.success) {
            showMessage('Registration successful! You can now log in.', 'success');
            form.reset();
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        } else {
            showMessage(result.message || 'Registration failed.', 'error');
        }
    } catch (error) {
        showMessage('An error occurred during registration.', 'error');
    } finally {
        hideLoading(submitBtn);
    }
}

async function handleLoginSubmit(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    showLoading(submitBtn);
    
    try {
        const formData = new FormData(form);
        formData.append('action', 'login');
        const result = await makeAjaxRequest('CORE/handleforms.php', formData);
        
        if (result.success) {
            showMessage('Login successful! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        } else {
            showMessage(result.message || 'Login failed.', 'error');
        }
    } catch (error) {
        showMessage('An error occurred during login.', 'error');
    } finally {
        hideLoading(submitBtn);
    }
}

async function confirmDeleteAttendance(attendanceId) {
    if (confirm('Are you sure you want to delete this attendance record?')) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_attendance');
            formData.append('attendance_id', attendanceId);
            const result = await makeAjaxRequest('CORE/handleforms.php', formData);
            
            if (result.success) {
                showMessage('Attendance record deleted successfully!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showMessage('Failed to delete attendance record.', 'error');
            }
        } catch (error) {
            showMessage('An error occurred while deleting attendance.', 'error');
        }
    }
}

async function confirmDeleteCourse(courseId) {
    if (confirm('Are you sure you want to delete this course?')) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('course_id', courseId);
            const result = await makeAjaxRequest('CORE/handleforms.php', formData);
            
            if (result.success) {
                showMessage('Course deleted successfully!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showMessage('Failed to delete course.', 'error');
            }
        } catch (error) {
            showMessage('An error occurred while deleting course.', 'error');
        }
    }
}

function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            field.style.borderColor = '#e0f2fe';
        }
    });

    const password = form.querySelector('input[name="password"]');
    const confirmPassword = form.querySelector('input[name="confirm_password"]');
    
    if (password && confirmPassword) {
        if (password.value !== confirmPassword.value) {
            confirmPassword.style.borderColor = '#ef4444';
            showMessage('Passwords do not match.', 'error');
            isValid = false;
        }
    }
    
    return isValid;
}

function initializeEventListeners() {
    const allForms = document.querySelectorAll('form');
    
    allForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (form.querySelector('select[name="attendance_status"]') || form.querySelector('input[name="action"][value*="attendance"]')) {
                if (validateForm(form)) {
                    handleAttendanceSubmit(form);
                }
            } else if (form.querySelector('input[name="course_name"]') || form.querySelector('input[name="action"][value*="create"]') || form.querySelector('input[name="action"][value*="update"]')) {

                if (validateForm(form)) {
                    handleCourseSubmit(form);
                }
            } else if (form.querySelector('input[name="confirm_password"]') || form.querySelector('select[name="role"]')) {

                if (validateForm(form)) {
                    handleRegistrationSubmit(form);
                }
            } else if (form.querySelector('input[name="email_or_username"]') && form.querySelector('input[name="password"]') && !form.querySelector('input[name="confirm_password"]')) {

                if (validateForm(form)) {
                    handleLoginSubmit(form);
                }
            } else {
                form.submit();
            }
        });
    });
    
    const roleSelect = document.getElementById('role');
    if (roleSelect) {
        roleSelect.addEventListener('change', toggleStudentFields);
    }
    
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '#e0f2fe';
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    
    const mainContent = document.querySelector('.content, .form-container');
    if (mainContent) {
        mainContent.classList.add('fade-in');
    }
    
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        setTimeout(() => {
            message.remove();
        }, 5000);
    });

    const searchInput = document.getElementById('attendanceSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const table = document.querySelector('table.table tbody');
            if (!table) return;
            const rows = table.querySelectorAll('tr');

            rows.forEach(row => {
                if (row.id && row.id.startsWith('editForm-')) return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});

function toggleCourseEdit(courseId) {
    const form = document.getElementById('editCourse-' + courseId);
    if (form) {
        form.classList.toggle('hidden');
        if (!form.classList.contains('hidden')) {
            form.scrollIntoView({ behavior: 'smooth' });
        }
    }
}

window.toggleAddForm = toggleAddForm;
window.toggleEditForm = toggleEditForm;
window.toggleStudentFields = toggleStudentFields;
window.toggleCourseEdit = toggleCourseEdit;
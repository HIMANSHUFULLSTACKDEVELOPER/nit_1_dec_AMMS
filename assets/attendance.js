// Attendance-specific JavaScript functions

// Mark all students with a specific status
function markAll(status) {
    const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
    radios.forEach(radio => {
        radio.checked = true;
    });
    showNotification(`All students marked as ${status.toUpperCase()}`, 'success');
}

// Toggle student status
function toggleStatus(studentId, status) {
    const radio = document.querySelector(`input[name="attendance[${studentId}]"][value="${status}"]`);
    if (radio) {
        radio.checked = true;
    }
}

// Count attendance by status
function countAttendance() {
    const presentCount = document.querySelectorAll('input[value="present"]:checked').length;
    const absentCount = document.querySelectorAll('input[value="absent"]:checked').length;
    const lateCount = document.querySelectorAll('input[value="late"]:checked').length;
    
    return {
        present: presentCount,
        absent: absentCount,
        late: lateCount,
        total: presentCount + absentCount + lateCount
    };
}

// Show attendance summary before submission
function showAttendanceSummary() {
    const counts = countAttendance();
    const message = `
        Attendance Summary:
        
        ✅ Present: ${counts.present}
        ❌ Absent: ${counts.absent}
        ⏰ Late: ${counts.late}
        📊 Total: ${counts.total}
        
        Do you want to submit this attendance?
    `;
    return confirm(message);
}

// Validate attendance form
function validateAttendance() {
    const counts = countAttendance();
    
    if (counts.total === 0) {
        showNotification('Please mark attendance for at least one student', 'error');
        return false;
    }
    
    // Check if all students have attendance marked
    const totalStudents = document.querySelectorAll('input[type="radio"][value="present"]').length;
    
    if (counts.total < totalStudents) {
        const unmarked = totalStudents - counts.total;
        const proceed = confirm(`${unmarked} student(s) have no attendance marked. Do you want to continue?`);
        if (!proceed) return false;
    }
    
    return showAttendanceSummary();
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add animations
const animationStyles = document.createElement('style');
animationStyles.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(animationStyles);

// Quick filters for attendance viewing
function filterByStatus(status) {
    const rows = document.querySelectorAll('table tbody tr');
    
    rows.forEach(row => {
        const statusCell = row.querySelector('.badge');
        if (!statusCell) return;
        
        const statusText = statusCell.textContent.trim().toLowerCase();
        
        if (status === 'all') {
            row.style.display = '';
        } else {
            row.style.display = statusText === status ? '' : 'none';
        }
    });
}

// Calculate attendance percentage
function calculatePercentage(present, total) {
    if (total === 0) return 0;
    return ((present / total) * 100).toFixed(2);
}

// Highlight low attendance
function highlightLowAttendance(threshold = 75) {
    const percentageCells = document.querySelectorAll('.attendance-percentage');
    
    percentageCells.forEach(cell => {
        const percentage = parseFloat(cell.textContent);
        
        if (percentage < threshold) {
            cell.style.color = '#dc3545';
            cell.style.fontWeight = 'bold';
            
            // Add warning icon
            if (!cell.querySelector('.warning-icon')) {
                const icon = document.createElement('span');
                icon.className = 'warning-icon';
                icon.textContent = ' ⚠️';
                cell.appendChild(icon);
            }
        }
    });
}

// Auto-save draft
let autoSaveTimer;
function enableAutoSave() {
    const form = document.querySelector('form');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('change', () => {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                saveDraft(form);
            }, 2000);
        });
    });
}

function saveDraft(form) {
    const formData = new FormData(form);
    const data = {};
    
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    localStorage.setItem('attendance_draft', JSON.stringify(data));
    showNotification('Draft saved', 'info');
}

function loadDraft() {
    const draft = localStorage.getItem('attendance_draft');
    
    if (draft) {
        const proceed = confirm('Found a saved draft. Do you want to load it?');
        
        if (proceed) {
            const data = JSON.parse(draft);
            
            Object.keys(data).forEach(key => {
                const input = document.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'radio') {
                        const radio = document.querySelector(`[name="${key}"][value="${data[key]}"]`);
                        if (radio) radio.checked = true;
                    } else {
                        input.value = data[key];
                    }
                }
            });
            
            showNotification('Draft loaded', 'success');
        }
    }
}

// Initialize attendance features
document.addEventListener('DOMContentLoaded', function() {
    // Attach form validation to attendance forms
    const attendanceForms = document.querySelectorAll('form[action*="save_attendance"]');
    attendanceForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateAttendance()) {
                e.preventDefault();
            }
        });
    });
    
    // Highlight low attendance
    highlightLowAttendance();
    
    // Enable auto-save for marking attendance
    if (window.location.pathname.includes('mark_attendance')) {
        enableAutoSave();
        loadDraft();
    }
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.click();
        }
        
        // Ctrl/Cmd + A to mark all present
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && window.location.pathname.includes('mark_attendance')) {
            e.preventDefault();
            markAll('present');
        }
    });
});
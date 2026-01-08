document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not([data-persistent])');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            document.getElementById('confirmDeleteBtn').onclick = function() {
                window.location.href = href;
            };
            modal.show();
        });
    });

    const testForms = document.querySelectorAll('.test-question-form');
    testForms.forEach(function(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                saveAnswer(form);
            });
        });
    });
});

function saveAnswer(form) {
    return new Promise(function(resolve, reject) {
        const formData = new FormData(form);
        const questionId = formData.get('question_id');
        const attemptId = formData.get('attempt_id');
        const testId = formData.get('test_id');
        
        let answer = null;
        const questionType = formData.get('question_type');
        
        if (questionType === 'single_choice') {
            const selectedRadio = form.querySelector('input[name="answer"]:checked');
            answer = selectedRadio ? selectedRadio.value : null;
        } else if (questionType === 'multiple_choice') {
            const selectedCheckboxes = form.querySelectorAll('input[name="answer[]"]:checked');
            answer = Array.from(selectedCheckboxes).map(cb => cb.value);
        } else if (questionType === 'true_false') {
            const selectedRadio = form.querySelector('input[name="answer"]:checked');
            answer = selectedRadio ? selectedRadio.value : null;
        } else if (questionType === 'short_answer') {
            answer = formData.get('answer');
        }
        
        const answerData = Array.isArray(answer) ? JSON.stringify(answer) : (answer !== null ? answer : '');
        
        fetch(`/test/${testId}/save/${attemptId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'csrf_token': formData.get('csrf_token'),
                'question_id': questionId,
                'answer': answerData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Answer saved:', answer);
                resolve(data);
            } else {
                console.error('Failed to save answer:', data.message);
                reject(new Error(data.message || 'Failed to save answer'));
            }
        })
        .catch(error => {
            console.error('Error saving answer:', error);
            reject(error);
        });
    });
}

function startTestTimer(durationInSeconds, callback) {
    let timeLeft = durationInSeconds;
    const timerElement = document.getElementById('test-timer');
    
    if (!timerElement) return;
    
    const interval = setInterval(function() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        timerElement.textContent = 
            String(minutes).padStart(2, '0') + ':' + 
            String(seconds).padStart(2, '0');
        
        if (timeLeft <= 0) {
            clearInterval(interval);
            if (callback) callback();
        }
        
        timeLeft--;
    }, 1000);
}

// API 엔드포인트 설정
const API_URL = '/api/submit.php';

// 폼 제출 처리
document.getElementById('applicationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const messageDiv = document.getElementById('message');
    const submitButton = this.querySelector('.submit-button');
    
    // 폼 데이터 수집
    const formData = {
        name: document.getElementById('name').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        date: document.querySelector('input[name="date"]:checked')?.value,
        agreement: document.getElementById('agreement').checked
    };
    
    // 클라이언트 측 검증
    if (!formData.name || !formData.phone || !formData.date || !formData.agreement) {
        showMessage('모든 필수 항목을 입력해주세요.', 'error');
        return;
    }
    
    // 전화번호 형식 검증
    const phoneRegex = /^[0-9-]+$/;
    if (!phoneRegex.test(formData.phone)) {
        showMessage('올바른 전화번호 형식을 입력해주세요.', 'error');
        return;
    }
    
    // 버튼 비활성화
    submitButton.disabled = true;
    submitButton.textContent = '전송 중...';
    
    try {
        // AJAX 요청
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            showMessage(result.message, 'success');
            // 폼 초기화
            document.getElementById('applicationForm').reset();
        } else {
            showMessage(result.message || '신청 처리 중 오류가 발생했습니다.', 'error');
        }
    } catch (error) {
        console.error('신청 오류:', error);
        showMessage('네트워크 오류가 발생했습니다. 다시 시도해주세요.', 'error');
    } finally {
        // 버튼 활성화
        submitButton.disabled = false;
        submitButton.textContent = '신청하기';
    }
});

// 메시지 표시 함수
function showMessage(text, type) {
    const messageDiv = document.getElementById('message');
    messageDiv.textContent = text;
    messageDiv.className = `message ${type}`;
    messageDiv.style.display = 'block';
    
    // 3초 후 메시지 자동 숨김 (성공 메시지인 경우)
    if (type === 'success') {
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
}

// 개인정보 처리방침 모달 열기
function showPrivacyModal(e) {
    e.preventDefault();
    document.getElementById('privacyModal').style.display = 'block';
}

// 개인정보 처리방침 모달 닫기
function closePrivacyModal() {
    document.getElementById('privacyModal').style.display = 'none';
}

// 모달 외부 클릭시 닫기
window.onclick = function(event) {
    const modal = document.getElementById('privacyModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// 전화번호 자동 하이픈 추가
document.getElementById('phone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/[^0-9]/g, '');
    
    if (value.length > 3 && value.length <= 7) {
        value = value.slice(0, 3) + '-' + value.slice(3);
    } else if (value.length > 7) {
        value = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
    }
    
    e.target.value = value;
});



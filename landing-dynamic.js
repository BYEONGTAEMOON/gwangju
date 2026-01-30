// 지역 정보 (각 페이지에서 설정되어야 함)
// HTML 파일에서 const REGION = '지역명'; 으로 먼저 선언되어야 합니다
if (typeof REGION === 'undefined') {
    console.error('❌ REGION 변수가 정의되지 않았습니다!');
}

// 메인 페이지 체크
const IS_MAIN = typeof IS_MAIN_PAGE !== 'undefined' && IS_MAIN_PAGE === true;

// API 엔드포인트
const API_URL = `/api/submit.php?region=${REGION}`;
const IMAGES_API_URL = `/api/images.php?region=${REGION}`;
const DATES_API_URL = `/api/dates.php?region=${REGION}`;

let availableDates = []; // 사용 가능한 날짜 목록

// 페이지 로드시 이미지 불러오기 (메인 페이지 제외)
document.addEventListener('DOMContentLoaded', async function () {
    if (IS_MAIN) {
        console.log('🏠 메인 페이지 - 동적 로딩 스킵');
        return; // 메인 페이지는 이미지 로딩하지 않음
    }

    console.log('🚀 페이지 로드 시작');
    console.log(
        '📍 현재 지역:',
        typeof REGION !== 'undefined' ? REGION : '정의되지 않음',
    );
    await loadDates();
    await loadImages();
});

// 날짜 목록 로드
async function loadDates() {
    try {
        const response = await fetch(DATES_API_URL);
        const result = await response.json();

        if (result.success && result.data) {
            availableDates = result.data;
        } else {
            // 기본값
            availableDates = ['1월3일(토)', '1월4일(일)'];
        }
    } catch (error) {
        console.error('날짜 로드 오류:', error);
        // 기본값
        availableDates = ['1월3일(토)', '1월4일(일)'];
    }
}

// 이미지 동적 로드
async function loadImages() {
    console.log('🔍 이미지 로드 시작');
    console.log('📍 현재 지역:', REGION);
    console.log('🔗 API URL:', IMAGES_API_URL);

    try {
        const response = await fetch(IMAGES_API_URL);
        console.log('📡 API 응답 상태:', response.status);

        const result = await response.json();
        console.log('📦 API 응답 데이터:', result);

        if (result.success) {
            const { pc, mobile } = result.data;
            console.log('✅ PC 이미지:', pc);
            console.log('✅ 모바일 이미지:', mobile);

            // PC 이미지 렌더링
            renderImages('pcImages', pc || [], 'pc');

            // 모바일 이미지 렌더링
            renderImages('mobileImages', mobile || [], 'mobile');
        } else {
            console.warn('⚠️ API 성공하지 못함:', result);
            // 데이터가 없어도 폼은 표시
            renderImages('pcImages', [], 'pc');
            renderImages('mobileImages', [], 'mobile');
        }
    } catch (error) {
        console.error('❌ 이미지 로드 오류:', error);
        // 오류가 발생해도 폼은 표시
        renderImages('pcImages', [], 'pc');
        renderImages('mobileImages', [], 'mobile');
    }
}

// 이미지 렌더링 함수
function renderImages(containerId, images, type) {
    console.log(`🎨 렌더링 시작: ${type}`, images);
    const container = document.getElementById(containerId);

    if (!container) {
        console.error(`❌ 컨테이너를 찾을 수 없음: ${containerId}`);
        return;
    }

    container.innerHTML = '';

    const formAnchor = type === 'pc' ? 'index_form_pc' : 'index_form_mob';

    // 이미지가 없을 때도 폼은 표시
    if (!images || images.length === 0) {
        console.warn(`⚠️ ${type} 이미지가 없습니다. 폼만 표시합니다.`);
        const formDiv = document.createElement('div');
        formDiv.className = 'form-container';
        formDiv.id = formAnchor;
        formDiv.innerHTML = createFormHTML(formAnchor);
        container.appendChild(formDiv);
        return;
    }

    let formAdded = false;

    images.forEach((imageSrc, index) => {
        // 이미지 2번째 다음에 폼 삽입
        if (index === 2 && !formAdded) {
            const formDiv = document.createElement('div');
            formDiv.className = 'form-container';
            formDiv.id = formAnchor;
            formDiv.innerHTML = createFormHTML(formAnchor);
            container.appendChild(formDiv);
            formAdded = true;
        }

        // 유튜브 영상인지 확인
        if (imageSrc.includes('youtube.com/embed/')) {
            const iframeWrapper = document.createElement('div');
            iframeWrapper.className = 'youtube-wrapper';
            iframeWrapper.innerHTML = `
                <iframe 
                    src="${imageSrc}" 
                    title="YouTube video player" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    referrerpolicy="strict-origin-when-cross-origin" 
                    allowfullscreen>
                </iframe>
            `;
            container.appendChild(iframeWrapper);
        } else {
            // 일반 이미지
            const img = document.createElement('img');
            img.src = imageSrc;
            img.alt = `이미지 ${index + 1}`;
            img.onclick = () => {
                document
                    .getElementById(formAnchor)
                    .scrollIntoView({ behavior: 'smooth' });
            };
            container.appendChild(img);
        }
    });

    // 폼이 아직 추가되지 않았으면 마지막에 추가
    if (!formAdded) {
        const formDiv = document.createElement('div');
        formDiv.className = 'form-container';
        formDiv.id = formAnchor;
        formDiv.innerHTML = createFormHTML(formAnchor);
        container.appendChild(formDiv);
    }
}

// 폼 HTML 생성
function createFormHTML(formId) {
    return `
        <section class="form-section">
            <div class="form-header">
                <div class="form-brand">Gwangju Wedding Fair</div>
                <h2 class="form-title">더스페셜 웨딩박람회 참가신청</h2>
                <div class="form-subtitle">참가비 <span class="strike-price">10,000</span>원</div>
            </div>
            <form id="applicationForm_${formId}" class="application-form">
                <div class="form-group">
                    <label for="name_${formId}">성함 <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="name_${formId}" 
                        name="name" 
                        required 
                        placeholder="이름을 입력해주세요"
                    >
                </div>

                <div class="form-group">
                    <label for="phone_${formId}">연락처 <span class="required">*</span></label>
                    <input 
                        type="tel" 
                        id="phone_${formId}" 
                        name="phone" 
                        required 
                        placeholder="010-1234-5678"
                    >
                </div>

                <div class="form-group">
                    <label>참가일자 <span class="required">*</span></label>
                    <div class="radio-group">
                        ${availableDates
                            .map(
                                (date, index) => `
                            <label class="radio-label">
                                <input type="radio" name="date_${formId}" value="${date}" ${index === 0 ? '' : ''} required>
                                <span>${date}</span>
                            </label>
                        `,
                            )
                            .join('')}
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            id="agreement_${formId}" 
                            name="agreement" 
                            required
                        >
                        <span>
                            개인정보 수집 및 이용에 동의합니다. 
                            <a href="#privacy" class="privacy-link" onclick="showPrivacyModal(event)">자세히보기</a>
                        </span>
                    </label>
                </div>

                <button type="submit" class="submit-button">신청하기</button>
            </form>

            <div id="message_${formId}" class="message"></div>
        </section>
    `;
}

// 폼 제출 이벤트 위임
document.addEventListener('submit', async function (e) {
    if (e.target.classList.contains('application-form')) {
        e.preventDefault();
        await handleFormSubmit(e.target);
    }
});

// 폼 제출 처리
async function handleFormSubmit(form) {
    const formId = form.id;
    const submitButton = form.querySelector('.submit-button');

    // 버튼 비활성화
    submitButton.disabled = true;
    submitButton.textContent = '전송 중...';

    try {
        // FormData 방식으로 변경 (서버 WAF 호환)
        const formData = new FormData(form);

        // 날짜 필드 찾기
        const dateField = Object.keys(Object.fromEntries(formData)).find(
            (key) => key.startsWith('date_'),
        );

        // 새로운 FormData 생성 (올바른 필드명으로)
        const submitData = new FormData();
        submitData.append('name', formData.get('name'));
        submitData.append('phone', formData.get('phone'));
        submitData.append('date', formData.get(dateField));
        submitData.append(
            'agreement',
            formData.get('agreement') === 'on' ? 'true' : 'false',
        );
        submitData.append('region', REGION);

        console.log('📤 폼 제출:', {
            name: formData.get('name'),
            phone: formData.get('phone'),
            date: formData.get(dateField),
            region: REGION,
        });

        const response = await fetch(API_URL, {
            method: 'POST',
            body: submitData, // Content-Type 자동 설정됨
        });

        const result = await response.json();
        console.log('📥 서버 응답:', result);

        if (response.ok && result.success) {
            // 성공 팝업 표시
            showSuccessModal();
            form.reset();
        } else {
            // 에러는 기존 방식으로 표시
            showFormMessage(
                formId,
                result.message || '신청 처리 중 오류가 발생했습니다.',
                'error',
            );
        }
    } catch (error) {
        console.error('❌ 신청 오류:', error);
        showFormMessage(
            formId,
            '네트워크 오류가 발생했습니다. 다시 시도해주세요.',
            'error',
        );
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = '신청하기';
    }
}

// 폼 메시지 표시
function showFormMessage(formId, text, type) {
    const parts = formId.split('_');
    const messageId = `message_${parts[1]}_${parts[2]}`;
    const messageDiv = document.getElementById(messageId);

    if (messageDiv) {
        messageDiv.textContent = text;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';

        if (type === 'success') {
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    }
}

// 전화번호 자동 포맷팅 (이벤트 위임)
document.addEventListener('input', function (e) {
    if (e.target.type === 'tel') {
        let value = e.target.value.replace(/[^0-9]/g, '');

        if (value.length > 3 && value.length <= 7) {
            value = value.slice(0, 3) + '-' + value.slice(3);
        } else if (value.length > 7) {
            value =
                value.slice(0, 3) +
                '-' +
                value.slice(3, 7) +
                '-' +
                value.slice(7, 11);
        }

        e.target.value = value;
    }
});

// 신청 완료 모달
function showSuccessModal() {
    document.getElementById('successModal').style.display = 'block';
}

function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
}

// 개인정보 처리방침 모달
function showPrivacyModal(e) {
    e.preventDefault();
    document.getElementById('privacyModal').style.display = 'block';
}

function closePrivacyModal() {
    document.getElementById('privacyModal').style.display = 'none';
}

window.onclick = function (event) {
    const privacyModal = document.getElementById('privacyModal');
    const successModal = document.getElementById('successModal');

    if (event.target === privacyModal) {
        privacyModal.style.display = 'none';
    }
    if (event.target === successModal) {
        successModal.style.display = 'none';
    }
};

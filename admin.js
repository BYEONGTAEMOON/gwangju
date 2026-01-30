// 페이지 상태
let currentPage = 'login';
let pcImages = [];
let mobileImages = [];
let allSubmissions = []; // 엑셀 다운로드용 전체 데이터 저장
let dates = []; // 날짜 목록
let blockedList = []; // 차단 목록
let currentRegion = 'main'; // 현재 선택된 지역 (메인만 운영)

// 페이지 로드
document.addEventListener('DOMContentLoaded', function () {
    checkAuth();
    setupEventListeners();
});

// 이벤트 리스너 설정
function setupEventListeners() {
    // 로그인
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // 로그아웃
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

    // 탭 전환
    document.querySelectorAll('.nav-btn').forEach((btn) => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    // 새로고침
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', loadSubmissions);
    }

    // 엑셀 다운로드
    const downloadExcelBtn = document.getElementById('downloadExcelBtn');
    if (downloadExcelBtn) {
        downloadExcelBtn.addEventListener('click', downloadExcel);
    }

    // 이미지 추가
    document.querySelectorAll('.add-image-btn').forEach((btn) => {
        btn.addEventListener('click', () => addImageInput(btn.dataset.type));
    });

    // 이미지 저장
    const saveImagesBtn = document.getElementById('saveImagesBtn');
    if (saveImagesBtn) {
        saveImagesBtn.addEventListener('click', saveImages);
    }

    // 이메일 설정 저장
    const saveEmailBtn = document.getElementById('saveEmailBtn');
    if (saveEmailBtn) {
        saveEmailBtn.addEventListener('click', saveEmailSettings);
    }

    // 테스트 이메일 발송
    const testEmailBtn = document.getElementById('testEmailBtn');
    if (testEmailBtn) {
        testEmailBtn.addEventListener('click', sendTestEmail);
    }

    // 파일 업로드
    const pcFileInput = document.getElementById('pcFileInput');
    if (pcFileInput) {
        pcFileInput.addEventListener('change', (e) => handleImageUpload(e, 'pc'));
    }

    const mobileFileInput = document.getElementById('mobileFileInput');
    if (mobileFileInput) {
        mobileFileInput.addEventListener('change', (e) => handleImageUpload(e, 'mobile'));
    }

    // 날짜 관리
    const addDateBtn = document.getElementById('addDateBtn');
    if (addDateBtn) {
        addDateBtn.addEventListener('click', addDate);
    }

    const saveDatesBtn = document.getElementById('saveDatesBtn');
    if (saveDatesBtn) {
        saveDatesBtn.addEventListener('click', saveDates);
    }

    // 차단 관리
    const addBlockedBtn = document.getElementById('addBlockedBtn');
    if (addBlockedBtn) {
        addBlockedBtn.addEventListener('click', addBlocked);
    }

    // 지역 선택
    const regionSelect = document.getElementById('regionSelect');
    if (regionSelect) {
        regionSelect.addEventListener('change', handleRegionChange);
    }
}

// 지역 변경 처리
function handleRegionChange(e) {
    currentRegion = e.target.value;
    // 현재 탭에 따라 데이터 다시 로드
    const activeTab = document.querySelector('.tab-content.active');
    if (activeTab) {
        const tabId = activeTab.id.replace('Tab', '');
        switchTab(tabId);
    }
}

// 인증 확인
async function checkAuth() {
    try {
        const response = await fetch('/api/admin/check-session.php');
        const data = await response.json();
        
        if (data.success) {
            // 사용자 권한 정보 저장
            window.adminRole = data.role;
            window.adminRegions = data.regions || [];
            
            showPage('dashboard');
            
            // 지역 드롭다운 제한 (메인만 운영 - 드롭다운이 있을 경우에만)
            const regionSelect = document.getElementById('regionSelect');
            if (regionSelect) {
                Array.from(regionSelect.options).forEach(option => {
                    if (!window.adminRegions.includes(option.value)) {
                        option.style.display = 'none';
                        option.disabled = true;
                    } else {
                        option.style.display = 'block';
                        option.disabled = false;
                    }
                });
                
                // 첫 번째 허용된 지역으로 설정
                if (window.adminRegions.length > 0) {
                    currentRegion = window.adminRegions[0];
                    regionSelect.value = currentRegion;
                }
            } else {
                // 지역 선택 드롭다운이 없으면 메인으로 고정
                currentRegion = 'main';
            }
            
            loadSubmissions();
            loadImagesConfig();
        } else {
            showPage('login');
        }
    } catch {
        showPage('login');
    }
}

// 페이지 전환
function showPage(page) {
    currentPage = page;
    document
        .querySelectorAll('.page')
        .forEach((p) => p.classList.remove('active'));
    document.getElementById(`${page}Page`).classList.add('active');
}

// 탭 전환
function switchTab(tab) {
    document
        .querySelectorAll('.nav-btn')
        .forEach((btn) => btn.classList.remove('active'));
    document
        .querySelectorAll('.tab-content')
        .forEach((content) => content.classList.remove('active'));

    event.target.classList.add('active');
    document.getElementById(`${tab}Tab`).classList.add('active');

    if (tab === 'submissions') {
        loadSubmissions();
    } else if (tab === 'dates') {
        loadDates();
    } else if (tab === 'blocked') {
        loadBlocked();
    } else if (tab === 'images') {
        loadImagesConfig();
    } else if (tab === 'email') {
        loadEmailSettings();
    }
}

// 로그인 처리
async function handleLogin(e) {
    e.preventDefault();

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    try {
        // JSON 대신 FormData 사용 (서버 WAF 차단 회피)
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);

        const response = await fetch('/api/admin/login.php', {
            method: 'POST',
            body: formData  // Content-Type 자동 설정됨
        });

        const result = await response.json();

        if (result.success) {
            // 사용자 권한 정보 저장
            window.adminRole = result.role;
            window.adminRegions = result.regions || [];
            
            showMessage('loginMessage', '로그인 성공!', 'success');
            setTimeout(() => {
                showPage('dashboard');
                
                // 지역 드롭다운 제한
                const regionSelect = document.getElementById('regionSelect');
                Array.from(regionSelect.options).forEach(option => {
                    if (!window.adminRegions.includes(option.value)) {
                        option.style.display = 'none';
                        option.disabled = true;
                    } else {
                        option.style.display = 'block';
                        option.disabled = false;
                    }
                });
                
                // 첫 번째 허용된 지역으로 설정
                if (window.adminRegions.length > 0) {
                    currentRegion = window.adminRegions[0];
                    regionSelect.value = currentRegion;
                }
                
                loadSubmissions();
                loadImagesConfig();
            }, 500);
        } else {
            showMessage('loginMessage', result.message, 'error');
        }
    } catch (error) {
        console.error('로그인 오류:', error);
        showMessage('loginMessage', '로그인 중 오류가 발생했습니다.', 'error');
    }
}

// 로그아웃 처리
async function handleLogout() {
    try {
        await fetch('/api/admin/logout.php', { method: 'POST' });
        showPage('login');
        document.getElementById('loginForm').reset();
    } catch (error) {
        console.error('로그아웃 오류:', error);
    }
}

// 신청 내역 로드
async function loadSubmissions() {
    const tbody = document.getElementById('submissionsBody');
    tbody.innerHTML =
        '<tr><td colspan="6" class="loading">데이터를 불러오는 중...</td></tr>';

    try {
        const response = await fetch(
            `/api/admin/submissions.php?region=${currentRegion}`
        );
        const result = await response.json();

        if (!response.ok) {
            if (response.status === 401) {
                showPage('login');
                return;
            }
            throw new Error(result.message);
        }

        const submissions = result.data;
        allSubmissions = submissions; // 전체 데이터 저장 (엑셀 다운로드용)
        document.getElementById('totalCount').textContent = submissions.length;

        if (submissions.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="6" class="loading">신청 내역이 없습니다.</td></tr>';
            return;
        }

        tbody.innerHTML = submissions
            .map(
                (item, index) => `
            <tr>
                <td>${submissions.length - index}</td>
                <td>${item.name}</td>
                <td>${item.phone}</td>
                <td>${item.date}</td>
                <td>${new Date(item.createdAt).toLocaleString('ko-KR')}</td>
                <td>
                    <button class="btn-delete" onclick="deleteSubmission('${
                        item.id
                    }')">삭제</button>
                    <button class="btn-block" onclick="blockFromSubmission('${
                        item.phone
                    }', '${item.name}')">차단</button>
                </td>
            </tr>
        `
            )
            .join('');
    } catch (error) {
        tbody.innerHTML =
            '<tr><td colspan="6" class="loading">데이터를 불러올 수 없습니다.</td></tr>';
        console.error('신청 내역 로드 오류:', error);
    }
}

// 신청 삭제
async function deleteSubmission(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    try {
        // FormData 방식으로 변경 (서버 WAF 호환)
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_method', 'DELETE'); // 삭제 요청 표시

        const response = await fetch(
            `/api/admin/submissions.php?region=${currentRegion}`,
            {
                method: 'POST', // POST로 전송 (DELETE 대신)
                body: formData
            }
        );

        const result = await response.json();

        if (result.success) {
            loadSubmissions();
        } else {
            alert(result.message);
        }
    } catch (error) {
        alert('삭제 중 오류가 발생했습니다.');
        console.error('삭제 오류:', error);
    }
}

// 이미지 설정 로드
async function loadImagesConfig() {
    try {
        const response = await fetch(
            `/api/admin/images.php?region=${currentRegion}`
        );
        const result = await response.json();

        if (!response.ok) {
            if (response.status === 401) {
                showPage('login');
                return;
            }
            throw new Error(result.message);
        }

        pcImages = result.data.pc || [];
        mobileImages = result.data.mobile || [];

        renderImageInputs();
    } catch (error) {
        console.error('이미지 설정 로드 오류:', error);
    }
}

// 이미지 입력 필드 렌더링
function renderImageInputs() {
    renderImageList('pcImagesList', pcImages, 'pc');
    renderImageList('mobileImagesList', mobileImages, 'mobile');
}

function renderImageList(containerId, images, type) {
    const container = document.getElementById(containerId);
    container.innerHTML = images
        .map((url, index) => {
            const isYoutube = url.includes('youtube.com/embed/');
            const previewHTML = isYoutube
                ? `<div class="youtube-preview">
                        <iframe src="${url}" frameborder="0" allowfullscreen></iframe>
                       </div>`
                : `<img src="${url}" alt="이미지 ${
                      index + 1
                  }" onerror="this.style.display='none'">`;

            return `
        <div class="image-item">
            <div class="image-preview">
                ${previewHTML}
                <div class="image-info">
                    <input type="text" value="${url}" data-type="${type}" data-index="${index}" placeholder="/images/example.jpg 또는 https://www.youtube.com/embed/VIDEO_ID">
                </div>
            </div>
            <div style="display: flex; gap: 5px; margin-top: 10px;">
                <button class="btn-remove" onclick="removeImage('${type}', ${index})">목록에서 제거</button>
                ${
                    !isYoutube
                        ? `<button class="btn-delete-image" onclick="deleteImageFile('${url}', '${type}')">파일 삭제</button>`
                        : ''
                }
            </div>
        </div>
    `;
        })
        .join('');
}

// 이미지 입력 필드 추가
function addImageInput(type) {
    if (type === 'pc') {
        pcImages.push('');
    } else {
        mobileImages.push('');
    }
    renderImageInputs();
}

// 이미지 삭제
function removeImage(type, index) {
    if (type === 'pc') {
        pcImages.splice(index, 1);
    } else {
        mobileImages.splice(index, 1);
    }
    renderImageInputs();
}

// 이미지 설정 저장
async function saveImages() {
    // 입력 필드에서 값 수집
    const pcInputs = document.querySelectorAll('#pcImagesList input');
    const mobileInputs = document.querySelectorAll('#mobileImagesList input');

    pcImages = Array.from(pcInputs)
        .map((input) => input.value.trim())
        .filter((v) => v);
    mobileImages = Array.from(mobileInputs)
        .map((input) => input.value.trim())
        .filter((v) => v);

    try {
        // FormData 방식으로 변경
        const formData = new FormData();
        formData.append('pc', JSON.stringify(pcImages));
        formData.append('mobile', JSON.stringify(mobileImages));

        const response = await fetch(
            `/api/admin/images.php?region=${currentRegion}`,
            {
                method: 'POST',
                body: formData
            }
        );

        const result = await response.json();

        if (result.success) {
            showMessage(
                'imageMessage',
                '이미지 설정이 저장되었습니다.',
                'success'
            );
            setTimeout(() => {
                document.getElementById('imageMessage').style.display = 'none';
            }, 3000);
        } else {
            showMessage('imageMessage', result.message, 'error');
        }
    } catch (error) {
        showMessage('imageMessage', '저장 중 오류가 발생했습니다.', 'error');
        console.error('이미지 저장 오류:', error);
    }
}

// 엑셀(CSV) 다운로드
function downloadExcel() {
    if (!allSubmissions || allSubmissions.length === 0) {
        alert('다운로드할 데이터가 없습니다.');
        return;
    }

    // CSV 헤더
    const headers = ['번호', '이름', '연락처', '참가일자', '신청일시'];

    // CSV 데이터 생성
    const csvData = allSubmissions.map((item, index) => {
        return [
            allSubmissions.length - index, // 번호 (역순)
            item.name,
            item.phone,
            item.date,
            new Date(item.createdAt).toLocaleString('ko-KR'),
        ];
    });

    // CSV 문자열 생성
    let csvContent = '\uFEFF'; // UTF-8 BOM for Excel
    csvContent += headers.join(',') + '\n';
    csvData.forEach((row) => {
        csvContent += row.join(',') + '\n';
    });

    // Blob 생성 및 다운로드
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    // 파일명에 현재 날짜 포함
    const now = new Date();
    const dateStr = `${now.getFullYear()}${String(now.getMonth() + 1).padStart(
        2,
        '0'
    )}${String(now.getDate()).padStart(2, '0')}`;
    const timeStr = `${String(now.getHours()).padStart(2, '0')}${String(
        now.getMinutes()
    ).padStart(2, '0')}`;

    link.setAttribute('href', url);
    link.setAttribute('download', `신청내역_${dateStr}_${timeStr}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    console.log(`${allSubmissions.length}건의 데이터를 다운로드했습니다.`);
}

// 메시지 표시
function showMessage(elementId, text, type) {
    const element = document.getElementById(elementId);
    element.textContent = text;
    element.className = `message ${type}`;
    element.style.display = 'block';
}

// 이미지 파일 업로드
async function handleImageUpload(event, type) {
    const file = event.target.files[0];
    if (!file) return;

    const statusId = `${type}UploadStatus`;
    const statusElement = document.getElementById(statusId);

    // 파일 타입 체크
    if (!file.type.startsWith('image/')) {
        statusElement.textContent = '이미지 파일만 업로드 가능합니다.';
        statusElement.className = 'upload-status error';
        return;
    }

    // 파일 크기 체크 (5MB)
    if (file.size > 5 * 1024 * 1024) {
        statusElement.textContent = '파일 크기는 5MB 이하여야 합니다.';
        statusElement.className = 'upload-status error';
        return;
    }

    // 업로드 진행 중
    statusElement.textContent = '업로드 중...';
    statusElement.className = 'upload-status uploading';

    try {
        const formData = new FormData();
        formData.append('image', file);
        formData.append('type', type);
        formData.append('region', currentRegion); // 현재 선택된 지역 추가

        const response = await fetch('/api/admin/upload.php', {
            method: 'POST',
            body: formData,
        });

        const result = await response.json();

        if (result.success) {
            statusElement.textContent = '업로드 완료!';
            statusElement.className = 'upload-status success';

            // 이미지 목록 새로고침
            await loadImagesConfig();

            // 3초 후 상태 메시지 숨김
            setTimeout(() => {
                statusElement.className = 'upload-status';
            }, 3000);
        } else {
            statusElement.textContent = result.message || '업로드 실패';
            statusElement.className = 'upload-status error';
        }
    } catch (error) {
        console.error('업로드 오류:', error);
        statusElement.textContent = '업로드 중 오류가 발생했습니다.';
        statusElement.className = 'upload-status error';
    }

    // 파일 입력 초기화
    event.target.value = '';
}

// 이미지 파일 삭제
async function deleteImageFile(path, type) {
    if (
        !confirm(
            '이미지 파일을 서버에서 완전히 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.'
        )
    ) {
        return;
    }

    try {
        // FormData 방식으로 변경
        const formData = new FormData();
        formData.append('path', path);
        formData.append('type', type);
        formData.append('region', currentRegion);
        formData.append('_method', 'DELETE');

        const response = await fetch('/api/admin/delete-image.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage('imageMessage', '이미지가 삭제되었습니다.', 'success');
            await loadImagesConfig();

            setTimeout(() => {
                document.getElementById('imageMessage').style.display = 'none';
            }, 3000);
        } else {
            showMessage('imageMessage', result.message || '삭제 실패', 'error');
        }
    } catch (error) {
        console.error('삭제 오류:', error);
        showMessage('imageMessage', '삭제 중 오류가 발생했습니다.', 'error');
    }
}

// 날짜 목록 로드
async function loadDates() {
    try {
        const response = await fetch(
            `/api/admin/dates-v2.php?region=${currentRegion}`
        );
        const result = await response.json();

        if (!response.ok) {
            if (response.status === 401) {
                showPage('login');
                return;
            }
            throw new Error(result.message);
        }

        dates = result.data || [];
        renderDates();
    } catch (error) {
        console.error('날짜 목록 로드 오류:', error);
    }
}

// 날짜 목록 렌더링
function renderDates() {
    const container = document.getElementById('datesList');

    if (dates.length === 0) {
        container.innerHTML =
            '<p class="empty-message">등록된 날짜가 없습니다.</p>';
        return;
    }

    container.innerHTML = dates
        .map(
            (date, index) => `
            <div class="date-item">
                <input 
                    type="text" 
                    value="${date.value}" 
                    data-index="${index}"
                    class="date-input"
                    placeholder="예: 1월3일(토)"
                />
                <label class="toggle-label">
                    <input 
                        type="checkbox" 
                        ${date.enabled ? 'checked' : ''} 
                        data-index="${index}"
                        class="date-toggle"
                    />
                    <span>활성화</span>
                </label>
                <button class="btn-delete" onclick="removeDate(${index})">삭제</button>
            </div>
        `
        )
        .join('');
}

// 날짜 추가
function addDate() {
    dates.push({ value: '', enabled: true });
    renderDates();
}

// 날짜 제거
function removeDate(index) {
    if (!confirm('이 날짜를 삭제하시겠습니까?')) return;
    dates.splice(index, 1);
    renderDates();
}

// 날짜 저장
async function saveDates() {
    // 입력값 수집
    const inputs = document.querySelectorAll('.date-input');
    const toggles = document.querySelectorAll('.date-toggle');

    dates = Array.from(inputs)
        .map((input, index) => ({
            value: input.value.trim(),
            enabled: toggles[index].checked,
        }))
        .filter((date) => date.value); // 빈 값 제외

    console.log('💾 날짜 저장 시작:', dates);

    try {
        // FormData 방식으로 전송
        const formData = new FormData();
        formData.append('dates', JSON.stringify(dates));

        const response = await fetch(
            `/api/admin/dates-v2.php?region=${currentRegion}`,
            {
                method: 'POST',
                body: formData,
            }
        );

        const result = await response.json();
        console.log('📥 서버 응답:', result);

        if (result.success) {
            showMessage(
                'datesMessage',
                '날짜 설정이 저장되었습니다.',
                'success'
            );
            setTimeout(() => {
                document.getElementById('datesMessage').style.display = 'none';
            }, 3000);
            loadDates();
        } else {
            showMessage('datesMessage', result.message, 'error');
        }
    } catch (error) {
        showMessage('datesMessage', '저장 중 오류가 발생했습니다.', 'error');
        console.error('날짜 저장 오류:', error);
    }
}

// 차단 목록 로드
async function loadBlocked() {
    const tbody = document.getElementById('blockedBody');
    tbody.innerHTML =
        '<tr><td colspan="5" class="loading">데이터를 불러오는 중...</td></tr>';

    try {
        const response = await fetch('/api/admin/blocked.php');
        const result = await response.json();

        if (!response.ok) {
            if (response.status === 401) {
                showPage('login');
                return;
            }
            throw new Error(result.message);
        }

        blockedList = result.data || [];
        document.getElementById('blockedCount').textContent =
            blockedList.length;

        if (blockedList.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="5" class="loading">차단된 번호가 없습니다.</td></tr>';
            return;
        }

        tbody.innerHTML = blockedList
            .map(
                (item, index) => `
                <tr>
                    <td>${blockedList.length - index}</td>
                    <td>${item.phone}</td>
                    <td>${item.reason || '-'}</td>
                    <td>${new Date(item.createdAt).toLocaleString('ko-KR')}</td>
                    <td>
                        <button class="btn-delete" onclick="unblockPhone('${
                            item.id
                        }')">차단 해제</button>
                    </td>
                </tr>
            `
            )
            .join('');
    } catch (error) {
        tbody.innerHTML =
            '<tr><td colspan="5" class="loading">데이터를 불러올 수 없습니다.</td></tr>';
        console.error('차단 목록 로드 오류:', error);
    }
}

// 차단 추가
async function addBlocked() {
    const phone = document.getElementById('blockedPhone').value.trim();
    const reason = document.getElementById('blockedReason').value.trim();

    if (!phone) {
        alert('전화번호를 입력해주세요.');
        return;
    }

    try {
        // FormData 방식으로 변경
        const formData = new FormData();
        formData.append('phone', phone);
        formData.append('reason', reason);

        const response = await fetch('/api/admin/blocked.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage(
                'blockedMessage',
                '차단 목록에 추가되었습니다.',
                'success'
            );
            document.getElementById('blockedPhone').value = '';
            document.getElementById('blockedReason').value = '';
            setTimeout(() => {
                document.getElementById('blockedMessage').style.display =
                    'none';
            }, 3000);
            loadBlocked();
        } else {
            showMessage('blockedMessage', result.message, 'error');
        }
    } catch (error) {
        showMessage('blockedMessage', '추가 중 오류가 발생했습니다.', 'error');
        console.error('차단 추가 오류:', error);
    }
}

// 차단 해제
async function unblockPhone(id) {
    if (!confirm('차단을 해제하시겠습니까?')) return;

    try {
        // FormData 방식으로 변경 (서버 WAF 호환)
        const formData = new FormData();
        formData.append('id', id);
        formData.append('_method', 'DELETE');

        const response = await fetch('/api/admin/blocked.php', {
            method: 'POST', // POST로 전송
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            loadBlocked();
        } else {
            alert(result.message);
        }
    } catch (error) {
        alert('차단 해제 중 오류가 발생했습니다.');
        console.error('차단 해제 오류:', error);
    }
}

// 신청 내역에서 바로 차단
async function blockFromSubmission(phone, name) {
    if (!confirm(`${name} (${phone})을 차단하시겠습니까?`)) return;

    try {
        // FormData 방식으로 변경
        const formData = new FormData();
        formData.append('phone', phone);
        formData.append('reason', `신청 내역에서 차단 (${name})`);

        const response = await fetch('/api/admin/blocked.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert('차단되었습니다.');
        } else {
            alert(result.message);
        }
    } catch (error) {
        alert('차단 중 오류가 발생했습니다.');
        console.error('차단 오류:', error);
    }
}

// ==================== 이메일 설정 ====================

// 이메일 설정 로드
async function loadEmailSettings() {
    try {
        const response = await fetch('/api/admin/email-settings.php');
        const result = await response.json();

        if (!response.ok) {
            if (response.status === 401) {
                showPage('login');
                return;
            }
            throw new Error(result.message);
        }

        if (result.success && result.data) {
            const settings = result.data;
            document.getElementById('emailEnabled').checked = settings.enabled || false;
            document.getElementById('emailTo').value = settings.email_to || '';
            document.getElementById('emailSubject').value = settings.email_subject || '[허니문박람회] 새로운 신청이 접수되었습니다';
        }
    } catch (error) {
        console.error('이메일 설정 로드 오류:', error);
        showMessage('emailMessage', '이메일 설정을 불러오는 중 오류가 발생했습니다.', 'error');
    }
}

// 이메일 설정 저장
async function saveEmailSettings() {
    const enabled = document.getElementById('emailEnabled').checked;
    const email_to = document.getElementById('emailTo').value.trim();
    const email_subject = document.getElementById('emailSubject').value.trim();

    if (enabled && !email_to) {
        showMessage('emailMessage', '받는 사람 이메일을 입력해주세요.', 'error');
        return;
    }

    // 이메일 형식 검증
    if (enabled && email_to) {
        const emails = email_to.split(',').map(e => e.trim());
        for (const email of emails) {
            if (!validateEmail(email)) {
                showMessage('emailMessage', `잘못된 이메일 형식입니다: ${email}`, 'error');
                return;
            }
        }
    }

    try {
        const formData = new FormData();
        formData.append('enabled', enabled ? 'true' : 'false');
        formData.append('email_to', email_to);
        formData.append('email_subject', email_subject);

        const response = await fetch('/api/admin/email-settings.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage('emailMessage', '이메일 설정이 저장되었습니다.', 'success');
            setTimeout(() => {
                document.getElementById('emailMessage').style.display = 'none';
            }, 3000);
        } else {
            showMessage('emailMessage', result.message, 'error');
        }
    } catch (error) {
        console.error('이메일 설정 저장 오류:', error);
        showMessage('emailMessage', '저장 중 오류가 발생했습니다.', 'error');
    }
}

// 테스트 이메일 발송
async function sendTestEmail() {
    const enabled = document.getElementById('emailEnabled').checked;
    const email_to = document.getElementById('emailTo').value.trim();

    if (!enabled) {
        showMessage('emailMessage', '먼저 이메일 알림을 활성화해주세요.', 'error');
        return;
    }

    if (!email_to) {
        showMessage('emailMessage', '받는 사람 이메일을 입력해주세요.', 'error');
        return;
    }

    // 설정을 먼저 저장
    await saveEmailSettings();

    showMessage('emailMessage', '테스트 이메일을 발송 중입니다...', 'info');

    try {
        // 전용 테스트 API 호출
        const response = await fetch(`/api/admin/test-email.php?region=${currentRegion}`, {
            method: 'POST'
        });

        const result = await response.json();

        if (result.success) {
            showMessage('emailMessage', '✅ ' + result.message, 'success');
        } else {
            showMessage('emailMessage', '❌ ' + result.message, 'error');
        }
    } catch (error) {
        console.error('테스트 이메일 발송 오류:', error);
        showMessage('emailMessage', '테스트 이메일 발송 중 오류가 발생했습니다.', 'error');
    }
}

// 이메일 형식 검증
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

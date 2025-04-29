// ✅ 로딩 오버레이 표시 함수
function showLoadingOverlay(message) {
  // 기존 오버레이가 있으면 제거
  hideLoadingOverlay();

  // 새 오버레이 생성
  const overlay = document.createElement('div');
  overlay.id = 'loadingOverlay';
  overlay.style.cssText = `
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		z-index: 9999;
	`;

  // 로딩 스피너
  const spinner = document.createElement('div');
  spinner.style.cssText = `
		border: 5px solid #f3f3f3;
		border-top: 5px solid #3498db;
		border-radius: 50%;
		width: 50px;
		height: 50px;
		animation: spin 2s linear infinite;
		margin-bottom: 20px;
	`;

  // 애니메이션 스타일 추가
  const style = document.createElement('style');
  style.textContent = `
		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
	`;
  document.head.appendChild(style);

  // 메시지 요소
  const messageElement = document.createElement('div');
  messageElement.id = 'loadingMessage';
  messageElement.style.cssText = `
		color: white;
		font-size: 18px;
		text-align: center;
		padding: 10px 20px;
		background-color: rgba(0, 0, 0, 0.7);
		border-radius: 5px;
	`;
  messageElement.textContent = message;

  // 요소들 조합
  overlay.appendChild(spinner);
  overlay.appendChild(messageElement);
  document.body.appendChild(overlay);
}

// ✅ 로딩 메시지 업데이트 함수
function updateLoadingMessage(message) {
  const messageElement = document.getElementById('loadingMessage');
  if (messageElement) {
    messageElement.textContent = message;
  }
}

// ✅ 로딩 오버레이 숨기기 함수
function hideLoadingOverlay() {
  const overlay = document.getElementById('loadingOverlay');
  if (overlay) {
    overlay.remove();
  }
}

// ✅ 알림 메시지 표시 함수
function showAlert(message) {
  const alertBox = document.createElement('div');
  alertBox.style.cssText = `
		position: fixed;
		top: 20px;
		left: 50%;
		transform: translateX(-50%);
		background-color: #f8d7da;
		color: #721c24;
		padding: 15px 20px;
		border-radius: 5px;
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		z-index: 9999;
		text-align: center;
		max-width: 80%;
	`;
  alertBox.textContent = message;

  document.body.appendChild(alertBox);

  // 5초 후 알림 제거
  setTimeout(() => {
    alertBox.remove();
  }, 5000);
}
# 23yellow SSH 환경에서 git 연동하기

## 기존 원격 저장소 확인.
git remote -v

## 기존 origin 제거.
git remote remove origin

## 새로운 원격 저장소 추가. (github https URL 복사)
git remote add origin https://github.com/easygoing2/23yellow.git

## 원격 저장소의 내용을 가져오면서 관련 없는 히스토리도 허용
git pull origin main --allow-unrelated-histories


## 서버 정보 요약

- **운영 체제**: Ubuntu 24.04
- **사용자 계정**: sml
- **설치된 기본 패키지**: procps, locales, tzdata, git, curl, cron, sudo, vim-tiny, iputils-ping, openssh-server
- **NAS 경로**: /opt/file/23yellow
- **호스트 이름**: 23yellow

## 네트워크 설정

- **SSH 포트**:
    - 외부: 40800
    - 내부: 22
- **웹 포트**:
    - 외부: 40801
    - 내부: 80
- **추가 사용 가능 포트**: 40802 ~ 40809 (외부와 내부 포트 번호 동일)
- **HTTP 리디렉션**:
    - http://camping....(80) -> https://camping....(443)
- **웹사이트 접속 정보**:
    - https://camping.23yellow.com/ (현재 Bad Gateway 상태)
    - 외부 443 포트 -> 내부 80 포트 연결

## 추가 정보

- nginx, php 등 필요한 추가 소프트웨어는 사용자가 직접 설치해야 합니다.
- **서비스 자동 시작 등록 방법**:
    - `sudo init-add [서비스 이름]` (예: `sudo init-add nginx`)

## 주의사항

- 현재 웹사이트([https://camping.23yellow.com/)는](https://www.google.com/search?q=https://camping.23yellow.com/)%EB%8A%94) Bad Gateway 상태이므로, 80 포트에 웹 서비스를 설정해야 정상적으로 접근할 수 있습니다.
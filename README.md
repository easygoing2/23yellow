# sm-board-01

board test

## info

- nas shared directory: /opt/file/23yellow

## clone

```bash
cd
cd webroot
create-project sm-board-07
```

## nas link

```bash
cd ${project-root-dir}

rm -rf files # 폴더를 삭제한 다음 아래 링크 명령어 실행해야함.

ln -s /opt/file/23yellow/camping static/files # NAS 서버와 링크. (참고로 크론탭에서 사진이미지 파일들이 files/images 폴더에 저장이 되더라도 자동으로 NAS 서버로 이동함.)

```

# 23yellow
--------------------------------------------------------------------------------
# 기존 원격 저장소 확인.
git remote -v

# 기존 origin 제거.
git remote remove origin

# 새로운 원격 저장소 추가.
git remote add origin https://github.com/easygoing2/23yellow.git

# 원격 저장소의 내용을 가져오면서 관련 없는 히스토리도 허용
git pull origin main --allow-unrelated-histories
--------------------------------------------------------------------------------


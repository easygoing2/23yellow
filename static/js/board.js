console.log("board.js 로드!");
// 이벤트 리스너 등록
document.querySelector(".table tbody").addEventListener("click", function (e) {
	if (e.target.classList.contains("address-link")) {
		handleAddressLinkClick(e);
	}
});

// ✅ [board] 주소 클릭 이벤트 처리
function handleAddressLinkClick(e) {
	console.log("주소 클릭 이벤트 발생!");
	e.preventDefault();

	const link = e.target;
	const mapX = link.getAttribute("data-mapx");
	const mapY = link.getAttribute("data-mapy");
	const transformedX = link.getAttribute("data-transformedX");
	const transformedY = link.getAttribute("data-transformedY");
	const name = link.getAttribute("data-name");
	console.log("name : ", name);
	const address = link.textContent.trim();
	const tel = link.getAttribute("data-tel");
	const homepage = link.getAttribute("data-homepage");
	const contentId = link.getAttribute("data-contentid");
	const intro = link.getAttribute("data-intro");
	const position = new kakao.maps.LatLng(mapY, mapX);
	const message = `<div style="padding:5px;">${name}</div>`;

	// 지도 중심 이동 및 줌
	map.setCenter(position);
	map.setLevel(3);

	// 새 캠핑장 마커 표시
	displayMarker(position, message, false);

	// faclt-detail-wrap 영역 내용 업데이트
	updateFacltDetailWrap(link, name, address, tel, homepage, intro);

	// 이미지 API 호출
	fetchImages(contentId);

	// 블로그 API 호출
	fetchBlogs(contentId, link);

	// iframe에 경로 표시
	showMapInIframe(name, transformedX, transformedY);
}

// faclt-detail-wrap 영역 업데이트
function updateFacltDetailWrap(link, name, address, tel, homepage, intro) {
	const detailWrap = document.querySelector(".faclt-detail-wrap");
	const thumbnail = link.closest("tr").querySelector(".thumbnail").style.backgroundImage.slice(4, -1).replace(/"/g, "");

	// intro가 '정보없음'이거나 비어있는지 확인
	const introDisplay = (!intro || intro === '정보없음') ? 'style="display: none;"' : '';

	detailWrap.innerHTML = `
        <div class="faclt-info-wrap">
            <div class="faclt-thumb">
                <div class="img" style="background-image: url('${thumbnail}')"></div>
            </div>
            <div class="faclt-txt">
                <div class="faclt-tit">${name}</div>
                <div class="faclt-addr">주소 : ${address || "정보없음"}</div>
                <div class="faclt-tel">TEL : ${tel || "정보없음"}</div>
                <div class="faclt-tel">H.P : ${homepage || "정보없음"}</div>
            </div>
        </div>
        <div class="faclt-add-txt" ${introDisplay}>
            <div class="txt-wrap">
                <div class="txt">${intro || "정보없음"}</div>
            </div>
        </div>
        <div class="faclt-img-wrap">
            <div class="detail-img" id="imageListWrap"></div>
        </div>
    `;
}

// 이미지 API 호출
function fetchImages(contentId) {
	const apiImageUrl = `api/images?contentId=${contentId}`;
	fetch(apiImageUrl)
		.then((res) => res.json())
		.then((data) => {
			const imgListWrap = document.getElementById("imageListWrap");
			if (!data || data.length === 0) {
				imgListWrap.innerHTML = `
                    <div class="no-image">
                        <img src="static/img/no-image.png" alt="기본 이미지">
                    </div>
                `;
			} else {
				let html = "";
				data.forEach((img) => {
					const imageUrl = img.local_image_path || "static/img/no-image.png";
					html += `
                        <div class="faclt-image">
                            <img src="${imageUrl}" alt="캠핑장 이미지" onerror="this.onerror=null; this.src='static/img/no-image.png'">
                        </div>
                    `;
				});
				imgListWrap.innerHTML = html;

				// 이미지 로드 후 갤러리 새로고침
				if (typeof refreshImageGallery === 'function') {
					refreshImageGallery();
				}
			}
		})
		.catch((err) => console.error(err));
}

// 블로그 API 호출
function fetchBlogs(contentId, link) {
	const apiUrl = `api/blogs?contentId=${contentId}`;
	fetch(apiUrl)
		.then((response) => response.json())
		.then((blogData) => {
			const detailWrap = document.querySelector(".faclt-detail-wrap");
			let blogHtml = "";

			if (blogData.length > 0) {
				blogData.forEach((blog) => {
					blogHtml += `
                        <div class="contents-wrap">
                            <div class="contents">
                                <img src="${blog.blogThumbnail || link.getAttribute("data-default-image") || "static/img/no-image.png"}" alt="blog image">
                                <div class="review-wrap">
                                    <div class="tit">${blog.blogTitle ? escapeHtml(blog.blogTitle) : "제목 없음"}</div>
                                    <div class="txt">${blog.blogDescription ? escapeHtml(blog.blogDescription) : ""}</div>
                                    <div class="link">
                                        <div class="date">작성일: ${blog.blogPostdate ? escapeAttr(blog.blogPostdate) : "#"}</div>
                                        <a href="${blog.blogLink ? escapeAttr(blog.blogLink) : "#"}" target="_blank">블로그 글 바로가기</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
				});
			} else {
				blogHtml = `<p>블로그 리뷰가 없습니다.</p>`;
			}

			detailWrap.innerHTML += `
                <div class="faclt-review-wrap">
                    ${blogHtml}
                </div>
            `;
		})
		.catch((error) => console.error(error));
}
